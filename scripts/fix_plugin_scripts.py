"""Remove wp-content/plugins script tags that have no local files."""
import os, re

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))

PLUGIN_SCRIPT_RE = re.compile(
    r'<script[^>]+src="[^"]*wp-content/plugins/[^"]*\.js[^"]*"[^>]*>\s*</script>\s*\n?',
    re.IGNORECASE
)

fixed = 0
for dirpath, dirnames, filenames in os.walk(ROOT):
    dirnames[:] = [d for d in dirnames if d not in ('node_modules', '.git', 'scripts')]
    for fname in filenames:
        if fname != 'index.html':
            continue
        fpath = os.path.join(dirpath, fname)
        with open(fpath, 'r', encoding='utf-8') as f:
            html = f.read()

        if 'wp-content/plugins/' not in html:
            continue

        new_html = PLUGIN_SCRIPT_RE.sub('', html)

        if new_html != html:
            with open(fpath, 'w', encoding='utf-8') as f:
                f.write(new_html)
            rel = os.path.relpath(fpath, ROOT).replace('\\', '/')
            print(f'  Fixed: {rel}')
            fixed += 1

print(f'\nDone — {fixed} files updated.')
