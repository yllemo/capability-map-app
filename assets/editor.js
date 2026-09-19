(() => {
  // Sidebar search: filter the file list by name, ID, area or path.
  const search = document.querySelector('#editor-search');
  const files = [...document.querySelectorAll('.editor-file')];
  const empty = document.querySelector('#editor-file-empty');
  const countEl = document.querySelector('#editor-file-count');
  const totalCount = files.length;
  const normalize = value => value.normalize('NFC').toLocaleLowerCase('sv');
  function filterFiles() {
    if (!search) return;
    const query = normalize(search.value.trim());
    let visible = 0;
    files.forEach(file => {
      const match = !query || normalize(file.dataset.search || '').includes(query);
      file.hidden = !match;
      if (match) visible++;
    });
    if (empty) empty.hidden = visible > 0;
    if (countEl) countEl.textContent = query ? `${visible} av ${totalCount} filer` : `${totalCount} filer`;
  }
  if (search) {
    search.addEventListener('input', filterFiles);
    filterFiles();
  }

  // Folder switcher.
  const contentDirSelect = document.querySelector('#contentDirSelect');
  if (contentDirSelect) {
    contentDirSelect.addEventListener('change', async (e) => {
      const key = e.target.value;
      try {
        const formData = new FormData();
        formData.append('key', key);
        formData.append('csrf_token', contentDirSelect.dataset.csrf || '');
        const response = await fetch(contentDirSelect.dataset.switchUrl, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
          window.location.reload();
        } else {
          alert('Kunde inte byta katalog: ' + (result.error || 'Okänt fel'));
        }
      } catch (error) {
        console.error('Error switching content directory:', error);
        alert('Ett fel uppstod vid byte av katalog');
      }
    });
  }

  // New folder modal.
  window.showNewFolderModal = function () {
    const modal = document.getElementById('newFolderModal');
    if (modal) { modal.style.display = 'flex'; document.getElementById('folderKey')?.focus(); }
  };
  window.hideNewFolderModal = function () {
    const modal = document.getElementById('newFolderModal');
    if (modal) modal.style.display = 'none';
    document.getElementById('newFolderForm')?.reset();
    const err = document.getElementById('folderError');
    if (err) err.style.display = 'none';
  };
  const newFolderForm = document.getElementById('newFolderForm');
  if (newFolderForm) {
    newFolderForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const errorDiv = document.getElementById('folderError');
      const submitBtn = e.target.querySelector('button[type=submit]');
      submitBtn.disabled = true;
      submitBtn.textContent = 'Skapar…';
      errorDiv.style.display = 'none';
      try {
        const response = await fetch('create_folder.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
          window.location.assign(result.redirect);
        } else {
          errorDiv.textContent = result.error || 'Ett fel uppstod';
          errorDiv.style.display = 'block';
          submitBtn.disabled = false;
          submitBtn.textContent = 'Skapa';
        }
      } catch (error) {
        console.error('Error creating folder:', error);
        errorDiv.textContent = 'Ett fel uppstod vid skapande av folder';
        errorDiv.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Skapa';
      }
    });
  }
  document.getElementById('newFolderModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'newFolderModal') window.hideNewFolderModal();
  });

  // Delete confirmation.
  window.confirmDelete = function (filename) {
    if (confirm('Är du säker på att du vill radera "' + filename + '"?\n\nDenna åtgärd kan inte ångras.')) {
      document.getElementById('deleteForm').submit();
    }
  };

  // Rename modal.
  window.showRenameModal = function (currentName) {
    const newName = prompt('Ange nytt filnamn (utan .md):', currentName);
    if (newName && newName.trim() !== '' && newName !== currentName) {
      document.getElementById('renameNewName').value = newName.trim();
      document.getElementById('renameForm').submit();
    }
  };

  // Raw editor modal.
  window.showRawEditor = function () {
    const modal = document.getElementById('rawEditorModal');
    if (modal) {
      modal.style.display = 'flex';
      document.getElementById('rawContent')?.focus();
    }
  };
  window.closeRawEditor = function () {
    const modal = document.getElementById('rawEditorModal');
    if (modal) modal.style.display = 'none';
  };
  document.getElementById('rawEditorModal')?.addEventListener('click', function (e) {
    if (e.target === this) window.closeRawEditor();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') window.closeRawEditor();
  });
})();
