function plainMoney(value) { return value.replace(/[\s\u00a0\u202f]/g, '').replace(',', '.'); }

function updateAmountLimit(input) {
    const value = plainMoney(input.value);
    input.classList.toggle('amount-over-limit', /^\d+(?:\.\d+)?$/.test(value) && Number(value) > Number(input.dataset.maxAmount));
}

function updatePercent(input) {
    const hasValue = input.value !== '';
    input.classList.toggle('has-value', hasValue);
    input.style.width = hasValue ? `${Math.max(1, input.value.length)}ch` : '';
    input.parentElement.querySelector('[data-percent-suffix]').hidden = !hasValue;
}

function updateRange(range) {
    const percentage = 100 * (Number(range.value) - Number(range.min)) / (Number(range.max) - Number(range.min));
    range.style.setProperty('--progress', `${percentage}%`);
}

export function initCalculator() {
    const form = document.querySelector('[data-calculator]');
    if (!form) return;
    const email = document.querySelector('[data-email-open]');
    const error = form.querySelector('[data-calculation-error]');
    let revision = 0;
    const clearResults = () => {
        document.querySelectorAll('[data-metric-value], [data-retirement-balance]').forEach(node => { node.textContent = node.dataset.zeroValue; });
        const comparison = document.querySelector('[data-income-comparison]');
        comparison.hidden = true; comparison.textContent = '';
        const down = form.querySelector('[data-down-amount]');
        if (down) down.textContent = '0 ₽';
    };
    const invalidate = () => { revision++; delete email.dataset.calculationId; email.disabled = true; clearResults(); };
    form.addEventListener('input', invalidate);
    form.addEventListener('change', invalidate);
    form.addEventListener('keydown', event => {
        if (event.key === 'Enter' && event.target instanceof HTMLInputElement) event.preventDefault();
    });
    const limitedAmounts = form.querySelectorAll('[data-max-amount]');
    limitedAmounts.forEach(input => {
        input.addEventListener('input', () => updateAmountLimit(input));
        updateAmountLimit(input);
    });
    form.querySelectorAll('[data-money-input]').forEach(input => input.addEventListener('blur', () => {
        const value = plainMoney(input.value);
        if (/^\d+(\.\d{1,2})?$/.test(value)) {
            const [whole, fraction] = value.split('.');
            input.value = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + (fraction ? `,${fraction}` : '');
        }
    }));
    form.querySelectorAll('[data-percent-input]').forEach(input => input.addEventListener('input', () => updatePercent(input)));
    const years = form.querySelector('[data-years-input]');
    const range = form.querySelector('[data-years-range]');
    if (range) {
        range.addEventListener('input', () => { years.value = range.value; updateRange(range); });
        years.addEventListener('input', () => {
            if (/^\d+$/.test(years.value) && Number(years.value) >= Number(range.min) && Number(years.value) <= Number(range.max)) {
                range.value = years.value; updateRange(range);
            }
        });
    }
    const clearErrors = () => {
        error.hidden = true;
        form.querySelectorAll('[data-error]').forEach(node => { node.textContent = ''; });
        form.querySelectorAll('[aria-invalid]').forEach(node => node.removeAttribute('aria-invalid'));
    };
    form.addEventListener('reset', event => {
        event.preventDefault();
        invalidate(); clearErrors();
        form.querySelectorAll('input:not([type=hidden])').forEach(input => {
            if (input.type === 'radio' || input.type === 'checkbox') input.checked = false;
            else if (input.type !== 'range') input.value = '';
        });
        form.querySelectorAll('[data-percent-input]').forEach(updatePercent);
        limitedAmounts.forEach(updateAmountLimit);
        if (range) { range.value = range.min; updateRange(range); }
    });
    form.addEventListener('submit', async event => {
        event.preventDefault(); invalidate(); clearErrors();
        const requestRevision = revision;
        const button = form.querySelector('[type=submit]');
        button.disabled = true;
        const data = Object.fromEntries(new FormData(form).entries());
        for (const key of ['amount', 'salary', 'down_payment']) if (key in data) data[key] = plainMoney(data[key]);
        try {
            const response = await fetch(form.action, { method: 'POST', headers: {
                'Content-Type': 'application/json', 'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            }, body: JSON.stringify(data) });
            const result = await response.json();
            if (requestRevision !== revision) return;
            if (!response.ok) {
                for (const [name, messages] of Object.entries(result.errors || {})) {
                    const target = form.querySelector(`[data-error="${name}"]`);
                    if (target) target.textContent = messages[0];
                    const input = form.elements.namedItem(name);
                    if (input instanceof HTMLElement) input.setAttribute('aria-invalid', 'true');
                }
                throw new Error(response.status === 422 ? 'Проверьте введённые данные.' : 'Расчёт недоступен. Попробуйте ещё раз.');
            }
            const metrics = document.querySelectorAll('.metric');
            result.metrics.forEach((metric, index) => {
                metrics[index].querySelector('[data-metric-label]').textContent = metric.label;
                metrics[index].querySelector('[data-metric-value]').textContent = metric.value;
            });
            const retirementBalance = document.querySelector('[data-retirement-balance]');
            if (retirementBalance) retirementBalance.textContent = result.summary.balance_at_retirement;
            const down = form.querySelector('[data-down-amount]');
            if (down) down.textContent = result.summary.down_payment;
            const comparison = document.querySelector('[data-income-comparison]');
            comparison.hidden = !result.income_comparison;
            comparison.textContent = result.income_comparison ? (result.income_comparison.sufficient ? 'Ваша зарплата соответствует рекомендуемому доходу.' : 'Ваша зарплата ниже рекомендуемого дохода.') : '';
            email.dataset.calculationId = result.id; email.disabled = false;
        } catch (failure) {
            if (requestRevision !== revision) return;
            error.textContent = failure instanceof TypeError || failure instanceof SyntaxError ? 'Не удалось получить расчёт. Проверьте соединение и повторите попытку.' : failure.message;
            error.hidden = false;
        } finally { button.disabled = false; }
    });
}
