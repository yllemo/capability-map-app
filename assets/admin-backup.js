(() => {
  const form = document.getElementById('backup-import-form');
  if (!form) return;
  form.addEventListener('submit', (event) => {
    const confirmed = form.elements.namedItem('warning_confirmed');
    confirmed.value = '';
    const overwrite = form.elements.namedItem('existing_files').value === 'overwrite';
    const settings = form.elements.namedItem('restore_settings').checked;
    const filename = form.elements.namedItem('backup').files[0]?.name || 'vald ZIP-fil';
    const message = [
      'VARNING – BEKRÄFTA IMPORT',
      `\nFil: ${filename}`,
      overwrite
        ? '\nSKRIV ÖVER: Backupens kartor ersätter det aktiva kartvalet. Rotfiler med samma namn skrivs över. Tidigare kartkataloger finns kvar på disk men används inte längre.'
        : '\nBEHÅLL: Befintliga kartor och filer behålls. Nya filer och kartor läggs till. Vid filnamns- eller ID-konflikt används befintligt innehåll.',
      settings
        ? '\nKONTON OCH INSTÄLLNINGAR ERSÄTTS: Backupens sparade admininställningar, konton och MCP-nycklar blir aktiva. Du kan förlora åtkomst och behöva logga in med ett konto från backupen.'
        : '\nBefintliga konton, MCP-nycklar och admininställningar behålls.',
      '\nTa en aktuell export först. Inga andra användare bör redigera under importen.',
      '\nVill du starta importen med dessa val?'
    ].join('\n');
    if (!window.confirm(message)) { event.preventDefault(); return; }
    confirmed.value = '1';
  });
})();
