(() => {
  'use strict';

  const root = document.documentElement;
  const body = document.body;

  const isCrm = Boolean(document.querySelector('.crm-shell'));
  const isAdmin = Boolean(document.querySelector('.admin-shell'));

  root.classList.add('motion-ready');

  const revealSelector = isAdmin
    ? [
        '.admin-head',
        '.admin-stat',
        '.admin-panel',
        '.admin-calendar-card',
        '.admin-media-card',
        '.admin-notice',
      ].join(',')
    : [
        '.crm-head',
        '.crm-stat',
        '.crm-panel',
        '.crm-plan-card',
        '.crm-notice',
        '.crm-day',
      ].join(',');

  const revealClass = isAdmin
    ? 'admin-ui-reveal'
    : 'crm-ui-reveal';

  const revealItems = Array.from(
    document.querySelectorAll(revealSelector)
  );

  revealItems.forEach((item, index) => {
    item.classList.add(revealClass);
    item.style.setProperty(
      '--ui-delay',
      Math.min(index * 45, 270) + 'ms'
    );
  });

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
      (entries, instance) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) {
            return;
          }

          entry.target.classList.add('is-visible');
          instance.unobserve(entry.target);
        });
      },
      {
        threshold: 0.08,
        rootMargin: '0px 0px -24px 0px',
      }
    );

    revealItems.forEach((item) => observer.observe(item));
  } else {
    revealItems.forEach((item) => {
      item.classList.add('is-visible');
    });
  }

  const toggle = document.querySelector(
    isAdmin
      ? '.admin-mobile-toggle'
      : '.crm-mobile-toggle'
  );

  const backdrop = document.querySelector(
    isAdmin
      ? '.admin-sidebar-backdrop'
      : '.crm-sidebar-backdrop'
  );

  const openClass = isAdmin
    ? 'admin-nav-open'
    : 'crm-nav-open';

  const closeNav = () => {
    body.classList.remove(openClass);

    if (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
    }
  };

  const openNav = () => {
    body.classList.add(openClass);

    if (toggle) {
      toggle.setAttribute('aria-expanded', 'true');
    }
  };

  toggle?.addEventListener('click', () => {
    body.classList.contains(openClass)
      ? closeNav()
      : openNav();
  });

  backdrop?.addEventListener('click', closeNav);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeNav();
    }
  });

  document
    .querySelectorAll(
      isAdmin
        ? '.admin-sidebar a'
        : '.crm-sidebar a'
    )
    .forEach((link) => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= (isAdmin ? 1000 : 1100)) {
          closeNav();
        }
      });
    });

  const buttons = document.querySelectorAll(
    isAdmin
      ? '.admin-button'
      : '.crm-button, .crm-plan-cta'
  );

  buttons.forEach((button) => {
    button.addEventListener('pointermove', (event) => {
      const rect = button.getBoundingClientRect();

      button.style.setProperty(
        '--pointer-x',
        event.clientX - rect.left + 'px'
      );

      button.style.setProperty(
        '--pointer-y',
        event.clientY - rect.top + 'px'
      );
    });
  });
})();
