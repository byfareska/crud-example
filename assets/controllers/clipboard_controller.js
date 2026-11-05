import { Controller } from '@hotwired/stimulus';

/*
 * The button of CopyAction: copies its "text" value to the clipboard. The icon turns into a tick for a moment and the
 * panel toast says it (texts as values - JS has none):
 *
 * <button data-controller="clipboard" data-action="clipboard#copy" data-clipboard-text-value="…"
 *         data-clipboard-done-value="Skopiowano" data-clipboard-failed-value="Nie udało się skopiować">
 *     <i class="bi bi-copy"></i></button>
 */
export default class extends Controller {
    static values = { text: String, done: String, failed: String };

    async copy() {
        try {
            await navigator.clipboard.writeText(this.textValue.trim());
        } catch {
            this.toast(this.failedValue);
            return;
        }

        this.toast(this.doneValue);
        const icon = this.element.querySelector('.bi-copy');
        if (icon) {
            icon.classList.replace('bi-copy', 'bi-check2');
            clearTimeout(this.timer);
            this.timer = setTimeout(() => icon.classList.replace('bi-check2', 'bi-copy'), 1500);
        }
    }

    toast(title) {
        if (title) {
            window.dispatchEvent(new CustomEvent('toast:show', { detail: { title } }));
        }
    }

    disconnect() {
        clearTimeout(this.timer);
    }
}
