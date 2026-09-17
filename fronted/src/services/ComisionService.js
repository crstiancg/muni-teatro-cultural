import { api } from '@/boot/axios'

class ComisionService {
  static async getData(params) {
    return (await api.get('/api/comisiones', params)).data
  }

  static async get(codigo) {
    return (await api.get(`/api/comisiones/${codigo}`)).data
  }

  static async storeGrupo(nombre) {
    return (await api.post('/api/grupos', { comision: { nombre } })).data
  }

  static async storeFamilia(nombre, codGrupo) {
    return (await api.post('/api/familias', { comision: { nombre, cod_grupo: codGrupo } })).data
  }

  static async update(id, nombre) {
    return (await api.put(`/api/comisiones/${id}`, { comision: { nombre } })).data
  }

  static async delete(id) {
    return await api.delete(`/api/comisiones/${id}`)
  }
}

export default ComisionService
