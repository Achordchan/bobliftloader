/**
 * PDFs and pages on other websites open in a new tab, so the visitor keeps this page; links to our own pages open
 * here, as on most sites. The listener sits on window, so it runs after every other click handler and leaves alone a
 * link one of them already handled (dialogs, the sister-site notice). Downloads, links with their own target and links
 * marked data-same-tab (or inside a data-same-tab-area) are not touched.
 */
export function initializeNewTabLinks() {
  window.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
    if (!link || link.matches('[download], [target], [data-same-tab]') || link.closest('[data-same-tab-area]')) return;
    if (link.protocol !== 'http:' && link.protocol !== 'https:') return;
    const isPdf = /\.pdf$/i.test(link.pathname);
    if (!isPdf && link.hostname.replace(/^www\./, '') === window.location.hostname.replace(/^www\./, '')) return;
    link.target = '_blank';
    const rel = new Set((link.rel || '').split(/\s+/).filter(Boolean));
    rel.add('noopener');
    link.rel = [...rel].join(' ');
  });
}
