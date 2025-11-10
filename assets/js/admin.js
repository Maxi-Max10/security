// Basic interactions for admin dashboard
(function(){
  const modeKey = 'admin_theme_dark';
  const root = document.documentElement;
  function applyMode(){
    const dark = localStorage.getItem(modeKey) === 'true';
    root.classList.remove('dark');
    root.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
    if (dark) root.classList.add('dark');
  }
  if (localStorage.getItem(modeKey) === null) {
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    localStorage.setItem(modeKey, prefersDark ? 'true' : 'false');
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
