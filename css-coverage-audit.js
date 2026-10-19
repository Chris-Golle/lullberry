#!/usr/bin/env node

/**
 * CSS coverage audit for lullberry.com
 *
 * Usage:
 *   node css-coverage-audit.js
 *
 * Requirements:
 *   npm i puppeteer-core
 */

const fs = require("fs");
const path = require("path");
const crypto = require("crypto");
const puppeteer = require("puppeteer-core");
const csstree = require("css-tree");

const CHROMIUM_PATH =
  process.env.CHROMIUM_PATH ||
  "/Applications/Chromium.app/Contents/MacOS/Chromium";

const SITE_ORIGIN = "https://www.lullberry.com";
const SITEMAP_CANDIDATES = [
  `${SITE_ORIGIN}/sitemap_index.xml`,
  `${SITE_ORIGIN}/sitemap.xml`,
];

const STYLE_CSS_PATH = path.join(
  __dirname,
  "app/public/wp-content/themes/twentytwentyfive-child/style.css"
);
const STYLE_CSS_URL_FRAGMENT = "/wp-content/themes/twentytwentyfive-child/style.css";
const WAIT_MS_PER_PAGE = Number(process.env.WAIT_MS_PER_PAGE || 5000);
const ENABLE_INTERACTIVE = process.argv.includes("--interactive");
const ENABLE_STRENGTHS_FOCUS = process.argv.includes("--focus-strengths");
const ENABLE_STRENGTHS_SAMPLE = process.argv.includes("--focus-strengths-sample");
const ENABLE_STRENGTHS_HYBRID = process.argv.includes("--focus-strengths-hybrid");

const ECO_BAG_URLS = [
  "https://www.lullberry.com/shop/an-eco-bag-for-calm/",
  "https://www.lullberry.com/shop/an-eco-bag-for-compassion/",
  "https://www.lullberry.com/shop/an-eco-bag-for-courage/",
  "https://www.lullberry.com/shop/an-eco-bag-for-creativity/",
  "https://www.lullberry.com/shop/an-eco-bag-for-curiosity/",
  "https://www.lullberry.com/shop/an-eco-bag-for-discipline/",
  "https://www.lullberry.com/shop/an-eco-bag-for-forgiveness/",
  "https://www.lullberry.com/shop/an-eco-bag-for-gratitude/",
  "https://www.lullberry.com/shop/an-eco-bag-for-honesty/",
  "https://www.lullberry.com/shop/an-eco-bag-for-hope/",
  "https://www.lullberry.com/shop/an-eco-bag-for-humility/",
  "https://www.lullberry.com/shop/an-eco-bag-for-humor/",
  "https://www.lullberry.com/shop/an-eco-bag-for-joy/",
  "https://www.lullberry.com/shop/an-eco-bag-for-kindness/",
  "https://www.lullberry.com/shop/an-eco-bag-for-leadership/",
  "https://www.lullberry.com/shop/an-eco-bag-for-love-of-learning/",
  "https://www.lullberry.com/shop/an-eco-bag-for-love/",
  "https://www.lullberry.com/shop/an-eco-bag-for-spirituality/",
  "https://www.lullberry.com/shop/an-eco-bag-for-team-spirit/",
  "https://www.lullberry.com/shop/an-eco-bag-for-wisdom/",
];

const DAYS_21_URLS = [
  "https://www.lullberry.com/shop/21-days-digital-detox/",
  "https://www.lullberry.com/shop/21-days-to-aging-gracefully/",
  "https://www.lullberry.com/shop/21-days-to-coping-with-big-events/",
  "https://www.lullberry.com/shop/21-days-to-courage-and-confidence/",
  "https://www.lullberry.com/shop/21-days-to-creativity/",
  "https://www.lullberry.com/shop/21-days-to-finding-your-calling/",
  "https://www.lullberry.com/shop/21-days-to-gratitude/",
  "https://www.lullberry.com/shop/21-days-to-losing-weight/",
];

const LOOK_AT_YOU_CATEGORY_URLS = [
  "https://www.lullberry.com/cat/look-at-you/calm/",
  "https://www.lullberry.com/cat/look-at-you/compassion/",
  "https://www.lullberry.com/cat/look-at-you/courage/",
  "https://www.lullberry.com/cat/look-at-you/creativity/",
  "https://www.lullberry.com/cat/look-at-you/curiosity/",
  "https://www.lullberry.com/cat/look-at-you/discipline/",
  "https://www.lullberry.com/cat/look-at-you/forgiveness/",
  "https://www.lullberry.com/cat/look-at-you/gratitude/",
  "https://www.lullberry.com/cat/look-at-you/honesty/",
  "https://www.lullberry.com/cat/look-at-you/hope/",
  "https://www.lullberry.com/cat/look-at-you/humility/",
  "https://www.lullberry.com/cat/look-at-you/humor/",
  "https://www.lullberry.com/cat/look-at-you/joy/",
  "https://www.lullberry.com/cat/look-at-you/kindness/",
  "https://www.lullberry.com/cat/look-at-you/leadership/",
  "https://www.lullberry.com/cat/look-at-you/love-of-learning/",
  "https://www.lullberry.com/cat/look-at-you/love/",
  "https://www.lullberry.com/cat/look-at-you/spirituality/",
  "https://www.lullberry.com/cat/look-at-you/team-spirit/",
  "https://www.lullberry.com/cat/look-at-you/wisdom/",
];

const THREE_D_RIBBONS_URLS = [
  "https://www.lullberry.com/3d-ribbons/aging/",
  "https://www.lullberry.com/3d-ribbons/breaking-a-bad-habit/",
  "https://www.lullberry.com/3d-ribbons/coping-with-big-events/",
  "https://www.lullberry.com/3d-ribbons/courage/",
  "https://www.lullberry.com/3d-ribbons/creativity/",
  "https://www.lullberry.com/3d-ribbons/digital-detox/",
  "https://www.lullberry.com/3d-ribbons/finding-your-calling/",
  "https://www.lullberry.com/3d-ribbons/gratitude/",
  "https://www.lullberry.com/3d-ribbons/loosing-weight/",
];

function bytesUsed(ranges) {
  return ranges.reduce((sum, r) => sum + Math.max(0, r.end - r.start), 0);
}

function percent(used, total) {
  if (!total) return 0;
  return (used / total) * 100;
}

function log(message) {
  process.stdout.write(`${message}\n`);
}

function normalizeUrl(rawUrl) {
  const url = new URL(rawUrl);
  url.hash = "";
  return url.toString();
}

function canonicalStylesheetKey(rawUrl) {
  if (!rawUrl || rawUrl === "inline-style") return rawUrl || "inline-style";
  if (/^[a-z]+-inline-css$/i.test(rawUrl)) return rawUrl;
  if (/^global-styles-inline-css$/i.test(rawUrl)) return rawUrl;
  if (rawUrl.startsWith("http://") || rawUrl.startsWith("https://")) {
    const url = new URL(rawUrl);
    url.hash = "";
    url.search = "";
    return url.toString();
  }
  return rawUrl;
}

function looksLikeHtmlPage(urlString) {
  const pathname = new URL(urlString).pathname.toLowerCase();
  return !/\.(xml|jpg|jpeg|png|gif|webp|svg|pdf|zip|mp4|webm|mp3|json|css|js)$/.test(pathname);
}

function extractLocValues(xmlText) {
  const matches = xmlText.matchAll(/<loc>([\s\S]*?)<\/loc>/gi);
  return Array.from(matches, (m) => m[1].trim()).filter(Boolean);
}

async function fetchText(url) {
  const response = await fetch(url, {
    redirect: "follow",
    headers: { "user-agent": "lullberry-css-coverage-audit" },
  });
  if (!response.ok) {
    throw new Error(`Failed to fetch ${url}: ${response.status} ${response.statusText}`);
  }
  return response.text();
}

async function discoverAllPagesFromSitemaps() {
  const sitemapQueue = [...SITEMAP_CANDIDATES];
  const seenSitemaps = new Set();
  const pageUrls = new Set();

  while (sitemapQueue.length > 0) {
    const sitemapUrl = sitemapQueue.shift();
    if (!sitemapUrl || seenSitemaps.has(sitemapUrl)) continue;
    seenSitemaps.add(sitemapUrl);

    let xmlText = "";
    try {
      log(`Reading sitemap ${sitemapUrl}`);
      xmlText = await fetchText(sitemapUrl);
    } catch (_) {
      continue;
    }

    const locValues = extractLocValues(xmlText);
    for (const loc of locValues) {
      let normalized = "";
      try {
        normalized = normalizeUrl(loc);
      } catch (_) {
        continue;
      }

      if (!normalized.startsWith(SITE_ORIGIN)) continue;

      if (normalized.endsWith(".xml")) {
        if (!seenSitemaps.has(normalized)) sitemapQueue.push(normalized);
        continue;
      }

      if (looksLikeHtmlPage(normalized)) {
        pageUrls.add(normalized);
      }
    }
  }

  const sortedPages = Array.from(pageUrls).sort((a, b) => a.localeCompare(b));
  if (!sortedPages.includes(`${SITE_ORIGIN}/`)) {
    sortedPages.unshift(`${SITE_ORIGIN}/`);
  }
  return sortedPages;
}

function getFocusedStrengthPages() {
  const unique = new Set();
  [
    ...ECO_BAG_URLS,
    ...DAYS_21_URLS,
    ...LOOK_AT_YOU_CATEGORY_URLS,
    ...THREE_D_RIBBONS_URLS,
  ].forEach((url) => {
    unique.add(normalizeUrl(url));
  });
  return Array.from(unique);
}

function getFocusedStrengthSamplePages() {
  return [
    normalizeUrl(ECO_BAG_URLS[0]),
    normalizeUrl(DAYS_21_URLS[0]),
    normalizeUrl(LOOK_AT_YOU_CATEGORY_URLS[0]),
    normalizeUrl(THREE_D_RIBBONS_URLS[0]),
  ];
}

function toNormalizedSet(urls) {
  return new Set(urls.map((u) => normalizeUrl(u)));
}

async function getHybridStrengthPagesFromSitemap() {
  const allPages = await discoverAllPagesFromSitemaps();
  const groupedPagesSet = toNormalizedSet([
    ...ECO_BAG_URLS,
    ...DAYS_21_URLS,
    ...LOOK_AT_YOU_CATEGORY_URLS,
    ...THREE_D_RIBBONS_URLS,
  ]);

  const representatives = getFocusedStrengthSamplePages();
  const filtered = allPages.filter((url) => !groupedPagesSet.has(normalizeUrl(url)));
  const combined = new Set([...filtered, ...representatives]);
  return Array.from(combined).sort((a, b) => a.localeCompare(b));
}

function mergeRanges(ranges) {
  if (!ranges.length) return [];
  const sorted = [...ranges].sort((a, b) => a.start - b.start);
  const merged = [sorted[0]];
  for (let i = 1; i < sorted.length; i += 1) {
    const prev = merged[merged.length - 1];
    const cur = sorted[i];
    if (cur.start <= prev.end) {
      prev.end = Math.max(prev.end, cur.end);
    } else {
      merged.push({ ...cur });
    }
  }
  return merged;
}

function hasRangeIntersection(rangeStart, rangeEnd, mergedRanges) {
  for (const r of mergedRanges) {
    if (r.end <= rangeStart) continue;
    if (r.start >= rangeEnd) break;
    return true;
  }
  return false;
}

function extractUnusedSelectors(styleCssText, mergedUsedRanges) {
  const ast = csstree.parse(styleCssText, {
    positions: true,
    parseCustomProperty: true,
  });

  const uniqueSelectors = new Set();
  const unusedSelectors = [];

  csstree.walk(ast, {
    visit: "Rule",
    enter(node, item, list) {
      if (!node.prelude || node.prelude.type !== "SelectorList" || !node.loc) {
        return;
      }

      // Ignore keyframe step selectors like "from", "to", "50%".
      let parent = list;
      while (parent) {
        const parentNode = parent.node;
        if (parentNode && parentNode.type === "Atrule" && parentNode.name === "keyframes") {
          return;
        }
        parent = parent.parent;
      }

      const start = node.loc.start.offset;
      const end = node.loc.end.offset;
      const ruleIsUsed = hasRangeIntersection(start, end, mergedUsedRanges);
      if (ruleIsUsed) return;

      node.prelude.children.forEach((selectorNode) => {
        const selector = csstree.generate(selectorNode).trim();
        const dedupeKey = `${selector}::${start}::${end}`;
        if (uniqueSelectors.has(dedupeKey)) return;
        uniqueSelectors.add(dedupeKey);

        unusedSelectors.push({
          selector,
          ruleStartOffset: start,
          ruleEndOffset: end,
        });
      });
    },
  });

  return unusedSelectors;
}

async function runInteractiveActions(page) {
  await page.evaluate(() => {
    document.querySelectorAll(".start-quiz").forEach((el) => {
      if (el instanceof HTMLElement) el.click();
    });
  });
  await new Promise((resolve) => setTimeout(resolve, 500));

  await page.mouse.move(20, 20);
  await page.hover("button, a, .quiz-answer, .product-fullscreen-button").catch(() => {});
  await new Promise((resolve) => setTimeout(resolve, 350));

  await page.evaluate(() => {
    document.querySelectorAll(".product-fullscreen-button,.board-modal-trigger").forEach((el) => {
      if (el instanceof HTMLElement) el.click();
    });
  });
  await new Promise((resolve) => setTimeout(resolve, 700));

  await page.keyboard.press("Escape").catch(() => {});
  await new Promise((resolve) => setTimeout(resolve, 250));
}

async function run() {
  const targetPages = ENABLE_STRENGTHS_SAMPLE
    ? getFocusedStrengthSamplePages()
    : ENABLE_STRENGTHS_HYBRID
      ? await getHybridStrengthPagesFromSitemap()
    : ENABLE_STRENGTHS_FOCUS
      ? getFocusedStrengthPages()
      : await discoverAllPagesFromSitemaps();

  if (targetPages.length === 0) {
    throw new Error("No pages discovered from sitemap.");
  }
  log(
    ENABLE_STRENGTHS_SAMPLE
      ? `Using focused strengths sample set (${targetPages.length} pages).`
      : ENABLE_STRENGTHS_HYBRID
      ? `Using hybrid strengths set (${targetPages.length} pages): sitemap pages + 1 representative per grouped strengths family.`
      : ENABLE_STRENGTHS_FOCUS
      ? `Using focused strengths set (${targetPages.length} unique pages).`
      : `Discovered ${targetPages.length} pages from sitemap(s).`
  );

  const browser = await puppeteer.launch({
    executablePath: CHROMIUM_PATH,
    headless: true,
  });
  const page = await browser.newPage();

  await page.setViewport({ width: 1440, height: 900 });

  // Keep coverage across navigations.
  await page.coverage.startCSSCoverage({ resetOnNavigation: false });

  for (const url of targetPages) {
    log(`Visiting ${url}`);
    await page.goto(url, { waitUntil: "networkidle2", timeout: 60000 });
    await new Promise((resolve) => setTimeout(resolve, WAIT_MS_PER_PAGE));
    if (ENABLE_INTERACTIVE) {
      await runInteractiveActions(page);
    }
  }

  const coverage = await page.coverage.stopCSSCoverage();
  await browser.close();

  const sheetMap = new Map();
  for (const entry of coverage) {
    const rawUrl = entry.url || "inline-style";
    const canonicalUrl = canonicalStylesheetKey(rawUrl);
    const text = entry.text || "";
    const textHash = crypto.createHash("sha1").update(text).digest("hex");
    const dedupeKey = `${canonicalUrl}::${textHash}`;

    if (!sheetMap.has(dedupeKey)) {
      sheetMap.set(dedupeKey, {
        url: canonicalUrl,
        displayUrl: rawUrl,
        totalBytes: text.length,
        ranges: [],
        chunks: 0,
        text,
      });
    }
    const row = sheetMap.get(dedupeKey);
    row.ranges.push(...(entry.ranges || []));
    row.chunks += 1;
  }

  const aggregatedByUrl = new Map();
  for (const row of sheetMap.values()) {
    const merged = mergeRanges(row.ranges);
    const usedBytes = bytesUsed(merged);
    const key = row.url;
    if (!aggregatedByUrl.has(key)) {
      aggregatedByUrl.set(key, {
        url: key,
        totalBytes: 0,
        usedBytes: 0,
        chunks: 0,
      });
    }
    const out = aggregatedByUrl.get(key);
    out.totalBytes += row.totalBytes;
    out.usedBytes += Math.min(usedBytes, row.totalBytes);
    out.chunks += row.chunks;
  }

  const report = Array.from(aggregatedByUrl.values())
    .map((row) => {
      const unusedBytes = Math.max(0, row.totalBytes - row.usedBytes);
      const usedPct = percent(row.usedBytes, row.totalBytes);
      const unusedPct = 100 - usedPct;
      return {
        ...row,
        unusedBytes,
        usedPercent: Number(usedPct.toFixed(2)),
        unusedPercent: Number(unusedPct.toFixed(2)),
      };
    })
    .sort((a, b) => b.unusedBytes - a.unusedBytes);

  const styleCssCoverageEntries = Array.from(sheetMap.values()).filter(
    (entry) => entry.url && entry.url.includes(STYLE_CSS_URL_FRAGMENT)
  );
  const mergedUsedRanges = mergeRanges(
    styleCssCoverageEntries.flatMap((entry) => entry.ranges || [])
  );
  const styleCssText = fs.readFileSync(STYLE_CSS_PATH, "utf8");
  const unusedSelectors = extractUnusedSelectors(styleCssText, mergedUsedRanges);

  const totals = report.reduce(
    (acc, r) => {
      acc.totalBytes += r.totalBytes;
      acc.usedBytes += r.usedBytes;
      acc.unusedBytes += r.unusedBytes;
      return acc;
    },
    { totalBytes: 0, usedBytes: 0, unusedBytes: 0 }
  );

  const summary = {
    pagesVisited: targetPages,
    resetOnNavigation: false,
    interactiveMode: ENABLE_INTERACTIVE,
    focusStrengthsMode: ENABLE_STRENGTHS_FOCUS,
    focusStrengthsSampleMode: ENABLE_STRENGTHS_SAMPLE,
    focusStrengthsHybridMode: ENABLE_STRENGTHS_HYBRID,
    generatedAt: new Date().toISOString(),
    totals: {
      ...totals,
      usedPercent: Number(percent(totals.usedBytes, totals.totalBytes).toFixed(2)),
      unusedPercent: Number((100 - percent(totals.usedBytes, totals.totalBytes)).toFixed(2)),
    },
    stylesheets: report,
    localStyleCss: {
      path: STYLE_CSS_PATH,
      matchedCoverageChunks: styleCssCoverageEntries.length,
      usedRangesMergedCount: mergedUsedRanges.length,
      unusedSelectorsCount: unusedSelectors.length,
      unusedSelectors,
    },
  };

  const outJson = path.join(process.cwd(), "css-coverage-report.json");
  fs.writeFileSync(outJson, JSON.stringify(summary, null, 2), "utf8");

  const lines = [];
  lines.push("# CSS Coverage Report");
  lines.push("");
  lines.push(`Generated: ${summary.generatedAt}`);
  lines.push(`resetOnNavigation: false`);
  lines.push(`interactiveMode: ${ENABLE_INTERACTIVE}`);
  lines.push(`focusStrengthsMode: ${ENABLE_STRENGTHS_FOCUS}`);
  lines.push(`focusStrengthsSampleMode: ${ENABLE_STRENGTHS_SAMPLE}`);
  lines.push(`focusStrengthsHybridMode: ${ENABLE_STRENGTHS_HYBRID}`);
  lines.push("");
  lines.push("## Pages Visited");
  for (const p of targetPages) lines.push(`- ${p}`);
  lines.push("");
  lines.push("## Totals");
  lines.push(`- Total bytes: ${summary.totals.totalBytes}`);
  lines.push(`- Used bytes: ${summary.totals.usedBytes} (${summary.totals.usedPercent}%)`);
  lines.push(`- Unused bytes: ${summary.totals.unusedBytes} (${summary.totals.unusedPercent}%)`);
  lines.push("");
  lines.push("## Unused CSS by Stylesheet");
  lines.push("");
  lines.push("| URL | Unused Bytes | Unused % | Used % |");
  lines.push("| --- | ---: | ---: | ---: |");
  for (const row of report) {
    lines.push(
      `| ${row.url} | ${row.unusedBytes} | ${row.unusedPercent}% | ${row.usedPercent}% |`
    );
  }
  lines.push("");

  lines.push("## Unused Selectors in twentytwentyfive-child/style.css");
  lines.push("");
  if (unusedSelectors.length === 0) {
    lines.push("- No fully-unused selectors were found.");
  } else {
    for (const row of unusedSelectors) {
      lines.push(`- \`${row.selector}\``);
    }
  }
  lines.push("");

  const outMd = path.join(process.cwd(), "css-coverage-report.md");
  fs.writeFileSync(outMd, lines.join("\n"), "utf8");

  log(`Saved JSON report: ${outJson}`);
  log(`Saved Markdown report: ${outMd}`);
}

run().catch((err) => {
  process.stderr.write(`CSS coverage audit failed: ${err?.stack || err}\n`);
  process.exit(1);
});
