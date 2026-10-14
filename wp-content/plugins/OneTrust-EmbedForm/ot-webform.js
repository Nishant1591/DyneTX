/**
 * OneTrust WebForm Embed — Generic Agency Package
 * Version: 1.3.1
 * Compatible with: Static HTML, WordPress, Drupal
 *
 * All hidden fields (sourcePlatform, dataSource, UTMs) are optional.
 * If a field is not present on the OT form it is silently skipped.
 * If a UTM is not in the URL it is silently skipped.
 *
 * Config priority (highest to lowest):
 *   1. window.OT_PAGE_CONFIG  — per-page shortcode override
 *   2. window.OT_WP_CONFIG    — WordPress global settings
 *   3. drupalSettings.otWebform — Drupal global settings
 *   4. OT_CONFIG defaults below — static HTML fallback
 */

(function (global) {
  'use strict';

  // ─────────────────────────────────────────────
  //  DEFAULT CONFIGURATION — static HTML fallback
  // ─────────────────────────────────────────────
  var OT_CONFIG = {
    ids: {
      production: 'PROD-GUID-XXXX-XXXX-XXXXXXXXXXXX',
      staging:    'STAGE-GUID-XXXX-XXXX-XXXXXXXXXXXX-test'
    },
    stagingKeywords: ['dev', 'stage', 'staging', 'test', 'demo', 'uat', 'localhost'],
    scriptSrc: 'https://privacyportal.onetrust.com/consent-embedded-forms/consent-receipt-scripts/OtFormStub.js',
    workerUrl:  'https://consent-api.onetrust.com',
    fields: {
      sourcePlatform: 'https://your-website.com',
      dataSource: {
        production: 'Conference March 2026',
        staging:    'Conference March 2026 test'
      }
    },
    gtmEvent:          'onetrust_form_submitted',
    thankYouElementId: 'thank-you-message',
    debug: false
  };
  // ─────────────────────────────────────────────

  // ── Merge global CMS config ───────────────────
  (function applyCMSConfig() {
    var cms = global.OT_WP_CONFIG
           || (global.drupalSettings && global.drupalSettings.otWebform)
           || null;
    if (!cms) return;
    if (cms.prod_id)          OT_CONFIG.ids.production        = cms.prod_id;
    if (cms.staging_id)       OT_CONFIG.ids.staging           = cms.staging_id;
    if (cms.staging_keywords) OT_CONFIG.stagingKeywords       = cms.staging_keywords.split(',').map(function(s){ return s.trim(); });
    if (cms.source_platform)  OT_CONFIG.fields.sourcePlatform = cms.source_platform;
    if (cms.gtm_event)        OT_CONFIG.gtmEvent              = cms.gtm_event;
    if (cms.thank_you_id)     OT_CONFIG.thankYouElementId     = cms.thank_you_id;
    if (cms.debug === 'true' || cms.debug === true) OT_CONFIG.debug = true;
    // dataSource resolved lazily so OT_PAGE_CONFIG per-form override always wins
  }());

  // ── Utility: logger ──────────────────────────
  function log() {
    if (!OT_CONFIG.debug) return;
    var args = Array.prototype.slice.call(arguments);
    args.unshift('[OT-Embed]');
    console.log.apply(console, args);
  }

  // ── Utility: detect environment ───────────────
  function isStaging() {
    var hostname = global.location.hostname.toLowerCase();
    return OT_CONFIG.stagingKeywords.some(function (kw) {
      return hostname.indexOf(kw) !== -1;
    });
  }

  // ── Utility: get UTM params from URL ─────────
  // Returns only keys actually present in the URL.
  // Keys absent from URL are not in the returned object at all.
  function getUTMParams() {
    var params = new URLSearchParams(global.location.search);
    var result = {};
    ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach(function (k) {
      var val = params.get(k);
      if (val !== null && val !== '') result[k] = val; // only add if truly present
    });
    return result;
  }

  // ── Utility: find OT form field element by OT field name ──────
  // OT renders a <label data-name="fieldName" for="fieldId"> + <input id="fieldId">
  // Returns the INPUT element, or null if this field is not on the form.
  // Never throws.
  function getFieldElement(form, otFieldName) {
    try {
      var label = form.querySelector('[data-name="' + otFieldName + '"]');
      if (!label) return null;
      var inputId = label.getAttribute('for');
      if (!inputId) return null;
      return form.querySelector('#' + inputId) || null;
    } catch (e) {
      return null;
    }
  }

  // ── Utility: set a field value ────────────────
  // Skips silently if:
  //   - value is null / undefined / empty string (nothing to set)
  //   - field does not exist on this OT form
  function setField(form, otFieldName, value) {
    if (value === null || value === undefined || value === '') {
      log('Skip setField (no value):', otFieldName);
      return;
    }
    var el = getFieldElement(form, otFieldName);
    if (!el) {
      log('Skip setField (field not on form):', otFieldName);
      return;
    }
    el.value = value;
    log('Field set:', otFieldName, '=', value);
  }

  // ── Utility: hide a field's wrapper div ───────
  // IMPORTANT: called AFTER setField so value is already injected.
  // Finds the closest wrapping container and hides it entirely,
  // removing it from the visible form layout.
  // Skips silently if field not on form.
  function hideField(form, otFieldName) {
    var el = getFieldElement(form, otFieldName);
    if (!el) {
      log('Skip hideField (field not on form):', otFieldName);
      return;
    }

    try {
      // Walk up from the input to find its wrapping container.
      // OT typically wraps each field in a div with class containing "field" or "row".
      // We stop at the first div ancestor that is a direct child of the form or a section,
      // not the form itself — to avoid accidentally hiding the whole form.
      var wrapper = null;

      if (el.closest) {
        // Prefer the most specific OT wrapper class first
        wrapper = el.closest('.ot-form-field')
               || el.closest('.ot-form-group')
               || el.closest('[class*="form-field"]')
               || el.closest('[class*="form-row"]')
               || el.parentNode;
      } else {
        wrapper = el.parentNode;
      }

      // Safety: never hide the form itself
      if (!wrapper || wrapper === form || wrapper === document.body) {
        // Fallback: hide just the label and input individually
        var label = form.querySelector('[data-name="' + otFieldName + '"]');
        if (label) label.style.cssText += ';display:none!important';
        el.style.cssText += ';display:none!important';
        log('Field hidden (individual fallback):', otFieldName);
        return;
      }

      wrapper.style.cssText += ';display:none!important';
      wrapper.setAttribute('aria-hidden', 'true');
      log('Field wrapper hidden:', otFieldName);

    } catch (e) {
      log('Error hiding field (skipped):', otFieldName, e);
    }
  }

  // ── Resolve DataSource with full priority chain ──
  // Returns null if not configured anywhere — field will be skipped.
  function resolveDataSource(env) {
    var key = (env === 'staging') ? 'data_source_stage' : 'data_source_prod';

    if (global.OT_PAGE_CONFIG && global.OT_PAGE_CONFIG[key]) {
      log('DataSource → OT_PAGE_CONFIG:', global.OT_PAGE_CONFIG[key]);
      return global.OT_PAGE_CONFIG[key];
    }
    if (global.OT_WP_CONFIG && global.OT_WP_CONFIG[key]) {
      log('DataSource → OT_WP_CONFIG:', global.OT_WP_CONFIG[key]);
      return global.OT_WP_CONFIG[key];
    }
    var drupal = global.drupalSettings && global.drupalSettings.otWebform;
    if (drupal && drupal[key]) {
      log('DataSource → drupalSettings:', drupal[key]);
      return drupal[key];
    }
    var fallback = OT_CONFIG.fields.dataSource[env];
    if (fallback) {
      log('DataSource → OT_CONFIG default:', fallback);
      return fallback;
    }
    log('DataSource not configured — will skip if field absent');
    return null;
  }

  // ── Step 1: Inject the OneTrust script ───────
  function injectOTScript() {
    var env    = isStaging() ? 'staging' : 'production';
    var dataId = OT_CONFIG.ids[env];
    log('Environment:', env, '| data-id:', dataId);
    var script = document.createElement('script');
    script.src = OT_CONFIG.scriptSrc;
    script.setAttribute('data-id', dataId);
    script.setAttribute('worker', OT_CONFIG.workerUrl);
    document.head.appendChild(script);
    log('OneTrust script injected');
  }

  // ── Step 2: Populate then hide each optional field ────────────
  //
  // Two-pass approach — SET all values first, THEN hide wrappers.
  // This avoids any risk of hiding a wrapper before its value is written,
  // and prevents one field's hide operation from interfering with another.
  //
  // Each field is fully independent:
  //   - Missing from form    → skipped (getFieldElement returns null)
  //   - Value not available  → setField skips, hideField still runs
  //     (field exists on form with no value → still hidden from user)
  //
  function populateForm() {
    var form = document.querySelector('form[data-ot-cp-id]');
    if (!form) return false;

    log('OT form found — processing fields');

    var env  = isStaging() ? 'staging' : 'production';
    var utms = getUTMParams();

    // ── PASS 1: Set all values ──
    // Each field is optional — setField skips if value missing or field absent
    setField(form, 'sourcePlatform', OT_CONFIG.fields.sourcePlatform || null);
    setField(form, 'dataSource',     resolveDataSource(env));
    setField(form, 'utmSource',      utms.utm_source   || null);
    setField(form, 'utmMedium',      utms.utm_medium   || null);
    setField(form, 'utmCampaign',    utms.utm_campaign || null);
    setField(form, 'utmTerm',        utms.utm_term     || null);
    setField(form, 'utmContent',     utms.utm_content  || null);

    // ── PASS 2: Hide wrappers ──
    // Only hides fields that actually exist on this form.
    // Runs after all values are set so DOM state is stable.
    hideField(form, 'sourcePlatform');
    hideField(form, 'dataSource');
    hideField(form, 'utmSource');
    hideField(form, 'utmMedium');
    hideField(form, 'utmCampaign');
    hideField(form, 'utmTerm');
    hideField(form, 'utmContent');

    return true;
  }

  // ── Step 3: Watch for OT form injection ──────
  function watchForForm() {
    if (populateForm()) {
      log('Form populated immediately');
      return;
    }
    var observer = new MutationObserver(function () {
      if (populateForm()) {
        log('Form populated via observer — disconnecting');
        observer.disconnect();
      }
    });
    observer.observe(document.body, { childList: true, subtree: true });
    log('MutationObserver watching for form...');
  }

  // ── Step 4: Handle form submission ───────────
  function handleFormSubmit(event) {
    log('OneTrustWebFormSubmitted fired', event.detail);
    global.dataLayer = global.dataLayer || [];
    global.dataLayer.push({
      event: OT_CONFIG.gtmEvent,
      formDetails: {
        source:     (event.detail && event.detail.sourcePlatform) || '',
        dataSource: (event.detail && event.detail.dataSource)     || '',
        timestamp:  new Date().toISOString()
      }
    });
    if (OT_CONFIG.thankYouElementId) {
      var el = document.getElementById(OT_CONFIG.thankYouElementId);
      if (el) el.style.display = 'block';
    }
  }

  // ── Bootstrap ─────────────────────────────────
  function init() {
    injectOTScript();
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', watchForForm);
    } else {
      watchForForm();
    }
    global.addEventListener('OneTrustWebFormSubmitted', handleFormSubmit);
  }

  init();

}(window));
