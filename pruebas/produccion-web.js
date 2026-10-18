const { chromium } = require('playwright')
const assert = require('node:assert/strict')
const fs = require('node:fs')

async function comprobarProduccionWeb() {
  const url = process.env.HU02_URL
  assert.ok(['localhost', '127.0.0.1'].includes(new URL(url).hostname))
  const datos = JSON.parse(process.env.PRODUCCION_DATOS_WEB)
  const navegador = await chromium.launch({
    executablePath: process.env.HU02_CHROME || undefined,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  })
  let casos = 0
  const comprobar = (condicion, nombre) => {
    assert.ok(condicion, nombre)
    console.log(`OK PRODUCCION WEB ${++casos}: ${nombre}`)
  }
  const pagina = await navegador.newPage()
  const errores = []
  pagina.on('pageerror', (error) => errores.push(error.message))
  pagina.on('console', (mensaje) => {
    if (mensaje.type() === 'error') errores.push(mensaje.text())
  })
  if (process.env.HU02_BOOTSTRAP_PRUEBA) {
    const css = fs.readFileSync(process.env.HU02_BOOTSTRAP_PRUEBA, 'utf8')
    await pagina.route(
      'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css',
      (ruta) =>
        ruta.fulfill({ status: 200, contentType: 'text/css', body: css }),
    )
  }
  try {
    for (const ruta of [
      'index.php',
      'recetas-posibles.php',
      'proyeccion.php',
    ]) {
      await pagina.goto(`${url}/modules/produccion/${ruta}`)
      comprobar(
        await pagina
          .getByRole('heading', { name: 'Iniciar sesión' })
          .isVisible(),
        `sin sesión se protege ${ruta}`,
      )
    }
    await pagina.locator('#correo').fill(process.env.HU02_PRUEBA_CORREO)
    await pagina.locator('#clave').fill(process.env.HU02_PRUEBA_CLAVE_USUARIO)
    await pagina.getByRole('button', { name: 'Entrar', exact: true }).click()
    await pagina
      .getByRole('link', { name: 'Producción máxima', exact: true })
      .click()
    await pagina.locator('#receta_id').selectOption(String(datos.receta))
    await pagina.getByRole('button', { name: 'Calcular', exact: true }).click()
    comprobar(
      (await pagina.locator('#maximo-unidades').innerText()) === '5',
      'HU04: cálculo desde formulario da cinco',
    )
    comprobar(
      (await pagina.locator('section').innerText()).includes(
        'Ingredientes limitantes: Azúcar cálculo',
      ),
      'HU04: limitante visible',
    )
    await pagina
      .getByRole('link', { name: 'Recetas posibles', exact: true })
      .click()
    const filas = pagina.locator('#recetas-posibles tbody')
    comprobar(
      (
        await filas.locator(`[data-receta-id="${datos.posible}"]`).innerText()
      ).includes('Posible'),
      'HU05: receta viable identificada',
    )
    comprobar(
      (
        await filas.locator(`[data-receta-id="${datos.imposible}"]`).innerText()
      ).includes('No posible'),
      'HU05: receta no viable distinguida',
    )
    comprobar(
      (
        await filas.locator(`[data-receta-id="${datos.vacia}"]`).innerText()
      ).includes('Revisar receta'),
      'HU05: receta vacía informa revisión sin romper lista',
    )
    await pagina
      .getByRole('link', { name: 'Proyección con pendientes', exact: true })
      .click()
    await pagina.locator('#receta_id').selectOption(String(datos.proyeccion))
    await pagina.getByRole('button', { name: 'Proyectar', exact: true }).click()
    comprobar(
      (await pagina.locator('#unidades-actuales').innerText()) === '2',
      'HU06: producción actual separada',
    )
    comprobar(
      (await pagina.locator('#unidades-proyectadas').innerText()) === '3',
      'HU06: producción proyectada separada',
    )
    comprobar(
      (await pagina.locator('tbody').innerText()).includes('500.00'),
      'HU06: pendiente y cantidad por unidad visibles',
    )
    await pagina.setViewportSize({ width: 390, height: 844 })
    comprobar(
      await pagina.locator('#unidades-proyectadas').isVisible(),
      'proyección legible en móvil 390 px',
    )
    comprobar(
      await pagina.evaluate(
        () => document.documentElement.scrollWidth <= window.innerWidth,
      ),
      'sin desbordamiento horizontal del documento en móvil',
    )
    comprobar(
      errores.length === 0,
      'sin excepciones ni errores de consola de los flujos válidos',
    )
    const respuesta = await pagina.goto(
      `${url}/modules/produccion/index.php?receta_id[]=1`,
    )
    comprobar(
      respuesta.status() === 400 &&
        (await pagina.getByRole('alert').isVisible()),
      'identificador con forma de arreglo rechazado de forma controlada',
    )
    if (process.env.PRODUCCION_CAPTURAS) {
      fs.mkdirSync(process.env.PRODUCCION_CAPTURAS, { recursive: true })
      await pagina.goto(
        `${url}/modules/produccion/proyeccion.php?receta_id=${datos.proyeccion}`,
      )
      await pagina.screenshot({
        path: process.env.PRODUCCION_CAPTURAS + '/proyeccion-movil.png',
        fullPage: true,
      })
    }
    console.log(`RESULTADO PRODUCCION WEB: ${casos} comprobaciones correctas.`)
  } finally {
    await navegador.close()
  }
}

comprobarProduccionWeb().catch((error) => {
  console.error(error)
  process.exitCode = 1
})
