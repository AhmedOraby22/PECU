(() => {
  const KEY = 'pecu_theme';

  function isDark() {
    return localStorage.getItem(KEY) === 'dark';
  }

  function applyTheme() {
    const dark = isDark();
    document.body.classList.toggle('dark-theme', dark);
    document.querySelectorAll('.theme-toggle').forEach((btn) => {
      btn.textContent = dark ? '☀️' : '🌙';
      btn.setAttribute('title', dark ? 'تفعيل الوضع الفاتح' : 'تفعيل الوضع الداكن');
    });
  }

  function toggleTheme() {
    localStorage.setItem(KEY, isDark() ? 'light' : 'dark');
    applyTheme();
  }

  function initTheme() {
    applyTheme();
    document.querySelectorAll('.theme-toggle').forEach((btn) => {
      btn.addEventListener('click', toggleTheme);
    });
  }

  window.PECUTheme = { initTheme, applyTheme, toggleTheme };
})();
