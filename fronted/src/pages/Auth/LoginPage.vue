<template>
  <q-page class="pagina-login">
    <LoginForm @logued="onLogued" />
  </q-page>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import LoginForm from '@/pages/Auth/LoginForm.vue'

const route = useRoute()
const router = useRouter()

// si llegó redirigido desde una pantalla protegida, vuelve a ella
const onLogued = () => {
  const destino = route.query.redirectTo
  router.push(
    // solo rutas internas: "//otro-sitio.com" también empieza con "/"
    typeof destino === 'string' && destino.startsWith('/') && !destino.startsWith('//')
      ? destino
      : { name: 'Dashboard' },
  )
}
</script>

<style>
.pagina-login {
  min-height: 100vh !important;
  padding: 0 !important;
  display: flex;
}
</style>
