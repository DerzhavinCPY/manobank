const form = document.querySelector('[data-calculation-export]');

if (form) {
    const button = form.querySelector('button[type="submit"]');
    const updateSelection = () => {
        button.disabled = !form.querySelector('input[name="selected[]"]:checked');
    };
    form.addEventListener('change', updateSelection);
    form.addEventListener('submit', event => {
        if (!form.querySelector('input[name="selected[]"]:checked')) event.preventDefault();
    });
    window.addEventListener('pageshow', updateSelection);
    updateSelection();
}
