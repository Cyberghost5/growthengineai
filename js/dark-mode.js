/**
 * GrowthEngineAI - Dark Mode Toggle
 * Handles theme switching between light and dark modes
 */

(function() {
    'use strict';

    // DOM Elements
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = themeToggle ? themeToggle.querySelector('i') : null;
    const htmlElement = document.documentElement;

    // Theme constants
    const THEME_KEY = 'growthengine-theme';
    const DARK_THEME = 'dark';
    const LIGHT_THEME = 'light';

    /**
     * Get the user's preferred theme
     * Priority: localStorage > default (light)
     * We default to light mode for a clean white look
     */
    function getPreferredTheme() {
        // Check localStorage first
        const savedTheme = localStorage.getItem(THEME_KEY);
        if (savedTheme) {
            return savedTheme;
        }

        // Default to light (white) theme
        return LIGHT_THEME;
    }

    /**
     * Apply the theme to the document
     */
    function applyTheme(theme) {
        if (theme === DARK_THEME) {
            htmlElement.setAttribute('data-theme', DARK_THEME);
            updateToggleIcon(true);
        } else {
            htmlElement.setAttribute('data-theme', LIGHT_THEME);
            updateToggleIcon(false);
        }
    }

    /**
     * Update the toggle button icon
     */
    function updateToggleIcon(isDark) {
        if (!themeIcon) return;

        if (isDark) {
            themeIcon.classList.remove('bi-moon-fill');
            themeIcon.classList.add('bi-sun-fill');
            themeToggle.setAttribute('aria-label', 'Switch to light mode');
        } else {
            themeIcon.classList.remove('bi-sun-fill');
            themeIcon.classList.add('bi-moon-fill');
            themeToggle.setAttribute('aria-label', 'Switch to dark mode');
        }
    }

    /**
     * Toggle between light and dark themes
     */
    function toggleTheme() {
        const currentTheme = htmlElement.getAttribute('data-theme');
        const newTheme = currentTheme === DARK_THEME ? LIGHT_THEME : DARK_THEME;

        // Add animation class
        if (themeToggle) {
            themeToggle.classList.add('rotating');
            setTimeout(() => themeToggle.classList.remove('rotating'), 500);
        }

        // Apply and save the new theme
        applyTheme(newTheme);
        localStorage.setItem(THEME_KEY, newTheme);

        // Dispatch custom event for other scripts
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme: newTheme } }));
    }

    /**
     * Initialize the theme system
     */
    function init() {
        // Apply preferred theme immediately (before DOM fully loads)
        const preferredTheme = getPreferredTheme();
        applyTheme(preferredTheme);

        // Set up toggle button click handler
        if (themeToggle) {
            themeToggle.addEventListener('click', toggleTheme);
        }

        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                // Only auto-switch if user hasn't manually set a preference
                if (!localStorage.getItem(THEME_KEY)) {
                    applyTheme(e.matches ? DARK_THEME : LIGHT_THEME);
                }
            });
        }

        // Keyboard support (Enter or Space)
        if (themeToggle) {
            themeToggle.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleTheme();
                }
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Apply theme immediately to prevent flash
    applyTheme(getPreferredTheme());

    // Expose API for external use
    window.GrowthEngineTheme = {
        toggle: toggleTheme,
        setTheme: (theme) => {
            applyTheme(theme);
            localStorage.setItem(THEME_KEY, theme);
        },
        getTheme: () => htmlElement.getAttribute('data-theme') || LIGHT_THEME,
        isDark: () => htmlElement.getAttribute('data-theme') === DARK_THEME
    };

})();
