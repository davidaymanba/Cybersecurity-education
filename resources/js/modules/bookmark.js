import { toast } from '../ui/shell';
import { gsap, prefersReducedMotion } from '../ui/motion';

/** Bookmark toggle on the lesson page (POST bookmarks.toggle, JSON response). */
export function initBookmark(button) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const label = button.querySelector('[data-label]');

    const render = (active) => {
        button.setAttribute('aria-pressed', String(active));
        label.textContent = active ? button.dataset.labelOn : button.dataset.labelOff;
    };

    button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            const response = await fetch(button.dataset.url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
            });
            if (!response.ok) throw new Error(String(response.status));
            const data = await response.json();
            render(data.bookmarked);
            toast(data.bookmarked ? button.dataset.labelOn : (window.__ui?.strings?.bookmarkRemoved || button.dataset.labelOff), data.bookmarked ? 'safe' : 'signal');
            if (!prefersReducedMotion()) gsap.fromTo(button.querySelector('svg'), { scale: 0.6 }, { scale: 1, duration: 0.5, ease: 'back.out(3)' });
        } catch {
            toast(window.__ui?.strings?.genericError || 'Error', 'danger');
        } finally {
            button.disabled = false;
        }
    });
}
