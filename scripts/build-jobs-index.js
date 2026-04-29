/**
 * build-jobs-index.js
 *
 * Scans every freelance-job/{slug}/content.md, extracts front matter (title, location),
 * and writes freelance-job/jobs-index.json.
 *
 * Run:  node scripts/build-jobs-index.js
 * Or:   npm run build-jobs-index
 *
 * Add this to package.json scripts if you want a shortcut:
 *   "build-jobs-index": "node scripts/build-jobs-index.js"
 *
 * The JSON is fetched at runtime by freelance-job/job-renderer.js to populate
 * the "Other vacancies" sidebar on every freelance job detail page.
 */

'use strict';

const fs   = require('fs');
const path = require('path');

const JOBS_DIR  = path.resolve(__dirname, '..', 'freelance-job');
const OUT_FILE  = path.join(JOBS_DIR, 'jobs-index.json');

function parseFrontMatter(text) {
  const m = text.match(/^---\r?\n([\s\S]*?)\r?\n---/);
  if (!m) return {};
  const meta = {};
  m[1].split('\n').forEach(line => {
    const kv = line.match(/^([^:]+):\s*"?(.*?)"?\s*$/);
    if (kv) meta[kv[1].trim()] = kv[2].trim();
  });
  return meta;
}

const jobs = [];

const entries = fs.readdirSync(JOBS_DIR, { withFileTypes: true });
for (const entry of entries) {
  if (!entry.isDirectory()) continue;
  const mdPath = path.join(JOBS_DIR, entry.name, 'content.md');
  if (!fs.existsSync(mdPath)) continue;

  const meta = parseFrontMatter(fs.readFileSync(mdPath, 'utf8'));
  if (!meta.title) {
    console.warn(`  [skip] ${entry.name}: no title in front matter`);
    continue;
  }

  jobs.push({
    slug:     entry.name,
    title:    meta.title,
    location: meta.location || '',
  });
  console.log(`  [ok]   ${entry.name} — ${meta.title} (${meta.location || '?'})`);
}

// Sort alphabetically by slug for stable output
jobs.sort((a, b) => a.slug.localeCompare(b.slug));

fs.writeFileSync(OUT_FILE, JSON.stringify(jobs, null, 2) + '\n', 'utf8');
console.log(`\nWrote ${jobs.length} job(s) to ${OUT_FILE}`);
