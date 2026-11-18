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

  // cierra sus sesiones en todos los dispositivos y le bloquea el login
  static async darDeBaja(id, motivo) {
    return (await api.put(`/api/usuarios/${id}/baja`, { motivo })).data
  }

  static async reactivar(id) {
    return (await api.put(`/api/usuarios/${id}/reactivar`)).data
  }
}

export default UserService
