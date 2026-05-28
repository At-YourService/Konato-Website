# Konato Website — Developer Guide

Static site for [konato.be](https://www.konato.be), crawled from WordPress and deployed on GitHub Pages at `https://at-yourservice.github.io/Konato-Website/`.

---

## Project structure

```
/                        → site root (index.html = homepage)
/news/                   → news article index (paginated: page/2/, page/3/)
/news/<slug>/            → individual news article (full HTML pages)
/jobs/                   → permanent jobs index
/job/<slug>/             → permanent job detail (full HTML pages)
/freelance-jobs/         → freelance jobs index
/freelance-job/<slug>/   → freelance job detail (rendered from content.md at runtime)
/freelance-job/<slug>/apply/ → apply form for that freelance job
/scripts/                → build & server-side scripts
/wp-content/themes/Konato/ → theme assets (CSS, JS, images)
```

---

## Brand & style guidelines

### Colors

| Name       | Hex       | Usage                              |
|------------|-----------|------------------------------------|
| Blue       | `#00ADDA` | Primary accent, header bg, buttons, links, icons |
| Red        | `#FC575E`  | Accent blocks (`div.blok-red`)     |
| Green      | `#66CC99`  | Accent blocks (`div.blok-green`)   |
| Dark Blue  | `#112233`  | Accent blocks (`div.blok-darkblue`), footer background is `#242424` |
| Yellow     | `#FCFB5D`  | Accent blocks (`div.blok-yellow`)  |
| Dark Grey  | `#242424`  | Footer background                  |
| Light Grey | `#F5F5F5`  | Page background, card backgrounds  |

### Typography

- **Headings** (`h1`–`h6`): `Lato` (weights 300, 400, 700) — `font-weight: 100` on `h1` and `p`
- **Body / paragraphs**: `Open Sans` (weights 400, 600)
- **Font imports**: always include both Google Font links in `<head>`:
  ```html
  <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
  ```

### Layout

- Bootstrap grid (`col-md-*`, `col-sm-*`)
- Standard spacing utilities: `.pad-top` (75 px), `.pad-bottom` (75 px), `.smal-pad-top` (30 px), `.smal-pad-bot` (30 px)
- All pages use a **small blue header** (`header.small-header`, background `#00ADDA`) with `<h1 class="small-header-title">` for the page title

### Buttons

```html
<!-- Primary (blue) -->
<button class="info">Label</button>

<!-- Submit / CTA -->
<button class="submit-btn">Label</button>
```

The `.submit-btn` style: background `#00ADDA`, white text, `border-radius: 2px`, hover `#008aae`.

### Icons

FontAwesome 5 (`fa fa-*`) and Simple Line Icons are loaded globally.

---

## Page template pattern

Every page follows the same shell:

```html
<!DOCTYPE html><html><head>
  <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
  <meta charset="UTF-8">
  <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
  <title>Page Title | Konato</title>
  <!-- stylesheet links (depth-relative paths) -->
  <link rel="stylesheet" href="../wp-content/themes/Konato/css/bootstrap.min.css">
  <link rel="stylesheet" href="../wp-content/themes/Konato/css/style-konato.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.4.1/css/simple-line-icons.css">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css">
</head>
<body>
  <header class="small-header"><!-- nav --></header>
  <section><!-- content --></section>
  <footer><!-- footer --></footer>
  <div class="link-footer"><!-- legal links --></div>
  <!-- JS -->
  <script src="../wp-content/themes/Konato/js/jquery.min.js"></script>
  <script src="../wp-content/themes/Konato/js/bootstrap.min.js"></script>
  <script src="../wp-content/themes/Konato/js/konato_js.js"></script>
</body></html>
```

All asset paths are **depth-relative** (e.g. `../../wp-content/...` from a two-level-deep page). Adjust `../` depth to match the page's folder depth from the root.

---

## Contact form

- HTML lives in [`contact/index.html`](contact/index.html)
- Client-side logic: [`wp-content/themes/Konato/js/contact-form.js`](wp-content/themes/Konato/js/contact-form.js) — POSTs `application/x-www-form-urlencoded` to `https://www.konato.be/send-mail.php`
- Server-side handler: [`scripts/sendmail.php`](scripts/sendmail.php) — creates a DevRev ticket. Requires a `.env` file one level above the webroot with `DEVREV_API_KEY` and `DEVREV_PART_ID`
- Fields: `name`, `email`, `message`, `privacy` (checkbox), `website` (honeypot — hidden, must stay empty)

---

## Adding a news article

News articles are **static HTML pages**. There is no CMS or markdown pipeline for news.

1. **Create the folder and file**
   ```
   news/<slug>/index.html
   ```
   Use a URL-friendly slug, e.g. `news/my-article-title/index.html`.

2. **Copy the page shell** from an existing article (e.g. [`news/technovate-2024-ai-beyond-the-buzz/index.html`](news/technovate-2024-ai-beyond-the-buzz/index.html)) and update:
   - `<title>Article Title | Konato</title>`
   - All `og:*` and `twitter:*` meta tags
   - `<link rel="canonical" href="../<slug>/">`
   - `property="article:published_time"` and `article:modified_time`
   - `name="author"` meta tag
   - `name="description"` meta tag

3. **Content area** — the article body goes inside `<section class="content-news">` (or the equivalent single-post section). Use the same HTML structure as existing articles.

4. **Add the article card to the news index** in [`news/index.html`](news/index.html) (and/or the relevant pagination page `news/page/2/index.html`, `news/page/3/index.html`). A card looks like:

   ```html
   <div class="col-md-4 col-sm-6 col-sm-12 smal-pad-top">
     <div class="news-img">
       <img width="600" height="335" src="../wp-content/uploads/<year>/<month>/image.jpg"
            class="card-img-top wp-post-image" alt="Description">
     </div>
     <div class="card-body grey">
       <h3 class="blog-title">Article Title</h3>
       <div class="ellipsis">
         <p>Short excerpt text...</p>
       </div>
       <hr>
       <div class="d-flex justify-content-between align-items-center pad-b-10">
         <small class="text-muted overal-pad">
           <i class="far fa-clock" style="margin-right: 5px"></i>DD Month YYYY
           <a href="<slug>/">
             <p class="pull-right" style="font-size:12px; margin: 2px 10px 0 0">Read more</p>
           </a>
         </small>
       </div>
     </div>
   </div>
   ```

5. News is paginated at 9 articles per page. When the current page reaches 9 cards, add a new `news/page/<n>/index.html` (copy from an existing pagination page) and update pagination links. Note: page 1 = `/news/` — never create `news/page/1/`.

---

## Adding a freelance job

Freelance jobs use a **markdown + front matter** system rendered at runtime by `freelance-job/job-renderer.js`.

### 1. Create the content file

```
freelance-job/<slug>/content.md
```

Front matter (required fields):

```yaml
---
title: "Job Title"
location: "City"
apply_path: "apply/index.html"
---
```

Body: standard Markdown. Use `####` headings for sections. Common sections:

- Intro paragraph(s)
- `#### Details` — bullet list of practical info (start date, location, duration, etc.)
- `#### Challenges` — bullet list of responsibilities
- `#### Wie ben je en wat kan je` / `#### Wishlist` — bullet list of requirements
- `#### Do you have a question` — contact person with tel and email links

### 2. Create the HTML shell

```
freelance-job/<slug>/index.html
```

Copy from [`freelance-job/sr-functioneel-analist/index.html`](freelance-job/sr-functioneel-analist/index.html) and update:
- `<title>`, all `og:*` / `twitter:*` meta, `canonical` link
- The `job-renderer.js` script at the bottom loads `content.md` automatically based on the URL slug — no other changes needed

### 3. Create the apply page

```
freelance-job/<slug>/apply/index.html
```

Copy from [`freelance-job/sr-functioneel-analist/apply/index.html`](freelance-job/sr-functioneel-analist/apply/index.html) and update the title and meta tags.

### 4. Rebuild the jobs index

After adding or removing a freelance job, run:

```bash
npm run build-jobs-index
```

This regenerates [`freelance-job/jobs-index.json`](freelance-job/jobs-index.json), which powers the "Other vacancies" sidebar on every job detail page.

### 5. Add a card to the freelance jobs index

Add a card to [`freelance-jobs/index.html`](freelance-jobs/index.html) following the same card pattern as the existing jobs there.

---

## Adding a permanent job

Permanent jobs are **full static HTML pages** (no markdown pipeline).

1. Create `job/<slug>/index.html` — copy from an existing job (e.g. [`job/atlassian-professional/index.html`](job/atlassian-professional/index.html)) and replace the content inside `<section class="content-jobs">`.

2. Content area uses a two-column layout:
   - Left column (`col-md-6`): feature image `<img class="img-responsive wp-post-image">`
   - Right column (`col-md-6 single-job`): job description with `<h2>`, `<h3>`, `<ul>` lists, and an apply CTA at the bottom

3. Add a card to [`jobs/index.html`](jobs/index.html) following the same card pattern as the existing permanent job cards.

---

## npm scripts

| Command | Description |
|---|---|
| `npm run crawl` | Re-crawl konato.be and regenerate all static HTML |
| `npm run crawl:dry` | Discover URLs only, no file writes |
| `npm run inject-cookie-banner` | Inject the cookie consent banner into all HTML pages |
| `npm run make-paths-relative` | Convert absolute asset paths to relative paths |
| `npm run build-jobs-index` | Regenerate `freelance-job/jobs-index.json` from content.md files |
