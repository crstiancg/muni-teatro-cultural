import { api } from '@/boot/axios'

class MiInformacionService {
  static async get() {
    const res = await api.get('/api/mi-informacion')
    return res.data
  }
}

export default MiInformacionService
