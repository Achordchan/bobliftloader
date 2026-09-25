/**
 * Family page range chart: the marker is the visitor's own number (narrowest opening for loaders, load per trip for
 * dumpers). Moving the slider moves the marker and recolours the rows as templates/partials/range-story.php does.
 */
export function initializeRuler() {
  const form = document.querySelector('[data-ruler-control]');
  const figure = document.querySelector('[data-ruler]');
  if (!form || !figure) return;
  const input = form.querySelector('input[type="range"]');
  const width = input.name === 'opening';
  const { unit, noun } = form.dataset;
  const from = Number(figure.dataset.from);
  const to = Number(figure.dataset.to);
  const number = (value) => value.toLocaleString('en-US');
  const rows = [...figure.querySelectorAll('[data-ruler-row]')].map((element) => ({
    element,
    value: Number(element.dataset.value),
    sub: Number(element.dataset.sub),
    note: element.querySelector('[data-ruler-note]'),
  }));

  const update = () => {
    const limit = Number(input.value);
    const marked = width || limit > 0;
    input.style.setProperty('--p', `${((limit - input.min) / (input.max - input.min)) * 100}%`);
    figure.classList.toggle('bl-ruler-marked', marked);
    figure.style.setProperty('--marker', `${((limit - from) / (to - from)) * 100}%`);
    form.querySelector('[data-ruler-out]').textContent = limit ? `${number(limit)} ${unit}` : 'Any';

    let hits = 0;
    let clean = 0;
    rows.forEach((row) => {
      const hit = width ? row.value <= limit : !limit || row.value >= limit;
      const over = width && hit && row.sub > limit;
      row.element.classList.toggle('bl-ruler-hit', hit);
      row.element.classList.toggle('bl-ruler-miss', !hit);
      row.element.classList.toggle('bl-ruler-over', over);
      if (width && row.note && row.sub) row.note.textContent = `Bucket ${number(row.sub)} mm${over ? `\n${number(row.sub - limit)} mm over` : ''}`;
      hits += hit;
      clean += hit && !over;
    });
    form.querySelector('[data-ruler-summary]').textContent = width
      ? `${hits} of ${rows.length} ${noun} fit through ${number(limit)} mm · ${clean} with the bucket fitted`
      : limit ? `${hits} of ${rows.length} ${noun} are rated for ${number(limit)} kg or more` : 'Move the slider to the load you carry per trip.';

    const params = new URLSearchParams();
    if (!width) params.set('equipment', 'dumper');
    if (limit) params.set(width ? 'width' : 'load', limit);
    form.querySelector('[data-ruler-ask]').href = `${form.dataset.contact}?${params}#enquiry`;
  };

  input.addEventListener('input', update);
  form.addEventListener('submit', (event) => { event.preventDefault(); update(); });
}
