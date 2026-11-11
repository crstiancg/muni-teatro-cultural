<template>
  <article class="fila">
    <FechaEvento :evento="evento" chica />

    <div class="afiche">
      <img v-if="evento.imagen_url" :src="evento.imagen_url" alt="" loading="lazy" />
      <ImageOff v-else :size="18" class="afiche-vacio" />
    </div>

    <div class="info">
      <router-link :to="rutaDetalle" class="info-titulo">{{ evento.titulo }}</router-link>
      <div class="info-meta">
        <span><Clock :size="14" /> {{ cuando.horario }}</span>
        <span v-if="evento.modalidad === 'virtual'"><Video :size="14" /> Virtual</span>
        <span v-else><MapPin :size="14" /> {{ evento.lugar }}</span>
        <span v-if="evento.comision?.nombre"><Tag :size="14" /> {{ evento.comision.nombre }}</span>
      </div>
    </div>

    <!-- desktop: cada uno en su columna; pantallas medianas: juntos debajo del título -->
    <div class="lado">
      <div class="organizador" :title="organizador.nombre">
        <component :is="organizador.icono" :size="15" class="organizador-icono" />
        <span class="ellipsis">{{ organizador.nombre }}</span>
      </div>
      <div class="estado">
        <q-chip
          dense
          square
          :color="estadoEvento(evento.estado).color"
          text-color="white"
          class="q-ma-none chip"
        >
          {{ estadoEvento(evento.estado).label }}
          <q-tooltip v-if="evento.observacion" max-width="320px">{{
            evento.observacion
          }}</q-tooltip>
        </q-chip>
      </div>
    </div>

    <div class="acciones">
      <q-btn
        :unelevated="evento.estado === 'pendiente'"
        :outline="evento.estado !== 'pendiente'"
        no-caps
        color="primary"
        class="accion"
        :to="rutaDetalle"
      >
        {{ evento.estado === 'pendiente' ? 'Revisar' : 'Ver detalle' }}
      </q-btn>
      <q-btn flat round dense class="mas" aria-label="Más opciones">
        <EllipsisVertical :size="18" />
        <q-menu anchor="bottom right" self="top right">
          <q-list dense style="min-width: 200px">
            <q-item v-close-popup clickable @click="emit('editar', evento)">
              <q-item-section avatar class="opcion-icono"><Pencil :size="16" /></q-item-section>
              <q-item-section>Editar</q-item-section>
            </q-item>
            <q-item
              v-if="evento.estado === 'aprobado'"
              v-close-popup
              clickable
              :to="{ name: 'EventoPublico', params: { evento: String(evento.id) } }"
              target="_blank"
            >
              <q-item-section avatar class="opcion-icono"
                ><ExternalLink :size="16"
              /></q-item-section>
              <q-item-section>Ver en la agenda</q-item-section>
            </q-item>
            <q-item
              v-if="evento.estado !== 'cancelado'"
              v-close-popup
              clickable
              class="text-orange-9"
              @click="emit('cancelar', evento)"
            >
              <q-item-section avatar class="opcion-icono"><CalendarX :size="16" /></q-item-section>
              <q-item-section>Cancelar evento</q-item-section>
            </q-item>
            <q-item v-close-popup clickable class="text-negative" @click="emit('eliminar', evento)">
              <q-item-section avatar class="opcion-icono"><Trash2 :size="16" /></q-item-section>
              <q-item-section>Eliminar</q-item-section>
            </q-item>
          </q-list>
        </q-menu>
      </q-btn>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import {
  Clock,
  MapPin,
  Video,
  Tag,
  Landmark,
  User,
  UsersRound,
  Pencil,
  ExternalLink,
  EllipsisVertical,
  CalendarX,
  Trash2,
  ImageOff,
} from 'lucide-vue-next'
import FechaEvento from '@/components/eventos/FechaEvento.vue'
import { estadoEvento } from '@/config/estadosEvento'
import { cuandoTexto } from '@/utils/fechasAgenda'

const props = defineProps({
  evento: { type: Object, required: true },
})
const emit = defineEmits(['editar', 'cancelar', 'eliminar'])

const cuando = computed(() => cuandoTexto(props.evento))
const rutaDetalle = computed(() => ({
  name: 'EventoAdminDetalle',
  params: { id: props.evento.id },
}))

const organizador = computed(() => {
  const e = props.evento
  if (e.organizador_tipo === 'persona')
    return { icono: User, nombre: e.persona?.nombre_completo ?? 'Artista' }
  if (e.organizador_tipo === 'agrupacion')
    return { icono: UsersRound, nombre: e.agrupacion?.nombre ?? 'Agrupación' }
  return { icono: Landmark, nombre: 'Municipalidad' }
})
</script>

<style scoped>
.fila {
  --azul: var(--panel-acento);
  display: grid;
  grid-template-columns: 48px 48px minmax(0, 1fr) 220px 120px 172px;
  align-items: center;
  column-gap: 16px;
  row-gap: 8px;
  padding: 12px 16px;
}

.fila + .fila {
  border-top: 1px solid rgba(128, 128, 128, 0.18);
}

.afiche {
  width: 48px;
  aspect-ratio: 3 / 4;
  border-radius: 6px;
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

.info-titulo {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  overflow-wrap: anywhere;
  font-weight: 700;
  line-height: 1.35;
  color: inherit;
  text-decoration: none;
}

.info-titulo:hover {
  color: var(--azul);
  text-decoration: underline;
}

.info-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 2px 14px;
  margin-top: 4px;
  font-size: 0.82rem;
  opacity: 0.72;
}

.info-meta span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  min-width: 0;
}

/* en desktop sus hijos son celdas de la grilla */
.lado {
  display: contents;
}

.organizador {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
  font-size: 0.88rem;
}

.organizador-icono {
  flex-shrink: 0;
  opacity: 0.6;
}

.chip {
  font-weight: 700;
}

.acciones {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 4px;
}

.accion {
  border-radius: 8px;
  padding: 2px 14px;
  min-width: 118px;
}

.mas {
  opacity: 0.7;
}

.opcion-icono {
  min-width: 28px;
}

/* pantallas medianas: organizador y estado debajo del título; acciones a la derecha */
@media (max-width: 1279px) {
  .fila {
    grid-template-columns: 48px 48px minmax(0, 1fr) auto;
  }

  .lado {
    display: flex;
    align-items: center;
    gap: 12px;
    grid-column: 3;
    min-width: 0;
  }

  /* .fecha es la raíz de FechaEvento: el scope del padre llega a la raíz del hijo */
  .fecha,
  .afiche {
    grid-row: span 2;
  }

  .acciones {
    grid-column: 4;
    grid-row: 1 / span 2;
  }
}

/* celular: acciones abajo a lo ancho */
@media (max-width: 599px) {
  .fila {
    grid-template-columns: 44px 44px minmax(0, 1fr);
    column-gap: 12px;
    padding: 12px;
  }

  .afiche {
    width: 44px;
  }

  .lado {
    flex-wrap: wrap;
    gap: 6px 12px;
  }

  .acciones {
    grid-column: 1 / -1;
    grid-row: auto;
  }
}
</style>
