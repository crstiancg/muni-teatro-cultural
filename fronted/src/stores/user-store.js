import { defineStore } from 'pinia'
import { api } from '@/boot/axios'
import { Cookies } from 'quasar'

export const useUserStore = defineStore('user', {
  state: () => ({
    id: null,
    name: null,
    email: null,
    roles: null,
    permisos: null,
  }),

  getters: {
    getId: (state) => state.id,
    getName: (state) => state.name,
    getEmail: (state) => state.email,
    getRoles: (state) => state.roles,
    getPermisos: (state) => state.permisos,
    getRole: (state) => state.roles?.[0] || null,
  },

  actions: {
    async login(email, password) {
      Cookies.remove('token', { path: '/' })
      const res = await api.post('oauth/token', {
        grant_type: 'password',
        client_id: '01a0acde-012f-7122-a483-84f1e2043dc0',
        client_secret: import.meta.env.QCLI_APP_SECRET,
        username: email,
        password: password,
        scope: '',
      })
      const tokenString = 'Bearer ' + res.data.access_token
      Cookies.set('token', tokenString, { path: '/' })
      await this.getUser()
    },

    async getUser() {
      const res = await api.get('api/user')
      this.setUser(res.data)
    },

    async logout() {
      Cookies.remove('token', { path: '/' })
      this.clearUser()
    },

    setUser(payload) {
      const user = payload.user
      if (user.id) this.id = user.id
      if (user.name) this.name = user.name
      if (user.email) this.email = user.email
      if (payload.permisos) this.permisos = payload.permisos
      if (payload.roles) this.roles = payload.roles
    },

    clearUser() {
      this.id = null
      this.name = null
      this.email = null
      this.roles = null
      this.permisos = null
    },

    hasPermission(permission) {
      return this.permisos?.includes(permission)
    },
  },

  persist: {
    key: 'UserStore',
    storage: sessionStorage,
  },
})
