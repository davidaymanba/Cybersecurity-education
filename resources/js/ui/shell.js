/**
 * App shell behaviour shared by every server-rendered page, wired through data attributes:
 *
 *   [data-menu-toggle]            opens/closes the element named in aria-controls (mobile nav)
 *   [data-password-toggle]        shows/hides the password input in the same .field
 *   [data-spotlight]              light that follows the pointer (.spotlight-card)
 *   form[data-confirm="…"]        asks in the shared confirm dialog before submitting
 *   form[data-busy-text="…"]      disables the submit button and shows a busy label on submit
 *   [data-modal-open="id"]        opens <dialog id> ; [data-modal-close] closes the parent dialog
 *   [data-dismiss]                removes its closest [data-dismissible]
 *   [data-copy="text"]            copies text to the clipboard
 */

const strings = () => window.__ui?.strings || {};

export function toast(message, tone = 'signal') {
    let region = document.getElementById('toast-region');
    if (!region) return;
    const colors = { signal: 'bg-signal', safe: 'bg-safe', danger: 'bg-danger', amber: 'bg-amber' };
    const item = document.createElement('div');
    item.className = 'toast';
    item.setAttribute('role', 'status');
    item.innerHTML = `<span class="mt-1.5 h-2 w-2 flex-none rounded-full ${colors[tone] || colors.signal}"></span><span class="text-slate-100"></span>`;
    item.lastElementChild.textContent = message;
    region.appendChild(item);
    setTimeout(() => {
        item.style.transition = 'opacity .3s ease';
        item.style.opacity = '0';
        setTimeout(() => item.remove(), 320);
    }, 3800);
}

function menu() {
    document.querySelectorAll('[data-menu-toggle]').forEach((button) => {
        const panel = document.getElementById(button.getAttribute('aria-controls'));
        if (!panel) return;
        const set = (open) => {
            button.setAttribute('aria-expanded', String(open));
            panel.hidden = !open;
            button.setAttribute('aria-label', open ? strings().closeMenu : strings().openMenu);
            button.querySelector('[data-icon-open]')?.toggleAttribute('hidden', open);
            button.querySelector('[data-icon-close]')?.toggleAttribute('hidden', !open);
        };
        button.addEventListener('click', () => set(button.getAttribute('aria-expanded') !== 'true'));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') { set(false); button.focus(); }
        });
        window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => e.matches && set(false));
    });
}

function passwords() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = button.closest('.field')?.querySelector('input');
        if (!input) return;
        button.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute('aria-label', show ? strings().hidePassword : strings().showPassword);
            button.querySelector('[data-eye]')?.toggleAttribute('hidden', show);
            button.querySelector('[data-eye-off]')?.toggleAttribute('hidden', !show);
        });
    });
}

function spotlights() {
    if (window.matchMedia('(hover: none)').matches) return;
    document.addEventListener('pointermove', (event) => {
        const card = event.target.closest?.('[data-spotlight]');
        if (!card) return;
        const rect = card.getBoundingClientRect();
        card.style.setProperty('--x', `${event.clientX - rect.left}px`);
        card.style.setProperty('--y', `${event.clientY - rect.top}px`);
    }, { passive: true });
}

function confirmDialog() {
    const dialog = document.getElementById('confirm-dialog');
    let pending = null;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed) return;
        event.preventDefault();
        if (!dialog?.showModal) {
            // Very old browsers: fall back to the native prompt.
            if (window.confirm(form.dataset.confirm)) { form.dataset.confirmed = '1'; form.requestSubmit(event.submitter); }
            return;
        }
        pending = { form, submitter: event.submitter };
        dialog.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
        const accept = dialog.querySelector('[data-confirm-accept]');
        accept.textContent = form.dataset.confirmLabel || strings().confirm;
        accept.className = `btn ${form.dataset.confirmTone === 'danger' ? 'btn-danger' : 'btn-primary'}`;
        dialog.showModal();
        dialog.querySelector('[data-modal-close]')?.focus();
    }, true);

    dialog?.querySelector('[data-confirm-accept]')?.addEventListener('click', () => {
        if (!pending) return;
        const { form, submitter } = pending;
        pending = null;
        dialog.close();
        form.dataset.confirmed = '1';
        form.requestSubmit(submitter && form.contains(submitter) ? submitter : undefined);
    });
    dialog?.addEventListener('close', () => { pending = null; });
}

function busyForms() {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
        const button = event.submitter || form.querySelector('[type="submit"]');
        if (!button || !form.dataset.busyText) return;
        // Defer so the submitter's name/value is still sent with the form.
        requestAnimationFrame(() => {
            const label = button.querySelector('[data-label]') || button;
            button.dataset.idleText ??= label.textContent;
            button.disabled = true;
            button.dataset.busy = 'true';
            label.textContent = form.dataset.busyText;
        });
    });
    // Coming back through the back/forward cache: make the buttons usable again.
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) return;
        document.querySelectorAll('[data-busy="true"]').forEach((button) => {
            button.disabled = false;
            button.dataset.busy = 'false';
            const label = button.querySelector('[data-label]') || button;
            if (button.dataset.idleText) label.textContent = button.dataset.idleText;
        });
    });
}

function modals() {
    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-modal-open]');
        if (opener) {
            document.getElementById(opener.dataset.modalOpen)?.showModal();
            return;
        }
        const closer = event.target.closest('[data-modal-close]');
        if (closer) closer.closest('dialog')?.close();

        // Click on the backdrop closes the dialog.
        if (event.target instanceof HTMLDialogElement && event.target.classList.contains('modal')) {
            const rect = event.target.firstElementChild?.getBoundingClientRect();
            if (rect && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) {
                event.target.close();
            }
        }

        const dismiss = event.target.closest('[data-dismiss]');
        if (dismiss) dismiss.closest('[data-dismissible]')?.remove();

        const copy = event.target.closest('[data-copy]');
        if (copy) {
            navigator.clipboard?.writeText(copy.dataset.copy).then(() => toast(strings().copied || '✓', 'safe'));
        }
    });
}

function reopenInvalidDialogs() {
    // After a failed submit, the dialog that holds the submitted form opens again with its errors.
    const dialog = document.querySelector('dialog[data-open-on-load]');
    if (dialog?.showModal) {
        dialog.showModal();
        dialog.querySelector('[aria-invalid="true"]')?.focus();
    }
}

function headerShadow() {
    const header = document.querySelector('[data-app-header]');
    if (!header) return;
    const update = () => header.toggleAttribute('data-scrolled', window.scrollY > 8);
    update();
    window.addEventListener('scroll', update, { passive: true });
}

export function initShell() {
    menu();
    passwords();
    spotlights();
    confirmDialog();
    busyForms();
    modals();
    headerShadow();
    reopenInvalidDialogs();
    window.ui = { ...(window.ui || {}), toast };
}
