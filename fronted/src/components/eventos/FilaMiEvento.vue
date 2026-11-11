<template>
  <article class="fila" :class="[`fila--${evento.estado}`]">
    <FechaEvento :evento="evento" />

    <!-- afiche en proporción vertical (los afiches casi siempre lo son) -->
    <div class="afiche">
      <img
        v-if="evento.imagen_url"
        :src="evento.imagen_url"
        :alt="`Afiche de ${evento.titulo}`"
        loading="lazy"
      />
      <ImageOff v-else :size="22" class="afiche-vacio" />
    </div>

    <div class="info">
      <h3 class="info-titulo">{{ evento.titulo }}</h3>
      <div class="info-meta">
        <span><Clock :size="14" /> {{ cuando.horario }}</span>
        <span v-if="evento.modalidad === 'virtual'"><Video :size="14" /> Virtual</span>
        <span v-else><MapPin :size="14" /> {{ evento.lugar }}</span>
      </div>
      <div class="info-meta">
        <span>
          <UsersRound :size="14" />
          {{ evento.organizador_tipo === 'agrupacion' ? evento.agrupacion?.nombre : 'A tu nombre' }}
        </span>
        <span v-if="evento.comision?.nombre"><Tag :size="14" /> {{ evento.comision.nombre }}</span>
      </div>
    </div>

    <!-- seguimiento: dónde está la revisión y cuándo pasó cada cosa -->
    <div v-if="evento.estado === 'cancelado'" class="cancelado">
      <Ban :size="16" /> Cancelado · no se muestra en la agenda
    </div>
    <ol v-else class="pasos" :aria-label="`Estado: ${estadoEvento(evento.estado).label}`">
      <li v-for="p in pasos" :key="p.clave" class="paso" :class="`paso--${p.estado}`">
        <span class="paso-punto">
          <Check v-if="p.estado === 'hecho'" :size="12" :stroke-width="3" />
          <span v-else-if="p.estado === 'alerta'" class="paso-exclamacion">!</span>
        </span>
        <span class="paso-label">{{ p.label }}</span>
        <span v-if="p.fecha" class="paso-fecha">{{ p.fecha }}</span>
      </li>
    </ol>

    <div class="acciones">
      <q-btn
        v-if="evento.estado === 'observado'"
        unelevated
        no-caps
        color="orange-9"
        class="accion"
        @click="emit('editar', evento)"
      >
        <Pencil :size="15" class="q-mr-xs" /> Corregir
      </q-btn>
      <q-btn
        v-else-if="evento.estado === 'aprobado'"
        outline
        no-caps
        color="primary"
        class="accion"
        :to="{ name: 'EventoPublico', params: { evento: String(evento.id) } }"
        target="_blank"
      >
        <ExternalLink :size="15" class="q-mr-xs" /> Ver en la agenda
      </q-btn>
      <q-btn
        v-else-if="evento.estado === 'pendiente'"
        outline
        no-caps
        color="primary"
        class="accion"
        @click="emit('editar', evento)"
      >
        <Pencil :size="15" class="q-mr-xs" /> Editar
      </q-btn>

      <q-btn v-if="opciones.length" flat round dense class="mas" aria-label="Más opciones">
        <EllipsisVertical :size="18" />
        <q-menu anchor="bottom right" self="top right">
          <q-list dense style="min-width: 190px">
            <q-item
              v-for="o in opciones"
              :key="o.clave"
              v-close-popup
              clickable
              :class="o.clase"
              @click="emit(o.clave, evento)"
            >
              <q-item-section avatar class="opcion-icono"
                ><component :is="o.icono" :size="16"
              /></q-item-section>
              <q-item-section>{{ o.label }}</q-item-section>
            </q-item>
          </q-list>
        </q-menu>
      </q-btn>
    </div>

    <!-- el motivo de la observación, a lo ancho: es lo más importante de la fila -->
    <div v-if="evento.estado === 'observado' && evento.observacion" class="observacion">
      <MessageSquareWarning :size="18" class="observacion-icono" />
      <div>
        <div class="observacion-titulo">El administrador pidió corregir:</div>
        <div class="observacion-texto">{{ evento.observacion }}</div>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import {
  Clock,
  MapPin,
  Video,
  UsersRound,
  Tag,
  Check,
  Ban,
  Pencil,
  ExternalLink,
  EllipsisVertical,
  CalendarX,
  Trash2,
  MessageSquareWarning,
  ImageOff,
} from 'lucide-vue-next'
import FechaEvento from '@/components/eventos/FechaEvento.vue'
import { estadoEvento } from '@/config/estadosEvento'
import { cuandoTexto, marcaTiempo as marca } from '@/utils/fechasAgenda'

const props = defineProps({
  evento: { type: Object, required: true },
})
const emit = defineEmits(['editar', 'cancelar', 'eliminar'])

const cuando = computed(() => cuandoTexto(props.evento))

// Enviado → Revisión → Publicado. Un evento editado vuelve a "pendiente",
// por eso el envío se fecha con updated_at mientras espera revisión.
const pasos = computed(() => {
  const e = props.evento
  const enviado = {
    clave: 'enviado',
    label: 'Enviado',
    estado: 'hecho',
    fecha: marca(e.estado === 'pendiente' ? e.updated_at : e.created_at),
  }
  const revision = {
    pendiente: { clave: 'revision', label: 'En revisión', estado: 'actual', fecha: null },
    observado: {
      clave: 'revision',
      label: 'Observado',
      estado: 'alerta',
      fecha: marca(e.revisado_en),
    },
    aprobado: {
      clave: 'revision',
      label: 'Revisado',
      estado: 'hecho',
      fecha: marca(e.revisado_en),
    },
  }[e.estado]
  const publicado = {
    clave: 'publicado',
    label: 'Publicado',
    estado: e.estado === 'aprobado' ? 'hecho' : 'espera',
    fecha: null,
  }
  return [enviado, revision, publicado]
})

const opciones = computed(() => {
  const e = props.evento
  const lista = []
  // "Editar" ya es el botón principal cuando está pendiente u observado
  if (e.estado === 'aprobado')
    lista.push({ clave: 'editar', label: 'Editar (vuelve a revisión)', icono: Pencil })
  if (e.estado !== 'cancelado')
    lista.push({
      clave: 'cancelar',
      label: 'Cancelar evento',
      icono: CalendarX,
      clase: 'text-orange-9',
    })
  if (e.estado !== 'aprobado')
    lista.push({ clave: 'eliminar', label: 'Eliminar', icono: Trash2, clase: 'text-negative' })
  return lista
})
</script>

<style scoped>
.fila {
  --azul: var(--panel-acento);
  display: grid;
  grid-template-columns: 56px 72px minmax(0, 1fr) 300px 216px;
  align-items: center;
  column-gap: 18px;
  row-gap: 12px;
  padding: 16px 18px;
}

.fila + .fila {
  border-top: 1px solid rgba(128, 128, 128, 0.18);
}

.fila--observado {
  box-shadow: inset 4px 0 0 var(--estado-observado);
}

/* ---------- afiche ---------- */
.afiche {
  width: 72px;
  aspect-ratio: 3 / 4;
  border-radius: 8px;
  overflow: hidden;
  display: grid;
  place-items: center;
  background: rgba(128, 128, 128, 0.12);
}

.afiche img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: top;
  display: block;
}

.afiche-vacio {
  opacity: 0.4;
}

/* ---------- info ---------- */
.info-titulo {
  margin: 0 0 6px;
  font-size: 1rem;
  font-weight: 700;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  overflow-wrap: anywhere;
}

.info-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 16px;
  font-size: 0.85rem;
  opacity: 0.72;
}

.info-meta + .info-meta {
  margin-top: 2px;
}

.info-meta span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  min-width: 0;
}

/* ---------- seguimiento ---------- */
.pasos {
  --hecho: var(--estado-publicado);
  --actual: var(--estado-revision);
  --alerta: var(--estado-observado);
  --linea: rgba(128, 128, 128, 0.3);
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  margin: 0;
  padding: 0;
  list-style: none;
}

.paso {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 4px;
  font-size: 0.75rem;
}

/* línea que une con el paso anterior; se pinta si este paso ya se alcanzó */
.paso + .paso::before {
  content: '';
  position: absolute;
  top: 10px;
  right: calc(50% + 14px);
  width: calc(100% - 28px);
  height: 2px;
  border-radius: 2px;
  background: var(--linea);
}

.paso--hecho::before {
  background: var(--hecho) !important;
}

.paso--actual::before {
  background: var(--actual) !important;
}

.paso--alerta::before {
  background: var(--alerta) !important;
}

.paso-punto {
  display: grid;
  place-items: center;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid var(--linea);
  color: white;
  font-weight: 800;
}

.paso--hecho .paso-punto {
  background: var(--hecho);
  border-color: var(--hecho);
}

.paso--alerta .paso-punto {
  background: var(--alerta);
  border-color: var(--alerta);
}

/* el paso en curso "late" para que se entienda que está esperando */
.paso--actual .paso-punto {
  border-color: var(--actual);
  background: radial-gradient(circle, var(--actual) 0 4px, transparent 5px);
  animation: latido 1.8s ease-out infinite;
}

.paso-exclamacion {
  font-size: 0.8rem;
  line-height: 1;
}

.paso-label {
  font-weight: 700;
}

.paso--espera .paso-label {
  opacity: 0.5;
}

.paso--actual .paso-label {
  color: var(--estado-revision-texto);
}

.paso--alerta .paso-label {
  color: var(--estado-observado-texto);
}

.paso-fecha {
  opacity: 0.6;
  font-size: 0.7rem;
  white-space: nowrap;
}

@keyframes latido {
  0% {
    box-shadow: 0 0 0 0 color-mix(in srgb, var(--estado-revision) 45%, transparent);
  }
  70%,
  100% {
    box-shadow: 0 0 0 8px color-mix(in srgb, var(--estado-revision) 0%, transparent);
  }
}

@media (prefers-reduced-motion: reduce) {
  .paso--actual .paso-punto {
    animation: none;
  }
}

.cancelado {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 0.85rem;
  opacity: 0.65;
}

/* ---------- acciones ---------- */
.acciones {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 4px;
}

.accion {
  border-radius: 8px;
  padding: 4px 12px;
}

/* ícono y texto en una sola línea (si no, q-btn los apila) */
.accion :deep(.q-btn__content) {
  flex-wrap: nowrap;
  white-space: nowrap;
}

.mas {
  opacity: 0.7;
}

.opcion-icono {
  min-width: 28px;
}

/* ---------- observación ---------- */
.observacion {
  grid-column: 3 / -1;
  display: flex;
  gap: 10px;
  padding: 10px 14px;
  border-radius: 10px;
  background: var(--estado-observado-suave);
  font-size: 0.9rem;
  overflow-wrap: anywhere;
}

.observacion-icono {
  flex-shrink: 0;
  color: var(--estado-observado-texto);
  margin-top: 1px;
}

.observacion-titulo {
  font-weight: 700;
  color: var(--estado-observado-texto);
}

/* ---------- pantallas medianas y celular ---------- */
@media (max-width: 1100px) {
  .fila {
    grid-template-columns: 56px 72px minmax(0, 1fr);
  }

  .pasos,
  .cancelado {
    grid-column: 3 / -1;
    justify-self: stretch;
    max-width: 360px;
  }

  .acciones {
    grid-column: 1 / -1;
    justify-content: flex-end;
    order: 2;
  }

  /* primero se lee qué hay que corregir, después el botón "Corregir" */
  .observacion {
    order: 1;
  }
}

@media (max-width: 599px) {
  .fila {
    grid-template-columns: 44px 56px minmax(0, 1fr);
    column-gap: 12px;
    padding: 14px;
  }

  .afiche {
    width: 56px;
  }

  .pasos,
  .cancelado,
  .observacion {
    grid-column: 1 / -1;
    max-width: none;
  }
}
</style>
