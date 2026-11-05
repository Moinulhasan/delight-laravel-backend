import Sortable from 'sortablejs';
import { confirmAction, send, showError, toast } from './swal';

const dialog = document.getElementById('item-dialog');
const form = document.getElementById('item-form');
const list = document.getElementById('item-list');
const title = document.getElementById('item-dialog-title');
const saveButton = document.getElementById('item-save');
const baseUrl = form.dataset.url;

export function setPreview(name, url) {
    const preview = document.getElementById(`${name}-preview`);
    preview.src = url ?? '';
    preview.hidden = !url;
}

export function clearErrors(scope) {
    scope.querySelectorAll('[data-error]').forEach((el) => {
        el.textContent = '';
        el.hidden = true;
    });
    scope.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
}

/** Puts server validation messages next to their fields. */
export function showErrors(scope, errors) {
    for (const [name, messages] of Object.entries(errors)) {
        const slot = scope.querySelector(`[data-error="${name.split('.')[0]}"]`);

        if (slot) {
            slot.textContent = messages[0];
            slot.hidden = false;
            scope.elements[name]?.setAttribute?.('aria-invalid', 'true');
        }
    }
    scope.querySelector('[aria-invalid]')?.focus();
}

/**
 * The list screens' shared behaviour: a create/edit pop-up, confirmed actions, and reordering.
 * `fill(form, record)` puts a record (or null for a new one) into the pop-up's fields.
 */
export function setupCrud({ noun, where, files = [], checkboxes = [], fill }) {
    let editingId = null;

    function open(record = null) {
        form.reset();
        clearErrors(form);
        editingId = record?.id ?? null;
        title.textContent = record ? `Edit ${noun}` : `Add ${noun}`;
        saveButton.textContent = record ? 'Save changes' : `Add ${noun}`;
        fill(form, record);
        dialog.showModal();
    }

    document.getElementById('add-item').addEventListener('click', () => open());
    dialog.querySelectorAll('[data-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));

    for (const name of files) {
        form.elements[name].addEventListener('change', (event) => {
            const file = event.target.files[0];

            if (file) {
                setPreview(name, URL.createObjectURL(file));
            }
        });
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(form);

        const body = new FormData(form);
        // Multipart uploads only reach PHP over POST, so an update says PUT through Laravel's _method.
        if (editingId) {
            body.append('_method', 'PUT');
        }
        for (const name of checkboxes) {
            body.set(name, form.elements[name].checked ? '1' : '0');
        }

        const label = saveButton.textContent;
        saveButton.disabled = true;
        saveButton.textContent = 'Saving…';

        try {
            await send(editingId ? `${baseUrl}/${editingId}` : baseUrl, { body });
            location.reload();
        } catch (error) {
            saveButton.disabled = false;
            saveButton.textContent = label;

            if (error.errors) {
                showErrors(form, error.errors);

                return;
            }

            // The native dialog sits on the top layer and would cover the alert, so close it first.
            dialog.close();
            showError(error.message);
        }
    });

    async function run(url, method) {
        try {
            await send(url, { method });
            location.reload();
        } catch (error) {
            showError(error.message);
        }
    }

    const actions = {
        edit: (row) => open(JSON.parse(row.dataset.record)),

        toggle: async (row) => {
            const active = row.dataset.active === '1';
            const confirmed = await confirmAction(
                active
                    ? { title: `Deactivate this ${noun}?`, text: `It will disappear from the ${where} within about a minute. You can activate it again at any time.`, confirm: 'Yes, deactivate', danger: true }
                    : { title: `Activate this ${noun}?`, text: `It will appear on the ${where} within about a minute.`, confirm: 'Yes, activate' },
            );

            if (confirmed) {
                run(`${baseUrl}/${row.dataset.id}/toggle`, 'PATCH');
            }
        },

        delete: async (row) => {
            const confirmed = await confirmAction({
                title: `Delete this ${noun}?`,
                text: `The ${noun} and its image files will be removed for good. This cannot be undone.`,
                confirm: 'Yes, delete',
                danger: true,
            });

            if (confirmed) {
                run(`${baseUrl}/${row.dataset.id}`, 'DELETE');
            }
        },
    };

    list?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action]');

        if (button) {
            actions[button.dataset.action](button.closest('[data-id]'));
        }
    });

    /* Reorder: drag the handle, or arrow keys while it has focus */

    async function saveOrder() {
        const ids = [...list.querySelectorAll('[data-id]')].map((row) => Number(row.dataset.id));

        try {
            const { message } = await send(`${baseUrl}/reorder`, { body: { ids } });
            toast(message);
        } catch (error) {
            await showError(error.message);
            location.reload();
        }
    }

    if (!list) {
        return;
    }

    Sortable.create(list, {
        handle: '[data-handle]',
        animation: 180,
        ghostClass: 'opacity-40',
        onEnd: (event) => {
            if (event.oldIndex !== event.newIndex) {
                saveOrder();
            }
        },
    });

    list.addEventListener('keydown', (event) => {
        if (!event.target.matches('[data-handle]') || !['ArrowUp', 'ArrowDown'].includes(event.key)) {
            return;
        }

        event.preventDefault();

        const row = event.target.closest('[data-id]');
        const neighbour = event.key === 'ArrowUp' ? row.previousElementSibling : row.nextElementSibling;

        if (neighbour) {
            event.key === 'ArrowUp' ? neighbour.before(row) : neighbour.after(row);
            event.target.focus();
            saveOrder();
        }
    });
}
