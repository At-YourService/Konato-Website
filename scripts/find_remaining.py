import os

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
PATTERNS = ['photo-gallery/js', 'wp-includes/js/dist', 'contact-form-7/includes', 'wp-includes/js/jquery']

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
        for p in PATTERNS:
            if p in html:
                rel = os.path.relpath(fpath, ROOT).replace('\\', '/')
                print(rel + ': ' + p)
                break
