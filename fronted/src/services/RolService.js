import { api } from '@/boot/axios'

class RolService {
  static async getData(params) {
    return (await api.get('/api/roles', params)).data
  }

  static async get(id) {
    return (await api.get(`/api/roles/${id}`)).data
  }

  static async delete(id) {
    return await api.delete(`/api/roles/${id}`)
  }
}

export default RolService
