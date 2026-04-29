/* Loads content.md, parses front matter, renders markdown into the job template.
   Also fetches jobs-index.json and builds the "Other vacancies" sidebar dynamically. */
(function () {
  'use strict';

  // ── Front-matter parser ─────────────────────────────────────────────────────
  function parseFrontMatter(text) {
    var m = text.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n([\s\S]*)$/);
    if (!m) return { meta: {}, body: text };
    var meta = {};
    m[1].split('\n').forEach(function (line) {
      var kv = line.match(/^([^:]+):\s*"?(.*?)"?\s*$/);
      if (kv) meta[kv[1].trim()] = kv[2].trim();
    });
    return { meta: meta, body: m[2] };
  }

  // ── Derive the current job slug from the URL ────────────────────────────────
  // Works for both:
  //   /freelance-job/sr-functioneel-analist/
  //   /Konato-Website/freelance-job/sr-functioneel-analist/
  function currentSlug() {
    var parts = window.location.pathname.split('/').filter(Boolean);
    // The slug is the segment right after "freelance-job"
    var idx = parts.indexOf('freelance-job');
    if (idx !== -1 && parts[idx + 1]) return parts[idx + 1];
    // Fallback: second-to-last segment
    return parts[parts.length - 1] || '';
  }

  // ── Render "Other vacancies" sidebar ───────────────────────────────────────
  function renderOtherVacancies(jobs, slug) {
    var container = document.getElementById('other-vacancies');
    if (!container) return;

    var others = jobs.filter(function (j) { return j.slug !== slug; });

    if (others.length === 0) {
      container.innerHTML = '<p style="color:#888;font-size:13px">No other vacancies at the moment.</p>';
      return;
    }

    var html = others.map(function (j) {
      return (
        '<div class="row">' +
          '<div class="col-sm-12">' +
            '<a class="small-job-block" href="../' + j.slug + '/index.html">' +
              '<h4>' + escHtml(j.title) + '</h4>' +
              (j.location ? '<p>' + escHtml(j.location) + '</p>' : '') +
            '</a>' +
          '</div>' +
        '</div>'
      );
    }).join('');

    container.innerHTML = html;
  }

  function escHtml(str) {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // ── Main loader ─────────────────────────────────────────────────────────────
  function loadJob() {
    var slug = currentSlug();

    // Load job content and jobs index in parallel
    Promise.all([
      fetch('./content.md').then(function (r) {
        if (!r.ok) throw new Error('content.md not found');
        return r.text();
      }),
      fetch('../jobs-index.json').then(function (r) {
        if (!r.ok) return [];          // non-fatal: sidebar stays empty
        return r.json();
      }).catch(function () { return []; }),
    ]).then(function (results) {
      var text  = results[0];
      var jobs  = results[1];

      // Render job content
      var parsed = parseFrontMatter(text);
      var meta   = parsed.meta;
      var body   = parsed.body;

      if (meta.title) {
        document.title = meta.title + ' | Konato';
        var titleEl = document.getElementById('job-title');
        if (titleEl) titleEl.textContent = meta.title;
      }

      var bodyEl = document.getElementById('job-body');
      if (bodyEl && window.marked) {
        bodyEl.innerHTML = window.marked.parse(body);
      }

      if (meta.apply_path) {
        var applyBtn = document.getElementById('apply-btn');
        if (applyBtn) applyBtn.href = meta.apply_path;
      }

      // Render sidebar
      renderOtherVacancies(jobs, slug);

    }).catch(function (err) {
      console.error('Job renderer:', err);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadJob);
  } else {
    loadJob();
  }
}());
