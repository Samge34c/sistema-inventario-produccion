<?php

declare(strict_types=1);

/*
 * Parte inferior de todas las páginas. Si la página define $scriptsPagina
 * (lista de rutas), se cargan después del JavaScript común.
 */

$scriptsPagina ??= [];
?>
  </main>

  <footer class="pie py-4">
    <div class="container-xl small text-body-secondary d-flex flex-wrap justify-content-between gap-2">
      <span>Sistema de inventario, recetas y producción</span>
      <span><?= escapar(ucfirst(formatearFechaLarga(obtenerFechaHoy()))) ?></span>
    </div>
  </footer>

  <!-- Ventana de confirmación para acciones que no se pueden deshacer -->
  <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title fs-5" id="modalConfirmacionTitulo">¿Confirma la acción?</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body" id="modalConfirmacionMensaje"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
          <button type="button" class="btn btn-danger" id="modalConfirmacionAceptar">Confirmar</button>
        </div>
      </div>
    </div>
  </div>

  <script src="<?= escapar(url('assets/lib/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
  <script src="<?= escapar(url('assets/js/app.js')) ?>"></script>
  <?php foreach ($scriptsPagina as $script) : ?>
    <script src="<?= escapar(url($script)) ?>"></script>
  <?php endforeach; ?>
</body>
</html>
