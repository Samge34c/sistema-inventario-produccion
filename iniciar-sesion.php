<?php

declare(strict_types=1);

require_once __DIR__ . '/config/acceso.php';
iniciarSesionSegura();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirTokenAcceso();
    $correo = is_string($_POST['correo'] ?? null) ? strtolower(trim($_POST['correo'])) : '';
    $clave = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';
    require __DIR__ . '/config/database.php';
    $consulta = $conexion->prepare('SELECT id, nombre, clave_hash FROM usuarios WHERE correo = ? AND activo = 1');
    $consulta->execute([$correo]);
    $usuario = $consulta->fetch();
    if ($usuario !== false && password_verify($clave, $usuario['clave_hash'])) {
        session_regenerate_id(true);
        $_SESSION = ['usuario_id' => (int) $usuario['id'], 'usuario_nombre' => $usuario['nombre']];
        header('Location: /index.php', true, 303);
        exit;
    }
    http_response_code(401);
    $error = 'No se pudo iniciar sesión con esos datos.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5" style="max-width: 480px">
    <h1 class="h3">Iniciar sesión</h1>
    <?php if ($error !== ''): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form method="post" class="card card-body gap-3">
        <input type="hidden" name="token" value="<?= obtenerTokenAcceso() ?>">
        <label for="correo">Correo</label>
        <input class="form-control" id="correo" name="correo" type="email" maxlength="190" autocomplete="username" required>
        <label for="clave">Contraseña</label>
        <input class="form-control" id="clave" name="clave" type="password" autocomplete="current-password" required>
        <button class="btn btn-primary" type="submit">Entrar</button>
    </form>
</main>
</body>
</html>
