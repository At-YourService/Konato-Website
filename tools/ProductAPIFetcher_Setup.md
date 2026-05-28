# ProductAPIFetcher — Setup Guide (ASA Berekeningen template)

## What it does

A button in the **Berekening** sheet calls an API, parses the JSON response, and
writes each product as a fully-calculated row — matching the existing template
structure exactly. All formulas (EUR conversion, discounts, margins) are
generated automatically from the values in `# Rekensleutels`.

---

## Template structure (for reference)

| Col | Field | Source |
|-----|-------|--------|
| A | Omschrijving licentie | API → product name |
| B | Startbedrag (€) | Formula: `dollar_rate × (1+uplift%) × H` |
| C | Korting | Formula: tiered on Abano discount (J) |
| D | Prijs (€) | Formula: `B × (1−C)` |
| E | Geschatte IC-korting | Formula: based on J |
| F | IC-bedrag na korting (€) | Formula: `D × (1−E)` |
| G | Geschatte marge IC | Formula: `D−F` |
| H | Startbedrag ($) | **API → list_price** |
| I | Kost Abano ($) | **API → partner_price** |
| J | Korting Abano | Formula: `(H−I)/H` |
| K | Geschatte kost Abano (€) | Formula: `I × dollar_rate` |
| L | Geschatte marge Abano (€) | Formula: `F−K` (or `D−K` if no IC) |

Dollar rate and uplift % are read from `# Rekensleutels` cells C3 and C2.

---

## Setup

### 1. Import the module

Press **Alt+F11** → right-click your workbook in the Project panel →
**Import File** → select `ProductAPIFetcher.bas`.

### 2. Configure your API

At the top of the module update the two constants:

```vba
Private Const API_URL = "https://your-api-endpoint.com/products"
Private Const API_KEY = "your-api-key-here"
```

### 3. Add the button to the Berekening sheet

- **Developer tab → Insert → Button (Form Control)**
- Draw the button on the sheet
- In the "Assign Macro" dialog select **FetchProducts**
- Right-click → **Edit Text** → label it e.g. "Importeer producten"

> If the Developer tab is hidden: File → Options → Customize Ribbon → check **Developer**

### 4. Save as .xlsm

File → Save As → choose **Excel Macro-Enabled Workbook (*.xlsm)**.

---

## Expected API response

The module expects a **JSON array** of objects. It auto-detects common field
name variants (see table below).

```json
[
  {
    "product_name": "Atlassian Jira Software Cloud",
    "list_price": 2310.00,
    "partner_price": 1990.00
  },
  {
    "product_name": "Atlassian Confluence Cloud",
    "list_price": 1540.00,
    "partner_price": 1320.00
  }
]
```

### Supported field name aliases

| Data | Accepted key names |
|------|--------------------|
| Product name | `product_name`, `name`, `productName`, `title`, `description` |
| List price ($) | `list_price`, `listPrice`, `price`, `msrp` |
| Partner price ($) | `partner_price`, `partnerPrice`, `discount_price`, `cost` |

To add more aliases, edit the `ExtractStr` / `ExtractNum` calls in `ParseProductJSON`.

---

## Authentication header

Default: `X-API-Key: <key>`. To switch to Bearer token, swap these two lines
in the `CallAPI` function:

```vba
' .setRequestHeader "X-API-Key",     apiKey          ← comment out
.setRequestHeader "Authorization", "Bearer " & apiKey  ← uncomment
```

---

## Behaviour on re-run

When the sheet already contains product rows, the macro asks:
> "X existing product row(s) found. Replace them with Y product(s) from the API?"

Choosing **Yes** deletes the old rows and writes fresh ones.
Choosing **No** cancels without making any changes.

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| "HTTP 401" error | Wrong API key — check `API_KEY` constant |
| "HTTP 404" error | Wrong URL — check `API_URL` constant |
| Amounts show 0 | Field name mismatch — add your API's key names to `ExtractNum` aliases |
| Products show but no name | Add your API's name field to `ExtractStr` aliases |
| "Automation error" | Excel blocked the XMLHTTP object — check macro security settings |
| Macro security warning | File → Options → Trust Center → Macro Settings → Enable macros |
