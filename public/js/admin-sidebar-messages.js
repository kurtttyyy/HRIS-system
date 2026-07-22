(() => {
  let requestRunning = false;
  let timer = null;

  const communicationLink = () => document.querySelector('[data-admin-communication-nav]');

  const render = (value) => {
    const count = Math.max(Number.parseInt(value ?? '0', 10) || 0, 0);

    document.querySelectorAll('[data-admin-communication-unread-dot]').forEach((badge) => {
      badge.textContent = count > 9 ? '9+' : String(count);
      badge.hidden = count === 0;
      badge.classList.toggle('hidden', count === 0);
      badge.classList.toggle('inline-flex', count > 0);
      badge.style.setProperty('display', count > 0 ? 'inline-flex' : 'none', 'important');
    });

    document.querySelectorAll('[data-admin-communication-unread-count]').forEach((badge) => {
      badge.textContent = count > 99 ? '99+' : String(count);
      badge.hidden = count === 0;
      badge.classList.toggle('hidden', count === 0);
    });
  };

  const refresh = async () => {
    const link = communicationLink();
    const endpoint = link?.dataset.unreadEndpoint;
    if (!endpoint || requestRunning || document.hidden) return;

    requestRunning = true;
    try {
      const response = await fetch(endpoint, {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        cache: 'no-store',
      });
      if (!response.ok) return;

      const payload = await response.json();
      render(payload.count);
    } catch (error) {
      // Leave the page undisturbed and retry shortly.
    } finally {
      requestRunning = false;
    }
  };

  const start = () => {
    window.updateAdminCommunicationUnreadCount = render;
    window.refreshAdminCommunicationUnreadCount = refresh;
    refresh();

    if (timer === null) {
      timer = window.setInterval(refresh, 3000);
    }

    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) refresh();
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
  } else {
    start();
  }
})();
