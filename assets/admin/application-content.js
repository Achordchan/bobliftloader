(() => {
  'use strict';
  const panel = document.querySelector('[data-bl-new-application]');
  if (!panel) return;
  const title = panel.querySelector('[name="bl_content[new_jobs][0][title]"]');
  const slug = panel.querySelector('[name="bl_content[new_jobs][0][slug]"]');
  let edited = slug.value !== '';
  slug.addEventListener('input', () => { edited = slug.value !== ''; });
  title.addEventListener('input', () => {
    if (!edited) slug.value = title.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  });
})();
