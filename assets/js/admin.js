// Basic interactions for admin dashboard
(function(){
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const modeKey = 'admin_theme_dark';
  const root = document.documentElement;
  function applyMode(){
    const dark = localStorage.getItem(modeKey) === 'true';
    root.classList.toggle('dark', dark);
  }
  applyMode();
  document.addEventListener('click', e => {
    if (e.target.matches('[data-toggle-theme]')){
      const current = localStorage.getItem(modeKey) === 'true';
      localStorage.setItem(modeKey, (!current).toString());
      applyMode();
    }
  });
})();
