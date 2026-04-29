/* Populates the #other-vacancies sidebar on apply pages (freelance-job/{slug}/apply/).
   Fetches ../../jobs-index.json (= freelance-job/jobs-index.json),
   excludes the current job, and renders small-job-block links. */
(function () {
  'use strict';

  function currentSlug() {
    // URL is like /freelance-job/sr-functioneel-analist/apply/
    // or           /Konato-Website/freelance-job/sr-functioneel-analist/apply/
    var parts = window.location.pathname.split('/').filter(Boolean);
    var idx = parts.indexOf('freelance-job');
    if (idx !== -1 && parts[idx + 1]) return parts[idx + 1];
    // fallback: third-from-last non-empty segment
    return parts.length >= 3 ? parts[parts.length - 3] : '';
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function render() {
    var slug      = currentSlug();
    var container = document.getElementById('other-vacancies');
    if (!container) return;

    // apply/ is 2 levels below freelance-job/
    fetch('../../jobs-index.json')
      .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
      .then(function (jobs) {
        var others = jobs.filter(function (j) { return j.slug !== slug; });

        if (others.length === 0) {
          container.innerHTML = '<p style="color:#888;font-size:13px">No other vacancies at the moment.</p>';
          return;
        }

        container.innerHTML = others.map(function (j) {
          // from apply/ go up two levels to reach freelance-job/, then into slug
          return (
            '<div class="row">' +
              '<div class="col-sm-12">' +
                '<a class="small-job-block" href="../../' + encodeURIComponent(j.slug) + '/index.html">' +
                  '<h4>' + escHtml(j.title) + '</h4>' +
                  (j.location ? '<p>' + escHtml(j.location) + '</p>' : '') +
                '</a>' +
              '</div>' +
            '</div>'
          );
        }).join('');
      })
      .catch(function (err) {
        console.error('apply-sidebar:', err);
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', render);
  } else {
    render();
  }
}());
