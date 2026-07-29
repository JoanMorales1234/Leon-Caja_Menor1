<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Caja Menor / Mayor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index_nuevo.php">
            <i class="bi bi-cash-stack"></i> 
            Caja Menor / Mayor
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="cajas_historial.php"><i class="bi bi-clock-history"></i> Historial</a></li>
                <li class="nav-item"><a class="nav-link" href="import_excel.php"><i class="bi bi-file-earmark-excel"></i> Importar Excel</a></li>
                <li class="nav-item"><a class="nav-link" href="logo.php"><i class="bi bi-image"></i> Logo</a></li>
                <li class="nav-item"><a class="nav-link" href="seed_data.php"><i class="bi bi-database-fill-up"></i> Datos ejemplo</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="reset_db.php"><i class="bi bi-arrow-counterclockwise"></i> Reset DB</a></li>
            </ul>
        </div>
    </div>
</nav>
