<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width:500px">
        <div class="card-body text-center">
            <h4 class="card-title mb-3 text-<?= $tipo === 'success' ? 'success' : 'danger' ?>">
                <i class="bi bi-<?= $tipo === 'success' ? 'check-circle' : 'x-circle' ?>"></i> <?= htmlspecialchars($titulo) ?>
            </h4>
            <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
            <a class="btn btn-primary" href="<?= url('index') ?>">Ir al inicio</a>
        </div>
    </div>
</div>
