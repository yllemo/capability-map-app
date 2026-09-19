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
  const layerHeadings = [...document.querySelectorAll('[data-layer-heading]')];
  const legendButtons = [...document.querySelectorAll('[data-maturity-legend]')];
  const cards = [...document.querySelectorAll('[data-capability]')];
  const optionKey = 'capmap_overview_card_options';
  const options = [...document.querySelectorAll('[data-card-option]')];
  let savedOptions = {};
  try {
    const saved = JSON.parse(localStorage.getItem(optionKey) || '{}');
    if (saved && typeof saved === 'object' && !Array.isArray(saved)) savedOptions = saved;
  } catch (_) { /* Storage may be disabled or contain an older value. */ }
  options.forEach(option => {
    const field = option.dataset.cardOption;
    if (typeof savedOptions[field] === 'boolean') option.checked = savedOptions[field];
    const elements = [...document.querySelectorAll(`[data-card-field="${field}"]`)];
    const apply = () => elements.forEach(element => { element.hidden = !option.checked; });
    apply();
    option.addEventListener('change', () => {
      apply();
      const values = Object.fromEntries(options.map(input => [input.dataset.cardOption, input.checked]));
      try { localStorage.setItem(optionKey, JSON.stringify(values)); } catch (_) {}
    });
  });
  let layer = '';
  let legendMaturity = '';
  const normalize = value => value.normalize('NFC').toLocaleLowerCase('sv');
  function filter() {
    const query = normalize(search.value.trim());
    let visible = 0;
    cards.forEach(card => {
      const matchesBase = (!layer || card.dataset.layer === layer) && (!area.value || card.dataset.area === area.value) && (!maturity.value || card.dataset.maturity === maturity.value) && normalize(card.dataset.search).includes(query);
      card.hidden = !matchesBase;
      card.classList.toggle('maturity-inactive', matchesBase && !!legendMaturity && card.dataset.maturity !== legendMaturity);
      if (!card.hidden) visible++;
    });
    document.querySelectorAll('.area').forEach(group => { group.hidden = !group.querySelector('[data-capability]:not([hidden])'); });
    document.querySelectorAll('.layer').forEach(section => {
      const count = section.querySelectorAll('[data-capability]:not([hidden])').length;
      section.querySelector('.layer-count').textContent = `${count} förmågor`;
    });
    document.querySelector('#result-count').textContent = `Visar ${visible} av ${cards.length} förmågor`;
    document.querySelector('#empty-state').hidden = visible > 0;
    chips.forEach(chip => chip.setAttribute('aria-pressed', String(chip.dataset.layer === layer)));
    legendButtons.forEach(btn => btn.setAttribute('aria-pressed', String(btn.dataset.maturityLegend === legendMaturity)));
  }
  search.addEventListener('input', filter);
  area.addEventListener('change', filter);
  maturity.addEventListener('change', filter);
  chips.forEach(chip => chip.addEventListener('click', () => { layer = chip.dataset.layer; filter(); }));
  layerHeadings.forEach(btn => {
    const cardsWrap = btn.closest('.layer')?.querySelector('.layer-cards');
    if (!cardsWrap) return;
    btn.addEventListener('click', () => {
      cardsWrap.hidden = !cardsWrap.hidden;
      btn.setAttribute('aria-expanded', String(!cardsWrap.hidden));
    });
  });
  legendButtons.forEach(btn => btn.addEventListener('click', () => { legendMaturity = (legendMaturity === btn.dataset.maturityLegend) ? '' : btn.dataset.maturityLegend; filter(); }));
  document.querySelector('#reset-filters').addEventListener('click', () => { search.value = ''; area.value = ''; maturity.value = ''; layer = ''; legendMaturity = ''; filter(); });
  filter();

  const exportToggle = document.querySelector('#export-menu-toggle');
  const exportContent = document.querySelector('#export-menu-content');
  if (exportToggle && exportContent) {
    const setOpen = open => {
      exportContent.hidden = !open;
      exportToggle.setAttribute('aria-expanded', String(open));
    };
    exportToggle.addEventListener('click', () => setOpen(exportContent.hidden));
    document.addEventListener('click', event => {
      if (!exportContent.hidden && !exportToggle.contains(event.target) && !exportContent.contains(event.target)) setOpen(false);
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && !exportContent.hidden) { setOpen(false); exportToggle.focus(); }
    });
  }
})();
