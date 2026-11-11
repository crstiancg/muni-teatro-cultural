import { onBeforeUnmount } from 'vue'

// body.panel activa los tokens de css/panel.scss (el portal público comparte el
// mismo body y NO debe llevarla). La usan AdminLayout y AuthLayout.
//
// Se cuenta cuántos layouts la piden: al ir del login al panel, Vue monta el
// layout nuevo ANTES de desmontar el viejo; si cada uno la quitara al salir,
// el login le borraría la clase al panel recién montado.
let usuarios = 0

export function useClasePanel() {
  usuarios++
  document.body.classList.add('panel')

  onBeforeUnmount(() => {
    usuarios--
    if (usuarios === 0) document.body.classList.remove('panel')
  })
}
