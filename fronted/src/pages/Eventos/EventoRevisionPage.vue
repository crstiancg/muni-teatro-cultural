<template>
  <q-dialog v-model="mostrarForm" persistent>
    <EventoForm v-if="evento" :key="formKey" :item="evento" @save="alGuardar" />
  </q-dialog>

  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el><Home size="16" /></q-breadcrumbs-el>
        <q-breadcrumbs-el label="Agenda cultural" :to="{ name: 'EventosAdmin' }" />
        <q-breadcrumbs-el :label="evento?.titulo ?? 'Evento'" />
      </q-breadcrumbs>
    </div>
    <q-separator />

    <div class="pagina">
      <router-link :to="{ name: 'EventosAdmin' }" class="volver">
        <ArrowLeft :size="16" /> Agenda cultural
      </router-link>

      <div v-if="cargando" class="grilla">
        <q-skeleton height="420px" class="rounded-borders" />
        <div>
          <q-skeleton type="text" width="40%" />
          <q-skeleton type="text" height="48px" />
          <q-skeleton height="220px" class="rounded-borders q-mt-md" />
        </div>
      </div>

      <div v-else-if="errorCarga" class="vacio">
        <div class="q-mb-sm">{{ errorCarga }}</div>
        <q-btn outline no-caps color="primary" label="Reintentar" @click="cargar" />
      </div>

      <div v-else class="grilla">
        <!-- ---------- columna fija: afiche + decisión ---------- -->
        <aside class="lateral">
          <a
            v-if="evento.imagen_url"
            :href="evento.imagen_url"
            target="_blank"
            rel="noopener"
            class="afiche"
            title="Abrir el afiche en tamaño completo"
          >
            <img :src="evento.imagen_url" :alt="`Afiche de ${evento.titulo}`" />
            <span class="afiche-zoom"><Maximize2 :size="14" /> Ver completo</span>
          </a>
          <div v-else class="afiche afiche--vacio">
            <ImageOff :size="28" />
            <span>Sin afiche</span>
          </div>

          <!-- la decisión: solo para lo que propusieron artistas y agrupaciones -->
          <div
            v-if="esPropuesta && puedeRevisar"
            class="decision"
            :class="`decision--${evento.estado}`"
          >
            <template v-if="decidido">
              <div class="decision-titulo">
                <CircleCheck :size="18" />
                {{ decidido === 'aprobado' ? 'Aprobado y publicado' : 'Observación enviada' }}
              </div>
              <p class="decision-texto">Le avisamos al organizador por la campanita.</p>
              <q-btn
                v-if="siguienteId"
                unelevated
                no-caps
                color="primary"
                class="full-width"
                :to="{ name: 'EventoAdminDetalle', params: { id: siguienteId } }"
              >
                Revisar el siguiente <ArrowRight :size="16" class="q-ml-xs" />
              </q-btn>
              <q-btn
                v-else
                flat
                no-caps
                color="primary"
                class="full-width"
                :to="{ name: 'EventosAdmin' }"
              >
                No quedan pendientes · volver a la agenda
              </q-btn>
            </template>

            <template v-else-if="evento.estado === 'pendiente'">
              <div class="decision-titulo">Tu decisión</div>
              <p class="decision-texto">
                Al aprobarlo se publica en la agenda. Si algo no está bien, obsérvalo y dile qué
                corregir.
              </p>
              <template v-if="!observando">
                <q-btn
                  unelevated
                  no-caps
                  color="positive"
                  class="full-width"
                  :loading="enviando"
                  @click="aprobar"
                >
                  <Check :size="17" class="q-mr-xs" /> Aprobar y publicar
                </q-btn>
                <q-btn
                  outline
                  no-caps
                  color="deep-orange-9"
                  class="full-width q-mt-sm"
                  :disable="enviando"
                  @click="observando = true"
                >
                  <MessageSquareWarning :size="17" class="q-mr-xs" /> Observar
                </q-btn>
              </template>
            </template>

            <template v-else-if="evento.estado === 'aprobado'">
              <div class="decision-titulo"><CircleCheck :size="18" /> Publicado en la agenda</div>
              <p class="decision-texto">
                Si detectas un problema puedes observarlo: sale de la agenda hasta que lo corrijan.
              </p>
              <q-btn
                v-if="!observando"
                outline
                no-caps
                color="deep-orange-9"
                class="full-width"
                @click="observando = true"
              >
                <MessageSquareWarning :size="17" class="q-mr-xs" /> Observar
              </q-btn>
            </template>

            <template v-else-if="evento.estado === 'observado'">
              <div class="decision-titulo"><Clock :size="18" /> Esperando la corrección</div>
              <p class="decision-texto">
                Cuando el organizador lo corrija, vuelve a tu bandeja de revisión.
              </p>
            </template>

            <template v-else>
              <div class="decision-titulo"><Ban :size="18" /> Cancelado</div>
              <p class="decision-texto">No se muestra en la agenda pública.</p>
            </template>

            <!-- observar: en línea, sin otro dialog encima -->
            <form v-if="observando && !decidido" class="observar" @submit.prevent="observar">
              <label for="motivo" class="observar-label">¿Qué tiene que corregir?</label>
              <q-input
                id="motivo"
                v-model="motivo"
                type="textarea"
                outlined
                autogrow
                autofocus
                counter
                maxlength="1000"
                placeholder="Por ejemplo: falta la dirección exacta del local; el afiche está pixelado."
                :input-style="{ minHeight: '84px' }"
              />
              <div class="observar-acciones">
                <q-btn
                  flat
                  no-caps
                  label="Volver"
                  :disable="enviando"
                  @click="observando = false"
                />
                <q-btn
                  unelevated
                  no-caps
                  color="deep-orange-9"
                  type="submit"
                  label="Enviar observación"
                  :loading="enviando"
                  :disable="!motivo.trim()"
                />
              </div>
            </form>
          </div>
        </aside>

        <!-- ---------- contenido ---------- -->
        <article class="contenido">
          <div class="encabezado">
            <div class="etiquetas">
              <q-chip
                dense
                square
                :color="estadoEvento(evento.estado).color"
                text-color="white"
                class="q-ma-none chip"
              >
                {{ estadoEvento(evento.estado).label }}
              </q-chip>
              <span class="tipo">{{
                esPropuesta
                  ? 'Propuesta de ' +
                    (evento.organizador_tipo === 'agrupacion' ? 'agrupación' : 'artista')
                  : 'Evento de la municipalidad'
              }}</span>
            </div>
            <div class="encabezado-acciones">
              <q-btn
                v-if="evento.estado === 'aprobado'"
                flat
                no-caps
                color="primary"
                :to="{ name: 'EventoPublico', params: { evento: String(evento.id) } }"
                target="_blank"
              >
                <ExternalLink :size="15" class="q-mr-xs" /> Ver en la agenda
              </q-btn>
              <q-btn outline no-caps color="primary" @click="abrirEditar">
                <Pencil :size="15" class="q-mr-xs" /> Editar
              </q-btn>
              <q-btn flat round dense aria-label="Más opciones">
                <EllipsisVertical :size="18" />
                <q-menu anchor="bottom right" self="top right">
                  <q-list dense style="min-width: 190px">
                    <q-item
                      v-if="evento.estado !== 'cancelado'"
                      v-close-popup
                      clickable
                      class="text-orange-9"
                      @click="cancelar"
                    >
                      <q-item-section avatar class="opcion-icono"
                        ><CalendarX :size="16"
                      /></q-item-section>
                      <q-item-section>Cancelar evento</q-item-section>
                    </q-item>
                    <q-item v-close-popup clickable class="text-negative" @click="eliminar">
                      <q-item-section avatar class="opcion-icono"
                        ><Trash2 :size="16"
                      /></q-item-section>
                      <q-item-section>Eliminar</q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </div>
          </div>

          <h1 class="titulo">{{ evento.titulo }}</h1>

          <div v-if="evento.estado === 'observado' && evento.observacion" class="aviso-observado">
            <MessageSquareWarning :size="18" />
            <div><strong>Lo observaste:</strong> {{ evento.observacion }}</div>
          </div>

          <dl class="datos">
            <div class="dato">
              <dt><CalendarDays :size="16" /> Cuándo</dt>
              <dd>
                {{ cuando.fecha }}<br />
                <span class="secundario">{{ cuando.horario }}</span>
              </dd>
            </div>
            <div class="dato">
              <dt>
                <component :is="evento.modalidad === 'virtual' ? Video : MapPin" :size="16" />
                {{ evento.modalidad === 'virtual' ? 'Transmisión' : 'Dónde' }}
              </dt>
              <dd v-if="evento.modalidad === 'virtual'">
                Virtual<br />
                <a :href="evento.enlace" target="_blank" rel="noopener" class="enlace">{{
                  evento.enlace
                }}</a>
              </dd>
              <dd v-else>
                {{ evento.lugar }}<br />
                <span class="secundario">{{ evento.direccion || 'Sin dirección' }}</span>
              </dd>
            </div>
            <div class="dato">
              <dt><Ticket :size="16" /> Entrada</dt>
              <dd>
                {{
                  evento.flag_gratuito ? 'Ingreso libre' : `S/ ${Number(evento.costo).toFixed(2)}`
                }}
              </dd>
            </div>
            <div class="dato">
              <dt><Tag :size="16" /> Disciplina</dt>
              <dd>{{ evento.comision?.nombre ?? 'Sin disciplina' }}</dd>
            </div>
            <div v-if="evento.modalidad === 'presencial' && evento.enlace" class="dato dato--ancho">
              <dt><Link2 :size="16" /> Más información</dt>
              <dd>
                <a :href="evento.enlace" target="_blank" rel="noopener" class="enlace">{{
                  evento.enlace
                }}</a>
              </dd>
            </div>
          </dl>

          <section v-if="esPropuesta" class="seccion">
            <h2 class="seccion-titulo">Organiza</h2>
            <router-link v-if="rutaOrganizador" :to="rutaOrganizador" class="organizador">
              <span class="organizador-avatar">
                <component
                  :is="evento.organizador_tipo === 'agrupacion' ? UsersRound : User"
                  :size="20"
                />
              </span>
              <span class="organizador-texto">
                <span class="organizador-nombre">{{ nombreOrganizador }}</span>
                <span class="secundario"
                  >{{ evento.organizador_tipo === 'agrupacion' ? 'Agrupación' : 'Artista' }} · ver
                  su ficha</span
                >
              </span>
              <ArrowRight :size="16" class="organizador-flecha" />
            </router-link>
          </section>

          <section class="seccion">
            <h2 class="seccion-titulo">Descripción</h2>
            <!-- HTML sanitizado en el backend (App\Support\Html::limpio) -->
            <!-- eslint-disable-next-line vue/no-v-html -->
            <div v-if="evento.descripcion" class="descripcion" v-html="evento.descripcion" />
            <p v-else class="secundario">El organizador no escribió una descripción.</p>
          </section>

          <section v-if="evento.revisiones?.length" class="seccion">
            <h2 class="seccion-titulo">Historial</h2>
            <ol class="historial">
              <li
                v-for="r in evento.revisiones"
                :key="r.id"
                class="hito"
                :class="`hito--${r.accion}`"
              >
                <span class="hito-punto" />
                <div>
                  <div class="hito-titulo">
                    {{ ACCIONES[r.accion] }}
                    <span class="secundario"
                      >· {{ marcaTiempo(r.created_at)
                      }}<template v-if="r.usuario"> · {{ r.usuario.name }}</template></span
                    >
                  </div>
                  <div v-if="r.observacion" class="hito-texto">{{ r.observacion }}</div>
                </div>
              </li>
            </ol>
          </section>
        </article>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import {
  Home,
  ArrowLeft,
  ArrowRight,
  ImageOff,
  Maximize2,
  Check,
  CircleCheck,
  Clock,
  Ban,
  MessageSquareWarning,
  ExternalLink,
  Pencil,
  EllipsisVertical,
  CalendarX,
  Trash2,
  CalendarDays,
  MapPin,
  Video,
  Ticket,
  Tag,
  Link2,
  User,
  UsersRound,
} from 'lucide-vue-next'
import EventoService from '@/services/EventoService'
import EventoForm from '@/components/EventoForm.vue'
import { useNotify } from '@/composables/useNotify'
import { useUserStore } from '@/stores/user-store'
import { estadoEvento } from '@/config/estadosEvento'
import { cuandoTexto, marcaTiempo } from '@/utils/fechasAgenda'

const ACCIONES = { enviado: 'Enviado a revisión', aprobado: 'Aprobado', observado: 'Observado' }

const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const auth = useUserStore()
const { notifySuccess, notifyError } = useNotify()

const puedeRevisar = computed(() => auth.hasPermission('admin-eventos-aprobar'))

const evento = ref(null)
const cargando = ref(true)
const errorCarga = ref('')

const observando = ref(false)
const motivo = ref('')
const enviando = ref(false)
// tras decidir: 'aprobado' | 'observado', y el próximo de la cola
const decidido = ref(null)
const siguienteId = ref(null)

const esPropuesta = computed(() => evento.value?.organizador_tipo !== 'municipalidad')
const cuando = computed(() => cuandoTexto(evento.value))
const nombreOrganizador = computed(() =>
  evento.value.organizador_tipo === 'agrupacion'
    ? evento.value.agrupacion?.nombre
    : evento.value.persona?.nombre_completo,
)
const rutaOrganizador = computed(() => {
  const e = evento.value
  if (e.organizador_tipo === 'persona' && e.persona)
    return { name: 'PersonaDetalle', params: { id: e.persona.id } }
  if (e.organizador_tipo === 'agrupacion' && e.agrupacion)
    return { name: 'AgrupacionAdminDetalle', params: { id: e.agrupacion.id } }
  return null
})

async function cargar() {
  cargando.value = true
  errorCarga.value = ''
  try {
    evento.value = await EventoService.get(route.params.id)
  } catch (error) {
    errorCarga.value =
      error.response?.status === 404 ? 'Este evento ya no existe.' : 'No se pudo cargar el evento.'
  } finally {
    cargando.value = false
  }
}

// "Revisar el siguiente" cambia solo el :id: misma página, otro evento
watch(
  () => route.params.id,
  (id) => {
    if (!id) return
    observando.value = false
    motivo.value = ''
    decidido.value = null
    siguienteId.value = null
    cargar()
  },
  { immediate: true },
)

async function buscarSiguiente() {
  try {
    const r = await EventoService.getData({
      params: { estado: 'pendiente', order_by: 'fecha_inicio', per_page: 1 },
    })
    siguienteId.value = r.data[0]?.id ?? null
  } catch {
    siguienteId.value = null
  }
}

async function aprobar() {
  enviando.value = true
  try {
    evento.value = await EventoService.aprobar(evento.value.id)
    decidido.value = 'aprobado'
    notifySuccess('Aprobado: ya aparece en la agenda cultural.')
    await buscarSiguiente()
  } catch (error) {
    notifyError(error.response?.data?.message || 'No se pudo aprobar el evento.')
  } finally {
    enviando.value = false
  }
}

async function observar() {
  if (!motivo.value.trim()) return
  enviando.value = true
  try {
    evento.value = await EventoService.observar(evento.value.id, motivo.value.trim())
    decidido.value = 'observado'
    observando.value = false
    notifySuccess('Observación enviada al organizador.')
    await buscarSiguiente()
  } catch (error) {
    notifyError(error.response?.data?.message || 'No se pudo enviar la observación.')
  } finally {
    enviando.value = false
  }
}

// ---------- editar / cancelar / eliminar ----------
const mostrarForm = ref(false)
const formKey = ref(0)

function abrirEditar() {
  formKey.value++
  mostrarForm.value = true
}

function alGuardar() {
  mostrarForm.value = false
  cargar()
  notifySuccess('Evento guardado.')
}

function cancelar() {
  $q.dialog({
    title: '¿Cancelar este evento?',
    message: `"${evento.value.titulo}" dejará de verse en la agenda pública.`,
    cancel: { label: 'Volver', flat: true, noCaps: true },
    ok: { label: 'Sí, cancelar evento', color: 'orange-9', noCaps: true },
    persistent: true,
  }).onOk(async () => {
    try {
      await EventoService.cancelar(evento.value.id)
      cargar()
      notifySuccess('Evento cancelado.')
    } catch (error) {
      notifyError(error.response?.data?.message || 'No se pudo cancelar el evento.')
    }
  })
}

function eliminar() {
  $q.dialog({
    title: '¿Eliminar este evento?',
    message: `Se borrará "${evento.value.titulo}" y su imagen. Este proceso es irreversible.`,
    cancel: { label: 'Volver', flat: true, noCaps: true },
    ok: { label: 'Eliminar', color: 'negative', noCaps: true },
    persistent: true,
  }).onOk(async () => {
    try {
      await EventoService.delete(evento.value.id)
      notifySuccess('Evento eliminado.')
      router.push({ name: 'EventosAdmin' })
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
  padding: 20px clamp(16px, 3vw, 40px) 48px;
}

.volver {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 16px;
  color: var(--azul);
  font-weight: 600;
  text-decoration: none;
}

.volver:hover {
  text-decoration: underline;
}

.grilla {
  display: grid;
  grid-template-columns: 340px minmax(0, 1fr);
  gap: 32px;
  align-items: start;
}

/* ---------- lateral ---------- */
.lateral {
  position: sticky;
  top: 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.afiche {
  position: relative;
  display: block;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid var(--borde);
  background: rgba(128, 128, 128, 0.12);
}

.afiche img {
  display: block;
  width: 100%;
  max-height: 480px;
  object-fit: contain;
}

.afiche-zoom {
  position: absolute;
  right: 10px;
  bottom: 10px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.65);
  color: white;
  font-size: 0.78rem;
  opacity: 0;
  transition: opacity 0.15s;
}

.afiche:hover .afiche-zoom,
.afiche:focus-visible .afiche-zoom {
  opacity: 1;
}

.afiche--vacio {
  aspect-ratio: 3 / 4;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  opacity: 0.6;
}

.decision {
  padding: 16px;
  border-radius: 14px;
  border: 1px solid var(--borde);
  border-top: 4px solid var(--estado-revision);
  background: var(--superficie);
}

.decision--aprobado {
  border-top-color: var(--estado-publicado);
}

.decision--observado {
  border-top-color: var(--estado-observado);
}

.decision--cancelado {
  border-top-color: var(--estado-borrador);
}

.decision-titulo {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 800;
  font-size: 1.05rem;
}

.decision-texto {
  margin: 6px 0 14px;
  font-size: 0.88rem;
  opacity: 0.72;
}

.observar {
  margin-top: 4px;
}

.observar-label {
  display: block;
  margin-bottom: 6px;
  font-weight: 700;
  font-size: 0.9rem;
}

.observar-acciones {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 8px;
}

/* ---------- contenido ---------- */
.encabezado {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.etiquetas {
  display: flex;
  align-items: center;
  gap: 10px;
}

.chip {
  font-weight: 700;
}

.tipo {
  font-size: 0.88rem;
  opacity: 0.7;
}

.encabezado-acciones {
  display: flex;
  align-items: center;
  gap: 6px;
}

.opcion-icono {
  min-width: 28px;
}

.titulo {
  margin: 12px 0 16px;
  font-size: clamp(1.4rem, 2.4vw, 2rem);
  line-height: 1.2;
  font-weight: 800;
  letter-spacing: -0.01em;
  overflow-wrap: anywhere;
}

.aviso-observado {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
  padding: 10px 14px;
  border-radius: 10px;
  background: var(--estado-observado-suave);
  color: inherit;
}

.aviso-observado svg {
  flex-shrink: 0;
  color: var(--estado-observado-texto);
}

.datos {
  display: grid;
  /* columnas fijas: con auto-fit sobraba una celda vacía (gris) */
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1px;
  margin: 0;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid var(--borde);
  background: var(--borde);
}

.dato {
  padding: 14px 16px;
  background: var(--superficie);
}

.dato--ancho {
  grid-column: 1 / -1;
}

.dato dt {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.6;
}

.dato dd {
  margin: 6px 0 0;
  font-weight: 600;
  overflow-wrap: anywhere;
}

.secundario {
  font-weight: 400;
  opacity: 0.68;
}

.enlace {
  color: var(--azul);
  font-weight: 500;
}

.seccion {
  margin-top: 32px;
}

.seccion-titulo {
  /* los h2 heredan de Quasar line-height: 3.75rem */
  line-height: 1.3;
  margin: 0 0 10px;
  font-size: 0.8rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  opacity: 0.6;
}

.organizador {
  display: flex;
  align-items: center;
  gap: 12px;
  max-width: 520px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid var(--borde);
  background: var(--superficie);
  color: inherit;
  text-decoration: none;
}

.organizador:hover {
  border-color: var(--azul);
}

.organizador-avatar {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  color: var(--azul);
  background: var(--panel-acento-suave);
  flex-shrink: 0;
}

.organizador-texto {
  display: flex;
  flex-direction: column;
  min-width: 0;
  flex: 1;
  font-size: 0.85rem;
}

.organizador-nombre {
  font-weight: 700;
  font-size: 0.95rem;
}

.organizador-flecha {
  opacity: 0.5;
}

.descripcion {
  max-width: 72ch;
  line-height: 1.65;
  overflow-wrap: anywhere;
}

.descripcion :deep(p) {
  margin: 0 0 0.8em;
}

.descripcion :deep(a) {
  color: var(--azul);
}

.historial {
  margin: 0;
  padding: 0;
  list-style: none;
}

.hito {
  position: relative;
  display: flex;
  gap: 12px;
  padding-bottom: 14px;
}

/* línea vertical entre hitos */
.hito:not(:last-child)::before {
  content: '';
  position: absolute;
  left: 5px;
  top: 16px;
  bottom: 0;
  width: 2px;
  background: var(--borde);
}

.hito-punto {
  flex-shrink: 0;
  width: 12px;
  height: 12px;
  margin-top: 4px;
  border-radius: 50%;
  background: var(--estado-revision);
}

.hito--aprobado .hito-punto {
  background: var(--estado-publicado);
}

.hito--observado .hito-punto {
  background: var(--estado-observado);
}

.hito-titulo {
  font-weight: 700;
  font-size: 0.92rem;
}

.hito-texto {
  margin-top: 2px;
  font-size: 0.9rem;
  opacity: 0.8;
}

.vacio {
  text-align: center;
  padding: 56px 24px;
  border: 1px dashed var(--borde);
  border-radius: 16px;
}

@media (max-width: 1399px) {
  .datos {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 599px) {
  .datos {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 1023px) {
  .grilla {
    grid-template-columns: 1fr;
  }

  /* en una columna: afiche chico arriba y la decisión después del contenido */
  .lateral {
    position: static;
    display: contents;
  }

  .afiche {
    max-width: 320px;
  }

  .decision {
    order: 3;
  }
}
</style>
