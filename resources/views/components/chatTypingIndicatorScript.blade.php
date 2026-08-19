<script>
(() => {
    const form = document.querySelector(@json($typingFormSelector));
    const indicator = document.getElementById(@json($typingIndicatorId));
    const textarea = form?.querySelector('textarea[name="body"]');
    const conversationId = form?.querySelector('input[name="conversation_id"]')?.value;
    const endpoint = @json($typingRoute);
    const csrfToken = form?.querySelector('input[name="_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.content;
    if (!form || !indicator || !textarea || !conversationId || !endpoint) return;

    let stopTimer = null;
    let lastTypingSignalAt = 0;
    let statusRequestRunning = false;

    const sendTypingState = async (isTyping) => {
        try {
            await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ conversation_id: conversationId, is_typing: isTyping }),
                keepalive: !isTyping,
            });
        } catch (error) {
            // The server-side expiry clears interrupted typing signals automatically.
        }
    };

    const stopTyping = () => {
        window.clearTimeout(stopTimer);
        sendTypingState(false);
    };

    textarea.addEventListener('input', () => {
        const isTyping = textarea.value.trim().length > 0;
        if (!isTyping) {
            stopTyping();
            return;
        }

        const now = Date.now();
        if (now - lastTypingSignalAt > 1200) {
            lastTypingSignalAt = now;
            sendTypingState(true);
        }
        window.clearTimeout(stopTimer);
        stopTimer = window.setTimeout(stopTyping, 2200);
    });

    textarea.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.shiftKey || event.isComposing) return;

        event.preventDefault();
        if (!textarea.value.trim() && !form.querySelector('[data-chat-image-input]')?.files?.length) return;

        stopTyping();
        form.requestSubmit();
    });

    form.addEventListener('submit', stopTyping);
    window.addEventListener('pagehide', stopTyping);

    const refreshTypingIndicator = async () => {
        if (statusRequestRunning || document.hidden) return;
        statusRequestRunning = true;
        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('conversation_id', conversationId);
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) return;
            const payload = await response.json();
            indicator.classList.toggle('hidden', !payload.typing);
        } catch (error) {
            indicator.classList.add('hidden');
        } finally {
            statusRequestRunning = false;
        }
    };

    refreshTypingIndicator();
    window.setInterval(refreshTypingIndicator, 1400);
})();
</script>
