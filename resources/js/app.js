import Alpine from 'alpinejs'
import { initPasskeys } from './passkeys'

window.Alpine = Alpine
Alpine.start()

// Forms with data-confirm ask before submitting (delete, restore …).
document.addEventListener('submit', (event) => {
    const message = event.target?.dataset?.confirm
    if (message && !window.confirm(message)) {
        event.preventDefault()
    }
})

// <select data-autosubmit> submits its form on change (language switch).
document.addEventListener('change', (event) => {
    if (event.target?.matches?.('[data-autosubmit]')) {
        event.target.form?.submit()
    }
})

// "/" focuses the search field, as in many web apps.
document.addEventListener('keydown', (event) => {
    if (event.key !== '/' || event.target.closest('input, textarea, select, [contenteditable]')) return
    const field = document.querySelector('[data-shortcut="/"]')
    if (field && field.offsetParent !== null) {
        event.preventDefault()
        field.focus()
    }
})

initPasskeys()

// The editor bundle is only loaded where it is needed.
const editorElement = document.querySelector('[data-editor]')
if (editorElement) {
    import('./editor').then(({ mountEditor }) => mountEditor(editorElement))
}
