"""
Fixes unquoted url(../  CSS paths in moved news article HTML files.
The previous migration only handled quoted "../ and '../ but missed url(../ .
"""
import os

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

fixed = 0
for slug in NEWS_SLUGS:
    html_path = os.path.join(NEWS_DIR, slug, 'index.html')
    if not os.path.exists(html_path):
        continue

    with open(html_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Fix unquoted url(../ → url(../../
    updated = content.replace('url(../', 'url(../../')

    # Restore cross-article links inside url() if any (unlikely but safe)
    for news_slug in NEWS_SLUGS:
        updated = updated.replace(f'url(../../{news_slug}/', f'url(../{news_slug}/')

    if updated != content:
        with open(html_path, 'w', encoding='utf-8') as f:
            f.write(updated)
        fixed += 1
        print(f"  Fixed: {slug}/index.html")

print(f"\nFixed {fixed} files.")
