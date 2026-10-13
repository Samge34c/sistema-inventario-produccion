/* Prueba sobre un despliegue local aislado con schema.sql nuevo y sin datos. */
const { chromium } = require('playwright')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')

async function verificarFlujo() {
  const url = process.env.HU02_URL || 'http://127.0.0.1:8000'
  assert.ok(
    ['127.0.0.1', 'localhost'].includes(new URL(url).hostname),
    'Usar un despliegue local de prueba',
  )
  const navegador = await chromium.launch({
    executablePath: process.env.HU02_CHROME || undefined,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  })
  const pagina = await navegador.newPage({
    viewport: { width: 1280, height: 960 },
  })
  const bootstrapPrueba = process.env.HU02_BOOTSTRAP_PRUEBA
  if (bootstrapPrueba) {
    const cssBootstrap = fs.readFileSync(bootstrapPrueba, 'utf8')
    assert.ok(
      /Bootstrap\s+v5\.3\.8/.test(cssBootstrap),
      'Usar Bootstrap 5.3.8 real',
    )
    await pagina.route(
      'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css',
      (ruta) =>
        ruta.fulfill({
          status: 200,
          contentType: 'text/css',
          body: cssBootstrap,
        }),
    )
    console.log(
      'ENTORNO: Bootstrap 5.3.8 servido como recurso local de prueba; disponibilidad del CDN no evaluada.',
    )
  }
  const erroresJavaScript = []
  const erroresConsola = []
  pagina.on('pageerror', (error) => erroresJavaScript.push(error.message))
  pagina.on('console', (mensaje) => {
    if (mensaje.type() === 'error') erroresConsola.push(mensaje.text())
  })
  let numeroPruebas = 0
  function comprobar(esCorrecto, caso) {
    assert.ok(esCorrecto, caso)
    numeroPruebas++
    console.log(`OK WEB ${numeroPruebas}: ${caso}`)
  }
  try {
    await pagina.goto(url + '/modules/inventario/index.php', {
      waitUntil: 'domcontentloaded',
    })
    await pagina.locator('#nombre').fill('Harina HU02 prueba web')
    await pagina.locator('#unidad_medida').selectOption('g')
    await pagina.locator('#cantidad_disponible').fill('10000')
    await pagina.locator('#stock_minimo').fill('1000')
    await pagina.getByRole('button', { name: 'Guardar materia prima' }).click()
    comprobar(
      (await pagina.locator('tbody').innerText()).includes('10000.00'),
      'HU01 registra y consulta 10000 g',
    )
    const enlaceEditar = await pagina
      .locator('tr', { hasText: 'Harina HU02 prueba web' })
      .getByRole('link', { name: 'Editar' })
      .getAttribute('href')
    await pagina.getByRole('link', { name: 'Movimientos', exact: true }).click()
    const opcion = pagina.locator('#materia_prima_id option', {
      hasText: 'Harina HU02 prueba web',
    })
    const id = await opcion.getAttribute('value')
    async function registrar(tipo, cantidad) {
      await pagina.locator('#materia_prima_id').selectOption(id)
      await pagina.locator('#tipo').selectOption(tipo)
      await pagina.locator('#cantidad').fill(cantidad)
      await pagina.locator('#observacion').fill('Caso reproducible')
      await pagina.getByRole('button', { name: 'Guardar movimiento' }).click()
    }
    await registrar('ENTRADA', '2000')
    comprobar(
      (await pagina.locator('#materia_prima_id').innerText()).includes(
        '12000.00',
      ),
      'entrada web aumenta a 12000',
    )
    await registrar('SALIDA', '1000')
    comprobar(
      (await pagina.locator('#materia_prima_id').innerText()).includes(
        '11000.00',
      ),
      'salida web disminuye a 11000',
    )
    await registrar('PENDIENTE', '5000')
    comprobar(
      (await pagina.locator('#materia_prima_id').innerText()).includes(
        '11000.00',
      ),
      'pendiente web conserva 11000 disponibles',
    )
    comprobar(
      (await pagina.locator('section').last().innerText()).includes(
        '5000.00 g',
      ),
      'pendiente visible por separado',
    )
    await registrar('SALIDA', '11000.01')
    comprobar(
      (await pagina.getByRole('alert').innerText()).includes(
        'supera la existencia',
      ),
      'salida excesiva informa error controlado',
    )
    comprobar(
      (await pagina.locator('#materia_prima_id').innerText()).includes(
        '11000.00',
      ),
      'salida rechazada no modifica existencia',
    )
    let respuesta = await pagina.request.post(
      url + '/modules/inventario/guardar-movimiento.php',
      {
        form: {
          materia_prima_id: id,
          tipo: 'SALIDA',
          cantidad: '1',
          observacion: '',
          token: 'invalido',
        },
      },
    )
    comprobar(respuesta.status() === 403, 'POST sin token válido rechazado')
    respuesta = await pagina.request.get(
      url + '/modules/inventario/guardar-movimiento.php',
    )
    comprobar(respuesta.status() === 405, 'GET al registro rechazado')
    const token = await pagina
      .locator('input[name=token]')
      .getAttribute('value')
    respuesta = await pagina.request.post(
      url + '/modules/inventario/guardar-movimiento.php',
      {
        form: {
          materia_prima_id: id,
          tipo: 'ENTRADA',
          cantidad: '1.001',
          observacion: '',
          token,
        },
        maxRedirects: 0,
      },
    )
    comprobar(
      respuesta.status() === 303,
      'backend controla más de dos decimales',
    )
    await pagina.goto(url + '/modules/inventario/movimientos.php', {
      waitUntil: 'domcontentloaded',
    })
    comprobar(
      (await pagina.getByRole('alert').innerText()).includes('dos decimales'),
      'validación del backend visible tras redirect',
    )
    const observacion = "<script>window.hu02Ataque=true</script>' OR 1=1 --"
    respuesta = await pagina.request.post(
      url + '/modules/inventario/guardar-movimiento.php',
      {
        form: {
          materia_prima_id: id,
          tipo: 'PENDIENTE',
          cantidad: '1',
          observacion,
          token,
        },
      },
    )
    comprobar(
      respuesta.status() === 200,
      'texto con comillas se almacena mediante consulta preparada',
    )
    await pagina.goto(url + '/modules/inventario/movimientos.php', {
      waitUntil: 'domcontentloaded',
    })
    comprobar(
      await pagina.evaluate(() => window.hu02Ataque === undefined),
      'observación escapada no ejecuta script',
    )
    await pagina.goto(url + '/modules/inventario/' + enlaceEditar, {
      waitUntil: 'domcontentloaded',
    })
    await pagina.locator('#cantidad_disponible').fill('9000')
    await pagina.getByRole('button', { name: 'Guardar cambios' }).click()
    comprobar(
      (await pagina.getByRole('alert').innerText()).includes(
        'entrada o salida',
      ),
      'edición directa del saldo bloqueada',
    )
    await pagina.goto(url + '/modules/inventario/index.php', {
      waitUntil: 'domcontentloaded',
    })
    await pagina
      .locator('tr', { hasText: 'Harina HU02 prueba web' })
      .getByRole('button', { name: 'Eliminar', exact: true })
      .click()
    comprobar(
      (await pagina.getByRole('alert').innerText()).includes('historial'),
      'borrado de materia referenciada informa error controlado',
    )
    comprobar(
      (await pagina.locator('tbody').innerText()).includes('11000.00'),
      'materia e inventario se conservan',
    )
    await pagina.getByRole('link', { name: 'Movimientos', exact: true }).click()
    comprobar(
      erroresJavaScript.length === 0,
      'sin excepciones JavaScript del flujo',
    )
    if (erroresConsola.length) console.error('ERRORES CONSOLA:', erroresConsola)
    comprobar(
      erroresConsola.length === 0,
      'sin errores en consola del navegador',
    )
    const destinoCaptura = process.env.HU02_CAPTURA
    if (destinoCaptura) {
      fs.mkdirSync(path.dirname(destinoCaptura), { recursive: true })
      await pagina.screenshot({ path: destinoCaptura, fullPage: true })
    }
    console.log(`RESULTADO WEB: ${numeroPruebas} comprobaciones correctas.`)
  } finally {
    await navegador.close()
  }
}

verificarFlujo().catch((error) => {
  console.error(error)
  process.exit(1)
})
