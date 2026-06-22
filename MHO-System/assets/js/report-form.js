document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('reportForm');
    if (!form) return;

    var submissionId = form.dataset.submissionId;
    var saveBtn = document.getElementById('saveDraftBtn');
    var submitBtn = document.getElementById('submitReportBtn');
    var copyBtn = document.getElementById('copyPrevBtn');
    var saveStatus = document.getElementById('saveStatus');
    var searchInput = document.getElementById('indicatorSearch');
    var searchClear = document.getElementById('searchClearBtn');
    var prevBtn = document.getElementById('prevPartBtn');
    var nextBtn = document.getElementById('nextPartBtn');
    var currentPartNum = document.getElementById('currentPartNum');
    var progressSummary = document.getElementById('progressSummary');
    var stepPills = document.getElementById('stepPills');

    var dirty = false;
    var autoTimer = null;
    var saving = false;

    var currentPart = 1;
    var partCards = form.querySelectorAll('.part-card');
    var totalParts = partCards.length;

    // ─── Part navigation ────────────────────────────────────────

    function showPart(index) {
        if (index < 1 || index > totalParts || index === currentPart) return;
        var oldPart = document.getElementById('part' + currentPart);
        var newPart = document.getElementById('part' + index);
        if (!oldPart || !newPart) return;
        oldPart.style.display = 'none';
        newPart.style.display = 'block';
        newPart.style.animation = 'none';
        void newPart.offsetWidth;
        newPart.style.animation = '';
        currentPart = index;
        updateStepper();
        updateNavButtons();
        if (currentPartNum) currentPartNum.textContent = currentPart;
        if (searchInput && searchInput.value) applySearch();
        updateProgress();
        updateSectionBadges();
    }

    function updateStepper() {
        if (!stepPills) return;
        stepPills.querySelectorAll('.step-pill').forEach(function (pill) {
            var idx = parseInt(pill.dataset.part);
            pill.classList.remove('step-pill--active');
            if (idx === currentPart) {
                pill.classList.add('step-pill--active');
            }
        });
    }

    function updateNavButtons() {
        if (prevBtn) prevBtn.disabled = currentPart <= 1;
        if (nextBtn) {
            if (currentPart >= totalParts) {
                nextBtn.disabled = true;
                nextBtn.innerHTML = 'Complete <i class="bi bi-check-lg"></i>';
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
            var idx = parseInt(pill.dataset.part);
            if (idx !== currentPart) showPart(idx);
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () { if (currentPart > 1) showPart(currentPart - 1); });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () { if (currentPart < totalParts) showPart(currentPart + 1); });
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

    // ─── Auto-compute totals (M + F = T) ────────────────────────

    function computeTotals(triggerInput) {
        var indId = triggerInput.dataset.indicatorId;
        var ageGroup = triggerInput.dataset.ageGroup;
        var sex = triggerInput.dataset.sex;
        if (sex !== 'M' && sex !== 'F') return;
        var mInput = form.querySelector('.indicator-input[data-indicator-id="' + indId + '"][data-sex="M"][data-age-group="' + ageGroup + '"]');
        var fInput = form.querySelector('.indicator-input[data-indicator-id="' + indId + '"][data-sex="F"][data-age-group="' + ageGroup + '"]');
        var tInput = form.querySelector('.indicator-input[data-indicator-id="' + indId + '"][data-sex="T"][data-age-group="' + ageGroup + '"]');
        if (!tInput) return;
        var mVal = parseFloat(mInput ? mInput.value : 0) || 0;
        var fVal = parseFloat(fInput ? fInput.value : 0) || 0;
        tInput.value = (mVal + fVal) > 0 ? (mVal + fVal) : '';
    }

    form.addEventListener('input', function (e) {
        var input = e.target;
        if (input.classList.contains('indicator-input')) {
            computeTotals(input);
        }
    });

    // ─── Section completion badges ──────────────────────────────

    function updateSectionBadges() {
        form.querySelectorAll('.part-card').forEach(function (card) {
            var inputs = card.querySelectorAll('.indicator-input');
            var total = inputs.length;
            if (total === 0) return;
            var filled = 0;
            inputs.forEach(function (i) { if (i.value !== '') filled++; });
            var badge = card.querySelector('.part-count-badge');
            if (badge) {
                var filledSpan = badge.querySelector('.part-filled-count');
                if (filledSpan) filledSpan.textContent = filled;
                badge.className = 'badge part-count-badge ms-2 ' + (filled === total ? 'bg-success' : 'bg-secondary');
            }
            // Update stepper pill count
            var partIdx = card.dataset.partIndex;
            var pill = stepPills ? stepPills.querySelector('.step-pill[data-part="' + partIdx + '"]') : null;
            if (pill) {
                var filledPart = pill.querySelector('.step-filled-' + partIdx);
                if (filledPart) filledPart.textContent = filled;
                if (filled === total && total > 0) {
                    pill.classList.add('step-pill--done');
                } else {
                    pill.classList.remove('step-pill--done');
                }
            }
        });
    }

    // ─── Search / filter ────────────────────────────────────────

    function applySearch() {
        if (!searchInput) return;
        var q = searchInput.value.toLowerCase().trim();
        var currentCard = document.getElementById('part' + currentPart);
        if (!currentCard) return;
        var rows = currentCard.querySelectorAll('.indicator-row');
        rows.forEach(function (row) {
            var text = row.textContent.toLowerCase();
            var match = !q || text.indexOf(q) !== -1;
            row.style.display = match ? '' : 'none';
            if (match) matchCount++;
        });
        // Show/hide category blocks
        currentCard.querySelectorAll('.indicator-category-block').forEach(function (cat) {
            var hasVisible = Array.from(cat.querySelectorAll('.indicator-row')).some(function (r) { return r.style.display !== 'none'; });
            cat.style.display = hasVisible || !q ? '' : 'none';
        });
        // Show/hide column headers
        currentCard.querySelectorAll('.indicator-column-headers').forEach(function (hdr) {
            var block = hdr.closest('.indicator-table-group');
            var hasVisible = block ? Array.from(block.querySelectorAll('.indicator-row')).some(function (r) { return r.style.display !== 'none'; }) : true;
            hdr.style.display = hasVisible || !q ? '' : 'none';
        });
        if (searchClear) {
            searchClear.classList.toggle('d-none', !q);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applySearch);
    }
    if (searchClear) {
        searchClear.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
                applySearch();
                searchInput.focus();
            }
        });
    }

    // ─── Dirty tracking ────────────────────────────────────────

    function markDirty() {
        if (!dirty) {
            dirty = true;
            showStatus('Unsaved changes...', 'info');
        }
        updateProgress();
        updateSectionBadges();
        maybeBeforeUnload();
    }

    form.addEventListener('input', function () { markDirty(); });

    function maybeBeforeUnload() {
        if (dirty && !saving) {
            window.addEventListener('beforeunload', beforeUnloadHandler);
        } else {
            window.removeEventListener('beforeunload', beforeUnloadHandler);
        }
    }

    function beforeUnloadHandler(e) {
        e.preventDefault();
        e.returnValue = '';
    }

    // ─── Collect values ─────────────────────────────────────────

    function collectValues() {
        var values = {};
        form.querySelectorAll('.indicator-input').forEach(function (input) {
            var id = input.dataset.indicatorId;
            var sex = input.dataset.sex || '_';
            var age = input.dataset.ageGroup || '_';
            if (!values[id]) values[id] = {};
            if (!values[id][sex]) values[id][sex] = {};
            values[id][sex][age] = input.value;
        });
        return values;
    }

    // ─── Status display ─────────────────────────────────────────

    function showStatus(msg, type) {
        if (!saveStatus) return;
        saveStatus.className = 'alert alert-' + type + ' mb-0 py-1 px-2 small d-flex align-items-center gap-1';
        saveStatus.innerHTML = msg;
        saveStatus.classList.remove('d-none');
    }

    function hideStatus() {
        if (saveStatus) saveStatus.classList.add('d-none');
    }

    // ─── Save draft ─────────────────────────────────────────────

    function saveDraft(callback, isAuto) {
        if (saving) return;
        saving = true;
        if (saveBtn) saveBtn.disabled = true;
        if (!isAuto) showStatus('<span class="spinner-border spinner-border-sm" role="status"></span> Saving...', 'info');

        var fd = new FormData();
        fd.append('csrf_token', window.MHO.csrfToken);
        fd.append('submission_id', submissionId);
        var values = collectValues();
        for (var indId in values) {
            for (var sex in values[indId]) {
                for (var age in values[indId][sex]) {
                    fd.append('values[' + indId + '][' + sex + '][' + age + ']', values[indId][sex][age]);
                }
            }
        }
        fetch(window.MHO.baseUrl + '/api/save-draft.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                saving = false;
                if (saveBtn) saveBtn.disabled = false;
                if (data.success) {
                    dirty = false;
                    maybeBeforeUnload();
                    if (!isAuto) {
                        showStatus('\u2714 Saved ' + data.saved + ' field(s)', 'success');
                        setTimeout(hideStatus, 3000);
                    }
                } else {
                    if (!isAuto) showStatus('\u2716 ' + data.message, 'danger');
                }
                if (callback) callback(data);
            })
            .catch(function () {
                saving = false;
                if (saveBtn) saveBtn.disabled = false;
                if (!isAuto) showStatus('\u2716 Save failed. Check connection.', 'danger');
            });
    }

    // ─── Auto-save every 45s ────────────────────────────────────

    function startAutoSave() {
        if (autoTimer) clearInterval(autoTimer);
        autoTimer = setInterval(function () {
            if (dirty && !saving) saveDraft(null, true);
        }, 45000);
    }
    startAutoSave();

    // ─── Ctrl+S ─────────────────────────────────────────────────

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            saveDraft();
        }
    });

    // ─── Save button ────────────────────────────────────────────

    if (saveBtn) {
        saveBtn.addEventListener('click', function () { saveDraft(); });
    }

    // ─── Copy from previous ─────────────────────────────────────

    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            if (dirty && !confirm('You have unsaved changes. Save first and then copy?')) return;
            if (!confirm('Copy indicator values from the previous month\'s report? Existing values will be overwritten.')) return;
            copyBtn.disabled = true;
            copyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Copying...';
            var fd = new FormData();
            fd.append('csrf_token', window.MHO.csrfToken);
            fd.append('submission_id', submissionId);
            fetch(window.MHO.baseUrl + '/api/copy-report.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    copyBtn.disabled = false;
                    copyBtn.innerHTML = '<i class="bi bi-copy"></i><span class="d-none d-sm-inline ms-1">Copy Previous</span>';
                    if (data.success) {
                        showStatus('\u2714 Copied ' + data.copied + ' field(s) from previous period', 'success');
                        setTimeout(function () { window.location.reload(); }, 1500);
                    } else {
                        showStatus('\u2716 ' + data.message, 'danger');
                    }
                })
                .catch(function () {
                    copyBtn.disabled = false;
                    copyBtn.innerHTML = '<i class="bi bi-copy"></i><span class="d-none d-sm-inline ms-1">Copy Previous</span>';
                    showStatus('\u2716 Copy failed.', 'danger');
                });
        });
    }

    // ─── Submit ─────────────────────────────────────────────────

    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            var inputs = form.querySelectorAll('.indicator-input');
            var total = inputs.length;
            var filled = 0;
            inputs.forEach(function (i) { if (i.value !== '') filled++; });
            var empty = total - filled;
            if (empty > 0) {
                if (!confirm(filled + ' of ' + total + ' fields filled (' + empty + ' empty). Submit anyway?')) return;
            }
            if (!confirm('Save and submit this report? You cannot edit after submission unless rejected.')) return;
            saveDraft(function (data) {
                if (!data.success) return;
                var fd = new FormData();
                fd.append('csrf_token', window.MHO.csrfToken);
                fd.append('submission_id', submissionId);
                fetch(window.MHO.baseUrl + '/api/submit-report.php', { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.success) {
                            window.location.href = window.MHO.baseUrl + '/nurse/submissions.php';
                        } else {
                            alert(res.message);
                        }
                    });
            });
        });
    }

    // ─── Progress ───────────────────────────────────────────────

    function updateProgress() {
        if (!progressSummary) return;
        var inputs = form.querySelectorAll('.indicator-input');
        var total = inputs.length;
        if (total === 0) return;
        var filled = 0;
        inputs.forEach(function (i) { if (i.value !== '') filled++; });
        progressSummary.textContent = filled + ' / ' + total + ' fields';
    }

    // ─── Init ───────────────────────────────────────────────────

    updateStepper();
    updateNavButtons();
    updateProgress();
    updateSectionBadges();
});
