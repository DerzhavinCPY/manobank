let previousSection = null;
let opener = null;

export function openDialog(id, account = false) {
    document.querySelectorAll('dialog[open]').forEach(dialog => dialog.close('transition'));
    const dialog = document.getElementById(id);
    if (!dialog) return;
    if (account && previousSection === null) {
        previousSection = document.querySelector('[data-nav].is-active')?.dataset.nav || '';
        document.querySelectorAll('[data-nav]').forEach(link => {
            const active = link.dataset.nav === 'account';
            link.classList.toggle('is-active', active);
            if (active) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });
    }
    dialog.showModal();
}

export function closeDialogs() {
    document.querySelectorAll('dialog[open]').forEach(dialog => dialog.close('finished'));
    document.querySelectorAll('[data-account-phone], [data-account-password], [data-card-number]').forEach(input => { input.value = ''; });
    document.querySelector('[data-account-error]').hidden = true;
    if (previousSection !== null) {
        document.querySelectorAll('[data-nav]').forEach(link => {
            const active = link.dataset.nav === previousSection;
            link.classList.toggle('is-active', active);
            if (active) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });
        previousSection = null;
    }
    opener?.focus();
}

export function initDialogs() {
    document.querySelectorAll('[data-account-open]').forEach(link => link.addEventListener('click', event => {
        event.preventDefault(); opener = link; openDialog('account-dialog', true);
    }));
    document.querySelectorAll('[data-dialog-close]').forEach(button => button.addEventListener('click', closeDialogs));
    document.querySelectorAll('dialog').forEach(dialog => dialog.addEventListener('cancel', event => {
        event.preventDefault(); closeDialogs();
    }));
    const phone = document.querySelector('[data-account-phone]');
    const password = document.querySelector('[data-account-password]');
    document.querySelector('[data-account-login]').addEventListener('click', () => {
        if (!phone.value.trim()) { phone.focus(); return; }
        if (!password.value) { password.focus(); return; }
        document.querySelector('[data-account-error]').hidden = false;
        password.value = '';
    });
    document.querySelector('[data-register-open]').addEventListener('click', () => {
        password.value = ''; openDialog('register-dialog', true);
    });
    document.querySelector('[data-register-check]').addEventListener('click', () => {
        const card = document.querySelector('[data-card-number]');
        if (!card.value.trim()) { card.focus(); return; }
        card.value = ''; openDialog('card-error-dialog', true);
    });
    document.querySelector('[data-card-back]').addEventListener('click', () => openDialog('register-dialog', true));
    if (document.body.hasAttribute('data-open-account')) openDialog('account-dialog', true);
}
