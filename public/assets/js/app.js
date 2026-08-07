document.addEventListener('click', async (event) => {
    const copyButton = event.target.closest('.copy-button');

    if (copyButton) {
        const copyLabel = copyButton.querySelector('[data-copy-label]');
        const originalText = copyLabel ? copyLabel.textContent : copyButton.textContent;
        const setCopyLabel = (text) => {
            if (copyLabel) {
                copyLabel.textContent = text;
            } else {
                copyButton.textContent = text;
            }
        };

        copyButton.disabled = true;
        copyButton.classList.remove('is-copied', 'is-copy-error');
        copyButton.classList.add('is-copying');
        setCopyLabel('Copying prompt');

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

            copyButton.classList.add('is-copied');
            setCopyLabel('Prompt copied');
        } catch (error) {
            copyButton.classList.add('is-copy-error');
            setCopyLabel(error.message || 'Copy failed');
        } finally {
            copyButton.classList.remove('is-copying');

            window.setTimeout(() => {
                copyButton.disabled = false;
                copyButton.classList.remove('is-copied', 'is-copy-error');
                setCopyLabel(originalText);
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
    const siteHeader = document.querySelector('[data-site-header]');

    if (!siteHeader) {
        return;
    }

    const navToggle = siteHeader.querySelector('[data-site-nav-toggle]');
    const navMenu = siteHeader.querySelector('[data-site-nav-menu]');
    const navLinks = Array.from(siteHeader.querySelectorAll('[data-home-nav]'));
    const sections = navLinks
        .map((link) => ({
            key: link.dataset.homeNav,
            link,
            section: document.querySelector(`[data-home-section="${link.dataset.homeNav}"]`)
        }))
        .filter((item) => item.section);
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const mobileNavigation = window.matchMedia('(max-width: 860px)');
    let frameRequested = false;

    const setMenuOpen = (isOpen) => {
        if (!navToggle || !navMenu) {
            return;
        }

        navMenu.classList.toggle('is-open', isOpen);
        navToggle.setAttribute('aria-expanded', String(isOpen));

        const label = navToggle.querySelector('.site-nav-toggle-label');

        if (label) {
            label.textContent = isOpen ? 'Close' : 'Menu';
        }
    };

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
        siteHeader.classList.toggle('is-scrolled', window.scrollY > 18);

        if (sections.length === 0) {
            frameRequested = false;
            return;
        }

        const activationLine = siteHeader.getBoundingClientRect().bottom + 80;
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

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            const isOpen = navToggle.getAttribute('aria-expanded') !== 'true';
            setMenuOpen(isOpen);
        });

        navMenu.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setMenuOpen(false));
        });

        document.addEventListener('click', (event) => {
            if (!mobileNavigation.matches
                || navToggle.getAttribute('aria-expanded') !== 'true'
                || siteHeader.contains(event.target)) {
                return;
            }

            setMenuOpen(false);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || navToggle.getAttribute('aria-expanded') !== 'true') {
                return;
            }

            setMenuOpen(false);
            navToggle.focus();
        });
    }

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
    window.addEventListener('resize', () => {
        if (!mobileNavigation.matches) {
            setMenuOpen(false);
        }

        requestNavigationUpdate();
    });
    updateFloatingNavigation();
})();
