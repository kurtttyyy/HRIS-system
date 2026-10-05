(() => {
  const frame = document.getElementById('docxViewer');
  if (!frame) return;
  let started = false;
  frame.addEventListener('load', () => {
    frame.contentWindow.postMessage({ type: 'docx-init' }, '*');
  });
  window.addEventListener('message', async (event) => {
    if (event.source !== frame.contentWindow || event.data?.type !== 'docx-ready' || started) return;
    started = true;
    try {
      const response = await fetch(frame.dataset.url, { credentials: 'same-origin', cache: 'no-store' });
      if (!response.ok || response.redirected) throw new Error('Document request failed');
      const buffer = await response.arrayBuffer();
      frame.contentWindow.postMessage({ type: 'docx-document', buffer }, '*', [buffer]);
    } catch (error) {
      frame.contentWindow.postMessage({ type: 'docx-error' }, '*');
    }
  });
})();
