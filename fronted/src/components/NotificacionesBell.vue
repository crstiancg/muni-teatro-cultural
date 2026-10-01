<template>
  <q-btn flat round dense class="q-mr-sm">
    <Bell :size="20" />
    <q-badge v-if="noLeidas" color="negative" floating rounded>{{ noLeidas }}</q-badge>

    <q-menu anchor="bottom right" self="top right" style="width: 340px; max-width: 90vw">
      <div class="row items-center justify-between q-px-md q-py-sm">
        <span class="text-weight-bold">Notificaciones</span>
        <q-btn
          v-if="noLeidas"
          flat
          dense
          no-caps
          size="sm"
          color="primary"
          label="Marcar todas como leídas"
          @click="leerTodas"
        />
      </div>
      <q-separator />

      <q-list v-if="avisos.length" separator style="max-height: 400px; overflow-y: auto">
        <q-item
          v-for="aviso in avisos"
          :key="aviso.id"
          clickable
          v-close-popup
          :class="{ 'bg-blue-1': !aviso.read_at && !$q.dark.isActive }"
          @click="abrir(aviso)"
        >
          <q-item-section>
            <q-item-label :class="{ 'text-weight-bold': !aviso.read_at }">
              {{ aviso.data.mensaje }}
            </q-item-label>
            <q-item-label caption>{{ hace(aviso.created_at) }}</q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
      <div v-else class="text-center text-grey-6 q-pa-md">No tienes notificaciones.</div>
    </q-menu>
  </q-btn>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { date } from 'quasar'
import { Bell } from 'lucide-vue-next'
import NotificacionService from '@/services/NotificacionService'

const router = useRouter()
const avisos = ref([])
const noLeidas = ref(0)
let intervalo = null

// sin websockets: se consulta cada minuto, alcanza para avisos de revisión
const CADA_MS = 60_000

async function cargar() {
  try {
    const datos = await NotificacionService.listar()
    avisos.value = datos.data
    noLeidas.value = datos.no_leidas
  } catch {
    // la campanita no debe romper el layout si falla una consulta
  }
}

async function abrir(aviso) {
  if (!aviso.read_at) {
    await NotificacionService.leer(aviso.id)
    cargar()
  }
  const tipo = aviso.data.tipo
  // agrupaciones: el admin va a revisarla; el representante, a gestionarla
  if (['agrupacion_solicitud', 'agrupacion_actualizacion'].includes(tipo)) {
    router.push({ name: 'AgrupacionAdminDetalle', params: { id: aviso.data.agrupacion_id } })
    return
  }
  if (tipo?.startsWith('agrupacion_')) {
    router.push({ name: 'MiAgrupacion', params: { id: aviso.data.agrupacion_id } })
    return
  }
  // al admin le llegan solicitud/actualizacion; al artista aprobado/observado
  if (['solicitud', 'actualizacion'].includes(tipo)) {
    router.push({ name: 'PersonaDetalle', params: { id: aviso.data.persona_id } })
  } else {
    router.push({ name: 'CurriculumVitae' })
  }
}

async function leerTodas() {
  await NotificacionService.leerTodas()
  cargar()
}

function hace(fecha) {
  return date.formatDate(fecha, 'DD/MM/YYYY HH:mm')
}

onMounted(() => {
  cargar()
  intervalo = setInterval(cargar, CADA_MS)
})
onBeforeUnmount(() => clearInterval(intervalo))
</script>
