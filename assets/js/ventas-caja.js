// Caja: sumar productos tocándolos, ajustar cantidades y ver el resumen.

;(function () {
  'use strict'

  const formulario = document.getElementById('formularioCaja')
  if (!formulario) return

  const productos = Array.from(formulario.querySelectorAll('[data-producto]'))
  const resumen = document.getElementById('resumenProductos')
  const total = document.getElementById('totalUnidades')
  const botonRegistrar = document.getElementById('botonRegistrarVenta')
  const totalMovil = formulario.querySelector('[data-total-movil]')
  const botonMovil = formulario.querySelector('[data-registrar-movil]')

  function leerCantidad(producto) {
    const valor = parseInt(producto.querySelector('[data-cantidad]').value, 10)
    return valor > 0 ? valor : 0
  }

  function escribirCantidad(producto, cantidad) {
    producto.querySelector('[data-cantidad]').value =
      cantidad > 0 ? cantidad : ''
    actualizar()
  }

  function actualizar() {
    let totalUnidades = 0
    resumen.innerHTML = ''

    productos.forEach(function (producto) {
      const cantidad = leerCantidad(producto)
      const insignia = producto.querySelector('[data-insignia]')
      producto.classList.toggle('seleccionado', cantidad > 0)
      insignia.hidden = cantidad === 0
      insignia.textContent = cantidad

      if (cantidad === 0) return

      totalUnidades += cantidad
      const linea = document.createElement('li')
      linea.className = 'd-flex justify-content-between py-1'
      const nombre = document.createElement('span')
      nombre.textContent = producto.dataset.nombre
      const unidades = document.createElement('span')
      unidades.className = 'fw-semibold'
      unidades.textContent = `× ${cantidad}`
      linea.append(nombre, unidades)
      resumen.append(linea)
    })

    if (totalUnidades === 0) {
      const vacio = document.createElement('li')
      vacio.className = 'text-body-secondary small'
      vacio.textContent = 'Todavía no hay productos.'
      resumen.append(vacio)
    }

    const textoTotal = `${totalUnidades} ${totalUnidades === 1 ? 'unidad' : 'unidades'}`
    total.textContent = textoTotal
    totalMovil.textContent = textoTotal
    botonRegistrar.disabled = totalUnidades === 0
    botonMovil.disabled = totalUnidades === 0
  }

  productos.forEach(function (producto) {
    producto
      .querySelector('[data-agregar]')
      .addEventListener('click', function () {
        escribirCantidad(producto, leerCantidad(producto) + 1)
      })
    producto
      .querySelector('[data-sumar]')
      .addEventListener('click', function () {
        escribirCantidad(producto, leerCantidad(producto) + 1)
      })
    producto
      .querySelector('[data-restar]')
      .addEventListener('click', function () {
        escribirCantidad(producto, leerCantidad(producto) - 1)
      })
    producto
      .querySelector('[data-cantidad]')
      .addEventListener('input', actualizar)
  })

  document
    .getElementById('limpiarVenta')
    .addEventListener('click', function () {
      productos.forEach(function (producto) {
        producto.querySelector('[data-cantidad]').value = ''
      })
      actualizar()
    })

  actualizar()
})()
