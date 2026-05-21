const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const BASE = process.env.SMARTRECRUIT_BASE_URL || 'http://localhost/smartrecruit/public/index.php?url=';
const OUT_DIR = path.resolve(__dirname, '..', 'docs');
const VIDEO_NAME = 'smartrecruit-demo-fullscreen.webm';
const VIEWPORT = { width: 1920, height: 1080 };

async function main() {
  fs.mkdirSync(OUT_DIR, { recursive: true });

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: VIEWPORT,
    deviceScaleFactor: 1,
    locale: 'fr-FR',
    recordVideo: {
      dir: OUT_DIR,
      size: VIEWPORT,
    },
  });

  const page = await context.newPage();
  const startedAt = Date.now();

  const url = route => `${BASE}${route}`;
  const elapsed = () => Date.now() - startedAt;
  const holdUntil = async ms => {
    const remaining = ms - elapsed();
    if (remaining > 0) await page.waitForTimeout(remaining);
  };
  const pause = ms => page.waitForTimeout(ms);

  async function installCursor() {
    await page.evaluate(() => {
      if (document.getElementById('demo-cursor')) return;
      const cursor = document.createElement('div');
      cursor.id = 'demo-cursor';
      cursor.style.cssText = [
        'position:fixed',
        'left:0',
        'top:0',
        'width:22px',
        'height:22px',
        'border:3px solid #9400D3',
        'border-radius:50%',
        'background:rgba(148,0,211,.10)',
        'box-shadow:0 8px 24px rgba(148,0,211,.28)',
        'pointer-events:none',
        'z-index:2147483647',
        'transform:translate(60px,60px)',
        'transition:transform .35s ease',
      ].join(';');
      document.documentElement.appendChild(cursor);
    }).catch(() => {});
  }

  async function moveTo(locator) {
    await installCursor();
    await locator.scrollIntoViewIfNeeded().catch(() => {});
    const box = await locator.boundingBox();
    if (!box) return;
    const x = Math.round(box.x + Math.min(box.width - 8, Math.max(8, box.width / 2)));
    const y = Math.round(box.y + Math.min(box.height - 8, Math.max(8, box.height / 2)));
    await page.evaluate(({ x, y }) => {
      const cursor = document.getElementById('demo-cursor');
      if (cursor) cursor.style.transform = `translate(${x}px, ${y}px)`;
    }, { x, y }).catch(() => {});
    await pause(450);
  }

  async function goto(route) {
    await page.goto(url(route), { waitUntil: 'networkidle' });
    await installCursor();
    await pause(700);
  }

  async function click(selector) {
    const locator = page.locator(selector).first();
    await moveTo(locator);
    await locator.click();
    await page.waitForLoadState('networkidle').catch(() => {});
    await installCursor();
    await pause(700);
  }

  async function fill(selector, value) {
    const locator = page.locator(selector).first();
    await moveTo(locator);
    await locator.fill(value);
    await pause(350);
  }

  async function select(selector, value) {
    const locator = page.locator(selector).first();
    await moveTo(locator);
    await locator.selectOption(value);
    await page.waitForLoadState('networkidle').catch(() => {});
    await pause(500);
  }

  async function scrollTo(y) {
    await page.evaluate(targetY => window.scrollTo({ top: targetY, behavior: 'smooth' }), y);
    await pause(1500);
  }

  async function login(email) {
    await goto('auth/login');
    await fill('input[name="email"]', email);
    await fill('input[name="mot_de_passe"]', 'password');
    await click('button[type="submit"]');
  }

  async function logout() {
    await goto('auth/logout');
  }

  // 0:00 - 0:15 Intro
  await goto('auth/login');
  await pause(2500);
  await moveTo(page.locator('.brand').first());
  await holdUntil(15_000);
  console.log('section=intro done');

  // 0:15 - 0:30 Login
  await fill('input[name="email"]', 'recruteur@test.com');
  await fill('input[name="mot_de_passe"]', 'password');
  await click('button[type="submit"]');
  await holdUntil(30_000);
  console.log('section=login done');

  // 0:30 - 1:00 Recruiter dashboard
  await scrollTo(360);
  await scrollTo(900);
  await scrollTo(1450);
  await holdUntil(60_000);
  console.log('section=dashboard done');

  // 1:00 - 1:25 Offers management
  await click('a[href*="url=offres"]');
  await fill('input[name="search"]', 'Developpeur');
  await click('.filter-bar button[type="submit"]');
  await click('a[href*="url=offres/create"]');
  await fill('input[name="titre"]', 'Chef de projet RH digital');
  await fill('input[name="localisation"]', 'Casablanca, Maroc');
  await select('select[name="statut"]', 'Publiee');
  await scrollTo(420);
  await holdUntil(85_000);
  console.log('section=offers done');

  // 1:25 - 1:50 Candidate space
  await logout();
  await login('sara@test.com');
  await click('a[href*="url=offres"]');
  await fill('input[name="search"]', 'Node');
  await click('.filter-bar button[type="submit"]');
  await goto('offres/detail/3');
  await goto('candidatures/postuler/3');
  const sampleCv = path.resolve(__dirname, '..', 'public', 'uploads', 'cv_2_1778860458.pdf');
  if (fs.existsSync(sampleCv)) {
    await page.setInputFiles('input[name="cv"]', sampleCv).catch(() => {});
  }
  await scrollTo(560);
  await holdUntil(110_000);
  console.log('section=candidate done');

  // 1:50 - 2:20 Applications management
  await logout();
  await login('recruteur@test.com');
  await goto('candidatures&statut=Entretien');
  await pause(1500);
  await fill('input[name="search"]', 'Sara');
  await pause(1500);
  await goto('candidatures/detail/2');
  await scrollTo(460);
  await holdUntil(140_000);
  console.log('section=applications done');

  // 2:20 - 2:45 AI features
  await scrollTo(700);
  await click('#ai-summary-btn');
  await page.locator('#ai-summary-result:not(.hidden)').waitFor({ timeout: 15_000 }).catch(() => {});
  await pause(3500);
  await holdUntil(165_000);
  console.log('section=ai done');

  // 2:45 - 3:05 Interviews
  await goto('entretiens');
  await click('button:has-text("Décider"), button:has-text("Decider")');
  await pause(1800);
  await click('#modal-decision button:has-text("Annuler")');
  await goto('candidatures/detail/1');
  await click('a[href*="url=entretiens/create"]');
  await fill('input[name="date_entretien"]', '2026-05-27T10:30');
  await select('select[name="type_entretien"]', 'Visio');
  await fill('input[name="lieu_ou_lien"]', 'https://meet.google.com/demo-smartrecruit');
  await holdUntil(185_000);
  console.log('section=interviews done');

  // 3:05 - 3:20 Notifications and communication
  await goto('dashboard');
  await click('#notif-btn');
  await pause(4500);
  await holdUntil(200_000);
  console.log('section=notifications done');

  // 3:20 - 3:40 Conclusion
  await goto('dashboard');
  await scrollTo(380);
  await scrollTo(0);
  await holdUntil(220_000);

  const video = page.video();
  await context.close();
  await browser.close();

  if (video) {
    const rawPath = await video.path();
    const finalPath = path.join(OUT_DIR, VIDEO_NAME);
    fs.copyFileSync(rawPath, finalPath);
    console.log(`SMARTRECRUIT_DEMO_VIDEO=${finalPath}`);
  }
}

main().catch(error => {
  console.error(error);
  process.exit(1);
});
