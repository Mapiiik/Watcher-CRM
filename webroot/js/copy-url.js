const COPY_FEEDBACK_MS = 1500;

/**
 * Puts text on the clipboard.
 *
 * The async clipboard API needs a secure context, so fall back to a throwaway
 * textarea when the page is served over plain HTTP.
 */
async function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);

        return;
    }

    const field = document.createElement('textarea');
    field.value = text;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.top = '-1000px';
    document.body.appendChild(field);
    field.select();

    try {
        if (!document.execCommand('copy')) {
            throw new Error('Copying to the clipboard was rejected.');
        }
    } finally {
        document.body.removeChild(field);
    }
}

// Delegated so the buttons work no matter when they are rendered.
document.addEventListener('click', async (event) => {
    const button = event.target.closest?.('[data-copy-url]');

    if (!button) {
        return;
    }

    event.preventDefault();

    const original = button.textContent;

    try {
        await copyText(button.dataset.copyUrl);
        button.textContent = button.dataset.copied || original;
        setTimeout(() => {
            button.textContent = original;
        }, COPY_FEEDBACK_MS);
    } catch {
        // Copying can be blocked by the browser. Show the URL so it can still be
        // selected by hand rather than leaving the button silently doing nothing.
        window.prompt(original, button.dataset.copyUrl);
    }
});
