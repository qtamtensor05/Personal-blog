document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-post-interaction]');
    if (!form) return;
    event.preventDefault();
    if (form.dataset.busy === 'true') return;

    const button = form.querySelector('button[type="submit"]');
    const method = form.querySelector('[name="_method"]');
    const wasActive = button.getAttribute('aria-pressed') === 'true';
    form.dataset.busy = 'true';
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!response.ok || response.redirected) throw new Error();
        const result = await response.json();
        if (typeof result.active !== 'boolean') throw new Error();
        const active = result.active;
        method.value = active ? 'DELETE' : 'PUT';
        button.setAttribute('aria-pressed', String(active));
        button.querySelector('svg').setAttribute('fill', active ? 'currentColor' : 'none');
        button.querySelector('[data-interaction-label]').textContent = active
            ? form.dataset.activeLabel : form.dataset.inactiveLabel;

        const isLike = form.dataset.postInteraction === 'like';
        const activeClasses = isLike
            ? ['border-rose-300', 'bg-rose-50', 'text-rose-600']
            : ['border-accent-primary', 'bg-surface-secondary', 'text-accent-primary'];
        const inactiveClasses = isLike
            ? ['border-slate-300', 'text-slate-700', 'hover:border-rose-300', 'hover:text-rose-600']
            : ['border-default', 'text-secondary', 'hover:border-accent-primary', 'hover:text-accent-primary'];
        for (const name of activeClasses) button.classList.toggle(name, active);
        for (const name of inactiveClasses) button.classList.toggle(name, !active);
        if (isLike && active !== wasActive) {
            const group = form.closest('[data-post-actions]');
            const total = Math.max(0, Number(group.dataset.likeCount) + (active ? 1 : -1));
            group.dataset.likeCount = String(total);
            const formatted = new Intl.NumberFormat(document.documentElement.lang, {
                notation: total >= 1000 ? 'compact' : 'standard', maximumFractionDigits: 1,
            }).format(total);
            group.querySelectorAll('[data-public-like-count], [data-likers-count-value]')
                .forEach((count) => { count.textContent = formatted; });
            const opener = group.querySelector('[data-likers-open]');
            if (opener) opener.dataset.likersCount = String(total);
        }
    } catch {
        window.showErrorModal?.(document.documentElement.lang.startsWith('vi')
            ? 'Không thể cập nhật. Vui lòng kiểm tra kết nối hoặc đăng nhập lại rồi thử lại.'
            : 'Unable to update. Check your connection or sign in again, then retry.');
    } finally {
        form.dataset.busy = 'false';
        button.disabled = false;
        button.removeAttribute('aria-busy');
    }
});
