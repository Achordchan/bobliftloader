<?php
declare(strict_types=1);

/**
 * Other languages of the site, each in its own folder (/es/). English is the site itself, at the root.
 *
 * A page in another language is the English page drawn again under /es/ and translated on the way out: every piece of
 * text (a paragraph, a heading, a button, alt and aria-label text, the title and description) is looked up in that
 * language's dictionary (inc/i18n/es.php, plus corrections saved in the option bl_i18n_es_strings) and replaced when
 * found. A piece the dictionary does not know stays in English, so a page is only listed in a language once its text has
 * been translated: the pages a language has are BL_I18N_DEFAULT_PAGES, or the option bl_i18n_es_pages once saved. Every
 * other address under /es/ is not found, links to it stay on the English page, and only listed pages carry hreflang.
 *
 * A dictionary key is the text as a reader sees it: entities decoded, runs of white space as one space. Inline markup
 * inside it (a link, <strong>, an accent <span>) becomes numbered placeholders, <t0>…</t0>, and a line break <br>; the
 * translation keeps the same placeholders and the markup goes back where the translator put them.
 */

const BL_I18N_LANGUAGES = [
    'en' => ['name' => 'English', 'hreflang' => 'en', 'locale' => 'en_US', 'zh' => '英语'],
    'es' => ['name' => 'Español', 'hreflang' => 'es', 'locale' => 'es_LA', 'zh' => '西班牙语'],
];

/** The pages each language has until the option bl_i18n_<lang>_pages is saved. */
const BL_I18N_DEFAULT_PAGES = [
    'es' => [
        '/',
        '/about/',
        '/products/skid-steer-loaders/',
        '/products/mini-dumpers/',
        '/applications/',
        '/support/',
        '/dealers/',
        '/contact/',
        '/privacy/',
        '/applications/landscaping/',
        '/applications/site-preparation/',
        '/applications/material-transport/',
        '/applications/gardens-and-homesteads/',
        '/applications/farms-and-orchards/',
        '/applications/building-sites/',
        '/product/bl-t360-h-mini-skid-steer-loader/',
        '/product/bl-t460-h-mini-skid-steer-loader/',
        '/product/bl-t750-h-mini-skid-steer-loader/',
        '/product/bl-s750-h-mini-skid-steer-loader/',
        '/product/bl-t1000-h-mini-skid-steer-loader/',
        '/product/bl-s390-c-mini-skid-steer-loader/',
        '/product/bl-t390-c-mini-skid-steer-loader/',
        '/product/bl-s490-c-mini-skid-steer-loader/',
        '/product/bl-t490-c-mini-skid-steer-loader/',
        '/product/500kg-tracked-mini-dumper/',
        '/product/1-ton-mini-dumper/',
        '/product/1-5-ton-mini-dumper/',
        '/product/2-ton-mini-dumper/',
        '/product/3-ton-mini-dumper/',
        '/product/4-ton-mini-dumper/',
        '/product/5-ton-mini-dumper/',
        '/product/2-ton-2wd-mini-dumper/',
        '/product/3-ton-mini-dumper-with-bucket/',
        '/product/tracked-mini-mixer-truck/',
        '/bl-t360-t460/',
        '/thank-you/',
    ],
];

/** Elements that sit inside a run of text: a sentence keeps them and is translated as one piece. */
const BL_I18N_INLINE = ['a', 'abbr', 'b', 'bdi', 'bdo', 'br', 'cite', 'code', 'data', 'dfn', 'em', 'i', 'kbd', 'mark', 'q', 's', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'u', 'var'];

/** Attributes whose text people read or hear. */
const BL_I18N_ATTRIBUTES = ['alt', 'title', 'aria-label', 'placeholder', 'data-alt', 'data-caption', 'data-gallery-alt']; // data-*: the product gallery's lightbox (assets/js/lightbox.js) shows them

/** Meta tags whose content is page text (search results and link previews). */
const BL_I18N_META = ['description', 'og:title', 'og:description', 'og:image:alt', 'twitter:title', 'twitter:description'];

/** Structured-data properties that hold text. */
const BL_I18N_JSON_TEXT = ['name', 'description', 'headline', 'alternativeHeadline', 'caption', 'text', 'articleSection', 'jobTitle'];

function bl_lang(): string
{
    return (string) ($GLOBALS['bl_lang'] ?? 'en');
}

/** A language other than English, by its code; null for English and for codes the site does not have. */
function bl_i18n_language(string $code): ?array
{
    return $code !== 'en' && isset(BL_I18N_LANGUAGES[$code]) ? BL_I18N_LANGUAGES[$code] : null;
}

/** The front end's root path ('' at the domain root, '/sub' in a subfolder install). */
function bl_i18n_base(): string
{
    return function_exists('home_url') ? rtrim((string) parse_url(home_url('/'), PHP_URL_PATH), '/') : '';
}

/**
 * An address under a language folder is read as the English page it translates: the folder is taken out of the request
 * (so WordPress and the theme's routes find the English page) and remembered as the page's language. Admin, REST,
 * cron and command-line requests are left alone.
 */
function bl_i18n_detect(): void
{
    if (!isset($_SERVER['REQUEST_URI'])) return; // command line
    if ((defined('WP_ADMIN') && WP_ADMIN) || (defined('REST_REQUEST') && REST_REQUEST) || (defined('DOING_CRON') && DOING_CRON)) return;
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
    $base = bl_i18n_base();
    if ($base !== '' && !str_starts_with($path, $base . '/')) return;
    $rest = substr($path, strlen($base));
    foreach (array_keys(BL_I18N_LANGUAGES) as $code) {
        if ($code === 'en') continue;
        if ($rest === '/' . $code) {
            // /es → /es/: the folder's home page has one address.
            header('Location: ' . $base . '/' . $code . '/' . (str_contains($uri, '?') ? substr($uri, strpos($uri, '?')) : ''), true, 301);
            exit;
        }
        if (!str_starts_with($rest, '/' . $code . '/')) continue;
        $GLOBALS['bl_lang'] = $code;
        $GLOBALS['bl_i18n_original_uri'] = $uri;
        $_SERVER['REQUEST_URI'] = $base . substr($uri, strlen($base) + strlen($code) + 1);
        return;
    }
}

/** A site path as one form: a leading and a trailing slash, no query or fragment (/sitemap.html keeps its ending). */
function bl_i18n_normalize_path(string $path): string
{
    $path = (string) (parse_url($path, PHP_URL_PATH) ?? '/');
    $path = '/' . trim($path, '/');
    return $path === '/' || str_contains(basename($path), '.') ? $path : $path . '/';
}

/** The pages a language has: the saved list (Settings → bl_i18n_<lang>_pages), else the defaults. */
function bl_i18n_pages(string $lang): array
{
    if ($lang === 'en') return [];
    static $pages = [];
    if (isset($pages[$lang])) return $pages[$lang];
    $saved = function_exists('get_option') ? get_option('bl_i18n_' . $lang . '_pages') : null;
    $list = is_array($saved) && $saved ? $saved : (BL_I18N_DEFAULT_PAGES[$lang] ?? []);
    return $pages[$lang] = array_values(array_unique(array_map('bl_i18n_normalize_path', array_map('strval', $list))));
}

/** Whether a page (its English path) has a version in this language; every page has an English one. */
function bl_has_language(string $path, string $lang): bool
{
    return $lang === 'en' || (bl_i18n_language($lang) && in_array(bl_i18n_normalize_path($path), bl_i18n_pages($lang), true));
}

/** The path of a page (given by its English path, with any query and fragment) in a language. */
function bl_lang_path(string $path, string $lang): string
{
    if ($lang === 'en' || !bl_i18n_language($lang)) return $path;
    return '/' . $lang . '/' . ltrim($path, '/');
}

/**
 * WordPress's own redirect (redirect_canonical, old_slug_redirect_url) on a page in another language: kept in that
 * language where the destination has a version in it, cancelled (false) where it is a page of this site that the
 * language does not have, so /es/about answers 404 like /es/about/ rather than landing on the English page.
 */
function bl_i18n_redirect($redirect)
{
    $lang = bl_lang();
    if (!is_string($redirect) || $redirect === '' || $lang === 'en') return $redirect;
    $localized = bl_i18n_localize_href($redirect, $lang);
    if ($localized !== $redirect) return $localized;
    $parts = parse_url($redirect);
    if ($parts === false) return $redirect;
    if (isset($parts['host'])) {
        $home = function_exists('home_url') ? (string) parse_url(home_url('/'), PHP_URL_HOST) : '';
        if (preg_replace('/^www\./', '', strtolower((string) $parts['host'])) !== preg_replace('/^www\./', '', strtolower($home))) return $redirect; // another site
    }
    $path = (string) ($parts['path'] ?? '/');
    $base = bl_i18n_base();
    if ($base !== '' && str_starts_with($path, $base . '/')) $path = substr($path, strlen($base));
    if (str_starts_with($path, '/wp-') || str_starts_with($path, '/' . $lang . '/')) return $redirect; // files, admin, already in the language
    return bl_has_language($path, $lang) ? $redirect : false;
}

/** The current page's counterpart in each language: its address, or '' when that language has no such page. */
function bl_i18n_alternates(string $path): array
{
    $alternates = [];
    foreach (array_keys(BL_I18N_LANGUAGES) as $code) $alternates[$code] = bl_has_language($path, $code) ? bl_lang_path($path, $code) : '';
    return $alternates;
}

/**
 * A link as the current language reads it: an address on this site whose page exists in the current language gets that
 * language's folder; anything else (another site, a file, a page not translated yet, an anchor) is returned unchanged.
 */
function bl_i18n_localize_href(string $href, ?string $lang = null): string
{
    $lang ??= bl_lang();
    if ($lang === 'en' || $href === '' || preg_match('~^(#|mailto:|tel:|javascript:|data:)~i', $href)) return $href;
    $parts = parse_url($href);
    if ($parts === false) return $href;
    if (isset($parts['host'])) {
        $home = function_exists('home_url') ? (string) parse_url(home_url('/'), PHP_URL_HOST) : 'bobliftloader.com';
        $strip = static fn(string $host): string => preg_replace('/^www\./', '', strtolower($host));
        if ($strip((string) $parts['host']) !== $strip($home)) return $href;
    } elseif (!str_starts_with($href, '/') || str_starts_with($href, '//')) {
        return $href;
    }
    $base = bl_i18n_base();
    $path = (string) ($parts['path'] ?? '/');
    if ($base !== '') {
        if (!str_starts_with($path, $base . '/') && $path !== $base) return $href;
        $path = substr($path, strlen($base)) ?: '/';
    }
    foreach (array_keys(BL_I18N_LANGUAGES) as $code) if ($code !== 'en' && ($path === '/' . $code || str_starts_with($path, '/' . $code . '/'))) return $href;
    if (str_starts_with($path, '/wp-') || !bl_has_language($path, $lang)) return $href;
    $localized = $base . bl_lang_path($path, $lang);
    $prefix = isset($parts['host']) ? ($parts['scheme'] ?? 'https') . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') : '';
    return $prefix . $localized . (isset($parts['query']) ? '?' . $parts['query'] : '') . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
}

/** The dictionary of a language: the theme's file, with corrections saved in the option bl_i18n_<lang>_strings laid over it. */
function bl_i18n_dictionary(string $lang): array
{
    static $dictionaries = [];
    if (isset($dictionaries[$lang])) return $dictionaries[$lang];
    if (!bl_i18n_language($lang)) return $dictionaries[$lang] = [];
    $file = __DIR__ . '/i18n/' . $lang . '.php';
    $entries = is_file($file) ? (array) require $file : [];
    $saved = function_exists('get_option') ? get_option('bl_i18n_' . $lang . '_strings') : null;
    if (is_array($saved)) foreach ($saved as $original => $text) if (is_string($text) && trim($text) !== '') $entries[(string) $original] = $text;
    $dictionary = [];
    foreach ($entries as $original => $text) $dictionary[bl_i18n_key((string) $original)] = (string) $text;
    $dictionary[BL_I18N_LANG_KEY] = $lang; // which patterns (bl_i18n_patterns) fill in what the entries do not have
    return $dictionaries[$lang] = $dictionary;
}

/** The key under which a dictionary records its language; no text on a page can be it. */
const BL_I18N_LANG_KEY = "\0lang";

/**
 * Text that follows one shape with different figures (dates, photo counters: "Show photograph 3 of 11"), from
 * inc/i18n/{lang}-patterns.php: a list of [regular expression on the key, translation with $1… or a function of the
 * match]. They are tried, in order, only for a key the dictionary does not have.
 */
function bl_i18n_patterns(string $lang): array
{
    static $patterns = [];
    if (isset($patterns[$lang])) return $patterns[$lang];
    $file = __DIR__ . '/i18n/' . $lang . '-patterns.php';
    return $patterns[$lang] = bl_i18n_language($lang) && is_file($file) ? array_values(array_filter((array) require $file, 'is_array')) : [];
}

/** A key's translation: the dictionary's entry, else the first pattern that fits it, else null. */
function bl_i18n_get(array $dictionary, string $key): ?string
{
    if ($key === BL_I18N_LANG_KEY) return null;
    if (isset($dictionary[$key])) return $dictionary[$key];
    foreach (bl_i18n_patterns((string) ($dictionary[BL_I18N_LANG_KEY] ?? '')) as [$pattern, $translation]) {
        if (!preg_match($pattern, $key, $match)) continue;
        $text = is_callable($translation) ? $translation($match) : preg_replace($pattern, (string) $translation, $key);
        if (is_string($text) && $text !== '') return $text;
    }
    return null;
}

/**
 * Whether a piece has words to translate: not only figures and units ("1,500 kg", "17 MPa<t0>2,466 psi</t0>"), an
 * address or a web link.
 */
function bl_i18n_has_words(string $key): bool
{
    if (preg_match('~^(https?://\S*|[\w.+-]+@[\w-]+(\.[\w-]+)+)$~', $key)) return false;
    $text = (string) preg_replace('~</?t\d+>|<br>~', ' ', $key);
    $text = (string) preg_replace('~(?<![\p{L}])(kg|lb|lbs|mm|cm|in|ft|km/h|mph|kW|hp|m³|yd³|ft³|MPa|psi|bar|rpm|cc|Ah|gal|L|l|m|t|V|h)(?![\p{L}])~u', ' ', $text);
    return (bool) preg_match('/\p{Ll}{2}|\p{Lu}\p{Ll}|\p{Lu}{3,}/u', $text);
}

/** Text as a dictionary key: entities decoded, any run of white space (a no-break space too) one space, no space around <br>. */
function bl_i18n_key(string $text): string
{
    return bl_i18n_space(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

/** Decoded text with any run of white space as one space and none around <br>. */
function bl_i18n_space(string $text): string
{
    $text = (string) preg_replace('/[\s\x{00A0}\x{202F}]+/u', ' ', $text);
    return trim((string) preg_replace('~ ?<br> ?~', '<br>', $text));
}

/** A plain-text string (no markup) in the current language, or as given when the dictionary does not have it. */
function bl_t(string $text, ?string $lang = null): string
{
    $lang ??= bl_lang();
    if ($lang === 'en') return $text;
    return bl_i18n_get(bl_i18n_dictionary($lang), bl_i18n_key($text)) ?? $text;
}

/** Pieces the dictionary did not have on this request (read by tools/i18n and the audit comment). */
function bl_i18n_missing(?string $key = null): array
{
    static $missing = [];
    if ($key !== null && bl_i18n_has_words($key)) $missing[$key] = true;
    return array_keys($missing);
}

/** A tag's name and whether it closes, from its source (<a href…>, </a>); null for comments and doctypes. */
function bl_i18n_tag(string $tag): ?array
{
    if (!preg_match('~^<(/?)([a-zA-Z][a-zA-Z0-9:-]*)~', $tag, $match)) return null;
    return [strtolower($match[2]), $match[1] === '/'];
}

/** HTML in pieces: text at even positions, tags (and comments) at odd ones. */
function bl_i18n_tokens(string $html): array
{
    return preg_split('~(<!--.*?-->|<![^>]*>|</?[a-zA-Z][a-zA-Z0-9:-]*(?:[^>"\']|"[^"]*"|\'[^\']*\')*>)~s', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$html];
}

/**
 * A page translated into a language: text runs, attributes people read, meta descriptions, structured data and the
 * page's own links. With $collect, nothing is replaced and the keys found are returned instead (tools/i18n).
 *
 * @return string|array
 */
function bl_i18n_translate_html(string $html, string $lang, bool $collect = false)
{
    $dictionary = $collect ? [] : bl_i18n_dictionary($lang);
    $found = [];
    $note = static function (string $key) use (&$found, $collect): void {
        if ($collect) $found[$key] = true;
        else bl_i18n_missing($key);
    };
    $tokens = bl_i18n_tokens($html);
    $out = '';
    $buffer = [];
    $flush = static function () use (&$buffer, &$out, $dictionary, $note): void {
        $out .= bl_i18n_translate_run($buffer, $dictionary, $note);
        $buffer = [];
    };
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        if ($i % 2 === 0) {
            if ($token !== '') $buffer[] = ['text', $token];
            continue;
        }
        $info = bl_i18n_tag($token);
        if ($info === null) { $flush(); $out .= $token; continue; }
        [$name, $closing] = $info;
        if (!$closing && in_array($name, ['script', 'style', 'textarea', 'title', 'svg', 'template'], true) && !str_ends_with(rtrim($token), '/>')) {
            $flush();
            $inner = '';
            $end = '';
            for ($j = $i + 1; $j < $count; $j++) {
                if ($j % 2 === 1 && ($endInfo = bl_i18n_tag($tokens[$j])) && $endInfo === [$name, true]) { $end = $tokens[$j]; break; }
                $inner .= $tokens[$j];
            }
            if ($name === 'script' && preg_match('~type\s*=\s*["\']application/ld\+json~i', $token)) $inner = bl_i18n_translate_json($inner, $lang, $dictionary, $note);
            elseif ($name === 'script' && preg_match('~\sid\s*=\s*["\']site-search-index["\']~i', $token)) $inner = bl_i18n_translate_search_index($inner, $lang, $dictionary, $note);
            elseif (in_array($name, ['textarea', 'title'], true) && trim($inner) !== '') {
                $key = bl_i18n_key($inner);
                if (($translation = bl_i18n_get($dictionary, $key)) !== null) $inner = bl_escape($translation);
                else $note($key);
            }
            $out .= bl_i18n_translate_tag($token, $name, $lang, $dictionary, $note) . $inner . $end;
            $i = $j;
            continue;
        }
        if (in_array($name, BL_I18N_INLINE, true)) {
            $buffer[] = ['tag', bl_i18n_translate_tag($token, $name, $lang, $dictionary, $note), $name, $closing];
            continue;
        }
        $flush();
        $out .= bl_i18n_translate_tag($token, $name, $lang, $dictionary, $note);
    }
    $flush();
    return $collect ? array_keys($found) : $out;
}

/** One run of text and inline tags between two block boundaries, translated as a whole where the dictionary has it. */
function bl_i18n_translate_run(array $run, array $dictionary, callable $note): string
{
    $raw = static fn(array $tokens): string => implode('', array_map(static fn($token) => $token[1], $tokens));
    $texts = [];
    foreach ($run as $k => $token) if ($token[0] === 'text' && trim(html_entity_decode($token[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B\u{00A0}") !== '') $texts[] = $k;
    if (!$texts) return $raw($run);
    [$first, $last] = [min($texts), max($texts)];
    // Widen the piece until every tag inside it is closed inside it.
    for ($pass = 0; $pass < 8; $pass++) {
        $stack = [];
        $strayClose = null;
        for ($k = $first; $k <= $last; $k++) {
            if ($run[$k][0] !== 'tag' || $run[$k][2] === 'br') continue;
            if (!$run[$k][3]) $stack[] = $k;
            elseif ($stack && $run[end($stack)][2] === $run[$k][2]) array_pop($stack);
            else { $strayClose = $k; break; }
        }
        if ($strayClose !== null) {
            $opener = null;
            for ($k = $first - 1; $k >= 0; $k--) if ($run[$k][0] === 'tag' && !$run[$k][3] && $run[$k][2] === $run[$strayClose][2]) { $opener = $k; break; }
            if ($opener === null) return bl_i18n_translate_nodes($run, $dictionary, $note);
            $first = $opener;
            continue;
        }
        if ($stack) {
            $open = $stack[0];
            $closer = null;
            $depth = 0;
            for ($k = $open + 1; $k < count($run); $k++) {
                if ($run[$k][0] !== 'tag' || $run[$k][2] !== $run[$open][2]) continue;
                if (!$run[$k][3]) $depth++;
                elseif ($depth-- === 0) { $closer = $k; break; }
            }
            if ($closer === null) return bl_i18n_translate_nodes($run, $dictionary, $note);
            $last = max($last, $closer);
            continue;
        }
        break;
    }
    // Opening tags that wrap the whole piece stay outside it (a heading's link, a button's span).
    while ($run[$first][0] === 'tag' && !$run[$first][3] && $run[$first][2] !== 'br') {
        $depth = 0;
        $closer = null;
        for ($k = $first + 1; $k <= $last; $k++) {
            if ($run[$k][0] !== 'tag' || $run[$k][2] !== $run[$first][2]) continue;
            if (!$run[$k][3]) $depth++;
            elseif ($depth-- === 0) { $closer = $k; break; }
        }
        if ($closer !== $last) break;
        $first++;
        $last--;
    }
    // Side-by-side elements with no text of their own between them (menu links, a label's title and note) are
    // separate pieces: each is translated on its own.
    $segments = [];
    $depth = 0;
    $loose = false;
    for ($k = $first; $k <= $last; $k++) {
        $token = $run[$k];
        if ($token[0] === 'text') {
            if ($depth === 0 && trim(html_entity_decode($token[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B\u{00A0}") !== '') $loose = true;
            continue;
        }
        if ($token[2] === 'br') { if ($depth === 0) $loose = true; continue; }
        if (!$token[3]) { if ($depth++ === 0) $segments[] = [$k, null]; }
        elseif (--$depth === 0) $segments[count($segments) - 1][1] = $k;
    }
    if (!$loose && count($segments) > 1) {
        $html = $raw(array_slice($run, 0, $first));
        $at = $first;
        foreach ($segments as [$open, $close]) {
            $html .= $raw(array_slice($run, $at, $open - $at)) . $run[$open][1] . bl_i18n_translate_run(array_slice($run, $open + 1, $close - $open - 1), $dictionary, $note) . $run[$close][1];
            $at = $close + 1;
        }
        return $html . $raw(array_slice($run, $at));
    }
    $core = array_slice($run, $first, $last - $first + 1);
    $parts = [];
    $tags = [];
    $open = [];
    foreach ($core as $token) {
        if ($token[0] === 'text') { $parts[] = html_entity_decode($token[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'); continue; }
        if ($token[2] === 'br') { $parts[] = '<br>'; continue; }
        if (!$token[3]) { $n = count($tags); $tags[$n] = [$token[1], '']; $open[] = $n; $parts[] = '<t' . $n . '>'; continue; }
        $n = array_pop($open);
        $tags[$n][1] = $token[1];
        $parts[] = '</t' . $n . '>';
    }
    $key = bl_i18n_space(implode('', $parts));
    $lead = $trail = '';
    if ($core[0][0] === 'text') $lead = (string) preg_replace('/^([\s\x{00A0}]*).*$/su', '$1', $core[0][1]);
    $end = $core[count($core) - 1];
    if ($end[0] === 'text') $trail = (string) preg_replace('/^.*?([\s\x{00A0}]*)$/su', '$1', $end[1]);
    $translation = bl_i18n_get($dictionary, $key);
    if ($translation !== null && ($rebuilt = bl_i18n_rebuild($translation, $tags)) !== null) {
        return $raw(array_slice($run, 0, $first)) . $lead . $rebuilt . $trail . $raw(array_slice($run, $last + 1));
    }
    if ($tags) {
        // Not as a whole: each text node on its own, and the whole piece noted (a translator sees the sentence).
        $note($key);
        return bl_i18n_translate_nodes($run, $dictionary, static function (): void {});
    }
    $note($key);
    return $raw($run);
}

/** A translation with its placeholders turned back into the page's own tags; null when a placeholder is lost or repeated. */
function bl_i18n_rebuild(string $translation, array $tags): ?string
{
    $pieces = preg_split('~(</?t\d+>|<br\s*/?>)~', $translation, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
    $seen = [];
    $html = '';
    foreach ($pieces as $k => $piece) {
        if ($k % 2 === 0) { $html .= bl_escape(html_entity_decode($piece, ENT_QUOTES | ENT_HTML5, 'UTF-8')); continue; }
        if (str_starts_with($piece, '<br')) { $html .= '<br>'; continue; }
        preg_match('~^<(/?)t(\d+)>$~', $piece, $match);
        $n = (int) $match[2];
        if (!isset($tags[$n]) || isset($seen[$piece])) return null;
        $seen[$piece] = true;
        $html .= $match[1] === '/' ? $tags[$n][1] : $tags[$n][0];
    }
    return count($seen) === 2 * count($tags) ? $html : null;
}

/** A run whose sentence the dictionary does not have: each text node translated on its own where it can be. */
function bl_i18n_translate_nodes(array $run, array $dictionary, callable $note): string
{
    $html = '';
    foreach ($run as $token) {
        if ($token[0] !== 'text' || trim($token[1]) === '') { $html .= $token[1]; continue; }
        $key = bl_i18n_key($token[1]);
        if (($found = bl_i18n_get($dictionary, $key)) === null) { $note($key); $html .= $token[1]; continue; }
        preg_match('/^([\s\x{00A0}]*)(.*?)([\s\x{00A0}]*)$/su', $token[1], $match);
        $html .= $match[1] . bl_escape($found) . $match[3];
    }
    return $html;
}

/** A tag with its readable attributes translated and, for links and forms, the address in the page's language. */
function bl_i18n_translate_tag(string $tag, string $name, string $lang, array $dictionary, callable $note): string
{
    if (str_starts_with($tag, '</') || !str_contains($tag, '=')) return $tag;
    $attributes = [];
    preg_match_all('~\s([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*("[^"]*"|\'[^\']*\')~', $tag, $all, PREG_SET_ORDER);
    foreach ($all as $match) $attributes[strtolower($match[1])] = html_entity_decode(substr($match[2], 1, -1), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $translate = [];
    foreach (BL_I18N_ATTRIBUTES as $attribute) if (isset($attributes[$attribute])) $translate[] = $attribute;
    if ($name === 'meta' && in_array($attributes['name'] ?? $attributes['property'] ?? '', BL_I18N_META, true)) $translate[] = 'content';
    if ($name === 'input' && in_array(strtolower($attributes['type'] ?? ''), ['submit', 'button'], true)) $translate[] = 'value';
    $links = [];
    // A link that names its language (the language switch: hreflang) already points where it should.
    if ($name === 'a' && isset($attributes['href']) && !isset($attributes['hreflang'])) $links[] = 'href';
    if ($name === 'form' && isset($attributes['action'])) $links[] = 'action';
    if (!$translate && !$links) return $tag;
    return (string) preg_replace_callback('~(\s)([a-zA-Z_:][-a-zA-Z0-9_:.]*)(\s*=\s*)("[^"]*"|\'[^\']*\')~', static function (array $match) use ($translate, $links, $lang, $dictionary, $note): string {
        $attribute = strtolower($match[2]);
        $value = html_entity_decode(substr($match[4], 1, -1), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (in_array($attribute, $links, true)) {
            $localized = bl_i18n_whatsapp_text(bl_i18n_localize_href($value, $lang), $dictionary);
            return $localized === $value ? $match[0] : $match[1] . $match[2] . $match[3] . '"' . bl_escape($localized) . '"';
        }
        if (!in_array($attribute, $translate, true) || trim($value) === '') return $match[0];
        $key = bl_i18n_key($value);
        if (($found = bl_i18n_get($dictionary, $key)) === null) { $note($key); return $match[0]; }
        return $match[1] . $match[2] . $match[3] . '"' . bl_escape($found) . '"';
    }, $tag);
}

/** A WhatsApp link's ready-written message, translated when the dictionary has it. */
function bl_i18n_whatsapp_text(string $href, array $dictionary): string
{
    if (!preg_match('~^https://(wa\.me|api\.whatsapp\.com)/~', $href) || !preg_match('~([?&]text=)([^&#]*)~', $href, $match)) return $href;
    $key = bl_i18n_key(rawurldecode(str_replace('+', '%20', $match[2])));
    return ($found = bl_i18n_get($dictionary, $key)) !== null ? str_replace($match[0], $match[1] . rawurlencode($found), $href) : $href;
}

/** Structured data with its text translated and its breadcrumb addresses in the page's language. */
function bl_i18n_translate_json(string $json, string $lang, array $dictionary, callable $note): string
{
    $data = json_decode(trim($json), true);
    if (!is_array($data)) return $json;
    $walk = static function ($value, $key = null) use (&$walk, $lang, $dictionary, $note) {
        if (is_array($value)) {
            foreach ($value as $k => $child) $value[$k] = $walk($child, $k);
            return $value;
        }
        if (!is_string($value) || !is_string($key)) return $value;
        if ($key === 'item') return bl_i18n_localize_href($value, $lang);
        if (!in_array($key, BL_I18N_JSON_TEXT, true) || trim($value) === '') return $value;
        $k = bl_i18n_key($value);
        if (($found = bl_i18n_get($dictionary, $k)) !== null) return html_entity_decode($found, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $note($k);
        return $value;
    };
    return (string) json_encode($walk($data), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
}

/**
 * The header search's index (templates/partials/site-search.php): what each result says, in the page's language, and
 * its address in that language where the page has one. Only pages that exist in the language report missing text, so
 * the audit is not flooded by the English-only news.
 */
function bl_i18n_translate_search_index(string $json, string $lang, array $dictionary, callable $note): string
{
    $entries = json_decode(trim($json), true);
    if (!is_array($entries)) return $json;
    $translate = static function (string $text, bool $report) use ($dictionary, $note): string {
        if (trim($text) === '') return $text;
        $key = bl_i18n_key($text);
        if (($found = bl_i18n_get($dictionary, $key)) !== null) return html_entity_decode($found, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($report && !in_array($key, $dictionary, true)) $note($key); // text the template already wrote in the language is not missing
        return $text;
    };
    foreach ($entries as &$entry) {
        if (!is_array($entry)) continue;
        $url = is_string($entry['url'] ?? null) ? $entry['url'] : '';
        $localized = bl_i18n_localize_href($url, $lang);
        $entry['url'] = $localized;
        $translated = $localized !== $url;
        if (is_string($entry['kind'] ?? null)) $entry['kind'] = $translate($entry['kind'], true);
        // The English words still find and rank the result: assets/js/search.js matches and scores en.title and en.text
        // as it does the title and text.
        $english = [];
        foreach (['title', 'text'] as $field) if (is_string($entry[$field] ?? null)) {
            $original = $entry[$field];
            $entry[$field] = $translate($original, $translated);
            if ($entry[$field] !== $original) $english[$field] = $original;
        }
        if ($english) $entry['en'] = $english;
    }
    unset($entry);
    return (string) json_encode($entries, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * A saved translation or its key (option bl_i18n_{code}_strings), safe and in the dictionary's own form: plain text
 * with only the placeholders <t0>…</t19> and <br>, which keep no attributes.
 */
function bl_i18n_clean_markup(string $text): string
{
    $placeholders = array_merge(['br'], array_map(static fn(int $n): string => 't' . $n, range(0, 19)));
    $text = strip_tags($text, $placeholders);
    $text = (string) preg_replace_callback('~<(/?)(t\d+|br)\b[^>]*>~i', static fn(array $m): string => strtolower($m[2]) === 'br' ? '<br>' : '<' . $m[1] . strtolower($m[2]) . '>', $text);
    return trim($text);
}

/** The option bl_i18n_{code}_strings as saved: English original => translation, both cleaned, empty ones dropped. */
function bl_i18n_sanitize_strings($value): array
{
    $clean = [];
    foreach (is_array($value) ? $value : [] as $original => $text) {
        if (!is_string($text)) continue;
        $key = bl_i18n_key(bl_i18n_clean_markup((string) $original));
        $text = bl_i18n_clean_markup($text);
        if ($key !== '' && $text !== '') $clean[$key] = $text;
    }
    return $clean;
}

/** The page buffer's callback on a page in another language. */
function bl_i18n_output(string $html): string
{
    $lang = bl_lang();
    if ($lang === 'en' || $html === '') return $html;
    $translated = (string) bl_i18n_translate_html($html, $lang);
    // An administrator (or a local run) can see what is still English: ?bl_i18n_audit=1 lists it in a comment at the end.
    $audit = isset($_GET['bl_i18n_audit']) && (!function_exists('current_user_can') || current_user_can('manage_options'));
    // Each line escaped: decoded page text may hold "-->" or "<", which must not end the comment or start markup.
    if ($audit) $translated .= "\n<!-- bl-i18n-missing\n" . implode("\n", array_map(static fn(string $line): string => str_replace('-', '&#45;', bl_escape($line)), bl_i18n_missing())) . "\n-->";
    return $translated;
}

/** Strings the theme's scripts show, in the page's language, for assets/js/i18n.js. */
const BL_I18N_SCRIPT_STRINGS = [
    'Open navigation', 'Close navigation', 'Pause the slideshow', 'Play the slideshow', 'Show all specs', 'Show fewer specs', 'result', 'results',
    'The enquiry could not be sent. Please check your connection and try again, or email us directly.',
    'Please enter a value, not only spaces.', 'Please complete the verification and send again.',
    'Move the slider to the load you carry per trip.', '{hits} of {total} {noun} are rated for {limit} kg or more', 'Any', 'dumpers', 'loaders', 'models', 'model',
    // The email-draft form used while the inquiry plugin is off (templates/partials/inquiry-form.php, assets/js/inquiry.js).
    'Equipment', 'Model', 'Name', 'Email', 'Country', 'Company', 'Project', 'Skid steer loader', 'Mini dumper', 'Help choosing equipment', 'Parts & service',
    'Enquiry copied. Paste it into an email to {email}.', 'Your browser could not copy automatically. Select and copy the details above, then email {email}.',
];

/** The script dictionary as a JSON island; empty in English. */
function bl_i18n_script_dictionary(): string
{
    $lang = bl_lang();
    if ($lang === 'en') return '';
    $strings = [];
    foreach (BL_I18N_SCRIPT_STRINGS as $text) if (($translated = bl_t($text, $lang)) !== $text) $strings[$text] = $translated;
    return '<script type="application/json" id="bl-i18n">' . json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
}

bl_i18n_detect();
