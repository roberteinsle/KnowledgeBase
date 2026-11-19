# Pages

Pages are the actual content. Every page belongs to a space and can have sub-pages.

## Page tree

The left sidebar shows all pages of the current space as a tree. Pages with sub-pages have an arrow to expand or collapse them; your browser remembers what is open.

When you hover over a page, two buttons appear:

- **⋮** opens the page menu (see below),
- **+** creates a sub-page (role **Editor**).

Next to the heading *Pages*, **…** opens the space menu (import Markdown, export space, trash), **+** creates a new top-level page.

### Moving pages with the mouse

With the role **Editor** you can drag pages in the tree:

- onto the **upper or lower edge** of a page – the page is placed before or after it (a line shows where),
- onto the **middle** of a page – the page becomes its last sub-page (the target page is highlighted).

Sub-pages move along. A page cannot be dragged into its own sub-pages. On a phone or with the keyboard, use **⋮ → Move** instead.

### Page menu (⋮)

| Entry | Effect | Role |
|---|---|---|
| Copy link | copies the address of the page | Reader |
| Copy page content | copies the Markdown content of the page | Reader |
| Add to / Remove from favorites | the page appears under *Favorites* on the home page | Reader |
| This page as Markdown / HTML / with sub-pages as ZIP | export (see [Import and Export](Import-and-Export.md)) | Reader |
| Duplicate | copy with sub-pages, labels and attachments right below the original, title with "(copy)" | Editor |
| Move | below another page or to the top level | Editor |
| Copy to space | copy with sub-pages, labels and attachments into another space you can edit | Reader (source), Editor (target) |
| Version history | see below | Reader |
| Move to trash | see below | Space admin |

The same entries are available on the page itself under **More**. Copies start with a new version history.

## Favorites

Favorites are personal: every user has their own. They appear on the home page and in the space overview under **Favorites**; on the page itself a star next to the title shows that it is a favorite.

## Creating a page

- In a space: **New page** (main action at the top right, next to **Import Markdown**) or in the sidebar.
- Sub-page: **+** next to the page in the tree, or **More → New sub-page** on the page.

You need at least the role **Editor** in the space (see [Spaces, Groups and Permissions](Spaces-Groups-and-Permissions.md)).

## Writing

Pages are stored as **Markdown**. This keeps import, export and version comparison lossless.

**Edit** opens the Markdown editor: the source on the left and a **live preview** on the right (on narrow screens below each other). The preview updates while you type and looks exactly like the saved page.

- Toolbar: bold, italic, strikethrough, code, headings, lists, task lists, quote, code block, table, link, divider, table of contents. In the Markdown editor the buttons insert the Markdown syntax at the cursor or around the selected text; blocks such as tables or the table of contents go below the current line.
- **Editor** (on the right of the toolbar) switches to formatted editing without Markdown syntax; **Markdown** switches back. Switching alone never changes the content.
- In the formatted editor Markdown shortcuts work while typing (`# `, `- `, `1. `, `> `, ` ``` `), and pasted Markdown text turns into formatting directly.
- Without JavaScript a plain Markdown text field appears.
- On the page, every code block has a **Copy** button in its top right corner that copies the code to the clipboard.

HTML in the text is not executed for security reasons but shown as text. Links with `javascript:` are removed.

### Table of contents

Write `[TOC]` on a line of its own (or use the ☰ button in the toolbar). When the page is shown, the line is replaced by a table of contents of the headings H1–H3, nested and linked to the headings. The GitLab form `[[_TOC_]]` works as well.

Every heading can be linked directly: hovering over it shows a **#**, which is the link to that heading (e.g. `#h-getting-started`).

On wide screens longer pages (three or more headings) additionally show an **On this page** outline next to the text.

### Editing at the same time

If somebody else saves the page while you are editing it, your save is rejected instead of overwriting their changes. Copy your text, reload the page and add your changes again.

## Labels

Labels group pages across spaces. Enter them comma-separated in the **Labels** field when editing. They are stored in lower case, spaces become hyphens ("HR Processes" becomes `hr-processes`). Clicking a label shows all pages with that label that you may read.

## Version history

Every save with a changed title or content creates a new version.

- **⋮ → Version history** lists all versions with date and author.
- A version shows the **changes** compared to the previous version (green added, red removed) and the content.
- **Restore** makes an old version the new current version – the previous one stays in the history.

## Moving

Fastest with the mouse in the page tree (see above). Alternatively **⋮ → Move** or **More → Move** places a page below another page of the same space or at the top level. Sub-pages move along. A page cannot be moved below itself or its own sub-pages.

## Attachments

At the bottom of every page: upload files (up to 20 MB per file, up to 10 files at once). Allowed are PDF, images (PNG, JPG, GIF, WebP), text, Markdown, CSV, ZIP and office documents (docx, xlsx, pptx, odt, ods).

- PDFs and images open in the browser, all other files are downloaded.
- Only users who may read the page see its attachments.
- Text, Markdown and CSV files are included in the [Search](Search.md).
- To show an image in the text: `![Description](/attachments/123)` – the address is the link of the attachment.

## Trash

**⋮ → Move to trash** moves the page with its sub-pages into the trash of the space (role **Space admin**).

In the **Trash** (space menu **…** in the sidebar):

- **Restore** brings back the page with the sub-pages that were deleted with it. If the parent page is still in the trash, the page is restored at the top level.
- **Delete permanently** removes the page, sub-pages, versions and attachments for good.
