import { api } from '@/boot/axios'

class ActividadService {
  static async delete(basePath, id) {
    return await api.delete(`/api/${basePath}/${id}`)
  }

  static async reactivar(basePath, id) {
    return (await api.put(`/api/${basePath}/${id}/reactivar`)).data
  }

  static async eliminarPermanente(basePath, id) {
    return await api.delete(`/api/${basePath}/${id}/permanente`)
  }
}

export default ActividadService
