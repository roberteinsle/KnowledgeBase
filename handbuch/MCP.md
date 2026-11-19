# MCP (AI assistants)

Knowledgebase provides a **Model Context Protocol (MCP) server**. AI assistants such as Claude can search, read, create and update pages and manage spaces – always with **your** permissions.

**Server address:** `https://<your-domain>/mcp` (also shown on your **Account** page under *Connected AI apps*).

## Connecting

Sign-in uses OAuth: the AI app opens your knowledge base in the browser, you sign in as usual (passkey or e-mail link) and approve the access on a consent page. No password or token has to be copied.

### claude.ai (and the Claude desktop and mobile apps)

1. In Claude open **Settings → Connectors → Add custom connector**.
2. Name: e.g. *Knowledgebase*; URL: `https://<your-domain>/mcp`.
3. Click **Connect**, sign in to the knowledge base and choose **Allow**.

In a chat you can then ask, for example, "Search the knowledge base for our travel policy" or "Create a page *Release notes 2.4* in the space *Product*".

### Claude Code

```bash
claude mcp add --transport http knowledgebase https://<your-domain>/mcp
```

Then run `/mcp` in Claude Code and choose *Authenticate*; the browser opens for sign-in and consent.

### Other MCP clients

Any client that supports remote MCP servers over HTTP with OAuth (dynamic client registration, PKCE) works. Callback addresses are limited to claude.ai, claude.com and `localhost` by default; operators can allow more (see [Operations](Operations.md)).

## What the AI can do

| Tool | Does | Needs |
|---|---|---|
| `search` | full-text search over pages and attachments (optionally in one space) | Reader |
| `list_spaces` | spaces you can read, with your role | – |
| `list_pages` | page tree of a space | Reader |
| `read_page` | title, Markdown content, labels, version, sub-pages, attachments | Reader |
| `create_page` | new page (Markdown, labels, optionally below a parent) | Editor |
| `update_page` | change title, content and/or labels; refuses if the page changed since it was read | Editor |
| `move_page` | before/after another page, into it, or to the top level | Editor |
| `trash_page` | move a page with its sub-pages to the trash | Space admin |
| `create_space`, `update_space` | create spaces, change name, description, emoji | Workspace administrator |

The AI never gets more rights than you have: spaces you cannot read do not exist for it. Every change creates a page version (see [Pages](Pages.md)) and is recorded in the activity log under your name.

## Connected apps and revoking access

**Account → Connected AI apps** lists every app with access. **Revoke** ends the access immediately; the app has to ask for approval again. Deactivating a user also ends all of their access.

Access tokens are valid for one hour and are renewed automatically by the app for up to 30 days.
