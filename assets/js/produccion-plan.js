;(function () {
  'use strict'

  const formulario = document.getElementById('formularioPlan')
  if (!formulario) return

  const recetas = new Map(
    JSON.parse(document.getElementById('datosRecetas').textContent).map(
      function (receta) {
        return [String(receta.id), receta]
      },
    ),
  )
  const selectorReceta = document.getElementById('receta_id')
  const campoCantidad = document.getElementById('cantidad_planeada')
  const campoFecha = document.getElementById('fecha_planeada')
  const atajos = document.getElementById('atajosCantidad')
  const vista = document.getElementById('vistaMateriales')
  const formato = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 })

  function crearAtajo(texto, valor) {
    const boton = document.createElement('button')
    boton.type = 'button'
    boton.className = 'btn btn-link btn-sm p-0'
    boton.textContent = texto
    boton.addEventListener('click', function () {
      campoCantidad.value = valor
      actualizarVista()
    })
    return boton
  }

  function actualizarAtajos(receta) {
    atajos.innerHTML = ''
    if (!receta) return

    atajos.append(
      crearAtajo(`1 tanda (${receta.rendimiento})`, receta.rendimiento),
    )
    if (receta.promedio)
      atajos.append(
        crearAtajo(`Venta diaria (${receta.promedio})`, receta.promedio),
      )
    if (receta.maximo > 0)
      atajos.append(
        crearAtajo(`Máximo posible (${receta.maximo})`, receta.maximo),
      )
  }

  function crearCelda(texto, clase) {
    const celda = document.createElement('td')
    celda.textContent = texto
    if (clase) celda.className = clase
    return celda
  }

  function actualizarVista() {
    const receta = recetas.get(selectorReceta.value)
    const unidades = parseInt(campoCantidad.value, 10)
    vista.innerHTML = ''

    if (!receta || !(unidades > 0)) {
      const aviso = document.createElement('p')
      aviso.className = 'text-body-secondary mb-0'
      aviso.textContent =
        'Elija el producto y la cantidad para ver los materiales.'
      vista.append(aviso)
      return
    }

    const tandas = unidades / receta.rendimiento
    const encabezado = document.createElement('p')
    encabezado.className = 'small text-body-secondary'
    encabezado.textContent = `${formato.format(tandas)} ${tandas === 1 ? 'tanda' : 'tandas'} de ${receta.rendimiento} unidades.`
    vista.append(encabezado)

    const tabla = document.createElement('table')
    tabla.className = 'table table-sm align-middle mb-0'
    tabla.innerHTML =
      '<thead><tr><th scope="col">Ingrediente</th><th scope="col" class="text-end">Necesita</th>' +
      '<th scope="col" class="text-end">Hay</th><th scope="col" class="text-end">Falta</th></tr></thead>'
    const cuerpo = document.createElement('tbody')
    let hayFaltantes = false

    receta.ingredientes.forEach(function (ingrediente) {
      const requerido =
        Math.round(
          ((unidades * ingrediente.cantidad) / receta.rendimiento) * 100,
        ) / 100
      const falta = Math.max(
        0,
        Math.round((requerido - ingrediente.disponible) * 100) / 100,
      )
      hayFaltantes = hayFaltantes || falta > 0

      const fila = document.createElement('tr')
      fila.append(
        crearCelda(ingrediente.nombre),
        crearCelda(
          `${formato.format(requerido)} ${ingrediente.unidad}`,
          'numero',
        ),
        crearCelda(
          `${formato.format(ingrediente.disponible)}`,
          'numero text-body-secondary',
        ),
        crearCelda(
          falta > 0 ? `${formato.format(falta)} ${ingrediente.unidad}` : '-',
          falta > 0 ? 'numero texto-faltante' : 'numero text-body-secondary',
        ),
      )
      cuerpo.append(fila)
    })

    tabla.append(cuerpo)
    vista.append(tabla)

    const resumen = document.createElement('div')
    resumen.className = hayFaltantes
      ? 'alert alert-warning small mt-3 mb-0'
      : 'alert alert-success small mt-3 mb-0'
    resumen.textContent = hayFaltantes
      ? `Con la existencia de hoy no alcanza. Máximo posible ahora: ${formato.format(receta.maximo)} unidades. Puede guardarlo igual si espera un pedido.`
      : 'Con la existencia de hoy alcanza.'
    vista.append(resumen)
  }

  selectorReceta.addEventListener('change', function () {
    const receta = recetas.get(selectorReceta.value)
    actualizarAtajos(receta)
    if (receta && !campoCantidad.value)
      campoCantidad.value = receta.promedio || receta.rendimiento
    actualizarVista()
  })

  campoCantidad.addEventListener('input', actualizarVista)

  formulario.querySelectorAll('[data-fecha]').forEach(function (boton) {
    boton.addEventListener('click', function () {
      campoFecha.value = boton.dataset.fecha
    })
  })

  actualizarAtajos(recetas.get(selectorReceta.value))
  actualizarVista()
})()
