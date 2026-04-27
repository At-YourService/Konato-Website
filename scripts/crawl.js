/**
 * konato.be static site crawler
 *
 * Fetches every page from the live WordPress site, strips runtime-only
 * WordPress artefacts, localises all assets, and writes the cleaned HTML
 * directly to the project root — ready to commit and deploy on Netlify.
 *
 * Usage:
 *   node scripts/crawl.js            # full run
 *   node scripts/crawl.js --dry-run  # discover URLs only, no writes
 */

"use strict";

const fetch = require("node-fetch");
const fs = require("fs-extra");
const path = require("path");
const cheerio = require("cheerio");
const pLimit = require("p-limit");

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------

const BASE_URL = "https://www.konato.be";
const API_BASE = `${BASE_URL}/wp-json/wp/v2`;
const OUT_DIR = path.resolve(__dirname, ".."); // project root
const ASSET_CONCURRENCY = 6;
const PAGE_CONCURRENCY = 3;

const DRY_RUN = process.argv.includes("--dry-run");

// Scripts whose src matches these patterns are removed (don't work statically)
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
  /wp-content\/plugins\/.*\/js/,  // all plugin JS
];

// CSS whose href matches these patterns are removed (GDPR / plugin-only UI)
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

// Elements removed wholesale
const REMOVE_SELECTORS = [
  "#wpadminbar",
  "#cookie-law-info-bar",
  "#cookie-law-info-again",
  ".cli-bar-container",
  ".cli-modal-backdrop",
  "#cliSettingsPopup",
  ".addtoany_share_save_container",
  "noscript",                // all noscript blocks are WP/tracking artefacts
];

// ---------------------------------------------------------------------------
// Asset cache  url → local web path
// ---------------------------------------------------------------------------

const assetCache = new Map();
const assetLimit = pLimit(ASSET_CONCURRENCY);

// ---------------------------------------------------------------------------
// URL helpers
// ---------------------------------------------------------------------------

const LEGACY_PREFIX_RE = /^\/Konato-Website/;

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

/** Final text-level sweep — catches anything missed by DOM traversal */
function stripLegacyOrigin(html) {
  return html
    .replace(/https?:\/\/www\.konato\.be\/Konato-Website\//g, "/")
    .replace(/https?:\/\/www\.konato\.be\//g, "/")
    .replace(/\/Konato-Website\//g, "/");
}

// ---------------------------------------------------------------------------
// Asset downloading
// ---------------------------------------------------------------------------

async function downloadAsset(rawUrl) {
  const absolute = toAbsolute(rawUrl);
  if (!absolute || !isInternal(absolute)) return toRootRelative(rawUrl);
  if (assetCache.has(absolute)) return assetCache.get(absolute);

  const parsed = new URL(absolute);
  const relPath = parsed.pathname.replace(LEGACY_PREFIX_RE, "");
  const fsPath = path.join(OUT_DIR, relPath);
  const webPath = relPath;

  // Optimistic cache — prevent parallel duplicate downloads
  assetCache.set(absolute, webPath);

  if (!DRY_RUN) {
    try {
      await fs.ensureDir(path.dirname(fsPath));
      const res = await fetch(absolute);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      await fs.writeFile(fsPath, await res.buffer());
    } catch (err) {
      console.warn(`  ⚠  asset ${relPath}: ${err.message}`);
    }
  }

  return webPath;
}

// ---------------------------------------------------------------------------
// HTML cleaning + asset localisation
// ---------------------------------------------------------------------------

async function processPage(rawHtml, pageUrl) {
  const $ = cheerio.load(rawHtml);

  // --- Remove runtime-only elements ---
  for (const sel of REMOVE_SELECTORS) $(sel).remove();

  $("script[src]").each((_, el) => {
    const src = $(el).attr("src") || "";
    if (REMOVE_SCRIPT_PATTERNS.some((re) => re.test(src))) $(el).remove();
  });

  // Remove inline WP emoji / wpEmojiSettings script blocks
  $("script:not([src])").each((_, el) => {
    const code = $(el).html() || "";
    if (/wpEmojiSettings|wp\.i18n|_wpemojiSettings/.test(code)) $(el).remove();
  });

  $('link[rel="stylesheet"]').each((_, el) => {
    const href = $(el).attr("href") || "";
    if (REMOVE_CSS_PATTERNS.some((re) => re.test(href))) $(el).remove();
  });

  // Remove generator / WP meta tags that leak internal info
  $('meta[name="generator"]').remove();
  $('link[rel="shortlink"]').remove();
  $('link[rel="wlwmanifest"]').remove();
  $('link[rel="EditURI"]').remove();
  $('link[rel="dns-prefetch"]').remove();

  // Fix canonical to root-relative
  $('link[rel="canonical"]').each((_, el) => {
    const href = $(el).attr("href") || "";
    $(el).attr("href", toRootRelative(href) || "/");
  });

  // Fix og: / twitter: meta with absolute konato.be URLs
  $("meta[property], meta[name]").each((_, el) => {
    const content = $(el).attr("content") || "";
    if (content.startsWith(BASE_URL) || LEGACY_PREFIX_RE.test(content)) {
      $(el).attr("content", toRootRelative(content));
    }
  });

  // --- Localise and relink assets (in parallel) ---
  const tasks = [];

  // CSS links
  $('link[rel="stylesheet"][href]').each((_, el) => {
    const href = $(el).attr("href");
    if (!isInternal(href)) return;
    tasks.push(
      assetLimit(async () => {
        const local = await downloadAsset(href);
        $(el).attr("href", local);
      })
    );
  });

  // JS src
  $("script[src]").each((_, el) => {
    const src = $(el).attr("src");
    if (!isInternal(src)) return;
    tasks.push(
      assetLimit(async () => {
        const local = await downloadAsset(src);
        $(el).attr("src", local);
      })
    );
  });

  // Images src
  $("img[src]").each((_, el) => {
    const src = $(el).attr("src");
    if (!isInternal(src)) return;
    tasks.push(
      assetLimit(async () => {
        const local = await downloadAsset(src);
        $(el).attr("src", local);
        // srcset refers to size variants we may not have — remove to avoid 404s
        $(el).removeAttr("srcset").removeAttr("sizes");
      })
    );
  });

  // Inline style: url(...)
  $("[style]").each((_, el) => {
    const style = $(el).attr("style") || "";
    const matches = [...style.matchAll(/url\(['"]?((?:https?:\/\/www\.konato\.be|\/(?:Konato-Website\/)?wp-content)[^'")\s]*)['"]?\)/g)];
    for (const m of matches) {
      tasks.push(
        assetLimit(async () => {
          const local = await downloadAsset(m[1]);
          $(el).attr("style", ($(el).attr("style") || "").replace(m[1], local));
        })
      );
    }
  });

  // CSS url(...) inside <style> blocks
  $("style").each((_, el) => {
    const css = $(el).html() || "";
    const matches = [...css.matchAll(/url\(['"]?((?:https?:\/\/www\.konato\.be|\/(?:Konato-Website\/)?wp-(?:content|includes))[^'")\s]*)['"]?\)/g)];
    for (const m of matches) {
      tasks.push(
        assetLimit(async () => {
          const local = await downloadAsset(m[1]);
          $(el).html(($(el).html() || "").replaceAll(m[1], local));
        })
      );
    }
  });

  // Internal <a href> — just rewrite, no download
  $("a[href]").each((_, el) => {
    const href = $(el).attr("href") || "";
    if (href.startsWith(BASE_URL) || LEGACY_PREFIX_RE.test(href)) {
      $(el).attr("href", toRootRelative(href));
    }
  });

  await Promise.all(tasks);

  // Final text sweep for anything missed above
  return stripLegacyOrigin($.html());
}

// ---------------------------------------------------------------------------
// URL discovery
// ---------------------------------------------------------------------------

async function fetchAllFromApi(endpoint) {
  const results = [];
  let page = 1;

  while (true) {
    const params = new URLSearchParams({ per_page: 100, page });
    const res = await fetch(`${API_BASE}/${endpoint}?${params}`);
    if (res.status === 400) break;
    if (!res.ok) { console.warn(`  ⚠  API ${endpoint} p${page}: ${res.status}`); break; }
    const data = await res.json();
    if (!Array.isArray(data) || data.length === 0) break;
    results.push(...data);
    const total = parseInt(res.headers.get("X-WP-TotalPages") || "1", 10);
    if (page >= total) break;
    page++;
  }

  return results;
}

async function discoverUrls() {
  console.log("  Fetching posts from API…");
  const posts = await fetchAllFromApi("posts");

  console.log("  Fetching pages from API…");
  const pages = await fetchAllFromApi("pages");

  const urls = new Set();

  // Homepage
  urls.add("/");

  for (const p of pages) {
    const rel = toRootRelative(p.link);
    if (rel && rel !== "/") urls.add(rel);
  }

  for (const p of posts) {
    const rel = toRootRelative(p.link);
    if (rel) urls.add(rel);
  }

  // News / blog archive — scrape pagination links from the first archive page
  try {
    const archiveRes = await fetch(`${BASE_URL}/news/`);
    if (archiveRes.ok) {
      const html = await archiveRes.text();
      const $ = cheerio.load(html);
      $('a[href*="/news/page/"]').each((_, el) => {
        const rel = toRootRelative($(el).attr("href") || "");
        if (rel) urls.add(rel);
      });
    }
  } catch (_) {}

  // Job / freelance-job sub-pages (apply forms, etc.) already tracked in git
  const tracked = (await fs.readdir(OUT_DIR, { withFileTypes: true }))
    .filter((d) => d.isDirectory() && !d.name.startsWith(".") && !d.name.startsWith("scripts") && d.name !== "node_modules")
    .map((d) => `/${d.name}/`);

  for (const t of tracked) urls.add(t);

  return [...urls].sort();
}

// ---------------------------------------------------------------------------
// Per-page save
// ---------------------------------------------------------------------------

async function savePage(relUrl) {
  const fullUrl = `${BASE_URL}${relUrl}`;
  let res;

  try {
    res = await fetch(fullUrl, { redirect: "follow" });
  } catch (err) {
    console.warn(`  ⚠  fetch ${relUrl}: ${err.message}`);
    return;
  }

  if (!res.ok) {
    console.warn(`  ⚠  ${relUrl}: HTTP ${res.status}`);
    return;
  }

  const raw = await res.text();
  const cleaned = await processPage(raw, relUrl);

  if (!DRY_RUN) {
    // /  → index.html   /about-us/ → about-us/index.html
    const fsPath =
      relUrl === "/"
        ? path.join(OUT_DIR, "index.html")
        : path.join(OUT_DIR, relUrl.replace(/^\//, ""), "index.html");

    await fs.ensureDir(path.dirname(fsPath));
    await fs.writeFile(fsPath, cleaned, "utf8");
  }
}

// ---------------------------------------------------------------------------
// Sitemap
// ---------------------------------------------------------------------------

async function writeSitemap(urls) {
  const entries = urls
    .map((u) => {
      const priority = u === "/" ? "1.0" : u.includes("/news/") && !u.includes("/page/") ? "0.8" : "0.6";
      return `  <url>\n    <loc>${BASE_URL}${u}</loc>\n    <changefreq>monthly</changefreq>\n    <priority>${priority}</priority>\n  </url>`;
    })
    .join("\n");

  const xml = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${entries}\n</urlset>`;

  if (!DRY_RUN) {
    await fs.writeFile(path.join(OUT_DIR, "sitemap.xml"), xml, "utf8");
    console.log("  ✓ sitemap.xml");
  }
}

// ---------------------------------------------------------------------------
// Netlify config
// ---------------------------------------------------------------------------

async function writeNetlifyConfig() {
  const toml = `[build]
  publish = "."
  command = "node scripts/crawl.js"

[[headers]]
  for = "/*"
  [headers.values]
    X-Frame-Options = "DENY"
    X-Content-Type-Options = "nosniff"
    Referrer-Policy = "strict-origin-when-cross-origin"

[[headers]]
  for = "/wp-content/*"
  [headers.values]
    Cache-Control = "public, max-age=31536000, immutable"

[[headers]]
  for = "/*.html"
  [headers.values]
    Cache-Control = "public, max-age=3600, must-revalidate"

[[redirects]]
  from = "/Konato-Website/*"
  to = "/:splat"
  status = 301
  force = true

[[redirects]]
  from = "/*"
  to = "/404.html"
  status = 404
`;

  const redirects = `# Strip legacy GitHub Pages subpath
/Konato-Website/*  /:splat  301!
`;

  if (!DRY_RUN) {
    await fs.writeFile(path.join(OUT_DIR, "netlify.toml"), toml, "utf8");
    await fs.writeFile(path.join(OUT_DIR, "_redirects"), redirects, "utf8");
    await fs.writeFile(path.join(OUT_DIR, "robots.txt"),
      `User-agent: *\nAllow: /\nSitemap: ${BASE_URL}/sitemap.xml\n`, "utf8");
    console.log("  ✓ netlify.toml, _redirects, robots.txt");
  }
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

async function main() {
  console.log(`\nkonato.be crawler${DRY_RUN ? " [dry run]" : ""}\n`);

  console.log("Discovering URLs…");
  const urls = await discoverUrls();
  console.log(`  ${urls.length} URLs to process\n`);

  if (DRY_RUN) {
    urls.forEach((u) => console.log(" ", u));
    return;
  }

  // Crawl pages with bounded concurrency
  const pageLimit = pLimit(PAGE_CONCURRENCY);
  let done = 0;

  await Promise.all(
    urls.map((url) =>
      pageLimit(async () => {
        await savePage(url);
        done++;
        process.stdout.write(`\r  ${done}/${urls.length} pages`);
      })
    )
  );

  console.log("\n");

  // Only include pages that exist as local HTML (e.g. exclude job sub-dirs that 404)
  const existingUrls = [];
  for (const u of urls) {
    const fsPath = u === "/" ? path.join(OUT_DIR, "index.html") : path.join(OUT_DIR, u.replace(/^\//, ""), "index.html");
    if (await fs.pathExists(fsPath)) existingUrls.push(u);
  }

  console.log("Writing meta files…");
  await writeSitemap(existingUrls);
  await writeNetlifyConfig();

  console.log(`\n✓ Done — ${existingUrls.length} pages, assets in wp-content/\n`);
}

main().catch((err) => {
  console.error("\nFailed:", err.message);
  process.exit(1);
});
