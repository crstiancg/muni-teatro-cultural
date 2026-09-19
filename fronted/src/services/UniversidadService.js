import { api } from '@/boot/axios'

class UniversidadService {
  static async getData(params) {
    return (await api.get('/api/universidades', params)).data
  }

  static async get(id) {
    return (await api.get(`/api/universidades/${id}`)).data
  }

  static async delete(id) {
    return await api.delete(`/api/universidades/${id}`)
  }
}

export default UniversidadService