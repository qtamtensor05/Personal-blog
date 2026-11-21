const storageKey = 'bm-navigation-stack-v1';

function localUrl(value, origin) {
    if (typeof value !== 'string' || !value.trim()) return null;
    try {
        const url = new URL(value, origin);
        return url.origin === origin && /^https?:$/.test(url.protocol) ? url : null;
    } catch {
        return null;
    }
}

export function recordNavigation(saved, currentHref, referrer = '') {
    const current = new URL(currentHref);
    const stack = (Array.isArray(saved) ? saved : [])
        .map((value) => localUrl(value, current.origin))
        .filter(Boolean)
        .map((url) => url.href);

    if (stack.length === 0) {
        const previous = localUrl(referrer, current.origin);
        if (previous && previous.pathname !== current.pathname) stack.push(previous.href);
    }

    const index = stack.findLastIndex((value) => new URL(value).pathname === current.pathname);
    if (index !== -1) stack.splice(index);
    stack.push(current.href);
    return stack.slice(-50);
}

export function initNavigationBack(win = window, doc = document) {
    let stack = [];
    const refresh = () => {
        let saved = [];
        try {
            saved = JSON.parse(win.sessionStorage.getItem(storageKey) || '[]');
        } catch { /* Storage may be disabled or contain stale data. */ }

        stack = recordNavigation(saved, win.location.href, doc.referrer);
        try {
            win.sessionStorage.setItem(storageKey, JSON.stringify(stack));
        } catch { /* Links still work using their fallback URL. */ }

        doc.querySelectorAll('a[data-navigation-back]').forEach((link) => {
            if (stack.length > 1) link.href = stack.at(-2);
        });
    };

    refresh();
    win.addEventListener('pageshow', refresh);
}
