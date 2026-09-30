(() => {
  'use strict';

  const compareKey = 'wz_venue_compare';

  const getCompare = () => {
    try {
      const data = JSON.parse(
        localStorage.getItem(compareKey) || '[]'
      );

      return Array.isArray(data) ? data : [];
    } catch {
      return [];
    }
  };

  const setCompare = (items) => {
    localStorage.setItem(
      compareKey,
      JSON.stringify(items.slice(0, 4))
    );

    renderCompare();
  };

  const ensureDock = () => {
    let dock = document.querySelector(
      '.marketplace-compare-dock'
    );

    if (dock) {
      return dock;
    }

    dock = document.createElement('div');
    dock.className = 'marketplace-compare-dock';
    dock.innerHTML = [
      '<div class="marketplace-compare-dock-head">',
      '<strong>Compare venues</strong>',
      '<small data-compare-count>0 / 4</small>',
      '</div>',
      '<div class="marketplace-compare-dock-list" data-compare-list></div>',
      '<div class="marketplace-compare-dock-actions">',
      '<button class="pill-btn outline" type="button" data-compare-clear>Clear</button>',
      '<a class="pill-btn wine" href="#" data-compare-open>Compare ↗</a>',
      '</div>',
    ].join('');

    document.body.appendChild(dock);

    dock
      .querySelector('[data-compare-clear]')
      ?.addEventListener('click', () => {
        setCompare([]);
      });

    return dock;
  };

  const renderCompare = () => {
    const dock = ensureDock();
    const items = getCompare();
    const list = dock.querySelector(
      '[data-compare-list]'
    );
    const count = dock.querySelector(
      '[data-compare-count]'
    );
    const open = dock.querySelector(
      '[data-compare-open]'
    );

    dock.classList.toggle(
      'active',
      items.length > 0
    );

    if (count) {
      count.textContent =
        items.length + ' / 4';
    }

    if (list) {
      list.innerHTML = '';

      items.forEach((item) => {
        const chip = document.createElement('span');
        chip.textContent = item.name || item.id;
        list.appendChild(chip);
      });
    }

    if (open) {
      open.href =
        'compare.php?ids=' +
        encodeURIComponent(
          items.map((item) => item.id).join(',')
        );
    }

    document
      .querySelectorAll('[data-compare-venue]')
      .forEach((button) => {
        const selected = items.some(
          (item) =>
            item.id === button.dataset.compareVenue
        );

        button.classList.toggle(
          'active',
          selected
        );

        if (
          button.matches('.pill-btn')
        ) {
          button.textContent = selected
            ? '✓ Added to compare'
            : '+ Compare venue';
        }
      });
  };

  document.addEventListener(
    'click',
    (event) => {
      const button = event.target.closest(
        '[data-compare-venue]'
      );

      if (!button) {
        return;
      }

      event.preventDefault();

      const id = button.dataset.compareVenue;
      const name =
        button.dataset.compareName || id;
      const items = getCompare();
      const exists = items.some(
        (item) => item.id === id
      );

      if (exists) {
        setCompare(
          items.filter((item) => item.id !== id)
        );
        return;
      }

      if (items.length >= 4) {
        const toast =
          document.querySelector('#toast');

        if (toast) {
          toast.textContent =
            'Compare up to 4 venues at a time.';
          toast.classList.add('show');

          setTimeout(
            () => toast.classList.remove('show'),
            2200
          );
        }

        return;
      }

      setCompare([
        ...items,
        {
          id,
          name,
        },
      ]);
    }
  );

  renderCompare();
})();
