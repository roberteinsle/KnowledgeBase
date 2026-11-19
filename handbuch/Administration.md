# Administration

The administration area is at `/admin` and only available to workspace administrators. Open it via the account menu at the top right → **Administration**; **Back to the knowledge base** (below *Dashboard* in the navigation) returns to the app.

## Dashboard

The start page of the administration shows:

- **Key figures:** users (with administrators and deactivated accounts), groups, spaces, pages (changes in the last 7 days, pages in the trash), attachments (number and size), labels, sign-ins in the last 7 days (users with passkey or authenticator app) and connected AI apps.
- **System status:** database (PostgreSQL version), file storage (writable), application (version, PHP, Laravel; warns if `APP_DEBUG` is on or `APP_URL` is not https), **e-mail (SMTP)** and the **MCP server** (address, where the OAuth keys come from, connected apps).
- **Test SMTP connection** connects to the mail server and signs in without sending anything; **Send test e-mail** sends a mail to your own address. The result of the last test is shown in the status (e-mail delivery is needed for sign-in links).
- **Activity in the last 30 days:** page changes and sign-ins per day.

## Users

**Administration → Users**

- **New:** name, e-mail address, role (*Member* or *Administrator*), language, groups. The user then signs in with a sign-in link.
- **Send sign-in link:** sends the user a link directly (e.g. as an invitation).
- **Deactivate** (switch *Active*): the user is signed out on the next request and can no longer sign in – neither with a link nor with a passkey. Their pages are kept.
- **Delete:** removes the account. Pages are kept; "unknown" appears as author.

There must always be at least one active administrator; the last one can be neither demoted nor deactivated nor deleted.

### First administrator

After installation there is no user yet. The address from the environment variable `ADMIN_EMAIL` can still sign in: the first time it requests a sign-in link, the account is created as administrator.

## Groups

**Administration → Groups**: name, description, members. See [Spaces, Groups and Permissions](Spaces-Groups-and-Permissions.md).

## Spaces

**Administration → Spaces**: create, upload an icon, grant permissions, delete. The list shows the uploaded icon (or the emoji next to the name). **Open** jumps into the space.

## Labels

**Administration → Labels**: all labels with the number of pages. Rename or delete them (the pages stay, only the label disappears).

## Activity log

**Administration → Activity log** shows who changed what and when: sign-ins, pages (created, changed, moved, duplicated, deleted), attachments, imports, exports, spaces, groups, users and settings. Page content, sign-in links and secrets are not logged.

## Workspace settings

**Administration → Workspace**

- **Name** and **description** (shown in the header, on the home page and in e-mails)
- **Default language** for everybody who has not chosen a language and whose browser language is not supported
- **Require a passkey or authenticator app for everyone**
