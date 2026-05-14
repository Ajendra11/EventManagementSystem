/**
 * EventHub Theme Controller
 * - Applies theme immediately to prevent dark-mode flicker
 * - One light/dark preference for the whole app
 * - Respects system preference when the user has not chosen
 * - Persists explicit user choice in localStorage
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'eventhub_theme';
    var root = document.documentElement;
    var mediaQuery = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function systemTheme() {
        return mediaQuery && mediaQuery.matches ? 'dark' : 'light';
    }

    function storedTheme() {
        try {
            var value = localStorage.getItem(STORAGE_KEY);
            return value === 'dark' || value === 'light' ? value : null;
        } catch (error) {
            return null;
        }
    }

    function preferredTheme() {
        return storedTheme() || systemTheme();
    }

    function setStoredTheme(theme) {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (error) {
            // Storage can be blocked. The theme still applies for this page.
        }
    }

    function iconFor(theme) {
        return theme === 'dark' ? '🌙' : '☀️';
    }

    function labelFor(theme) {
        return theme === 'dark' ? 'Dark' : 'Light';
    }

    function updateThemeButtons(theme) {
        var nextTheme = theme === 'dark' ? 'light' : 'dark';

        document.querySelectorAll('[data-theme-toggle], [data-admin-theme-toggle]').forEach(function (button) {
            var icon = button.querySelector('[data-theme-icon], [data-admin-theme-icon]');
            var label = button.querySelector('[data-theme-label], [data-admin-theme-label], .label');

            if (icon) {
                icon.textContent = iconFor(theme);
            }

            if (label) {
                label.textContent = labelFor(theme);
            }

            button.setAttribute(
                'aria-label',
                'Current theme: ' + labelFor(theme) + '. Switch to ' + labelFor(nextTheme) + ' theme'
            );

            button.setAttribute('title', 'Switch to ' + labelFor(nextTheme) + ' theme');
            button.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        });
    }

    function applyTheme(theme, persist) {
        var safeTheme = theme === 'dark' ? 'dark' : 'light';

        root.setAttribute('data-theme', safeTheme);
        root.style.colorScheme = safeTheme;

        if (persist) {
            setStoredTheme(safeTheme);
        }

        if (document.readyState !== 'loading') {
            updateThemeButtons(safeTheme);
        }

        window.dispatchEvent(new CustomEvent('eventhub:theme-change', {
            detail: { theme: safeTheme }
        }));
    }

    function toggleTheme() {
        var current = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        var next = current === 'dark' ? 'light' : 'dark';

        applyTheme(next, true);
    }

    /* IMPORTANT: apply before DOMContentLoaded to prevent black-theme flicker */
    applyTheme(preferredTheme(), false);

    document.addEventListener('DOMContentLoaded', function () {
        updateThemeButtons(root.getAttribute('data-theme') || preferredTheme());

        document.querySelectorAll('[data-theme-toggle], [data-admin-theme-toggle]').forEach(function (button) {
            button.addEventListener('click', toggleTheme);
        });
    });

    if (mediaQuery) {
        var onSystemThemeChange = function () {
            if (!storedTheme()) {
                applyTheme(systemTheme(), false);
            }
        };

        if (typeof mediaQuery.addEventListener === 'function') {
            mediaQuery.addEventListener('change', onSystemThemeChange);
        } else if (typeof mediaQuery.addListener === 'function') {
            mediaQuery.addListener(onSystemThemeChange);
        }
    }

    window.EventHubTheme = {
        apply: function (theme) {
            applyTheme(theme, true);
        },

        current: function () {
            return root.getAttribute('data-theme') || preferredTheme();
        },

        clearPreference: function () {
            try {
                localStorage.removeItem(STORAGE_KEY);
            } catch (error) {}

            applyTheme(systemTheme(), false);
        }
    };
}());