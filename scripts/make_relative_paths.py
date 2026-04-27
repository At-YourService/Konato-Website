"""
Convert all root-relative asset/link paths to truly relative paths.

Root-relative paths like /wp-content/... break when the site is served from
a subpath (e.g. GitHub Pages at /Konato-Website/).  Replacing them with
depth-relative paths (../../wp-content/...) makes the site work at any base URL.
"""
import os, re

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))

# Attributes whose values we rewrite
ATTR_PATTERNS = [
    # href="/..."  src="/..."  action="/..."
    (re.compile(r'((?:href|src|action)\s*=\s*")/(?!/)'), r'\g<1>__PREFIX__'),
    (re.compile(r"((?:href|src|action)\s*=\s*')/(?!/)"), r"\g<1>__PREFIX__"),
    # content="/..." (og:image etc.)
    (re.compile(r'(content\s*=\s*")/(?!/)'), r'\g<1>__PREFIX__'),
    (re.compile(r"(content\s*=\s*')/(?!/)"), r"\g<1>__PREFIX__"),
]

# CSS url(/...)
CSS_URL_PAT = re.compile(r"(url\(['\"]?)/(?!/)")

def prefix_for(html_path):
    """Return the relative prefix needed to reach the site root from this file."""
    rel = os.path.relpath(html_path, ROOT)
    depth = rel.count(os.sep)   # number of directory levels
    return ('../' * depth) if depth > 0 else './'

def convert(html, pfx):
    # HTML attributes
    for pat, repl in ATTR_PATTERNS:
        html = pat.sub(repl.replace('__PREFIX__', pfx), html)
    # CSS url(/ ...)
    html = CSS_URL_PAT.sub(r'\1' + pfx, html)
    return html

fixed = 0
for dirpath, dirnames, filenames in os.walk(ROOT):
    dirnames[:] = [d for d in dirnames if d not in ('node_modules', '.git', 'scripts')]
    if 'index.html' not in filenames:
        continue
    fpath = os.path.join(dirpath, 'index.html')
    try:
        with open(fpath, 'r', encoding='utf-8') as f:
            html = f.read()
    except Exception as e:
        print(f'  SKIP {fpath}: {e}')
        continue

    pfx = prefix_for(fpath)
    new_html = convert(html, pfx)

    if new_html != html:
        try:
            with open(fpath, 'w', encoding='utf-8') as f:
                f.write(new_html)
        except OSError:
            with open(fpath, 'wb') as f:
                f.write(new_html.encode('utf-8'))
        rel = os.path.relpath(fpath, ROOT).replace('\\', '/')
        print(f'  {pfx!r:12s}  {rel}')
        fixed += 1

print(f'\nDone — {fixed} files updated.')
