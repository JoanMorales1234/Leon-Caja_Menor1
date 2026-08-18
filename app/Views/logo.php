<div class="container py-4">
    <div class="card shadow-sm" style="max-width:600px;margin:0 auto;">
        <div class="card-body">
            <h4 class="card-title mb-3"><i class="bi bi-image"></i> Logo de la empresa</h4>
            <?php if ($message): ?>
                <div class="alert alert-<?= $type ?> alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <div class="text-center mb-4 p-4 bg-light rounded" style="min-height:120px;display:flex;align-items:center;justify-content:center;">
                <?php if ($logoExists): ?>
                    <img src="<?= asset($logoPath) ?>?t=<?= time() ?>" alt="Logo" style="max-width:300px;max-height:100px;">
                <?php else: ?>
                    <span class="text-muted"><i class="bi bi-building"></i> Sin logo — se mostrará el nombre de la empresa</span>
                <?php endif; ?>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Subir nuevo logo</label>
                    <input class="form-control" type="file" name="logo" accept="image/*">
                    <small class="text-muted">Se redimensionará automáticamente a 300x100 px max. Formatos: JPG, PNG, GIF, WEBP.</small>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Subir logo</button>
                <?php if ($logoExists): ?>
                    <button type="submit" name="eliminar" value="1" class="btn btn-outline-danger" onclick="return confirm('¿Eliminar el logo actual?')"><i class="bi bi-trash"></i> Eliminar logo</button>
                <?php endif; ?>
                <a href="<?= url('index') ?>" class="btn btn-secondary">Volver</a>
            </form>
        </div>
    </div>
</div>
