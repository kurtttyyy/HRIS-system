<script>
    (function () {
        const allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        const maxImageSize = 10 * 1024 * 1024;
        const maxImageCount = 6;
        const selectedFiles = new WeakMap();

        const assignFiles = (input, files) => {
            const transfer = new DataTransfer();
            files.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
            selectedFiles.set(input, files);
        };

        const revokePreviewUrls = (previewList) => {
            previewList?.querySelectorAll('[data-object-url]').forEach((image) => {
                URL.revokeObjectURL(image.dataset.objectUrl);
            });
        };

        const renderPreview = (form) => {
            const input = form?.querySelector('[data-chat-image-input]');
            const preview = form?.querySelector('[data-chat-image-preview]');
            const previewList = form?.querySelector('[data-chat-image-preview-list]');
            if (!input || !preview || !previewList) return;

            revokePreviewUrls(previewList);
            previewList.innerHTML = '';

            const files = selectedFiles.get(input) || [];
            files.forEach((file, index) => {
                const tile = document.createElement('div');
                tile.className = 'relative h-14 w-14 shrink-0';

                const image = document.createElement('img');
                const objectUrl = URL.createObjectURL(file);
                image.src = objectUrl;
                image.dataset.objectUrl = objectUrl;
                image.alt = file.name;
                image.title = file.name;
                image.className = 'h-14 w-14 rounded-lg object-cover';

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.dataset.chatImageRemoveIndex = String(index);
                removeButton.className = 'absolute -right-2 -top-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-700 text-xs text-white shadow-md transition hover:bg-rose-500';
                removeButton.setAttribute('aria-label', `Remove ${file.name}`);
                removeButton.innerHTML = '<i class="fa-solid fa-xmark"></i>';

                tile.append(image, removeButton);
                previewList.appendChild(tile);
            });

            preview.classList.toggle('hidden', files.length === 0);
        };

        const resetPreview = (form) => {
            const input = form?.querySelector('[data-chat-image-input]');
            const previewList = form?.querySelector('[data-chat-image-preview-list]');
            if (!input) return;

            revokePreviewUrls(previewList);
            assignFiles(input, []);
            renderPreview(form);
        };

        window.resetChatImagePreview = resetPreview;

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-chat-image-trigger]');
            if (trigger) {
                event.preventDefault();
                const input = trigger.closest('form')?.querySelector('[data-chat-image-input]');
                if (input) {
                    input.click();
                }
                return;
            }

            const removeButton = event.target.closest('[data-chat-image-remove-index]');
            if (removeButton) {
                event.preventDefault();
                const form = removeButton.closest('form');
                const input = form?.querySelector('[data-chat-image-input]');
                if (!input) return;

                const files = [...(selectedFiles.get(input) || [])];
                files.splice(Number(removeButton.dataset.chatImageRemoveIndex), 1);
                assignFiles(input, files);
                renderPreview(form);
            }
        });

        document.addEventListener('change', function (event) {
            const input = event.target.closest('[data-chat-image-input]');
            if (!input) return;

            const existingFiles = [...(selectedFiles.get(input) || [])];
            const incomingFiles = Array.from(input.files || []);
            const validFiles = [];

            for (const file of incomingFiles) {
                if (!allowedImageTypes.includes(file.type)) {
                    alert(`${file.name} is not a supported image. Use JPG, JPEG, PNG, GIF, or WEBP.`);
                    continue;
                }
                if (file.size > maxImageSize) {
                    alert(`${file.name} is larger than 10 MB.`);
                    continue;
                }
                validFiles.push(file);
            }

            const mergedFiles = [...existingFiles];
            validFiles.forEach((file) => {
                const duplicate = mergedFiles.some((savedFile) =>
                    savedFile.name === file.name
                    && savedFile.size === file.size
                    && savedFile.lastModified === file.lastModified
                );
                if (!duplicate && mergedFiles.length < maxImageCount) {
                    mergedFiles.push(file);
                }
            });

            if (existingFiles.length + validFiles.length > maxImageCount) {
                alert(`You can attach up to ${maxImageCount} images per message.`);
            }

            assignFiles(input, mergedFiles);
            renderPreview(input.closest('form'));
        });
    })();
</script>
