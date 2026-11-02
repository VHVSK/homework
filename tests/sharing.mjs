import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../public/assets/sharing.js', import.meta.url), 'utf8');
let checks = 0;
const check = (condition, message) => { assert.ok(condition, message); checks++; };
const defaultUrl = '/index.php?r=home&school=1&class=2&period=week&date=2026-10-30';
const absoluteUrl = new URL(defaultUrl, 'https://school.invalid').href;

function harness(options = {}) {
  const status = {textContent: ''};
  const shared = [], copied = [], legacyCopied = [], created = [], prompts = [], legacyCommands = [];
  let printed = 0;
  let document;
  const makeElement = (tag) => ({
    tagName: tag, dataset: {}, listeners: {}, attributes: {}, style: {}, hidden: true,
    focused: false, selected: false, children: [], textContent: '', removed: false,
    classList: {values: new Set(), add(value) { this.values.add(value); }, remove(value) { this.values.delete(value); }},
    append(child) { this.children.push(child); child.parentNode = this; },
    appendChild(child) { this.append(child); return child; },
    removeChild(child) { this.children = this.children.filter(item => item !== child); child.parentNode = null; child.removed = true; },
    remove() { this.parentNode?.removeChild(this); this.removed = true; },
    focus() { this.focused = true; document.activeElement = this; },
    select() { this.selected = true; },
    setSelectionRange() { this.selected = true; },
    setAttribute(name, value) { this.attributes[name] = value; },
    removeAttribute(name) { delete this.attributes[name]; },
    getAttribute(name) { return this.attributes[name] ?? null; },
    addEventListener(type, callback) { this.listeners[type] = callback; },
    async fire(type) { await this.listeners[type]?.({currentTarget: this, preventDefault() {}}); },
  });
  const panel = {
    children: [],
    querySelector(selector) { return selector === '[data-share-status]' ? status : null; },
    append(element) { this.children.push(element); },
  };
  const button = (data) => {
    const label = {textContent: 'Копіювати посилання'};
    return {
      ...makeElement('button'), dataset: data, label,
      closest() { return panel; },
      querySelector(selector) { return selector === '[data-copy-label]' ? label : null; },
      hasAttribute(name) { return name === 'data-copy-url' && Object.hasOwn(this.dataset, 'copyUrl'); },
    };
  };
  const shareButton = button({shareUrl: options.url ?? defaultUrl, shareTitle: 'Завдання класу 7-А'});
  const copyButton = button({copyUrl: options.url ?? defaultUrl});
  const printButton = button({});
  const window = {
    location: new URL('https://school.invalid/index.php'), isSecureContext: options.secure ?? true,
    print() { printed++; },
    prompt(message, value) { prompts.push({message, value}); return options.promptValue ?? null; },
    setTimeout() { return 0; }, clearTimeout() {},
  };
  const navigator = {};
  if (options.native) navigator.share = async data => {
    shared.push(data);
    if (options.shareError) throw Object.assign(new Error('Unavailable'), {name: options.shareError});
  };
  if (options.clipboard !== false) navigator.clipboard = {async writeText(value) {
    if (options.clipboardError) throw new Error('Denied');
    copied.push(value);
  }};
  document = {
    body: makeElement('body'), activeElement: copyButton,
    querySelectorAll(selector) { return selector === '[data-print]' ? [printButton] : [shareButton, copyButton]; },
    createElement(tag) { const element = makeElement(tag); created.push(element); return element; },
    execCommand(command) {
      legacyCommands.push(command);
      if (options.legacyThrow) throw new Error('Legacy copying unavailable');
      if (options.legacy === false) return false;
      const textarea = this.body.children.find(element => element.tagName === 'textarea');
      if (!textarea?.selected) return false;
      legacyCopied.push(textarea.value);
      return true;
    },
  };
  vm.runInNewContext(source, {window, document, navigator, URL, setTimeout: window.setTimeout, clearTimeout: window.clearTimeout});
  return {shareButton, copyButton, printButton, status, shared, copied, legacyCopied, created, prompts,
    legacyCommands, panel, document, get printed() { return printed; }};
}

const idle = harness({native: true});
check(idle.shared.length === 0 && idle.copied.length === 0 && idle.printed === 0 && idle.legacyCommands.length === 0 && idle.prompts.length === 0, 'Loading must never share, copy, print or ask for copying.');
check(!idle.shareButton.hidden && !idle.copyButton.hidden && !idle.printButton.hidden, 'JavaScript must reveal working controls.');

await idle.shareButton.fire('click');
check(idle.shared.length === 1 && idle.shared[0].title === 'Завдання класу 7-А', 'The explicit native-share control must preserve the intended title.');
check(idle.shared[0].url === absoluteUrl && idle.copied.length === 0, 'Native sharing must preserve filters without copying separately.');
await idle.copyButton.fire('click');
check(idle.shared.length === 1 && idle.copied.length === 1 && idle.copied[0] === absoluteUrl, 'Copying must write the exact URL directly and bypass native sharing.');
check(idle.status.textContent.includes('скопійовано'), 'Successful copying must be announced.');
check(idle.panel.children.length === 0 && idle.created.length === 0, 'Normal copying must never create a visible URL row or a fallback element.');
await idle.printButton.fire('click');
check(idle.printed === 1, 'Printing must follow a deliberate click.');

const cancelled = harness({native: true, shareError: 'AbortError'});
await cancelled.shareButton.fire('click');
check(cancelled.copied.length === 0 && cancelled.legacyCommands.length === 0 && cancelled.prompts.length === 0 && cancelled.status.textContent === '', 'Cancelling native sharing must not copy or open another dialog.');

const deniedShare = harness({native: true, shareError: 'NotAllowedError'});
await deniedShare.shareButton.fire('click');
check(deniedShare.copied[0] === absoluteUrl, 'Unavailable native sharing must fall back to copying.');

const noNative = harness();
await noNative.shareButton.fire('click');
check(noNative.copied[0] === absoluteUrl, 'A browser without native sharing must support a copied link.');

const insecure = harness({native: true, secure: false});
await insecure.copyButton.fire('click');
check(insecure.shared.length === 0 && insecure.copied.length === 0 && insecure.legacyCopied[0] === absoluteUrl, 'HTTP browsing must use user-initiated fallback copying without secure-only APIs.');
check(insecure.created.length === 1 && insecure.created[0].tagName === 'textarea' && insecure.created[0].removed && insecure.document.body.children.length === 0, 'The fallback textarea must be temporary and removed after successful copying.');
check(insecure.document.activeElement === insecure.copyButton && insecure.status.textContent.includes('скопійовано'), 'Fallback success must restore focus and announce the result.');
check(insecure.panel.children.length === 0 && insecure.prompts.length === 0, 'Fallback success must keep the toolbar one line without exposing a URL field.');

const deniedClipboard = harness({clipboardError: true});
await deniedClipboard.copyButton.fire('click');
check(deniedClipboard.legacyCopied[0] === absoluteUrl && deniedClipboard.prompts.length === 0, 'Clipboard denial must try the temporary copy fallback before asking the user.');

const blocked = harness({clipboardError: true, legacy: false});
await blocked.copyButton.fire('click');
check(blocked.prompts.length === 1 && blocked.prompts[0].value === absoluteUrl, 'When both APIs fail, manual copying must remain available in a dialog.');
check(blocked.document.body.children.length === 0 && blocked.panel.children.length === 0, 'Complete clipboard failure must still remove temporary elements and avoid a permanent URL row.');
check(!blocked.status.textContent.includes('скопійовано'), 'Failure must never be reported as successful copying.');

const throwingLegacy = harness({clipboard: false, legacyThrow: true});
await throwingLegacy.copyButton.fire('click');
check(throwingLegacy.created[0]?.removed && throwingLegacy.document.body.children.length === 0 && throwingLegacy.prompts.length === 1, 'Thrown fallback errors must clean up and leave manual copying available.');

for (const url of ['https://attacker.invalid/phish', 'javascript:alert(1)', 'https://[']) {
  const unsafe = harness({url, native: true});
  await unsafe.copyButton.fire('click');
  check(unsafe.shared.length === 0 && unsafe.copied.length === 0 && unsafe.legacyCommands.length === 0 && unsafe.prompts.length === 0 && unsafe.status.textContent.includes('Не вдалося'), 'Cross-origin, active-scheme and malformed URLs must never reach clipboard or sharing APIs.');
}

console.log(`OK: ${checks} перевірок компактного копіювання, резервного буфера й друку.`);