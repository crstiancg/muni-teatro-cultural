import { api } from '@/boot/axios'

class ConsultaDniService {
  // { existe: true, persona } si ya está registrado; si no, nombre y apellidos de RENIEC
  static async consultar(dni) {
    return (await api.get(`/api/consulta-dni/${dni}`)).data
  }
}

export default ConsultaDniService
