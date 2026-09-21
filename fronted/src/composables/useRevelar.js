/**
 * Directiva `v-revelar`: el elemento aparece con un fundido corto y un
 * desplazamiento mínimo cuando entra en pantalla. Nada de rebotes ni de
 * recorridos largos — la animación acompaña la lectura, no la interrumpe.
 *
 *   <section v-revelar>            → aparece al entrar en pantalla
 *   <div v-revelar="80">           → con 80ms de retraso (para escalonar)
 *
 * Si el sistema pide menos animación, el contenido se muestra directamente.
 */

const PREFIERE_QUIETO = () =>
  typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches

let observador

function obtenerObservador() {
  if (observador) return observador

  observador = new IntersectionObserver(
    (entradas) => {
      entradas.forEach((entrada) => {
        if (!entrada.isIntersecting) return
        entrada.target.classList.add('revelado')
        // una sola vez: si el visitante vuelve a subir, el contenido ya está
        observador.unobserve(entrada.target)
      })
    },
    // se dispara un poco antes de que el bloque termine de entrar
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
  )

  return observador
}

export const revelar = {
  mounted(el, binding) {
    if (PREFIERE_QUIETO()) {
      el.classList.add('revelado')
      return
    }

    el.classList.add('por-revelar')

    const retraso = Number(binding.value) || 0
    if (retraso) el.style.transitionDelay = `${retraso}ms`

    obtenerObservador().observe(el)
  },

  unmounted(el) {
    observador?.unobserve(el)
  },
}
