/* 资料下载: pick the catalogue PDF from the media library and show its name and size before saving. */
(function ($) {
  var input = $('#bl-catalogue-url'), current = $('.bl-dl-current'), clear = $('#bl-catalogue-clear'), frame;
  if (!input.length) return;
  function show(name, size) {
    current.empty();
    if (name) current.append($('<strong class="bl-dl-name">').text(name));
    else current.append($('<span class="bl-chip bl-chip-warn">').text(current.data('default')));
    if (size) current.append(' ', $('<span class="bl-dl-size">').text(size));
    current.append(' ', $('<span class="bl-chip bl-chip-info">').text('未保存'));
    clear.prop('hidden', !name);
  }
  $('#bl-catalogue-pick').on('click', function () {
    frame = frame || wp.media({ title: '选择产品画册 PDF', library: { type: 'application/pdf' }, button: { text: '使用这个 PDF' }, multiple: false });
    frame.off('select').on('select', function () {
      var file = frame.state().get('selection').first().toJSON();
      input.val(file.url);
      show(file.filename, file.filesizeHumanReadable);
    });
    frame.open();
  });
  clear.on('click', function () { input.val(''); show('', ''); });
  input.on('change', function () {
    var url = input.val().trim();
    var name = url.split(/[?#]/)[0].split('/').pop();
    try { name = decodeURIComponent(name); } catch (e) {}
    show(url ? name : '', '');
  });
})(jQuery);
