/* =============================================
   DLMS — Custom Alert / Confirm Dialogs
   ============================================= */

/**
 * showAlert(message, type)
 * type: 'error' | 'success' | 'info' | 'warning'
 */
function showAlert(message, type = 'error') {
    // Remove existing
    document.querySelectorAll('.dlms-alert-overlay').forEach(e => e.remove());

    const colors = {
        error:   { icon: 'fa-circle-xmark',        bg: 'var(--danger-bg)',  color: 'var(--danger)' },
        success: { icon: 'fa-circle-check',         bg: 'var(--success-bg)', color: 'var(--success)' },
        info:    { icon: 'fa-circle-info',          bg: 'var(--info-bg)',    color: 'var(--info)' },
        warning: { icon: 'fa-triangle-exclamation', bg: 'var(--warning-bg)', color: 'hsl(38,80%,32%)' },
    };
    const c = colors[type] || colors.error;

    const overlay = document.createElement('div');
    overlay.className = 'dlms-alert-overlay';
    overlay.innerHTML = `
        <div class="dlms-alert-box">
            <div class="dlms-alert-icon" style="color:${c.color};background:${c.bg}">
                <i class="fa-solid ${c.icon}"></i>
            </div>
            <p class="dlms-alert-msg">${message}</p>
            <button class="dlms-alert-btn" onclick="this.closest('.dlms-alert-overlay').remove()">OK</button>
        </div>
    `;
    document.body.appendChild(overlay);

    // Focus button
    setTimeout(() => overlay.querySelector('button')?.focus(), 50);

    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.remove();
    });
    document.addEventListener('keydown', function esc(e) {
        if (e.key === 'Escape') { overlay.remove(); document.removeEventListener('keydown', esc); }
    });
}

/**
 * showConfirm(opts, onConfirm?, onCancel?)
 * opts can be:
 *   - string: used as message
 *   - object: { title?, message, confirmText?, onConfirm?, onCancel? }
 */
function showConfirm(opts, onConfirm, onCancel) {
    // Normalize arguments
    let message, confirmText, _onConfirm, _onCancel;
    if (typeof opts === 'object' && opts !== null) {
        message     = opts.message || 'Are you sure?';
        confirmText = opts.confirmText || 'Delete';
        _onConfirm  = opts.onConfirm || onConfirm;
        _onCancel   = opts.onCancel  || onCancel;
    } else {
        message     = opts || 'Are you sure?';
        confirmText = 'Delete';
        _onConfirm  = onConfirm;
        _onCancel   = onCancel;
    }

    document.querySelectorAll('.dlms-alert-overlay').forEach(e => e.remove());

    const overlay = document.createElement('div');
    overlay.className = 'dlms-alert-overlay';
    overlay.innerHTML = `
        <div class="dlms-alert-box">
            <div class="dlms-alert-icon" style="color:var(--warning);background:var(--warning-bg)">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <p class="dlms-alert-msg">${message}</p>
            <div class="dlms-alert-actions">
                <button class="dlms-alert-btn cancel" id="_dlmsCancel">Cancel</button>
                <button class="dlms-alert-btn confirm danger" id="_dlmsConfirm">${confirmText}</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    document.getElementById('_dlmsConfirm').onclick = () => {
        overlay.remove();
        if (typeof _onConfirm === 'function') _onConfirm();
    };
    document.getElementById('_dlmsCancel').onclick = () => {
        overlay.remove();
        if (typeof _onCancel === 'function') _onCancel();
    };

    document.getElementById('_dlmsConfirm').focus();

    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.remove();
    });
}

/**
 * showToast(message, type)
 * type: 'success' | 'error' | 'info' | 'warning'
 */
function showToast(message, type = 'info') {
    const icons = {
        success: 'fa-circle-check',
        error:   'fa-circle-xmark',
        info:    'fa-circle-info',
        warning: 'fa-triangle-exclamation',
    };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.cssText = 'pointer-events:all;';
    toast.innerHTML = `
        <i class="fa-solid ${icons[type] || icons.info}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;padding:.2rem;opacity:.6;color:inherit"><i class="fa-solid fa-xmark"></i></button>
    `;

    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        container.style.cssText = 'position:fixed;top:1.2rem;right:1.2rem;z-index:9999;display:flex;flex-direction:column;gap:.6rem;pointer-events:none;';
        document.body.appendChild(container);
    }
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        toast.style.transition = 'opacity .4s, transform .4s';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}


/* ── Inline styles for dialog ── */
(function () {
    const style = document.createElement('style');
    style.textContent = `
        .dlms-alert-overlay {
            position: fixed; inset: 0; z-index: 9998;
            background: rgba(0,0,0,.45);
            display: flex; align-items: center; justify-content: center;
            animation: dlmsFadeIn .15s ease;
        }
        @keyframes dlmsFadeIn { from { opacity:0 } to { opacity:1 } }

        .dlms-alert-box {
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius);
            padding: 2rem 1.8rem 1.5rem;
            width: 100%; max-width: 360px;
            box-shadow: var(--shadow-lg);
            display: flex; flex-direction: column; align-items: center; gap: .8rem;
            animation: dlmsPopIn .2s ease;
        }
        @keyframes dlmsPopIn { from { transform:scale(.92); opacity:0 } to { transform:scale(1); opacity:1 } }

        .dlms-alert-icon {
            width: 54px; height: 54px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }
        .dlms-alert-msg {
            font-size: .92rem; color: var(--text-main);
            text-align: center; line-height: 1.55;
        }
        .dlms-alert-btn {
            padding: .55rem 1.6rem; border-radius: var(--radius-sm);
            font-weight: 700; font-size: .88rem; cursor: pointer;
            border: none; background: var(--accent); color: #fff;
            transition: background .15s;
        }
        .dlms-alert-btn:hover { background: var(--accent-hover); }
        .dlms-alert-btn.cancel {
            background: var(--bg-hover); color: var(--text-muted);
            border: 1px solid var(--border-soft);
        }
        .dlms-alert-btn.danger { background: var(--danger); }
        .dlms-alert-btn.danger:hover { background: hsl(0,65%,42%); }

        .dlms-alert-actions { display: flex; gap: .7rem; }
    `;
    document.head.appendChild(style);
})();
