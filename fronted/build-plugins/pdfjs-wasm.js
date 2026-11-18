// Plugin de Vite: publica los decodificadores WebAssembly de pdf.js en /pdfjs/wasm/.
//
// pdf.js 6 decodifica con WASM las imágenes de los PDF escaneados (CCITT/JBIG2,
// el formato de las fotocopiadoras) y JPEG 2000. Sin el parámetro `wasmUrl` esas
// páginas salen EN BLANCO, sin error visible: pasó con las resoluciones del
// Consejo y con los certificados escaneados del CV.
//
// Se toman de node_modules/pdfjs-dist/wasm: siempre en la misma versión que pdf.js.
// En dev se sirven desde ahí; en el build se copian a dist/spa/pdfjs/wasm/.
import { readFileSync, readdirSync } from 'node:fs'
import { fileURLToPath } from 'node:url'

const ORIGEN = fileURLToPath(new URL('../node_modules/pdfjs-dist/wasm/', import.meta.url))
const TIPOS = { wasm: 'application/wasm', js: 'text/javascript' }

export function pdfjsWasm(ruta = 'pdfjs/wasm') {
  const archivos = () => readdirSync(ORIGEN)

  return {
    name: 'pdfjs-wasm',

    configureServer(server) {
      server.middlewares.use(`/${ruta}`, (req, res, next) => {
        const nombre = decodeURIComponent((req.url || '').split('?')[0].replace(/^\//, ''))
        // solo archivos de esa carpeta: nada de rutas con ../
        if (!archivos().includes(nombre)) return next()
        res.setHeader('Content-Type', TIPOS[nombre.split('.').pop()] || 'text/plain; charset=utf-8')
        res.end(readFileSync(ORIGEN + nombre))
      })
    },

    generateBundle() {
      for (const nombre of archivos()) {
        this.emitFile({ type: 'asset', fileName: `${ruta}/${nombre}`, source: readFileSync(ORIGEN + nombre) })
      }
    },
  }
}
