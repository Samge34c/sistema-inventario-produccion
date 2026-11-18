<?php

declare(strict_types=1);

const PAGINA_PUBLICA = true;

require_once __DIR__ . '/includes/inicio.php';

if (isset($_SESSION['usuario_id'])) {
    redirigir('index.php');
}

$error = '';
$correo = '';

if (esPeticionPost()) {
    verificarTokenCsrf('iniciar-sesion.php');
    $correo = is_string($_POST['correo'] ?? null) ? strtolower(trim($_POST['correo'])) : '';
    $clave = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';
    $consulta = obtenerConexion()->prepare('SELECT id, nombre, clave_hash FROM usuarios WHERE correo = ? AND activo = 1');
    $consulta->execute([$correo]);
    $usuario = $consulta->fetch();

    if ($usuario !== false && password_verify($clave, $usuario['clave_hash'])) {
        session_regenerate_id(true);
        $_SESSION = ['usuario_id' => (int) $usuario['id'], 'usuario_nombre' => $usuario['nombre']];
        header('Location: ' . url('index.php'), true, 303);
        exit;
    }

    http_response_code(401);
    $error = 'No se pudo iniciar sesión con esos datos.';
}

$nombreNegocio = (string) obtenerConfiguracion('nombre_negocio');
$mensajes = obtenerMensajes();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión - <?= escapar($nombreNegocio) ?></title>
  <link rel="stylesheet" href="<?= escapar(url('assets/lib/bootstrap/bootstrap.min.css')) ?>">
  <link rel="stylesheet" href="<?= escapar(url('assets/css/estilos.css')) ?>">
</head>
<body class="bg-light">
  <main class="container py-5" style="max-width: 420px">
    <h1 class="h3 mb-4 text-center"><?= escapar($nombreNegocio) ?></h1>

    <?php foreach ($mensajes as $mensaje) : ?>
      <p class="alert alert-<?= $mensaje['tipo'] === 'error' ? 'danger' : 'info' ?>" role="alert"><?= escapar($mensaje['texto']) ?></p>
    <?php endforeach; ?>
    <?php if ($error !== '') : ?>
      <p class="alert alert-danger" role="alert"><?= escapar($error) ?></p>
    <?php endif; ?>

    <form method="post" class="card card-body">
      <?= generarCampoCsrf() ?>
      <div class="mb-3">
        <label class="form-label" for="correo">Correo</label>
        <input class="form-control" id="correo" name="correo" type="email" maxlength="190" autocomplete="username"
          required autofocus value="<?= escapar($correo) ?>">
      </div>
      <div class="mb-4">
        <label class="form-label" for="clave">Contraseña</label>
        <input class="form-control" id="clave" name="clave" type="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-primary w-100" type="submit">Entrar</button>
    </form>
  </main>
</body>
</html>
