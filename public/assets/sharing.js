/* Sharing always follows a click; the visitor chooses the destination. */
(() => {
  'use strict';
  const statusFor = (button) => button.closest('[data-share-panel], .collection-actions')?.querySelector('[data-share-status]');
  const showStatus = (button, message) => {
    const status = statusFor(button);
    if (status) status.textContent = message;
  };
  const safeUrl = (value) => {
    try {
      const url = new URL(value, window.location.href);
      return url.origin === window.location.origin && ['https:', 'http:'].includes(url.protocol) ? url.href : null;
    } catch { return null; }
  };
  const copySucceeded = (button) => {
    showStatus(button, 'Посилання скопійовано.');
    button.setAttribute('title', 'Посилання скопійовано');
    button.setAttribute('aria-label', 'Посилання скопійовано');
    button.classList.add('is-copied');
    const label = button.querySelector('[data-copy-label]');
    if (label) label.textContent = 'Скопійовано';
  };
  const copy = async (button, url) => {
    if (navigator.clipboard?.writeText && window.isSecureContext) {
      try {
        await navigator.clipboard.writeText(url);
        copySucceeded(button);
        return;
      } catch { /* Try a temporary selection for browsers that block the modern API. */ }
    }
    const selection = document.createElement('textarea');
    selection.value = url;
    selection.readOnly = true;
    selection.style.cssText = 'position:fixed;left:-9999px;top:0;opacity:0;font-size:16px';
    selection.setAttribute('aria-hidden', 'true');
    const previousFocus = document.activeElement;
    let copied = false;
    try {
      document.body.append(selection);
      selection.focus();
      selection.select();
      selection.setSelectionRange(0, url.length);
      copied = typeof document.execCommand === 'function' && document.execCommand('copy');
    } catch { /* Manual copy is the final fallback; the toolbar never grows. */ }
    finally {
      selection.remove();
      (previousFocus || button).focus();
    }
    if (copied) { copySucceeded(button); return; }
    if (typeof window.prompt === 'function') window.prompt('Скопіюйте посилання:', url);
    showStatus(button, 'Браузер не дозволив автоматичне копіювання. Скопіюйте посилання у відкритому вікні.');
  };
  document.querySelectorAll('[data-share-url], [data-copy-url]').forEach((button) => {
    button.hidden = false;
    button.addEventListener('click', async () => {
      const url = safeUrl(button.dataset.shareUrl || button.dataset.copyUrl);
      if (!url) { showStatus(button, 'Не вдалося підготувати посилання. Оновіть сторінку.'); return; }
      if (!button.hasAttribute('data-copy-url') && navigator.share && window.isSecureContext) {
        try {
          await navigator.share({title: button.dataset.shareTitle || 'Домашні завдання · Після дзвоника', url});
          return;
        } catch (error) {
          if (error?.name === 'AbortError') return;
        }
      }
      await copy(button, url);
    });
  });
  document.querySelectorAll('[data-print]').forEach((button) => {
    button.hidden = false;
    button.addEventListener('click', () => window.print());
  });
})();
