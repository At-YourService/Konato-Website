"""Remove <video> elements (and their wrapping containers) from news pages."""
import os, re

NEWS_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'news'))

# Match a full <video ...>...</video> block including any whitespace around it
VIDEO_BLOCK_RE = re.compile(r'\s*<video[\s\S]*?</video>\s*', re.IGNORECASE)

for dirpath, _, filenames in os.walk(NEWS_DIR):
    if 'index.html' not in filenames:
        continue
    fpath = os.path.join(dirpath, 'index.html')
    with open(fpath, 'r', encoding='utf-8') as f:
        html = f.read()
    if '<video' not in html.lower():
        continue
    new_html = VIDEO_BLOCK_RE.sub('\n', html)
    with open(fpath, 'wb') as f:
        f.write(new_html.encode('utf-8'))
    rel = os.path.relpath(fpath, os.path.join(NEWS_DIR, '..')).replace('\\', '/')
    print(f'  Fixed: {rel}')

print('Done.')
