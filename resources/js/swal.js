import Swal from 'sweetalert2';

// The website's palette, so every alert matches the admin.
const brand = Swal.mixin({
    background: '#112737',
    color: '#f4eee3',
    confirmButtonColor: '#c9a25b',
    cancelButtonColor: '#15334a',
    reverseButtons: true,
    customClass: { confirmButton: 'swal-confirm' },
});

export const confirmAction = ({ title, text, confirm, danger = false }) =>
    brand
        .fire({
            icon: danger ? 'warning' : 'question',
            iconColor: danger ? '#f0897d' : '#c9a25b',
            title,
            text,
            showCancelButton: true,
            confirmButtonText: confirm,
            confirmButtonColor: danger ? '#ba131a' : '#c9a25b',
            customClass: { confirmButton: danger ? '' : 'swal-confirm' },
            focusCancel: danger,
        })
        .then((result) => result.isConfirmed);

export const showError = (text) =>
    brand.fire({ icon: 'error', iconColor: '#f0897d', title: 'That did not work', text, confirmButtonText: 'OK' });

export const toast = (title) =>
    brand.fire({
        icon: 'success',
        iconColor: '#c9a25b',
        title,
        toast: true,
        position: 'top-end',
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false,
    });

/**
 * fetch with the CSRF token and JSON errors. Resolves to the JSON body; rejects with
 * { status, message, errors } so callers can show field errors or a Swal.
 */
export async function send(url, { method = 'POST', body } = {}) {
    const json = body && !(body instanceof FormData);

    let response;

    try {
        response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                ...(json ? { 'Content-Type': 'application/json' } : {}),
            },
            body: json ? JSON.stringify(body) : body,
        });
    } catch {
        throw { status: 0, message: 'Could not reach the server. Check your connection and try again.' };
    }

    const data = await response.json().catch(() => ({}));

    if (response.ok) {
        return data;
    }

    const messages = {
        401: 'You have been signed out. Please sign in again.',
        413: 'These files are too large for the server. Export smaller copies and try again.',
        419: 'This page has expired. Reload it and try again.',
    };

    throw {
        status: response.status,
        errors: data.errors,
        message: messages[response.status] ?? (response.status === 422 ? data.message : 'Something went wrong on the server. Please try again.'),
    };
}

// A message saved by the server before the page reloaded.
const flash = document.body.dataset.flash;

if (flash) {
    toast(flash);
}
