;(function () {
  'use strict'

  const formulario = document.getElementById('formularioReceta')
  if (!formulario) return

  const materias = new Map(
    JSON.parse(document.getElementById('datosMaterias').textContent).map(
      function (materia) {
        return [String(materia.id), materia]
      },
    ),
  )
  const lista = document.getElementById('listaIngredientes')
  const plantilla = document.getElementById('plantillaIngrediente')
  const campoRendimiento = document.getElementById('cantidad_producir')
  const vistaCapacidad = document.getElementById('vistaCapacidad')
  const formato = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 })
  let siguienteIndice = lista.querySelectorAll('[data-fila-ingrediente]').length

  function actualizarUnidad(fila) {
    const materia = materias.get(
      fila.querySelector('[data-campo-materia]').value,
    )
    fila.querySelector('[data-unidad]').textContent = materia
      ? materia.unidad
      : '-'
  }

  function actualizarCapacidad() {
    const rendimiento = parseInt(campoRendimiento.value, 10)
    let unidadesMaximas = null
    let limitante = ''

    lista.querySelectorAll('[data-fila-ingrediente]').forEach(function (fila) {
      const selector = fila.querySelector('[data-campo-materia]')
      const materia = materias.get(selector.value)
      const cantidad = parseFloat(
        fila.querySelector('[data-campo-cantidad]').value,
      )
      if (!materia || !(cantidad > 0) || !(rendimiento > 0)) return

      const unidades = Math.floor(
        (materia.disponible * rendimiento) / cantidad + 1e-6,
      )
      if (unidadesMaximas === null || unidades < unidadesMaximas) {
        unidadesMaximas = unidades
        limitante = selector.selectedOptions[0].textContent
      }
    })

    if (unidadesMaximas === null) {
      vistaCapacidad.hidden = true
      return
    }

    vistaCapacidad.hidden = false
    vistaCapacidad.textContent = `Alcanza para ${formato.format(unidadesMaximas)} unidades. Limita: ${limitante}.`
  }

  function agregarFila() {
    const html = plantilla.innerHTML.replaceAll(
      '__indice__',
      String(siguienteIndice++),
    )
    lista.insertAdjacentHTML('beforeend', html)
    const nuevaFila = lista.lastElementChild
    actualizarUnidad(nuevaFila)
    nuevaFila.querySelector('[data-campo-materia]').focus()
  }

  lista.addEventListener('change', function (evento) {
    if (evento.target.matches('[data-campo-materia]')) {
      actualizarUnidad(evento.target.closest('[data-fila-ingrediente]'))
    }
    actualizarCapacidad()
  })

  lista.addEventListener('input', actualizarCapacidad)
  campoRendimiento.addEventListener('input', actualizarCapacidad)

  lista.addEventListener('click', function (evento) {
    const boton = evento.target.closest('[data-quitar-ingrediente]')
    if (!boton) return

    const filas = lista.querySelectorAll('[data-fila-ingrediente]')
    const fila = boton.closest('[data-fila-ingrediente]')

    if (filas.length === 1) {
      fila.querySelector('[data-campo-materia]').value = ''
      fila.querySelector('[data-campo-cantidad]').value = ''
      actualizarUnidad(fila)
    } else {
      fila.remove()
    }
    actualizarCapacidad()
  })

  document
    .getElementById('agregarIngrediente')
    .addEventListener('click', agregarFila)

  lista.querySelectorAll('[data-fila-ingrediente]').forEach(actualizarUnidad)
  actualizarCapacidad()
})()
