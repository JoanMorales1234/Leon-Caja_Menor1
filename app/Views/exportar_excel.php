<html xml:lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: Calibri, Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #999; padding: 4px 6px; font-size: 10pt; vertical-align: top; }
        th { background: #4472C4; color: #fff; font-weight: bold; text-align: center; }
        .title-row td { font-size: 28pt; font-weight: bold; text-align: center; border: none; padding: 8px; }
        .section-title td { font-size: 12pt; font-weight: bold; text-align: left; border: none; padding: 6px 0; }
        .summary-reintegro td { font-weight: bold; background: #E2EFDA; }
        .summary-saldo-actual td { font-weight: bold; background: #D9E2F3; font-size: 12pt; }
        .saldo-anterior td { font-weight: bold; background: #D9E2F3; }
        .total-gastos td { font-weight: bold; background: #FCE4D6; }
        .nuevo-saldo td { font-weight: bold; background: #E2EFDA; }
        .funcionarios th { background: #70AD47; }
        .page-break { page-break-before: always; }
        .valor-col { text-align: right; white-space: nowrap; }
        .fecha-col { text-align: center; white-space: nowrap; }
        .cedula-col { text-align: center; }
        .no-data td { text-align: center; color: #999; font-style: italic; }
    </style>
</head>
<body>
    <?php $tieneLogo = file_exists(ROOT_PATH . '/uploads/logo.png'); ?>
    <?php foreach ($cajas as $cajaIdx => $caja):
        $gastos = $getMovs($caja['id'], 'gastos');
        $reintegros = $getMovs($caja['id'], 'reintegros');
        $tieneReintegros = !empty($reintegros);
        $movs = $gastos;
        $totalGastos = (float)$caja['total_gastos'];
        $totalReintegros = (float)$caja['total_reintegros'];
        $saldoAnterior = 0;
        if ($cajaIdx + 1 < count($cajas)) {
            $prev = $cajas[$cajaIdx + 1];
            $saldoAnterior = (float)$prev['valor_inicial'] + (float)$prev['total_reintegros'] - (float)$prev['total_gastos'];
        } else {
            $saldoAnterior = (float)$caja['valor_inicial'];
        }
        $nuevoSaldo = (float)$caja['valor_final'];
        if ($cajaIdx > 0) { echo '<div class="page-break"></div>'; }
        $fechaObj = \DateTime::createFromFormat('Y-m-d', $caja['fecha_caja']);
        $nombreMes = strtoupper($fechaObj->format('F'));
        $dia = $fechaObj->format('d');
        $anio = $fechaObj->format('Y');
        $mesNum = $fechaObj->format('m');
    ?>
    <table>
        <?php if ($tieneLogo): ?>
        <tr>
            <td colspan="8" style="text-align:center;border:none;padding:4px;">
                <img src="<?= asset('uploads/logo.png') ?>?t=<?= time() ?>" alt="Logo" style="max-width:300px;max-height:80px;">
            </td>
        </tr>
        <?php endif; ?>
        <tr class="title-row">
            <td colspan="8">CAJA <?= $nombreTipoMay ?></td>
        </tr>
        <tr class="section-title">
            <td colspan="8"><?= $nombreMes ?> <?= $dia ?> <?= $anio ?></td>
        </tr>
        <tr>
            <th>FECHA</th>
            <th>CEDULA(NIT)</th>
            <th>NOMBRE</th>
            <th>FUNCIONARIO</th>
            <th>DESCRIPCION O DETALLE</th>
            <th>NIT</th>
            <th>PROVEEDOR</th>
            <th>VALOR</th>
        </tr>
        <tr class="saldo-anterior">
            <td colspan="7" style="text-align:right">SALDO ANTERIOR</td>
            <td class="valor-col"><?= number_format($saldoAnterior, 2, ',', '.') ?></td>
        </tr>
        <?php if ($totalReintegros > 0): ?>
        <tr class="summary-reintegro">
            <td colspan="7" style="text-align:right">REINTEGRO</td>
            <td class="valor-col" style="color:#006100"><?= number_format($totalReintegros, 2, ',', '.') ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($totalReintegros > 0): ?>
        <tr class="summary-saldo-actual">
            <td colspan="7" style="text-align:right">SALDO ACTUAL</td>
            <td class="valor-col"><?= number_format($saldoAnterior + $totalReintegros, 2, ',', '.') ?></td>
        </tr>
        <?php endif; ?>
        <?php if (empty($movs)): ?>
            <tr class="no-data">
                <td colspan="8">No hay movimientos registrados</td>
            </tr>
        <?php else:
            foreach ($movs as $g):
                $cedula = $g['cedula'] ?? '';
                $nombre = htmlspecialchars(trim(($g['nombres'] ?? '') . ' ' . ($g['apellidos'] ?? '')));
                $cargo = htmlspecialchars($g['cargo'] ?? '');
                $desc = htmlspecialchars($g['descripcion'] ?? '');
                $provNit = htmlspecialchars($g['proveedor_nit'] ?? '');
                $provNombre = htmlspecialchars($g['proveedor_nombre'] ?? '');
        ?>
            <tr>
                <td class="fecha-col"><?= date('j/n/Y', strtotime($g['fecha_gasto'] ?? $caja['fecha_caja'])) ?></td>
                <td class="cedula-col"><?= $cedula ?></td>
                <td><?= $nombre ?></td>
                <td><?= $cargo ?></td>
                <td><?= $desc ?></td>
                <td class="cedula-col"><?= $provNit ?></td>
                <td><?= $provNombre ?></td>
                <td class="valor-col">$ <?= number_format($g['valor'], 2, ',', '.') ?></td>
            </tr>
        <?php endforeach;
        endif; ?>
        <?php if ($totalGastos > 0 || $totalReintegros > 0): ?>
        <tr class="total-gastos">
            <td colspan="7" style="text-align:right">TOTAL GASTOS</td>
            <td class="valor-col">$ <?= number_format($totalGastos, 2, ',', '.') ?></td>
        </tr>

        <tr class="nuevo-saldo">
            <td colspan="7" style="text-align:right">NUEVO SALDO</td>
            <td class="valor-col">$ <?= number_format($nuevoSaldo, 2, ',', '.') ?></td>
        </tr>
        <?php endif; ?>
    </table>
    <?php endforeach; ?>
</body>
</html>
