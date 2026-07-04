<script>
    (function () {
        const closeEmojiPickers = (exceptPicker = null) => {
            document.querySelectorAll('[data-chat-emoji-picker]').forEach((picker) => {
                if (picker === exceptPicker) return;
                picker.classList.add('hidden');
                picker.closest('form')?.querySelector('[data-chat-emoji-trigger]')
                    ?.setAttribute('aria-expanded', 'false');
            });
        };

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-chat-emoji-trigger]');
            if (trigger) {
                event.preventDefault();
                const form = trigger.closest('form');
                const picker = form?.querySelector('[data-chat-emoji-picker]');
                if (!picker) return;

                const willOpen = picker.classList.contains('hidden');
                closeEmojiPickers(willOpen ? picker : null);
                picker.classList.toggle('hidden', !willOpen);
                trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                return;
            }

            const emojiButton = event.target.closest('[data-chat-emoji]');
            if (emojiButton) {
                event.preventDefault();
                const form = emojiButton.closest('form');
                const textarea = form?.querySelector('textarea[name="body"]');
                if (!textarea) return;

                const emoji = emojiButton.dataset.chatEmoji || '';
                const start = textarea.selectionStart ?? textarea.value.length;
                const end = textarea.selectionEnd ?? start;
                textarea.setRangeText(emoji, start, end, 'end');
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                textarea.focus();
                closeEmojiPickers();
                return;
            }

            if (!event.target.closest('[data-chat-emoji-picker]')) {
                closeEmojiPickers();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeEmojiPickers();
        });
    })();
</script>
