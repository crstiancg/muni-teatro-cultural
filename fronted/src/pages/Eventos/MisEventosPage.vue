<template>
  <q-dialog v-model="mostrarForm" persistent>
    <EventoForm
      :key="formKey"
      modo="artista"
      :item="eventoEditando"
      :agrupaciones="agrupaciones"
      @save="alGuardar"
    />
  </q-dialog>

  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el><Home size="16" /></q-breadcrumbs-el>
        <q-breadcrumbs-el label="Mis eventos" />
      </q-breadcrumbs>
    </div>
    <q-separator />

    <div class="pagina">
      <header class="cabecera">
        <div>
          <h1 class="titulo">Mis eventos</h1>
          <p class="bajada">
            Propón tus presentaciones y sigue su revisión. Cuando el administrador las aprueba, se
            publican en la agenda cultural.
          </p>
        </div>
        <q-btn
          unelevated
          no-caps
          color="primary"
          class="btn-proponer"
          :disable="cargando"
          @click="abrirCrear"
        >
          <Plus :size="18" class="q-mr-xs" /> Proponer evento
        </q-btn>
      </header>

      <template v-if="cargando">
        <div class="resumen">
          <q-skeleton v-for="n in 3" :key="n" height="92px" class="rounded-borders" />
        </div>
        <q-skeleton height="260px" class="rounded-borders" />
      </template>

      <!-- 403: la cuenta no tiene ficha de persona (p. ej. un usuario solo administrativo) -->
      <div v-else-if="sinFicha" class="vacio">
        <div class="vacio-titulo">Esta sección es para artistas</div>
        <p class="vacio-texto">
          Para proponer eventos tu cuenta necesita una ficha de persona. Los eventos de la
          municipalidad se cargan desde Agenda cultural.
        </p>
      </div>

      <div v-else-if="errorCarga" class="vacio">
        <div class="q-mb-sm">{{ errorCarga }}</div>
        <q-btn outline no-caps color="primary" label="Reintentar" @click="cargar" />
      </div>

      <div v-else-if="!eventos.length" class="vacio">
        <div class="vacio-icono"><CalendarPlus :size="32" /></div>
        <div class="vacio-titulo">Todavía no propusiste ningún evento</div>
        <p class="vacio-texto">
          ¿Tienes una presentación, un taller o un recital? Cárgalo con su afiche y, cuando el
          administrador lo apruebe, aparecerá en la agenda cultural del portal.
        </p>
        <q-btn
          unelevated
          no-caps
          color="primary"
          label="Proponer mi primer evento"
          @click="abrirCrear"
        />
      </div>

      <template v-else>
        <!-- resumen = filtro: de un vistazo qué está aprobado y qué necesita corrección -->
        <div class="resumen">
          <button
            v-for="r in resumen"
            :key="r.estado"
            type="button"
            class="indicador"
            :class="[`indicador--${r.estado}`, { activo: filtro === r.estado, apagado: !r.total }]"
            :aria-pressed="filtro === r.estado"
            @click="alternarFiltro(r.estado)"
          >
            <span class="indicador-icono"><component :is="r.icono" :size="20" /></span>
            <span class="indicador-texto">
              <span class="indicador-numero">{{ r.total }}</span>
              <span class="indicador-label">{{ r.label }}</span>
              <span class="indicador-ayuda">{{ r.ayuda }}</span>
            </span>
          </button>
        </div>

        <div v-if="filtro" class="filtro-activo">
          Mostrando solo: <strong>{{ estadoEvento(filtro).label.toLowerCase() }}</strong>
          <q-btn flat dense no-caps color="primary" label="Ver todos" @click="filtro = null" />
        </div>

        <div v-if="!meses.length" class="vacio vacio--chico">
          {{
            filtro ? 'No tienes próximos eventos en este estado.' : 'No tienes eventos próximos.'
          }}
        </div>

        <section v-for="mes in meses" :key="mes.clave" class="bloque">
          <h2 class="bloque-titulo">{{ mes.titulo }}</h2>
          <q-card flat bordered class="lista">
            <FilaEvento
              v-for="e in mes.eventos"
              :key="e.id"
              :evento="e"
              @editar="editar"
              @cancelar="cancelar"
              @eliminar="eliminar"
            />
          </q-card>
        </section>

        <section v-if="anteriores.length" class="bloque">
          <button type="button" class="plegable" @click="verAnteriores = !verAnteriores">
            <ChevronRight :size="18" class="plegable-flecha" :class="{ abierto: verAnteriores }" />
            Anteriores y cancelados ({{ anteriores.length }})
          </button>
          <q-card v-if="verAnteriores" flat bordered class="lista lista--apagada">
            <FilaEvento
              v-for="e in anteriores"
              :key="e.id"
              :evento="e"
              @editar="editar"
              @cancelar="cancelar"
              @eliminar="eliminar"
            />
          </q-card>
        </section>
      </template>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import {
  Home,
  Plus,
  CalendarPlus,
  ChevronRight,
  MessageSquareWarning,
  Clock,
  CircleCheck,
} from 'lucide-vue-next'
import EventoService from '@/services/EventoService'
import EventoForm from '@/components/EventoForm.vue'
import FilaEvento from '@/components/eventos/FilaMiEvento.vue'
import { useNotify } from '@/composables/useNotify'
import { estadoEvento } from '@/config/estadosEvento'
import { hoy, diaDe, tituloMes } from '@/utils/fechasAgenda'

const $q = useQuasar()
const { notifySuccess, notifyError } = useNotify()

const eventos = ref([])
const agrupaciones = ref([])
const cargando = ref(true)
const errorCarga = ref('')
const sinFicha = ref(false)
const filtro = ref(null)
const verAnteriores = ref(false)

const mostrarForm = ref(false)
const eventoEditando = ref(null)
const formKey = ref(0)

const yaPaso = (e) => diaDe(e.fecha_fin || e.fecha_inicio) < hoy()
const proximos = computed(() => eventos.value.filter((e) => !yaPaso(e) && e.estado !== 'cancelado'))
const anteriores = computed(() =>
  eventos.value
    .filter((e) => yaPaso(e) || e.estado === 'cancelado')
    .sort((a, b) => b.fecha_inicio.localeCompare(a.fecha_inicio)),
)

const resumen = computed(() => {
  const contar = (estado) => proximos.value.filter((e) => e.estado === estado).length
  return [
    {
      estado: 'observado',
      label: 'Observados',
      ayuda: 'Necesitan tu corrección',
      icono: MessageSquareWarning,
      total: contar('observado'),
    },
    {
      estado: 'pendiente',
      label: 'En revisión',
      ayuda: 'Esperando al administrador',
      icono: Clock,
      total: contar('pendiente'),
    },
    {
      estado: 'aprobado',
      label: 'Publicados',
      ayuda: 'Visibles en la agenda',
      icono: CircleCheck,
      total: contar('aprobado'),
    },
  ]
})

// próximos agrupados por mes, como una agenda
const meses = computed(() => {
  const lista = proximos.value
    .filter((e) => !filtro.value || e.estado === filtro.value)
    .sort((a, b) => a.fecha_inicio.localeCompare(b.fecha_inicio))
  const grupos = new Map()
  for (const e of lista) {
    const clave = e.fecha_inicio.slice(0, 7)
    if (!grupos.has(clave))
      grupos.set(clave, { clave, titulo: tituloMes(`${clave}-01`), eventos: [] })
    grupos.get(clave).eventos.push(e)
  }
  return [...grupos.values()]
})

const alternarFiltro = (estado) => {
  filtro.value = filtro.value === estado ? null : estado
}

async function cargar() {
  cargando.value = true
  errorCarga.value = ''
  try {
    const datos = await EventoService.misEventos()
    eventos.value = datos.eventos
    agrupaciones.value = datos.agrupaciones
  } catch (error) {
    sinFicha.value = error.response?.status === 403
    errorCarga.value = error.response?.data?.message || 'No se pudieron cargar tus eventos.'
  } finally {
    cargando.value = false
  }
}

function abrirCrear() {
  eventoEditando.value = null
  formKey.value++
  mostrarForm.value = true
}

async function editar(evento) {
  try {
    eventoEditando.value = await EventoService.miEvento(evento.id)
    formKey.value++
    await nextTick()
    mostrarForm.value = true
  } catch (error) {
    notifyError(error.response?.data?.message || 'No se pudo abrir el evento.')
  }
}

function alGuardar() {
  mostrarForm.value = false
  cargar()
  notifySuccess('Enviado a revisión. Te avisaremos cuando el administrador lo revise.')
}

function cancelar(evento) {
  $q.dialog({
    title: '¿Cancelar este evento?',
    message: `"${evento.titulo}" dejará de verse en la agenda cultural. No se puede volver a activar.`,
    cancel: { label: 'Volver', flat: true, noCaps: true },
    ok: { label: 'Sí, cancelar evento', color: 'orange-9', noCaps: true },
    persistent: true,
  }).onOk(async () => {
    try {
      await EventoService.cancelarMio(evento.id)
      cargar()
      notifySuccess('Evento cancelado.')
    } catch (error) {
      notifyError(error.response?.data?.message || 'No se pudo cancelar el evento.')
    }
  })
}

function eliminar(evento) {
  $q.dialog({
    title: '¿Eliminar este evento?',
    message: `Se borrará "${evento.titulo}" y su imagen. Este proceso es irreversible.`,
    cancel: { label: 'Volver', flat: true, noCaps: true },
    ok: { label: 'Eliminar', color: 'negative', noCaps: true },
    persistent: true,
  }).onOk(async () => {
    try {
      await EventoService.eliminarMio(evento.id)
      cargar()
      notifySuccess('Evento eliminado.')
    } catch (error) {
      notifyError(error.response?.data?.message || 'No se pudo eliminar el evento.')
    }
  })
}

onMounted(cargar)
</script>

<style scoped>
.pagina {
  --azul: var(--panel-acento);
  padding: 24px clamp(16px, 3vw, 40px) 48px;
}

.cabecera {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 24px;
}

.titulo {
  margin: 0;
  font-size: 1.6rem;
  line-height: 1.2;
  font-weight: 800;
  letter-spacing: -0.01em;
}

.bajada {
  margin: 6px 0 0;
  max-width: 62ch;
  opacity: 0.7;
}

.btn-proponer {
  padding: 8px 18px;
  border-radius: 10px;
}

/* ---------- resumen ---------- */
.resumen {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin-bottom: 28px;
}

.indicador {
  --acento: var(--estado-revision-texto);
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  border-radius: 14px;
  border: 1px solid var(--panel-borde);
  border-left: 4px solid var(--acento);
  background: var(--panel-superficie);
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
  transition:
    box-shadow 0.15s,
    transform 0.15s;
}

.indicador:hover {
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
}

.indicador:focus-visible {
  outline: 2px solid var(--acento);
  outline-offset: 2px;
}

.indicador.activo {
  background: color-mix(in srgb, var(--acento) 9%, transparent);
  box-shadow: inset 0 0 0 1px var(--acento);
}

.indicador.apagado {
  opacity: 0.55;
}

.indicador--observado {
  --acento: var(--estado-observado-texto);
}

.indicador--pendiente {
  --acento: var(--estado-revision-texto);
}

.indicador--aprobado {
  --acento: var(--estado-publicado-texto);
}

.indicador-icono {
  flex-shrink: 0;
  display: grid;
  place-items: center;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  color: var(--acento);
  background: color-mix(in srgb, var(--acento) 12%, transparent);
}

.indicador-texto {
  display: grid;
  grid-template-columns: auto 1fr;
  column-gap: 8px;
  align-items: baseline;
}

.indicador-numero {
  font-size: 1.6rem;
  font-weight: 800;
  line-height: 1;
}

.indicador-label {
  font-weight: 700;
}

.indicador-ayuda {
  grid-column: 1 / -1;
  font-size: 0.8rem;
  opacity: 0.65;
  margin-top: 4px;
}

.filtro-activo {
  display: flex;
  align-items: center;
  gap: 6px;
  margin: -14px 0 16px;
  font-size: 0.9rem;
}

/* celular: los tres indicadores en una fila compacta (apilados ocupaban media pantalla) */
@media (max-width: 599px) {
  .resumen {
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 20px;
  }

  .indicador {
    padding: 10px;
    border-left-width: 1px;
    border-top: 3px solid var(--acento);
  }

  .indicador-icono,
  .indicador-ayuda {
    display: none;
  }

  .indicador-texto {
    grid-template-columns: 1fr;
  }

  .indicador-label {
    font-size: 0.75rem;
    margin-top: 2px;
  }
}

/* ---------- lista por mes ---------- */
.bloque {
  margin-bottom: 28px;
}

.bloque-titulo {
  /* los h2 heredan de Quasar line-height: 3.75rem */
  line-height: 1.3;
  margin: 0 0 10px;
  font-size: 0.8rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  opacity: 0.6;
}

.lista {
  border-radius: 14px;
  overflow: hidden;
}

.lista--apagada {
  margin-top: 10px;
}

/* lo que ya pasó o se canceló: presente pero en segundo plano */
.lista--apagada :deep(.fila) {
  opacity: 0.7;
}

.plegable {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 4px;
  border: 0;
  background: none;
  color: inherit;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
  opacity: 0.75;
}

.plegable-flecha {
  transition: transform 0.15s;
}

.plegable-flecha.abierto {
  transform: rotate(90deg);
}

/* ---------- vacío ---------- */
.vacio {
  text-align: center;
  padding: 56px 24px;
  border: 1px dashed rgba(128, 128, 128, 0.35);
  border-radius: 16px;
}

.vacio--chico {
  padding: 28px;
  opacity: 0.7;
}

.vacio-icono {
  display: inline-grid;
  place-items: center;
  width: 64px;
  height: 64px;
  border-radius: 18px;
  color: var(--azul);
  background: var(--panel-acento-suave);
  margin-bottom: 12px;
}

.vacio-titulo {
  font-size: 1.1rem;
  font-weight: 800;
}

.vacio-texto {
  max-width: 52ch;
  margin: 6px auto 18px;
  opacity: 0.7;
}
</style>
