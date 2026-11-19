# Sign-in

There are no passwords. You sign in with a **passkey** or a **sign-in link by e-mail**, optionally with a second factor from an authenticator app.

## With a passkey (recommended)

A passkey is a key stored securely on your device (or in your password manager). You unlock it with your fingerprint, face or device PIN.

1. Choose **Sign in with a passkey** on the sign-in page.
2. Confirm the passkey in the browser dialog.

A passkey already counts as two factors (device + biometrics/PIN), so there is no additional code prompt.

### Setting up a passkey

- After the first sign-in with an e-mail link the app offers to set up a passkey right away.
- At any time under **Account → Security → Add passkey**. Give it a name (e.g. "Laptop") so you recognise it later.
- You can have several passkeys (laptop, phone, security key) and remove single ones.

## With a sign-in link by e-mail

1. Enter your e-mail address and choose **Send sign-in link**.
2. The page always says "If the address exists, we have sent a sign-in link to it." – also for unknown addresses, so nobody can find out which addresses have an account.
3. Click **Sign in** in the e-mail and confirm with **Sign in now** on the page that opens.

The link

- is valid for **15 minutes** and works **only once**,
- stops working as soon as you request a new one,
- makes all other open links expire once you are signed in.

The confirmation button is intentional: some e-mail programs open links in advance to scan them. Only the click on the button signs you in.

After too many attempts in a short time (per address and per internet connection) requesting links is blocked for a while.

## Two-factor authentication (authenticator app)

If you sign in with e-mail links, you can additionally require a code from an authenticator app (e.g. Aegis, 2FAS, Google Authenticator, 1Password).

**Set up:** Account → Security → **Set up authenticator app**, scan the QR code, enter the code shown. Afterwards **recovery codes** appear – store them safely once. Each code works exactly once and replaces the app code if your phone is not at hand.

**Turn off:** Account → Security → **Disable**.

Administrators can require a passkey or an authenticator app for everyone under **Administration → Workspace**. Users who have neither are sent to the security page after signing in.

## Confirming again

The security page (passkeys, two-factor) requires a sign-in within the last 15 minutes. Otherwise you confirm with your passkey or have a confirmation link sent to you.

## Signing out

Click your name in the top right → **Sign out**.

## Questions

**The link has expired.** Simply request a new one; old links no longer work.

**I lost the phone with my authenticator app.** Sign in with a recovery code and set up the app again under Account → Security. Without codes an administrator cannot help without recreating the account – so set up a passkey as a second way in.

**I do not get an e-mail.** Check your spam folder. If the account is deactivated or unknown, no e-mail is sent on purpose.
