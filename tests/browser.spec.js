const { test, expect } = require('@playwright/test');

const publicRoutes = [
  '/',
  '/vendors.php',
  '/event.php?type=Wedding',
  '/city.php?city=Jaipur',
  '/blog.php',
  '/planner.php',
  '/shortlist.php',
  '/invites.php',
];

for (const route of publicRoutes) {
  test(`renders ${route}`, async ({ page }) => {
    const response = await page.goto(route, {
      waitUntil: 'domcontentloaded',
    });

    expect(response).not.toBeNull();
    expect(response.status()).toBeLessThan(400);

    await expect(page.locator('body')).toBeVisible();

    const title = await page.title();

    expect(title).toContain('Wedding Za');
  });
}

test('vendor discovery filters are interactive', async ({ page }) => {
  await page.goto('/vendors.php');

  const eventFilter = page.locator('#filterEvent');

  await expect(eventFilter).toBeVisible();

  await eventFilter.selectOption({
    label: 'Wedding',
  });

  await expect(page.locator('#filterCount')).toContainText('vendor');
});

test('planner accepts event brief input', async ({ page }) => {
  await page.goto('/planner.php');

  const occasion = page.locator('[data-brief="event"]');
  const city = page.locator('[data-brief="city"]');

  await occasion.selectOption({
    label: 'Wedding',
  });

  await city.selectOption({
    label: 'Jaipur',
  });

  await expect(occasion).toHaveValue('Wedding');
  await expect(city).toHaveValue('Jaipur');
});

test('admin login is isolated from public account login', async ({ page }) => {
  await page.goto('/admin/login.php');

  await expect(
    page.getByRole('heading', {
      name: 'Wedding Za Admin',
    })
  ).toBeVisible();

  await expect(
    page.locator('meta[name="robots"]')
  ).toHaveAttribute(
    'content',
    'noindex,nofollow'
  );
});


test('homepage animation boot has no runtime errors', async ({ page }) => {
  const errors = [];

  page.on('pageerror', (error) => {
    errors.push(error.message);
  });

  await page.goto('/', {
    waitUntil: 'networkidle',
  });

  await page.waitForTimeout(1200);

  expect(errors).toEqual([]);

  const hero = page.locator('.vision-hero');

  await expect(hero).toBeVisible();

  const hasGsap = await page.evaluate(() => {
    return typeof window.gsap !== 'undefined';
  });

  expect(hasGsap).toBeTruthy();
});


test('header exposes account actions responsively', async ({ page }, testInfo) => {
  await page.goto('/', {
    waitUntil: 'domcontentloaded',
  });

  const login = page.locator('.vision-auth-login');
  const signup = page.locator('.vision-auth-signup');

  await expect(signup).toBeVisible();

  await expect(signup).toHaveAttribute(
    'href',
    'register.php?role=host'
  );

  await expect(login).toHaveAttribute(
    'href',
    'login.php?role=host'
  );

  if (testInfo.project.name === 'desktop-chromium') {
    await expect(login).toBeVisible();
  } else {
    await expect(login).toBeHidden();
  }
});


test('customer vendor and venue login routes render', async ({ page }) => {
  const roles = [
    {
      role: 'host',
      text: 'CUSTOMER CRM ACCESS',
    },
    {
      role: 'vendor',
      text: 'VENDOR CRM ACCESS',
    },
    {
      role: 'venue',
      text: 'VENUE CRM ACCESS',
    },
  ];

  for (const item of roles) {
    await page.goto(
      '/login.php?role=' + item.role,
      {
        waitUntil: 'domcontentloaded',
      }
    );

    await expect(
      page.getByText(item.text)
    ).toBeVisible();
  }
});

test('private CRM routes redirect to matching login role', async ({ page }) => {
  const routes = [
    {
      path: '/crm/customer/index.php',
      role: 'host',
    },
    {
      path: '/crm/vendor/index.php',
      role: 'vendor',
    },
    {
      path: '/crm/venue/index.php',
      role: 'venue',
    },
    {
      path: '/crm/venue/leads.php',
      role: 'venue',
    },
    {
      path: '/crm/venue/leads-new.php',
      role: 'venue',
    },
    {
      path: '/crm/venue/leads-followups.php',
      role: 'venue',
    },
    {
      path: '/crm/venue/leads-site-visits.php',
      role: 'venue',
    },
    {
      path: '/crm/venue/leads-lost.php',
      role: 'venue',
    },
    {
      path: '/crm/venue/functions.php',
      role: 'venue',
    },
    {
      path: '/crm/venue/functions-upcoming.php',
      role: 'venue',
    },
    {
      path: '/crm/venue/functions-calendar.php',
      role: 'venue',
    },
    {
      path: '/crm/venue/bookings.php',
      role: 'venue',
    },
    {
      path: '/crm/venue/payments.php',
      role: 'venue',
    },
    {
      path: '/crm/venue/reports.php',
      role: 'venue',
    },
  ];

  for (const item of routes) {
    await page.goto(
      item.path,
      {
        waitUntil: 'domcontentloaded',
      }
    );

    expect(page.url()).toContain(
      'login.php?role=' + item.role
    );
  }
});

test('venue registration route is available', async ({ page }) => {
  await page.goto(
    '/register.php?role=venue',
    {
      waitUntil: 'domcontentloaded',
    }
  );

  await expect(
    page.getByText('VENUE CRM ACCOUNT')
  ).toBeVisible();
});


test('Admin CRM operation routes require Admin login', async ({ page }) => {
  const routes = [
    '/admin/index.php',
    '/admin/leads.php',
    '/admin/leads-new.php',
    '/admin/leads-followups.php',
    '/admin/leads-site-visits.php',
    '/admin/leads-lost.php',
    '/admin/functions.php',
    '/admin/functions-upcoming.php',
    '/admin/functions-calendar.php',
    '/admin/bookings.php',
    '/admin/payments.php',
    '/admin/invoices.php',
    '/admin/refunds.php',
    '/admin/commission.php',
    '/admin/customers.php',
    '/admin/venues.php',
    '/admin/venues-active.php',
    '/admin/venues-inactive.php',
    '/admin/vendors.php',
    '/admin/vendors-active.php',
    '/admin/reports.php',
    '/admin/website-cities.php',
    '/admin/website-categories.php',
    '/admin/website-venues.php',
    '/admin/website-blogs.php',
    '/admin/team.php',
  ];

  for (const route of routes) {
    await page.goto(
      route,
      {
        waitUntil: 'domcontentloaded',
      }
    );

    expect(page.url()).toContain(
      '/admin/login.php'
    );
  }
});
