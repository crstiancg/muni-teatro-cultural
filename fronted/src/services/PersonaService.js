import { api } from '@/boot/axios'

class PersonaService {
  static async getData(params) {
    return (await api.get('/api/personas', params)).data
  }

  static async get(id) {
    return (await api.get(`/api/personas/${id}`)).data
  }

  static async delete(id) {
    return await api.delete(`/api/personas/${id}`)
  }
}

export default PersonaService
