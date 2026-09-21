import { defineBoot } from '#q-app'
import { revelar } from '@/composables/useRevelar'

// registra v-revelar para todo el sitio público
export default defineBoot(({ app }) => {
  app.directive('revelar', revelar)
})
