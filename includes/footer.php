<?php if (isLoggedIn()): ?>
        </main>
    </div>
</div>
<?php else: ?>
</main>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.MHO = {
        baseUrl: <?= json_encode(baseUrl()) ?>,
        csrfToken: <?= json_encode(csrfToken()) ?>
    };
</script>
<script src="<?= e(assetUrl('js/app.js')) ?>"></script>
<?php if (!empty($extraScripts)): foreach ((array)$extraScripts as $script): ?>
<script src="<?= e(assetUrl('js/' . $script)) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
