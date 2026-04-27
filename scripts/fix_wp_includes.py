"""Remove wp-includes/js script tags from all HTML files.
For freelance-job pages that load konato_js.js but not theme jQuery, inject theme jQuery.
"""
import os, re

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))

WP_INCLUDES_JS_RE = re.compile(
    r'<script[^>]+src="[^"]*wp-includes/js/[^"]*\.js[^"]*"[^>]*>\s*</script>\s*\n?',
    re.IGNORECASE
)

def depth_prefix(html_path):
    rel = os.path.relpath(html_path, ROOT).replace('\\', '/')
    depth = rel.count('/')
    return '../../' * depth if depth > 0 else ''

fixed = 0
for dirpath, dirnames, filenames in os.walk(ROOT):
    dirnames[:] = [d for d in dirnames if d not in ('node_modules', '.git', 'scripts')]
    for fname in filenames:
        if fname != 'index.html':
            continue
        fpath = os.path.join(dirpath, fname)
        with open(fpath, 'r', encoding='utf-8') as f:
            html = f.read()

        if 'wp-includes/js/' not in html:
            continue

        # Remove all wp-includes/js script tags
        new_html = WP_INCLUDES_JS_RE.sub('', html)

        # If konato_js.js is present but theme jQuery is not, inject it
        prefix = depth_prefix(fpath)
        theme_jquery = f'{prefix}wp-content/themes/Konato/js/jquery.min.js'
        bootstrap_src = f'{prefix}wp-content/themes/Konato/js/bootstrap.min.js'

        has_konato_js = 'konato_js.js' in new_html
        has_theme_jquery = 'themes/Konato/js/jquery.min.js' in new_html

        if has_konato_js and not has_theme_jquery:
            inject = f'<script src="{theme_jquery}"></script>\n'
            if bootstrap_src in new_html:
                new_html = new_html.replace(
                    f'<script src="{bootstrap_src}">',
                    inject + f'<script src="{bootstrap_src}">'
                )
            else:
                # Inject before konato_js.js
                konato_tag = re.search(r'<script[^>]+konato_js\.js[^>]*>', new_html)
                if konato_tag:
                    new_html = new_html[:konato_tag.start()] + inject + new_html[konato_tag.start():]

        if new_html != html:
            with open(fpath, 'w', encoding='utf-8') as f:
                f.write(new_html)
            rel = os.path.relpath(fpath, ROOT).replace('\\', '/')
            print(f'  Fixed: {rel}')
            fixed += 1

print(f'\nDone — {fixed} files updated.')
