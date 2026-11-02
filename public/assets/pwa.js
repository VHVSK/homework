/* Installation is requested only by the visitor; private pages are never cached. */
(() => {
  'use strict';
  const script = document.currentScript || document.querySelector('[data-pwa-script]');
  const buttons = Array.from(document.querySelectorAll('[data-pwa-install]'));
  const dialog = document.querySelector('#install-dialog');
  const displayMode = typeof window.matchMedia === 'function' ? window.matchMedia('(display-mode: standalone)') : null;
  let pendingPrompt = null;
  let busy = false;
  let installed = navigator.standalone === true || Boolean(displayMode && displayMode.matches);

  const updateButtons = () => buttons.forEach((button) => { button.hidden = installed; });
  const showInstructions = () => {
    if (!dialog) return;
    const secureNote = dialog.querySelector('[data-pwa-insecure]');
    if (secureNote) secureNote.hidden = window.isSecureContext === true;
    if (typeof dialog.showModal === 'function') {
      if (!dialog.open) dialog.showModal();
    } else {
      dialog.setAttribute('open', '');
      dialog.querySelector('[data-pwa-close]')?.focus();
    }
  };
  const closeInstructions = () => {
    if (!dialog) return;
    if (typeof dialog.close === 'function') dialog.close();
    else dialog.removeAttribute('open');
    buttons.find((button) => !button.hidden)?.focus();
  };

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    pendingPrompt = event;
    updateButtons();
  });
  window.addEventListener('appinstalled', () => {
    installed = true;
    pendingPrompt = null;
    closeInstructions();
    updateButtons();
  });
  displayMode?.addEventListener?.('change', (event) => {
    installed = event.matches || navigator.standalone === true;
    updateButtons();
  });
  buttons.forEach((button) => button.addEventListener('click', async (event) => {
    event.preventDefault();
    if (busy || installed) return;
    if (!pendingPrompt) {
      showInstructions();
      return;
    }
    const prompt = pendingPrompt;
    pendingPrompt = null;
    busy = true;
    button.setAttribute('aria-disabled', 'true');
    try {
      await prompt.prompt();
      const choice = await prompt.userChoice;
      if (choice && choice.outcome === 'accepted') installed = true;
    } catch (error) {
      showInstructions();
    } finally {
      busy = false;
      button.removeAttribute('aria-disabled');
      updateButtons();
    }
  }));
  dialog?.querySelectorAll('[data-pwa-close]').forEach((button) => button.addEventListener('click', closeInstructions));
  updateButtons();

  const registerWorker = () => {
    if (!window.isSecureContext || !('serviceWorker' in navigator) || !script) return;
    const workerPath = script.dataset.workerUrl;
    const scopePath = script.dataset.workerScope;
    if (!workerPath || !scopePath) return;
    try {
      const worker = new URL(workerPath, window.location.href);
      const scope = new URL(scopePath, window.location.href);
      if (worker.origin !== window.location.origin || scope.origin !== window.location.origin) return;
      navigator.serviceWorker.register(worker.href, {scope: scope.href, updateViaCache: 'none'}).catch(() => {});
    } catch (error) {
      // Ordinary browsing and the manual installation instructions remain available.
    }
  };
  if (document.readyState === 'complete') registerWorker();
  else window.addEventListener('load', registerWorker, {once: true});
})();
