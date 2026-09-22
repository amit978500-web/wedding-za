(() => {
  'use strict';

  const qs = (selector, context = document) => {
    return context.querySelector(selector);
  };

  const qsa = (selector, context = document) => {
    return [...context.querySelectorAll(selector)];
  };

  const storage = {
    get(key, fallback = []) {
      try {
        const raw = localStorage.getItem(key);

        if (raw === null) {
          return fallback;
        }

        return JSON.parse(raw);
      } catch {
        return fallback;
      }
    },

    set(key, value) {
      localStorage.setItem(
        key,
        JSON.stringify(value)
      );

      window.dispatchEvent(
        new CustomEvent('wz:storage', {
          detail: {
            key,
            value,
          },
        })
      );
    },
  };

  function toast(message) {
    const element = qs('#toast');

    if (!element) {
      return;
    }

    element.textContent = message;
    element.classList.add('show');

    clearTimeout(window.__wzToast);

    window.__wzToast = setTimeout(() => {
      element.classList.remove('show');
    }, 2600);
  }

  window.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('is-loading');

    setTimeout(() => {
      document.body.classList.remove('is-loading');
      qs('#preloader')?.classList.add('hide');
    }, 550);

    initHeader();
    initReveal();
    initParallax();
    initTransitions();
    initShortlist();
    initIdeaSaves();
    initForms();
    initVendorFilters();
    initEventBrief();
    initPlanner();
    initBudget();
    initInviteBuilder();
    initShare();
    initWorkspaceSync();
  });

  function initHeader() {
    const header = qs('#siteHeader');
    const progress = qs('#pageProgress');
    const button = qs('#menuToggle');
    const menu = qs('#mobileMenu');

    const onScroll = () => {
      const y = window.scrollY;
      const max = document.documentElement.scrollHeight - window.innerHeight;

      header?.classList.toggle(
        'scrolled',
        y > 12
      );

      if (progress) {
        const percentage = max > 0
          ? (y / max) * 100
          : 0;

        progress.style.width = percentage + '%';
      }
    };

    const setMenu = (open) => {
      document.body.classList.toggle(
        'menu-open',
        open
      );

      button?.classList.toggle(
        'active',
        open
      );

      button?.setAttribute(
        'aria-expanded',
        String(open)
      );

      menu?.classList.toggle(
        'open',
        open
      );

      menu?.setAttribute(
        'aria-hidden',
        String(!open)
      );
    };

    window.addEventListener(
      'scroll',
      onScroll,
      {
        passive: true,
      }
    );

    button?.addEventListener('click', () => {
      setMenu(
        !menu?.classList.contains('open')
      );
    });

    menu?.addEventListener('click', (event) => {
      if (event.target.closest('a')) {
        setMenu(false);
      }
    });

    window.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setMenu(false);
      }
    });

    onScroll();
  }

  function initReveal() {
    const elements = qsa('.reveal');

    if (!elements.length) {
      return;
    }

    if (!('IntersectionObserver' in window)) {
      elements.forEach((element) => {
        element.classList.add('in-view');
      });

      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) {
            return;
          }

          entry.target.classList.add('in-view');
          observer.unobserve(entry.target);
        });
      },
      {
        threshold: 0.12,
        rootMargin: '0px 0px -30px',
      }
    );

    elements.forEach((element) => {
      observer.observe(element);
    });
  }

  function initParallax() {
    if (
      window.matchMedia(
        '(prefers-reduced-motion: reduce)'
      ).matches
    ) {
      return;
    }

    const elements = qsa('[data-parallax]');

    if (!elements.length) {
      return;
    }

    let ticking = false;

    const paint = () => {
      const viewportHeight = window.innerHeight;

      elements.forEach((element) => {
        const rect = element.getBoundingClientRect();

        const speed = parseFloat(
          element.dataset.parallax || '.08'
        );

        const midpoint =
          rect.top +
          rect.height / 2 -
          viewportHeight / 2;

        element.style.transform =
          'translate3d(0,' +
          midpoint * -speed +
          'px,0)';
      });

      ticking = false;
    };

    window.addEventListener(
      'scroll',
      () => {
        if (ticking) {
          return;
        }

        window.requestAnimationFrame(paint);
        ticking = true;
      },
      {
        passive: true,
      }
    );

    paint();
  }

  function initTransitions() {
    const wipe = qs('#pageWipe');

    if (!wipe) {
      return;
    }

    qsa('a[href]').forEach((anchor) => {
      anchor.addEventListener('click', (event) => {
        const href = anchor.getAttribute('href') || '';

        if (
          href.startsWith('#') ||
          href.startsWith('mailto:') ||
          href.startsWith('tel:') ||
          anchor.target === '_blank' ||
          event.ctrlKey ||
          event.metaKey ||
          event.shiftKey
        ) {
          return;
        }

        let target;

        try {
          target = new URL(
            anchor.href,
            window.location.href
          );
        } catch {
          return;
        }

        if (target.origin !== window.location.origin) {
          return;
        }

        event.preventDefault();

        wipe.classList.add('active');

        setTimeout(() => {
          window.location.href = anchor.href;
        }, 470);
      });
    });
  }

  function shortlistIds() {
    return storage.get(
      'wz_shortlist',
      []
    );
  }

  function initShortlist() {
    const sync = () => {
      const ids = shortlistIds();
      const count = qs('#shortlistCount');

      if (count) {
        count.textContent = String(ids.length);
      }

      qsa('[data-shortlist]').forEach((button) => {
        const id = button.dataset.shortlist;
        const selected = ids.includes(id);

        button.classList.toggle(
          'active',
          selected
        );

        if (button.classList.contains('heart-btn')) {
          button.textContent = selected
            ? '♥'
            : '♡';
        } else {
          button.textContent = selected
            ? '♥ Saved to shortlist'
            : '♡ Save to shortlist';
        }

        button.setAttribute(
          'aria-pressed',
          String(selected)
        );
      });

      renderShortlist(ids);
    };

    document.addEventListener('click', (event) => {
      const button = event.target.closest(
        '[data-shortlist]'
      );

      if (!button) {
        return;
      }

      event.preventDefault();

      const id = button.dataset.shortlist;
      const ids = shortlistIds();

      const next = ids.includes(id)
        ? ids.filter((item) => item !== id)
        : [...ids, id];

      storage.set(
        'wz_shortlist',
        next
      );

      toast(
        next.includes(id)
          ? 'Saved to your shortlist ♡'
          : 'Removed from shortlist'
      );

      sync();
    });

    sync();
  }

  function renderShortlist(ids) {
    const root = qs('#shortlistGrid');
    const heroCount = qs('#shortlistHeroCount');
    const plannerCount = qs('#plannerShortlistCount');
    const heroText = qs('#shortlistHeroText');
    const emptyState = qs('#shortlistEmpty');
    const action = qs('#shortlistAction');

    if (heroCount) {
      heroCount.textContent = String(ids.length);
    }

    if (plannerCount) {
      plannerCount.textContent = String(ids.length);
    }

    if (heroText) {
      heroText.textContent = ids.length
        ? ids.length +
          ' saved profile' +
          (ids.length === 1 ? '' : 's') +
          ' ready to compare.'
        : 'Your shortlist is empty.';
    }

    if (root) {
      qsa(
        '.vendor-card[data-vendor-id]',
        root
      ).forEach((card) => {
        card.classList.toggle(
          'hidden',
          !ids.includes(card.dataset.vendorId)
        );
      });
    }

    if (emptyState) {
      emptyState.hidden = ids.length > 0;
    }

    action?.classList.toggle(
      'is-empty',
      ids.length === 0
    );
  }

  function initEventBrief() {
    const form = qs('#eventBriefForm');
    const key = 'wz_event_brief';
    const saved = storage.get(key, {});

    const paintSummary = (data) => {
      const title = qs('#shortlistBriefTitle');
      const meta = qs('#shortlistBriefMeta');
      const status = qs('#briefStatus strong');

      if (title) {
        const parts = [
          data.event,
          data.city,
        ].filter(Boolean);

        title.textContent = parts.length
          ? parts.join(' · ')
          : 'No event brief yet';
      }

      if (meta) {
        const bits = [];

        if (data.date) {
          bits.push(
            new Date(
              data.date + 'T12:00:00'
            ).toLocaleDateString(
              'en-IN',
              {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
              }
            )
          );
        }

        if (data.guests) {
          bits.push(
            data.guests + ' guests'
          );
        }

        if (data.budget) {
          bits.push(data.budget);
        }

        if (data.direction) {
          bits.push(data.direction);
        }

        meta.textContent = bits.length
          ? bits.join(' · ')
          : 'Add occasion, city, date and guest count so your shortlist has context.';
      }

      if (status) {
        status.textContent = Object.values(data).some(Boolean)
          ? 'Brief saved automatically.'
          : 'Your brief saves automatically.';
      }
    };

    if (!form) {
      paintSummary(saved || {});
      return;
    }

    const fields = qsa(
      '[data-brief]',
      form
    );

    fields.forEach((field) => {
      const value = saved[field.dataset.brief];

      if (value !== undefined && value !== null) {
        field.value = value;
      }
    });

    const sync = () => {
      const data = {};

      fields.forEach((field) => {
        data[field.dataset.brief] =
          field.value.trim();
      });

      storage.set(
        key,
        data
      );

      paintSummary(data);
    };

    fields.forEach((field) => {
      field.addEventListener(
        'input',
        sync
      );

      field.addEventListener(
        'change',
        sync
      );
    });

    sync();
  }

  function initIdeaSaves() {
    const key = 'wz_ideas';

    const sync = () => {
      const ids = storage.get(
        key,
        []
      );

      qsa('[data-save-idea]').forEach((button) => {
        const selected = ids.includes(
          button.dataset.saveIdea
        );

        button.classList.toggle(
          'saved',
          selected
        );

        button.textContent = selected
          ? '♥'
          : '♡';
      });
    };

    document.addEventListener('click', (event) => {
      const button = event.target.closest(
        '[data-save-idea]'
      );

      if (!button) {
        return;
      }

      event.preventDefault();

      const id = button.dataset.saveIdea;

      const ids = storage.get(
        key,
        []
      );

      const next = ids.includes(id)
        ? ids.filter((item) => item !== id)
        : [...ids, id];

      storage.set(
        key,
        next
      );

      toast(
        next.includes(id)
          ? 'Idea saved to your board'
          : 'Idea removed'
      );

      sync();
    });

    sync();
  }

  function initForms() {
    qsa('form[data-async]').forEach((form) => {
      form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const button = form.querySelector(
          '[type=submit]'
        );

        const successBox = form.querySelector(
          '.success-box'
        );

        const label = button?.textContent;

        if (button) {
          button.disabled = true;
          button.textContent = 'Sending…';
        }

        try {
          const response = await fetch(
            form.action || 'api/lead.php',
            {
              method: 'POST',
              body: new FormData(form),
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
              },
            }
          );

          const output = await response.json();

          if (!response.ok || !output.ok) {
            throw new Error(
              output.message ||
              'Could not submit'
            );
          }

          form.reset();

          if (successBox) {
            successBox.textContent =
              output.message ||
              'Thanks — we received it.';

            successBox.classList.add('show');
          }

          toast(
            output.message ||
            'Thanks — we received it.'
          );
        } catch (error) {
          toast(
            error.message ||
            'Something went wrong. Please try again.'
          );
        } finally {
          if (button) {
            button.disabled = false;
            button.textContent = label;
          }
        }
      });
    });
  }

  function initVendorFilters() {
    const grid = qs('#vendorListing');

    if (!grid) {
      return;
    }

    const cards = qsa(
      '.vendor-card',
      grid
    );

    const search = qs('#filterSearch');
    const event = qs('#filterEvent');
    const city = qs('#filterCity');
    const category = qs('#filterCategory');
    const sort = qs('#filterSort');
    const count = qs('#filterCount');
    const reset = qs('#filterReset');
    const activeBar = qs('#activeFilterBar');

    const controls = {
      event,
      city,
      category,
    };

    const syncUrl = () => {
      const url = new URL(
        window.location.href
      );

      Object.entries(controls).forEach(
        ([key, control]) => {
          if (control?.value) {
            url.searchParams.set(
              key,
              control.value
            );
          } else {
            url.searchParams.delete(key);
          }
        }
      );

      window.history.replaceState(
        {},
        '',
        url
      );
    };

    const renderChips = () => {
      if (!activeBar) {
        return;
      }

      const chips = [];

      Object.entries(controls).forEach(
        ([key, control]) => {
          if (!control?.value) {
            return;
          }

          chips.push(
            '<button type="button" data-clear-filter="' +
            key +
            '">' +
            control.value +
            ' ×</button>'
          );
        }
      );

      activeBar.innerHTML = chips.join('');

      activeBar.classList.toggle(
        'has-filters',
        chips.length > 0
      );
    };

    const run = () => {
      const term = (
        search?.value || ''
      ).toLowerCase().trim();

      const eventValue = event?.value || '';
      const cityValue = city?.value || '';
      const categoryValue = category?.value || '';

      const shown = cards.filter((card) => {
        const events = (
          card.dataset.events || ''
        ).split('|');

        const matches =
          (!term ||
            card.dataset.search
              .toLowerCase()
              .includes(term)) &&
          (!eventValue ||
            events.includes(eventValue)) &&
          (!cityValue ||
            card.dataset.city === cityValue) &&
          (!categoryValue ||
            card.dataset.category === categoryValue);

        card.classList.toggle(
          'hidden',
          !matches
        );

        return matches;
      });

      if (sort) {
        const mode = sort.value;

        shown
          .sort((left, right) => {
            if (mode === 'rating') {
              return (
                Number(right.dataset.rating) -
                Number(left.dataset.rating)
              );
            }

            if (mode === 'price') {
              return (
                Number(left.dataset.price) -
                Number(right.dataset.price)
              );
            }

            return 0;
          })
          .forEach((card) => {
            grid.appendChild(card);
          });
      }

      if (count) {
        count.textContent =
          shown.length +
          ' vendor' +
          (shown.length === 1 ? '' : 's') +
          ' found';
      }

      const empty = qs('#vendorEmpty');

      if (empty) {
        empty.hidden = shown.length > 0;
      }

      renderChips();
      syncUrl();
    };

    [
      search,
      event,
      city,
      category,
      sort,
    ].forEach((control) => {
      if (!control) {
        return;
      }

      const eventName =
        control === search
          ? 'input'
          : 'change';

      control.addEventListener(
        eventName,
        run
      );
    });

    reset?.addEventListener('click', () => {
      if (search) {
        search.value = '';
      }

      [
        event,
        city,
        category,
      ].forEach((control) => {
        if (control) {
          control.value = '';
        }
      });

      if (sort) {
        sort.value = 'featured';
      }

      run();
    });

    activeBar?.addEventListener('click', (eventObject) => {
      const button = eventObject.target.closest(
        '[data-clear-filter]'
      );

      if (!button) {
        return;
      }

      const control = controls[
        button.dataset.clearFilter
      ];

      if (control) {
        control.value = '';
      }

      run();
    });

    run();
  }

  function initPlanner() {
    const root = qs('#plannerChecklist');

    if (!root) {
      return;
    }

    const boxes = qsa(
      'input[type=checkbox][data-task]',
      root
    );

    const key = 'wz_tasks';

    const saved = storage.get(
      key,
      []
    );

    boxes.forEach((box) => {
      box.checked = saved.includes(
        box.dataset.task
      );
    });

    const sync = () => {
      const completed = boxes
        .filter((box) => box.checked)
        .map((box) => box.dataset.task);

      storage.set(
        key,
        completed
      );

      const percentage = boxes.length
        ? Math.round(
            completed.length /
            boxes.length *
            100
          )
        : 0;

      const ring = qs('#progressRing');

      if (ring) {
        ring.style.setProperty(
          '--p',
          percentage
        );
      }

      const percentageText = qs('#progressPct');
      const doneText = qs('#progressDone');

      if (percentageText) {
        percentageText.textContent =
          percentage + '%';
      }

      if (doneText) {
        doneText.textContent =
          completed.length +
          '/' +
          boxes.length;
      }
    };

    boxes.forEach((box) => {
      box.addEventListener(
        'change',
        sync
      );
    });

    sync();
  }

  function initBudget() {
    const table = qs('#budgetTable');

    if (!table) {
      return;
    }

    const key = 'wz_budget';

    const inputs = qsa(
      'input[data-budget]',
      table
    );

    const saved = storage.get(
      key,
      {}
    );

    inputs.forEach((input) => {
      const value = saved[
        input.dataset.budget
      ];

      if (value !== undefined && value !== null) {
        input.value = value;
      }
    });

    const money = (value) => {
      return new Intl.NumberFormat(
        'en-IN',
        {
          style: 'currency',
          currency: 'INR',
          maximumFractionDigits: 0,
        }
      ).format(value || 0);
    };

    const sync = () => {
      const data = {};

      inputs.forEach((input) => {
        data[input.dataset.budget] =
          Number(input.value || 0);
      });

      storage.set(
        key,
        data
      );

      let planned = 0;
      let spent = 0;

      Object.entries(data).forEach(
        ([budgetKey, value]) => {
          if (budgetKey.endsWith(':planned')) {
            planned += value;
          } else {
            spent += value;
          }
        }
      );

      const plannedText = qs('#budgetPlanned');
      const spentText = qs('#budgetSpent');
      const remainingText = qs('#budgetLeft');

      if (plannedText) {
        plannedText.textContent = money(planned);
      }

      if (spentText) {
        spentText.textContent = money(spent);
      }

      if (remainingText) {
        remainingText.textContent = money(
          planned - spent
        );
      }

      qsa(
        '.budget-row',
        table
      ).slice(1).forEach((row) => {
        const rowInputs = qsa(
          'input',
          row
        );

        const status = row.querySelector(
          '[data-budget-status]'
        );

        if (
          !status ||
          rowInputs.length < 2
        ) {
          return;
        }

        const rowPlanned = Number(
          rowInputs[0].value || 0
        );

        const rowSpent = Number(
          rowInputs[1].value || 0
        );

        if (!rowPlanned && !rowSpent) {
          status.textContent = 'Track';
        } else if (
          rowSpent > rowPlanned &&
          rowPlanned > 0
        ) {
          status.textContent = 'Over plan';
        } else if (
          rowSpent === rowPlanned &&
          rowPlanned > 0
        ) {
          status.textContent = 'Fully spent';
        } else if (rowSpent > 0) {
          status.textContent = 'In progress';
        } else {
          status.textContent = 'Planned';
        }

        status.classList.toggle(
          'over',
          rowSpent > rowPlanned &&
          rowPlanned > 0
        );
      });
    };

    inputs.forEach((input) => {
      input.addEventListener(
        'input',
        sync
      );
    });

    sync();
  }

  function initInviteBuilder() {
    const live = qs('#liveInvite');

    if (!live) {
      return;
    }

    const names = qs('#inviteNames');
    const date = qs('#inviteDate');
    const venue = qs('#inviteVenue');
    const theme = qs('#inviteTheme');

    const themes = {
      gulab: [
        '#f4dedf',
        '#7b2942',
      ],
      mehr: [
        '#f0ddc2',
        '#7c3f26',
      ],
      ivory: [
        '#f5f1e9',
        '#34302d',
      ],
      noor: [
        '#e8e3ec',
        '#4b315f',
      ],
      bagh: [
        '#e7efe2',
        '#36523b',
      ],
      saanjh: [
        '#2e2432',
        '#e4bd82',
      ],
    };

    const sync = () => {
      const selectedTheme =
        themes[theme?.value] ||
        themes.gulab;

      live.style.background =
        selectedTheme[0];

      live.style.color =
        selectedTheme[1];

      live.querySelector(
        '[data-live-names]'
      ).textContent =
        names?.value ||
        'Your Celebration';

      live.querySelector(
        '[data-live-date]'
      ).textContent = date?.value
        ? new Date(
            date.value + 'T12:00:00'
          ).toLocaleDateString(
            'en-IN',
            {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
            }
          )
        : '12 December 2026';

      live.querySelector(
        '[data-live-venue]'
      ).textContent =
        venue?.value ||
        'Jaipur, Rajasthan';
    };

    [
      names,
      date,
      venue,
      theme,
    ].forEach((control) => {
      control?.addEventListener(
        'input',
        sync
      );
    });

    qs('#downloadInvite')?.addEventListener(
      'click',
      () => {
        toast(
          'Preview ready. PDF export can be connected in production.'
        );
      }
    );

    sync();
  }

  function initShare() {
    qsa('[data-share]').forEach((button) => {
      button.addEventListener(
        'click',
        async () => {
          try {
            await navigator.clipboard.writeText(
              window.location.href
            );

            toast('Link copied');
          } catch {
            toast(
              'Copy the page URL to share'
            );
          }
        }
      );
    });
  }

  function initWorkspaceSync() {
    const boot = window.WZ_BOOT || {};

    if (
      !boot.loggedIn ||
      boot.role !== 'host' ||
      !boot.databaseReady
    ) {
      return;
    }

    let timer = null;
    let hydrating = false;

    const keys = [
      'wz_event_brief',
      'wz_tasks',
      'wz_budget',
      'wz_shortlist',
    ];

    const snapshot = () => {
      return {
        brief: storage.get(
          'wz_event_brief',
          {}
        ),
        tasks: storage.get(
          'wz_tasks',
          []
        ),
        budget: storage.get(
          'wz_budget',
          {}
        ),
        shortlist: storage.get(
          'wz_shortlist',
          []
        ),
      };
    };

    const hasLocalData = () => {
      const data = snapshot();

      return (
        Object.keys(data.brief).length > 0 ||
        data.tasks.length > 0 ||
        Object.keys(data.budget).length > 0 ||
        data.shortlist.length > 0
      );
    };

    const save = async () => {
      if (hydrating) {
        return;
      }

      try {
        await fetch(
          'api/workspace.php',
          {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
              csrf: boot.csrf,
              ...snapshot(),
            }),
          }
        );
      } catch {
        // Local browser storage remains the fallback.
      }
    };

    const queueSave = () => {
      clearTimeout(timer);

      timer = setTimeout(
        save,
        500
      );
    };

    window.addEventListener(
      'wz:storage',
      (event) => {
        if (
          keys.includes(
            event.detail?.key
          )
        ) {
          queueSave();
        }
      }
    );

    fetch('api/workspace.php', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then((response) => response.json())
      .then((output) => {
        if (
          !output.ok ||
          !output.configured
        ) {
          return;
        }

        const workspace =
          output.workspace || {};

        const serverHasData =
          Object.keys(
            workspace.brief || {}
          ).length > 0 ||
          (workspace.tasks || []).length > 0 ||
          Object.keys(
            workspace.budget || {}
          ).length > 0 ||
          (workspace.shortlist || []).length > 0;

        if (
          serverHasData &&
          !hasLocalData()
        ) {
          hydrating = true;

          localStorage.setItem(
            'wz_event_brief',
            JSON.stringify(
              workspace.brief || {}
            )
          );

          localStorage.setItem(
            'wz_tasks',
            JSON.stringify(
              workspace.tasks || []
            )
          );

          localStorage.setItem(
            'wz_budget',
            JSON.stringify(
              workspace.budget || {}
            )
          );

          localStorage.setItem(
            'wz_shortlist',
            JSON.stringify(
              workspace.shortlist || []
            )
          );

          window.location.reload();
          return;
        }

        if (
          !serverHasData &&
          hasLocalData()
        ) {
          queueSave();
        }
      })
      .catch(() => {
        // Local browser storage remains the fallback.
      });
  }
})();
