import { createRichEditor, markdownOf, slashLabelsFrom } from './tiptap'

/**
 * Page editor. Opens in the Markdown source with a live preview; the rich
 * text editor (TipTap) is optional. The textarea is always what is submitted.
 */
export function mountEditor(root) {
    const source = root.querySelector('[data-editor-source]')
    const mount = root.querySelector('[data-editor-mount]')
    const toolbar = root.querySelector('[data-editor-toolbar]')
    const modeToggle = root.querySelector('[data-mode-toggle]')
    const sourcePane = root.querySelector('[data-source-pane]')
    const preview = root.querySelector('[data-preview]')
    const form = root.closest('form')

    let editor = null
    let richMode = false
    let dirty = false
    // Placeholders for empty lines/selections, translated by the page.
    const L = { text: 'text', code: 'code', heading: 'Heading', item: 'Item', quote: 'Quote', link: 'link', ...JSON.parse(root.dataset.placeholders || '{}') }

    // --- Live preview, rendered by the server (same renderer as the page) ---
    let previewTimer = null
    let previewRequest = 0
    const renderPreview = (delay = 300) => {
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
        }, delay)
    }

    const changed = () => {
        dirty = true
        if (!richMode) renderPreview()
    }
    source.addEventListener('input', changed)

    // --- Markdown helpers for the toolbar in source mode ---
    const replaceSelection = (transform) => {
        const { selectionStart: start, selectionEnd: end, value } = source
        const { text, selectFrom, selectTo } = transform(value.slice(start, end))
        source.setRangeText(text, start, end, 'end')
        source.focus()
        if (selectFrom !== undefined) source.setSelectionRange(start + selectFrom, start + selectTo)
        source.dispatchEvent(new Event('input'))
    }
    const wrap = (before, after = before, placeholder = '') => replaceSelection((selected) => {
        const inner = selected || placeholder
        return { text: before + inner + after, selectFrom: before.length, selectTo: before.length + inner.length }
    })
    // Prefix the current line(s); an empty line gets a selected placeholder,
    // otherwise an empty "## " or "> " would not show up in the preview.
    const prefixLines = (prefix, placeholder) => {
        const { value } = source
        const lineStart = value.lastIndexOf('\n', source.selectionStart - 1) + 1
        const lineEndIndex = value.indexOf('\n', source.selectionEnd)
        const lineEnd = lineEndIndex === -1 ? value.length : lineEndIndex
        source.setSelectionRange(lineStart, lineEnd)
        const prefixFor = (i) => (typeof prefix === 'function' ? prefix(i) : prefix)
        replaceSelection((selected) => {
            if ((selected || '').trim() === '') {
                return { text: prefixFor(0) + placeholder, selectFrom: prefixFor(0).length, selectTo: prefixFor(0).length + placeholder.length }
            }
            return { text: selected.split('\n').map((line, i) => prefixFor(i) + line).join('\n') }
        })
    }
    const insertBlock = (block, select = null) => {
        // Blocks go after the current line – never into selected text or inline markup.
        const lineEnd = source.value.indexOf('\n', source.selectionEnd)
        const position = lineEnd === -1 ? source.value.length : lineEnd
        source.setSelectionRange(position, position)
        insertAtCursor(block, select)
    }
    const insertAtCursor = (block, select = null) => replaceSelection(() => {
        const before = source.value.slice(0, source.selectionStart)
        const lead = before === '' || before.endsWith('\n\n') ? '' : before.endsWith('\n') ? '\n' : '\n\n'
        const at = select !== null ? block.indexOf(select) : -1
        return at === -1
            ? { text: lead + block + '\n' }
            : { text: lead + block + '\n', selectFrom: lead.length + at, selectTo: lead.length + at + select.length }
    })
    // Code block on lines of its own (fences inside a line are no code block).
    const codeBlock = () => {
        const selected = source.value.slice(source.selectionStart, source.selectionEnd)
        if (selected.trim() !== '') {
            replaceSelection((text) => ({ text: '\n```\n' + text.replace(/^\n+|\n+$/g, '') + '\n```\n' }))
            return
        }
        insertBlock('```\n' + L.code + '\n```', L.code)
    }

    const markdownCommands = {
        bold: () => wrap('**', '**', L.text),
        italic: () => wrap('*', '*', L.text),
        strike: () => wrap('~~', '~~', L.text),
        code: () => wrap('`', '`', L.code),
        h2: () => prefixLines('## ', L.heading),
        h3: () => prefixLines('### ', L.heading),
        bulletList: () => prefixLines('- ', L.item),
        orderedList: () => prefixLines((i) => `${i + 1}. `, L.item),
        taskList: () => prefixLines('- [ ] ', L.item),
        blockquote: () => prefixLines('> ', L.quote),
        codeBlock,
        table: () => insertBlock('| Column 1 | Column 2 |\n| --- | --- |\n|  |  |'),
        hr: () => insertBlock('---'),
        toc: () => insertBlock('[TOC]'),
        link: () => {
            const url = window.prompt('URL', 'https://')
            if (!url || !/^(https?:\/\/|mailto:|\/|#)/i.test(url)) return
            wrap('[', `](${url})`, L.link)
        },
    }

    // --- Optional rich text editor (with "/" commands) ---
    const createEditor = () => createRichEditor(mount, {
        placeholder: root.dataset.placeholder || '',
        slashLabels: slashLabelsFrom(root),
    })

    const richCommands = {
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
        toc: () => editor.chain().focus().insertContent({ type: 'paragraph', content: [{ type: 'text', text: '[TOC]' }] }).run(),
        table: () => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
        link: () => {
            const url = window.prompt('URL', editor.getAttributes('link').href || '')
            if (url === null) return
            if (url === '') return editor.chain().focus().unsetLink().run()
            if (!/^(https?:\/\/|mailto:|\/|#)/i.test(url)) return
            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
        },
    }

    const setMode = (rich) => {
        if (rich) {
            if (!editor) {
                try {
                    editor = createEditor()
                } catch (error) {
                    console.warn('Rich editor could not start', error)
                    return
                }
                editor.on('update', () => {
                    if (richMode) {
                        source.value = markdownOf(editor)
                        dirty = true
                    }
                })
                // Paste Markdown text as formatted content.
                mount.addEventListener('paste', (event) => {
                    const text = event.clipboardData?.getData('text/plain')
                    const html = event.clipboardData?.getData('text/html')
                    if (text && !html && /(^|\n)(#{1,6} |[-*+] |\d+\. |> |```|\|.+\|)/.test(text)) {
                        event.preventDefault()
                        editor.chain().focus().insertContent(text, { contentType: 'markdown' }).run()
                    }
                }, true)
            }
            // Only load, never write back on switching: unchanged Markdown stays byte-identical.
            editor.commands.setContent(source.value, { contentType: 'markdown', emitUpdate: false })
        } else {
            renderPreview(0)
        }

        richMode = rich
        mount.hidden = !rich
        sourcePane.hidden = rich
        preview.hidden = rich
        modeToggle.textContent = rich ? root.dataset.labelSource : root.dataset.labelRich
        if (rich) editor.commands.focus('end')
    }

    toolbar.addEventListener('click', (event) => {
        const button = event.target.closest('[data-cmd]')
        if (!button) return
        const commands = richMode ? richCommands : markdownCommands
        commands[button.dataset.cmd]?.()
    })
    modeToggle.addEventListener('click', () => setMode(!richMode))

    // Warn before leaving with unsaved changes.
    form.addEventListener('submit', () => { dirty = false })
    window.addEventListener('beforeunload', (event) => { if (dirty) event.preventDefault() })

    toolbar.hidden = false
    setMode(false)
}
