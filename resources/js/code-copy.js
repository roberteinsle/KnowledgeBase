/**
 * Adds a "Copy" button to every code block in rendered content
 * (containers marked with data-code-copy).
 */
export function initCodeCopy() {
    document.querySelectorAll('[data-code-copy]').forEach((container) => {
        const label = container.dataset.copyLabel || 'Copy'
        const copied = container.dataset.copiedLabel || 'Copied'

        container.querySelectorAll('pre').forEach((pre) => {
            if (pre.parentElement?.classList.contains('kb-code')) return

            const wrapper = document.createElement('div')
            wrapper.className = 'kb-code group relative'
            pre.parentNode.insertBefore(wrapper, pre)
            wrapper.appendChild(pre)

            const button = document.createElement('button')
            button.type = 'button'
            button.className = 'absolute right-2 top-2 rounded-md border border-white/20 bg-slate-800/90 px-2 py-1 text-xs font-medium text-slate-100 opacity-80 transition hover:opacity-100 focus:opacity-100 group-hover:opacity-100'
            button.textContent = label
            button.setAttribute('aria-label', label)
            button.addEventListener('click', async () => {
                const text = (pre.querySelector('code') || pre).innerText
                try {
                    await navigator.clipboard.writeText(text)
                    button.textContent = copied
                    setTimeout(() => { button.textContent = label }, 1500)
                } catch (error) {
                    console.warn('Copy failed', error)
                }
            })
            wrapper.appendChild(button)
        })
    })
}
