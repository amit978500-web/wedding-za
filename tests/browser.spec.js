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


test('header exposes login and signup actions', async ({ page }) => {
  await page.goto('/', {
    waitUntil: 'domcontentloaded',
  });

  await expect(
    page.locator('.vision-auth-login')
  ).toBeVisible();

  await expect(
    page.locator('.vision-auth-signup')
  ).toBeVisible();

  await expect(
    page.locator('.vision-auth-login')
  ).toHaveAttribute(
    'href',
    'login.php?role=host'
  );

  await expect(
    page.locator('.vision-auth-signup')
  ).toHaveAttribute(
    'href',
    'register.php?role=host'
  );
});
