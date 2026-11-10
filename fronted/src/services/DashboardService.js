import { api } from '@/boot/axios'

class DashboardService {
  static async admin() {
    return (await api.get('/api/dashboard/admin')).data
  }
}

export default DashboardService
