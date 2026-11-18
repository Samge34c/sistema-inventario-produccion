;(function () {
  'use strict'

  const modal = document.getElementById('modalRecibir')
  if (!modal) return

  modal.addEventListener('show.bs.modal', function (evento) {
    const boton = evento.relatedTarget
    const datos = boton.dataset

    document.getElementById('recibirPedidoId').value = datos.pedidoId
    document.getElementById('recibirDescripcion').textContent =
      `Pedido #${datos.pedidoId}: ${datos.cantidadTexto} ${datos.unidad} de ${datos.materia}.`
    document.getElementById('recibirUnidad').textContent = datos.unidad
    document.getElementById('cantidad_recibida').value = datos.cantidad
  })

  modal.addEventListener('shown.bs.modal', function () {
    document.getElementById('cantidad_recibida').select()
  })
})()
