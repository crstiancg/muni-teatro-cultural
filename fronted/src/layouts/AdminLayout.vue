<template>
  <q-layout view="lHh LpR fFf">
    <q-header :style="headerStyle">
      <q-toolbar>
        <q-btn flat dense round icon="menu" @click="drawer = !drawer" />
        <q-toolbar-title />
        <AppClock class="q-mr-md" />
        <SwitchDarkMode />
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="drawer"
      show-if-above
      side="left"
      :width="280"
      :breakpoint="1023"
      bordered
      :style="drawerStyle"
    >
      <div class="absolute-top q-pa-none" style="height: 165px">
        <q-item
          :to="{ name: 'Dashboard' }"
          clickable
          v-ripple
          class="text-white q-mx-sm q-mt-sm"
          style="border-radius: 10px"
        >
          <q-item-section avatar>
            <q-avatar rounded size="48px" color="secondary" text-color="white">
              <q-icon name="theater_comedy" size="28px" />
            </q-avatar>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white text-weight-bold text-h6"> TEATRO CULTURAL </q-item-label>
          </q-item-section>
        </q-item>

        <q-item
          :to="{ name: 'Perfil' }"
          clickable
          v-ripple
          class="text-white q-ma-sm"
          style="border-radius: 10px; border: 1px solid #ffffff50"
        >
          <q-item-section>
            <q-item-label lines="2" class="text-weight-bold">
              {{ userStore.getName || 'Usuario' }}
            </q-item-label>
            <q-item-label caption class="text-white text-weight-bold">
              {{ userStore.getEmail || 'Correo' }} {{ userStore.getRole || 'Administrador' }}
            </q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-avatar color="secondary" text-color="white">
              {{ initialMayus }}
            </q-avatar>
          </q-item-section>
        </q-item>
        <q-separator spaced dark />
      </div>

      <q-scroll-area style="height: calc(100% - 165px - 80px); margin-top: 165px" class="q-mx-sm">
        <q-list padding>
          <template v-for="(entry, idx) in menu" :key="idx">
            <MenuItem
              v-if="entry.type === 'item'"
              :label="entry.label"
              :route-name="entry.routeName"
              :icon="entry.icon"
              :active-name="currentRouteName"
            />
            <MenuGroup
              v-else
              :label="entry.label"
              :caption="entry.caption"
              :icon="entry.icon"
              :children="entry.children"
              :active-name="currentRouteName"
              :default-open="entry.defaultOpen"
            />
          </template>
        </q-list>
      </q-scroll-area>

      <div class="absolute-bottom q-pa-xs">
        <q-separator spaced dark />
        <q-item
          clickable
          v-ripple
          class="bg-red-10 text-white q-ma-sm"
          style="border-radius: 10px"
          @click="logout"
        >
          <q-item-section avatar>
            <LogOut :size="20" color="white" />
          </q-item-section>
          <q-item-section class="text-weight-bold">CERRAR SESION</q-item-section>
        </q-item>
      </div>
    </q-drawer>

    <q-page-container>
      <router-view v-slot="{ Component }">
        <component :is="Component" :key="route.fullPath" />
      </router-view>
    </q-page-container>

    <q-dialog v-model="mostrarMiInformacion">
      <MiInformacionDialog :data-inicial="miInformacion" />
    </q-dialog>
  </q-layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user-store'
import { useMenu } from '@/composables/useMenu'
import { LogOut } from 'lucide-vue-next'
import MenuItem from '@/components/sidebar/MenuItem.vue'
import MenuGroup from '@/components/sidebar/MenuGroup.vue'
import AppClock from '@/components/AppClock.vue'
import SwitchDarkMode from '@/components/SwitchDarkMode.vue'
import MiInformacionDialog from '@/components/MiInformacionDialog.vue'
import MiInformacionService from '@/services/MiInformacionService'

const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const userStore = useUserStore()
const { menu } = useMenu()

const mostrarMiInformacion = ref(false)
const miInformacion = ref(null)

const drawer = ref(false)
const currentRouteName = computed(() => route.name)
const initialMayus = computed(() => userStore.getName?.charAt(0).toUpperCase() || '?')

const headerStyle = computed(() => ({
  backgroundColor: $q.dark.isActive ? '#1d1d1d' : '#fafafa',
  color: $q.dark.isActive ? 'white' : 'black',
}))

const drawerStyle = computed(() => ({
  backgroundColor: $q.dark.isActive ? '#1d1d1d' : 'var(--q-primary)',
  color: 'white',
}))

async function logout() {
  await userStore.logout()
  router.push({ name: 'Login' })
}

onMounted(async () => {
  // se muestra siempre al loguearse (montar el layout admin) si el usuario
  // tiene una ficha de persona vinculada; si es un admin sin persona, no sale
  const datos = await MiInformacionService.get()
  if (datos?.persona) {
    miInformacion.value = datos
    mostrarMiInformacion.value = true
  }
})
</script>

<style lang="scss">
.my-menu-link {
  color: white;
  background: var(--q-secondary) !important;
  border-radius: 10px !important;
}

.q-scrollarea__thumb--v {
  width: 6px;
}

.q-drawer {
  .q-expansion-item {
    border-radius: 10px;

    .q-item {
      border-radius: 10px;
    }
  }

  .q-item--clickable:hover {
    border-radius: 10px;
  }
}
</style>
