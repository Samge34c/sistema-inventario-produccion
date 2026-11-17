// Formulario de pedido: muestra la unidad, la existencia de la materia prima
// elegida y una cantidad sugerida para llegar al doble del mínimo.

;(function () {
  'use strict'

  const selector = document.getElementById('materia_prima_id')
  if (!selector) return

  const unidad = document.getElementById('unidadCantidad')
  const contexto = document.getElementById('contextoMateria')
  const botonSugerencia = document.getElementById('usarSugerencia')
  const campoCantidad = document.getElementById('cantidad')

  function actualizar() {
    const opcion = selector.selectedOptions[0]
    const datos = opcion ? opcion.dataset : {}

    if (!datos.unidad) {
      unidad.textContent = '—'
      contexto.textContent = ''
      botonSugerencia.hidden = true
      return
    }

    unidad.textContent = datos.unidad
    contexto.textContent = `Hay ${datos.existencia} · mínimo ${datos.minimo} · ya pedido ${datos.porLlegar}.`

    const sugerencia = parseFloat(datos.sugerencia)
    botonSugerencia.hidden = !(sugerencia > 0)
    botonSugerencia.textContent = `Usar ${sugerencia.toLocaleString('es-CO')} ${datos.unidad} (para llegar al doble del mínimo)`
    botonSugerencia.dataset.valor = datos.sugerencia
  }

  botonSugerencia.addEventListener('click', function () {
    campoCantidad.value = botonSugerencia.dataset.valor
    campoCantidad.focus()
  })

  selector.addEventListener('change', actualizar)
  actualizar()
})()
