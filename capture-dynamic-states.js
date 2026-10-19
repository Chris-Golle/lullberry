const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const CHROMIUM_PATH = "/Applications/Chromium.app/Contents/MacOS/Chromium";
const BASE_URL = "http://lullberry.local/";
const SAMPLE_PRODUCT_URL = "http://lullberry.local/shop/an-eco-bag-for-calm/"; // Adjust to a product page that uses these features
const CATEGORY_URL = "http://lullberry.local/cat/look-at-you/calm/"; // Captures term-description-structured
const SHOP_URL = "http://lullberry.local/shop/";
const OUTPUT_DIR = "/tmp/lb-crawl/";

async function capture() {
  console.log('Capturing dynamic states for PurgeCSS...');
  const browser = await puppeteer.launch({ executablePath: CHROMIUM_PATH, headless: true });
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 900 });
  
  // 1. Capture Region Switcher open state (on homepage)
  console.log(`Visiting ${BASE_URL} to capture region switcher state...`);
  await page.goto(BASE_URL, { waitUntil: 'networkidle2' });

  // 1. Trigger the Region Switcher click
  const regionLink = await page.$('#region');
  if (regionLink) {
    await regionLink.click();
    await new Promise(r => setTimeout(r, 600)); // Wait for JS/CSS transitions
    const html = await page.content();
    fs.writeFileSync(path.join(OUTPUT_DIR, 'dynamic-region-open.html'), html);
    console.log('✅ Captured region switcher open state.');
    await regionLink.click(); // Close it to reset state for next actions
    await new Promise(r => setTimeout(r, 300));
  } else {
    console.warn('⚠️ Region switcher (#region) not found on homepage.');
  }

  // 1b. Capture Category state
  console.log(`Visiting ${CATEGORY_URL}...`);
  await page.goto(CATEGORY_URL, { waitUntil: 'networkidle2' });
  const catHtml = await page.content();
  fs.writeFileSync(path.join(OUTPUT_DIR, 'dynamic-category.html'), catHtml);

  // 2. Capture Product Overlay and Board Modal states (on a product page)
  console.log(`Visiting ${SAMPLE_PRODUCT_URL} to capture product overlay and board modal states...`);
  await page.goto(SAMPLE_PRODUCT_URL, { waitUntil: 'networkidle2' });

  // Capture Product Overlay
  const productFullscreenButton = await page.$('.product-fullscreen-button');
  if (productFullscreenButton) {
    await productFullscreenButton.click();
    await new Promise(r => setTimeout(r, 1000)); // Wait for overlay to open and content to load
    const html = await page.content();
    fs.writeFileSync(path.join(OUTPUT_DIR, 'dynamic-product-overlay-open.html'), html);
    console.log('✅ Captured product overlay open state.');
    // Close the overlay
    const overlayCloseButton = await page.$('.product-overlay-close');
    if (overlayCloseButton) {
      await overlayCloseButton.click();
      await new Promise(r => setTimeout(r, 500));
    }
  } else {
    console.warn('⚠️ Product fullscreen button (.product-fullscreen-button) not found on sample product page.');
  }

  // Capture Board Modal
  const boardModalTrigger = await page.$('.board-modal-trigger');
  if (boardModalTrigger) {
    await boardModalTrigger.click();
    await new Promise(r => setTimeout(r, 1000)); // Wait for modal to open
    const html = await page.content();
    fs.writeFileSync(path.join(OUTPUT_DIR, 'dynamic-board-modal-open.html'), html);
    console.log('✅ Captured board modal open state.');
    // Close the modal
    const boardModalCloseButton = await page.$('.board-modal-close');
    if (boardModalCloseButton) {
      await boardModalCloseButton.click();
      await new Promise(r => setTimeout(r, 500));
    }
  } else {
    console.warn('⚠️ Board modal trigger (.board-modal-trigger) not found on sample product page.');
  }

  await browser.close();
}

capture().catch(err => {
  console.error('Capture failed:', err);
  process.exit(1);
});