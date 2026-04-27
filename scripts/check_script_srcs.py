import os, re

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
NEEDLE = re.compile(r'<script[^>]+src=["\'][^"\']*(?:photo-gallery/js|wp-includes/js/dist|contact-form-7)[^"\']*["\']', re.IGNORECASE)

for dirpath, dirnames, filenames in os.walk(ROOT):
    dirnames[:] = [d for d in dirnames if d not in ('node_modules', '.git', 'scripts')]
    for fname in filenames:
        if not fname.endswith('.html'):
            continue
        fpath = os.path.join(dirpath, fname)
        try:
            with open(fpath, 'r', encoding='utf-8') as f:
                html = f.read()
        except Exception:
            continue
        for m in NEEDLE.finditer(html):
            rel = os.path.relpath(fpath, ROOT).replace('\\', '/')
            print(rel + ': ' + m.group()[:100])
