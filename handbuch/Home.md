# Knowledgebase

Knowledgebase is a self-hosted knowledge base for teams: documentation organised in **spaces** with nested **pages**, version history, labels, attachments, full-text search and lossless **Markdown** import and export.

## For users

- [Sign-in](Sign-in.md) – passkeys, e-mail sign-in links, authenticator app
- [Account](Account.md) – profile picture, name, language
- [Pages](Pages.md) – writing, page tree, drag & drop, table of contents, versions, labels, attachments, trash
- [Import and Export](Import-and-Export.md) – Markdown files, ZIP archives (Obsidian, Notion), copy & paste, HTML, backups
- [Search](Search.md)
- [MCP (AI assistants)](MCP.md) – connect Claude and other AI apps

## For administrators and operators

- [Spaces, Groups and Permissions](Spaces-Groups-and-Permissions.md)
- [Administration](Administration.md) – users, groups, spaces, labels, activity log, settings
- [Operations](Operations.md) – installation, environment variables, Cloudflare Access, updates, backup

## Structure

| Level | Meaning |
|---|---|
| **Workspace** | The whole installation: name, default language, security settings. |
| **Space** | A section for a team, a project or a department – with its own permissions and icon. |
| **Page** | A document in a space. Pages can have sub-pages. |
| **Group** | Several users who get permissions together. Every user automatically belongs to the group **Everyone**. |

## Navigation

- **Header:** logo and *Home* on the left, search in the middle (key <kbd>/</kbd>), account menu on the right.
- **Left sidebar:** inside a space the **space switcher** at the top (with a search field), below it *Overview*, *Search* (this space only), *Space settings* (administrators) and *New page*, followed by the **page tree** of the space. Outside a space (home page, spaces list, account, search) the sidebar lists all your spaces.
- **Phone:** a tab bar at the bottom (Home, Spaces, Search, Account); the sidebar opens via **Spaces and pages**.
- **Home page:** tabs *Recently updated*, *Favorites* and *Created by me*, and *Your spaces* sorted A–Z – the first six, the rest via **Show all spaces**. The space overview shows the same tabs for one space.

## Addresses

All addresses are English, whatever language the interface uses. A page has a readable address made of space, title and number, e.g. `/handbook/onboarding-guide-12`. If the title changes or the page moves to another space, old addresses forward to the current one. `/pages/12` is the permanent link of the same page.

## Languages

The interface is available in German, English, French, Italian and Spanish. Every user chooses a language under **Account**; without a choice the browser language is used, otherwise the default language of the workspace.

## Planned

Databases (Bases) and comments follow in later versions.
