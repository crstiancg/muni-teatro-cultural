import { api } from '@/boot/axios'

class ProfesionService {
  static async getData(params) {
    return (await api.get('/api/profesiones', params)).data
  }

  static async get(id) {
    return (await api.get(`/api/profesiones/${id}`)).data
  }

  static async delete(id) {
    return await api.delete(`/api/profesiones/${id}`)
  }
}

export default ProfesionService