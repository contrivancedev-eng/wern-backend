/**
 * Theme Switcher with localStorage
 * Toggles 'light-mode' class on body element
 */

(function() {
  'use strict';

  const THEME_STORAGE_KEY = 'theme-preference';
  const LIGHT_MODE_CLASS = 'light-mode';

  /**
   * Apply theme to body
   */
  function applyTheme(isLightMode) {
    if (isLightMode) {
      document.body.classList.add(LIGHT_MODE_CLASS);
    } else {
      document.body.classList.remove(LIGHT_MODE_CLASS);
    }
    // Store preference
    localStorage.setItem(THEME_STORAGE_KEY, isLightMode ? 'light' : 'dark');
  }

  /**
   * Toggle theme
   */
  function toggleTheme() {
    const isLightMode = !document.body.classList.contains(LIGHT_MODE_CLASS);
    applyTheme(isLightMode);
  }

  /**
   * Initialize theme from localStorage
   */
  function initTheme() {
    const savedTheme = localStorage.getItem(THEME_STORAGE_KEY);
    const isLightMode = savedTheme === 'light';
    applyTheme(isLightMode);
  }

  /**
   * Setup toggle button listeners
   */
  function setupToggleButtons() {
    // Handle both desktop and mobile toggle buttons
    document.addEventListener('click', function(e) {
      const toggleButton = e.target.closest('#theme-toggle, #theme-toggle-mobile, .theme-toggle');

      if (toggleButton) {
        e.preventDefault();
        toggleTheme();
      }
    });
  }

  /**
   * Initialize on page load
   */
  function init() {
    initTheme();
    setupToggleButtons();
  }

  // Apply theme immediately to prevent flash
  initTheme();

  // Setup when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose globally for inline onclick handlers
  window.toggleTheme = toggleTheme;

})();
