document.addEventListener('click', async (event) => {
    const copyButton = event.target.closest('.copy-button');

    if (copyButton) {
        const originalText = copyButton.textContent;
        copyButton.disabled = true;
        copyButton.textContent = 'Copying';

        try {
            const response = await fetch(copyButton.dataset.copyUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({ _token: copyButton.dataset.csrf || '' })
            });
            const data = await response.json();

            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Copy failed');
            }

            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(data.prompt);
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = data.prompt;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                textarea.remove();
            }

            copyButton.textContent = 'Copied';
        } catch (error) {
            copyButton.textContent = error.message || 'Copy failed';
        } finally {
            window.setTimeout(() => {
                copyButton.disabled = false;
                copyButton.textContent = originalText;
            }, 1400);
        }
    }

    const checkAll = event.target.closest('[data-check-all]');

    if (checkAll) {
        document.querySelectorAll('input[name="ids[]"]').forEach((checkbox) => {
            checkbox.checked = checkAll.checked;
        });
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    const message = form.dataset.confirm;

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

