(() => {
  const open = document.querySelector('[data-capability-link-picker]');
  if (!open) return;
  const dialog = document.querySelector('#capability-link-dialog');
  const search = document.querySelector('#capability-link-search');
  const select = document.querySelector('#capability-link-target');
  const label = document.querySelector('#capability-link-label');
  const status = document.querySelector('#capability-link-status');
  const insert = document.querySelector('#capability-link-insert');
  let items = [];
  let initialLabel = '';
  const normalize = text => text.normalize('NFC').toLocaleLowerCase('sv');
  function updateSelection() {
    const item = items[Number(select.value)];
    insert.disabled = !item || select.value === '';
    label.value = insert.disabled ? '' : (initialLabel || item.name);
  }
  function filter() {
    select.replaceChildren();
    const query = normalize(search.value.trim());
    items.forEach((item, index) => {
      if (!normalize(`${item.name} ${item.id} ${item.mapLabel} ${item.map}`).includes(query)) return;
      select.add(new Option(`${item.mapLabel} · ${item.name} · ${item.id}`, String(index)));
    });
    if (select.options.length) select.selectedIndex = 0;
    status.textContent = `${select.options.length} förmågor`;
    updateSelection();
  }
  open.addEventListener('click', async () => {
    const context = { selectedText: '' };
    document.dispatchEvent(new CustomEvent('capability-link-open', { detail: context }));
    initialLabel = context.selectedText;
    items = [];
    search.value = '';
    select.replaceChildren();
    label.value = '';
    insert.disabled = true;
    dialog.showModal();
    search.focus();
    status.textContent = 'Hämtar förmågor…';
    try {
      const response = await fetch(open.dataset.targetsUrl);
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'Kunde inte hämta förmågor.');
      items = data.items;
      filter();
    } catch (error) { status.textContent = error.message; }
  });
  search.addEventListener('input', filter);
  select.addEventListener('change', updateSelection);
  document.querySelector('#capability-link-cancel').addEventListener('click', () => dialog.close());
  insert.addEventListener('click', () => {
    const item = items[Number(select.value)];
    if (!item || insert.disabled) return;
    const text = (label.value.trim() || item.name).replace(/[\r\n]+/g, ' ').replace(/[\\[\]]/g, '\\$&');
    const encode = value => encodeURIComponent(value).replace(/[!'()*]/g, char => `%${char.charCodeAt(0).toString(16).toUpperCase()}`);
    const markdown = `[${text}](cap://${encode(item.map)}/${encode(item.id)})`;
    dialog.close();
    document.dispatchEvent(new CustomEvent('capability-link-insert', { detail: markdown }));
  });
})();
