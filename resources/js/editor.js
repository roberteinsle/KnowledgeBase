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

    const setMode = (rich) => {
        if (rich === richMode) return
        if (rich) {
            editor.commands.setContent(source.value, { contentType: 'markdown' })
        } else {
            source.value = editor.getMarkdown()
        }
        richMode = rich
        mount.hidden = !rich
        source.hidden = rich
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
    source.hidden = true
}
