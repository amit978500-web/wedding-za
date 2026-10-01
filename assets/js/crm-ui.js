(() => {
  'use strict';

  const root = document.documentElement;
  const body = document.body;

  const isCrm = Boolean(
    document.querySelector('.crm-shell')
  );

  const isAdmin = Boolean(
    document.querySelector('.admin-shell')
  );

  if (!isCrm && !isAdmin) {
    return;
  }

  const prefersReducedMotion = window.matchMedia(
    '(prefers-reduced-motion: reduce)'
  ).matches;

  const uiScript = Array.from(
    document.scripts
  ).find((script) =>
    script.src.includes(
      '/assets/js/crm-ui.js'
    )
  );

  const assetRoot = uiScript
    ? uiScript.src.split(
        '/assets/js/crm-ui.js'
      )[0]
    : '';

  const fallbackImageUrl =
    assetRoot
      + '/assets/images/image-fallback.svg';

  document
    .querySelectorAll('img')
    .forEach((image) => {
      if (!image.hasAttribute('loading')) {
        image.loading = 'lazy';
      }

      image.decoding = 'async';

      image.addEventListener(
        'error',
        () => {
          if (
            image.dataset.wzFallback
              === '1'
            || !assetRoot
          ) {
            return;
          }

          image.dataset.wzFallback = '1';
          image.src = fallbackImageUrl;

          if (!image.alt.trim()) {
            image.alt =
              'Wedding Za image unavailable';
          }
        }
      );
    });

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

  if (
    'IntersectionObserver' in window
    && !prefersReducedMotion
  ) {
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

    revealItems.forEach((item) => {
      observer.observe(item);
    });
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
      toggle.setAttribute(
        'aria-expanded',
        'false'
      );
    }
  };

  const openNav = () => {
    body.classList.add(openClass);

    if (toggle) {
      toggle.setAttribute(
        'aria-expanded',
        'true'
      );
    }
  };

  toggle?.addEventListener('click', () => {
    body.classList.contains(openClass)
      ? closeNav()
      : openNav();
  });

  backdrop?.addEventListener(
    'click',
    closeNav
  );

  document
    .querySelectorAll(
      isAdmin
        ? '.admin-sidebar a'
        : '.crm-sidebar a'
    )
    .forEach((link) => {
      link.addEventListener('click', () => {
        if (
          window.innerWidth
          <= (isAdmin ? 1000 : 1100)
        ) {
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
    button.addEventListener(
      'pointermove',
      (event) => {
        const rect =
          button.getBoundingClientRect();

        button.style.setProperty(
          '--pointer-x',
          event.clientX
            - rect.left
            + 'px'
        );

        button.style.setProperty(
          '--pointer-y',
          event.clientY
            - rect.top
            + 'px'
        );
      }
    );
  });

  /*
  |--------------------------------------------------------------------------
  | Quick Jump command palette
  |--------------------------------------------------------------------------
  */

  const nav = document.querySelector(
    isAdmin
      ? '.admin-nav'
      : '.crm-nav'
  );

  const commandItems = [];
  const seenUrls = new Set();

  nav
    ?.querySelectorAll('a[href]')
    .forEach((link) => {
      const url = link.href;

      if (!url || seenUrls.has(url)) {
        return;
      }

      seenUrls.add(url);

      const item = link.closest(
        isAdmin
          ? '.admin-nav-item'
          : '.crm-nav-item'
      );

      const parentLink = item
        ?.querySelector(':scope > a');

      const parentLabel =
        parentLink
        && parentLink !== link
          ? parentLink.textContent.trim()
          : '';

      commandItems.push({
        label: link.textContent
          .replace(/\s+\d+$/, '')
          .trim(),
        section: parentLabel,
        url,
      });
    });

  const shortcutLabel =
    /Mac|iPhone|iPad/i.test(
      navigator.platform
    )
      ? '⌘ K'
      : 'Ctrl K';

  const trigger =
    document.createElement('button');

  trigger.type = 'button';
  trigger.className = isAdmin
    ? 'admin-command-trigger'
    : 'crm-command-trigger';

  trigger.setAttribute(
    'aria-label',
    'Open quick jump'
  );

  const triggerText =
    document.createElement('span');

  triggerText.textContent = 'Quick jump';

  const triggerKey =
    document.createElement('kbd');

  triggerKey.textContent = shortcutLabel;

  trigger.append(
    triggerText,
    triggerKey
  );

  body.appendChild(trigger);

  const palette =
    document.createElement('div');

  palette.className = 'wz-command';
  palette.setAttribute(
    'aria-hidden',
    'true'
  );

  palette.innerHTML = [
    '<div class="wz-command-card"',
    ' role="dialog"',
    ' aria-modal="true"',
    ' aria-label="Quick jump">',
    '<div class="wz-command-head">',
    '<div class="wz-command-mark">WZ</div>',
    '<input',
    ' class="wz-command-input"',
    ' type="search"',
    ' autocomplete="off"',
    ' placeholder="Search CRM pages…"',
    ' aria-label="Search CRM pages"',
    '>',
    '</div>',
    '<div class="wz-command-list"></div>',
    '</div>',
  ].join('');

  body.appendChild(palette);

  const paletteInput =
    palette.querySelector(
      '.wz-command-input'
    );

  const paletteList =
    palette.querySelector(
      '.wz-command-list'
    );

  let activeIndex = 0;
  let visibleItems = [];

  const renderPalette = () => {
    const query =
      paletteInput.value
        .trim()
        .toLowerCase();

    visibleItems = commandItems.filter(
      (item) => {
        const haystack = [
          item.label,
          item.section,
        ]
          .join(' ')
          .toLowerCase();

        return haystack.includes(query);
      }
    );

    activeIndex = Math.min(
      activeIndex,
      Math.max(
        visibleItems.length - 1,
        0
      )
    );

    paletteList.innerHTML = '';

    if (!visibleItems.length) {
      const empty =
        document.createElement('div');

      empty.className =
        'wz-command-empty';

      empty.textContent =
        'No matching CRM page.';

      paletteList.appendChild(empty);

      return;
    }

    visibleItems.forEach(
      (item, index) => {
        const button =
          document.createElement('button');

        button.type = 'button';

        button.className =
          'wz-command-item'
          + (
            index === activeIndex
              ? ' active'
              : ''
          );

        const copy =
          document.createElement('span');

        copy.textContent = item.label;

        const section =
          document.createElement('small');

        section.textContent =
          item.section || 'Workspace';

        button.append(
          copy,
          section
        );

        button.addEventListener(
          'mouseenter',
          () => {
            activeIndex = index;

            paletteList
              .querySelectorAll(
                '.wz-command-item'
              )
              .forEach(
                (
                  listItem,
                  listIndex
                ) => {
                  listItem.classList.toggle(
                    'active',
                    listIndex
                      === activeIndex
                  );
                }
              );
          }
        );

        button.addEventListener(
          'click',
          () => {
            window.location.href =
              item.url;
          }
        );

        paletteList.appendChild(
          button
        );
      }
    );
  };

  const openPalette = () => {
    activeIndex = 0;
    paletteInput.value = '';

    renderPalette();

    palette.classList.add('open');
    palette.setAttribute(
      'aria-hidden',
      'false'
    );

    window.setTimeout(
      () => {
        paletteInput.focus();
      },
      30
    );
  };

  const closePalette = () => {
    palette.classList.remove('open');
    palette.setAttribute(
      'aria-hidden',
      'true'
    );

    trigger.focus({
      preventScroll: true,
    });
  };

  trigger.addEventListener(
    'click',
    openPalette
  );

  palette.addEventListener(
    'click',
    (event) => {
      if (event.target === palette) {
        closePalette();
      }
    }
  );

  paletteInput.addEventListener(
    'input',
    () => {
      activeIndex = 0;
      renderPalette();
    }
  );

  paletteInput.addEventListener(
    'keydown',
    (event) => {
      if (
        event.key === 'ArrowDown'
        && visibleItems.length
      ) {
        event.preventDefault();

        activeIndex =
          (
            activeIndex + 1
          ) % visibleItems.length;

        renderPalette();

        return;
      }

      if (
        event.key === 'ArrowUp'
        && visibleItems.length
      ) {
        event.preventDefault();

        activeIndex =
          (
            activeIndex
            - 1
            + visibleItems.length
          ) % visibleItems.length;

        renderPalette();

        return;
      }

      if (
        event.key === 'Enter'
        && visibleItems[activeIndex]
      ) {
        event.preventDefault();

        window.location.href =
          visibleItems[
            activeIndex
          ].url;
      }
    }
  );

  /*
  |--------------------------------------------------------------------------
  | KPI count-up
  |--------------------------------------------------------------------------
  */

  const statSelector = isAdmin
    ? '.admin-stat strong'
    : '.crm-stat strong';

  const stats = Array.from(
    document.querySelectorAll(
      statSelector
    )
  );

  const animateStat = (element) => {
    if (
      prefersReducedMotion
      || element.dataset.counted === '1'
    ) {
      return;
    }

    const original =
      element.textContent.trim();

    if (!/^[\d,]+$/.test(original)) {
      return;
    }

    const target = Number(
      original.replace(/,/g, '')
    );

    if (
      !Number.isFinite(target)
      || target < 1
    ) {
      return;
    }

    element.dataset.counted = '1';
    element.classList.add(
      'crm-ui-counting'
    );

    const started =
      performance.now();

    const duration = 850;

    const format = (value) => {
      if (original.includes(',')) {
        return Math.round(value)
          .toLocaleString('en-IN');
      }

      return String(
        Math.round(value)
      );
    };

    const frame = (now) => {
      const progress = Math.min(
        (now - started) / duration,
        1
      );

      const eased =
        1
        - Math.pow(
          1 - progress,
          3
        );

      element.textContent = format(
        target * eased
      );

      if (progress < 1) {
        requestAnimationFrame(frame);
      } else {
        element.textContent =
          original;
      }
    };

    requestAnimationFrame(frame);
  };

  if (
    'IntersectionObserver' in window
    && !prefersReducedMotion
  ) {
    const statObserver =
      new IntersectionObserver(
        (entries, instance) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) {
              return;
            }

            animateStat(
              entry.target
            );

            instance.unobserve(
              entry.target
            );
          });
        },
        {
          threshold: 0.35,
        }
      );

    stats.forEach((stat) => {
      statObserver.observe(stat);
    });
  } else {
    stats.forEach(animateStat);
  }

  /*
  |--------------------------------------------------------------------------
  | Wedding Za member card
  |--------------------------------------------------------------------------
  */

  const memberModal =
    document.querySelector(
      '[data-wz-member-modal]'
    );

  let memberLastFocus = null;

  const openMemberCard = () => {
    if (!memberModal) {
      return;
    }

    memberLastFocus =
      document.activeElement;

    memberModal.classList.add(
      'open'
    );

    memberModal.setAttribute(
      'aria-hidden',
      'false'
    );

    body.classList.add(
      'wz-member-modal-open'
    );

    const closeButton =
      memberModal.querySelector(
        '[data-wz-card-close]'
      );

    window.setTimeout(
      () => {
        closeButton?.focus({
          preventScroll: true,
        });
      },
      70
    );
  };

  const closeMemberCard = () => {
    if (!memberModal) {
      return;
    }

    memberModal.classList.remove(
      'open'
    );

    memberModal.setAttribute(
      'aria-hidden',
      'true'
    );

    body.classList.remove(
      'wz-member-modal-open'
    );

    if (
      memberLastFocus
      && typeof memberLastFocus.focus
        === 'function'
    ) {
      memberLastFocus.focus({
        preventScroll: true,
      });
    }
  };

  document
    .querySelectorAll(
      '[data-wz-card-open]'
    )
    .forEach((button) => {
      button.addEventListener(
        'click',
        openMemberCard
      );
    });

  memberModal
    ?.querySelectorAll(
      '[data-wz-card-close]'
    )
    .forEach((button) => {
      button.addEventListener(
        'click',
        closeMemberCard
      );
    });

  memberModal?.addEventListener(
    'click',
    (event) => {
      if (event.target === memberModal) {
        closeMemberCard();
      }
    }
  );

  document
    .querySelectorAll(
      '[data-wz-member-card]'
    )
    .forEach((card) => {
      card.addEventListener(
        'pointermove',
        (event) => {
          const rect =
            card.getBoundingClientRect();

          const x = Math.max(
            0,
            Math.min(
              100,
              (
                (
                  event.clientX
                  - rect.left
                )
                / rect.width
              ) * 100
            )
          );

          const y = Math.max(
            0,
            Math.min(
              100,
              (
                (
                  event.clientY
                  - rect.top
                )
                / rect.height
              ) * 100
            )
          );

          card.style.setProperty(
            '--wz-mx',
            x + '%'
          );

          card.style.setProperty(
            '--wz-my',
            y + '%'
          );
        }
      );

      card.addEventListener(
        'pointerleave',
        () => {
          card.style.removeProperty(
            '--wz-mx'
          );

          card.style.removeProperty(
            '--wz-my'
          );
        }
      );
    });

  if (
    memberModal
    && memberModal.dataset.autoOpen
      === '1'
  ) {
    window.setTimeout(
      openMemberCard,
      prefersReducedMotion
        ? 60
        : 420
    );
  }

  /*
  |--------------------------------------------------------------------------
  | Page progress
  |--------------------------------------------------------------------------
  */

  const progress =
    document.createElement('div');

  progress.className =
    'wz-scroll-progress';

  body.appendChild(progress);

  let scrollTicking = false;

  const updateProgress = () => {
    const height =
      document.documentElement
        .scrollHeight
      - window.innerHeight;

    const ratio = height > 0
      ? Math.min(
          window.scrollY / height,
          1
        )
      : 0;

    progress.style.width =
      (ratio * 100) + '%';

    scrollTicking = false;
  };

  window.addEventListener(
    'scroll',
    () => {
      if (scrollTicking) {
        return;
      }

      scrollTicking = true;

      requestAnimationFrame(
        updateProgress
      );
    },
    {
      passive: true,
    }
  );

  updateProgress();

  document.addEventListener(
    'keydown',
    (event) => {
      if (
        (
          event.ctrlKey
          || event.metaKey
        )
        && event.key.toLowerCase()
          === 'k'
      ) {
        event.preventDefault();

        palette.classList.contains(
          'open'
        )
          ? closePalette()
          : openPalette();

        return;
      }

      if (event.key === 'Escape') {
        if (
          memberModal?.classList.contains(
            'open'
          )
        ) {
          closeMemberCard();
        } else if (
          palette.classList.contains(
            'open'
          )
        ) {
          closePalette();
        } else {
          closeNav();
        }
      }
    }
  );
})();
