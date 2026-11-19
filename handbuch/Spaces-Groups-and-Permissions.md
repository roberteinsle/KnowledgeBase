# Spaces, Groups and Permissions

## Spaces

A space bundles pages, permissions and people for a team, a project or a purpose. Administrators create spaces under **Administration → Spaces**: name, URL name (e.g. `handbook` → page addresses like `/handbook/onboarding-guide-12`), description and icon. In the left sidebar **Space settings** takes administrators straight there.

Some URL names are reserved for the system (e.g. `admin`, `search`, `login`, `spaces`, `pages`); a space called "Search" gets the URL name `search-space`.

Deleting a space removes all its pages, versions and attachments permanently.

### Space icon

- **Upload an image:** PNG, JPG, WebP or GIF up to 1 MB; it is shown square (image editor in the form). SVG is not allowed for security reasons. Only users who may read the space see the image.
- **Emoji:** used when no image is uploaded.
- Without either the app generates an **avatar from the name**: the initials on a coloured background ("Human Resources" becomes "HR"), the same style as user avatars.

The icon is shown in the space switcher, on the space overview, in lists and in the space table of the administration.

## Roles in a space

| Role | May |
|---|---|
| **Reader** | view pages, versions and attachments, search, export, add favorites, copy pages into spaces where they are Editor |
| **Editor** | additionally create, edit, move (also by drag & drop) and duplicate pages, restore versions, import, upload and delete attachments |
| **Space admin** | additionally move pages to the trash, restore them and delete them permanently |

**Workspace administrators** have full rights in all spaces and manage spaces, groups and users.

Without a permission a space is invisible: it appears neither in lists nor in the search.

## Granting permissions

In **Administration → Spaces → (space) → Permissions**, one row each:

- **Type:** group or user
- **Group or user**
- **Role**

If somebody has several permissions (directly and through groups), the highest role applies.

## Groups

Instead of granting permissions to every user individually, grant them to groups (**Administration → Groups**).

- **Everyone:** this group always exists. All users belong to it automatically, including new ones. It cannot be deleted and has no member list. Example: a space "General" with *Everyone → Reader* is visible to everybody.
- Own groups (e.g. "Editorial team", "HR") get members in the group or at the user.

Example:

| Space | Permission |
|---|---|
| General | Everyone → Reader, Editorial team → Editor |
| HR | HR → Space admin |
| IT | IT team → Editor, Everyone → Reader |
