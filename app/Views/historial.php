<?php
function historialFiltros()
{
    $keep = ['estado', 'mes', 'anio', 'desde', 'hasta', 'ultimos'];
    $out = [];
    foreach ($keep as $k) {
        $v = $_GET[$k] ?? '';
        if ($v !== '' && $v !== 0 && $v !== '0') $out[$k] = $v;
    }
    return $out;
}

function mostrarPaginacionHistorial($pagina, $totalPaginas, $tabId)
{
    if ($totalPaginas <= 1) return '';
    $html = '<nav><ul class="pagination pagination-sm mb-0">';
    $prevDisabled = $pagina <= 1 ? 'disabled' : '';
    $html .= '<li class="page-item ' . $prevDisabled . '">
        <a class="page-link" href="' . url('historial', historialFiltros() + ['pagina' => $pagina - 1]) . '#' . $tabId . '">Anterior</a>
    </li>';
    for ($i = 1; $i <= $totalPaginas; $i++) {
        if ($i == 1 || $i == $totalPaginas || abs($i - $pagina) <= 2) {
            $active = $i === $pagina ? 'active' : '';
            $html .= '<li class="page-item ' . $active . '">
                <a class="page-link" href="' . url('historial', historialFiltros() + ['pagina' => $i]) . '#' . $tabId . '">' . $i . '</a>
            </li>';
        } elseif ($i == 2 || $i == $totalPaginas - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    $nextDisabled = $pagina >= $totalPaginas ? 'disabled' : '';
    $html .= '<li class="page-item ' . $nextDisabled . '">
        <a class="page-link" href="' . url('historial', historialFiltros() + ['pagina' => $pagina + 1]) . '#' . $tabId . '">Siguiente</a>
    </li>';
    $html .= '</ul></nav>';
    return $html;
}
?>

<style>
    .main-container { max-width: 90% !important; width: 90% !important; }
    .main-title { background: #f8f9fa; padding: 8px 0 6px 0; }
    .main-title h1 { font-size: 1.2rem; }
    .filter-card { margin-bottom: 6px !important; }
    .filter-card .card-body { padding: 10px 14px !important; }
    .filter-card .form-label { font-size: .8rem; margin-bottom: 2px; }
    .filter-card .form-select, .filter-card .form-control { font-size: .85rem; padding: 4px 8px; min-height: 32px; }
    .filter-card .form-select { padding-right: 28px; background-position: right 6px center; }
    .filter-card .btn { font-size: .85rem; padding: 4px 12px; }
    .nav-tabs .nav-link { padding: 6px 14px; font-size: .9rem; }
    .nav-tabs .badge { font-size: .7rem; padding: 2px 6px; }
    .scroll-table { max-height: calc(100vh - 300px); overflow-y: auto; }
    .scroll-table::-webkit-scrollbar { width: 6px; }
    .scroll-table::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
    .scroll-table thead th { position: sticky; top: 0; background: #212529; color: #fff; z-index: 10; font-size: .75rem; font-weight: 500; text-transform: uppercase; letter-spacing: .02em; padding: 4px 6px; border-color: #373b3e; }
    .scroll-table tbody td { padding: 3px 6px; font-size: .85rem; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; border-bottom: 1px solid #dee2e6; flex-wrap: wrap; gap: 6px; }
    .top-bar .btn { font-size: .85rem; padding: 4px 12px; }
    .top-bar .pagination { margin: 0; }
    .top-bar .page-link { padding: 4px 10px; font-size: .85rem; }
</style>

<div class="container py-3 main-container">
    <?php if (isset($_GET['msg']) && $_GET['msg'] !== ''): ?>
    <div class="alert alert-<?= ($_GET['tipo'] ?? 'success') === 'danger' ? 'danger' : 'success' ?> alert-dismissible fade show py-2" role="alert">
        <?= htmlspecialchars($_GET['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center main-title">
        <h1 class="h5 mb-0"><i class="bi bi-clock-history"></i> Historial de cajas</h1>
        <a class="btn btn-sm btn-outline-primary" href="<?= url('index') ?>"><i class="bi bi-house"></i> Inicio</a>
    </div>

    <div class="card shadow-sm filter-card">
        <div class="card-body">
            <form method="get" action="<?= url('historial') ?>" class="row g-2 align-items-end">
                <input type="hidden" name="url" value="historial">
                <div class="col-auto">
                    <label class="form-label small mb-0">Estado</label>
                    <select class="form-select form-select-sm" name="estado">
                        <option value="todas" <?= $filtroEstado === 'todas' ? 'selected' : '' ?>>Todos</option>
                        <option value="abierta" <?= $filtroEstado === 'abierta' ? 'selected' : '' ?>>Solo abiertas</option>
                        <option value="cerrada" <?= $filtroEstado === 'cerrada' ? 'selected' : '' ?>>Solo cerradas</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Mes</label>
                    <select class="form-select form-select-sm" name="mes">
                        <option value="0">Todos</option>
                        <?php foreach ($meses as $num => $nom): ?>
                            <option value="<?= $num ?>" <?= $filtroMes === $num ? 'selected' : '' ?>><?= $nom ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Año</label>
                    <select class="form-select form-select-sm" name="anio">
                        <option value="0">Todos</option>
                        <?php foreach ($aniosDisponibles as $a): ?>
                            <option value="<?= $a['anio'] ?>" <?= $filtroAnio === (int)$a['anio'] ? 'selected' : '' ?>><?= $a['anio'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Desde</label>
                    <input class="form-control form-control-sm" type="date" name="desde" value="<?= htmlspecialchars($filtroDesde) ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Hasta</label>
                    <input class="form-control form-control-sm" type="date" name="hasta" value="<?= htmlspecialchars($filtroHasta) ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Mostrar</label>
                    <select class="form-select form-select-sm" name="ultimos" id="selUltimos">
                        <option value="0" <?= $filtroUltimos === 0 ? 'selected' : '' ?>>Paginado</option>
                        <option value="<?= $diasMesActual ?>" <?= $filtroUltimos === $diasMesActual ? 'selected' : '' ?>>Últimos <?= $diasMesActual ?> días</option>
                        <option value="50" <?= $filtroUltimos === 50 ? 'selected' : '' ?>>Últimos 50</option>
                        <option value="100" <?= $filtroUltimos === 100 ? 'selected' : '' ?>>Últimos 100</option>
                        <option value="200" <?= $filtroUltimos === 200 ? 'selected' : '' ?>>Últimos 200</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Filtrar</button>
                    <a href="<?= url('historial') ?>" class="btn btn-sm btn-secondary"><i class="bi bi-x-lg"></i> Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <ul class="nav nav-tabs" id="histoTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="histoMenor-tab" data-bs-toggle="tab" data-bs-target="#histoMenor" type="button">
                <i class="bi bi-wallet2"></i> Menor
                <span class="badge bg-secondary ms-1"><?= count($cajasMenor) ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="histoMayor-tab" data-bs-toggle="tab" data-bs-target="#histoMayor" type="button">
                <i class="bi bi-safe"></i> Mayor
                <span class="badge bg-secondary ms-1"><?= count($cajasMayor) ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- TAB MENOR -->
        <div class="tab-pane fade show active" id="histoMenor" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="top-bar">
                        <a class="btn btn-outline-dark btn-sm" href="<?= url('imprimir', ['tipo' => 'menor']) ?>" target="_blank">
                            <i class="bi bi-printer"></i> Imprimir
                        </a>
                        <a class="btn btn-outline-success btn-sm" href="<?= url('exportar/excel', ['tipo' => 'menor'] + historialFiltros()) ?>">
                            <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                        </a>
                        <a class="btn btn-outline-warning btn-sm" href="<?= url('exportar/syscafe', ['tipo' => 'menor'] + historialFiltros()) ?>">
                            <i class="bi bi-file-earmark-excel"></i> Syscafe
                        </a>
                        <?php if ($totalPaginasMenor > 1 && $filtroUltimos <= 0): ?>
                            <?= mostrarPaginacionHistorial($pagina, $totalPaginasMenor, 'histoMenor') ?>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($cajasMenor)): ?>
                        <p class="text-muted p-3 mb-0">No hay cajas menor registradas.</p>
                    <?php else: ?>
                        <div class="table-responsive scroll-table">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Inicial</th>
                                        <th>Gastos</th>
                                        <th>Reintegros</th>
                                        <th>Saldo final</th>
                                        <th>Estado</th>
                                        <th>Cierre</th>
                                        <th style="width:140px">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cajasMenor as $caja): ?>
                                        <tr>
                                            <td><?= $caja['id'] ?></td>
                                            <td><?= htmlspecialchars($caja['fecha_caja']) ?></td>
                                            <td><?= number_format($caja['valor_inicial'], 2, ',', '.') ?></td>
                                            <td class="text-danger"><?= number_format($caja['total_gastos'], 2, ',', '.') ?></td>
                                            <td class="text-success"><?= number_format($caja['total_reintegros'], 2, ',', '.') ?></td>
                                            <td><strong><?= number_format($caja['valor_final'], 2, ',', '.') ?></strong></td>
                                            <td><span class="badge bg-<?= $caja['estado'] === 'abierta' ? 'success' : 'secondary' ?>"><?= $caja['estado'] ?></span></td>
                                            <td><?= htmlspecialchars($caja['fecha_cierre'] ?: '-') ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-info py-0 px-1" href="<?= url('historial', historialFiltros() + ['detalle' => $caja['id']]) ?>" title="Ver detalle">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a class="btn btn-sm btn-outline-dark py-0 px-1" href="<?= url('imprimir', ['caja_id' => $caja['id']]) ?>" target="_blank" title="Imprimir caja">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                                <?php if (!empty($caja['total_gastos']) && $caja['total_gastos'] > 0): ?>
                                                <a class="btn btn-sm btn-outline-secondary py-0 px-1" href="<?= url('soportes', ['caja_id' => $caja['id']]) ?>" target="_blank" title="Imprimir soportes">
                                                    <i class="bi bi-files"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($caja['estado'] === 'cerrada'): ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar esta caja y todos sus movimientos?')">
                                                    <input type="hidden" name="action" value="eliminar_caja">
                                                    <input type="hidden" name="id" value="<?= $caja['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar"><i class="bi bi-trash"></i></button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TAB MAYOR -->
        <div class="tab-pane fade" id="histoMayor" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="top-bar">
                        <a class="btn btn-outline-dark btn-sm" href="<?= url('imprimir', ['tipo' => 'mayor']) ?>" target="_blank">
                            <i class="bi bi-printer"></i> Imprimir
                        </a>
                        <a class="btn btn-outline-success btn-sm" href="<?= url('exportar/excel', ['tipo' => 'mayor'] + historialFiltros()) ?>">
                            <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                        </a>
                        <a class="btn btn-outline-warning btn-sm" href="<?= url('exportar/syscafe', ['tipo' => 'mayor'] + historialFiltros()) ?>">
                            <i class="bi bi-file-earmark-excel"></i> Syscafe
                        </a>
                        <?php if ($totalPaginasMayor > 1 && $filtroUltimos <= 0): ?>
                            <?= mostrarPaginacionHistorial($pagina, $totalPaginasMayor, 'histoMayor') ?>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($cajasMayor)): ?>
                        <p class="text-muted p-3 mb-0">No hay cajas mayor registradas.</p>
                    <?php else: ?>
                        <div class="table-responsive scroll-table">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Inicial</th>
                                        <th>Gastos</th>
                                        <th>Reintegros</th>
                                        <th>Saldo final</th>
                                        <th>Estado</th>
                                        <th>Cierre</th>
                                        <th style="width:140px">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cajasMayor as $caja): ?>
                                        <tr>
                                            <td><?= $caja['id'] ?></td>
                                            <td><?= htmlspecialchars($caja['fecha_caja']) ?></td>
                                            <td><?= number_format($caja['valor_inicial'], 2, ',', '.') ?></td>
                                            <td class="text-danger"><?= number_format($caja['total_gastos'], 2, ',', '.') ?></td>
                                            <td class="text-success"><?= number_format($caja['total_reintegros'], 2, ',', '.') ?></td>
                                            <td><strong><?= number_format($caja['valor_final'], 2, ',', '.') ?></strong></td>
                                            <td><span class="badge bg-<?= $caja['estado'] === 'abierta' ? 'success' : 'secondary' ?>"><?= $caja['estado'] ?></span></td>
                                            <td><?= htmlspecialchars($caja['fecha_cierre'] ?: '-') ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-info py-0 px-1" href="<?= url('historial', historialFiltros() + ['detalle' => $caja['id']]) ?>" title="Ver detalle">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a class="btn btn-sm btn-outline-dark py-0 px-1" href="<?= url('imprimir', ['caja_id' => $caja['id']]) ?>" target="_blank" title="Imprimir caja">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                                <?php if (!empty($caja['total_gastos']) && $caja['total_gastos'] > 0): ?>
                                                <a class="btn btn-sm btn-outline-secondary py-0 px-1" href="<?= url('soportes', ['caja_id' => $caja['id']]) ?>" target="_blank" title="Imprimir soportes">
                                                    <i class="bi bi-files"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($caja['estado'] === 'cerrada'): ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar esta caja y todos sus movimientos?')">
                                                    <input type="hidden" name="action" value="eliminar_caja">
                                                    <input type="hidden" name="id" value="<?= $caja['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar"><i class="bi bi-trash"></i></button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DETALLE -->
<?php if ($detalleCaja): ?>
<div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detalleModalLabel">
                    <i class="bi bi-cash-coin"></i> Caja <?= ucfirst(htmlspecialchars($detalleCaja['tipo_caja'])) ?>
                    — <?= htmlspecialchars($detalleCaja['fecha_caja']) ?>
                    <span class="badge bg-<?= $detalleCaja['estado'] === 'abierta' ? 'success' : 'secondary' ?> ms-2"><?= $detalleCaja['estado'] ?></span>
                </h5>
                <a href="<?= url('historial', historialFiltros()) ?>" class="btn-close"></a>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <small class="text-muted">Valor inicial</small>
                        <p class="h5"><?= number_format($detalleCaja['valor_inicial'], 2, ',', '.') ?></p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Total gastos</small>
                        <p class="h5 text-danger"><?= number_format($detalleCaja['total_gastos'] ?? 0, 2, ',', '.') ?></p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Total reintegros</small>
                        <p class="h5 text-success"><?= number_format($detalleCaja['total_reintegros'] ?? 0, 2, ',', '.') ?></p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Saldo final</small>
                        <?php
                        $saldoFinal = (float)$detalleCaja['valor_inicial']
                            + (float)($detalleCaja['total_reintegros'] ?? 0)
                            - (float)($detalleCaja['total_gastos'] ?? 0);
                        ?>
                        <p class="h5 fw-bold <?= $saldoFinal < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($saldoFinal, 2, ',', '.') ?></p>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarCaja"
                        data-id="<?= $detalleCaja['id'] ?>"
                        data-fecha-caja="<?= htmlspecialchars($detalleCaja['fecha_caja']) ?>"
                        data-valor-inicial="<?= htmlspecialchars($detalleCaja['valor_inicial']) ?>">
                        <i class="bi bi-pencil-square"></i> Editar datos de la caja
                    </button>
                </div>

                <?php if (!empty($detalleCaja['nota'])): ?>
                <div class="alert alert-info py-2 px-3 mb-3">
                    <strong><i class="bi bi-sticky"></i> Nota:</strong>
                    <?= nl2br(htmlspecialchars($detalleCaja['nota'])) ?>
                </div>
                <?php endif; ?>

                <ul class="nav nav-tabs" id="detalleTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="detGastos-tab" data-bs-toggle="tab" data-bs-target="#detGastos" type="button">
                            Gastos <span class="badge bg-danger ms-1"><?= count($detalleGastos) ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="detReintegros-tab" data-bs-toggle="tab" data-bs-target="#detReintegros" type="button">
                            Reintegros <span class="badge bg-success ms-1"><?= count($detalleReintegros) ?></span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-2">
                    <div class="tab-pane fade show active" id="detGastos" role="tabpanel">
                        <div class="d-flex justify-content-end mb-2">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalGasto"
                                data-edit="false"
                                data-caja-id="<?= $detalleCaja['id'] ?>"
                                data-tipo="<?= $detalleCaja['tipo_caja'] ?>"
                                data-fecha-caja="<?= htmlspecialchars($detalleCaja['fecha_caja']) ?>">
                                <i class="bi bi-plus-lg"></i> Agregar gasto
                            </button>
                        </div>
                        <?php if (empty($detalleGastos)): ?>
                            <p class="text-muted">Sin gastos registrados.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-detail">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Fecha</th>
                                            <th>Descripción</th>
                                            <th>Valor</th>
                                            <th>Empleado</th>
                                            <th>Proveedor</th>
                                            <th>Soporte</th>
                                            <th style="width:120px">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detalleGastos as $i => $g): ?>
                                            <tr>
                                                <td><?= $i + 1 ?></td>
                                                <td><?= htmlspecialchars($g['fecha_gasto']) ?></td>
                                                <td title="<?= htmlspecialchars($g['descripcion'] ?? '') ?>"><?= htmlspecialchars($g['descripcion']) ?></td>
                                                <td class="text-danger fw-semibold"><?= number_format($g['valor'], 2, ',', '.') ?></td>
                                                <td title="<?= htmlspecialchars(($g['nombres'] ?? '') . ' ' . ($g['apellidos'] ?? '')) ?>"><?= htmlspecialchars(($g['nombres'] ?? '') . ' ' . ($g['apellidos'] ?? '')) ?: '-' ?></td>
                                                <td title="<?= htmlspecialchars($g['proveedor_nombre'] ?? '') ?>"><?= htmlspecialchars($g['proveedor_nombre'] ?? '') ?: '-' ?></td>
                                                <td>
                                                    <?php if ($g['soporte'] && strpos($g['soporte'], 'uploads/') === 0): ?>
                                                        <a href="<?= asset(htmlspecialchars($g['soporte'])) ?>" target="_blank" title="Ver soporte">
                                                            <img src="<?= asset(htmlspecialchars($g['soporte'])) ?>" style="max-height:40px;max-width:80px" class="img-thumbnail" alt="Soporte">
                                                        </a>
                                                    <?php else: ?>
                                                        <small><?= htmlspecialchars($g['soporte'] ?: '-') ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" data-bs-toggle="modal" data-bs-target="#modalGasto"
                                                        data-edit="true"
                                                        data-id="<?= $g['id'] ?>"
                                                        data-caja-id="<?= $detalleCaja['id'] ?>"
                                                        data-tipo="<?= $detalleCaja['tipo_caja'] ?>"
                                                        data-fecha-caja="<?= htmlspecialchars($detalleCaja['fecha_caja']) ?>"
                                                        data-fecha-gasto="<?= htmlspecialchars($g['fecha_gasto']) ?>"
                                                        data-descripcion="<?= htmlspecialchars($g['descripcion']) ?>"
                                                        data-valor="<?= $g['valor'] ?>"
                                                        data-empleado-id="<?= $g['empleado_id'] ?>" data-empleado-nombre="<?= htmlspecialchars(($g['nombres'] ?? '') . ' ' . ($g['apellidos'] ?? '')) ?>"
                                                        data-proveedor-id="<?= $g['proveedor_id'] ?>" data-proveedor-nombre="<?= htmlspecialchars($g['proveedor_nombre'] ?? '') ?>"
                                                        data-soportes='<?= $g['soportes_json'] ?>'
                                                        title="Editar gasto"><i class="bi bi-pencil"></i></button>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este gasto?')">
                                                        <input type="hidden" name="action" value="eliminar_gasto">
                                                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar gasto"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="detReintegros" role="tabpanel">
                        <div class="d-flex justify-content-end mb-2">
                            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalReintegro"
                                data-edit="false"
                                data-caja-id="<?= $detalleCaja['id'] ?>"
                                data-tipo="<?= $detalleCaja['tipo_caja'] ?>"
                                data-fecha-caja="<?= htmlspecialchars($detalleCaja['fecha_caja']) ?>">
                                <i class="bi bi-plus-lg"></i> Agregar reintegro
                            </button>
                        </div>
                        <?php if (empty($detalleReintegros)): ?>
                            <p class="text-muted">Sin reintegros registrados.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-detail">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Fecha</th>
                                            <th>Descripción</th>
                                            <th>Valor</th>
                                            <th>Soporte</th>
                                            <th style="width:120px">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detalleReintegros as $i => $r): ?>
                                            <tr>
                                                <td><?= $i + 1 ?></td>
                                                <td><?= htmlspecialchars($r['fecha_reintegro']) ?></td>
                                                <td title="<?= htmlspecialchars($r['descripcion'] ?? '') ?>"><?= htmlspecialchars($r['descripcion']) ?></td>
                                                <td class="text-success fw-semibold"><?= number_format($r['valor'], 2, ',', '.') ?></td>
                                                <td>
                                                    <?php if ($r['soporte'] && strpos($r['soporte'], 'uploads/') === 0): ?>
                                                        <a href="<?= asset(htmlspecialchars($r['soporte'])) ?>" target="_blank" title="Ver soporte">
                                                            <img src="<?= asset(htmlspecialchars($r['soporte'])) ?>" style="max-height:40px;max-width:80px" class="img-thumbnail" alt="Soporte">
                                                        </a>
                                                    <?php else: ?>
                                                        <small><?= htmlspecialchars($r['soporte'] ?: '-') ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" data-bs-toggle="modal" data-bs-target="#modalReintegro"
                                                        data-edit="true"
                                                        data-id="<?= $r['id'] ?>"
                                                        data-caja-id="<?= $detalleCaja['id'] ?>"
                                                        data-tipo="<?= $detalleCaja['tipo_caja'] ?>"
                                                        data-fecha-caja="<?= htmlspecialchars($detalleCaja['fecha_caja']) ?>"
                                                        data-fecha-reintegro="<?= htmlspecialchars($r['fecha_reintegro']) ?>"
                                                        data-valor="<?= $r['valor'] ?>"
                                                        data-descripcion="<?= htmlspecialchars($r['descripcion'] ?? '') ?>"
                                                        data-soporte="<?= htmlspecialchars($r['soporte'] ?? '') ?>"
                                                        title="Editar reintegro"><i class="bi bi-pencil"></i></button>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este reintegro?')">
                                                        <input type="hidden" name="action" value="eliminar_reintegro">
                                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar reintegro"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a class="btn btn-outline-dark btn-sm" href="<?= url('imprimir', ['caja_id' => $detalleCaja['id']]) ?>" target="_blank">
                    <i class="bi bi-printer"></i> Imprimir
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="<?= url('soportes', ['caja_id' => $detalleCaja['id']]) ?>" target="_blank">
                    <i class="bi bi-files"></i> Soportes
                </a>
                <a class="btn btn-outline-success btn-sm" href="<?= url('exportar/excel', ['caja_id' => $detalleCaja['id']]) ?>">
                    <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                </a>
                <a class="btn btn-outline-warning btn-sm" href="<?= url('exportar/syscafe', ['caja_id' => $detalleCaja['id']]) ?>">
                    <i class="bi bi-file-earmark-excel"></i> Syscafe
                </a>
                <a href="<?= url('historial', historialFiltros()) ?>" class="btn btn-secondary">Cerrar</a>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = new bootstrap.Modal(document.getElementById('detalleModal'));
        modal.show();
    });
</script>
<?php endif; ?>

<!-- MODAL EDITAR CAJA -->
<div class="modal fade" id="modalEditarCaja" tabindex="-1" aria-labelledby="modalEditarCajaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarCajaLabel">Editar datos de la caja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="editar_caja">
                    <input type="hidden" name="id" id="editarCajaId" value="0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha</label>
                            <input class="form-control" type="date" name="fecha_caja" id="editarCajaFecha">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valor inicial ($)</label>
                            <input class="form-control" type="number" step="0.01" name="valor_inicial" id="editarCajaValor">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    var modalEditarCaja = document.getElementById('modalEditarCaja');
    if (modalEditarCaja) {
        modalEditarCaja.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            document.getElementById('editarCajaId').value = btn.getAttribute('data-id');
            document.getElementById('editarCajaFecha').value = btn.getAttribute('data-fecha-caja');
            document.getElementById('editarCajaValor').value = btn.getAttribute('data-valor-inicial');
        });
    }
</script>

<?php include APP_PATH . '/Views/modals/gasto.php'; ?>
<?php include APP_PATH . '/Views/modals/reintegro.php'; ?>
