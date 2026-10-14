/** Corporate external-link notice. Native link behavior remains the fallback. */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.dyneExitModal = {
    attach(context) {
      // Archived page links belong to this Drupal site. Media is normalized by
      // the importer because not every archived asset has a local counterpart.
      once('dyne-internal-link', 'a[href]', context).forEach((link) => {
        try {
          const url = new URL(link.getAttribute('data-mdb-url') || link.getAttribute('href'), window.location.href);
          const legacyHost = ['dyne-tx.com', 'www.dyne-tx.com', 'dynetxdev.wpengine.com'].includes(url.hostname);
          const asset = /^\/(wp-content|sites\/default\/files|themes)\//.test(url.pathname)
            || /\.(?!html?$|php$)[a-z0-9]{2,8}$/i.test(url.pathname);
          if (!['http:', 'https:'].includes(url.protocol) || asset || link.hasAttribute('download') || link.closest('#dyneExitModal')) return;
          if (legacyHost) {
            link.setAttribute('href', `${url.pathname}${url.search}${url.hash}`);
          }
          // Page navigation stays in this Drupal tab; document downloads and
          // provider destinations retain their original target attributes.
          if ((legacyHost || url.host === window.location.host) && link.target === '_blank') link.removeAttribute('target');
        }
        catch (error) {
          // Let the browser handle a malformed or non-URL value normally.
        }
      });

      const modal = document.getElementById('dyneExitModal');
      const proceed = document.getElementById('dyneExitContinue');
      if (!modal || !proceed || !window.bootstrap?.Modal) return;
      proceed.classList.add('no-exit-modal');

      once('dyne-exit-continue', [proceed]).forEach((link) => {
        link.addEventListener('click', () => window.bootstrap.Modal.getInstance(modal)?.hide());
      });

      once('dyne-exit', '[data-dyne-exit], [data-mdb-target="#modalExternal"]', context).forEach((link) => {
        if (link.closest('#dyneExitModal') || link.matches('.no-exit-modal, .video-lightbox, .video-link, .community-popup-button, [data-dyne-video]')) return;
        if (link.classList.contains('dropdown-toggle') && link.closest('.dyne-mobile-nav')) return;
        let url;
        try {
          url = new URL(link.getAttribute('data-mdb-url') || link.getAttribute('href'), window.location.href);
        }
        catch (error) {
          return;
        }
        if (!['http:', 'https:'].includes(url.protocol) || url.host === window.location.host) return;

        link.addEventListener('click', (event) => {
          if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
          event.preventDefault();
          event.stopPropagation();
          proceed.href = url.href;
          const sourceNotice = link.getAttribute('data-mdb-target') === '#modalExternal';
          // The original Physicians notice explicitly continues in a new tab;
          // any other opt-in Drupal notice retains its link's native target.
          proceed.target = link.getAttribute('data-dyne-exit-target') || link.getAttribute('target') || (sourceNotice ? '_blank' : '_self');
          proceed.rel = 'noopener noreferrer';
          const instance = window.bootstrap.Modal.getOrCreateInstance(modal);
          const drawer = link.closest('.offcanvas.show');
          if (drawer && window.bootstrap.Offcanvas) window.bootstrap.Offcanvas.getInstance(drawer)?.hide();
          modal.addEventListener('hidden.bs.modal', () => {
            const returnTarget = drawer ? document.querySelector(`[aria-controls="${CSS.escape(drawer.id)}"]`) : link;
            if (returnTarget?.isConnected) returnTarget.focus();
          }, { once: true });
          instance.show(link);
        });
      });
    }
  };
})(Drupal, once);
