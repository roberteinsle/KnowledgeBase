/**
 * Sidebar page tree: expand/collapse (remembered per space), the shared "⋮"
 * menu and drag & drop (before / after / inside).
 */
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''

const storageKey = (tree) => `kb-tree-open-${tree.dataset.spaceId}`

const readOpen = (tree) => {
    try {
        return new Set(JSON.parse(localStorage.getItem(storageKey(tree)) || '[]'))
    } catch {
        return new Set()
    }
}

const writeOpen = (tree, set) => {
    try {
        localStorage.setItem(storageKey(tree), JSON.stringify([...set]))
    } catch {
        // storage unavailable (private mode) – the tree still works
    }
}

const setExpanded = (item, expanded) => {
    const children = item.querySelector(':scope > [data-tree-children]')
    if (!children) return
    children.hidden = !expanded
    item.setAttribute('aria-expanded', expanded ? 'true' : 'false')
    item.querySelector(':scope > [data-tree-row] [data-chevron]')?.classList.toggle('rotate-90', expanded)
}

function initToggles(tree) {
    const open = readOpen(tree)

    tree.querySelectorAll('li[aria-expanded]').forEach((item) => {
        const expanded = item.getAttribute('aria-expanded') === 'true' || open.has(item.dataset.pageId)
        setExpanded(item, expanded)
    })

    tree.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-tree-toggle]')
        if (!toggle) return
        const item = toggle.closest('li')
        const expanded = item.getAttribute('aria-expanded') !== 'true'
        setExpanded(item, expanded)

        const state = readOpen(tree)
        expanded ? state.add(item.dataset.pageId) : state.delete(item.dataset.pageId)
        writeOpen(tree, state)
    })
}

function initMenu(tree) {
    const template = tree.querySelector('[data-page-menu-template]')
    if (!template) return
    let menu = null

    const close = () => {
        menu?.remove()
        menu = null
    }

    tree.addEventListener('click', (event) => {
        const button = event.target.closest('[data-page-menu]')
        if (!button) return
        event.preventDefault()
        const item = button.closest('li')
        if (menu && menu.dataset.for === item.dataset.pageId) return close()
        close()

        const html = template.innerHTML.replaceAll('__PAGE__', item.dataset.pageId)
        const wrapper = document.createElement('div')
        wrapper.innerHTML = html.trim()
        menu = wrapper.firstElementChild
        menu.dataset.for = item.dataset.pageId

        if (item.dataset.favorite === '1') {
            menu.querySelectorAll('[data-active-label]').forEach((el) => {
                el.querySelector('[data-label]').textContent = el.dataset.activeLabel
            })
        }

        document.body.appendChild(menu)
        const rect = button.getBoundingClientRect()
        const top = Math.min(rect.bottom + 4, window.innerHeight - menu.offsetHeight - 8)
        menu.style.top = `${Math.max(8, top)}px`
        menu.style.left = `${Math.min(rect.left, window.innerWidth - menu.offsetWidth - 8)}px`
        menu.querySelector('[role="menuitem"]')?.focus()
    })

    document.addEventListener('click', (event) => {
        if (!menu) return
        const copy = event.target.closest('[data-copy-url]')
        if (copy && menu.contains(copy)) {
            navigator.clipboard?.writeText(copy.dataset.copyUrl).then(() => {
                copy.lastChild.textContent = ` ${tree.dataset.copiedLabel}`
                setTimeout(close, 800)
            })
            return
        }
        if (!menu.contains(event.target) && !event.target.closest('[data-page-menu]')) close()
    })
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close() })
    window.addEventListener('resize', close)
    tree.closest('aside')?.addEventListener('scroll', close)
}

function initDragAndDrop(tree) {
    const placeUrl = tree.dataset.placeUrl
    if (!placeUrl) return

    let dragged = null
    let target = null
    let placement = null

    const clear = () => {
        tree.querySelectorAll('[data-tree-row]').forEach((row) => {
            row.classList.remove('ring-2', 'ring-indigo-400', 'bg-indigo-50', 'shadow-[inset_0_2px_0_0_#6366f1]', 'shadow-[inset_0_-2px_0_0_#6366f1]')
        })
    }

    tree.addEventListener('dragstart', (event) => {
        const row = event.target.closest?.('[data-tree-row]')
        if (!row) return
        dragged = row.closest('li')
        event.dataTransfer.effectAllowed = 'move'
        event.dataTransfer.setData('text/plain', dragged.dataset.pageId)
        row.classList.add('opacity-50')
    })

    tree.addEventListener('dragover', (event) => {
        const row = event.target.closest?.('[data-tree-row]')
        if (!dragged || !row) return
        const item = row.closest('li')
        // Never onto itself or into its own sub-tree.
        if (dragged === item || dragged.contains(item)) return

        event.preventDefault()
        const rect = row.getBoundingClientRect()
        const y = (event.clientY - rect.top) / rect.height
        placement = y < 0.25 ? 'before' : y > 0.75 ? 'after' : 'inside'
        target = item

        clear()
        row.classList.add(...{
            before: ['shadow-[inset_0_2px_0_0_#6366f1]'],
            after: ['shadow-[inset_0_-2px_0_0_#6366f1]'],
            inside: ['ring-2', 'ring-indigo-400', 'bg-indigo-50'],
        }[placement])
    })

    tree.addEventListener('dragleave', (event) => {
        if (!tree.contains(event.relatedTarget)) clear()
    })

    tree.addEventListener('dragend', () => {
        clear()
        tree.querySelectorAll('.opacity-50').forEach((el) => el.classList.remove('opacity-50'))
        dragged = null
    })

    tree.addEventListener('drop', async (event) => {
        if (!dragged || !target) return
        event.preventDefault()
        clear()

        const pageId = dragged.dataset.pageId
        const body = { target_id: Number(target.dataset.pageId), placement }

        if (placement === 'inside') {
            const state = readOpen(tree)
            state.add(target.dataset.pageId)
            writeOpen(tree, state)
        }

        try {
            const response = await fetch(placeUrl.replace('__PAGE__', pageId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify(body),
            })
            if (!response.ok) throw new Error(`HTTP ${response.status}`)
        } catch (error) {
            console.warn(error)
            window.alert(tree.dataset.moveError)
        }
        // Reload so the tree, breadcrumbs and positions all come from the server.
        window.location.reload()
    })
}

export function initPageTrees() {
    document.querySelectorAll('[data-page-tree]').forEach((tree) => {
        initToggles(tree)
        initMenu(tree)
        initDragAndDrop(tree)
    })
}
