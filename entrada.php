<?php
require_once 'config.php';

$mensaje        = null;
$error          = null;
$errorConflicto = null;
$registroActivo = null;
$vehiculo       = null;

// ─── Buscar vehículo por placa ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['buscar_placa'])) {
    $placa = strtoupper(trim($_GET['placa'] ?? ''));
    if (!empty($placa)) {
        $vehiculo = apiRequest('/vehiculos?placa=' . urlencode($placa));
        if (isset($vehiculo['error'])) {
            $error    = "Vehículo con placa <strong>$placa</strong> no está registrado. Regístrelo primero.";
            $vehiculo = null;
        }
    }
}

// ─── Registrar nueva entrada ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vehiculoId'])) {
    $resp = apiRequest('/registros', 'POST', ['vehiculoId' => (int)$_POST['vehiculoId']]);

    if (isset($resp['registroId'])) {
        header('Location: index.php?entrada_ok=1');
        exit;
    }

    // ── Conflicto 409: vehículo ya adentro ──
    if (isset($resp['registroActivo'])) {
        $errorConflicto = $resp['error'];
        $registroActivo = $resp['registroActivo'];
        // Recuperar datos del vehículo para seguir mostrándolos
        $vehiculo = apiRequest('/vehiculos/' . (int)$_POST['vehiculoId']);
    } else {
        $error = $resp['error'] ?? 'Error al registrar entrada';
    }
}

// ─── Crear vehículo nuevo ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_vehiculo'])) {
    $nuevoVehiculo = [
        'placa'       => strtoupper(trim($_POST['placa']       ?? '')),
        'tipo'        => $_POST['tipo']        ?? 'CARRO',
        'propietario' => trim($_POST['propietario'] ?? ''),
        'telefono'    => trim($_POST['telefono']    ?? ''),
    ];
    $resp = apiRequest('/vehiculos', 'POST', $nuevoVehiculo);
    if (!isset($resp['error'])) {
        $vehiculo = apiRequest('/vehiculos?placa=' . urlencode($nuevoVehiculo['placa']));
        $mensaje  = "Vehículo registrado. Ahora confirme la entrada.";
    } else {
        $error = $resp['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Entrada - Parqueadero Boyacá</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* ── Alerta conflicto 409 ── */
        .alerta-conflicto {
            background: #FFF8E1;
            border-left: 4px solid #F9A825;
            color: #5D4037;
            padding: 1.2rem 1.5rem;
            border-radius: 6px;
            margin-bottom: 1.2rem;
        }
        .alerta-conflicto strong { color: #E65100; }
        .conflicto-acciones {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .badge-activo {
            display: inline-block;
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #A5D6A7;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="logo">🅿️ Parqueadero Boyacá</div>
        <nav>
            <a href="index.php"    class="nav-link">🏠 Inicio</a>
            <a href="entrada.php"  class="nav-link active">⬇️ Registrar Entrada</a>
            <a href="historial.php" class="nav-link">📋 Historial</a>
            <a href="vehiculos.php" class="nav-link">🚗 Vehículos</a>
            <a href="reportes.php"  class="nav-link">📊 Reporte</a>
        </nav>
    </div>
</header>

<main class="container">
    <h1 class="titulo-pagina">⬇️ Registrar Entrada</h1>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-exito">✅ <?= $mensaje ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alerta alerta-error">❌ <?= $error ?></div>
    <?php endif; ?>

    <?php if ($errorConflicto): ?>
        <div class="alerta-conflicto">
            <div>
                ⚠️ <strong>Vehículo ya se encuentra dentro del parqueadero</strong>
                <span class="badge-activo">Registro activo #<?= $registroActivo ?></span>
            </div>
            <p style="margin-top:0.5rem;font-size:0.93rem;">
                Este vehículo ya tiene un registro activo. Debe registrar su salida antes de permitir una nueva entrada.
            </p>
            <div class="conflicto-acciones">
                <form method="POST" action="salida.php">
                    <input type="hidden" name="registroId" value="<?= $registroActivo ?>">
                    <button type="submit" class="btn btn-rojo">⬆️ Registrar salida del registro #<?= $registroActivo ?></button>
                </form>
                <a href="entrada.php" class="btn btn-azul">🔄 Buscar otro vehículo</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Paso 1: Buscar por placa -->
    <section class="seccion seccion-card">
        <h2>Paso 1 — Buscar vehículo por placa</h2>
        <form method="GET" class="form-inline">
            <input type="hidden" name="buscar_placa" value="1">
            <input type="text" name="placa" placeholder="Ej: BOY001"
                   value="<?= htmlspecialchars($_GET['placa'] ?? '') ?>"
                   class="input-texto input-grande" maxlength="10" required>
            <button type="submit" class="btn btn-azul">🔍 Buscar</button>
        </form>
    </section>

    <!-- Paso 2a: Vehículo encontrado → confirmar entrada -->
    <?php if ($vehiculo && !isset($vehiculo['error']) && !$errorConflicto): ?>
    <section class="seccion seccion-card seccion-verde">
        <h2>✅ Vehículo encontrado</h2>
        <div class="info-vehiculo">
            <div class="info-item"><span class="info-label">Placa:</span> <strong class="placa"><?= htmlspecialchars($vehiculo['placa']) ?></strong></div>
            <div class="info-item"><span class="info-label">Tipo:</span> <?= htmlspecialchars($vehiculo['tipo']) ?></div>
            <div class="info-item"><span class="info-label">Propietario:</span> <?= htmlspecialchars($vehiculo['propietario']) ?></div>
            <div class="info-item"><span class="info-label">Teléfono:</span> <?= htmlspecialchars($vehiculo['telefono']) ?></div>
        </div>
        <form method="POST">
            <input type="hidden" name="vehiculoId" value="<?= $vehiculo['id'] ?>">
            <button type="submit" class="btn btn-verde btn-grande">
                ⬇️ Confirmar Entrada — <?= date('H:i:s') ?>
            </button>
        </form>
    </section>
    <?php endif; ?>

    <!-- Paso 2b: Vehículo NO existe → formulario para crearlo -->
    <?php if ($error && isset($_GET['placa'])): ?>
    <section class="seccion seccion-card">
        <h2>➕ Registrar nuevo vehículo</h2>
        <form method="POST" class="form-vertical">
            <input type="hidden" name="crear_vehiculo" value="1">
            <div class="campo">
                <label>Placa</label>
                <input type="text" name="placa"
                       value="<?= htmlspecialchars(strtoupper($_GET['placa'])) ?>"
                       class="input-texto" maxlength="10" required readonly>
            </div>
            <div class="campo">
                <label>Tipo de vehículo</label>
                <select name="tipo" class="input-texto">
                    <option value="CARRO">🚗 Carro</option>
                    <option value="MOTO">🏍️ Moto</option>
                    <option value="CAMION">🚛 Camión</option>
                </select>
            </div>
            <div class="campo">
                <label>Nombre del propietario</label>
                <input type="text" name="propietario" class="input-texto" placeholder="Nombre completo" required>
            </div>
            <div class="campo">
                <label>Teléfono</label>
                <input type="text" name="telefono" class="input-texto" placeholder="3XXXXXXXXX">
            </div>
            <button type="submit" class="btn btn-azul btn-grande">💾 Guardar y registrar entrada</button>
        </form>
    </section>
    <?php endif; ?>

</main>

<footer class="footer">
    <p>SENA CIMM · ADSO 228118 · Regional Boyacá</p>
</footer>

<script src="js/app.js"></script>
</body>
</html>