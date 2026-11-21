// Localize browser constraint errors and keep feedback beside its own field.
document.addEventListener('invalid', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.matches('[data-validation-required]')) return;
    const error = input.closest('[data-input-validation]')?.querySelector('[data-input-error]');
    if (!error) return;
    event.preventDefault();
    const validity = input.validity;
    let message = input.dataset.validationFormat;
    if (validity.valueMissing) message = input.dataset.validationRequired;
    else if (validity.typeMismatch && input.type === 'email') message = input.dataset.validationEmail;
    else if (validity.typeMismatch && input.type === 'url') message = input.dataset.validationUrl;
    else if (validity.tooShort) message = input.dataset.validationMin;
    else if (validity.tooLong) message = input.dataset.validationMax;
    error.textContent = message;
    error.classList.remove('hidden');
    input.setAttribute('aria-invalid', 'true');
    // Focus only the first invalid field in this form.
    if (input.form?.querySelector(':invalid') === input) input.focus();
}, true);

document.addEventListener('input', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.matches('[data-validation-required]')) return;
    input.removeAttribute('aria-invalid');
    const error = input.closest('[data-input-validation]')?.querySelector('[data-input-error]');
    if (error) {
        error.textContent = '';
        error.classList.add('hidden');
    }
});
