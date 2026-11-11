import { api } from '@/boot/axios'

class PanelService {
  // { eventos?, agrupaciones?, personas? }: solo vienen las claves que el usuario puede revisar
  static async contadores() {
    return (await api.get('/api/panel/contadores')).data
  }
}

export default PanelService
