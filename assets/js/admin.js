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

document.addEventListener('DOMContentLoaded', () => {
  const layout = document.querySelector('.admin-layout');
  if (!layout) return;

  const mobileQuery = window.matchMedia('(max-width: 991.98px)');

  const syncBodyLock = () => {
    document.body.classList.toggle('sidebar-locked', layout.classList.contains('sidebar-open'));
  };

  const closeSidebar = () => {
    layout.classList.remove('sidebar-open');
    syncBodyLock();
  };

  document.addEventListener('click', event => {
    const toggleBtn = event.target.closest('[data-sidebar-toggle]');
    if (toggleBtn){
      event.preventDefault();
      if (mobileQuery.matches){
        layout.classList.toggle('sidebar-open');
        syncBodyLock();
      } else {
        layout.classList.toggle('sidebar-collapsed');
      }
      return;
    }

    const collapseBtn = event.target.closest('[data-sidebar-collapse]');
    if (collapseBtn){
      event.preventDefault();
      layout.classList.toggle('sidebar-collapsed');
      return;
    }

    if (layout.classList.contains('sidebar-open') && !event.target.closest('.sidebar')){
      closeSidebar();
    }
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && layout.classList.contains('sidebar-open')){
      closeSidebar();
    }
  });

  const handleBreakpoint = mq => {
    closeSidebar();
    if (mq.matches){
      layout.classList.remove('sidebar-collapsed');
    }
  };

  if (typeof mobileQuery.addEventListener === 'function'){
    mobileQuery.addEventListener('change', handleBreakpoint);
  } else if (typeof mobileQuery.addListener === 'function'){
    mobileQuery.addListener(handleBreakpoint);
  }

  handleBreakpoint(mobileQuery);
});
