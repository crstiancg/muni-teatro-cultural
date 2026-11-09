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
      <div class="row items-end justify-between q-col-gutter-md q-mb-md">
        <div class="col-12 col-md">
          <div class="text-h6 text-weight-bold">Agrupaciones</div>
          <div class="text-caption texto-secundario">
            Revisa las agrupaciones que envían los representantes antes de publicarlas en el portal.
          </div>
        </div>
        <div class="col-12 col-md-4">
          <q-input
            dense
            outlined
            clearable
            v-model="buscar"
            placeholder="Buscar por nombre"
            debounce="400"
            @update:model-value="cargar(1)"
          >
            <template #prepend><Search :size="18" /></template>
          </q-input>
        </div>
      </div>

      <!-- filtros con su contador: "Pendientes" es la bandeja de trabajo -->
      <div class="filtros q-mb-md">
        <button
          v-for="f in filtros"
          :key="f.value ?? 'todas'"
          class="filtro"
          :class="{ activo: estado === f.value, urgente: f.value === 'pendiente' && f.total > 0 }"
          @click="elegir(f.value)"
        >
          <span class="punto" :class="`bg-${f.color}`" />
          {{ f.label }}
          <span class="contador">{{ f.total }}</span>
        </button>
      </div>

      <q-card flat bordered class="tarjeta">
        <!-- encabezado de columnas (solo en pantallas anchas) -->
        <div class="fila encabezado gt-sm">
          <span>Agrupación</span>
          <span>Representante</span>
          <span>Integrantes</span>
          <span>Último cambio</span>
          <span>Estado</span>
          <span />
        </div>

        <router-link
          v-for="a in filas"
          :key="a.id"
          :to="{ name: 'AgrupacionAdminDetalle', params: { id: a.id } }"
          class="fila item"
          :class="{ pendiente: a.estado === 'pendiente' }"
        >
          <div class="agrupacion">
            <q-avatar size="44px" color="primary" text-color="white" rounded class="logo">
              <img v-if="a.logo?.miniatura_url" :src="a.logo.miniatura_url" />
              <template v-else>{{ a.nombre.charAt(0) }}</template>
            </q-avatar>
            <div class="nombre-bloque">
              <div class="nombre">{{ a.nombre }}</div>
              <div class="text-caption texto-secundario">{{ a.comision?.nombre || 'Sin comisión' }}</div>
            </div>
          </div>
          <div class="celda">
            <span class="lt-md etiqueta-movil">Representante</span>
            {{ a.integrantes?.[0]?.nombre_completo || '—' }}
          </div>
          <div class="celda">
            <span class="lt-md etiqueta-movil">Integrantes</span>
            <Users :size="14" class="q-mr-xs" />{{ a.integrantes_count }}
          </div>
          <div class="celda texto-secundario">
            <span class="lt-md etiqueta-movil">Último cambio</span>
            {{ hace(a.updated_at) }}
          </div>
          <div class="celda">
            <q-chip dense square :color="estadoPerfil(a.estado).color" text-color="white" class="q-ma-none">
              {{ estadoPerfil(a.estado).label }}
            </q-chip>
          </div>
          <div class="celda gt-sm"><ChevronRight :size="18" class="texto-secundario" /></div>
        </router-link>

        <div v-if="!filas.length && !cargando" class="vacio">
          <UsersRound :size="30" />
          <div>
            {{
              buscar
                ? 'No hay agrupaciones con ese nombre.'
                : estado === 'pendiente'
                  ? 'No hay agrupaciones esperando revisión. ¡Todo al día!'
                  : 'No hay agrupaciones en este estado.'
            }}
          </div>
        </div>
        <q-inner-loading :showing="cargando" />
      </q-card>

      <div v-if="ultimaPagina > 1" class="row justify-center q-mt-md">
        <q-pagination v-model="pagina" :max="ultimaPagina" direction-links @update:model-value="cargar" />
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { date } from 'quasar'
import { Home, UsersRound, Users, Search, ChevronRight } from 'lucide-vue-next'
import AgrupacionService from '@/services/AgrupacionService'
import { ESTADOS_PERFIL, estadoPerfil } from '@/config/estadosPerfil'
import { useNotify } from '@/composables/useNotify'

const route = useRoute()
const router = useRouter()
const { notifyError } = useNotify()

const filas = ref([])
const conteos = ref({})
const cargando = ref(false)
const pagina = ref(1)
const ultimaPagina = ref(1)
const buscar = ref('')
// arranca con ?estado= si viene de la campanita o del dashboard
const estado = ref(route.query.estado || null)

// orden de trabajo: primero lo que hay que revisar
const ORDEN = ['pendiente', 'observado', 'aprobado', 'borrador']
const filtros = computed(() => [
  {
    value: null,
    label: 'Todas',
    color: 'grey-7',
    total: Object.values(conteos.value).reduce((s, n) => s + Number(n), 0),
  },
  ...ORDEN.map((value) => ({
    value,
    label: value === 'pendiente' ? 'Pendientes' : ESTADOS_PERFIL[value].label,
    color: ESTADOS_PERFIL[value].color,
    total: Number(conteos.value[value] || 0),
  })),
])

function elegir(valor) {
  estado.value = valor
  router.replace({ query: valor ? { estado: valor } : {} })
  cargar(1)
}

// "hace 2 días" se lee más rápido que una fecha para saber cuánto espera
function hace(valor) {
  const dias = date.getDateDiff(new Date(), valor, 'days')
  if (dias === 0) {
    const horas = date.getDateDiff(new Date(), valor, 'hours')
    return horas <= 0 ? 'hace un momento' : `hace ${horas} h`
  }
  return dias === 1 ? 'ayer' : `hace ${dias} días`
}

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
    conteos.value = r.conteos || {}
    ultimaPagina.value = r.last_page
  } catch {
    notifyError('No se pudieron cargar las agrupaciones.')
  } finally {
    cargando.value = false
  }
}

onMounted(() => cargar(1))
</script>

<style scoped>
.texto-secundario {
  opacity: 0.68;
}

.tarjeta {
  border-radius: 12px;
  overflow: hidden;
  position: relative;
  min-height: 120px;
}

.filtros {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.filtro {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 999px;
  border: 1px solid rgba(128, 128, 128, 0.3);
  background: transparent;
  color: inherit;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.filtro:hover {
  border-color: var(--q-primary);
}

.filtro.activo {
  background: var(--q-primary);
  border-color: var(--q-primary);
  color: white;
}

.filtro.urgente:not(.activo) {
  border-color: #1976d2;
  box-shadow: 0 0 0 1px #1976d2 inset;
}

.punto {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.filtro.activo .punto {
  background: white !important;
}

.contador {
  min-width: 22px;
  padding: 0 6px;
  border-radius: 999px;
  background: rgba(128, 128, 128, 0.18);
  font-size: 0.75rem;
  text-align: center;
}

.filtro.activo .contador {
  background: rgba(255, 255, 255, 0.25);
}

/* grilla de columnas: la misma para el encabezado y cada fila */
.fila {
  display: grid;
  grid-template-columns: minmax(0, 2.4fr) minmax(0, 1.6fr) 0.8fr 1fr 0.9fr 24px;
  align-items: center;
  gap: 16px;
  padding: 12px 18px;
}

.encabezado {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  opacity: 0.6;
  border-bottom: 1px solid rgba(128, 128, 128, 0.2);
}

.item {
  color: inherit;
  text-decoration: none;
  border-bottom: 1px solid rgba(128, 128, 128, 0.12);
  transition: background 0.15s ease;
}

.item:last-of-type {
  border-bottom: 0;
}

.item:hover {
  background: rgba(128, 128, 128, 0.06);
}

/* lo que espera revisión se distingue de un vistazo */
.item.pendiente {
  box-shadow: inset 3px 0 0 #1976d2;
}

.agrupacion {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.logo img {
  object-fit: cover;
}

.nombre-bloque {
  min-width: 0;
}

.nombre {
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.celda {
  display: flex;
  align-items: center;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.vacio {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 40px 16px;
  opacity: 0.6;
  text-align: center;
}

/* pantallas chicas: cada fila es una tarjeta apilada */
@media (max-width: 1023px) {
  .fila {
    grid-template-columns: 1fr;
    gap: 6px;
  }

  .etiqueta-movil {
    width: 110px;
    flex: none;
    font-size: 0.75rem;
    opacity: 0.6;
  }
}
</style>
