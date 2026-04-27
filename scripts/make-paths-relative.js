"use strict";

/**
 * Rewrites every root-relative URL in every HTML file to a depth-correct
 * relative path so the site works on GitHub Pages (served from a subpath)
 * without needing a custom domain.
 *
 *   depth 0  index.html              /foo/bar  →  foo/bar
 *   depth 1  about-us/index.html     /foo/bar  →  ../foo/bar
 *   depth 3  news/page/2/index.html  /foo/bar  →  ../../../foo/bar
 */

const fs = require("fs-extra");
const path = require("path");
const cheerio = require("cheerio");

const ROOT = path.resolve(__dirname, "..");
const BASE_URL = "https://www.konato.be";

// ---------------------------------------------------------------------------
// Core rewriter
// ---------------------------------------------------------------------------

function toRelative(url, depth) {
  if (!url) return url;

  // Strip the live site origin so it's treated like a root-relative path
  if (url.startsWith(BASE_URL)) url = url.slice(BASE_URL.length) || "/";

  // Leave external URLs, protocol-relative, fragments, data URIs, mailto alone
  if (
    url.startsWith("http") ||
    url.startsWith("//") ||
    url.startsWith("#") ||
    url.startsWith("mailto:") ||
    url.startsWith("tel:") ||
    url.startsWith("data:")
  ) return url;

  // Strip legacy GitHub Pages subpath prefix
  if (url.startsWith("/Konato-Website/")) url = url.slice("/Konato-Website".length);

  if (!url.startsWith("/")) return url; // already relative

  const prefix = "../".repeat(depth);
  const stripped = url.slice(1); // drop leading /

  // "/" alone → go up to root
  if (!stripped) return prefix || "./";

  return prefix + stripped;
}

// Rewrite url(...) occurrences in a CSS string
function rewriteCssUrls(css, depth) {
  return css.replace(
    /url\(\s*(['"]?)((?:https?:\/\/www\.konato\.be)?\/[^'"\s)]*)\1\s*\)/g,
    (_, quote, url) => `url(${quote}${toRelative(url, depth)}${quote})`
  );
}

// ---------------------------------------------------------------------------
// Per-file processing
// ---------------------------------------------------------------------------

// Attributes that carry URLs, mapped to their element selector
const ATTR_MAP = [
  ["a, link, area",                    "href"],
  ["script, img, source, iframe, embed", "src"],
  ["form",                             "action"],
  ["video, audio",                     "poster"],
  ["input[type=image]",                "src"],
];

function processHtml(html, depth) {
  const $ = cheerio.load(html, { decodeEntities: false });

  // Standard URL attributes
  for (const [sel, attr] of ATTR_MAP) {
    $(sel).each((_, el) => {
      const val = $(el).attr(attr);
      if (val) $(el).attr(attr, toRelative(val, depth));
    });
  }

  // meta[content] – og:image, twitter:image, canonical-like content
  $("meta[content]").each((_, el) => {
    const content = $(el).attr("content") || "";
    if (content.startsWith(BASE_URL) || content.startsWith("/")) {
      $(el).attr("content", toRelative(content, depth));
    }
  });

  // Inline <style> blocks
  $("style").each((_, el) => {
    const rewritten = rewriteCssUrls($(el).html() || "", depth);
    $(el).html(rewritten);
  });

  // Inline style attributes
  $("[style]").each((_, el) => {
    const rewritten = rewriteCssUrls($(el).attr("style") || "", depth);
    $(el).attr("style", rewritten);
  });

  return $.html();
}

// ---------------------------------------------------------------------------
// Walk & transform
// ---------------------------------------------------------------------------

async function walk(dir, fileList = []) {
  const entries = await fs.readdir(dir, { withFileTypes: true });
  for (const e of entries) {
    if (
      e.name.startsWith(".") ||
      e.name === "node_modules" ||
      e.name === "scripts"
    ) continue;
    const full = path.join(dir, e.name);
    if (e.isDirectory()) await walk(full, fileList);
    else if (e.name.endsWith(".html")) fileList.push(full);
  }
  return fileList;
}

async function main() {
  const files = await walk(ROOT);
  console.log(`Rewriting paths in ${files.length} HTML files…\n`);

  for (const file of files) {
    const rel = path.relative(ROOT, file);
    // depth = number of directory components (not counting the filename)
    const depth = rel.split(path.sep).length - 1;

    const html = await fs.readFile(file, "utf8");
    const rewritten = processHtml(html, depth);
    await fs.writeFile(file, rewritten, "utf8");

    console.log(`  depth ${depth}  ${rel}`);
  }

  console.log(`\n✓ Done — all paths are now relative`);
}

main().catch((err) => { console.error(err); process.exit(1); });
