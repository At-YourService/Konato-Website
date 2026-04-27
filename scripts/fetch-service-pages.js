"use strict";

/**
 * Fetches the 6 service sub-pages from the live konato.be site and saves
 * them locally using the same processing pipeline as crawl.js.
 * Run with:  node scripts/fetch-service-pages.js
 */

const fetch = require("node-fetch");
const fs = require("fs-extra");
const path = require("path");
const cheerio = require("cheerio");
const pLimit = require("p-limit");

const BASE_URL = "https://www.konato.be";
const OUT_DIR = path.resolve(__dirname, "..");
const LEGACY_PREFIX_RE = /^\/Konato-Website/;
const ASSET_CONCURRENCY = 6;

const SERVICE_URLS = [
  "/service/applied-ai/",
  "/service/project-management/",
  "/service/analysis/",
  "/service/testing/",
  "/service/business-strategy/",
  "/service/project-tooling/",
];

const REMOVE_SCRIPT_PATTERNS = [
  /wp-emoji-release/,
  /wp-includes\/js/,
  /googletagmanager/,
  /google-analytics/,
  /gtag\/js/,
  /recaptcha/,
  /addtoany/,
  /cookie-law-info/,
  /webtoffee/,
  /wpcf7-redirect/,
  /wp-content\/plugins\/.*\/js/,
];

const REMOVE_CSS_PATTERNS = [
  /cookie-law-info/,
  /webtoffee/,
  /wpcf7-redirect/,
  /photo-gallery\/booster/,
  /sumoselect/,
  /mCustomScrollbar/,
  /bwg-fonts/,
  /pushlabs-vidbg/,
];

const REMOVE_SELECTORS = [
  "#wpadminbar",
  "#cookie-law-info-bar",
  "#cookie-law-info-again",
  ".cli-bar-container",
  ".cli-modal-backdrop",
  "#cliSettingsPopup",
  ".addtoany_share_save_container",
  "noscript",
];

const assetCache = new Map();
const assetLimit = pLimit(ASSET_CONCURRENCY);

function toAbsolute(url) {
  if (!url || url.startsWith("data:")) return url;
  if (url.startsWith("http")) return url;
  const stripped = url.replace(LEGACY_PREFIX_RE, "");
  return `${BASE_URL}${stripped.startsWith("/") ? stripped : "/" + stripped}`;
}

function toRootRelative(url) {
  if (!url) return url;
  return url
    .replace(/^https?:\/\/www\.konato\.be/, "")
    .replace(LEGACY_PREFIX_RE, "") || "/";
}

function isInternal(url) {
  if (!url) return false;
  return (
    url.startsWith(BASE_URL) ||
    url.startsWith("/wp-content/") ||
    url.startsWith("/wp-includes/") ||
    url.startsWith("/Konato-Website/")
  );
}

function stripLegacyOrigin(html) {
  return html
    .replace(/https?:\/\/www\.konato\.be\/Konato-Website\//g, "/")
    .replace(/https?:\/\/www\.konato\.be\//g, "/")
    .replace(/\/Konato-Website\//g, "/");
}

async function downloadAsset(rawUrl) {
  const absolute = toAbsolute(rawUrl);
  if (!absolute || !isInternal(absolute)) return toRootRelative(rawUrl);
  if (assetCache.has(absolute)) return assetCache.get(absolute);

  const parsed = new URL(absolute);
  const relPath = parsed.pathname.replace(LEGACY_PREFIX_RE, "");
  const fsPath = path.join(OUT_DIR, relPath);
  const webPath = relPath;

  assetCache.set(absolute, webPath);

  try {
    await fs.ensureDir(path.dirname(fsPath));
    const res = await fetch(absolute);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    await fs.writeFile(fsPath, await res.buffer());
  } catch (err) {
    console.warn(`  ⚠  asset ${relPath}: ${err.message}`);
  }

  return webPath;
}

async function processPage(rawHtml) {
  const $ = cheerio.load(rawHtml);

  for (const sel of REMOVE_SELECTORS) $(sel).remove();

  $("script[src]").each((_, el) => {
    const src = $(el).attr("src") || "";
    if (REMOVE_SCRIPT_PATTERNS.some((re) => re.test(src))) $(el).remove();
  });

  $("script:not([src])").each((_, el) => {
    const code = $(el).html() || "";
    if (/wpEmojiSettings|wp\.i18n|_wpemojiSettings/.test(code)) $(el).remove();
  });

  $('link[rel="stylesheet"]').each((_, el) => {
    const href = $(el).attr("href") || "";
    if (REMOVE_CSS_PATTERNS.some((re) => re.test(href))) $(el).remove();
  });

  $('meta[name="generator"]').remove();
  $('link[rel="shortlink"]').remove();
  $('link[rel="wlwmanifest"]').remove();
  $('link[rel="EditURI"]').remove();
  $('link[rel="dns-prefetch"]').remove();

  $('link[rel="canonical"]').each((_, el) => {
    $(el).attr("href", toRootRelative($(el).attr("href") || "") || "/");
  });

  $("meta[property], meta[name]").each((_, el) => {
    const content = $(el).attr("content") || "";
    if (content.startsWith(BASE_URL) || LEGACY_PREFIX_RE.test(content)) {
      $(el).attr("content", toRootRelative(content));
    }
  });

  const tasks = [];

  $('link[rel="stylesheet"][href]').each((_, el) => {
    const href = $(el).attr("href");
    if (!isInternal(href)) return;
    tasks.push(assetLimit(async () => $(el).attr("href", await downloadAsset(href))));
  });

  $("script[src]").each((_, el) => {
    const src = $(el).attr("src");
    if (!isInternal(src)) return;
    tasks.push(assetLimit(async () => $(el).attr("src", await downloadAsset(src))));
  });

  $("img[src]").each((_, el) => {
    const src = $(el).attr("src");
    if (!isInternal(src)) return;
    tasks.push(assetLimit(async () => {
      $(el).attr("src", await downloadAsset(src));
      $(el).removeAttr("srcset").removeAttr("sizes");
    }));
  });

  $("[style]").each((_, el) => {
    const style = $(el).attr("style") || "";
    const matches = [...style.matchAll(/url\(['"]?((?:https?:\/\/www\.konato\.be|\/(?:Konato-Website\/)?wp-content)[^'")\s]*)['"]?\)/g)];
    for (const m of matches) {
      tasks.push(assetLimit(async () => {
        const local = await downloadAsset(m[1]);
        $(el).attr("style", ($(el).attr("style") || "").replace(m[1], local));
      }));
    }
  });

  $("style").each((_, el) => {
    const css = $(el).html() || "";
    const matches = [...css.matchAll(/url\(['"]?((?:https?:\/\/www\.konato\.be|\/(?:Konato-Website\/)?wp-(?:content|includes))[^'")\s]*)['"]?\)/g)];
    for (const m of matches) {
      tasks.push(assetLimit(async () => {
        const local = await downloadAsset(m[1]);
        $(el).html(($(el).html() || "").replaceAll(m[1], local));
      }));
    }
  });

  $("a[href]").each((_, el) => {
    const href = $(el).attr("href") || "";
    if (href.startsWith(BASE_URL) || LEGACY_PREFIX_RE.test(href)) {
      $(el).attr("href", toRootRelative(href));
    }
  });

  await Promise.all(tasks);

  return stripLegacyOrigin($.html());
}

async function savePage(relUrl) {
  const fullUrl = `${BASE_URL}${relUrl}`;
  console.log(`  Fetching ${relUrl}…`);

  const res = await fetch(fullUrl, { redirect: "follow" });
  if (!res.ok) { console.warn(`  ⚠  HTTP ${res.status} for ${relUrl}`); return; }

  const raw = await res.text();
  const cleaned = await processPage(raw);

  const fsPath = path.join(OUT_DIR, relUrl.replace(/^\//, ""), "index.html");
  await fs.ensureDir(path.dirname(fsPath));
  await fs.writeFile(fsPath, cleaned, "utf8");
  console.log(`  ✓ saved ${fsPath.replace(OUT_DIR, "")}`);
}

async function main() {
  console.log("\nFetching service sub-pages…\n");
  for (const url of SERVICE_URLS) {
    await savePage(url);
  }
  console.log("\n✓ Done\n");
}

main().catch((err) => {
  console.error("\nFailed:", err.message);
  process.exit(1);
});
