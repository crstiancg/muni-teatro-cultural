<template>
  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el><Home size="16" /></q-breadcrumbs-el>
        <q-breadcrumbs-el label="Agrupaciones" :to="{ name: 'AgrupacionesAdmin' }" />
        <q-breadcrumbs-el :label="agrupacion?.nombre || '...'" />
      </q-breadcrumbs>
    </div>
    <q-separator />

    <div v-if="cargando" class="q-pa-md">
      <q-skeleton height="140px" class="rounded-borders q-mb-md" />
      <q-skeleton height="320px" class="rounded-borders" />
    </div>

    <div v-else-if="!agrupacion" class="text-center q-pa-lg">
      <div class="q-mb-sm">No se pudo cargar la agrupación.</div>
      <q-btn outline no-caps color="primary" label="Reintentar" @click="cargar" />
    </div>

    <div v-else class="q-pa-md">
      <!-- cabecera: quién es, en qué estado está y qué puede hacer el admin -->
      <q-card flat bordered class="tarjeta q-mb-md">
        <q-card-section class="row items-center q-col-gutter-md">
          <div class="col-auto">
            <q-avatar size="88px" color="primary" text-color="white" rounded class="text-h4 logo">
              <img v-if="agrupacion.logo?.url" :src="agrupacion.logo.url" />
              <template v-else>{{ agrupacion.nombre.charAt(0) }}</template>
            </q-avatar>
          </div>

          <div class="col" style="min-width: 0">
            <div class="row items-center q-gutter-sm">
              <div class="text-h5 text-weight-bold ellipsis">{{ agrupacion.nombre }}</div>
              <q-chip dense square :color="estado.color" text-color="white" class="text-weight-bold">
                {{ estado.label }}
              </q-chip>
            </div>
            <div class="row items-center q-gutter-md text-body2 texto-secundario q-mt-xs">
              <span><FolderTree :size="15" class="q-mr-xs" />{{ agrupacion.comision || 'Sin comisión' }}</span>
              <span><Users :size="15" class="q-mr-xs" />{{ agrupacion.integrantes.length }} integrantes</span>
              <span v-if="representante">
                <UserRound :size="15" class="q-mr-xs" />Representante: {{ representante.nombre_completo }}
              </span>
            </div>
          </div>

          <!-- acciones: mismas reglas que AgrupacionController::aprobar/observar -->
          <div class="col-12 col-md-auto row q-gutter-sm justify-end">
            <template v-if="puedeAprobar">
              <q-btn
                v-if="agrupacion.estado === 'pendiente'"
                unelevated
                no-caps
                color="positive"
                :loading="procesando"
                @click="aprobar"
              >
                <CircleCheck :size="16" class="q-mr-sm" /> Aprobar y publicar
              </q-btn>
              <q-btn
                v-if="['pendiente', 'aprobado'].includes(agrupacion.estado)"
                outline
                no-caps
                color="orange-9"
                :disable="procesando"
                @click="observar"
              >
                <MessageSquareWarning :size="16" class="q-mr-sm" />
                {{ agrupacion.estado === 'aprobado' ? 'Despublicar con observación' : 'Observar' }}
              </q-btn>
            </template>
            <q-btn
              v-if="agrupacion.estado === 'aprobado'"
              flat
              no-caps
              color="primary"
              :to="{ name: 'AgrupacionPublica', params: { slug: agrupacion.slug } }"
              target="_blank"
            >
              <ExternalLink :size="16" class="q-mr-sm" /> Ver en el portal
            </q-btn>
          </div>
        </q-card-section>

        <!-- contexto del estado, en una línea -->
        <q-banner
          v-if="agrupacion.estado === 'observado' && agrupacion.observacion"
          class="bg-orange-1 text-orange-10 texto-largo"
        >
          <strong>Observación enviada:</strong> {{ agrupacion.observacion }}
        </q-banner>
        <q-banner v-else-if="agrupacion.estado === 'pendiente'" class="bg-blue-1 text-blue-10">
          El representante la envió a revisión. Revisa los datos y los integrantes antes de aprobar.
        </q-banner>
        <q-banner v-else-if="agrupacion.estado === 'borrador'" class="bg-grey-2 text-grey-9">
          Todavía no fue enviada a revisión.
        </q-banner>
      </q-card>

      <div class="row q-col-gutter-md">
        <!-- principal: lo que se va a publicar -->
        <div class="col-12 col-md-8">
          <q-card flat bordered class="tarjeta q-mb-md">
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold q-mb-sm">Descripción</div>
              <!-- HTML sanitizado en el backend al guardar -->
              <div v-if="agrupacion.descripcion" class="descripcion texto-largo" v-html="agrupacion.descripcion" />
              <div v-else class="texto-secundario">Sin descripción.</div>

              <div v-if="redes.length" class="row q-gutter-sm q-mt-md">
                <q-btn
                  v-for="r in redes"
                  :key="r.clave"
                  outline
                  dense
                  no-caps
                  color="primary"
                  :href="r.url"
                  target="_blank"
                  class="q-px-sm"
                >
                  <component :is="r.icono" :size="15" class="q-mr-xs" /> {{ r.label }}
                </q-btn>
              </div>
            </q-card-section>
          </q-card>

          <q-card flat bordered class="tarjeta">
            <q-card-section class="row items-center justify-between">
              <div class="text-subtitle1 text-weight-bold">Integrantes ({{ agrupacion.integrantes.length }})</div>
              <div class="text-caption texto-secundario">
                {{ vinculados }} {{ vinculados === 1 ? 'es artista registrado' : 'son artistas registrados' }}
              </div>
            </q-card-section>
            <q-table
              flat
              :rows="agrupacion.integrantes"
              :columns="columnas"
              row-key="id"
              hide-bottom
              :pagination="{ rowsPerPage: 0 }"
            >
              <template #body-cell-nombre="props">
                <q-td :props="props">
                  <div class="row items-center no-wrap q-gutter-sm">
                    <q-avatar size="30px" color="primary" text-color="white" class="text-caption">
                      {{ props.row.nombre?.charAt(0) }}
                    </q-avatar>
                    <span class="text-weight-medium">{{ props.row.nombre_completo }}</span>
                  </div>
                </q-td>
              </template>
              <template #body-cell-tipo="props">
                <q-td :props="props">
                  <q-badge v-if="props.row.es_representante" color="primary" class="q-mr-xs">Representante</q-badge>
                  <q-badge v-if="props.row.persona_id" color="positive" outline>Artista registrado</q-badge>
                </q-td>
              </template>
            </q-table>
          </q-card>
        </div>

        <!-- lateral: requisitos e historial -->
        <div class="col-12 col-md-4">
          <q-card flat bordered class="tarjeta q-mb-md">
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold q-mb-xs">Requisitos</div>
              <q-list dense>
                <q-item v-for="r in agrupacion.requisitos" :key="r.clave" class="q-px-none">
                  <q-item-section avatar style="min-width: 28px">
                    <CircleCheck v-if="r.cumple" :size="18" class="text-positive" />
                    <Circle v-else :size="18" class="text-grey-5" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label :class="{ 'texto-secundario': r.cumple }">{{ r.label }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card-section>
          </q-card>

          <q-card flat bordered class="tarjeta">
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold q-mb-sm">Historial de revisiones</div>
              <HistorialRevisiones :revisiones="agrupacion.revisiones" />
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import {
  Home,
  FolderTree,
  Users,
  UserRound,
  CircleCheck,
  Circle,
  MessageSquareWarning,
  ExternalLink,
  Facebook,
  Instagram,
  Music2,
  Youtube,
  Globe,
} from 'lucide-vue-next'
import HistorialRevisiones from '@/components/HistorialRevisiones.vue'
import AgrupacionService from '@/services/AgrupacionService'
import { estadoPerfil } from '@/config/estadosPerfil'
import { useNotify } from '@/composables/useNotify'
import { useUserStore } from '@/stores/user-store'

const $q = useQuasar()
const route = useRoute()
const userStore = useUserStore()
const { notifySuccess, notifyError } = useNotify()
const id = Number(route.params.id)

const agrupacion = ref(null)
const cargando = ref(true)
const procesando = ref(false)

const puedeAprobar = computed(() => userStore.hasPermission('admin-agrupaciones-aprobar'))
const estado = computed(() => estadoPerfil(agrupacion.value?.estado))
const representante = computed(() => agrupacion.value?.integrantes.find((i) => i.es_representante))
const vinculados = computed(() => agrupacion.value?.integrantes.filter((i) => i.persona_id).length || 0)

const REDES = {
  facebook: { label: 'Facebook', icono: Facebook },
  instagram: { label: 'Instagram', icono: Instagram },
  tiktok: { label: 'TikTok', icono: Music2 },
  youtube: { label: 'YouTube', icono: Youtube },
  web: { label: 'Página web', icono: Globe },
}
const redes = computed(() =>
  Object.entries(agrupacion.value?.redes_sociales || {}).map(([clave, url]) => ({
    clave,
    url,
    ...(REDES[clave] || { label: clave, icono: Globe }),
  })),
)

// el DNI se ve acá (panel del admin), nunca en el portal
const columnas = [
  { name: 'nombre', label: 'Nombre', field: 'nombre_completo', align: 'left' },
  { name: 'dni', label: 'DNI', field: 'dni', align: 'left' },
  { name: 'rol', label: 'Rol', field: (r) => r.rol || '—', align: 'left' },
  { name: 'tipo', label: '', field: 'id', align: 'right' },
]

async function cargar() {
  cargando.value = true
  try {
    agrupacion.value = await AgrupacionService.ver(id)
  } catch {
    agrupacion.value = null
  } finally {
    cargando.value = false
  }
}

async function ejecutar(accion, mensaje) {
  procesando.value = true
  try {
    agrupacion.value = await accion()
    notifySuccess(mensaje)
  } catch (error) {
    notifyError(error.response?.data?.message || 'No se pudo completar la acción.')
  } finally {
    procesando.value = false
  }
}

const aprobar = () => ejecutar(() => AgrupacionService.aprobar(id), 'Agrupación aprobada y publicada.')

function observar() {
  $q.dialog({
    title: 'Observar agrupación',
    message: 'Indica qué debe corregir. El representante recibirá este mensaje.',
    prompt: { model: '', type: 'textarea', isValid: (v) => v.trim().length > 0 },
    cancel: true,
    persistent: true,
  }).onOk((texto) => ejecutar(() => AgrupacionService.observar(id, texto.trim()), 'Observación enviada.'))
}

onMounted(cargar)
</script>

<style scoped>
.tarjeta {
  border-radius: 12px;
  overflow: hidden;
}

.logo img {
  object-fit: cover;
}

.texto-secundario {
  opacity: 0.68;
}

.texto-largo {
  overflow-wrap: anywhere;
  word-break: break-word;
}

.descripcion {
  line-height: 1.7;
}

.descripcion :deep(p),
.descripcion :deep(div) {
  margin: 0 0 0.6em;
}

.descripcion :deep(ul),
.descripcion :deep(ol) {
  padding-left: 1.3em;
}
</style>
