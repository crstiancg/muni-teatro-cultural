import { api } from '@/boot/axios'

class CapacitacionService {
  static async delete(basePath, id) {
    return await api.delete(`/api/${basePath}/${id}`)
  }

  static async reactivar(basePath, id) {
    return (await api.put(`/api/${basePath}/${id}/reactivar`)).data
  }
}

export default CapacitacionService
