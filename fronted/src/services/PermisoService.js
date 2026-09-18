import { api } from '@/boot/axios'

class PermisoService {
  static async getData(params) {
    return (await api.get('/api/permisos', params)).data
  }

  static async get(id) {
    return (await api.get(`/api/permisos/${id}`)).data
  }

  static async delete(id) {
    return await api.delete(`/api/permisos/${id}`)
  }
}

export default PermisoService
