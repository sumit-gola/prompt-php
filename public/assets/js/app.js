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

(() => {
    const floatingHeader = document.querySelector('[data-floating-header]');

    if (!floatingHeader) {
        return;
    }

    const navLinks = Array.from(floatingHeader.querySelectorAll('[data-home-nav]'));
    const sections = navLinks
        .map((link) => ({
            key: link.dataset.homeNav,
            link,
            section: document.querySelector(`[data-home-section="${link.dataset.homeNav}"]`)
        }))
        .filter((item) => item.section);
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let frameRequested = false;

    const setActiveLink = (key) => {
        sections.forEach((item) => {
            const isActive = item.key === key;
            item.link.classList.toggle('is-active', isActive);

            if (isActive) {
                item.link.setAttribute('aria-current', 'location');
            } else {
                item.link.removeAttribute('aria-current');
            }
        });
    };

    const updateFloatingNavigation = () => {
        floatingHeader.classList.toggle('is-scrolled', window.scrollY > 18);

        const activationLine = floatingHeader.getBoundingClientRect().bottom + 80;
        let activeKey = sections[0]?.key;

        sections.forEach((item) => {
            if (item.section.getBoundingClientRect().top <= activationLine) {
                activeKey = item.key;
            }
        });

        if (activeKey) {
            setActiveLink(activeKey);
        }

        frameRequested = false;
    };

    const requestNavigationUpdate = () => {
        if (frameRequested) {
            return;
        }

        frameRequested = true;
        window.requestAnimationFrame(updateFloatingNavigation);
    };

    navLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const target = document.querySelector(link.hash);

            if (!target) {
                return;
            }

            event.preventDefault();
            setActiveLink(link.dataset.homeNav);
            target.scrollIntoView({
                behavior: prefersReducedMotion.matches ? 'auto' : 'smooth',
                block: 'start'
            });
            window.history.replaceState(null, '', link.hash);
        });
    });

    window.addEventListener('scroll', requestNavigationUpdate, { passive: true });
    window.addEventListener('resize', requestNavigationUpdate);
    updateFloatingNavigation();
})();
