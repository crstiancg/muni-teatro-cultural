import { computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useUserStore } from '@/stores/user-store'

// Tema del panel elegido por cada usuario (opciones curadas: config/panel.php en
// el backend y css/panel.scss). Se guarda en su cuenta; acá se aplica al <body>.

export const TEMA_POR_DEFECTO = { acento: 'rojo', neutro: 'calido', modo: 'claro' }

// muestra = el tono 700 que usa el tema claro (solo para pintar el selector)
export const ACENTOS = [
  { valor: 'rojo', label: 'Rojo', muestra: '#b91c1c' },
  { valor: 'rosa', label: 'Rosa', muestra: '#be123c' },
  { valor: 'violeta', label: 'Violeta', muestra: '#6d28d9' },
  { valor: 'indigo', label: 'Índigo', muestra: '#4338ca' },
  { valor: 'azul', label: 'Azul', muestra: '#1d4ed8' },
]

export const NEUTROS = [
  { valor: 'calido', label: 'Cálido', muestra: '#e7e5e4' },
  { valor: 'frio', label: 'Frío', muestra: '#e4e4e7' },
]

// copia local: al recargar, el tema se pinta antes de que responda /api/user
const CLAVE_CACHE = 'panel-tema'

function leerCache() {
  try {
    const guardado = JSON.parse(localStorage.getItem(CLAVE_CACHE))
    return guardado && typeof guardado === 'object' ? guardado : null
  } catch {
    return null // vacío, bloqueado o el formato viejo ("oscuro"/"claro")
  }
}

export function useTemaPanel() {
  const $q = useQuasar()
  const store = useUserStore()

  const tema = computed(() => ({
    ...TEMA_POR_DEFECTO,
    ...(store.preferencias ?? leerCache() ?? {}),
  }))

  function aplicar(t) {
    document.body.dataset.acento = t.acento
    document.body.dataset.neutro = t.neutro
    $q.dark.set(t.modo === 'oscuro')
    try {
      localStorage.setItem(CLAVE_CACHE, JSON.stringify(t))
    } catch {
      // sin caché: igual se aplica, solo que al recargar espera a /api/user
    }
  }

  watch(tema, aplicar, { immediate: true })

  // cambio parcial: { modo: 'oscuro' } no toca el acento ni el neutro
  async function cambiar(cambios) {
    try {
      await store.guardarPreferencias(cambios)
    } catch {
      $q.notify({ type: 'negative', message: 'No se pudo guardar el tema. Intenta de nuevo.' })
    }
  }

  // al salir del panel: el sitio vuelve a su aspecto propio
  function limpiar() {
    delete document.body.dataset.acento
    delete document.body.dataset.neutro
    $q.dark.set(false)
  }

  return { tema, cambiar, limpiar }
}
