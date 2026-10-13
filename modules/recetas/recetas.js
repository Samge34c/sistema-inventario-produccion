const contenedor = document.getElementById('ingredientes')
const boton = document.getElementById('agregar-ingrediente')
if (contenedor && boton) {
  boton.addEventListener('click', () => {
    const numero = contenedor.children.length
    if (numero >= 100) return
    const fila = contenedor.firstElementChild.cloneNode(true)
    fila.querySelectorAll('select, input').forEach((campo) => {
      campo.name = campo.name.replace(/\[\d+\]/, `[${numero}]`)
      campo.value = ''
    })
    contenedor.appendChild(fila)
  })
}
