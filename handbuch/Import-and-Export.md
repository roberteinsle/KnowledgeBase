# Import and Export

Markdown is the exchange format. What has been exported can be imported again without loss.

## Importing

In a space: **Import Markdown** at the top right of the space overview or in the space menu **…** in the sidebar, or on a page **More → Import below this page**. You need the role **Editor**.

Under **Import into** you choose where the new pages go (top level or below an existing page).

### Pasting text (copy & paste)

Choose **Paste text** and copy Markdown into the field. Without your own title the first heading (`# …`) becomes the page title and is removed from the text.

### Markdown file

Upload `.md`, `.markdown` or `.txt`. The title comes from

1. the front matter (`title:`),
2. otherwise the first heading `# …`,
3. otherwise the file name.

Labels come from `tags:` or `labels:` in the front matter:

```markdown
---
title: Travel policy
tags:
  - hr
  - travel
---

Text of the page …
```

Files in an encoding other than UTF-8 (e.g. Windows-1252) are converted.

### ZIP archive (Obsidian, Notion, own export)

A ZIP archive of Markdown files is imported as a page tree:

- **Folders become sub-pages.** The content of the folder page comes from `Folder.md` next to the folder (Notion, own export) or `Folder/index.md` or `README.md`. If neither exists, an empty page with the folder name is created.
- **Images and files** a page links to (`![](images/logo.png)`, `[PDF](docs/plan.pdf)`) become attachments of that page; the links then point to the attachment. The same file types as for uploading attachments are allowed.
- **Links between Markdown files** (`[Holidays](HR/Holidays.md)`) then point to the imported pages.
- **Obsidian:** wiki links `[[Page title]]` or `[[Page title|Text]]` and embeds `![[image.png]]` are converted.
- **Notion:** the IDs appended to file names ("Meeting notes 3f2a…") are removed.
- A common root folder (e.g. the vault folder) is skipped.
- Hidden files and `__MACOSX` are ignored, as are paths with `..`.

### Limits

| Limit | Value |
|---|---|
| File size of an upload | 20 MB |
| Unpacked size of a ZIP archive | 50 MB |
| Documents per archive | 500 |
| Pasted text | 500,000 characters |
| Imports per user and day | 50 |

An import runs completely or not at all: if something fails, nothing is created.

## Exporting

| What | Where | Result |
|---|---|---|
| One page as Markdown | page menu **⋮** or **More** | `.md` with front matter (title, labels) |
| One page as HTML | page menu **⋮** or **More** | standalone `.html` with styles and table of contents; images from the attachments are embedded, so the file also works offline |
| Page with sub-pages | page menu **⋮** or **More** | `.zip` |
| Whole space | space menu **…** in the sidebar | `.zip` – suitable as backup or for moving to another installation |

Structure of the ZIP archive:

```
Handbook.md
Handbook/
    Holidays.md
    Sick-leave.md
_attachments/
    17-logo.png
```

Links to other exported pages and to attachments become relative paths, so the archive also works in Obsidian or a Git repository. Anybody who may read may export. Pages in the trash are not exported.
