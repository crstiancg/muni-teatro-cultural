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
    <q-card v-if="persona" flat bordered>
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold q-mb-sm">Tu perfil en el portal público</div>
        <EstadoPerfilPublico modo="artista" v-model="persona" />
      </q-card-section>
      <q-card-actions align="right">
        <q-btn
          flat
          no-caps
          color="primary"
          label="Completar mi perfil"
          :to="{ name: 'CurriculumVitae' }"
        />
      </q-card-actions>
    </q-card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { date } from 'quasar'
import { ShieldAlert } from 'lucide-vue-next'
import EstadoPerfilPublico from '@/components/EstadoPerfilPublico.vue'
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

onMounted(async () => {
  try {
    const datos = await MiInformacionService.get()
    persona.value = datos.persona
    passwordEsDni.value = datos.password_es_dni
  } catch {
    // si falla, el dashboard igual se muestra sin los avisos
  }
})
</script>
