/* Download rows share the content editor's ordering, dirty tracking and model selector. */
(function () {
  'use strict';
  var form = document.querySelector('.bl-downloads .bl-ce-form');
  if (!form) return;
  form.addEventListener('click', function (event) {
    var button = event.target.closest('[data-bl-download-pick]');
    if (!button) return;
    var row = button.closest('.bl-ce-row');
    var frame = wp.media({ title: '选择资料文件', button: { text: '使用这个文件' }, multiple: false });
    frame.on('select', function () {
      var file = frame.state().get('selection').first().toJSON();
      var input = row.querySelector('[name$="[url]"]');
      input.value = file.url;
      input.dispatchEvent(new Event('change', { bubbles: true }));
      row.querySelector('[data-bl-download-size]').textContent = file.filesizeHumanReadable || '大小未知';
    });
    frame.open();
  });
  function update(event) {
    var row = event.target.closest('.bl-ce-row');
    if (!row) return;
    if (event.target.matches('[name$="[scope]"]')) row.querySelector('[data-bl-download-models]').hidden = event.target.value !== 'models';
    if (event.target.matches('[name$="[url]"]')) row.querySelector('[data-bl-download-size]').textContent = '保存后自动读取';
  }
  form.addEventListener('change', update);
  form.addEventListener('input', update);
})();
