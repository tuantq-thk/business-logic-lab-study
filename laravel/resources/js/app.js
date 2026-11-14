document.querySelectorAll('[data-quote-form]').forEach((form) => {
    form.querySelectorAll('[data-quote-preset]').forEach((preset) => {
        preset.addEventListener('click', () => {
            const quantities = preset.dataset.quoteQuantities.split(',');
            const rules = preset.dataset.quoteRules ? preset.dataset.quoteRules.split(',') : [];

            form.querySelector('[name="quantities[book-php]"]').value = quantities[0];
            form.querySelector('[name="quantities[book-sql]"]').value = quantities[1];
            form.querySelectorAll('[name="rules[]"]').forEach((checkbox) => {
                checkbox.checked = rules.includes(checkbox.value);
            });
        });
    });

    form.addEventListener('submit', () => {
        const submit = form.querySelector('[data-quote-submit]');

        if (! submit) {
            return;
        }

        submit.disabled = true;
        submit.textContent = submit.dataset.loadingLabel;
        submit.classList.add('cursor-wait', 'opacity-70');
    });
});

document.querySelectorAll('[data-inventory-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const submit = form.querySelector('[data-inventory-submit]');

        if (! submit) {
            return;
        }

        submit.disabled = true;
        submit.textContent = submit.dataset.loadingLabel;
        submit.setAttribute('aria-busy', 'true');
        form.setAttribute('aria-busy', 'true');
        const status = form.querySelector('[data-calendar-status]');

        if (status) {
            status.textContent = submit.dataset.loadingLabel;
        }
        submit.classList.add('cursor-wait', 'opacity-70');
    });
});

document.querySelectorAll('[data-calendar-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const submit = form.querySelector('[data-calendar-submit]');

        if (! submit) {
            return;
        }

        submit.disabled = true;
        submit.textContent = submit.dataset.loadingLabel;
        submit.setAttribute('aria-busy', 'true');
        form.setAttribute('aria-busy', 'true');
        submit.classList.add('cursor-wait', 'opacity-70');
    });
});
