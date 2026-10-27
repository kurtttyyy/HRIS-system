(async () => {
  const viewer = document.getElementById('pdfViewer');
  const status = document.getElementById('pdfStatus');
  const url = viewer?.dataset.url;
  const asImage = viewer?.dataset.renderAs === 'image';
  if (!viewer || !status || !url) return;
  if (!window.pdfjsLib) {
    status.textContent = 'Unable to load the document preview engine. Please refresh and try again.';
    return;
  }

  window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
  try {
    const pdf = await window.pdfjsLib.getDocument({ url }).promise;
    for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber += 1) {
      const page = await pdf.getPage(pageNumber);
      const baseViewport = page.getViewport({ scale: 1 });
      const targetWidth = Math.max(1, Math.min(viewer.clientWidth - 36, 980));
      const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
      const viewport = page.getViewport({ scale: targetWidth / baseViewport.width * pixelRatio });
      const canvas = document.createElement('canvas');
      canvas.width = Math.ceil(viewport.width);
      canvas.height = Math.ceil(viewport.height);
      await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

      if (asImage) {
        const image = document.createElement('img');
        image.className = 'pdf-page';
        image.alt = `${viewer.dataset.fileName} — page ${pageNumber} of ${pdf.numPages}`;
        image.width = canvas.width;
        image.height = canvas.height;
        image.style.height = 'auto';
        image.src = canvas.toDataURL('image/png');
        viewer.appendChild(image);
        canvas.width = 0;
        canvas.height = 0;
      } else {
        canvas.className = 'pdf-page';
        viewer.appendChild(canvas);
      }
      status.hidden = true;
      page.cleanup();
    }
    status.remove();
    await pdf.destroy();
  } catch (error) {
    console.error('Unable to render document preview.', error);
    status.hidden = false;
    status.textContent = asImage
      ? 'Unable to prepare the document preview. Please try again or open the original file.'
      : 'Unable to render this PDF preview. Please try again.';
  }
})();
