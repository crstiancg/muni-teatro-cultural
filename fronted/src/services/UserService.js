import { api } from '@/boot/axios'

class UserService {
  static async getData(params) {
    return (await api.get('/api/usuarios', params)).data
  }

  static async get(id) {
    return (await api.get(`/api/usuarios/${id}`)).data
  }

  static async delete(id) {
    return await api.delete(`/api/usuarios/${id}`)
  }
}

export default UserService
