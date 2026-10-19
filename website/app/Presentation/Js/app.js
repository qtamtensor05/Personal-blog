const themeStorageKey = 'theme';

/**
 * Server-rendered pages do not need a loading skeleton: their content is already
 * present in the HTML. Use GSAP only for a short entrance transition, and leave
 * content visible when the CDN is unavailable or motion is reduced.
 */
function initPageAnimations() {
    const gsap = window.gsap;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!gsap || reduceMotion) return;

    const pageTargets = document.querySelectorAll('[data-animate="page"]');
    if (pageTargets.length) {
        gsap.from(pageTargets, {
            autoAlpha: 0,
            y: 10,
            duration: 0.42,
            ease: 'power2.out',
            clearProps: 'opacity,visibility,transform',
        });
    }

    const cards = [...document.querySelectorAll('[data-animate="post-card"]')];
    if (cards.length) {
        gsap.from(cards, {
            autoAlpha: 0,
            y: 14,
            duration: 0.38,
            stagger: 0.055,
            delay: pageTargets.length ? 0.08 : 0,
            ease: 'power2.out',
            clearProps: 'opacity,visibility,transform',
        });
    }

    const sections = [...document.querySelectorAll('[data-animate="section"]')];
    if (sections.length) {
        gsap.from(sections, {
            autoAlpha: 0,
            y: 8,
            duration: 0.35,
            stagger: 0.06,
            delay: 0.1,
            ease: 'power1.out',
            clearProps: 'opacity,visibility,transform',
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPageAnimations, { once: true });
} else {
    initPageAnimations();
}

function initImageLoadingStates() {
    document.querySelectorAll('[data-image-frame]').forEach((frame) => {
        const image = frame.querySelector('[data-image-reveal]');
        if (!image) return;

        const reveal = () => {
            frame.classList.remove('is-loading');
            image.classList.remove('is-loading');
            image.classList.add('is-loaded');
        };
        const fail = () => {
            frame.classList.remove('is-loading');
            image.classList.remove('is-loading');
        };

        if (image.complete) {
            if (image.naturalWidth > 0) reveal();
            else fail();
            return;
        }

        frame.classList.add('is-loading');
        image.classList.add('is-loading');
        image.addEventListener('load', reveal, { once: true });
        image.addEventListener('error', fail, { once: true });
    });

    // Images rendered from the sanitized Tiptap document do not have a wrapper,
    // so use a subtle blur-to-sharp transition rather than a skeleton overlay.
    document.querySelectorAll('.post-content img').forEach((image) => {
        const reveal = () => {
            image.classList.remove('is-loading');
            image.classList.add('is-loaded');
        };
        const fail = () => image.classList.remove('is-loading');

        if (image.complete) {
            if (image.naturalWidth > 0) reveal();
            return;
        }

        image.classList.add('is-loading');
        image.addEventListener('load', reveal, { once: true });
        image.addEventListener('error', fail, { once: true });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initImageLoadingStates, { once: true });
} else {
    initImageLoadingStates();
}

function activeTheme() {
    return document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
}

function renderThemeToggle() {
    const theme = activeTheme();
    const nextTheme = theme === 'dark' ? 'light' : 'dark';

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-label', `Switch to ${nextTheme} theme`);
        button.setAttribute('title', `Switch to ${nextTheme} theme`);
        button
            .querySelector('[data-theme-icon="dark"]')
            ?.classList.toggle('hidden', theme === 'dark');
        button
            .querySelector('[data-theme-icon="light"]')
            ?.classList.toggle('hidden', theme !== 'dark');
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
    if (
        event.key === themeStorageKey &&
        (event.newValue === 'light' || event.newValue === 'dark')
    ) {
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
            button.setAttribute('aria-label', `${showPassword ? 'Hide' : 'Show'} password`);

            showIcon.hidden = showPassword;
            hideIcon.hidden = !showPassword;
        });
    });
}

function initErrorModal() {
    const modal = document.querySelector('[data-modal]');
    if (!modal || modal.dataset.initialized === 'true') return;

    const panel = modal.querySelector('[role="alertdialog"]');
    const title = modal.querySelector('[id$="-title"]');
    const message = modal.querySelector('[data-modal-message]');
    const input = modal.querySelector('[data-modal-input]');
    const cancel = modal.querySelector('[data-modal-cancel]');
    const confirm = modal.querySelector('[data-modal-confirm]');
    let pending = null;
    const close = (value = null) => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        input.classList.add('hidden');
        cancel.classList.add('hidden');
        confirm.textContent = 'Close';
        if (pending) {
            pending(value);
            pending = null;
        }
    };

    modal.dataset.initialized = 'true';
    modal
        .querySelectorAll('[data-modal-close]')
        .forEach((button) => button.addEventListener('click', () => close()));
    cancel.addEventListener('click', () => close());
    confirm.addEventListener('click', () =>
        close(input.classList.contains('hidden') ? true : input.value),
    );
    modal.addEventListener('click', (event) => {
        if (event.target === modal) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
        if (event.key === 'Enter' && !input.classList.contains('hidden')) close(input.value);
    });

    window.showErrorModal = (text) => {
        title.textContent = 'Something went wrong';
        message.textContent = text;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        panel.focus();
    };

    window.requestModalInput = (modalTitle, prompt, initialValue = '') =>
        new Promise((resolve) => {
            pending = resolve;
            title.textContent = modalTitle;
            message.textContent = prompt;
            input.value = initialValue;
            input.classList.remove('hidden');
            cancel.classList.remove('hidden');
            confirm.textContent = 'Confirm';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            input.focus();
            input.select();
        });

    window.requestModalConfirmation = (modalTitle, prompt) =>
        new Promise((resolve) => {
            pending = (value) => resolve(value === true);
            title.textContent = modalTitle;
            message.textContent = prompt;
            input.classList.add('hidden');
            cancel.classList.remove('hidden');
            confirm.textContent = 'Confirm';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            confirm.focus();
        });

    document.querySelectorAll('[data-modal-confirm-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed === 'true') return;
            event.preventDefault();
            const confirmed = await window.requestModalConfirmation(
                form.dataset.confirmTitle || 'Confirm action',
                form.dataset.confirmMessage || 'Are you sure you want to continue?',
            );
            if (confirmed) {
                form.dataset.confirmed = 'true';
                form.submit();
            }
        });
    });
}

function initHeroCarousels() {
    document.querySelectorAll('[data-hero-carousel]').forEach((carousel) => {
        const slides = [...carousel.querySelectorAll('[data-hero-slide]')];
        if (slides.length < 2) return;
        let index = 0;
        const count = carousel.querySelector('[data-hero-count]');
        const show = (next) => {
            index = (next + slides.length) % slides.length;
            slides.forEach((slide, slideIndex) => {
                slide.classList.toggle('is-active', slideIndex === index);
                slide.setAttribute('aria-hidden', slideIndex !== index ? 'true' : 'false');
            });
            count.textContent = `${index + 1} / ${slides.length}`;
            const activeThumb = carousel.querySelector(`[data-hero-thumb="${index}"]`);
            const thumbnailStrip = carousel.querySelector('[data-hero-thumbnails]');
            carousel.querySelectorAll('[data-hero-thumb]').forEach((thumb) => {
                const active = thumb === activeThumb;
                thumb.classList.toggle('is-active', active);
                thumb.setAttribute('aria-current', active ? 'true' : 'false');
            });

            if (activeThumb && thumbnailStrip) {
                const thumbRect = activeThumb.getBoundingClientRect();
                const stripRect = thumbnailStrip.getBoundingClientRect();

                if (thumbnailStrip.scrollHeight > thumbnailStrip.clientHeight) {
                    if (thumbRect.top < stripRect.top) {
                        thumbnailStrip.scrollTo({ top: thumbnailStrip.scrollTop - (stripRect.top - thumbRect.top), behavior: 'smooth' });
                    } else if (thumbRect.bottom > stripRect.bottom) {
                        thumbnailStrip.scrollTo({ top: thumbnailStrip.scrollTop + (thumbRect.bottom - stripRect.bottom), behavior: 'smooth' });
                    }
                } else if (thumbnailStrip.scrollWidth > thumbnailStrip.clientWidth) {
                    if (thumbRect.left < stripRect.left) {
                        thumbnailStrip.scrollTo({ left: thumbnailStrip.scrollLeft - (stripRect.left - thumbRect.left), behavior: 'smooth' });
                    } else if (thumbRect.right > stripRect.right) {
                        thumbnailStrip.scrollTo({ left: thumbnailStrip.scrollLeft + (thumbRect.right - stripRect.right), behavior: 'smooth' });
                    }
                }
            }
        };
        carousel
            .querySelector('[data-hero-prev]')
            ?.addEventListener('click', () => show(index - 1));
        carousel
            .querySelector('[data-hero-next]')
            ?.addEventListener('click', () => show(index + 1));
        carousel.querySelectorAll('[data-hero-thumb]').forEach((thumb) => {
            thumb.addEventListener('click', () => show(Number(thumb.dataset.heroThumb)));
        });
        window.setInterval(() => show(index + 1), 7000);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initPasswordToggles();
        initErrorModal();
        initHeroCarousels();
    });
} else {
    initPasswordToggles();
    initErrorModal();
    initHeroCarousels();
}
