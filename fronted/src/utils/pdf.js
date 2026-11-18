// Fuente para <VuePdfEmbed :source>. pdf.js 6 decodifica los escaneos (CCITT/
// JBIG2) y JPEG 2000 con WebAssembly: sin `wasmUrl` esas páginas salen EN BLANCO
// y sin error visible. Los .wasm los publica build-plugins/pdfjs-wasm.js.
//
// Usarla siempre dentro de un computed: un objeto nuevo en cada render hace que
// vue-pdf-embed vuelva a cargar el PDF una y otra vez.
const WASM_URL = new URL(`${import.meta.env.BASE_URL || '/'}pdfjs/wasm/`, window.location.origin).href

export function fuentePdf(url) {
  return url ? { url, wasmUrl: WASM_URL } : null
}
