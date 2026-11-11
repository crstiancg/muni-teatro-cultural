<template>
  <q-dialog v-model="mostrarForm" persistent>
    <EventoForm :key="formKey" :item="eventoEditando" @save="alGuardar" />
  </q-dialog>

  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el><Home size="16" /></q-breadcrumbs-el>
        <q-breadcrumbs-el label="Agenda cultural" />
      </q-breadcrumbs>
    </div>
    <q-separator />

    <div class="pagina">
      <header class="cabecera">
        <div>
          <h1 class="titulo">Agenda cultural</h1>
          <p class="bajada">
            Los eventos de la municipalidad y las propuestas de artistas y agrupaciones que pasan
            por tu revisión.
          </p>
        </div>
        <div class="cabecera-acciones">
          <q-btn flat no-caps color="primary" :to="{ name: 'AgendaPublica' }" target="_blank">
            <ExternalLink :size="16" class="q-mr-xs" /> Ver agenda pública
          </q-btn>
          <q-btn unelevated no-caps color="primary" class="btn-nuevo" @click="abrirCrear">
            <Plus :size="18" class="q-mr-xs" /> Nuevo evento
          </q-btn>
        </div>
      </header>

      <!-- ---------- bandeja: lo que espera una decisión ---------- -->
      <section v-if="puedeRevisar" class="bloque">
        <q-skeleton v-if="cargandoPendientes" height="150px" class="rounded-borders" />

        <template v-else-if="pendientes.length">
          <h2 class="bloque-titulo">
            Esperando tu revisión <span class="contador">{{ totalPendientes }}</span>
          </h2>
          <div class="bandeja">
            <router-link
              v-for="e in pendientes"
              :key="e.id"
              :to="{ name: 'EventoAdminDetalle', params: { id: e.id } }"
              class="pendiente"
            >
              <div class="pendiente-afiche">
                <img v-if="e.imagen_url" :src="e.imagen_url" alt="" loading="lazy" />
                <ImageOff v-else :size="20" class="afiche-vacio" />
              </div>
              <div class="pendiente-cuerpo">
                <div class="pendiente-quien">
                  <component
                    :is="e.organizador_tipo === 'agrupacion' ? UsersRound : User"
                    :size="14"
                  />
                  <span class="ellipsis">{{ nombreOrganizador(e) }}</span>
                </div>
                <div class="pendiente-titulo">{{ e.titulo }}</div>
                <div class="pendiente-cuando">
                  {{ cuandoTexto(e).fecha }} · {{ cuandoTexto(e).horario }}
                </div>
                <div class="pendiente-pie">
                  <span class="pendiente-enviado">Enviado {{ marcaTiempo(e.updated_at) }}</span>
                  <span class="pendiente-revisar">Revisar <ArrowRight :size="15" /></span>
                </div>
              </div>
            </router-link>
          </div>
          <div v-if="totalPendientes > pendientes.length" class="mas-pendientes">
            Y {{ totalPendientes - pendientes.length }} más:
            <q-btn
              flat
              dense
              no-caps
              color="primary"
              label="ver todos los pendientes"
              @click="verPendientes"
            />
          </div>
        </template>

        <div v-else class="al-dia">
          <CircleCheck :size="18" /> Estás al día: no hay eventos esperando revisión.
        </div>
      </section>

      <!-- ---------- todos los eventos ---------- -->
      <section class="bloque">
        <div class="barra">
          <q-btn-toggle
            v-model="cuando"
            no-caps
            unelevated
            rounded
            toggle-color="primary"
            :options="[
              { label: 'Próximos', value: 'proximos' },
              { label: 'Anteriores', value: 'anteriores' },
            ]"
            class="toggle"
            @update:model-value="recargarDesdeInicio"
          />
          <div class="filtros">
            <q-select
              v-model="organizador"
              dense
              outlined
              emit-value
              map-options
              :options="opcionesOrganizador"
              class="filtro"
              @update:model-value="recargarDesdeInicio"
            />
            <q-select
              v-model="estado"
              dense
              outlined
              emit-value
              map-options
              clearable
              :options="opcionesEstadoEvento"
              label="Estado"
              class="filtro"
              @update:model-value="recargarDesdeInicio"
            />
            <q-input
              v-model="buscar"
              dense
              outlined
              debounce="400"
              placeholder="Buscar por título o lugar"
              class="buscador"
              @update:model-value="recargarDesdeInicio"
            >
              <template #prepend><Search :size="16" /></template>
            </q-input>
          </div>
        </div>

        <q-card flat bordered class="lista">
          <template v-if="cargando">
            <div v-for="n in 4" :key="n" class="fila-esqueleto">
              <q-skeleton type="rect" width="48px" height="56px" />
              <q-skeleton type="rect" width="48px" height="64px" />
              <div class="col">
                <q-skeleton type="text" width="60%" /><q-skeleton type="text" width="35%" />
              </div>
            </div>
          </template>

          <div v-else-if="!eventos.length" class="vacio">
            <CalendarSearch :size="28" class="vacio-icono" />
            <div class="vacio-titulo">
              {{ hayFiltros ? 'Ningún evento coincide con los filtros' : textoVacio }}
            </div>
            <q-btn
              v-if="hayFiltros"
              flat
              no-caps
              color="primary"
              label="Quitar filtros"
              @click="limpiarFiltros"
            />
          </div>

          <template v-else>
            <FilaEventoAdmin
              v-for="e in eventos"
              :key="e.id"
              :evento="e"
              @editar="editar"
              @cancelar="cancelar"
              @eliminar="eliminar"
            />
          </template>
        </q-card>

        <div v-if="ultimaPagina > 1" class="paginacion">
          <span class="paginacion-texto">{{ total }} eventos</span>
          <q-pagination
            v-model="pagina"
            :max="ultimaPagina"
            :max-pages="6"
            direction-links
            boundary-links
            color="primary"
            @update:model-value="cargar"
          />
        </div>
      </section>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import {
  Home,
  Plus,
  ExternalLink,
  ImageOff,
  User,
  UsersRound,
  ArrowRight,
  CircleCheck,
  Search,
  CalendarSearch,
} from 'lucide-vue-next'
import EventoService from '@/services/EventoService'
import EventoForm from '@/components/EventoForm.vue'
import FilaEventoAdmin from '@/components/eventos/FilaEventoAdmin.vue'
import { useNotify } from '@/composables/useNotify'
import { useUserStore } from '@/stores/user-store'
import { opcionesEstadoEvento } from '@/config/estadosEvento'
import { cuandoTexto, marcaTiempo } from '@/utils/fechasAgenda'

const $q = useQuasar()
const route = useRoute()
const auth = useUserStore()
const { notifySuccess, notifyError } = useNotify()

const puedeRevisar = computed(() => auth.hasPermission('admin-eventos-aprobar'))

const POR_PAGINA = 15
const MAX_BANDEJA = 6

const opcionesOrganizador = [
  { label: 'Todos los organizadores', value: null },
  { label: 'Municipalidad', value: 'municipalidad' },
  { label: 'Artistas y agrupaciones', value: 'artistas' },
]

// ---------- bandeja ----------
const pendientes = ref([])
const totalPendientes = ref(0)
const cargandoPendientes = ref(true)

// los más urgentes primero: el evento que ocurre antes
async function cargarPendientes() {
  if (!puedeRevisar.value) return
  try {
    const r = await EventoService.getData({
      params: { estado: 'pendiente', order_by: 'fecha_inicio', per_page: MAX_BANDEJA },
    })
    pendientes.value = r.data
    totalPendientes.value = r.total
  } catch {
    pendientes.value = []
  } finally {
    cargandoPendientes.value = false
  }
}

const nombreOrganizador = (e) =>
  e.organizador_tipo === 'agrupacion' ? e.agrupacion?.nombre : e.persona?.nombre_completo

// ---------- listado ----------
const eventos = ref([])
const cargando = ref(true)
const pagina = ref(1)
const ultimaPagina = ref(1)
const total = ref(0)

const cuando = ref('proximos')
const organizador = ref(null)
// la campanita (versión anterior) mandaba ?estado=pendiente
const estado = ref(route.query.estado || null)
const buscar = ref('')

const hayFiltros = computed(() => !!(organizador.value || estado.value || buscar.value))
const textoVacio = computed(() =>
  cuando.value === 'proximos' ? 'No hay eventos próximos' : 'No hay eventos anteriores',
)

async function cargar() {
  cargando.value = true
  try {
    const r = await EventoService.getData({
      params: {
        cuando: cuando.value,
        organizador: organizador.value || undefined,
        estado: estado.value || undefined,
        search: buscar.value || undefined,
        per_page: POR_PAGINA,
        page: pagina.value,
      },
    })
    eventos.value = r.data
    total.value = r.total
    ultimaPagina.value = r.last_page
  } catch (error) {
    notifyError(error.response?.data?.message || 'No se pudieron cargar los eventos.')
  } finally {
    cargando.value = false
  }
}

function recargarDesdeInicio() {
  pagina.value = 1
  cargar()
}

function limpiarFiltros() {
  organizador.value = null
  estado.value = null
  buscar.value = ''
  recargarDesdeInicio()
}

function verPendientes() {
  estado.value = 'pendiente'
  organizador.value = null
  recargarDesdeInicio()
}

function recargarTodo() {
  cargarPendientes()
  cargar()
}

onMounted(cargar)
// los permisos del usuario pueden llegar DESPUÉS de montar la página: si se
// consultara solo en onMounted, la bandeja quedaría en "cargando" para siempre
watch(puedeRevisar, (puede) => puede && cargarPendientes(), { immediate: true })

// ---------- acciones ----------
const mostrarForm = ref(false)
const eventoEditando = ref(null)
const formKey = ref(0)

function abrirCrear() {
  eventoEditando.value = null
  formKey.value++
  mostrarForm.value = true
}

async function editar(evento) {
  eventoEditando.value = await EventoService.get(evento.id)
  formKey.value++
  await nextTick()
  mostrarForm.value = true
}

function alGuardar() {
  mostrarForm.value = false
  recargarTodo()
  notifySuccess('Evento guardado.')
}

function cancelar(evento) {
  $q.dialog({
    title: '¿Cancelar este evento?',
    message: `"${evento.titulo}" dejará de verse en la agenda pública. Seguirá en esta lista como cancelado.`,
    cancel: { label: 'Volver', flat: true, noCaps: true },
    ok: { label: 'Sí, cancelar evento', color: 'orange-9', noCaps: true },
    persistent: true,
  }).onOk(async () => {
    try {
      await EventoService.cancelar(evento.id)
      recargarTodo()
      notifySuccess('Evento cancelado.')
    } catch (error) {
      notifyError(error.response?.data?.message || 'No se pudo cancelar el evento.')
    }
  })
}

function eliminar(evento) {
  $q.dialog({
    title: '¿Eliminar este evento?',
    message: `Se borrará "${evento.titulo}" y su imagen. Este proceso es irreversible. Si solo no se va a realizar, mejor cancélalo.`,
    cancel: { label: 'Volver', flat: true, noCaps: true },
    ok: { label: 'Eliminar', color: 'negative', noCaps: true },
    persistent: true,
  }).onOk(async () => {
    try {
      await EventoService.delete(evento.id)
      recargarTodo()
      notifySuccess('Evento eliminado.')
    } catch (error) {
      notifyError(error.response?.data?.message || 'No se pudo eliminar el evento.')
    }
  })
}
</script>

<style scoped>
.pagina {
  --azul: var(--panel-acento);
  --superficie: var(--panel-superficie);
  --borde: var(--panel-borde);
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
  max-width: 64ch;
  opacity: 0.7;
}

.cabecera-acciones {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.btn-nuevo {
  padding: 8px 18px;
  border-radius: 10px;
}

.bloque {
  margin-bottom: 32px;
}

.bloque-titulo {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 12px;
  font-size: 1rem;
  line-height: 1.3;
  font-weight: 800;
}

.contador {
  /* el h2 hereda de Quasar line-height: 3.75rem: sin esto el número se cae */
  line-height: 1;
  display: inline-grid;
  place-items: center;
  min-width: 24px;
  height: 24px;
  padding: 0 7px;
  border-radius: 999px;
  background: var(--panel-acento);
  color: var(--panel-sobre-acento);
  font-size: 0.8rem;
}

/* ---------- bandeja ---------- */
.bandeja {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
  gap: 12px;
}

.pendiente {
  display: flex;
  gap: 14px;
  padding: 12px;
  border-radius: 14px;
  border: 1px solid var(--borde);
  background: var(--superficie);
  color: inherit;
  text-decoration: none;
  transition:
    box-shadow 0.15s,
    transform 0.15s;
}

.pendiente:hover {
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

.pendiente:focus-visible {
  outline: 2px solid var(--azul);
  outline-offset: 2px;
}

.pendiente-afiche {
  flex: 0 0 78px;
  aspect-ratio: 3 / 4;
  border-radius: 8px;
  overflow: hidden;
  display: grid;
  place-items: center;
  background: rgba(128, 128, 128, 0.12);
}

.pendiente-afiche img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: top;
  display: block;
}

.afiche-vacio {
  opacity: 0.4;
}

.pendiente-cuerpo {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.pendiente-quien {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  opacity: 0.7;
  min-width: 0;
}

.pendiente-titulo {
  margin-top: 4px;
  font-weight: 700;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  overflow-wrap: anywhere;
}

.pendiente-cuando {
  margin-top: 4px;
  font-size: 0.85rem;
  opacity: 0.75;
}

.pendiente-pie {
  margin-top: auto;
  padding-top: 8px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.pendiente-enviado {
  white-space: nowrap;
  font-size: 0.75rem;
  opacity: 0.6;
}

.pendiente-revisar {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-weight: 700;
  font-size: 0.88rem;
  color: var(--azul);
  white-space: nowrap;
}

.mas-pendientes {
  margin-top: 8px;
  font-size: 0.9rem;
}

.al-dia {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  border-radius: 12px;
  border: 1px solid var(--borde);
  background: var(--superficie);
  color: var(--estado-publicado-texto);
  font-weight: 600;
}

/* ---------- listado ---------- */
.barra {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

.toggle {
  border: 1px solid var(--borde);
  background: var(--superficie);
}

.filtros {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.filtro {
  min-width: 200px;
}

.buscador {
  min-width: 260px;
}

.lista {
  border-radius: 14px;
  overflow: hidden;
}

.fila-esqueleto {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 16px;
}

.fila-esqueleto + .fila-esqueleto {
  border-top: 1px solid rgba(128, 128, 128, 0.18);
}

.vacio {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 48px 24px;
  text-align: center;
}

.vacio-icono {
  opacity: 0.45;
}

.vacio-titulo {
  font-weight: 700;
  opacity: 0.8;
}

.paginacion {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 12px;
}

.paginacion-texto {
  font-size: 0.85rem;
  opacity: 0.7;
}

@media (max-width: 599px) {
  .filtros,
  .filtro,
  .buscador {
    width: 100%;
    min-width: 0;
  }

  .toggle {
    width: 100%;
  }

  .toggle :deep(.q-btn) {
    flex: 1;
  }
}
</style>
