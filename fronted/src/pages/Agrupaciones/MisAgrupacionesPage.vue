<template>
  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el><Home size="16" /></q-breadcrumbs-el>
        <q-breadcrumbs-el label="Mis agrupaciones"><UsersRound size="16" class="q-ml-xs" /></q-breadcrumbs-el>
      </q-breadcrumbs>
    </div>
    <q-separator />

    <div class="q-pa-md">
      <div class="row items-center justify-between q-mb-md">
        <div>
          <div class="text-h6 text-weight-bold">Mis agrupaciones</div>
          <div class="text-caption text-grey-7">
            Las agrupaciones que representas o donde figuras como integrante.
          </div>
        </div>
        <q-btn unelevated no-caps color="primary" @click="mostrarCrear = true">
          <Plus :size="16" class="q-mr-xs" /> Crear agrupación
        </q-btn>
      </div>

      <div v-if="cargando" class="row q-col-gutter-md">
        <div v-for="n in 3" :key="n" class="col-12 col-sm-6 col-md-4">
          <q-skeleton height="120px" class="rounded-borders" />
        </div>
      </div>

      <div v-else-if="errorCarga" class="text-center q-pa-lg">
        <div class="q-mb-sm">{{ errorCarga }}</div>
        <q-btn outline no-caps color="primary" label="Reintentar" @click="cargar" />
      </div>

      <q-card v-else-if="!agrupaciones.length" flat bordered class="text-center q-pa-xl">
        <UsersRound :size="36" class="text-grey-5" />
        <div class="text-subtitle1 q-mt-sm">Todavía no figuras en ninguna agrupación</div>
        <div class="text-caption text-grey-7 q-mb-md">
          Si diriges o representas un conjunto, créalo y carga a sus integrantes.
        </div>
        <q-btn unelevated no-caps color="primary" label="Crear agrupación" @click="mostrarCrear = true" />
      </q-card>

      <div v-else class="row q-col-gutter-md">
        <div v-for="a in agrupaciones" :key="a.id" class="col-12 col-sm-6 col-md-4">
          <q-card
            flat
            bordered
            class="tarjeta full-height"
            :class="{ 'cursor-pointer': a.es_representante }"
            @click="a.es_representante && router.push({ name: 'MiAgrupacion', params: { id: a.id } })"
          >
            <q-card-section class="row items-center no-wrap q-gutter-md">
              <q-avatar size="56px" color="primary" text-color="white" rounded>
                <img v-if="a.logo?.miniatura_url" :src="a.logo.miniatura_url" style="object-fit: cover" />
                <template v-else>{{ a.nombre.charAt(0) }}</template>
              </q-avatar>
              <div class="col" style="min-width: 0">
                <div class="text-subtitle1 text-weight-bold ellipsis">{{ a.nombre }}</div>
                <div class="text-caption text-grey-7 ellipsis">{{ a.comision || 'Sin comisión' }}</div>
              </div>
            </q-card-section>
            <q-card-section class="q-pt-none row items-center q-gutter-xs">
              <q-chip dense square :color="estadoPerfil(a.estado).color" text-color="white">
                {{ estadoPerfil(a.estado).label }}
              </q-chip>
              <q-chip v-if="a.es_representante" dense square outline color="primary">Representante</q-chip>
              <q-chip v-else-if="a.rol" dense square outline>{{ a.rol }}</q-chip>
            </q-card-section>
            <q-card-section v-if="a.es_representante" class="q-pt-none text-caption text-primary text-weight-bold">
              Gestionar →
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <q-dialog v-model="mostrarCrear">
      <q-card :style="{ width: '100%', maxWidth: '680px' }">
        <q-card-section class="row items-center">
          <div class="text-h6">Crear agrupación</div>
          <q-space />
          <q-btn v-close-popup flat round dense icon="close" />
        </q-card-section>
        <q-separator />
        <q-card-section>
          <AgrupacionDatosForm @guardado="alCrear" />
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Home, UsersRound, Plus } from 'lucide-vue-next'
import AgrupacionDatosForm from '@/components/AgrupacionDatosForm.vue'
import AgrupacionService from '@/services/AgrupacionService'
import { estadoPerfil } from '@/config/estadosPerfil'

const router = useRouter()
const agrupaciones = ref([])
const cargando = ref(true)
const errorCarga = ref('')
const mostrarCrear = ref(false)

async function cargar() {
  cargando.value = true
  errorCarga.value = ''
  try {
    agrupaciones.value = await AgrupacionService.mias()
  } catch (error) {
    errorCarga.value = error.response?.data?.message || 'No se pudieron cargar tus agrupaciones.'
  } finally {
    cargando.value = false
  }
}

// recién creada: se va directo a cargar logo e integrantes
function alCrear(agrupacion) {
  mostrarCrear.value = false
  router.push({ name: 'MiAgrupacion', params: { id: agrupacion.id } })
}

onMounted(cargar)
</script>

<style scoped>
.tarjeta {
  border-radius: 12px;
  transition: box-shadow 0.15s ease;
}

.tarjeta.cursor-pointer:hover {
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
}
</style>
