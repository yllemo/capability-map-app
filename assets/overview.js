(() => {
  const mapSelect = document.querySelector('#map-select');
  if (mapSelect) {
    mapSelect.addEventListener('change', () => mapSelect.form.requestSubmit());
    mapSelect.form.querySelector('button[type="submit"]').hidden = true;
  }
  const search = document.querySelector('#overview-search');
  const area = document.querySelector('#area-filter');
  const maturity = document.querySelector('#maturity-filter');
  const chips = [...document.querySelectorAll('button[data-layer]')];
  const cards = [...document.querySelectorAll('[data-capability]')];
  let layer = '';
  const normalize = value => value.normalize('NFC').toLocaleLowerCase('sv');
  function filter() {
    const query = normalize(search.value.trim());
    let visible = 0;
    cards.forEach(card => {
      card.hidden = !((!layer || card.dataset.layer === layer) && (!area.value || card.dataset.area === area.value) && (!maturity.value || card.dataset.maturity === maturity.value) && normalize(card.dataset.search).includes(query));
      if (!card.hidden) visible++;
    });
    document.querySelectorAll('.area').forEach(group => { group.hidden = !group.querySelector('[data-capability]:not([hidden])'); });
    document.querySelectorAll('.layer').forEach(section => {
      const count = section.querySelectorAll('[data-capability]:not([hidden])').length;
      section.hidden = !count;
      section.querySelector('.layer-count').textContent = `${count} förmågor`;
    });
    document.querySelector('#result-count').textContent = `Visar ${visible} av ${cards.length} förmågor`;
    document.querySelector('#empty-state').hidden = visible > 0;
    chips.forEach(chip => chip.setAttribute('aria-pressed', String(chip.dataset.layer === layer)));
  }
  search.addEventListener('input', filter);
  area.addEventListener('change', filter);
  maturity.addEventListener('change', filter);
  chips.forEach(chip => chip.addEventListener('click', () => { layer = chip.dataset.layer; filter(); }));
  document.querySelector('#reset-filters').addEventListener('click', () => { search.value = ''; area.value = ''; maturity.value = ''; layer = ''; filter(); });
  filter();
})();
