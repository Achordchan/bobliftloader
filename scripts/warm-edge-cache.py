#!/usr/bin/env python3
"""Request sitemap pages so the WordPress.com edge cache stops serving copies from before a deploy or a new post.

How the edge cache behaves (measured 2026-11-17):
- It keeps one copy of a page per compression: br (browsers and Googlebot, whose "gzip, deflate, br" counts as br),
  gzip (clients that send "gzip, deflate", such as some other crawlers) and uncompressed. The Accept header plays no part.
- Once a copy is five minutes old, the next request for that copy is answered with it (x-ac: STALE) while a fresh one
  is fetched behind it. A page nobody asks for keeps its old copy, so its next visitor, often a crawler, gets the old page.
- The script used to send no Accept-Encoding, which refreshed only the uncompressed copy nobody uses, so the br and gzip
  copies stayed old. Each page is now requested as br and as gzip.
- Each edge location keeps its own copies; this refreshes the one the runner reaches.
Sitemaps themselves are not stored at the edge (the theme sends no-store), so reading them here always gets the current list.

Usage:
  warm-edge-cache.py SITE                     every page in the sitemaps (after a deploy)
  warm-edge-cache.py SITE --recent HOURS      the busy list pages plus pages whose sitemap <lastmod> is newer than HOURS
                                              (after posts are published; also run on a schedule)
Paced and single-threaded to stay under the host's rate limit (429); a 429 makes it wait, three in a row stop the run.
"""
import argparse
import datetime as dt
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

USER_AGENT = "BOB-LIFT edge cache warmer"
# The value Googlebot and browsers send (the edge serves them the br copy), then the one that gets the gzip copy.
ENCODINGS = ("gzip, deflate, br", "gzip, deflate")
PAUSE = 0.6  # seconds between requests; --pause changes it
stats = {"rate_limited": 0, "statuses": {}}


class RateLimited(Exception):
    pass


def fetch(url: str, encoding: str | None) -> tuple[int, bytes, str]:
    """Status, body and the edge's cache result (HIT, MISS, STALE...). Waits out a 429; three in a row stop the run."""
    headers = {"User-Agent": USER_AGENT}
    if encoding:
        headers["Accept-Encoding"] = encoding
    in_a_row = 0
    for attempt in range(4):
        try:
            with urllib.request.urlopen(urllib.request.Request(url, headers=headers), timeout=60) as response:
                cache = (response.headers.get("x-ac") or "").split(" ")[-1]
                return response.status, response.read(), cache
        except urllib.error.HTTPError as error:
            if error.code != 429:
                return error.code, b"", ""
            stats["rate_limited"] += 1
            in_a_row += 1
            if in_a_row >= 3:
                raise RateLimited(url)
            time.sleep(120)
        except OSError:
            time.sleep(5)
    return 0, b"", ""


def sitemap_entries(site: str) -> tuple[list[tuple[str, str]], int]:
    """(url, lastmod) for every page the sitemaps list, in sitemap order without repeats, and the unreadable sitemap count."""
    status, index, _ = fetch(f"{site}/sitemap.xml", None)
    if status != 200:
        raise SystemExit(f"sitemap index returned {status}")
    text = index.decode("utf-8", "replace")
    # A sitemap index lists child sitemaps; a single sitemap (bobliftloader.com) lists pages directly.
    children = re.findall(r"<sitemap>\s*<loc>([^<]+)</loc>", text)
    bodies, failed = ([text] if not children else []), 0
    for child in children:
        time.sleep(PAUSE)
        status, body, _ = fetch(child, None)
        if status == 200:
            bodies.append(body.decode("utf-8", "replace"))
        else:
            failed += 1
            print(f"{child} returned {status}")
    seen: dict[str, str] = {}
    for body in bodies:
        for block in re.findall(r"<url>(.*?)</url>", body, re.S):
            loc = re.search(r"<loc>([^<]+)</loc>", block)
            lastmod = re.search(r"<lastmod>([^<]+)</lastmod>", block)
            if loc and loc.group(1) not in seen:
                seen[loc.group(1)] = lastmod.group(1) if lastmod else ""
    return list(seen.items()), failed


def is_recent(lastmod: str, hours: float) -> bool:
    try:
        when = dt.datetime.fromisoformat(lastmod.replace("Z", "+00:00"))
    except ValueError:
        return False
    if when.tzinfo is None:
        when = when.replace(tzinfo=dt.timezone.utc)
    return when >= dt.datetime.now(dt.timezone.utc) - dt.timedelta(hours=hours)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("site")
    parser.add_argument("--recent", type=float, help="only list pages and pages changed in the last HOURS")
    parser.add_argument("--lists", default=r"^/(?:[a-z]{2}/)?$",
                        help="regular expression for the paths of list pages that change with every new post")
    parser.add_argument("--pause", type=float, default=PAUSE, help="seconds between requests")
    args = parser.parse_args()
    globals()["PAUSE"] = max(args.pause, PAUSE)
    site = args.site.rstrip("/")

    try:
        entries, failed_sitemaps = sitemap_entries(site)
        if args.recent is not None:
            lists = re.compile(args.lists)
            urls = [url for url, lastmod in entries
                    if lists.search(urllib.parse.urlsplit(url).path) or is_recent(lastmod, args.recent)]
        else:
            urls = [url for url, _ in entries]
        print(f"{len(urls)} pages to warm, {len(ENCODINGS)} compressions each")
        failed = 0
        for url in urls:
            for encoding in ENCODINGS:
                time.sleep(PAUSE)
                status, _, cache = fetch(url, encoding)
                stats["statuses"][cache or "?"] = stats["statuses"].get(cache or "?", 0) + 1
                if status != 200:
                    failed += 1
                    print(f"{url} ({encoding}) returned {status}")
                    break
    except RateLimited as stop:
        print(f"stopped: three 429 answers in a row at {stop}; the host is rate-limiting this runner")
        return 1
    summary = ", ".join(f"{key} {value}" for key, value in sorted(stats["statuses"].items()))
    print(f"warmed {len(urls) - failed} of {len(urls)} pages; edge answers: {summary}; "
          f"{failed_sitemaps} sitemaps unreadable; {stats['rate_limited']} requests rate-limited (429)")
    # Any skipped sitemap or page means some pages may still be stale, so the run fails and shows up in Actions.
    return 1 if failed or failed_sitemaps else 0


if __name__ == "__main__":
    sys.exit(main())
