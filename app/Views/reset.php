<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width:500px">
        <div class="card-body text-center">
            <h4 class="card-title text-danger"><i class="bi bi-exclamation-triangle"></i> Restablecer base de datos</h4>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <a class="btn btn-primary" href="<?= url('index') ?>">Ir al inicio</a>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <a class="btn btn-secondary" href="<?= url('datos/reset') ?>">Intentar de nuevo</a>
            <?php else: ?>
                <p class="text-muted">Esto <strong>eliminará todos los datos</strong> (gastos, cajas, empleados, proveedores, etc.) y reiniciará los contadores de ID desde 1.</p>
                <p class="text-muted">Los roles y permisos se volverán a crear.</p>
                <form method="post">
                    <input type="hidden" name="confirmar" value="si">
                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('¿Estás seguro? Se borrarán TODOS los datos.')">
                        <i class="bi bi-trash"></i> Sí, restablecer todo
                    </button>
                </form>
                <a class="btn btn-secondary mt-2 w-100" href="<?= url('index') ?>">Cancelar</a>
            <?php endif; ?>
        </div>
    </div>
</div>
