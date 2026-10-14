/** Desktop dropdowns and the mobile drawer, attached once for Drupal AJAX. */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.dyneNavigation = {
    attach(context) {
      once('dyne-nav', '.dyne-nav .dropdown', context).forEach((item) => {
        const toggle = item.querySelector(':scope > .nav-link');
        const menu = item.querySelector(':scope > .dropdown-menu');
        if (!toggle || !menu) return;
        const desktop = () => window.matchMedia('(min-width: 992px)').matches;
        const setOpen = (open) => {
          menu.classList.toggle('show', open);
          toggle.classList.toggle('hover', open);
          toggle.setAttribute('aria-expanded', String(open));
        };
        item.addEventListener('mouseenter', () => {
          if (desktop()) setOpen(true);
        });
        item.addEventListener('mouseleave', () => {
          if (!item.contains(document.activeElement)) setOpen(false);
        });
        item.addEventListener('focusin', () => setOpen(true));
        item.addEventListener('focusout', (event) => {
          if (!item.contains(event.relatedTarget)) setOpen(false);
        });
        item.addEventListener('keydown', (event) => {
          const links = Array.from(menu.querySelectorAll('a[href]'));
          if (event.key === 'Escape') {
            toggle.focus();
            setOpen(false);
            event.preventDefault();
          }
          else if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key) && links.length) {
            event.preventDefault();
            setOpen(true);
            const index = links.indexOf(document.activeElement);
            let next = event.key === 'ArrowUp' ? index - 1 : index + 1;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = links.length - 1;
            links[(next + links.length) % links.length].focus();
          }
        });
      });

      once('dyne-mobile-nav', '.dyne-mobile-nav', context).forEach((nav) => {
        const links = Array.from(nav.querySelectorAll('.dropdown > .dropdown-toggle'));
        const select = (selected) => {
          links.forEach((link) => {
            const open = link === selected;
            link.setAttribute('aria-expanded', String(open));
            link.classList.toggle('active', open);
            link.parentElement.classList.toggle('is-selected', open);
            link.parentElement.querySelector(':scope > .dropdown-menu')?.classList.toggle('show', open);
          });
          nav.classList.toggle('has-selected-items', Boolean(selected));
        };
        links.forEach((link) => {
          link.setAttribute('aria-expanded', 'false');
          link.addEventListener('click', (event) => {
            event.preventDefault();
            select(link);
          });
        });
        nav.addEventListener('keydown', (event) => {
          if (event.key !== 'Escape') return;
          const open = links.find((link) => link.getAttribute('aria-expanded') === 'true');
          if (open) {
            event.preventDefault();
            event.stopPropagation();
            open.focus();
            select(null);
          }
        });
      });

      once('dyne-drawer', '#dyneMobileDrawer', context).forEach((drawer) => {
        const triggers = document.querySelectorAll('[data-bs-target="#dyneMobileDrawer"]');
        const expanded = (open) => triggers.forEach((trigger) => trigger.setAttribute('aria-expanded', String(open)));
        expanded(false);
        drawer.addEventListener('show.bs.offcanvas', () => {
          expanded(true);
          document.body.classList.add('dyne-navigation-open');
        });
        drawer.addEventListener('hidden.bs.offcanvas', () => {
          expanded(false);
          document.body.classList.remove('dyne-navigation-open');
        });
        window.matchMedia('(min-width: 992px)').addEventListener('change', (event) => {
          if (event.matches) window.bootstrap?.Offcanvas.getInstance(drawer)?.hide();
        });
        drawer.addEventListener('click', (event) => {
          const link = event.target.closest('a[href]');
          if (link && !link.classList.contains('dropdown-toggle') && !link.hasAttribute('data-bs-toggle') && window.bootstrap?.Offcanvas) {
            window.bootstrap.Offcanvas.getInstance(drawer)?.hide();
          }
        });
      });

      once('dyne-header-contrast', '.dyne-header', context).forEach((header) => {
        const hero = document.querySelector('.hero, .dyne-hero--home');
        if (!hero) {
          header.classList.add('inverse');
          return;
        }
        if (!window.IntersectionObserver) return;
        const observer = new IntersectionObserver(([entry]) => {
          header.classList.toggle('inverse', !entry.isIntersecting);
        }, { rootMargin: `-${header.offsetHeight}px 0px 0px 0px` });
        observer.observe(hero);
      });
    }
  };
})(Drupal, once);
