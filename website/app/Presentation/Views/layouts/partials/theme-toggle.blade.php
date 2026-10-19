<button
    type="button"
    id="theme-toggle"
    aria-pressed="false"
    aria-label="{{ __('ui.toggle_theme') ?? 'Toggle Theme' }}"
>
    <i class="fa-solid fa-sun theme-icon theme-icon-sun" aria-hidden="true"></i>
    <i class="fa-solid fa-moon theme-icon theme-icon-moon" aria-hidden="true"></i>
</button>

<style>
    #theme-toggle {
        position: relative;
        display: inline-block;
        width: 2.5rem;
        height: 2.5rem;
        padding: 0;
        border: 1px solid currentColor;
        border-radius: 0.5rem;
        background: transparent;
        color: inherit;
        cursor: pointer;
        overflow: hidden;
    }

    #theme-toggle:focus-visible {
        outline: 2px solid currentColor;
        outline-offset: 2px;
    }

    #theme-toggle .theme-icon {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: currentColor;
        transition:
            transform 0.35s cubic-bezier(0.4, 0, 0.2, 1),
            opacity 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #theme-toggle .theme-icon-sun {
        transform: translateY(0);
        opacity: 1;
    }
    #theme-toggle .theme-icon-moon {
        transform: translateY(100%);
        opacity: 0;
    }

    html[data-theme='dark'] #theme-toggle .theme-icon-sun {
        transform: translateY(-100%);
        opacity: 0;
    }
    html[data-theme='dark'] #theme-toggle .theme-icon-moon {
        transform: translateY(0);
        opacity: 1;
    }
</style>

<script>
    if (!window.themeToggleInitialized) {
        window.themeToggleInitialized = true;

        const syncAria = () => {
            const btn = document.getElementById('theme-toggle');
            if (btn)
                btn.setAttribute('aria-pressed', document.documentElement.dataset.theme === 'dark');
        };
        syncAria();

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('#theme-toggle');
            if (!btn) return;

            const html = document.documentElement;
            const nextTheme = html.dataset.theme === 'dark' ? 'light' : 'dark';

            html.dataset.theme = nextTheme;
            try {
                localStorage.setItem('theme', nextTheme);
            } catch {}
            syncAria();
        });
    }
</script>
