/** Configured OneTrust embeds. Never treat a browser submit event as success. */
(function (Drupal, once, drupalSettings) {
  'use strict';
  const providerScript = 'https://privacyportal.onetrust.com/consent-embedded-forms/consent-receipt-scripts/OtFormStub.js';
  let activeEmbed = null;

  function message(marker, text) {
    let status = marker.querySelector('[data-ot-status]');
    if (!status) {
      status = document.createElement('p');
      status.setAttribute('data-ot-status', '');
      status.setAttribute('role', 'status');
      marker.appendChild(status);
    }
    status.textContent = text;
    status.hidden = false;
    return status;
  }

  function populate(form, config, marker) {
    const params = new URLSearchParams(window.location.search);
    const fields = {
      sourcePlatform: config.source_platform,
      dataSource: marker.getAttribute('data-onetrust-source') || (config.environment === 'production' ? config.data_source_prod : config.data_source_stage),
      utmSource: params.get('utm_source'),
      utmMedium: params.get('utm_medium'),
      utmCampaign: params.get('utm_campaign'),
      utmTerm: params.get('utm_term'),
      utmContent: params.get('utm_content')
    };
    Object.entries(fields).forEach(([name, value]) => {
      if (!value) return;
      const label = form.querySelector(`[data-name="${name}"]`);
      const input = label?.htmlFor ? form.querySelector(`#${CSS.escape(label.htmlFor)}`) : form.querySelector(`[name="${name}"]`);
      if (!input) return;
      input.value = value;
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      const wrapper = input.closest('.ot-form-field, .ot-form-group, [class*="form-field"], [class*="form-row"]');
      // Never hide unrelated required questions in a shared provider wrapper.
      if (wrapper && wrapper !== form && wrapper.querySelectorAll('input, select, textarea').length === 1) wrapper.hidden = true;
      else {
        if (label) label.hidden = true;
        input.hidden = true;
      }
    });
  }

  Drupal.behaviors.otWebform = {
    attach(context) {
      const config = drupalSettings.otWebform || {};
      once('ot-webform', '[data-dyne-onetrust-form]', context).forEach((marker) => {
        if (!config.enabled) {
          message(marker, Drupal.t('This form is currently unavailable. Please contact info@dyne-tx.com.'));
          return;
        }
        const profile = marker.dataset.dyneOnetrustProfile || (document.getElementById('ot-container-corpdm1') ? 'dm1' : 'contact');
        const prefix = profile === 'dm1' ? 'dm1_' : '';
        const id = config[prefix + (config.environment === 'production' ? 'prod_id' : 'staging_id')];
        const collectionPoint = config[`${prefix}collection_point_id`];
        if (!id || !/^[A-Za-z0-9_-]+$/.test(id) || !collectionPoint || !/^[A-Za-z0-9_-]+$/.test(collectionPoint)) {
          message(marker, Drupal.t('This form is currently unavailable. Please contact info@dyne-tx.com.'));
          return;
        }
        if (activeEmbed) {
          message(marker, Drupal.t('Please use the form already displayed on this page.'));
          return;
        }
        activeEmbed = marker;
        const status = message(marker, Drupal.t('Loading form…'));
        const container = document.getElementById(profile === 'dm1' ? 'ot-container-corpdm1' : 'ot-container') || marker;
        const target = document.getElementById(collectionPoint) || document.createElement('div');
        target.id = collectionPoint;
        if (!target.isConnected) container.appendChild(target);
        let loaded = false;
        let confirmed = false;
        let submitted = false;
        let providerForm = null;
        let observer;
        const unavailable = () => {
          if (loaded) return;
          status.textContent = Drupal.t('The form could not be loaded. Please try again or contact info@dyne-tx.com.');
          observer?.disconnect();
        };
        const inspect = () => {
          const form = target.querySelector('form[data-ot-cp-id], form') || container.querySelector('form[data-ot-cp-id]') || document.querySelector(`form[data-ot-cp-id="${id}"]`);
          if (!form || form === providerForm) return;
          providerForm = form;
          populate(form, config, marker);
          loaded = true;
          status.hidden = true;
          // Track which form initiated the provider workflow, without showing a
          // success message or sending analytics until its confirmation arrives.
          form.addEventListener('submit', () => { submitted = true; });
          // The provider's rendered Sign up control is a type=button .ot-submit;
          // it may start its request without a native form submit event.
          form.addEventListener('click', (event) => {
            const button = event.target.closest('button.ot-submit');
            if (button && !button.disabled) submitted = true;
          }, true);
          observer?.disconnect();
        };
        observer = new MutationObserver(inspect);
        observer.observe(document.body, { childList: true, subtree: true });
        const timeout = window.setTimeout(unavailable, 20000);
        const script = document.createElement('script');
        script.src = providerScript;
        script.async = true;
        script.dataset.id = id;
        script.setAttribute('worker', 'https://consent-api.onetrust.com');
        script.addEventListener('error', unavailable, { once: true });
        script.addEventListener('load', () => {
          if (window.OneTrust?.webform?.InstallCP) {
            window.OneTrust.webform.InstallCP(collectionPoint, inspect);
          }
          inspect();
        }, { once: true });
        // This is the provider confirmation event used in the source plugin.
        // No native submit/click handler ever claims a successful submission.
        window.addEventListener('OneTrustWebFormSubmitted', (event) => {
          if (!loaded || !submitted || confirmed || event.detail?.success === false || event.detail?.error) return;
          confirmed = true;
          window.clearTimeout(timeout);
          message(marker, config.thank_you_text || Drupal.t('Thank you! Your submission has been received.'));
          if (config.gtm_event) {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ event: config.gtm_event, formProfile: profile });
          }
        });
        document.head.appendChild(script);
      });
    }
  };
})(Drupal, once, drupalSettings);
