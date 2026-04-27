import re, sys
path = sys.argv[1] if len(sys.argv) > 1 else 'index.html'
with open(path, 'r', encoding='utf-8') as f:
    html = f.read()
pat = re.compile(r'(?:href|src)=(["\'])([^"\']{0,80})\1')
samples = pat.findall(html)[:10]
for q, v in samples:
    print(repr(v))
