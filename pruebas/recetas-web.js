const { chromium } = require('playwright')
const assert = require('node:assert/strict')
const fs = require('node:fs')

async function comprobarRecetas() {
  const url = process.env.HU02_URL
  assert.ok(['127.0.0.1', 'localhost'].includes(new URL(url).hostname))
  const navegador = await chromium.launch({
    executablePath: process.env.HU02_CHROME || undefined,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  })
  const pagina = await navegador.newPage()
  const errores = []
  pagina.on('pageerror', (error) => errores.push(error.message))
  if (process.env.HU02_BOOTSTRAP_PRUEBA) {
    const css = fs.readFileSync(process.env.HU02_BOOTSTRAP_PRUEBA, 'utf8')
    await pagina.route(
      'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css',
      (ruta) =>
        ruta.fulfill({ status: 200, contentType: 'text/css', body: css }),
    )
  }
  let casos = 0
  function comprobar(condicion, nombre) {
    assert.ok(condicion, nombre)
    console.log(`OK RECETAS WEB ${++casos}: ${nombre}`)
  }
  try {
    await pagina.goto(url + '/modules/recetas/index.php')
    comprobar(
      await pagina.getByRole('heading', { name: 'Iniciar sesión' }).isVisible(),
      'recetas exige iniciar sesión',
    )
    await pagina.locator('#correo').fill(process.env.HU02_PRUEBA_CORREO)
    await pagina.locator('#clave').fill(process.env.HU02_PRUEBA_CLAVE_USUARIO)
    await pagina.getByRole('button', { name: 'Entrar', exact: true }).click()
    await pagina.getByRole('link', { name: 'Recetas', exact: true }).click()
    comprobar(
      (await pagina.locator('.fila-ingrediente').count()) === 3,
      'formulario muestra filas de ingredientes',
    )
    await pagina.getByRole('button', { name: 'Añadir ingrediente' }).click()
    comprobar(
      (await pagina.locator('.fila-ingrediente').count()) === 4,
      'añadir ingrediente funciona',
    )
    const nombre = 'Receta navegador ' + Date.now()
    await pagina.locator('#nombre').fill(nombre)
    await pagina
      .locator('[name="ingredientes[0][materia_prima_id]"]')
      .selectOption('1')
    await pagina.locator('[name="ingredientes[0][cantidad]"]').fill('500')
    await pagina
      .locator('[name="ingredientes[1][materia_prima_id]"]')
      .selectOption('2')
    await pagina.locator('[name="ingredientes[1][cantidad]"]').fill('200')
    await pagina
      .getByRole('button', { name: 'Guardar receta', exact: true })
      .click()
    const ingredientes = await pagina.locator('tbody').innerText()
    comprobar(
      ingredientes.includes('Harina') &&
        ingredientes.includes('500.00') &&
        ingredientes.includes('Azúcar') &&
        ingredientes.includes('200.00'),
      'CP-15 completo desde el navegador',
    )
    await pagina.getByRole('link', { name: 'Volver a recetas' }).click()
    comprobar(
      await pagina.getByRole('link', { name: nombre, exact: true }).isVisible(),
      'receta aparece en el listado',
    )
    comprobar(
      errores.length === 0,
      'sin excepciones JavaScript del flujo de recetas',
    )
    console.log(`RESULTADO RECETAS WEB: ${casos} comprobaciones correctas.`)
  } finally {
    await navegador.close()
  }
}

comprobarRecetas().catch((error) => {
  console.error(error)
  process.exitCode = 1
})
