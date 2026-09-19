import { api } from '@/boot/axios'

class CarreraService {
  static async getData(params) {
    return (await api.get('/api/carreras', params)).data
  }

  static async get(id) {
    return (await api.get(`/api/carreras/${id}`)).data
  }

  static async delete(id) {
    return await api.delete(`/api/carreras/${id}`)
  }
}

export default CarreraService