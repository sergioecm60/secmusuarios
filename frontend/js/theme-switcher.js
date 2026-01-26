/**
 * Theme Switcher - SECM Usuarios
 * Maneja el cambio de tema claro/oscuro/auto
 */

(function() {
    'use strict';

    const THEME_KEY = 'secmusuarios-theme';

    // Detectar preferencia del sistema
    function getSystemPreference() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    // Obtener tema guardado o preferencia del sistema
    function getSavedTheme() {
        return localStorage.getItem(THEME_KEY) || 'auto';
    }

    // Aplicar tema al documento
    function applyTheme(theme) {
        const effectiveTheme = theme === 'auto' ? getSystemPreference() : theme;
        document.documentElement.setAttribute('data-theme', effectiveTheme);
        updateActiveButton(theme);
    }

    // Actualizar boton activo
    function updateActiveButton(theme) {
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        const activeBtn = document.querySelector(`.theme-btn.${theme}`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }

    // Guardar preferencia
    function saveTheme(theme) {
        localStorage.setItem(THEME_KEY, theme);
    }

    // Inicializar
    function init() {
        const savedTheme = getSavedTheme();
        applyTheme(savedTheme);

        // Event listeners para botones
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                let theme = 'light';
                if (this.classList.contains('dark')) {
                    theme = 'dark';
                } else if (this.classList.contains('auto')) {
                    theme = 'auto';
                }

                saveTheme(theme);
                applyTheme(theme);
            });
        });

        // Escuchar cambios en preferencia del sistema
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (getSavedTheme() === 'auto') {
                applyTheme('auto');
            }
        });
    }

    // Toggle password visibility
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.querySelector('.toggle-password');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const passwordInput = document.getElementById('password');
                const icon = this.querySelector('i');

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('bi-eye-fill');
                    icon.classList.add('bi-eye-slash-fill');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('bi-eye-slash-fill');
                    icon.classList.add('bi-eye-fill');
                }
            });
        }
    });

    // Ejecutar cuando el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
