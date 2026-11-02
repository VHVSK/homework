/* Progressive enhancement: public browsing and ordinary forms work without JavaScript. */
(() => {
  'use strict';

  const sidebarButtons = document.querySelectorAll('[data-sidebar-toggle]');
  const setSidebar = (open) => {
    document.body.classList.toggle('sidebar-open', open);
    sidebarButtons.forEach((button) => button.setAttribute('aria-expanded', String(open)));
  };
  sidebarButtons.forEach((button) => button.addEventListener('click', () => {
    setSidebar(!document.body.classList.contains('sidebar-open'));
  }));
  document.querySelectorAll('[data-sidebar-close]').forEach((button) => {
    button.addEventListener('click', () => setSidebar(false));
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setSidebar(false);
  });
  document.querySelectorAll('.sidebar .nav-link').forEach((link) => {
    link.addEventListener('click', () => setSidebar(false));
  });

  document.querySelectorAll('form[data-filters]').forEach((form) => {
    const reset = (name) => {
      const field = form.querySelector(`[data-filter="${name}"]`);
      if (field) field.value = field.querySelector('option[value="0"]') ? '0' : '';
    };
    form.querySelectorAll('select[data-filter]').forEach((select) => {
      select.addEventListener('change', () => {
        if (select.dataset.filter === 'city') {
          ['school', 'class', 'subject'].forEach(reset);
        } else if (select.dataset.filter === 'school') {
          ['class', 'subject'].forEach(reset);
        }
        const page = form.querySelector('[name="page"]');
        if (page) page.value = '1';
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.submit();
      });
    });
  });

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm || 'Підтвердити дію?')) event.preventDefault();
    });
  });

  document.querySelectorAll('a[target="_blank"]').forEach((link) => {
    link.rel = 'noopener noreferrer';
  });

  const editorContainer = document.querySelector('#homework-editor');
  const source = document.querySelector('textarea#body_html');
  const editorForm = document.querySelector('form[data-editor-form]');
  if (editorContainer && source && editorForm && typeof window.Quill === 'function') {
    try {
      const quill = new window.Quill(editorContainer, {
        theme: 'snow',
        placeholder: 'Опишіть завдання та додайте посилання на матеріали…',
        formats: ['header', 'bold', 'italic', 'underline', 'strike', 'list', 'link', 'blockquote'],
        modules: {
          toolbar: [
            [{ header: [2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['blockquote', 'link'],
            ['clean']
          ]
        }
      });
      quill.root.setAttribute('role', 'textbox');
      quill.root.setAttribute('aria-label', 'Що потрібно зробити');
      quill.root.setAttribute('aria-multiline', 'true');
      // The server has already sanitized saved HTML; Quill also converts it to its supported formats.
      if (source.value.trim()) {
        quill.clipboard.dangerouslyPasteHTML(source.value);
      }
      source.hidden = true;
      source.required = false;
      editorContainer.hidden = false;
      editorForm.addEventListener('submit', (event) => {
        const plainText = quill.getText().trim();
        if (!plainText) {
          event.preventDefault();
          quill.focus();
          let message = editorForm.querySelector('[data-editor-error]');
          if (!message) {
            message = document.createElement('p');
            message.dataset.editorError = '';
            message.className = 'small';
            message.style.color = '#b16858';
            message.setAttribute('role', 'alert');
            editorContainer.insertAdjacentElement('afterend', message);
          }
          message.textContent = 'Додайте текст домашнього завдання.';
          return;
        }
        source.setCustomValidity('');
        source.value = typeof quill.getSemanticHTML === 'function'
          ? quill.getSemanticHTML()
          : quill.root.innerHTML;
      });
    } catch (error) {
      editorContainer.hidden = true;
      source.hidden = false;
      source.classList.add('editor-fallback');
    }
  } else if (editorContainer && source) {
    editorContainer.hidden = true;
    source.hidden = false;
    source.classList.add('editor-fallback');
  }
})();
