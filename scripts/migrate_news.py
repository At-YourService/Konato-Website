"""
Moves all news article folders into news/ and fixes all relative path references.
"""
import os
import re
import shutil

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
NEWS_DIR = os.path.join(ROOT, 'news')

NEWS_SLUGS = [
    '23-3-2021-ehb-gastcollege',
    'crisp-dm',
    'de-warmste-week-van-konato',
    'devops-een-paar-belangrijke-lessen',
    'digital-transformation-is-bimodel-it-the-answer',
    'dna-van-het-ideale-project',
    'domain-driven-design',
    'end-of-summer-reflections',
    'erasmushogeschool-bxl',
    'flexibiliteit-als-sleutel-tot-succes',
    'intelligent-automation-and-process-management',
    'konato-knowledge-sharing',
    'konato-team-weekend-2021',
    'konato-vip-summer-event',
    'lipdub-team-weekend-konato',
    'mei-2022-bowling-team-event',
    'processanalyse',
    'safe-training',
    'summer-event-padel-and-barbecue',
    'technovate-2024-ai-beyond-the-buzz',
    'testimonial-bart-govaerts',
    'the-great-gatsby-team-weekend',
]

def read_file(path):
    with open(path, 'r', encoding='utf-8') as f:
        return f.read()

def write_file(path, content):
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)

# Step 1: Move article folders into news/
print("=== Moving folders ===")
for slug in NEWS_SLUGS:
    src = os.path.join(ROOT, slug)
    dst = os.path.join(NEWS_DIR, slug)
    if os.path.exists(src):
        shutil.move(src, dst)
        print(f"  Moved: {slug}")
    elif os.path.exists(dst):
        print(f"  Already in news/: {slug}")
    else:
        print(f"  WARNING: Not found: {slug}")

# Step 2: Update each article's index.html
# Strategy:
#   - Replace all "../ with "../../  (depth goes from 1 to 2 levels)
#   - Then restore cross-article links: "../../{slug}/ back to "../{slug}/
#     because from /news/article/, ../other-article/ correctly resolves to /news/other-article/
print("\n=== Updating article HTML files ===")
for slug in NEWS_SLUGS:
    html_path = os.path.join(NEWS_DIR, slug, 'index.html')
    if not os.path.exists(html_path):
        print(f"  WARNING: Not found: {html_path}")
        continue

    content = read_file(html_path)

    # Replace all "../ → "../../ and '../ → '../../
    content = content.replace('"../', '"../../')
    content = content.replace("'../", "'../../")

    # Restore cross-article links (they work correctly at 1 level from news/)
    for news_slug in NEWS_SLUGS:
        content = content.replace(f'"../../{news_slug}/', f'"../{news_slug}/')
        content = content.replace(f"'../../{news_slug}/", f"'../{news_slug}/")

    write_file(html_path, content)
    print(f"  Updated: {slug}/index.html")

# Step 3: Update news/index.html article links
# Currently: href="../article-slug/" (from news/, ../ = root)
# After move: href="article-slug/" (article is now inside news/)
print("\n=== Updating news/index.html ===")
news_index = os.path.join(NEWS_DIR, 'index.html')
content = read_file(news_index)
for slug in NEWS_SLUGS:
    content = content.replace(f'"../{slug}/', f'"{slug}/')
    content = content.replace(f"'../{slug}/", f"'{slug}/")
write_file(news_index, content)
print("  Done.")

# Step 4: Update news/page/N/index.html article links
# Currently: href="../../../article-slug/" (from news/page/N/, ../../.. = root)
# After move: href="../article-slug/" (from news/page/N/, ../ = news/)
print("\n=== Updating news/page/*/index.html ===")
for page_num in [2, 3]:
    page_index = os.path.join(NEWS_DIR, 'page', str(page_num), 'index.html')
    if not os.path.exists(page_index):
        print(f"  Not found: news/page/{page_num}/index.html")
        continue
    content = read_file(page_index)
    for slug in NEWS_SLUGS:
        content = content.replace(f'"../../../{slug}/', f'"../{slug}/')
        content = content.replace(f"'../../../{slug}/", f"'../{slug}/")
    write_file(page_index, content)
    print(f"  Updated: news/page/{page_num}/index.html")

# Step 5: Update sitemap.xml
# Change /slug/ → /news/slug/ for all news articles
print("\n=== Updating sitemap.xml ===")
sitemap_path = os.path.join(ROOT, 'sitemap.xml')
content = read_file(sitemap_path)
for slug in NEWS_SLUGS:
    content = content.replace(
        f'https://www.konato.be/{slug}/',
        f'https://www.konato.be/news/{slug}/'
    )
write_file(sitemap_path, content)
print("  Done.")

# Step 6: Update _redirects — add redirects from old paths to new paths
print("\n=== Updating _redirects ===")
redirects_path = os.path.join(ROOT, '_redirects')
content = read_file(redirects_path)

# Add redirect rules for each article (old root path → new news/ path)
redirect_lines = []
for slug in NEWS_SLUGS:
    redirect_lines.append(f'/{slug}/ /news/{slug}/ 301')
    redirect_lines.append(f'/{slug} /news/{slug}/ 301')

# Append before the last line (keep the catch-all redirect at the end)
lines = content.rstrip('\n').split('\n')
new_redirects_block = '\n# News article redirects (moved to /news/)\n' + '\n'.join(redirect_lines)
content = '\n'.join(lines) + '\n' + new_redirects_block + '\n'
write_file(redirects_path, content)
print("  Done.")

print("\n=== Migration complete ===")
