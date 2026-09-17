import { defineBoot } from '#q-app'
import axios from 'axios'
import { Cookies, Notify } from 'quasar'
import { useUserStore } from '@/stores/user-store'

const api = axios.create({ baseURL: import.meta.env.QCLI_API_BACKEND_URL })

api.interceptors.request.use(
  (config) => {
    const token = Cookies.get('token')
    if (token) {
      config.headers['Authorization'] = token
    }
    return config
  },
  (error) => Promise.reject(error),
)

export default defineBoot(({ app, router }) => {
  api.interceptors.response.use(
    (response) => response,
    (error) => {
      const userStore = useUserStore()
      if (error.response && (error.response.status === 401 || error.response.status === 419)) {
        Notify.create({
          type: 'negative',
          message: 'Tu sesión ha expirado. Por favor vuelve a iniciar sesión.',
          position: 'top',
          progress: true,
          timeout: 4000,
        })
        userStore.logout()
        router.push({ name: 'Login' })
      }

      if (error.response && error.response.status === 403) {
        Notify.create({
          type: 'warning',
          message: 'No tienes permisos para realizar esta acción.',
          position: 'top',
          progress: true,
          timeout: 5000,
        })
      }
      return Promise.reject(error)
    },
  )

  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
})

export { api }
