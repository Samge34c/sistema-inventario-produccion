// Calculadora de la receta: materiales necesarios para una cantidad de
// unidades, comparados con la existencia actual.

;(function () {
  'use strict'

  const fuente = document.getElementById('datosCalculadora')
  if (!fuente) return

  const datos = JSON.parse(fuente.textContent)
  const campo = document.getElementById('unidadesCalculadora')
  const resultado = document.getElementById('resultadoCalculadora')
  const enlacePlan = document.getElementById('planificarCalculadora')
  const formato = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 })

  function crearCelda(texto, clase) {
    const celda = document.createElement('td')
    celda.textContent = texto
    if (clase) celda.className = clase
    return celda
  }

  function calcular() {
    const unidades = parseInt(campo.value, 10)
    resultado.innerHTML = ''

    if (!(unidades > 0)) {
      enlacePlan.classList.add('disabled')
      return
    }

    enlacePlan.classList.remove('disabled')
    enlacePlan.href = `${datos.urlPlan}&cantidad=${unidades}`

    const tabla = document.createElement('table')
    tabla.className = 'table table-sm align-middle mb-0'
    tabla.innerHTML =
      '<thead><tr><th scope="col">Ingrediente</th><th scope="col" class="text-end">Necesita</th>' +
      '<th scope="col" class="text-end">Falta</th></tr></thead>'
    const cuerpo = document.createElement('tbody')
    let faltantes = 0

    datos.ingredientes.forEach(function (ingrediente) {
      const requerido =
        Math.round(
          ((unidades * ingrediente.cantidad) / datos.rendimiento) * 100,
        ) / 100
      const falta = Math.max(
        0,
        Math.round((requerido - ingrediente.disponible) * 100) / 100,
      )
      if (falta > 0) faltantes++

      const fila = document.createElement('tr')
      fila.append(
        crearCelda(ingrediente.nombre),
        crearCelda(
          `${formato.format(requerido)} ${ingrediente.unidad}`,
          'numero',
        ),
        crearCelda(
          falta > 0 ? `${formato.format(falta)} ${ingrediente.unidad}` : '—',
          falta > 0 ? 'numero texto-faltante' : 'numero text-body-secondary',
        ),
      )
      cuerpo.append(fila)
    })

    tabla.append(cuerpo)
    resultado.append(tabla)

    const resumen = document.createElement('p')
    resumen.className =
      faltantes > 0
        ? 'small texto-faltante mt-2 mb-0'
        : 'small text-success mt-2 mb-0'
    resumen.textContent =
      faltantes > 0
        ? `${faltantes === 1 ? 'Falta 1 ingrediente' : `Faltan ${faltantes} ingredientes`} con la existencia actual.`
        : 'Alcanza con lo que hay en inventario.'
    resultado.append(resumen)
  }

  campo.addEventListener('input', calcular)
  calcular()
})()
