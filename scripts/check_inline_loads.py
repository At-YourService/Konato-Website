"""Find inline script blocks that dynamically load the missing files."""
import os, re

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
NEEDLES = ['photo-gallery/js', 'wp-includes/js/dist', 'wpcf7-redirect']

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
        # Find inline script blocks containing those strings
        for m in re.finditer(r'<script(?:\s[^>]*)?>[\s\S]*?</script>', html, re.IGNORECASE):
            block = m.group()
            if 'src=' in block.split('>')[0]:
                continue  # skip external scripts (already handled)
            for n in NEEDLES:
                if n in block:
                    rel = os.path.relpath(fpath, ROOT).replace('\\', '/')
                    # Print just the relevant line(s) from the block
                    for line in block.splitlines():
                        if n in line:
                            print(rel + ': ' + line.strip()[:120])
                    break
