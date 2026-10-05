(() => {
    const dirtyForms = new WeakSet();
    let refreshing = false;
    let submitting = false;
    document.addEventListener('input', event => {
        const form = event.target.closest('[data-evaluation-refresh] form');
        if (form) dirtyForms.add(form);
    });
    document.addEventListener('change', event => {
        const form = event.target.closest('[data-evaluation-refresh] form');
        if (form) dirtyForms.add(form);
    });
    document.addEventListener('submit', event => {
        if (event.target.closest('[data-evaluation-refresh]')) submitting = true;
    });

    const refresh = async () => {
        if (refreshing || submitting || document.hidden) return;
        refreshing = true;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            // Keep the current pagination and tab-specific authentication parameters.
            const response = await fetch(window.location.href, {
                credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
                headers: { Accept: 'text/html' },
            });
            if (!response.ok || response.redirected || submitting) return;
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            document.querySelectorAll('[data-evaluation-refresh]').forEach(current => {
                const incoming = page.getElementById(current.id);
                if (!incoming || incoming.innerHTML === current.innerHTML) return;
                const retained = [];
                const forms = [...incoming.querySelectorAll('form')];
                for (const form of current.querySelectorAll('form')) {
                    if (!dirtyForms.has(form)) continue;
                    const replacement = forms.find(candidate => candidate.getAttribute('action') === form.getAttribute('action'));
                    const version = node => node.querySelector('[name="template_id"]')?.value;
                    if (!replacement || version(form) !== version(replacement)) {
                        const notice = document.getElementById('evaluation-refresh-notice');
                        if (notice) notice.hidden = false;
                        return;
                    }
                    retained.push([replacement, form]);
                }
                // Preserve active drafts as live nodes, including focus and selection.
                const active = document.activeElement;
                const selection = active && typeof active.selectionStart === 'number'
                    ? [active.selectionStart, active.selectionEnd] : null;
                const openDetails = [...current.querySelectorAll('details')].map((node, index) => node.open ? index : -1);
                retained.forEach(([replacement, form]) => replacement.replaceWith(form));
                current.replaceChildren(...incoming.childNodes);
                current.querySelectorAll('details').forEach((node, index) => {
                    if (openDetails.includes(index)) node.open = true;
                });
                if (active && current.contains(active)) {
                    active.focus({ preventScroll: true });
                    if (selection) active.setSelectionRange(...selection);
                }
                document.dispatchEvent(new CustomEvent('evaluation:refreshed'));
            });
        } catch (error) {
            // A temporary network failure will be retried on the next interval.
        } finally {
            clearTimeout(timeout);
            refreshing = false;
        }
    };
    setInterval(refresh, 3000);
    window.addEventListener('focus', refresh);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
