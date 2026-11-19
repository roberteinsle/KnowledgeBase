import { Editor, Extension } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import { Markdown } from '@tiptap/markdown'
import { Table, TableRow, TableHeader, TableCell } from '@tiptap/extension-table'
import { TaskList, TaskItem } from '@tiptap/extension-list'
import { Placeholder } from '@tiptap/extensions'
import Suggestion from '@tiptap/suggestion'

/**
 * Block commands for the "/" menu. Labels come translated from the page
 * (data-slash-labels), keys are stable.
 */
const SLASH_ITEMS = [
    { key: 'text', icon: '¶', run: (c) => c.setParagraph() },
    { key: 'h1', icon: 'H1', run: (c) => c.setHeading({ level: 1 }) },
    { key: 'h2', icon: 'H2', run: (c) => c.setHeading({ level: 2 }) },
    { key: 'h3', icon: 'H3', run: (c) => c.setHeading({ level: 3 }) },
    { key: 'bulletList', icon: '•', run: (c) => c.toggleBulletList() },
    { key: 'orderedList', icon: '1.', run: (c) => c.toggleOrderedList() },
    { key: 'taskList', icon: '☑', run: (c) => c.toggleTaskList() },
    { key: 'blockquote', icon: '❝', run: (c) => c.toggleBlockquote() },
    { key: 'codeBlock', icon: '{ }', run: (c) => c.toggleCodeBlock() },
    { key: 'table', icon: '▦', run: (c) => c.insertTable({ rows: 3, cols: 3, withHeaderRow: true }) },
    { key: 'hr', icon: '—', run: (c) => c.setHorizontalRule() },
    { key: 'toc', icon: '☰', run: (c) => c.insertContent({ type: 'paragraph', content: [{ type: 'text', text: '[TOC]' }] }) },
]

function slashMenu(labels) {
    let element = null
    let selected = 0
    let current = null

    const draw = () => {
        if (!element || !current) return
        element.innerHTML = ''
        if (current.items.length === 0) {
            const empty = document.createElement('div')
            empty.className = 'px-3 py-2 text-sm text-slate-500'
            empty.textContent = labels.noResults || '—'
            element.appendChild(empty)
            return
        }
        current.items.forEach((item, index) => {
            const button = document.createElement('button')
            button.type = 'button'
            button.setAttribute('role', 'option')
            button.setAttribute('aria-selected', index === selected ? 'true' : 'false')
            button.className = 'flex w-full items-center gap-3 px-3 py-1.5 text-left text-sm '
                + (index === selected ? 'bg-indigo-50 text-indigo-800' : 'text-slate-700 hover:bg-slate-50')
            const icon = document.createElement('span')
            icon.className = 'grid size-7 shrink-0 place-items-center rounded border border-slate-200 bg-white text-xs font-semibold text-slate-600'
            icon.textContent = item.icon
            const text = document.createElement('span')
            text.textContent = item.label
            button.append(icon, text)
            button.addEventListener('mousedown', (event) => {
                event.preventDefault()
                current.command(item)
            })
            element.appendChild(button)
        })
    }

    const position = () => {
        const rect = current?.clientRect?.()
        if (!element || !rect) return
        element.style.left = `${Math.min(rect.left, window.innerWidth - element.offsetWidth - 8)}px`
        const below = rect.bottom + 6
        element.style.top = below + element.offsetHeight > window.innerHeight
            ? `${Math.max(8, rect.top - element.offsetHeight - 6)}px`
            : `${below}px`
    }

    return {
        onStart: (props) => {
            current = props
            selected = 0
            element = document.createElement('div')
            element.setAttribute('role', 'listbox')
            element.className = 'fixed z-50 max-h-80 w-64 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg'
            document.body.appendChild(element)
            draw()
            position()
        },
        onUpdate: (props) => {
            current = props
            selected = Math.min(selected, Math.max(0, props.items.length - 1))
            draw()
            position()
        },
        onKeyDown: ({ event }) => {
            if (!current) return false
            if (event.key === 'ArrowDown') { selected = (selected + 1) % Math.max(1, current.items.length); draw(); return true }
            if (event.key === 'ArrowUp') { selected = (selected - 1 + current.items.length) % Math.max(1, current.items.length); draw(); return true }
            if (event.key === 'Enter') { if (current.items[selected]) current.command(current.items[selected]); return true }
            if (event.key === 'Escape') { element?.remove(); element = null; return true }
            return false
        },
        onExit: () => {
            element?.remove()
            element = null
            current = null
        },
    }
}

function slashCommands(labels) {
    return Extension.create({
        name: 'slashCommands',
        addProseMirrorPlugins() {
            return [Suggestion({
                editor: this.editor,
                char: '/',
                // Only at the start of a line or after a space, never inside code.
                allowedPrefixes: [' '],
                allow: ({ editor }) => !editor.isActive('codeBlock') && !editor.isActive('code'),
                items: ({ query }) => SLASH_ITEMS
                    .map((item) => ({ ...item, label: labels[item.key] || item.key }))
                    .filter((item) => query === '' || item.label.toLowerCase().includes(query.toLowerCase()) || item.key.toLowerCase().includes(query.toLowerCase())),
                command: ({ editor, range, props }) => {
                    props.run(editor.chain().focus().deleteRange(range)).run()
                },
                render: () => slashMenu(labels),
            })]
        },
    })
}

/**
 * Rich text editor on Markdown with "/" commands. Styled like the rendered
 * page (prose), so headings, quotes and code blocks are clearly visible.
 */
export function createRichEditor(element, { content = '', placeholder = '', slashLabels = {} } = {}) {
    return new Editor({
        element,
        extensions: [
            StarterKit.configure({ link: { openOnClick: false, autolink: true } }),
            Markdown,
            Table.configure({ resizable: false }),
            TableRow,
            TableHeader,
            TableCell,
            TaskList,
            TaskItem.configure({ nested: true }),
            Placeholder.configure({ placeholder }),
            slashCommands(slashLabels),
        ],
        content,
        contentType: 'markdown',
        editorProps: {
            attributes: {
                class: 'kb-prose prose prose-slate max-w-none prose-headings:scroll-mt-20 prose-a:text-indigo-700 prose-table:text-sm focus:outline-none',
            },
        },
    })
}

/** Parses the translated labels of the "/" menu from a data attribute. */
export function slashLabelsFrom(element) {
    try {
        return JSON.parse(element.dataset.slashLabels || '{}')
    } catch {
        return {}
    }
}

/**
 * Markdown of the editor without trailing empty paragraphs (TipTap writes
 * them as "&nbsp;"), so leaving the cursor in a new empty line adds nothing.
 */
export function markdownOf(editor) {
    return editor.getMarkdown().replace(/(?:\s|&nbsp;|\u00a0)+$/u, '')
}
