import { defineBoot } from '#q-app'
import { Cookies } from 'quasar'

export default defineBoot(({ router }) => {
  router.beforeEach(authGuard)
})

const authGuard = (to) => {
  if (to.matched.some((record) => record.meta.requiresAuth)) {
    if (Cookies.get('token') === null && to.path !== '/login') {
      return { path: '/login', query: { redirectTo: to.fullPath } }
    }
  }
}
