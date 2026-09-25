/**
 * Medicine-Shield (Oushodh-Shield)
 * Theme Manager: Light, Dark & System Auto Modes
 */

(function () {
  const STORAGE_KEY = 'medicine_shield_theme';

  class ThemeManager {
    constructor() {
      this.theme = localStorage.getItem(STORAGE_KEY) || 'auto';
      this.mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
      this.init();
    }

    init() {
      this.applyTheme(this.theme);
      
      // Listen for OS system theme changes
      this.mediaQuery.addEventListener('change', () => {
        if (this.theme === 'auto') {
          this.applyTheme('auto');
        }
      });

      // Update UI active buttons once DOM is ready
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => this.updateUI());
      } else {
        this.updateUI();
      }
    }

    setTheme(theme) {
      if (!['light', 'dark', 'auto'].includes(theme)) return;
      this.theme = theme;
      localStorage.setItem(STORAGE_KEY, theme);
      this.applyTheme(theme);
      this.updateUI();
    }

    applyTheme(theme) {
      const root = document.documentElement;
      root.setAttribute('data-theme', theme);
      
      if (theme === 'auto') {
        const systemDark = this.mediaQuery.matches;
        root.classList.toggle('dark-mode', systemDark);
        root.classList.toggle('light-mode', !systemDark);
      } else if (theme === 'dark') {
        root.classList.add('dark-mode');
        root.classList.remove('light-mode');
      } else {
        root.classList.add('light-mode');
        root.classList.remove('dark-mode');
      }
    }

    updateUI() {
      const buttons = document.querySelectorAll('.theme-btn');
      buttons.forEach(btn => {
        const targetTheme = btn.getAttribute('data-theme-set');
        if (targetTheme === this.theme) {
          btn.classList.add('active');
        } else {
          btn.classList.remove('active');
        }
      });
    }
  }

  window.themeManager = new ThemeManager();
})();
