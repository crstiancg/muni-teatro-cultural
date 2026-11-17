import { api } from '@/boot/axios'

// Ficha de Registro de Agente Cultural (PDF que arma el backend) y su
// verificación pública por el token del QR.
class FichaRegistroService {
  // la propia (artista) o la de una persona (admin, con permiso de ver personas).
  // blob: la ruta lleva el token de sesión en el header, un <a href> no lo mandaría
  static async obtener(personaId = null) {
    const url = personaId ? `/api/personas/${personaId}/ficha-registro` : '/api/mi-informacion/ficha-registro'
    const res = await api.get(url, { responseType: 'blob' })
    return res.data
  }

  static guardar(blob, nombre) {
    const url = URL.createObjectURL(blob)
    const enlace = document.createElement('a')
    enlace.href = url
    enlace.download = nombre
    document.body.appendChild(enlace)
    enlace.click()
    enlace.remove()
    // diferido: algunos navegadores todavía están leyendo el blob al volver de click()
    setTimeout(() => URL.revokeObjectURL(url), 1000)
  }

  static async verificar(token) {
    const res = await api.get(`/api/publico/verificar/${encodeURIComponent(token)}`)
    return res.data
  }
}

export default FichaRegistroService
