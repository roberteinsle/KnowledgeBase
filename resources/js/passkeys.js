import { Passkeys } from '@laravel/passkeys'

const show = (element) => element.removeAttribute('hidden')

const fail = (root, error) => {
    const target = root.querySelector('[data-passkey-error]')
    if (!target) return
    target.textContent = root.dataset.error
    target.classList.remove('hidden', 'text-green-700')
    target.classList.add('text-red-700')
    console.warn(error)
}

const isCancelled = (error) => error?.name === 'NotAllowedError' || error?.name === 'AbortError'

export function initPasskeys() {
    if (!Passkeys.isSupported()) return

    // Sign-in page
    document.querySelectorAll('[data-passkey-login]').forEach((root) => {
        show(root)
        root.querySelector('[data-passkey-login-button]')?.addEventListener('click', async () => {
            try {
                const response = await Passkeys.verify()
                window.location.href = response?.redirect || '/'
            } catch (error) {
                if (!isCancelled(error)) fail(root, error)
            }
        })
    })

    // Re-confirmation for sensitive actions
    document.querySelectorAll('[data-passkey-confirm]').forEach((root) => {
        show(root)
        root.querySelector('[data-passkey-confirm-button]')?.addEventListener('click', async () => {
            try {
                const response = await Passkeys.verify({
                    routes: { options: '/passkeys/confirm/options', submit: '/passkeys/confirm' },
                })
                window.location.href = response?.redirect || '/'
            } catch (error) {
                if (!isCancelled(error)) fail(root, error)
            }
        })
    })

    // Add a passkey (security page and the offer after the first sign-in)
    document.querySelectorAll('[data-passkey-register]').forEach((root) => {
        show(root)
        root.querySelector('[data-passkey-register-button]')?.addEventListener('click', async () => {
            const name = (root.querySelector('[data-passkey-name]')?.value || '').trim() || 'Passkey'
            try {
                await Passkeys.register({ name: name.slice(0, 255) })
                window.location.href = root.dataset.redirect || window.location.href
            } catch (error) {
                // 423: the confirmation window has expired → confirm first.
                if (error?.status === 423 || error?.response?.status === 423) {
                    window.location.href = root.dataset.confirmUrl || '/confirm'
                    return
                }
                if (!isCancelled(error)) fail(root, error)
            }
        })
    })
}
