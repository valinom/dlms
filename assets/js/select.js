/* =============================================
   DLMS — Custom Select Dropdown
   ============================================= */
(function () {
    function initCustomSelects() {
        document.querySelectorAll('.custom-select:not([data-init])').forEach(sel => {
            sel.setAttribute('data-init', '1');

            const trigger = sel.querySelector('.select-trigger');
            const options = sel.querySelector('.select-options');
            const hidden  = sel.querySelector('input[type="hidden"]');
            const items   = sel.querySelectorAll('li[data-value]');

            if (!trigger || !options || !hidden) return;

            // Pre-select if hidden has a value
            if (hidden.value) {
                const match = [...items].find(i => i.dataset.value == hidden.value);
                if (match) trigger.textContent = match.textContent.trim();
            }

            trigger.addEventListener('click', e => {
                e.stopPropagation();
                const isOpen = sel.classList.contains('open');
                // Close all others
                document.querySelectorAll('.custom-select.open').forEach(s => s.classList.remove('open'));
                if (!isOpen) sel.classList.add('open');
            });

            items.forEach(item => {
                item.addEventListener('click', () => {
                    hidden.value       = item.dataset.value;
                    trigger.textContent = item.textContent.trim();
                    sel.classList.remove('open');
                    trigger.classList.remove('placeholder');
                });
            });
        });

        // Close on outside click
        document.addEventListener('click', () => {
            document.querySelectorAll('.custom-select.open').forEach(s => s.classList.remove('open'));
        }, { once: false });
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCustomSelects);
    } else {
        initCustomSelects();
    }

    // Expose for dynamic usage
    window.initCustomSelects = initCustomSelects;
})();

/* ── Inline styles ── */
(function () {
    const style = document.createElement('style');
    style.textContent = `
        .custom-select { position: relative; user-select: none; }

        .select-trigger {
            width: 100%;
            padding: .6rem .85rem;
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-sm);
            background: var(--bg-input);
            color: var(--text-main);
            font-size: .92rem;
            cursor: pointer;
            display: flex; align-items: center; justify-content: space-between;
            transition: border-color .15s;
        }
        .select-trigger::after {
            content: '\\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: .7rem;
            color: var(--text-muted);
            transition: transform .2s;
        }
        .custom-select.open .select-trigger {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px hsl(225,70%,55%,.12);
        }
        .custom-select.open .select-trigger::after { transform: rotate(180deg); }

        .select-options {
            position: absolute; top: 100%; left: 0; right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-md);
            max-height: 220px;
            overflow-y: auto;
            z-index: 500;
            display: none;
            list-style: none;
            padding: .3rem;
            margin-top: 4px;
        }
        .custom-select.open .select-options { display: block; }

        .select-options li {
            padding: .55rem .8rem;
            border-radius: 6px;
            font-size: .9rem;
            cursor: pointer;
            color: var(--text-main);
            transition: background .12s;
        }
        .select-options li:hover { background: var(--bg-hover); }
    `;
    document.head.appendChild(style);
})();
