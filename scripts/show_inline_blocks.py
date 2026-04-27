"""Show full inline script blocks containing photo-gallery or wp-includes/js/dist."""
import os, re

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
NEEDLES = ['photo-gallery/js', 'wp-includes/js/dist']

checked = set()
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
        for m in re.finditer(r'<script(?:\s[^>]*)?>[\s\S]*?</script>', html, re.IGNORECASE):
            block = m.group()
            if 'src=' in block.split('>')[0]:
                continue
            for n in NEEDLES:
                if n in block:
                    sig = block[:80]
                    if sig not in checked:
                        checked.add(sig)
                        print('=== ' + os.path.relpath(fpath, ROOT).replace('\\', '/'))
                        print(block[:500])
                        print()
                    break
        if checked:
            break  # just need one example per needle
