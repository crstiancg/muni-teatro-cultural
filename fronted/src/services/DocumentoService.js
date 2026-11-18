import { api } from '@/boot/axios'

// Resoluciones y ordenanzas del Consejo. El PDF lo sirve el backend desde el
// dominio del sitio (DocumentoPublicoController): leerlo directo del portal de
// la municipalidad lo bloquea el navegador (CORS).
class DocumentoService {
  static async listar() {
    const res = await api.get('/api/publico/documentos')
    return res.data
  }

  // URL absoluta: el visor (pdf.js) la pide por su cuenta, no pasa por axios
  static url(slug, { descargar = false } = {}) {
    const base = String(api.defaults.baseURL || '/').replace(/\/$/, '')
    return `${base}/api/publico/documentos/${encodeURIComponent(slug)}${descargar ? '?descargar=1' : ''}`
  }
}

export default DocumentoService
