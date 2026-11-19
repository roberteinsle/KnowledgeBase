import Alpine from 'alpinejs'
import { initPasskeys } from './passkeys'
import { initPageTrees } from './tree'
import { initCodeCopy } from './code-copy'

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
initPageTrees()
initCodeCopy()

// Inline editing: a click into the page text (not on links or buttons) swaps
// it for the editor. The editor bundle is only loaded then.
document.addEventListener('click', (event) => {
    const article = event.target.closest?.('[data-inline-edit]')
    if (!article || article.hidden || event.target.closest('a, button, input, summary, .kb-code button')) return
    if (window.getSelection()?.toString()) return // selecting text to copy is not editing
    import('./inline-editor').then(({ startInlineEdit }) => startInlineEdit(article, event))
})

// The editor bundle is only loaded where it is needed.
const editorElement = document.querySelector('[data-editor]')
if (editorElement) {
    import('./editor').then(({ mountEditor }) => mountEditor(editorElement))
}
