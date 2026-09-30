<template>
  <div class="q-pa-md q-gutter-sm">
    <q-breadcrumbs>
      <q-breadcrumbs-el icon="home" />
      <q-breadcrumbs-el label="DASHBOARD" icon="dashboard" />
    </q-breadcrumbs>
  </div>
  <q-separator />

  <div class="q-pa-md">
    <!-- la clave inicial es el DNI: se recomienda cambiarla mientras lo siga siendo -->
    <q-banner v-if="passwordEsDni" rounded class="bg-orange-1 text-orange-10 q-mb-md">
      <template #avatar>
        <ShieldAlert :size="28" />
      </template>
      <div class="text-weight-bold">Tu contraseña todavía es tu DNI</div>
      <div class="text-body2">
        Por seguridad, te recomendamos cambiarla por una que solo tú conozcas.
      </div>
      <template #action>
        <q-btn
          unelevated
          no-caps
          color="orange-9"
          label="Cambiar contraseña"
          :to="{ name: 'Perfil' }"
        />
      </template>
    </q-banner>

    <div class="q-mb-lg">
      <div class="text-h5 text-weight-bold">Hola, {{ primerNombre }} 👋</div>
      <div class="text-body2" style="opacity: 0.68">
        {{ hoy }} · Esto es lo que pasa hoy en el registro cultural.
      </div>
    </div>

    <!-- resumen para quien gestiona personas (admin) -->
    <DashboardAdmin v-if="userStore.hasPermission('admin-personas-index')" class="q-mb-md" />

    <!-- solo para quien tiene ficha de persona (artistas) -->
    <DashboardArtista v-if="persona" v-model="persona" :requisitos="requisitos" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { date } from 'quasar'
import { ShieldAlert } from 'lucide-vue-next'
import DashboardArtista from '@/components/DashboardArtista.vue'
import DashboardAdmin from '@/components/DashboardAdmin.vue'
import { useUserStore } from '@/stores/user-store'
import MiInformacionService from '@/services/MiInformacionService'

const userStore = useUserStore()
const persona = ref(null)

const primerNombre = computed(() => (userStore.getName || '').split(' ')[0] || 'bienvenido')
const hoy = date.formatDate(new Date(), 'dddd D [de] MMMM', {
  days: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
  months: [
    'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
  ],
})
const passwordEsDni = ref(false)
const requisitos = ref([])

onMounted(async () => {
  try {
    const datos = await MiInformacionService.get()
    persona.value = datos.persona
    passwordEsDni.value = datos.password_es_dni
    requisitos.value = datos.requisitos
  } catch {
    // si falla, el dashboard igual se muestra sin los avisos
  }
})
</script>
