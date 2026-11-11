<template>
  <q-layout view="lHh LpR fFf">
    <q-header class="panel-cabecera">
      <div class="panel-cabecera-fila">
        <q-btn
          flat
          round
          dense
          class="panel-cabecera-boton"
          :aria-label="drawer ? 'Ocultar menú' : 'Mostrar menú'"
          @click="drawer = !drawer"
        >
          <PanelLeft :size="20" />
        </q-btn>
        <q-space />
        <NotificacionesBell />
        <MenuCuenta
          :nombre="userStore.getName || 'Usuario'"
          :correo="userStore.getEmail || ''"
          :rol="userStore.getRole || ''"
          :oscuro="$q.dark.isActive"
          @cambiar-tema="cambiarTema"
          @salir="logout"
        />
      </div>
    </q-header>

    <q-drawer
      v-model="drawer"
      show-if-above
      side="left"
      :width="248"
      :breakpoint="1023"
      class="panel-cajon"
    >
      <PanelLateral :secciones="secciones" :contadores="contadores" />
    </q-drawer>

    <q-page-container>
      <router-view v-slot="{ Component }">
        <component :is="Component" :key="route.fullPath" />
      </router-view>
    </q-page-container>

    <!-- persistent + sin Cerrar: con datos incompletos solo se sale guardando -->
    <q-dialog v-model="mostrarMiInformacion" persistent>
      <MiInformacionDialog
        :data-inicial="miInformacion"
        obligatorio
        @save="mostrarMiInformacion = false"
      />
    </q-dialog>
  </q-layout>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import { PanelLeft } from 'lucide-vue-next'
import { useUserStore } from '@/stores/user-store'
import { useMenu } from '@/composables/useMenu'
import { useClasePanel } from '@/composables/useClasePanel'
import PanelLateral from '@/components/panel/PanelLateral.vue'
import MenuCuenta from '@/components/panel/MenuCuenta.vue'
import NotificacionesBell from '@/components/NotificacionesBell.vue'
import MiInformacionDialog from '@/components/MiInformacionDialog.vue'
import MiInformacionService from '@/services/MiInformacionService'
import PanelService from '@/services/PanelService'

const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const userStore = useUserStore()
const { secciones } = useMenu()

const drawer = ref(false)
const mostrarMiInformacion = ref(false)
const miInformacion = ref(null)

// ---------- identidad del panel ----------
// body.panel activa los tokens de css/panel.scss (ver useClasePanel)
useClasePanel()

// el tema se recuerda en este navegador (si el almacenamiento falla, queda claro)
const CLAVE_TEMA = 'panel-tema'
try {
  $q.dark.set(localStorage.getItem(CLAVE_TEMA) === 'oscuro')
} catch {
  // sin preferencia guardada
}

function cambiarTema() {
  const oscuro = !$q.dark.isActive
  $q.dark.set(oscuro)
  try {
    localStorage.setItem(CLAVE_TEMA, oscuro ? 'oscuro' : 'claro')
  } catch {
    // no se recuerda, pero el cambio se aplica igual
  }
}

// al salir del panel (al login o al portal) el sitio vuelve a su aspecto propio
onBeforeUnmount(() => {
  $q.dark.set(false)
})

// ---------- contadores del menú ----------
const contadores = ref({})

async function cargarContadores() {
  try {
    contadores.value = await PanelService.contadores()
  } catch {
    // sin contadores el menú se usa igual
  }
}

// al navegar (p. ej. después de aprobar algo) se actualizan
watch(() => route.name, cargarContadores)

async function logout() {
  await userStore.logout()
  // al cerrar sesión se vuelve a la portada pública
  router.push({ name: 'Inicio' })
}

onMounted(async () => {
  // los permisos se guardan al loguearse; se refrescan en cada carga del panel
  // para que un permiso dado o quitado se aplique sin cerrar sesión
  userStore.getUser().catch(() => {})
  cargarContadores()

  // sale al montar el panel solo si la persona tiene datos personales sin
  // completar (el admin la pudo registrar con lo mínimo, p. ej. sin comisión).
  // Un admin sin ficha de persona nunca lo ve. Si falla la consulta, el panel igual se usa.
  try {
    const datos = await MiInformacionService.get()
    if (datos?.persona && datos.datos_completos === false) {
      miInformacion.value = datos
      mostrarMiInformacion.value = true
    }
  } catch {
    // sin recordatorio
  }
})
</script>

<style>
/* sin scope: q-header y q-drawer renderizan sus propios contenedores */
body.panel .panel-cabecera {
  background: var(--panel-fondo);
  color: var(--panel-texto);
  border-bottom: 1px solid var(--panel-borde);
}

body.panel .panel-cabecera-fila {
  display: flex;
  align-items: center;
  gap: 4px;
  height: 56px;
  padding: 0 clamp(8px, 2vw, 20px);
}

body.panel .panel-cabecera-boton {
  color: var(--panel-texto-suave);
}

body.panel .panel-cajon {
  background: var(--panel-lateral);
}
</style>
