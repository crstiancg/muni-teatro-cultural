import { defineRouter } from '#q-app'
import {
  createRouter,
  createMemoryHistory,
  createWebHistory,
  createWebHashHistory,
} from 'vue-router'
import routes from './routes'

export default defineRouter((/* { store, ssrContext } */) => {
  const createHistory = import.meta.env.QUASAR_SERVER
    ? createMemoryHistory
    : import.meta.env.QUASAR_VUE_ROUTER_MODE === 'history'
      ? createWebHistory
      : createWebHashHistory

  const Router = createRouter({
    // las anclas del sitio público (#comisiones) tienen que funcionar también
    // al llegar desde otra página; el offset es la altura del header sticky
    scrollBehavior: (to, from, savedPosition) => {
      if (to.hash) return { el: to.hash, top: 80, behavior: 'smooth' }
      if (savedPosition) return savedPosition
      return { left: 0, top: 0 }
    },
    routes,
    history: createHistory(import.meta.env.QUASAR_VUE_ROUTER_BASE),
  })

  return Router
})
