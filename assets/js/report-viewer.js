document.addEventListener('DOMContentLoaded', function () {
    var viewer = document.getElementById('reportViewer');
    if (!viewer) return;

    var prevBtn = document.getElementById('prevPartBtn');
    var nextBtn = document.getElementById('nextPartBtn');
    var currentPartNum = document.getElementById('currentPartNum');
    var stepPills = document.getElementById('stepPills');

    var partCards = viewer.querySelectorAll('.part-card');
    var totalParts = parseInt(viewer.dataset.totalParts, 10) || partCards.length;
    var currentPart = 1;

    function showPart(index) {
        if (index < 1 || index > totalParts || index === currentPart) return;
        var oldPart = document.getElementById('part' + currentPart);
        var newPart = document.getElementById('part' + index);
        if (!oldPart || !newPart) return;
        oldPart.style.display = 'none';
        newPart.style.display = 'block';
        currentPart = index;
        updateStepper();
        updateNavButtons();
        if (currentPartNum) currentPartNum.textContent = currentPart;
        scrollToPart(newPart);
    }

    function scrollToPart(partEl) {
        var top = partEl.getBoundingClientRect().top + window.scrollY - 100;
        window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
    }

    function updateStepper() {
        if (!stepPills) return;
        stepPills.querySelectorAll('.step-pill').forEach(function (pill) {
            var idx = parseInt(pill.dataset.part, 10);
            var isActive = idx === currentPart;
            pill.classList.toggle('step-pill--active', isActive);
            pill.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        stepPills.querySelectorAll('.step-connector').forEach(function (conn, i) {
            var partIdx = i + 1;
            var pill = stepPills.querySelector('.step-pill[data-part="' + partIdx + '"]');
            conn.classList.toggle('step-connector--done', pill && pill.classList.contains('step-pill--done'));
        });
    }

    function updateNavButtons() {
        if (prevBtn) prevBtn.disabled = currentPart <= 1;
        if (nextBtn) {
            if (currentPart >= totalParts) {
                nextBtn.disabled = true;
                nextBtn.innerHTML = '<i class="bi bi-check-lg"></i> Complete';
            } else {
                nextBtn.disabled = false;
                nextBtn.innerHTML = 'Next <i class="bi bi-chevron-right"></i>';
            }
        }
    }

    if (stepPills) {
        stepPills.addEventListener('click', function (e) {
            var pill = e.target.closest('.step-pill');
            if (!pill) return;
            var idx = parseInt(pill.dataset.part, 10);
            if (idx !== currentPart) showPart(idx);
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            if (currentPart > 1) showPart(currentPart - 1);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            if (currentPart < totalParts) showPart(currentPart + 1);
        });
    }

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'ArrowLeft' && currentPart > 1) {
            e.preventDefault();
            showPart(currentPart - 1);
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'ArrowRight' && currentPart < totalParts) {
            e.preventDefault();
            showPart(currentPart + 1);
        }
    });

    updateStepper();
    updateNavButtons();
});
