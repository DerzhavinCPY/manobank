import { openDialog, closeDialogs } from '../site/dialogs';

export function initEmail() {
    const open = document.querySelector('[data-email-open]');
    if (!open) return;
    const form = document.querySelector('[data-email-form]');
    const error = document.querySelector('[data-email-error]');
    const notice = document.querySelector('[data-email-notice]');
    let timeout;
    open.addEventListener('click', () => {
        if (!open.dataset.calculationId) return;
        form.reset(); error.hidden = true; openDialog('email-dialog');
    });
    document.querySelector('[data-notice-close]').addEventListener('click', () => { notice.hidden = true; clearTimeout(timeout); });
    form.addEventListener('submit', async event => {
        event.preventDefault(); error.hidden = true;
        const button = form.querySelector('[type=submit]'); button.disabled = true;
        try {
            const response = await fetch(`/calculations/${encodeURIComponent(open.dataset.calculationId)}/email`, {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ email: form.elements.email.value }),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors?.email?.[0] || data.message || 'Не удалось отправить письмо. Попробуйте позднее.');
            closeDialogs(); form.reset(); notice.hidden = false; clearTimeout(timeout);
            timeout = setTimeout(() => { notice.hidden = true; }, 5000);
        } catch (failure) {
            error.textContent = failure instanceof TypeError || failure instanceof SyntaxError ? 'Не удалось отправить письмо. Попробуйте позднее.' : failure.message;
            error.hidden = false;
        } finally { button.disabled = false; }
    });
}
