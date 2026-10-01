<template>
  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el><Home size="16" /></q-breadcrumbs-el>
        <q-breadcrumbs-el label="Agrupaciones"><UsersRound size="16" class="q-ml-xs" /></q-breadcrumbs-el>
      </q-breadcrumbs>
    </div>
    <q-separator />

    <div class="q-pa-md">
      <div class="row items-center q-gutter-sm q-mb-md">
        <!-- "Pendientes" es la bandeja: lo que hay que revisar -->
        <q-btn-toggle
          v-model="estado"
          unelevated
          no-caps
          toggle-color="primary"
          :options="opcionesEstado"
          @update:model-value="cargar(1)"
        />
        <q-space />
        <q-input dense outlined v-model="buscar" placeholder="Buscar por nombre" debounce="400" @update:model-value="cargar(1)">
          <template #append><q-icon name="search" /></template>
        </q-input>
      </div>

      <q-card flat :bordered="!$q.dark.isActive">
        <q-list v-if="filas.length" separator>
          <q-item
            v-for="a in filas"
            :key="a.id"
            clickable
            :to="{ name: 'AgrupacionAdminDetalle', params: { id: a.id } }"
          >
            <q-item-section avatar>
              <q-avatar color="primary" text-color="white" rounded>
                <img v-if="a.logo?.miniatura_url" :src="a.logo.miniatura_url" style="object-fit: cover" />
                <template v-else>{{ a.nombre.charAt(0) }}</template>
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ a.nombre }}</q-item-label>
              <q-item-label caption>
                {{ a.comision?.nombre || 'Sin comisión' }} · {{ a.integrantes_count }} integrantes
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-chip dense square :color="estadoPerfil(a.estado).color" text-color="white">
                {{ estadoPerfil(a.estado).label }}
              </q-chip>
            </q-item-section>
          </q-item>
        </q-list>
        <div v-else-if="!cargando" class="text-center text-grey-6 q-pa-xl">
          No hay agrupaciones {{ estado ? 'en este estado' : 'registradas' }}.
        </div>
        <q-inner-loading :showing="cargando" />
      </q-card>

      <div v-if="ultimaPagina > 1" class="row justify-center q-mt-md">
        <q-pagination v-model="pagina" :max="ultimaPagina" @update:model-value="cargar" />
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Home, UsersRound } from 'lucide-vue-next'
import AgrupacionService from '@/services/AgrupacionService'
import { ESTADOS_PERFIL, estadoPerfil } from '@/config/estadosPerfil'
import { useNotify } from '@/composables/useNotify'

const route = useRoute()
const { notifyError } = useNotify()

const filas = ref([])
const cargando = ref(false)
const pagina = ref(1)
const ultimaPagina = ref(1)
const buscar = ref('')
// arranca con ?estado= si viene de la campanita o del dashboard
const estado = ref(route.query.estado || null)
const opcionesEstado = [
  { label: 'Todas', value: null },
  ...Object.entries(ESTADOS_PERFIL).map(([value, e]) => ({
    label: value === 'pendiente' ? 'Pendientes' : e.label,
    value,
  })),
]

async function cargar(p = pagina.value) {
  pagina.value = p
  cargando.value = true
  try {
    const r = await AgrupacionService.listar({
      page: p,
      estado: estado.value || undefined,
      buscar: buscar.value || undefined,
    })
    filas.value = r.data
    ultimaPagina.value = r.last_page
  } catch {
    notifyError('No se pudieron cargar las agrupaciones.')
  } finally {
    cargando.value = false
  }
}

onMounted(() => cargar(1))
</script>
