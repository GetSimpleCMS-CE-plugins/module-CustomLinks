# Custom Links

A [Dashboard](https://github.com/GetSimpleCMS-CE/GetSimpleCMS-CE) module for GetSimpleCMS-CE that lets you add your own action buttons, links, and dividers to the admin dashboard — no code required.

Built by [risingisland](https://ko-fi.com/ericmontgomery).

<img width="950" alt="image" src="https://github.com/user-attachments/assets/1a0a4159-4e86-4c9a-bd08-730fd4d9b450" />

## Features

- Add unlimited custom link buttons with your own icon, title, link, color, and target
- Paste any SVG icon code directly (or grab one from [Iconify](https://icon-sets.iconify.design/))
- Six button colors: Dark, Blue, Orange, Green, Red, Gray
- Choose whether each button opens in the same tab or a new tab
- Add dividers to visually group related buttons
- Reorder buttons and dividers with move up/down controls
- Edit everything inline from the dashboard tile — no separate settings page, no page reload required to open/close the editor
- Self-contained: stores its own data, makes no changes to Dashboard core files

## Requirements

- GetSimpleCMS-CE with the [Dashboard](https://github.com/GetSimpleCMS-CE/GetSimpleCMS-CE/wiki) plugin installed and active

## Installation

1. Copy `customlinks.php` into `plugins/Dashboard/modules/`
2. Copy the `customlinks/` folder (containing `lang/en_US.php`, `lang/es_ES.php`, `lang/.htaccess`) into `plugins/Dashboard/modules/`

Your folder structure should look like this:

```
plugins/
└── Dashboard/
    └── modules/
        ├── customlinks.php
        └── customlinks/
            └── lang/
                ├── en_US.php
                ├── es_ES.php
                └── .htaccess
```

3. In GetSimple, go to **Dashboard → Layout** (gear icon on the Dashboard page) and enable the **Custom Links** module
4. Save the layout

## Usage

Once enabled, the Custom Links tile appears on your dashboard. Click the gear icon in the top-right corner of the tile to open the editor.

### Adding a link

Click **+ Add Link** and fill in:

| Field | Description |
|---|---|
| **Type** | Button color: Dark, Blue, Orange, Green, Red, or Gray |
| **Icon (SVG code)** | Paste raw SVG markup, e.g. `<svg>...</svg>`. Leave blank for a text-only button. The [Iconify](https://icon-sets.iconify.design/) link next to the label is a quick source for free icons — copy the SVG code and paste it in |
| **Title** | The label shown under the icon |
| **Link** | The destination URL — an external address (`https://...`) or an internal admin path (`load.php?id=...`) |
| **Target** | Whether the link opens in the same tab or a new tab |

### Adding a divider

Click **+ Add Divider** to insert a visual separator between groups of buttons. Dividers don't need any fields filled in — just position them where you want the break.

### Reordering

Use the ↑ / ↓ buttons on each row to move it up or down. Use × to remove a row.

### Saving

Click **Save** to write your changes and refresh the tile, or **Done** to close the editor without navigating away.

## How it works

- Data is stored as JSON at `data/other/dashboard/customlinks.json` — completely separate from Dashboard's own `layout.json`, so this module can be added or removed without touching anything else.
- Saving is a plain form POST back to the dashboard page. The module's own code runs at the top of its include, before any of its widget HTML is echoed, so it can validate and save the new list and immediately re-render with the fresh data — no AJAX, no redirect, no changes to `Dashboard.php`.
- CSRF protection uses a token derived from GetSimple's own `$SALT` and `$USR`, rather than a PHP session, since a session can't be reliably started this late in an already-rendering admin page.
- Link URLs are checked for `javascript:` / `data:` / `vbscript:` schemes and rejected. Icon input is discarded unless it contains `<svg`.
- Older saved links (added before the Target option existed) keep working automatically — they fall back to the previous behavior of opening external links in a new tab and internal links in the same tab.
