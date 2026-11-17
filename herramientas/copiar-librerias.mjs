// Copia Bootstrap y Bootstrap Icons desde node_modules a assets/lib, para que
// el sistema funcione sin internet y sin necesidad de ejecutar npm en XAMPP.
// Uso: npm run copiar-librerias (después de npm install).

import { cpSync, mkdirSync, rmSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const raiz = join(dirname(fileURLToPath(import.meta.url)), '..')
const destino = join(raiz, 'assets', 'lib')

const archivos = [
  ['bootstrap/dist/css/bootstrap.min.css', 'bootstrap/bootstrap.min.css'],
  [
    'bootstrap/dist/css/bootstrap.min.css.map',
    'bootstrap/bootstrap.min.css.map',
  ],
  [
    'bootstrap/dist/js/bootstrap.bundle.min.js',
    'bootstrap/bootstrap.bundle.min.js',
  ],
  [
    'bootstrap/dist/js/bootstrap.bundle.min.js.map',
    'bootstrap/bootstrap.bundle.min.js.map',
  ],
  [
    'bootstrap-icons/font/bootstrap-icons.min.css',
    'bootstrap-icons/bootstrap-icons.min.css',
  ],
  ['bootstrap-icons/font/fonts', 'bootstrap-icons/fonts'],
]

rmSync(destino, { recursive: true, force: true })

for (const [origen, llegada] of archivos) {
  const rutaLlegada = join(destino, llegada)
  mkdirSync(dirname(rutaLlegada), { recursive: true })
  cpSync(join(raiz, 'node_modules', origen), rutaLlegada, { recursive: true })
}

console.log('Librerías copiadas en assets/lib')
