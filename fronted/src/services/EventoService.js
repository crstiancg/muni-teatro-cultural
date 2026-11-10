import { api } from '@/boot/axios'

class EventoService {
  // panel
  static async getData(params) {
    return (await api.get('/api/eventos', params)).data
  }

  static async get(id) {
    return (await api.get(`/api/eventos/${id}`)).data
  }

  static async cancelar(id) {
    return (await api.put(`/api/eventos/${id}/cancelar`)).data
  }

  static async delete(id) {
    return await api.delete(`/api/eventos/${id}`)
  }

  // portal: siempre por rango { desde, hasta } (YYYY-MM-DD), opcional { grupo }
  static async publicos(params) {
    return (await api.get('/api/publico/eventos', { params })).data
  }

  // portada: los más cercanos desde hoy (incluye los que están en curso)
  static async proximos(limite = 4) {
    return (await api.get('/api/publico/eventos/proximos', { params: { limite } })).data
  }

  // página del evento: { data, otros } (otros = próximos, misma disciplina primero)
  static async publico(id) {
    return (await api.get(`/api/publico/eventos/${id}`)).data
  }
}

export default EventoService
