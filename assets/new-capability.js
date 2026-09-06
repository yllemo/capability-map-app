(() => {
  const upload = document.querySelector('#json-preview-form');
  const form = document.querySelector('#new-capability-form');
  const message = document.querySelector('#json-import-message');
  const choice = document.querySelector('#json-choice');
  const select = document.querySelector('#json-capability');
  const description = document.querySelector('#json-choice-description');
  let items = [];
  let generation = 0;
  const reset = () => {
    generation++;
    items = [];
    select.replaceChildren();
    choice.hidden = true;
    message.textContent = '';
  };
  upload.elements.json_file.addEventListener('change', reset);
  select.addEventListener('change', () => {
    description.textContent = items[Number(select.value)]?.meta.description || '';
  });
  upload.addEventListener('submit', async event => {
    event.preventDefault();
    reset();
    const request = generation;
    const button = upload.querySelector('button');
    const file = upload.elements.json_file.files[0];
    if (!file || file.size > 5 * 1024 * 1024) {
      message.textContent = 'Välj en JSON-fil på högst 5 MB.';
      return;
    }
    button.disabled = true;
    message.textContent = 'Läser filen…';
    try {
      const response = await fetch(upload.action, { method: 'POST', body: new FormData(upload) });
      const result = await response.json();
      if (request !== generation) return;
      if (!response.ok || !result.success) throw new Error(result.error || 'Kunde inte läsa JSON-filen.');
      items = result.items;
      items.forEach((item, index) => {
        const option = document.createElement('option');
        option.value = String(index);
        option.textContent = `${item.meta.source_id} · ${item.meta.name}`;
        select.append(option);
      });
      choice.hidden = false;
      description.textContent = items[0].meta.description;
      message.textContent = `${items.length} förmågor hittades. Välj en och klicka på ”Fyll i formuläret”.`;
    } catch (error) {
      if (request === generation) message.textContent = error.message || 'Kunde inte läsa filen.';
    } finally {
      button.disabled = false;
    }
  });
  document.querySelector('#use-json-capability').addEventListener('click', () => {
    const item = items[Number(select.value)];
    if (!item) return;
    const values = { ...item.meta, body: item.body };
    for (const field of ['layer', 'type', 'level', 'maturity']) {
      if (![...form.elements.namedItem(field).options].some(option => option.value === String(values[field]))) {
        message.textContent = 'Förmågans skikt, typ eller nivå stöds inte av den aktuella konfigurationen.';
        return;
      }
    }
    for (const field of ['id', 'name', 'layer', 'type', 'level', 'area', 'description', 'maturity', 'url', 'body', 'source_id', 'source_status']) {
      form.elements.namedItem(field).value = values[field] ?? '';
    }
    message.textContent = 'Formuläret är ifyllt. Granska uppgifterna och klicka på Skapa för att spara förmågan.';
    form.elements.namedItem('name').focus();
  });
})();
