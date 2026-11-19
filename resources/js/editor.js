import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import { Markdown } from '@tiptap/markdown'
import { Table, TableRow, TableHeader, TableCell } from '@tiptap/extension-table'
import { TaskList, TaskItem } from '@tiptap/extension-list'
import { Placeholder } from '@tiptap/extensions'

/**
 * Rich-text editing on top of Markdown: the textarea stays the source of
 * truth that is submitted; TipTap reads and writes Markdown.
 */
export function mountEditor(root) {
    const source = root.querySelector('[data-editor-source]')
    const mount = root.querySelector('[data-editor-mount]')
    const toolbar = root.querySelector('[data-editor-toolbar]')
    const modeToggle = root.querySelector('[data-mode-toggle]')
    const sourcePane = root.querySelector('[data-source-pane]')
    const preview = root.querySelector('[data-preview]')
    const form = root.closest('form')

    let editor
    try {
        editor = new Editor({
            element: mount,
            extensions: [
                StarterKit.configure({ link: { openOnClick: false, autolink: true } }),
                Markdown,
                Table.configure({ resizable: false }),
                TableRow,
                TableHeader,
                TableCell,
                TaskList,
                TaskItem.configure({ nested: true }),
                Placeholder.configure({ placeholder: root.dataset.placeholder || '' }),
            ],
            content: source.value,
            contentType: 'markdown',
        })
    } catch (error) {
        console.warn('Editor could not start, falling back to Markdown.', error)
        return
    }

    let richMode = true
    const sync = () => { if (richMode) source.value = editor.getMarkdown() }

    // Live preview next to the Markdown source, rendered by the server so it
    // looks exactly like the saved page. Debounced; only the latest answer wins.
    let previewTimer = null
    let previewRequest = 0
    const renderPreview = () => {
        clearTimeout(previewTimer)
        previewTimer = setTimeout(async () => {
            const request = ++previewRequest
            try {
                const response = await fetch(root.dataset.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ content: source.value }),
                })
                if (!response.ok || request !== previewRequest) return
                const { html } = await response.json()
                // Escaped and sanitised by the server-side renderer.
                preview.innerHTML = html.trim() !== '' ? html : `<p class="text-slate-400">${root.dataset.previewEmpty}</p>`
            } catch (error) {
                console.warn('Preview failed', error)
            }
        }, 350)
    }
    source.addEventListener('input', () => { if (!richMode) renderPreview() })

    const setMode = (rich) => {
        if (rich === richMode) return
        if (rich) {
            editor.commands.setContent(source.value, { contentType: 'markdown' })
        } else {
            source.value = editor.getMarkdown()
        }
        richMode = rich
        mount.hidden = !rich
        sourcePane.hidden = rich
        preview.hidden = rich
        if (!rich) renderPreview()
        toolbar.querySelectorAll('[data-cmd]').forEach((b) => { b.disabled = !rich })
        modeToggle.textContent = rich ? root.dataset.labelSource : root.dataset.labelRich
    }

    const commands = {
        bold: () => editor.chain().focus().toggleBold().run(),
        italic: () => editor.chain().focus().toggleItalic().run(),
        strike: () => editor.chain().focus().toggleStrike().run(),
        code: () => editor.chain().focus().toggleCode().run(),
        h2: () => editor.chain().focus().toggleHeading({ level: 2 }).run(),
        h3: () => editor.chain().focus().toggleHeading({ level: 3 }).run(),
        bulletList: () => editor.chain().focus().toggleBulletList().run(),
        orderedList: () => editor.chain().focus().toggleOrderedList().run(),
        taskList: () => editor.chain().focus().toggleTaskList().run(),
        blockquote: () => editor.chain().focus().toggleBlockquote().run(),
        codeBlock: () => editor.chain().focus().toggleCodeBlock().run(),
        hr: () => editor.chain().focus().setHorizontalRule().run(),
        // "[TOC]" is replaced by a table of contents of the headings when the page is shown.
        toc: () => editor.chain().focus().insertContent({ type: 'paragraph', content: [{ type: 'text', text: '[TOC]' }] }).run(),
        table: () => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
        link: () => {
            const previous = editor.getAttributes('link').href || ''
            const url = window.prompt('URL', previous)
            if (url === null) return
            if (url === '') return editor.chain().focus().unsetLink().run()
            // Only http(s), mailto and internal links.
            if (!/^(https?:\/\/|mailto:|\/|#)/i.test(url)) return
            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
        },
    }

    toolbar.addEventListener('click', (event) => {
        const button = event.target.closest('[data-cmd]')
        if (button) commands[button.dataset.cmd]?.()
    })
    modeToggle.addEventListener('click', () => setMode(!richMode))

    form.addEventListener('submit', sync)
    editor.on('update', sync)

    // Paste Markdown text as formatted content.
    mount.addEventListener('paste', (event) => {
        const text = event.clipboardData?.getData('text/plain')
        const html = event.clipboardData?.getData('text/html')
        if (text && !html && /(^|\n)(#{1,6} |[-*+] |\d+\. |> |```|\|.+\|)/.test(text)) {
            event.preventDefault()
            editor.chain().focus().insertContent(text, { contentType: 'markdown' }).run()
        }
    }, true)

    // Warn before leaving with unsaved changes.
    let dirty = false
    editor.on('update', () => { dirty = true })
    source.addEventListener('input', () => { dirty = true })
    form.addEventListener('submit', () => { dirty = false })
    window.addEventListener('beforeunload', (event) => { if (dirty) event.preventDefault() })

    toolbar.hidden = false
    mount.hidden = false
    sourcePane.hidden = true
}
