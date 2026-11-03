/**
 * Links in the page body (articles, product pages, cards, other sites, PDFs) open in a new tab, so the visitor keeps
 * the page they were reading. Site controls stay in this tab: menus, breadcrumbs, section and news tabs, pagers,
 * filters, in-page anchors, downloads and links that open a dialog. The listener sits on window, so it runs after every
 * other click handler and leaves alone a link one of them already handled; the target is set just before the browser
 * follows the link, which also covers cards drawn later by scripts. Mark a body link data-same-tab to keep it here.
 */
const sameTabArea = ['nav', '[role="tablist"]', '[data-same-tab-area]', '[data-filter]', '.bl-catalog-filters'].join(',');
const sameTabLink = [
  '[download]', '[target]', '[hreflang]', '[role="tab"]', '[data-same-tab]', '[data-leave-site]', '[data-quick-open]',
  '[data-catalog-clear]', '[data-catalog-leave]', '[aria-haspopup]',
].join(',');

export function initializeNewTabLinks() {
  window.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
    if (!link || !link.closest('main') || link.matches(sameTabLink) || link.closest(sameTabArea)) return;
    if (link.protocol !== 'http:' && link.protocol !== 'https:') return;
    // A link back to this same page (or a spot on it) only scrolls.
    if (link.origin === window.location.origin && link.pathname === window.location.pathname
      && link.search === window.location.search) return;
    link.target = '_blank';
    const rel = new Set((link.rel || '').split(/\s+/).filter(Boolean));
    rel.add('noopener');
    link.rel = [...rel].join(' ');
  });
}
