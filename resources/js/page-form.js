import { confirmAction, send, showError, toast } from './swal';

// The single-record screens (Contact details, Settings): confirm, save, then a toast after reload.
const form = document.getElementById('page-form');
const saveButton = form.querySelector('[type="submit"]');

function clearErrors() {
    form.querySelectorAll('[data-error]').forEach((el) => {
        el.textContent = '';
        el.hidden = true;
    });
    form.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
}

function showErrors(errors) {
    for (const [name, messages] of Object.entries(errors)) {
        const slot = form.querySelector(`[data-error="${name.split('.')[0]}"]`);

        if (slot) {
            slot.textContent = messages[0];
            slot.hidden = false;
            form.elements[name]?.setAttribute?.('aria-invalid', 'true');
        }
    }

    const first = form.querySelector('[data-error]:not([hidden])');
    first?.closest('div')?.scrollIntoView({ block: 'center', behavior: 'smooth' });
    form.querySelector('[aria-invalid]')?.focus({ preventScroll: true });
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearErrors();

    const confirmed = await confirmAction({ title: form.dataset.confirmTitle, text: form.dataset.confirmText, confirm: 'Yes, save' });

    if (!confirmed) {
        return;
    }

    const label = saveButton.textContent;
    saveButton.disabled = true;
    saveButton.textContent = 'Saving…';

    try {
        await send(form.action, { body: new FormData(form) });
        location.reload();
    } catch (error) {
        saveButton.disabled = false;
        saveButton.textContent = label;

        if (error.errors) {
            showErrors(error.errors);
        } else {
            showError(error.message);
        }
    }
});

form.querySelectorAll('input[type="file"][data-preview]').forEach((input) => {
    input.addEventListener('change', () => {
        const preview = document.getElementById(input.dataset.preview);

        if (input.files[0]) {
            preview.src = URL.createObjectURL(input.files[0]);
            preview.hidden = false;
        }
    });
});

const testButton = document.getElementById('test-mail');

testButton?.addEventListener('click', async () => {
    const confirmed = await confirmAction({
        title: 'Send a test email?',
        text: `It uses the saved settings and goes to ${testButton.dataset.recipient || 'the saved recipient'}. Save any changes first.`,
        confirm: 'Yes, send',
    });

    if (!confirmed) {
        return;
    }

    const label = testButton.textContent;
    testButton.disabled = true;
    testButton.textContent = 'Sending…';

    try {
        const { message } = await send(testButton.dataset.url);
        toast(message);
    } catch (error) {
        showError(error.message);
    } finally {
        testButton.disabled = false;
        testButton.textContent = label;
    }
});
