<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body { margin: 0; background: #e8edf3; }
    #status { padding: 24px; font: 14px/1.6 Arial, sans-serif; color: #334155; }
    .docx-wrapper { padding: 24px !important; }
    .docx-wrapper > section.docx { flex-shrink: 0; }
  </style>
</head>
<body>
  <p id="status" role="status">Loading Word document preview...</p>
  <div id="documentStyles"></div>
  <div id="documentPages"></div>
  <script src="{{ asset('js/vendor/docx-preview/jszip.min.js') }}"></script>
  <script src="{{ asset('js/vendor/docx-preview/docx-preview.min.js') }}"></script>
  <script>
    let started = false;
    window.addEventListener('message', async (event) => {
      if (event.source === window.parent && event.data?.type === 'docx-init') {
        window.parent.postMessage({ type: 'docx-ready' }, '*');
        return;
      }
      if (event.source !== window.parent || started) return;
      const status = document.getElementById('status');
      if (event.data?.type === 'docx-error') {
        started = true;
        status.textContent = 'Unable to load this Word document. Please try again or open the original file in Word.';
        return;
      }
      if (event.data?.type !== 'docx-document') return;
      started = true;
      try {
        if (!window.docx || !window.JSZip) throw new Error('Preview engine unavailable');
        await window.docx.renderAsync(event.data.buffer,
          document.getElementById('documentPages'), document.getElementById('documentStyles'), {
            ignoreWidth: false, ignoreHeight: false, ignoreFonts: false,
            breakPages: true, renderHeaders: true, renderFooters: true,
            renderFootnotes: true, renderEndnotes: true,
            experimental: true, useBase64URL: true, renderAltChunks: false,
          });
        status.remove();
      } catch (error) {
        document.getElementById('documentPages').replaceChildren();
        document.getElementById('documentStyles').replaceChildren();
        status.textContent = 'Unable to render this Word document. Open the original file in Word, or upload a PDF for an exact preview.';
      }
    });
    window.parent.postMessage({ type: 'docx-ready' }, '*');
  </script>
</body>
</html>
