"""
Converts freelance-job detail pages into a markdown-driven system:
 - Creates content.md (front matter + markdown body) for each job
 - Rewrites index.html to a template that loads & renders content.md via JS
 - Creates freelance-job/job-renderer.js
"""
import os
import re
from html.parser import HTMLParser

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
JOBS_DIR = os.path.join(ROOT, 'freelance-job')

JOB_SLUGS = [
    'sr-functioneel-analist',
    'analyst',
    '2-talig-business-analyst',
    'application-consultant',
    'business-development-manager-payrol',
    'freelance-test-automation-consultant',
]


# ---------------------------------------------------------------------------
# Simple HTML → Markdown converter
# ---------------------------------------------------------------------------
class HtmlToMd(HTMLParser):
    def __init__(self):
        super().__init__()
        self.out = []
        self._href = None
        self._in_li = False

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag in ('h3',):
            self.out.append('\n### ')
        elif tag == 'h4':
            self.out.append('\n#### ')
        elif tag == 'p':
            self.out.append('\n')
        elif tag == 'ul':
            self.out.append('\n')
        elif tag == 'li':
            self.out.append('\n- ')
            self._in_li = True
        elif tag == 'br':
            self.out.append('  \n')
        elif tag == 'a':
            self._href = a.get('href', '')
            self.out.append('[')
        elif tag in ('strong', 'b'):
            self.out.append('**')
        elif tag in ('em', 'i'):
            self.out.append('*')

    def handle_endtag(self, tag):
        if tag in ('h3', 'h4'):
            self.out.append('\n')
        elif tag == 'p':
            self.out.append('\n')
        elif tag == 'li':
            self._in_li = False
        elif tag == 'ul':
            self.out.append('\n')
        elif tag == 'a':
            self.out.append(f']({self._href})')
            self._href = None
        elif tag in ('strong', 'b'):
            self.out.append('**')
        elif tag in ('em', 'i'):
            self.out.append('*')

    def handle_data(self, data):
        self.out.append(data)

    def handle_entityref(self, name):
        self.out.append({'nbsp': ' ', 'amp': '&', 'lt': '<', 'gt': '>',
                         'ndash': '–', 'mdash': '—'}.get(name, f'&{name};'))

    def handle_charref(self, name):
        n = int(name[1:], 16) if name.startswith('x') else int(name)
        self.out.append(chr(n))

    def result(self):
        md = ''.join(self.out)
        # Collapse 3+ consecutive newlines to 2
        md = re.sub(r'\n{3,}', '\n\n', md)
        return md.strip()


def html_to_md(html_snippet):
    p = HtmlToMd()
    p.feed(html_snippet)
    return p.result()


# ---------------------------------------------------------------------------
# Extraction helpers
# ---------------------------------------------------------------------------
def read(path):
    with open(path, 'r', encoding='utf-8') as f:
        return f.read()

def write(path, content):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)


def extract_inner(html, open_tag_pattern):
    """Return the content between an opening tag match and its matching closing tag."""
    m = re.search(open_tag_pattern, html, re.DOTALL)
    if not m:
        return None, None, None
    tag_name = re.match(r'<(\w+)', m.group()).group(1)
    start = m.end()
    depth = 1
    pos = start
    while pos < len(html) and depth > 0:
        open_m  = re.search(fr'<{tag_name}[\s>]', html[pos:])
        close_m = re.search(fr'</{tag_name}>', html[pos:])
        if close_m and (not open_m or close_m.start() < open_m.start()):
            pos += close_m.end()
            depth -= 1
        elif open_m:
            pos += open_m.end()
            depth += 1
        else:
            break
    close_end = pos
    return m.start(), close_end, html[start:pos - len(f'</{tag_name}>')]


def extract_job_content(html):
    """Return (title, apply_url, body_html) from the single-job section."""
    # Title
    title_m = re.search(r'<h1[^>]*class="content-title"[^>]*>(.*?)</h1>', html, re.DOTALL)
    title = html_to_md(title_m.group(1)).strip() if title_m else ''

    # Apply URL
    apply_m = re.search(r'href="([^"]*apply[^"]*)"[^>]*>\s*<button[^>]*>\s*Apply here', html)
    apply_path = apply_m.group(1) if apply_m else 'apply/'
    # Make it relative to the page (strip ../../freelance-job/{slug}/)
    apply_path = re.sub(r'^.*?apply', 'apply', apply_path)

    # Body: everything inside the single-job div's col-sm-12, after the h2
    _, _, single_job_inner = extract_inner(html, r'<div[^>]*class="row single-job"[^>]*>')
    if not single_job_inner:
        return title, apply_path, ''

    _, _, col_inner = extract_inner(single_job_inner, r'<div[^>]*class="col-sm-12"[^>]*>')
    if not col_inner:
        return title, apply_path, ''

    # Remove the h1 and h2 from the top
    body_html = re.sub(r'^.*?<h2[^>]*>.*?</h2>', '', col_inner, count=1, flags=re.DOTALL)
    # Remove trailing <br> tags
    body_html = re.sub(r'(\s*<br\s*/?>)+\s*$', '', body_html, flags=re.DOTALL)

    return title, apply_path, body_html.strip()


def get_other_vacancies(html):
    """Return list of (title, location, href) from the Other vacancies sidebar."""
    vacancies = []
    for m in re.finditer(
        r'<a[^>]*class="small-job-block"[^>]*href="([^"]*)"[^>]*>\s*<h4>(.*?)</h4>\s*<p>(.*?)</p>',
        html, re.DOTALL
    ):
        href, title, loc = m.group(1), m.group(2), m.group(3)
        # Normalise href to root-relative
        href = re.sub(r'^../../', '/', href).replace('/index.html', '/')
        vacancies.append({'title': html_to_md(title).strip(),
                           'location': html_to_md(loc).strip(),
                           'href': href})
    return vacancies


# ---------------------------------------------------------------------------
# Build content.md
# ---------------------------------------------------------------------------
def build_content_md(title, apply_path, body_html):
    body_md = html_to_md(body_html)
    fm = f'---\ntitle: "{title}"\napply_path: "{apply_path}"\n---\n\n'
    return fm + body_md + '\n'


# ---------------------------------------------------------------------------
# Rewrite index.html → template
# ---------------------------------------------------------------------------
TEMPLATE_CONTENT_BLOCK = '''\
<div class="row single-job">
<div class="col-sm-12">
<h1 class="content-title" id="job-title"></h1>
<div id="job-body" class="job-md-content"></div>
</div>
</div>'''

JOB_MD_CSS = '''\
<style>
.job-md-content h4 { font-size: 1.1em; margin-top: 1.2em; margin-bottom: .4em; }
.job-md-content ul { padding-left: 1.2em; }
.job-md-content li { padding: 0; margin: 0; }
.job-md-content p  { margin-bottom: .6em; }
</style>'''

SCRIPTS = '''\
<script src="../../wp-content/themes/Konato/js/marked.min.js"></script>
<script src="../job-renderer.js"></script>'''


def rewrite_template(html, slug):
    # 1. Replace the single-job div with the template placeholder
    m_start, m_end, _ = extract_inner(html, r'<div[^>]*class="row single-job"[^>]*>')
    if m_start is not None:
        html = html[:m_start] + TEMPLATE_CONTENT_BLOCK + html[m_end:]

    # 2. Set id="apply-btn" on the Apply here button anchor
    html = re.sub(
        r'(<a\s[^>]*href="[^"]*apply[^"]*"[^>]*>)(\s*<button[^>]*>\s*Apply here)',
        r'<a href="apply/" id="apply-btn">\2',
        html
    )

    # 3. Inject CSS for markdown rendering (before </head>)
    html = html.replace('</head>', JOB_MD_CSS + '\n</head>', 1)

    # 4. Inject scripts before </body>
    html = html.replace('</body>', SCRIPTS + '\n</body>', 1)

    return html


# ---------------------------------------------------------------------------
# Create job-renderer.js
# ---------------------------------------------------------------------------
JOB_RENDERER_JS = r"""/* Loads content.md, parses front matter, renders markdown into the job template. */
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
"""


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------
def main():
    # Write shared renderer one level up from job slugs
    renderer_path = os.path.join(JOBS_DIR, 'job-renderer.js')
    write(renderer_path, JOB_RENDERER_JS)
    print(f'  Written: freelance-job/job-renderer.js')

    for slug in JOB_SLUGS:
        job_dir  = os.path.join(JOBS_DIR, slug)
        html_path = os.path.join(job_dir, 'index.html')

        if not os.path.exists(html_path):
            print(f'  SKIP (not found): {slug}')
            continue

        html = read(html_path)

        title, apply_path, body_html = extract_job_content(html)
        md = build_content_md(title, apply_path, body_html)
        write(os.path.join(job_dir, 'content.md'), md)
        print(f'  Written: {slug}/content.md  (title="{title}")')

        template_html = rewrite_template(html, slug)
        write(html_path, template_html)
        print(f'  Rewritten: {slug}/index.html')

    print('\nDone.')


main()
