import { api } from '@/boot/axios'

class UbigeoService {
  static async getData(params) {
    return (await api.get('/api/ubigeos', params)).data
  }

  static async get(codigo) {
    return (await api.get(`/api/ubigeos/${codigo}`)).data
  }
}

export default UbigeoService
