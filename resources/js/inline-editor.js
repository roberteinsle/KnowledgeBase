import { createRichEditor, markdownOf, slashLabelsFrom } from './tiptap'

/**
 * Edit a page in place: the rendered article is swapped for a rich text editor
 * with "/" commands. Saves through the normal update route (JSON, version
 * check) and reloads, so the server renders the final page again.
 */
export async function startInlineEdit(article, clickEvent) {
    const mount = document.querySelector('[data-inline-mount]')
    const bar = document.querySelector('[data-inline-bar]')
    const status = bar.querySelector('[data-inline-status]')
    const hint = document.querySelector('[data-inline-hint]')
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || ''
    const d = article.dataset

    const response = await fetch(d.markdownUrl, { headers: { Accept: 'text/plain' } })
    if (!response.ok) return
    const markdown = await response.text()

    // Same width as the article, so the clicked position roughly matches.
    const clickPoint = clickEvent ? { left: clickEvent.clientX, top: clickEvent.clientY } : null
    article.hidden = true
    mount.hidden = false
    bar.hidden = false
    if (hint) hint.hidden = true

    const editor = createRichEditor(mount, { content: markdown, placeholder: d.placeholder || '', slashLabels: slashLabelsFrom(article) })
    let dirty = false
    let saving = false
    editor.on('update', () => { dirty = true })

    const position = clickPoint ? editor.view.posAtCoords(clickPoint) : null
    editor.commands.focus(position ? position.pos : 'end')

    const stop = () => {
        editor.destroy()
        mount.hidden = true
        mount.innerHTML = ''
        bar.hidden = true
        article.hidden = false
        if (hint) hint.hidden = false
        document.removeEventListener('keydown', onKey)
        window.removeEventListener('beforeunload', onLeave)
    }

    const cancel = () => {
        if (dirty && !window.confirm(d.msgDiscard)) return
        stop()
    }

    const save = async () => {
        if (saving) return
        if (!dirty) { stop(); return }
        saving = true
        status.textContent = d.msgSaving
        try {
            const result = await fetch(d.updateUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ title: d.title, content: markdownOf(editor), labels: d.labels, version: Number(d.version) }),
            })
            if (result.status === 409) { status.textContent = d.msgConflict; saving = false; return }
            if (!result.ok) throw new Error(`HTTP ${result.status}`)
            const { url } = await result.json()
            dirty = false
            window.location.href = url
        } catch (error) {
            console.warn(error)
            status.textContent = d.msgError
            saving = false
        }
    }

    const onKey = (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') { event.preventDefault(); save() }
        // Escape closes the "/" menu first; only a second Escape cancels.
        if (event.key === 'Escape' && !document.querySelector('[role="listbox"].fixed')) cancel()
    }
    const onLeave = (event) => { if (dirty) event.preventDefault() }

    document.addEventListener('keydown', onKey)
    window.addEventListener('beforeunload', onLeave)
    bar.querySelector('[data-inline-save]').onclick = save
    bar.querySelector('[data-inline-cancel]').onclick = cancel
}
