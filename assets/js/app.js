document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const minimizeBtn = document.getElementById('sidebarMinimize');
    const STORAGE_KEY = 'mho-sidebar-collapsed';
    const DESKTOP_BREAKPOINT = 992;

    function isDesktop() {
        return window.innerWidth >= DESKTOP_BREAKPOINT;
    }

    function isCollapsed() {
        return document.body.classList.contains('sidebar-collapsed');
    }

    function updateMinimizeButton() {
        if (!minimizeBtn) return;
        const collapsed = isCollapsed();
        const icon = minimizeBtn.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-chevron-left', !collapsed);
            icon.classList.toggle('bi-chevron-right', collapsed);
        }
        minimizeBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        minimizeBtn.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Minimize sidebar');
    }

    function setCollapsed(collapsed, persist) {
        if (!isDesktop()) {
            document.body.classList.remove('sidebar-collapsed');
            updateMinimizeButton();
            return;
        }
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        updateMinimizeButton();
        if (persist) {
            try {
                localStorage.setItem(STORAGE_KEY, collapsed ? 'true' : 'false');
            } catch (e) {}
        }
    }

    function restoreCollapsedState() {
        if (!minimizeBtn) return;
        let saved = false;
        try {
            saved = localStorage.getItem(STORAGE_KEY) === 'true';
        } catch (e) {}
        if (isDesktop() && saved) {
            document.body.classList.add('sidebar-collapsed');
        } else if (!isDesktop()) {
            document.body.classList.remove('sidebar-collapsed');
        }
        updateMinimizeButton();
    }

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    if (minimizeBtn) {
        restoreCollapsedState();
        minimizeBtn.addEventListener('click', function () {
            setCollapsed(!isCollapsed(), true);
        });
        window.addEventListener('resize', function () {
            if (!isDesktop()) {
                document.body.classList.remove('sidebar-collapsed');
                updateMinimizeButton();
                return;
            }
            let saved = false;
            try {
                saved = localStorage.getItem(STORAGE_KEY) === 'true';
            } catch (e) {}
            document.body.classList.toggle('sidebar-collapsed', saved);
            updateMinimizeButton();
        });
    }

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const targetId = button.getAttribute('data-password-toggle');
            const input = targetId ? document.getElementById(targetId) : null;
            if (!input) return;

            const icon = button.querySelector('i');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');

            if (icon) {
                icon.classList.toggle('bi-eye', !isHidden);
                icon.classList.toggle('bi-eye-slash', isHidden);
            }
        });
    });

    const saveStatus = document.getElementById('saveStatus');
    if (saveStatus) {
        window.showSaveStatus = function (msg, type) {
            saveStatus.className = 'alert alert-' + (type || 'info') + ' py-1 px-3 mb-0 small';
            saveStatus.textContent = msg;
            saveStatus.classList.remove('d-none');
            setTimeout(function () {
                saveStatus.classList.add('d-none');
            }, 3000);
        };
    }
});
