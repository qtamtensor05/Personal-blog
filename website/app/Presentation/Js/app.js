const themeStorageKey = 'theme';

function activeTheme() {
    return document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
}

function renderThemeToggle() {
    const theme = activeTheme();
    const nextTheme = theme === 'dark' ? 'light' : 'dark';

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-label', `Switch to ${nextTheme} theme`);
        button.setAttribute('title', `Switch to ${nextTheme} theme`);
        button.querySelector('[data-theme-icon="dark"]')?.classList.toggle('hidden', theme === 'dark');
        button.querySelector('[data-theme-icon="light"]')?.classList.toggle('hidden', theme !== 'dark');
    });
}

function setTheme(theme) {
    document.documentElement.dataset.theme = theme;

    try {
        localStorage.setItem(themeStorageKey, theme);
    } catch {
        // The visual toggle still works when browser storage is unavailable.
    }

    renderThemeToggle();
}

document.addEventListener('DOMContentLoaded', () => {
    renderThemeToggle();

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            setTheme(activeTheme() === 'dark' ? 'light' : 'dark');
        });
    });
});

window.addEventListener('storage', (event) => {
    if (event.key === themeStorageKey && (event.newValue === 'light' || event.newValue === 'dark')) {
        document.documentElement.dataset.theme = event.newValue;
        renderThemeToggle();
    }
});

function initPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        if (button.dataset.initialized === 'true') {
            return;
        }

        const container = button.parentElement;
        const input = container?.querySelector('[data-password-input]');
        const showIcon = button.querySelector('[data-password-show-icon]');
        const hideIcon = button.querySelector('[data-password-hide-icon]');

        if (!input || !showIcon || !hideIcon) {
            return;
        }

        button.dataset.initialized = 'true';

        button.addEventListener('click', () => {
            const showPassword = input.type === 'password';

            input.type = showPassword ? 'text' : 'password';

            button.setAttribute('aria-pressed', String(showPassword));
            button.setAttribute(
                'aria-label',
                `${showPassword ? 'Hide' : 'Show'} password`,
            );

            showIcon.hidden = showPassword;
            hideIcon.hidden = !showPassword;
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPasswordToggles);
} else {
    initPasswordToggles();
}
