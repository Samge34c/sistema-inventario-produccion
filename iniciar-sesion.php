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
  <title>Iniciar sesión · <?= escapar($nombreNegocio) ?></title>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%A5%96%3C/text%3E%3C/svg%3E">
  <link rel="stylesheet" href="<?= escapar(url('assets/lib/bootstrap/bootstrap.min.css')) ?>">
  <link rel="stylesheet" href="<?= escapar(url('assets/lib/bootstrap-icons/bootstrap-icons.min.css')) ?>">
  <link rel="stylesheet" href="<?= escapar(url('assets/css/estilos.css')) ?>">
</head>
<body class="pagina-acceso">
  <main class="container py-5" style="max-width: 440px">
    <div class="text-center mb-4">
      <span class="marca-icono marca-icono-grande" aria-hidden="true"><i class="bi bi-basket2-fill"></i></span>
      <h1 class="h3 mt-3 mb-1"><?= escapar($nombreNegocio) ?></h1>
      <p class="text-body-secondary">Inventario, recetas y producción</p>
    </div>

    <?php foreach ($mensajes as $mensaje) : ?>
      <p class="alert alert-<?= $mensaje['tipo'] === 'error' ? 'danger' : 'info' ?>" role="alert"><?= escapar($mensaje['texto']) ?></p>
    <?php endforeach; ?>
    <?php if ($error !== '') : ?>
      <p class="alert alert-danger" role="alert"><?= escapar($error) ?></p>
    <?php endif; ?>

    <form method="post" class="tarjeta p-4">
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
    <p class="texto-ayuda text-center mt-3">
      Las cuentas se crean desde la terminal con <code>herramientas/crear-usuario.php</code> (ver README).
    </p>
  </main>
</body>
</html>
