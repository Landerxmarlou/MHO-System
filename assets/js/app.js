document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
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
