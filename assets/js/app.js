/* global bootstrap */

;(function () {
  'use strict'

  function normalizarTexto(texto) {
    return texto
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim()
  }

  function iniciarConfirmaciones() {
    const elementoModal = document.getElementById('modalConfirmacion')
    if (!elementoModal) return

    const modal = new bootstrap.Modal(elementoModal)
    const mensaje = document.getElementById('modalConfirmacionMensaje')
    const botonAceptar = document.getElementById('modalConfirmacionAceptar')
    let formularioPendiente = null

    document.addEventListener(
      'submit',
      function (evento) {
        const formulario = evento.target
        if (
          !formulario.dataset.confirmar ||
          formulario.dataset.confirmado === '1'
        )
          return

        evento.preventDefault()
        evento.stopImmediatePropagation()
        formularioPendiente = formulario
        mensaje.textContent = formulario.dataset.confirmar
        botonAceptar.textContent =
          formulario.dataset.confirmarBoton || 'Confirmar'
        modal.show()
      },
      true,
    )

    botonAceptar.addEventListener('click', function () {
      if (!formularioPendiente) return
      formularioPendiente.dataset.confirmado = '1'
      modal.hide()
      formularioPendiente.requestSubmit()
    })
  }

  function evitarDobleEnvio() {
    document.addEventListener('submit', function (evento) {
      const formulario = evento.target
      if (evento.defaultPrevented || formulario.method.toLowerCase() !== 'post')
        return

      if (formulario.dataset.enviando === '1') {
        evento.preventDefault()
        return
      }

      formulario.dataset.enviando = '1'
      formulario.querySelectorAll('[type="submit"]').forEach(function (boton) {
        boton.classList.add('disabled')
        boton.setAttribute('aria-disabled', 'true')
      })
    })
  }

  function iniciarFiltrosDeTabla() {
    document.querySelectorAll('[data-filtrar]').forEach(function (campo) {
      const tabla = document.querySelector(campo.dataset.filtrar)
      if (!tabla) return

      const filas = Array.from(
        tabla.querySelectorAll('tbody tr:not([data-sin-resultados])'),
      )
      const filaVacia = tabla.querySelector('[data-sin-resultados]')

      campo.addEventListener('input', function () {
        const busqueda = normalizarTexto(campo.value)
        let visibles = 0

        filas.forEach(function (fila) {
          const coincide = normalizarTexto(fila.textContent).includes(busqueda)
          fila.hidden = !coincide
          if (coincide) visibles++
        })

        if (filaVacia) filaVacia.hidden = visibles > 0
      })
    })
  }

  function enfocarPrimerError() {
    const campo = document.querySelector('.is-invalid')
    if (campo) campo.focus()
  }

  function iniciarAyudas() {
    document
      .querySelectorAll('[data-bs-toggle="tooltip"]')
      .forEach(function (elemento) {
        bootstrap.Tooltip.getOrCreateInstance(elemento)
      })
  }

  function iniciarAtajos() {
    document
      .querySelectorAll('[data-enviar-al-cambiar]')
      .forEach(function (campo) {
        campo.addEventListener('change', function () {
          campo.form.requestSubmit()
        })
      })

    document.querySelectorAll('[data-imprimir]').forEach(function (boton) {
      boton.addEventListener('click', function () {
        window.print()
      })
    })
  }

  function ocultarAvisosDeExito() {
    document.querySelectorAll('.alert-success').forEach(function (aviso) {
      setTimeout(function () {
        bootstrap.Alert.getOrCreateInstance(aviso).close()
      }, 6000)
    })
  }

  document.addEventListener('DOMContentLoaded', function () {
    ocultarAvisosDeExito()
    iniciarConfirmaciones()
    evitarDobleEnvio()
    iniciarFiltrosDeTabla()
    enfocarPrimerError()
    iniciarAyudas()
    iniciarAtajos()
  })

  window.normalizarTexto = normalizarTexto
})()
