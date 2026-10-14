/** Quote and story carousel controls using the site's Bootstrap runtime. */
(function (Drupal, once) {
  'use strict';
  let sequence = 0;

  function attachMultiCarousel(carousel) {
    const inner = carousel.querySelector('.multi-carousel-inner');
    if (!inner) return;
    const slides = Array.from(inner.querySelectorAll(':scope > .multi-carousel-item'));
    if (!slides.length) return;
    const indicators = Array.from(carousel.querySelectorAll('.carousel-indicators button'));
    let index = 0;
    const last = () => Math.max(0, slides.length - Math.max(1, Math.round(inner.clientWidth / (slides[0].getBoundingClientRect().width || inner.clientWidth || 1))));
    const update = () => {
      const width = slides[0].getBoundingClientRect().width;
      if (width) index = Math.min(last(), Math.round(inner.scrollLeft / width));
      indicators.forEach((indicator, position) => {
        indicator.hidden = position > last();
        indicator.classList.toggle('active', position === index);
        if (position === index) indicator.setAttribute('aria-current', 'true');
        else indicator.removeAttribute('aria-current');
      });
    };
    const move = (position) => {
      const total = last() + 1;
      index = (position + total) % total;
      inner.scrollTo({ left: slides[0].getBoundingClientRect().width * index, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    };
    carousel.setAttribute('role', 'region');
    carousel.setAttribute('aria-roledescription', Drupal.t('carousel'));
    if (!carousel.getAttribute('aria-label')) carousel.setAttribute('aria-label', Drupal.t('Stories and quotations'));
    inner.tabIndex = 0;
    inner.setAttribute('aria-label', Drupal.t('Use arrow keys or swipe to browse'));
    carousel.querySelectorAll('.carousel-control-prev, .carousel-control-next').forEach((control) => {
      const previous = control.classList.contains('carousel-control-prev');
      control.setAttribute('aria-label', Drupal.t(previous ? 'Previous slide' : 'Next slide'));
      control.addEventListener('click', (event) => {
        event.preventDefault();
        move(index + (previous ? -1 : 1));
      });
    });
    indicators.forEach((indicator, position) => {
      indicator.setAttribute('aria-label', Drupal.t('Go to slide @number', { '@number': position + 1 }));
      indicator.addEventListener('click', () => move(position));
    });
    inner.addEventListener('keydown', (event) => {
      if (event.target !== inner || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      move(event.key === 'Home' ? 0 : event.key === 'End' ? last() : index + (event.key === 'ArrowLeft' ? -1 : 1));
    });
    inner.addEventListener('scroll', update, { passive: true });
    if (window.ResizeObserver) new ResizeObserver(update).observe(inner);
    update();
  }

  Drupal.behaviors.dyneCarousel = {
    attach(context) {
      once('dyne-carousel', '.dyne-carousel, .multi-carousel', context).forEach((carousel) => {
        if (carousel.classList.contains('multi-carousel')) {
          attachMultiCarousel(carousel);
          return;
        }
        if (!window.bootstrap?.Carousel) return;
        const inner = carousel.querySelector('.carousel-inner, .multi-carousel-inner');
        if (!inner) return;
        inner.classList.add('carousel-inner');
        const slides = Array.from(inner.children).filter((item) => item.matches('.carousel-item, .multi-carousel-item'));
        if (!slides.length) return;
        carousel.classList.add('carousel', 'slide');
        if (!carousel.id || document.querySelectorAll(`[id="${CSS.escape(carousel.id)}"]`).length > 1) {
          carousel.id = `dyne-carousel-${++sequence}`;
        }
        slides.forEach((slide, index) => {
          slide.classList.add('carousel-item');
          slide.setAttribute('role', 'group');
          slide.setAttribute('aria-roledescription', Drupal.t('slide'));
          slide.setAttribute('aria-label', Drupal.t('@current of @total', { '@current': index + 1, '@total': slides.length }));
        });
        if (!slides.some((slide) => slide.classList.contains('active'))) slides[0].classList.add('active');
        carousel.setAttribute('role', 'region');
        carousel.setAttribute('aria-roledescription', Drupal.t('carousel'));
        if (!carousel.hasAttribute('aria-label')) carousel.setAttribute('aria-label', Drupal.t('Stories and quotations'));

        // Manual rotation avoids moving quotes while someone is reading them.
        carousel.removeAttribute('data-bs-ride');
        const instance = window.bootstrap.Carousel.getOrCreateInstance(carousel, { interval: false, ride: false, touch: true, keyboard: true });
        instance.pause();
        inner.setAttribute('aria-live', 'polite');
        carousel.querySelectorAll('.carousel-control-prev, .carousel-control-next, [data-slide]').forEach((control) => {
          const previous = control.classList.contains('carousel-control-prev') || control.dataset.slide === 'prev';
          control.setAttribute('data-bs-target', `#${carousel.id}`);
          control.setAttribute('data-bs-slide', previous ? 'prev' : 'next');
          control.setAttribute('aria-label', Drupal.t(previous ? 'Previous slide' : 'Next slide'));
          control.hidden = slides.length < 2;
        });
        const indicators = Array.from(carousel.querySelectorAll('.carousel-indicators button, .carousel-indicators li'));
        indicators.forEach((indicator, index) => {
          indicator.setAttribute('data-bs-target', `#${carousel.id}`);
          indicator.setAttribute('data-bs-slide-to', String(index));
          indicator.setAttribute('aria-label', Drupal.t('Go to slide @number', { '@number': index + 1 }));
          indicator.hidden = slides.length < 2;
        });
        const update = () => {
          const active = slides.findIndex((slide) => slide.classList.contains('active'));
          indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === active);
            if (index === active) indicator.setAttribute('aria-current', 'true');
            else indicator.removeAttribute('aria-current');
          });
          slides.forEach((slide, index) => slide.setAttribute('aria-hidden', String(index !== active)));
        };
        carousel.addEventListener('slid.bs.carousel', update);
        update();
      });
    }
  };
})(Drupal, once);
