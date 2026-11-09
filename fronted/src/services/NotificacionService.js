import { api } from '@/boot/axios'

class NotificacionService {
  static async listar() {
    return (await api.get('/api/notificaciones')).data
  }

  static async leer(id) {
    return await api.put(`/api/notificaciones/${id}/leer`)
  }

  static async leerTodas() {
    return await api.put('/api/notificaciones/leer-todas')
  }
}

export default NotificacionService
