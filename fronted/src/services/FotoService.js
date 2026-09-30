import { api } from '@/boot/axios'

// basePath: "personas/{id}" (admin) o "mi-informacion" (el propio usuario)
class FotoService {
  static async subir(basePath, archivo) {
    const datos = new FormData()
    datos.append('foto', archivo)
    return (await api.post(`/api/${basePath}/foto`, datos)).data
  }

  static async eliminar(basePath) {
    return await api.delete(`/api/${basePath}/foto`)
  }
}

export default FotoService
