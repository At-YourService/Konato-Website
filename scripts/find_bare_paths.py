"""Find relative paths that are missing the depth prefix (e.g. wp-content/... instead of ../../wp-content/...)."""
import os, re, sys

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
target = sys.argv[1] if len(sys.argv) > 1 else None

ATTR_PAT = re.compile(r'(?:href|src|action|content)=["\']([^"\']+)["\']')
CSS_URL_PAT = re.compile(r'url\(["\']?([^"\')\s]+)["\']?\)')

SKIP_PREFIXES = ('http', 'https', '//', '#', 'mailto:', 'tel:', 'data:', '../', './', '/')

def check_file(fpath):
    with open(fpath, 'r', encoding='utf-8') as f:
        html = f.read()
    rel = os.path.relpath(fpath, ROOT).replace('\\', '/')
    for m in ATTR_PAT.finditer(html):
        v = m.group(1)
        if not any(v.startswith(p) for p in SKIP_PREFIXES) and v.strip():
            print(f'{rel}: attr {v[:80]}')
    for m in CSS_URL_PAT.finditer(html):
        v = m.group(1)
        if not any(v.startswith(p) for p in SKIP_PREFIXES) and v.strip():
            print(f'{rel}: url() {v[:80]}')

if target:
    check_file(os.path.join(ROOT, target))
else:
    for dirpath, dirnames, filenames in os.walk(ROOT):
        dirnames[:] = [d for d in dirnames if d not in ('node_modules', '.git', 'scripts')]
        if 'index.html' in filenames:
            check_file(os.path.join(dirpath, 'index.html'))
