import { api } from '@/boot/axios'

// basePath: "personas/{id}" (admin) o "mi-informacion" (el propio artista)
class PerfilPublicoService {
  static async guardar(basePath, datos) {
    return (await api.put(`/api/${basePath}/perfil-publico`, datos)).data
  }

  static async enviarRevision() {
    return (await api.post('/api/mi-informacion/enviar-revision')).data
  }

  static async aprobar(personaId) {
    return (await api.put(`/api/personas/${personaId}/aprobar`)).data
  }

  static async observar(personaId, observacion) {
    return (await api.put(`/api/personas/${personaId}/observar`, { observacion })).data
  }
}

export default PerfilPublicoService
