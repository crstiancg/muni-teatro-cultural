<template>
  <div class="dashboard-artista">
    <!-- observado: lo primero que tiene que ver es qué corregir -->
    <q-banner
      v-if="persona.estado === 'observado'"
      rounded
      class="observado q-mb-md"
    >
      <template #avatar>
        <MessageSquareWarning :size="26" />
      </template>
      <div class="text-weight-bold">Tu perfil tiene observaciones</div>
      <div class="text-body2 q-mt-xs observacion">{{ persona.observacion }}</div>
      <template #action>
        <q-btn
          unelevated
          no-caps
          color="orange-9"
          label="Corregir ahora"
          :to="{ name: 'CurriculumVitae' }"
        />
      </template>
    </q-banner>

    <div class="row q-col-gutter-md q-mb-md">
      <!-- checklist: mismo criterio que el backend (Persona::requisitosPerfil) -->
      <div class="col-12 col-md-7">
        <q-card flat bordered class="full-height tarjeta">
          <q-card-section>
            <div class="row items-end justify-between q-mb-sm">
              <div>
                <div class="text-subtitle1 text-weight-bold">Tu perfil está al {{ porcentaje }}%</div>
                <div class="text-caption texto-secundario">
                  {{
                    faltanObligatorios
                      ? 'Completa lo obligatorio para poder enviarlo a revisión'
                      : porcentaje === 100
                        ? '¡Perfil completo!'
                        : 'Lo obligatorio está listo; lo recomendado suma presencia'
                  }}
                </div>
              </div>
              <div class="porcentaje">{{ porcentaje }}%</div>
            </div>
            <q-linear-progress
              :value="porcentaje / 100"
              rounded
              size="10px"
              color="primary"
              :track-color="$q.dark.isActive ? 'grey-8' : 'grey-3'"
            />
          </q-card-section>

          <q-list dense class="q-pb-sm">
            <q-item v-for="r in requisitos" :key="r.clave" class="q-py-sm">
              <q-item-section avatar style="min-width: 32px">
                <CircleCheck v-if="r.cumple" :size="20" class="text-positive" />
                <Circle v-else :size="20" class="texto-secundario" />
              </q-item-section>
              <q-item-section>
                <q-item-label :class="{ 'texto-secundario': r.cumple }">
                  {{ r.label }}
                  <span v-if="!r.cumple && r.obligatorio" class="obligatorio">Obligatorio</span>
                </q-item-label>
              </q-item-section>
              <q-item-section v-if="!r.cumple" side>
                <q-btn
                  flat
                  dense
                  no-caps
                  color="primary"
                  :label="ACCIONES[r.clave].label"
                  :to="{ name: ACCIONES[r.clave].ruta }"
                />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </div>

      <!-- estado + qué hacer ahora: misma estructura en todos los estados
           (encabezado con chip, una línea de contexto y una sola zona de acción) -->
      <div class="col-12 col-md-5">
        <q-card flat bordered class="full-height tarjeta">
          <q-card-section class="portal">
            <div class="row items-center justify-between no-wrap">
              <div class="text-subtitle1 text-weight-bold">Tu perfil en el portal</div>
              <q-chip
                dense
                square
                :color="estadoActual.color"
                text-color="white"
                class="text-weight-bold q-ma-none"
              >
                {{ estadoActual.label }}
              </q-chip>
            </div>

            <div class="text-body2 texto-secundario">{{ contexto }}</div>

            <!-- publicado: enlace + compartir -->
            <template v-if="persona.estado === 'aprobado'">
              <q-input :model-value="urlCompartir" readonly dense outlined class="enlace">
                <template #prepend><Link2 :size="16" /></template>
                <template #append>
                  <q-btn flat dense round size="sm" @click="copiar">
                    <Copy :size="16" />
                    <q-tooltip>Copiar enlace</q-tooltip>
                  </q-btn>
                </template>
              </q-input>
              <div class="acciones">
                <q-btn unelevated no-caps color="primary" :href="urlPublica" target="_blank">
                  <ExternalLink :size="16" class="q-mr-sm" /> Ver mi perfil
                </q-btn>
                <q-btn outline no-caps color="positive" :href="urlWhatsapp" target="_blank">
                  <Share2 :size="16" class="q-mr-sm" /> WhatsApp
                </q-btn>
              </div>
            </template>

            <!-- en revisión: solo esperar -->
            <div v-else-if="persona.estado === 'pendiente'" class="aviso">
              <Clock :size="20" />
              <div>Te avisaremos en la campanita cuando un administrador lo revise.</div>
            </div>

            <!-- borrador u observado con lo obligatorio completo: enviar -->
            <q-btn
              v-else-if="!faltanObligatorios"
              unelevated
              no-caps
              color="primary"
              class="full-width"
              :loading="enviando"
              @click="enviar"
            >
              <Send :size="16" class="q-mr-sm" />
              {{ persona.estado === 'observado' ? 'Reenviar a revisión' : 'Enviar a revisión' }}
            </q-btn>

            <!-- falta algo obligatorio: decir qué -->
            <div v-else class="aviso">
              <Lock :size="20" />
              <div>
                Para enviarlo a revisión te falta:
                <ul class="q-my-xs q-pl-md">
                  <li v-for="r in pendientesObligatorios" :key="r.clave">{{ r.label }}</li>
                </ul>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- visitas: una sola serie en el tiempo -> número + columnas de un solo tono -->
    <q-card v-if="visitas" flat bordered class="tarjeta q-mb-md visitas">
      <q-card-section class="row items-center q-col-gutter-lg">
        <div class="col-12 col-sm-4">
          <div class="row items-center no-wrap q-mb-xs">
            <span class="icono"><Eye :size="18" /></span>
            <span class="q-ml-sm text-body2 texto-secundario">Visitas a tu perfil</span>
          </div>
          <div class="numero">{{ visitas.semana }}</div>
          <div class="text-caption">
            <template v-if="variacion !== null">
              <component :is="variacion >= 0 ? TrendingUp : TrendingDown" :size="14" />
              <strong>{{ variacion >= 0 ? '+' : '' }}{{ variacion }}%</strong>
              <span class="texto-secundario"> vs. la semana anterior</span>
            </template>
            <span v-else class="texto-secundario">en los últimos 7 días</span>
          </div>
          <div class="text-caption texto-secundario">{{ visitas.total }} en total</div>
        </div>

        <div class="col-12 col-sm-8">
          <div v-if="persona.estado !== 'aprobado' && !visitas.total" class="texto-secundario text-body2">
            Cuando tu perfil esté publicado, acá vas a ver cuántas personas lo visitan cada día.
          </div>
          <template v-else>
            <div class="columnas">
              <div v-for="d in visitas.dias" :key="d.fecha" class="columna">
                <div
                  class="barra"
                  :style="{ height: d.visitas ? `${Math.max((d.visitas / maxVisitas) * 100, 6)}%` : '2px' }"
                />
                <q-tooltip anchor="top middle" self="bottom middle">
                  {{ fechaCorta(d.fecha) }}: {{ d.visitas }} {{ d.visitas === 1 ? 'visita' : 'visitas' }}
                </q-tooltip>
              </div>
            </div>
            <div class="row justify-between text-caption texto-secundario q-mt-xs">
              <span>{{ fechaCorta(visitas.dias[0]?.fecha) }}</span>
              <span>Hoy</span>
            </div>
          </template>
        </div>
      </q-card-section>
    </q-card>

    <!-- lo que ya cargó, con su acción rápida -->
    <div class="row q-col-gutter-md">
      <div v-for="c in contenido" :key="c.label" class="col-6 col-md-3">
        <q-card
          flat
          bordered
          class="tarjeta tarjeta--clic cursor-pointer full-height"
          @click="$router.push({ name: 'CurriculumVitae' })"
        >
          <q-card-section>
            <div class="row items-center no-wrap q-mb-sm">
              <span class="icono"><component :is="c.icono" :size="18" /></span>
              <span class="q-ml-sm text-body2 texto-secundario">{{ c.label }}</span>
            </div>
            <div class="numero">{{ c.total }}</div>
            <div class="text-caption text-primary text-weight-bold">{{ c.accion }} →</div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { copyToClipboard, date } from 'quasar'
import {
  Circle,
  CircleCheck,
  Copy,
  ExternalLink,
  Share2,
  Lock,
  Link2,
  Clock,
  Send,
  MessageSquareWarning,
  Images,
  EyeOff,
  Award,
  GraduationCap,
  Eye,
  TrendingUp,
  TrendingDown,
} from 'lucide-vue-next'
import PerfilPublicoService from '@/services/PerfilPublicoService'
import { estadoPerfil } from '@/config/estadosPerfil'
import { useNotify } from '@/composables/useNotify'
import { urlCompartirPerfil } from '@/config/institucion'

const props = defineProps({
  // viene de GET mi-informacion (backend: Persona::requisitosPerfil)
  requisitos: { type: Array, default: () => [] },
  // GET mi-informacion -> Persona::resumenVisitas (semana, semana_anterior, total, dias)
  visitas: { type: Object, default: null },
})
const persona = defineModel({ type: Object, required: true })

const router = useRouter()
const { notifySuccess, notifyError } = useNotify()

// a dónde ir para completar cada requisito
const ACCIONES = {
  foto: { label: 'Subir foto', ruta: 'CurriculumVitae' },
  comision: { label: 'Elegir', ruta: 'Perfil' },
  biografia: { label: 'Escribir', ruta: 'CurriculumVitae' },
  actividad: { label: 'Subir', ruta: 'CurriculumVitae' },
  redes: { label: 'Agregar', ruta: 'CurriculumVitae' },
  trayectoria: { label: 'Agregar', ruta: 'CurriculumVitae' },
}

const porcentaje = computed(() => {
  if (!props.requisitos.length) return 0
  return Math.round((props.requisitos.filter((r) => r.cumple).length / props.requisitos.length) * 100)
})

const pendientesObligatorios = computed(() =>
  props.requisitos.filter((r) => r.obligatorio && !r.cumple),
)
const faltanObligatorios = computed(() => pendientesObligatorios.value.length)

const estadoActual = computed(() => estadoPerfil(persona.value.estado))

// una sola línea que explica qué pasa ahora
const contexto = computed(() => {
  switch (persona.value.estado) {
    case 'aprobado':
      return 'Tu perfil está publicado. Tus cambios se ven al guardar.'
    case 'pendiente':
      return 'Tu perfil está en revisión.'
    case 'observado':
      return faltanObligatorios.value
        ? 'Corrige lo observado y completa lo obligatorio para reenviarlo.'
        : '¿Ya corregiste lo observado? Reenvíalo a revisión.'
    default:
      return faltanObligatorios.value
        ? 'Completa lo obligatorio para poder enviarlo.'
        : 'Todo listo: envíalo para que lo publiquen.'
  }
})

const enviando = ref(false)
async function enviar() {
  enviando.value = true
  try {
    persona.value = { ...persona.value, ...(await PerfilPublicoService.enviarRevision()) }
    notifySuccess('Perfil enviado a revisión.')
  } catch (error) {
    notifyError(error.response?.data?.message || 'No se pudo enviar.')
  } finally {
    enviando.value = false
  }
}

const urlPublica = computed(
  () =>
    window.location.origin +
    router.resolve({ name: 'ConsejeroDetallePublico', params: { slug: persona.value.slug } }).href,
)

// para compartir se usa el link del backend: trae la vista previa (Open Graph)
const urlCompartir = computed(() => urlCompartirPerfil(persona.value.slug))

const urlWhatsapp = computed(
  () =>
    'https://wa.me/?text=' +
    encodeURIComponent(`Conoce mi trabajo en el registro cultural: ${urlCompartir.value}`),
)

async function copiar() {
  await copyToClipboard(urlCompartir.value)
  notifySuccess('Enlace copiado.')
}

const maxVisitas = computed(() => Math.max(1, ...(props.visitas?.dias || []).map((d) => d.visitas)))

// sin visitas la semana anterior no hay base para un porcentaje
const variacion = computed(() => {
  const { semana = 0, semana_anterior: anterior = 0 } = props.visitas || {}
  return anterior ? Math.round(((semana - anterior) / anterior) * 100) : null
})

const fechaCorta = (fecha) => (fecha ? date.formatDate(`${fecha}T00:00:00`, 'DD/MM') : '')

const actividades = computed(() => (persona.value.actividades || []).filter((a) => a.flag_activo))
const contenido = computed(() => [
  {
    label: 'Actividades públicas',
    total: actividades.value.filter((a) => a.flag_publico).length,
    icono: Images,
    accion: 'Subir actividad',
  },
  {
    label: 'Actividades ocultas',
    total: actividades.value.filter((a) => !a.flag_publico).length,
    icono: EyeOff,
    accion: 'Revisar',
  },
  {
    label: 'Capacitaciones',
    total: (persona.value.capacitaciones || []).filter((c) => c.flag_activo).length,
    icono: Award,
    accion: 'Agregar',
  },
  {
    label: 'Formación académica',
    total: (persona.value.formaciones_academicas || []).filter((f) => f.flag_activo).length,
    icono: GraduationCap,
    accion: 'Agregar',
  },
])
</script>

<style scoped lang="scss">
.texto-secundario {
  opacity: 0.68;
}

.tarjeta {
  border-radius: 12px;
}

.tarjeta--clic {
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
  }
}

.porcentaje {
  font-size: 1.8rem;
  font-weight: 800;
  line-height: 1;
  color: var(--q-primary);
}

.obligatorio {
  margin-left: 6px;
  padding: 1px 6px;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 700;
  color: #b3243a;
  background: rgba(179, 36, 58, 0.1);
}

.observado {
  background: rgba(239, 108, 0, 0.1);
  border: 1px solid rgba(239, 108, 0, 0.35);
  color: inherit;

  :deep(.q-banner__avatar) {
    color: #ef6c00;
  }
}

.observacion {
  white-space: pre-line;
}

.portal {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.enlace :deep(input) {
  font-size: 0.85rem;
}

/* grilla simple en vez de gutters anidados de Quasar (sumaban márgenes negativos) */
.acciones {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.aviso {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 12px;
  border-radius: 8px;
  background: rgba(128, 128, 128, 0.08);
}

.icono {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  color: var(--q-primary);
  background: rgba(0, 65, 115, 0.1);
}

/* tono de las columnas validado con dataviz (mismo que DashboardAdmin) */
.visitas {
  --barra: #2a6fb0;
}

:global(.body--dark) .visitas {
  --barra: #3b8fd6;
}

.columnas {
  display: flex;
  align-items: flex-end;
  gap: 4px;
  height: 90px;
  border-bottom: 1px solid rgba(128, 128, 128, 0.3);
}

.columna {
  flex: 1;
  height: 100%;
  display: flex;
  align-items: flex-end;
  cursor: default;
}

.barra {
  width: 100%;
  border-radius: 4px 4px 0 0;
  background: var(--barra);
  transition: height 0.4s ease;
}

.numero {
  font-size: 2.1rem;
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 4px;
}
</style>
