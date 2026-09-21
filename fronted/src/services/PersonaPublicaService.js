import { api } from '@/boot/axios'

class PersonaPublicaService {
  // devuelve { data, meta } — el directorio se pagina porque el registro
  // puede tener cientos de artistas
  static async getData(params) {
    return (await api.get('/api/publico/consejeros', { params })).data
  }

  static async get(id) {
    return (await api.get(`/api/publico/consejeros/${id}`)).data
  }

  static async getGrupos() {
    return (await api.get('/api/publico/consejeros/grupos')).data
  }

  static async getDestacadas() {
    return (await api.get('/api/publico/consejeros/destacadas')).data
  }

  static async getPortada() {
    return (await api.get('/api/publico/portada')).data
  }
}

export default PersonaPublicaService
