// Muestra cuánto quedará en existencia mientras se escribe la cantidad.

;(function () {
  'use strict'

  const formulario = document.getElementById('formularioMovimiento')
  if (!formulario) return

  const campoCantidad = document.getElementById('cantidad')
  const vistaPrevia = document.getElementById('vistaPrevia')
  const existencia = parseFloat(formulario.dataset.existencia) || 0
  const minimo = parseFloat(formulario.dataset.minimo) || 0
  const unidad = formulario.dataset.unidad
  const formato = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 })

  function actualizarVistaPrevia() {
    const cantidad = parseFloat(campoCantidad.value.replace(',', '.'))
    const esSalida =
      formulario.querySelector('[name="tipo"]:checked')?.value === 'SALIDA'

    if (!(cantidad > 0)) {
      vistaPrevia.textContent = ''
      vistaPrevia.className = 'form-text'
      return
    }

    const resultado = esSalida ? existencia - cantidad : existencia + cantidad

    if (resultado < 0) {
      vistaPrevia.textContent = `No alcanza: solo hay ${formato.format(existencia)} ${unidad}.`
      vistaPrevia.className = 'form-text text-danger fw-semibold'
      return
    }

    let texto = `Quedarán ${formato.format(resultado)} ${unidad}.`
    if (resultado < minimo) texto += ' Queda por debajo del mínimo.'
    vistaPrevia.textContent = texto
    vistaPrevia.className =
      resultado < minimo ? 'form-text text-warning-emphasis' : 'form-text'
  }

  campoCantidad.addEventListener('input', actualizarVistaPrevia)
  formulario.querySelectorAll('[name="tipo"]').forEach(function (opcion) {
    opcion.addEventListener('change', actualizarVistaPrevia)
  })
  actualizarVistaPrevia()
})()
