<template>
  <div v-if="cargando" class="row q-col-gutter-md">
    <div v-for="n in 4" :key="n" class="col-6 col-md-3">
      <q-skeleton height="112px" class="rounded-borders" />
    </div>
  </div>

  <div v-else-if="datos" class="dashboard-admin">
    <!-- estados: son tarjetas de número, no un gráfico. Ícono + texto: nunca solo color -->
    <div class="row q-col-gutter-md q-mb-md">
      <div v-for="t in tarjetas" :key="t.estado" class="col-6 col-md-3">
        <q-card
          flat
          bordered
          class="tarjeta cursor-pointer full-height"
          :class="{ 'tarjeta--destacada': t.destacada }"
          @click="irALista(t.estado)"
        >
          <q-card-section class="column full-height">
            <div class="row items-center no-wrap q-mb-sm">
              <span class="icono" :class="`icono--${t.estado}`">
                <component :is="t.icono" :size="18" />
              </span>
              <span class="q-ml-sm text-body2 texto-secundario">{{ t.label }}</span>
            </div>
            <div class="numero">{{ t.total }}</div>
            <div class="text-caption texto-secundario q-mt-auto">
              <template v-if="t.destacada">
                <span class="text-weight-bold text-primary">Revisar ahora</span>
                <ArrowRight :size="12" class="q-ml-xs text-primary" />
              </template>
              <template v-else>{{ t.ayuda }}</template>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-md q-mb-md">
      <!-- una sola medida por categoría: barras horizontales de un solo tono -->
      <div class="col-12 col-md-7">
        <q-card flat bordered class="full-height">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold">Artistas por disciplina</div>
            <div class="text-caption texto-secundario">Registrados en cada grupo</div>
          </q-card-section>

          <q-card-section v-if="maxDisciplina" class="q-pt-none">
            <div v-for="d in datos.disciplinas" :key="d.cod_grupo" class="fila-barra">
              <div class="etiqueta ellipsis">{{ comisionDe(d.cod_grupo).corto }}</div>
              <div class="pista">
                <div
                  class="barra"
                  :style="{ width: d.total ? `${Math.max((d.total / maxDisciplina) * 100, 2)}%` : '0' }"
                />
                <q-tooltip anchor="top middle" self="bottom middle">
                  {{ comisionDe(d.cod_grupo).corto }}: {{ d.total }}
                  {{ d.total === 1 ? 'artista' : 'artistas' }} · {{ d.publicados }} publicados
                </q-tooltip>
              </div>
              <div class="valor">
                <span class="text-weight-bold">{{ d.total }}</span>
                <span class="texto-secundario"> · {{ d.publicados }} publ.</span>
              </div>
            </div>
          </q-card-section>
          <div v-else class="vacio">
            <Users :size="28" />
            <div>Todavía no hay artistas con comisión asignada.</div>
          </div>
        </q-card>
      </div>

      <div class="col-12 col-md-5">
        <q-card flat bordered class="full-height column">
          <q-card-section class="row items-center justify-between no-wrap">
            <div>
              <div class="text-subtitle1 text-weight-bold">Solicitudes pendientes</div>
              <div class="text-caption texto-secundario">Las que más esperan, primero</div>
            </div>
            <q-btn
              flat
              dense
              no-caps
              color="primary"
              label="Ver bandeja"
              @click="irALista('pendiente')"
            />
          </q-card-section>

          <q-list v-if="datos.pendientes.length" separator class="q-pb-sm">
            <q-item
              v-for="p in datos.pendientes"
              :key="p.id"
              clickable
              :to="{ name: 'PersonaDetalle', params: { id: p.id } }"
            >
              <q-item-section avatar>
                <q-avatar color="primary" text-color="white" size="38px">
                  <img v-if="p.foto?.url" :src="p.foto.url" style="object-fit: cover" />
                  <template v-else>{{ p.nombre_completo?.charAt(0) }}</template>
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-medium">{{ p.nombre_completo }}</q-item-label>
                <q-item-label caption>Esperando desde {{ hace(p.updated_at) }}</q-item-label>
              </q-item-section>
              <q-item-section side><ChevronRight :size="18" /></q-item-section>
            </q-item>
          </q-list>
          <div v-else class="vacio col">
            <Inbox :size="28" />
            <div>No hay solicitudes pendientes. ¡Todo al día!</div>
          </div>
        </q-card>
      </div>
    </div>

    <q-card flat bordered>
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold">Últimas actividades subidas</div>
        <div class="text-caption texto-secundario">Lo más reciente que cargaron los artistas</div>
      </q-card-section>

      <div v-if="datos.actividades.length" class="row q-col-gutter-md q-px-md q-pb-md">
        <div v-for="a in datos.actividades" :key="a.id" class="col-6 col-sm-4 col-md-3">
          <router-link
            :to="{ name: 'PersonaDetalle', params: { id: a.persona_id } }"
            class="actividad"
          >
            <div class="miniatura">
              <img v-if="a.imagen_url" :src="a.imagen_url" :alt="a.titulo || a.descripcion_texto || ''" loading="lazy" />
              <div v-else class="sin-imagen"><ImageOff :size="22" /></div>
            </div>
            <div class="text-body2 text-weight-medium ellipsis q-mt-xs">
              {{ a.persona?.nombre_completo }}
            </div>
            <div class="text-caption texto-secundario">{{ hace(a.created_at) }}</div>
          </router-link>
        </div>
      </div>
      <div v-else class="vacio">
        <ImageOff :size="28" />
        <div>Todavía no hay actividades.</div>
      </div>
    </q-card>
  </div>

  <q-card v-else flat bordered class="text-center q-pa-lg">
    <div class="q-mb-sm">No se pudo cargar el resumen.</div>
    <q-btn outline no-caps color="primary" label="Reintentar" @click="cargar" />
  </q-card>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { date } from 'quasar'
import {
  ArrowRight,
  ChevronRight,
  Clock,
  CircleCheck,
  MessageSquareWarning,
  FilePen,
  ImageOff,
  Inbox,
  Users,
} from 'lucide-vue-next'
import DashboardService from '@/services/DashboardService'
import { estadoPerfil } from '@/config/estadosPerfil'
import { comisionDe } from '@/config/institucion'

const router = useRouter()
const datos = ref(null)
const cargando = ref(true)

const ICONOS = { pendiente: Clock, aprobado: CircleCheck, observado: MessageSquareWarning, borrador: FilePen }
const AYUDAS = {
  aprobado: 'Visibles en el portal',
  observado: 'Esperando correcciones',
  borrador: 'Aún no enviados',
  pendiente: 'Nada por revisar',
}

const tarjetas = computed(() =>
  Object.entries(datos.value?.estados || {}).map(([estado, total]) => ({
    estado,
    total,
    label: estadoPerfil(estado).label,
    icono: ICONOS[estado],
    ayuda: AYUDAS[estado],
    destacada: estado === 'pendiente' && total > 0,
  })),
)

const maxDisciplina = computed(() => Math.max(0, ...(datos.value?.disciplinas || []).map((d) => d.total)))

// "hace 3 días" se entiende más rápido que una fecha para saber cuánto espera
function hace(valor) {
  const dias = date.getDateDiff(new Date(), valor, 'days')
  if (dias === 0) {
    const horas = date.getDateDiff(new Date(), valor, 'hours')
    return horas <= 0 ? 'hace un momento' : `hace ${horas} h`
  }
  return dias === 1 ? 'ayer' : `hace ${dias} días`
}

// PersonasList lee ?estado= para arrancar con ese filtro
const irALista = (estado) => router.push({ name: 'Personas', query: { estado } })

async function cargar() {
  cargando.value = true
  try {
    datos.value = await DashboardService.admin()
  } catch {
    datos.value = null
  } finally {
    cargando.value = false
  }
}

onMounted(cargar)
</script>

<style scoped lang="scss">
/* Tono de las barras validado con el validador de dataviz: el $primary (#004173)
   es demasiado oscuro para una marca de datos, se usa un paso más claro de la
   misma familia; en dark un paso propio contra la superficie #1d1d1d */
.dashboard-admin {
  --barra: #2a6fb0;
  --pista: rgba(0, 0, 0, 0.06);
}

:global(.body--dark) .dashboard-admin {
  --barra: #3b8fd6;
  --pista: rgba(255, 255, 255, 0.08);
}

.texto-secundario {
  opacity: 0.68;
}

.tarjeta {
  border-radius: 12px;
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
  }
}

.tarjeta--destacada {
  border: 2px solid var(--q-primary);
}

.numero {
  font-size: 2.1rem;
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 4px;
}

.icono {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  flex: none;
}

/* mismo color de estado que los chips (config/estadosPerfil.js), en suave */
.icono--pendiente {
  color: #1976d2;
  background: rgba(25, 118, 210, 0.12);
}
.icono--aprobado {
  color: #21ba45;
  background: rgba(33, 186, 69, 0.12);
}
.icono--observado {
  color: #ef6c00;
  background: rgba(239, 108, 0, 0.12);
}
.icono--borrador {
  color: #757575;
  background: rgba(117, 117, 117, 0.14);
}

.fila-barra {
  display: grid;
  grid-template-columns: minmax(90px, 160px) 1fr auto;
  align-items: center;
  gap: 12px;
  padding: 6px 0;
}

.etiqueta {
  font-size: 0.85rem;
}

.pista {
  height: 10px;
  border-radius: 4px;
  background: var(--pista);
}

.barra {
  height: 100%;
  border-radius: 4px;
  background: var(--barra);
  transition: width 0.5s ease;
}

.valor {
  font-size: 0.85rem;
  white-space: nowrap;
  min-width: 84px;
  text-align: right;
}

.actividad {
  display: block;
  color: inherit;
  text-decoration: none;

  &:hover img {
    transform: scale(1.04);
  }
}

.miniatura {
  aspect-ratio: 4 / 3;
  border-radius: 10px;
  overflow: hidden;
  background: var(--pista);

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
  }
}

.sin-imagen {
  height: 100%;
  display: grid;
  place-items: center;
  opacity: 0.5;
}

.vacio {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 32px 16px;
  text-align: center;
  opacity: 0.6;
}
</style>
