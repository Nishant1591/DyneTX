const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const test = require('node:test');
const source = fs.readFileSync(`${__dirname}/../js/ot-webform.js`, 'utf8');

function fixture(overrides = {}, profile = 'contact') {
  const scripts = [];
  const events = {};
  const marker = {
    dataset: { dyneOnetrustProfile: profile }, children: [],
    querySelector(selector) { return selector === '[data-ot-status]' ? this.children.find((child) => child.attributes['data-ot-status'] === '') : null; },
    appendChild(child) { child.isConnected = true; this.children.push(child); },
    getAttribute() { return null; }
  };
  let form;
  const document = {
    head: { appendChild(script) { scripts.push(script); } }, body: {},
    getElementById() { return null; },
    querySelector() { return form || null; },
    createElement(tag) {
      return {
        tag, dataset: {}, attributes: {}, events: {}, children: [], isConnected: false,
        setAttribute(key, value) { this.attributes[key] = value; },
        addEventListener(name, callback) { this.events[name] = callback; },
        appendChild(child) { this.children.push(child); },
        querySelector() { return null; }
      };
    }
  };
  const config = {
    enabled: true, environment: 'staging', prod_id: 'production-id', staging_id: 'test-id',
    collection_point_id: 'ot-collection', thank_you_text: 'Provider confirmed receipt.',
    gtm_event: 'confirmed_form', ...overrides
  };
  const window = {
    location: { search: '', hostname: 'www.dyne-tx.com' },
    addEventListener(name, callback) { events[name] = callback; },
    setTimeout() { return 1; }, clearTimeout() {},
    OneTrust: { webform: { InstallCP(id, callback) { callback(); } } }
  };
  const Drupal = { behaviors: {}, t: (text) => text };
  vm.runInNewContext(source, {
    Drupal, drupalSettings: { otWebform: config },
    once: () => [marker], window, document, URLSearchParams, Event,
    CSS: { escape: (value) => value },
    MutationObserver: class { observe() {} disconnect() {} }
  });
  Drupal.behaviors.otWebform.attach(document);
  return {
    marker, scripts, window, events,
    status: () => marker.querySelector('[data-ot-status]'),
    injectForm() {
      form = { events: {}, querySelector() { return null; }, addEventListener(name, callback) { this.events[name] = callback; } };
      scripts[0].events.load();
      return form;
    }
  };
}

test('disabled embeds never request the provider script', () => {
  const page = fixture({ enabled: false });
  assert.equal(page.scripts.length, 0);
  assert.match(page.status().textContent, /currently unavailable/);
});

test('explicit test environment cannot become production based on hostname', () => {
  const page = fixture();
  assert.equal(page.scripts.length, 1);
  assert.equal(page.scripts[0].dataset.id, 'test-id');
});

test('unconfigured DM1 forms do not reuse the contact collection point', () => {
  const page = fixture({}, 'dm1');
  assert.equal(page.scripts.length, 0);
  assert.match(page.status().textContent, /currently unavailable/);
});

test('provider load failures report failure, never success', () => {
  const page = fixture();
  page.scripts[0].events.error();
  assert.match(page.status().textContent, /could not be loaded/);
  assert.equal(page.window.dataLayer, undefined);
});

test('native submit does not claim success or emit analytics', () => {
  const page = fixture();
  page.injectForm().events.submit();
  assert.equal(page.status().hidden, true);
  assert.equal(page.window.dataLayer, undefined);
});

test('an unrelated or explicitly failed provider event does not claim success', () => {
  const page = fixture();
  const form = page.injectForm();
  page.events.OneTrustWebFormSubmitted({ detail: {} });
  assert.equal(page.status().hidden, true);
  form.events.submit();
  page.events.OneTrustWebFormSubmitted({ detail: { success: false } });
  assert.equal(page.status().hidden, true);
  assert.equal(page.window.dataLayer, undefined);
});

test('provider confirmation after this form submits announces success once', () => {
  const page = fixture();
  page.injectForm().events.submit();
  page.events.OneTrustWebFormSubmitted({ detail: { success: true, email: 'not-for-analytics@example.test' } });
  page.events.OneTrustWebFormSubmitted({ detail: { success: true } });
  assert.equal(page.status().hidden, false);
  assert.equal(page.status().textContent, 'Provider confirmed receipt.');
  assert.equal(page.window.dataLayer.length, 1);
  assert.equal(page.window.dataLayer[0].event, 'confirmed_form');
  assert.equal(JSON.stringify(page.window.dataLayer).includes('email'), false);
});

test('the provider type=button control requires confirmation before success', () => {
  const page = fixture();
  const form = page.injectForm();
  form.events.click({ target: { closest: () => ({ disabled: false }) } });
  assert.equal(page.status().hidden, true);
  assert.equal(page.window.dataLayer, undefined);
  page.events.OneTrustWebFormSubmitted({ detail: { success: true } });
  assert.equal(page.status().textContent, 'Provider confirmed receipt.');
  assert.equal(page.window.dataLayer.length, 1);
});
