(function(){
  const key = 'capmap_theme';
  const root = document.documentElement;

  function applyTheme(t){
    root.dataset.theme = t;
    // Tailwind-style darkMode support (class="dark")
    if(t === 'dark') root.classList.add('dark'); else root.classList.remove('dark');
    try{ localStorage.setItem(key, t); }catch(e){}
  }

  function init(){
    let t = null;
    try{ t = localStorage.getItem(key); }catch(e){}
    if(!t){
      t = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    }
    applyTheme(t);
  }

  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('[data-theme-toggle]');
    if(btn){
      const current = root.dataset.theme || 'dark';
      applyTheme(current === 'dark' ? 'light' : 'dark');
      return;
    }

    const fb = e.target.closest('[data-filter-toggle]');
    if(fb){
      const panel = document.getElementById('filterPanel');
      if(panel) panel.classList.toggle('hidden');
      return;
    }

    // close filter panel when clicking outside
    const panel = document.getElementById('filterPanel');
    if(panel && !panel.classList.contains('hidden')){
      const within = e.target.closest('#filterPanel') || e.target.closest('[data-filter-toggle]');
      if(!within) panel.classList.add('hidden');
    }
  });

  init();
})();
