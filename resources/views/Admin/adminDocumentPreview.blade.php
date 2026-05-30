<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $fileName }} Preview</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      background: #f3f7fb;
      color: #0f172a;
      font-family: Arial, Helvetica, sans-serif;
    }

    .preview-shell {
      display: flex;
      min-height: 100vh;
      flex-direction: column;
    }

    .preview-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      border-bottom: 1px solid #dbe4ef;
      background: #ffffff;
      padding: 14px 18px;
    }

    .preview-title {
      min-width: 0;
    }

    .preview-title p {
      margin: 0 0 4px;
      color: #64748b;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
    }

    .preview-title h1 {
      margin: 0;
      overflow-wrap: anywhere;
      font-size: 16px;
      line-height: 1.35;
    }

    .preview-close {
      flex: 0 0 auto;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      background: #ffffff;
      color: #334155;
      cursor: pointer;
      font-size: 13px;
      font-weight: 700;
      padding: 9px 12px;
    }

    .preview-close:hover {
      border-color: #38bdf8;
      color: #0369a1;
    }

    .preview-body {
      flex: 1;
      padding: 18px;
    }

    .preview-frame {
      display: block;
      width: 100%;
      height: calc(100vh - 96px);
      border: 1px solid #dbe4ef;
      border-radius: 8px;
      background: #ffffff;
    }

    .pdf-viewer {
      display: flex;
      min-height: calc(100vh - 116px);
      flex-direction: column;
      align-items: center;
      gap: 18px;
      border: 1px solid #dbe4ef;
      border-radius: 8px;
      background: #1f2329;
      padding: 18px;
      overflow: auto;
    }

    .pdf-status {
      margin: auto;
      color: #cbd5e1;
      font-size: 14px;
      font-weight: 700;
    }

    .pdf-page {
      width: min(100%, 980px);
      border-radius: 4px;
      background: #ffffff;
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.28);
    }

    .image-wrap {
      display: flex;
      min-height: calc(100vh - 116px);
      align-items: center;
      justify-content: center;
      border: 1px solid #dbe4ef;
      border-radius: 8px;
      background: #ffffff;
      padding: 18px;
    }

    .image-wrap img {
      max-width: 100%;
      max-height: calc(100vh - 152px);
      object-fit: contain;
    }

    .word-page,
    .empty-state {
      max-width: 920px;
      margin: 0 auto;
      border: 1px solid #dbe4ef;
      border-radius: 8px;
      background: #ffffff;
      padding: 28px;
      box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    .word-page pre {
      margin: 0;
      white-space: pre-wrap;
      overflow-wrap: anywhere;
      color: #1e293b;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 15px;
      line-height: 1.7;
    }

    .word-images {
      display: flex;
      flex-direction: column;
      gap: 18px;
      margin-top: 18px;
    }

    .word-images:first-child {
      margin-top: 0;
    }

    .word-images img {
      max-width: 100%;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      background: #ffffff;
    }

    .empty-state h2 {
      margin: 0 0 8px;
      font-size: 18px;
    }

    .empty-state p {
      margin: 0;
      color: #64748b;
      font-size: 14px;
      line-height: 1.6;
    }
  </style>
</head>
<body>
  <main class="preview-shell">
    <header class="preview-header">
      <div class="preview-title">
        <p>Document Preview</p>
        <h1>{{ $fileName }}</h1>
      </div>
      <button type="button" class="preview-close" onclick="window.close()">Close</button>
    </header>

    <section class="preview-body">
      @if ($isPdf)
        <div id="pdfViewer" class="pdf-viewer" data-url="{{ $viewUrl }}">
          <p id="pdfStatus" class="pdf-status">Loading PDF preview...</p>
        </div>
      @elseif ($isImage)
        <div class="image-wrap">
          <img src="{{ $viewUrl }}" alt="{{ $fileName }}">
        </div>
      @elseif ($isText)
        <iframe class="preview-frame" src="{{ $viewUrl }}" title="{{ $fileName }}"></iframe>
      @elseif (in_array($extension, ['doc', 'docx'], true) && (filled($wordText) || !empty($wordImages)))
        <article class="word-page">
          @if (filled($wordText))
            <pre>{{ $wordText }}</pre>
          @endif
          @if (!empty($wordImages))
            <div class="word-images">
              @foreach ($wordImages as $image)
                <img src="{{ $image['data_uri'] }}" alt="{{ $image['name'] }}">
              @endforeach
            </div>
          @endif
        </article>
      @else
        <article class="empty-state">
          <h2>Preview is not available for this file type.</h2>
          <p>This page opened without downloading the file. Convert the document to PDF if you need an exact in-browser preview.</p>
        </article>
      @endif
    </section>
  </main>

  @if ($isPdf)
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
      (async function renderPdfPreview() {
        const viewer = document.getElementById('pdfViewer');
        const status = document.getElementById('pdfStatus');
        const url = viewer?.dataset.url;

        if (!viewer || !status || !url || !window.pdfjsLib) {
          if (status) status.textContent = 'Unable to load the PDF preview engine.';
          return;
        }

        window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        try {
          const pdf = await window.pdfjsLib.getDocument({ url }).promise;
          status.remove();

          for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber += 1) {
            const page = await pdf.getPage(pageNumber);
            const baseViewport = page.getViewport({ scale: 1 });
            const targetWidth = Math.min(viewer.clientWidth - 36, 980);
            const scale = Math.max(0.8, targetWidth / baseViewport.width);
            const viewport = page.getViewport({ scale });
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            canvas.className = 'pdf-page';
            canvas.width = Math.floor(viewport.width);
            canvas.height = Math.floor(viewport.height);
            viewer.appendChild(canvas);

            await page.render({ canvasContext: context, viewport }).promise;
          }
        } catch (error) {
          console.error('Unable to render PDF preview.', error);
          status.textContent = 'Unable to render this PDF preview.';
        }
      })();
    </script>
  @endif
</body>
</html>
