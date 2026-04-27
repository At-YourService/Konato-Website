/* Loads content.md, parses front matter, renders markdown into the job template. */
(function () {
  'use strict';

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

  function loadJob() {
    fetch('./content.md')
      .then(function (res) {
        if (!res.ok) throw new Error('content.md not found');
        return res.text();
      })
      .then(function (text) {
        var parsed = parseFrontMatter(text);
        var meta   = parsed.meta;
        var body   = parsed.body;

        if (meta.title) {
          document.title = meta.title + ' – | Konato';
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
      })
      .catch(function (err) {
        console.error('Job renderer:', err);
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadJob);
  } else {
    loadJob();
  }
}());
