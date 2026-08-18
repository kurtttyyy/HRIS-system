<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Bootstrap CSS (CDN for quick preview) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}" rel="stylesheet">
</head>
<body>

    {{-- Page loader (optional per page) --}}
    @yield('page-loader')

    @yield('content')

    @if (request()->routeIs('guest.*') && !request()->routeIs('guest.index'))
        @include('components.guest-ai-assistant')
    @endif

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chatbot = document.getElementById('ncChatbot');
            const launcher = document.getElementById('ncChatLauncher');
            if (!chatbot || !launcher) return;

            const storageKey = window.matchMedia('(max-width: 991.98px)').matches
                ? 'nc-chatbot-position-mobile'
                : 'nc-chatbot-position-desktop';
            let dragStart = null;
            let dragged = false;
            let ignoreNextClick = false;

            function keepInViewport(left, top) {
                const rect = chatbot.getBoundingClientRect();
                return {
                    left: Math.max(8, Math.min(left, window.innerWidth - rect.width - 8)),
                    // Leave room for the label shown beneath the launcher.
                    top: Math.max(8, Math.min(top, window.innerHeight - rect.height - 34))
                };
            }

            function setPosition(left, top, save) {
                const position = keepInViewport(left, top);
                chatbot.style.left = position.left + 'px';
                chatbot.style.top = position.top + 'px';
                chatbot.style.right = 'auto';
                chatbot.style.bottom = 'auto';
                if (save) localStorage.setItem(storageKey, JSON.stringify(position));
            }

            try {
                const saved = JSON.parse(localStorage.getItem(storageKey));
                if (saved && Number.isFinite(saved.left) && Number.isFinite(saved.top)) {
                    setPosition(saved.left, saved.top, false);
                }
            } catch (_) {
                localStorage.removeItem(storageKey);
            }

            launcher.addEventListener('pointerdown', function (event) {
                if (chatbot.classList.contains('is-open') || event.button > 0) return;
                const rect = chatbot.getBoundingClientRect();
                dragStart = {
                    pointerX: event.clientX,
                    pointerY: event.clientY,
                    left: rect.left,
                    top: rect.top,
                    threshold: event.pointerType === 'touch' ? 2 : 5
                };
                dragged = false;
                chatbot.classList.add('is-dragging');
                launcher.setPointerCapture(event.pointerId);
                launcher.style.cursor = 'grabbing';
            });

            launcher.addEventListener('pointermove', function (event) {
                if (!dragStart) return;
                const offsetX = event.clientX - dragStart.pointerX;
                const offsetY = event.clientY - dragStart.pointerY;
                if (Math.abs(offsetX) > dragStart.threshold || Math.abs(offsetY) > dragStart.threshold) {
                    dragged = true;
                    event.preventDefault();
                    setPosition(dragStart.left + offsetX, dragStart.top + offsetY, false);
                }
            });

            function finishDrag(event) {
                if (!dragStart) return;
                if (launcher.hasPointerCapture(event.pointerId)) launcher.releasePointerCapture(event.pointerId);
                launcher.style.cursor = '';
                chatbot.classList.remove('is-dragging');
                if (dragged) {
                    const rect = chatbot.getBoundingClientRect();
                    setPosition(rect.left, rect.top, true);
                    ignoreNextClick = true;
                }
                dragStart = null;
            }

            launcher.addEventListener('pointerup', finishDrag);
            launcher.addEventListener('pointercancel', finishDrag);
            launcher.addEventListener('click', function (event) {
                if (!ignoreNextClick) return;
                event.preventDefault();
                event.stopImmediatePropagation();
                ignoreNextClick = false;
            }, true);

            window.addEventListener('resize', function () {
                if (!chatbot.style.left) return;
                const rect = chatbot.getBoundingClientRect();
                setPosition(rect.left, rect.top, true);
            });
        });
    </script>

    @stack('loader-script')
</body>

</html>
