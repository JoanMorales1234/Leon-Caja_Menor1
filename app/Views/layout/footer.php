<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script src="<?= asset('assets/js/app.js?v=' . filemtime(ROOT_PATH . '/assets/js/app.js')) ?>"></script>
<?php if (!empty($extraJs)): ?>
    <?php foreach ($extraJs as $js): ?>
        <script src="<?= asset('assets/js/' . $js . '?v=' . filemtime(ROOT_PATH . '/assets/js/' . $js)) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
