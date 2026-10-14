/** Legacy presentation behaviors implemented with Drupal and Bootstrap 5. */
(function (Drupal, once) {
  'use strict';

  function mediaUnavailable(video) {
    if (!video.parentElement) return;
    const message = document.createElement('p');
    message.className = 'dyne-video-error';
    message.setAttribute('role', 'alert');
    message.textContent = Drupal.t('This video is currently unavailable. Please try again later.');
    video.replaceWith(message);
  }

  function playerFor(value, title) {
    if (!value) return null;
    let url;
    try {
      url = new URL(/^[\w-]{11}$/.test(value) ? `https://www.youtube.com/watch?v=${value}` : value, window.location.href);
    }
    catch (error) {
      return null;
    }
    if (!['https:', 'http:'].includes(url.protocol)) return null;
    let source;
    const host = url.hostname.replace(/^www\./, '');
    if (['youtube.com', 'youtube-nocookie.com', 'm.youtube.com', 'youtu.be'].includes(host)) {
      const id = host === 'youtu.be' ? url.pathname.slice(1) : url.searchParams.get('v') || url.pathname.split('/').filter(Boolean).pop();
      if (!/^[\w-]{11}$/.test(id || '')) return null;
      source = `https://www.youtube-nocookie.com/embed/${id}?autoplay=1&rel=0`;
    }
    else if (['vimeo.com', 'player.vimeo.com'].includes(host)) {
      const id = url.pathname.split('/').filter(Boolean).find((part) => /^\d+$/.test(part));
      if (!id) return null;
      source = `https://player.vimeo.com/video/${id}?autoplay=1`;
    }
    else if (/\.(mp4|webm|ogv)$/i.test(url.pathname)) {
      const video = document.createElement('video');
      video.src = url.href;
      video.controls = true;
      video.autoplay = true;
      video.playsInline = true;
      video.setAttribute('aria-label', title);
      video.addEventListener('error', () => mediaUnavailable(video), { once: true });
      return video;
    }
    else return null;

    const frame = document.createElement('iframe');
    frame.src = source;
    frame.title = title;
    frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
    frame.allowFullscreen = true;
    frame.referrerPolicy = 'strict-origin-when-cross-origin';
    return frame;
  }

  function videoDialog() {
    let modal = document.getElementById('dyneVideoModal');
    if (modal) return modal;
    modal = document.createElement('div');
    modal.id = 'dyneVideoModal';
    modal.className = 'modal fade dyne-video-modal';
    modal.tabIndex = -1;
    modal.setAttribute('aria-hidden', 'true');
    modal.setAttribute('aria-labelledby', 'dyneVideoTitle');
    // Static structure only; content and URLs are set through DOM properties.
    modal.innerHTML = '<div class="modal-dialog modal-dialog-centered modal-xl"><div class="modal-content"><div class="modal-header"><h2 id="dyneVideoTitle" class="modal-title h5"></h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="ratio ratio-16x9" data-dyne-player></div></div></div></div>';
    modal.querySelector('.btn-close').setAttribute('aria-label', Drupal.t('Close video'));
    modal.addEventListener('hide.bs.modal', () => modal.querySelector('[data-dyne-player]').replaceChildren());
    document.body.appendChild(modal);
    return modal;
  }

  function findHashTarget(hash) {
    if (!hash || hash === '#') return null;
    try {
      return document.getElementById(decodeURIComponent(hash.replace(/^#/, '')));
    }
    catch (error) {
      return null;
    }
  }

  function revealHash(hash) {
    if (!hash || hash === '#' || !window.bootstrap) return;
    let target = findHashTarget(hash);
    // The source pipeline has separate desktop sections and mobile panels.
    // Menu links use the desktop hash, so reveal its mobile counterpart.
    const mobilePanel = window.matchMedia('(max-width: 991.98px)').matches ? findHashTarget(`#collapse${hash.slice(1)}`) : null;
    if (mobilePanel?.closest('.pipeline-chart-24, .pipeline-chart-25, .pipeline-chart-26')) target = mobilePanel;
    if (!target) return;
    // Team section anchors precede their collapsed Drupal paragraph groups.
    const teamToggle = Array.from(document.querySelectorAll('[data-dyne-team-anchor]'))
      .find((control) => control.dataset.dyneTeamAnchor === hash.slice(1));
    const teamPanel = teamToggle && findHashTarget(teamToggle.getAttribute('data-bs-target') || teamToggle.getAttribute('href'));
    const panel = teamPanel || target.closest('.collapse');
    if (panel && !panel.classList.contains('show') && window.bootstrap.Collapse) {
      panel.addEventListener('shown.bs.collapse', () => target.scrollIntoView({ block: 'start' }), { once: true });
      window.bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false }).show();
    }
    else if (mobilePanel === target) target.scrollIntoView({ block: 'start' });
    if (target.classList.contains('tab-pane') && window.bootstrap.Tab) {
      const tab = Array.from(document.querySelectorAll('[data-bs-toggle="tab"]')).find((control) => (control.getAttribute('data-bs-target') || control.getAttribute('href')) === hash);
      if (tab) window.bootstrap.Tab.getOrCreateInstance(tab).show();
    }
  }

  Drupal.behaviors.dyneCustom = {
    attach(context) {
      once('dyne-media-error', '.dyne-legacy-content video', context).forEach((video) => {
        const failed = () => mediaUnavailable(video);
        video.addEventListener('error', failed, { once: true });
        const sources = Array.from(video.querySelectorAll('source'));
        sources.at(-1)?.addEventListener('error', failed, { once: true });
        if (video.error || video.networkState === HTMLMediaElement.NETWORK_NO_SOURCE) failed();
      });

      // The archive's cookie policy embeds a declaration on this page only.
      // This does not install the site's old global tracking/consent scripts.
      once('dyne-cookie-declaration', '[data-dyne-cookie-declaration]', context).forEach((marker) => {
        if (document.getElementById('CookieDeclaration')) return;
        const declaration = document.createElement('script');
        declaration.id = 'CookieDeclaration';
        declaration.src = 'https://consent.cookiebot.com/23b37b73-f275-4a71-8aa5-ac47aa8bfbfe/cd.js';
        declaration.async = true;
        marker.appendChild(declaration);
      });

      // MDB attributes in imported editorial HTML use the existing Bootstrap 5
      // runtime. The old WordPress/MDB scripts are never needed in Drupal.
      once('dyne-legacy-control', '[data-mdb-collapse-init], [data-mdb-pill-init], [data-mdb-tab-init], [data-mdb-modal-init], [data-mdb-dismiss]', context).forEach((control) => {
        let kind = control.hasAttribute('data-mdb-collapse-init') ? 'collapse' : control.hasAttribute('data-mdb-modal-init') ? 'modal' : 'tab';
        const target = control.getAttribute('data-mdb-target');
        if (control.hasAttribute('data-mdb-dismiss')) {
          control.setAttribute('data-bs-dismiss', control.getAttribute('data-mdb-dismiss'));
          return;
        }
        if (control.hasAttribute('data-mdb-url') || control.matches('.video-link, .community-popup-button')) return;
        if (control.closest('.pipeline-chart-24, .pipeline-chart-25, .pipeline-chart-26')) {
          const media = window.matchMedia('(max-width: 991.98px)');
          const update = () => {
            if (media.matches) control.setAttribute('data-bs-toggle', kind);
            else control.removeAttribute('data-bs-toggle');
          };
          update();
          media.addEventListener('change', update);
        }
        else control.setAttribute('data-bs-toggle', kind);
        if (target) control.setAttribute('data-bs-target', target);
      });

      // Legacy community links have ordinary href/target behavior on the live
      // site; only explicit lightbox triggers and covers create players.
      once('dyne-video', 'a.video-lightbox, .video-link, .custom-cover, [data-dyne-video]', context).forEach((trigger) => {
        const value = trigger.getAttribute('data-dyne-video') || trigger.getAttribute('embd-data-url') || trigger.getAttribute('data-video-url') || trigger.getAttribute('href');
        const title = trigger.getAttribute('embd-data-title') || trigger.getAttribute('data-video-title') || trigger.getAttribute('aria-label') || trigger.closest('section')?.querySelector('h2')?.textContent || Drupal.t('Watch video');
        let inlinePlaying = false;
        if (!trigger.matches('a, button, input')) {
          trigger.setAttribute('role', 'button');
          trigger.tabIndex = 0;
          trigger.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
              event.preventDefault();
              trigger.click();
            }
          });
        }
        if (!trigger.textContent.trim() && !trigger.getAttribute('aria-label')) trigger.setAttribute('aria-label', title);
        trigger.addEventListener('click', (event) => {
          if (inlinePlaying) return;
          if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
          const player = playerFor(value, title);
          if (!player) return;
          if (trigger.classList.contains('custom-cover')) {
            inlinePlaying = true;
            event.preventDefault();
            event.stopPropagation();
            trigger.classList.remove('custom-cover');
            trigger.classList.add('ratio', 'ratio-16x9');
            trigger.removeAttribute('role');
            trigger.removeAttribute('tabindex');
            trigger.replaceChildren(player);
            player.focus();
            return;
          }
          if (!window.bootstrap?.Modal) return;
          event.preventDefault();
          event.stopPropagation();
          const modal = videoDialog();
          modal.querySelector('#dyneVideoTitle').textContent = title;
          modal.querySelector('[data-dyne-player]').replaceChildren(player);
          modal.addEventListener('hidden.bs.modal', () => {
            if (trigger.isConnected) trigger.focus();
          }, { once: true });
          window.bootstrap.Modal.getOrCreateInstance(modal).show(trigger);
        });
      });

      once('dyne-home-video', '[data-dyne-home-video]', context).forEach((video) => {
        const wrap = video.closest('.dyne-home-hero');
        const still = wrap?.querySelector('[data-dyne-home-still], picture');
        const motion = wrap?.querySelector('[data-dyne-hero-motion]');
        const orientation = window.matchMedia('(orientation: landscape)');
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
        let ended = false;
        let pausedByUser = false;
        let currentSource = '';
        const showStill = () => {
          ended = true;
          video.pause();
          video.hidden = true;
          if (still) still.hidden = false;
          if (motion) motion.hidden = true;
          wrap?.classList.add('is-complete');
        };
        const load = () => {
          if (reduced.matches) {
            showStill();
            return;
          }
          if (ended) return;
          const variant = orientation.matches ? 'desktop' : 'mobile';
          const source = video.getAttribute(`data-${variant}-src`);
          if (!source || source === currentSource) return;
          const position = video.currentTime || 0;
          currentSource = source;
          video.poster = video.getAttribute(`data-${variant}-poster`) || '';
          video.muted = true;
          video.playsInline = true;
          video.loop = false;
          if (still) still.hidden = true;
          video.hidden = false;
          if (motion) motion.hidden = false;
          video.addEventListener('loadedmetadata', () => {
            if (ended || video.getAttribute('src') !== source) return;
            if (position && Number.isFinite(video.duration)) video.currentTime = Math.min(position, Math.max(0, video.duration - 0.1));
            if (!pausedByUser) video.play().catch(showStill);
          }, { once: true });
          video.src = source;
          video.load();
        };
        video.addEventListener('ended', showStill);
        video.addEventListener('error', showStill);
        motion?.addEventListener('click', () => {
          if (video.paused) {
            pausedByUser = false;
            video.play().catch(showStill);
            motion.textContent = Drupal.t('Pause animation');
            motion.setAttribute('aria-pressed', 'false');
          }
          else {
            pausedByUser = true;
            video.pause();
            motion.textContent = Drupal.t('Play animation');
            motion.setAttribute('aria-pressed', 'true');
          }
        });
        orientation.addEventListener('change', load);
        reduced.addEventListener('change', load);
        load();
      });

      once('dyne-expand-letter', '.read-more, .read-less', context).forEach((button) => {
        const section = button.closest('section, .letter, .card-description') || button.parentElement;
        const body = section.querySelector('.letter-body');
        if (!body) return;
        button.addEventListener('click', (event) => {
          event.preventDefault();
          const open = button.classList.contains('read-more');
          body.classList.toggle('d-none', !open);
          section.querySelector('.read-more')?.classList.toggle('d-none', open);
          section.querySelectorAll('.read-more, .read-less').forEach((control) => control.setAttribute('aria-expanded', String(open)));
        });
      });

      once('dyne-collapse-label', '.perspective-articles .collapse', context).forEach((panel) => {
        const label = panel.closest('.perspective-articles')?.querySelector('.red-label');
        if (!label) return;
        panel.addEventListener('shown.bs.collapse', () => { label.textContent = Drupal.t('Less content'); });
        panel.addEventListener('hidden.bs.collapse', () => { label.textContent = Drupal.t('Additional content'); });
      });

      once('dyne-job-filters', '.dyne-jobs[data-dyne-jobs-source="content"]', context).forEach((listing) => {
        const departmentFilter = listing.querySelector('[data-dyne-job-filter="department"]');
        const locationFilter = listing.querySelector('[data-dyne-job-filter="location"]');
        const groups = Array.from(listing.querySelectorAll('[data-dyne-job-group]'));
        const emptyMessage = listing.querySelector('[data-dyne-jobs-empty]');
        if (emptyMessage) emptyMessage.setAttribute('role', 'status');
        const applyFilters = () => {
          const department = departmentFilter?.value || '';
          const location = locationFilter?.value || '';
          let visibleJobs = 0;
          groups.forEach((group) => {
            const departmentMatches = !department || group.dataset.dyneJobGroup === department;
            let visibleInGroup = 0;
            group.querySelectorAll('[data-dyne-job-location]').forEach((job) => {
              job.hidden = !departmentMatches || Boolean(location && job.dataset.dyneJobLocation !== location);
              if (!job.hidden) visibleInGroup += 1;
            });
            group.hidden = visibleInGroup === 0;
            visibleJobs += visibleInGroup;
          });
          if (emptyMessage) emptyMessage.hidden = visibleJobs > 0;
        };
        departmentFilter?.addEventListener('change', applyFilters);
        locationFilter?.addEventListener('change', applyFilters);
        applyFilters();
      });

      once('dyne-font-ready', [document.documentElement]).forEach((root) => {
        if (!document.fonts?.load) return;
        document.fonts.load('100 1em "Google Sans Flex"').then((faces) => {
          root.classList.add(faces.length ? 'fonts-loaded' : 'fonts-timeout');
        }, () => root.classList.add('fonts-timeout'));
      });

      once('dyne-hash-navigation', [document.documentElement]).forEach(() => {
        const reveal = () => revealHash(window.location.hash);
        window.addEventListener('hashchange', reveal);
        reveal();
      });

      once('dyne-repeat-hash', 'a[href*="#"]', context).forEach((link) => {
        link.addEventListener('click', () => {
          const url = new URL(link.href, window.location.href);
          if (url.origin === window.location.origin && url.pathname === window.location.pathname && url.hash === window.location.hash) revealHash(url.hash);
        });
      });
    }
  };
})(Drupal, once);
