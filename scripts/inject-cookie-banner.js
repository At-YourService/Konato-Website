"use strict";

const fs = require("fs-extra");
const path = require("path");
const cheerio = require("cheerio");

const ROOT = path.resolve(__dirname, "..");

const BANNER_HTML = `
<div id="cookie-banner" role="dialog" aria-live="polite" aria-label="Cookie consent">
  <div id="cookie-banner-inner">
    <p>
      Wij gebruiken cookies om uw ervaring te verbeteren.
      <a href="/cookie-policy/">Meer info</a>
    </p>
    <div id="cookie-banner-actions">
      <button id="cookie-accept" class="cookie-btn cookie-btn--accept">Accepteer</button>
      <button id="cookie-decline" class="cookie-btn cookie-btn--decline">Weigeren</button>
    </div>
  </div>
</div>

<style>
#cookie-banner {
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 99999;
  background: rgba(17, 34, 51, 0.97);
  color: #fff;
  font-family: 'Open Sans', sans-serif;
  font-size: 14px;
  box-shadow: 0 -2px 12px rgba(0,0,0,.3);
}
#cookie-banner-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 16px 24px;
  display: flex;
  align-items: center;
  gap: 24px;
  flex-wrap: wrap;
}
#cookie-banner-inner p {
  margin: 0;
  flex: 1;
  min-width: 200px;
  line-height: 1.5;
}
#cookie-banner-inner a {
  color: #00ADDA;
  text-decoration: underline;
}
#cookie-banner-actions {
  display: flex;
  gap: 10px;
  flex-shrink: 0;
}
.cookie-btn {
  padding: 8px 22px;
  border: none;
  border-radius: 2px;
  font-size: 14px;
  font-family: inherit;
  cursor: pointer;
  transition: background .2s;
}
.cookie-btn--accept {
  background: #00ADDA;
  color: #fff;
}
.cookie-btn--accept:hover { background: #008aae; }
.cookie-btn--decline {
  background: transparent;
  color: #ccc;
  border: 1px solid #555;
}
.cookie-btn--decline:hover { background: rgba(255,255,255,.08); }
</style>

<script>
(function () {
  var banner = document.getElementById('cookie-banner');
  if (!banner) return;
  if (!localStorage.getItem('cookieConsent')) {
    banner.style.display = 'block';
  }
  document.getElementById('cookie-accept').addEventListener('click', function () {
    localStorage.setItem('cookieConsent', 'accepted');
    banner.style.display = 'none';
  });
  document.getElementById('cookie-decline').addEventListener('click', function () {
    localStorage.setItem('cookieConsent', 'declined');
    banner.style.display = 'none';
  });
})();
</script>`;

async function processFile(filePath) {
  const raw = await fs.readFile(filePath, "utf8");
  const $ = cheerio.load(raw, { decodeEntities: false });

  // Remove dead plugin remnants
  $('style#cookie-law-info-gdpr-inline-css').remove();
  $('script#cookie-law-info-js-extra').remove();
  $('link[href*="cookie-law-info"]').remove();
  $('link[href*="webtoffee"]').remove();
  $('script[src*="cookie-law-info"]').remove();
  $('script[src*="webtoffee"]').remove();

  // Remove the inline cli_cookiebar_settings script block
  $('script:not([src])').each((_, el) => {
    if (($(el).html() || '').includes('cli_cookiebar_settings')) $(el).remove();
  });

  // Replace the empty plugin container with our banner
  const container = $('.wt-cli-cookie-bar-container');
  if (container.length) {
    container.replaceWith(BANNER_HTML);
  } else if (!$('#cookie-banner').length) {
    // No container found — append before </body>
    $('body').append(BANNER_HTML);
  }

  await fs.writeFile(filePath, $.html(), "utf8");
}

async function main() {
  const htmlFiles = [];

  async function walk(dir) {
    const entries = await fs.readdir(dir, { withFileTypes: true });
    for (const e of entries) {
      if (e.name.startsWith('.') || e.name === 'node_modules' || e.name === 'scripts') continue;
      const full = path.join(dir, e.name);
      if (e.isDirectory()) await walk(full);
      else if (e.name.endsWith('.html')) htmlFiles.push(full);
    }
  }

  await walk(ROOT);
  console.log(`Processing ${htmlFiles.length} HTML files…`);

  for (const f of htmlFiles) {
    await processFile(f);
    console.log(' ✓', path.relative(ROOT, f));
  }

  console.log('\nDone.');
}

main().catch((err) => { console.error(err); process.exit(1); });
