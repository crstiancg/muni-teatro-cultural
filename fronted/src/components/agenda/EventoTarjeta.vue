<template>
  <router-link
    :to="{ name: 'EventoPublico', params: { evento: evento.ruta } }"
    class="tarjeta"
    :style="{ '--acento': comisionDe(evento.cod_grupo).color }"
  >
    <!-- miniatura del afiche; sin afiche, el color de la disciplina -->
    <span class="mini">
      <img v-if="evento.imagen_miniatura_url" :src="evento.imagen_miniatura_url" alt="" loading="lazy" />
    </span>
    <span class="cuerpo">
      <span class="hora">{{ horaVisible }}</span>
      <span class="titulo">{{ evento.titulo }}</span>
      <span class="meta">{{ meta }}</span>
    </span>
    <span v-if="evento.comision" class="etiqueta">{{ comisionDe(evento.cod_grupo).corto }}</span>
  </router-link>
</template>

<script setup>
import { computed } from 'vue'
import { comisionDe } from '@/config/institucion'
import { horaEnDia } from '@/utils/fechasAgenda'

const props = defineProps({
  evento: { type: Object, required: true },
  // día de la agenda en el que se muestra (YYYY-MM-DD): en los días siguientes
  // de un festival no se repite la hora de inicio
  dia: { type: String, default: null },
})

const horaVisible = computed(() => horaEnDia(props.evento, props.dia))

const meta = computed(() =>
  [
    props.evento.modalidad === 'virtual' ? 'Virtual' : props.evento.lugar,
    props.evento.flag_gratuito ? 'Ingreso libre' : `S/ ${props.evento.costo}`,
  ]
    .filter(Boolean)
    .join(' · '),
)
</script>

<style scoped lang="scss">
.tarjeta {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 10px 14px 10px 10px;
  border: 1px solid var(--borde);
  border-left: 4px solid var(--acento);
  border-radius: var(--r-md);
  background: var(--blanco);
  color: inherit;
  text-decoration: none;
  transition:
    transform var(--transicion),
    box-shadow var(--transicion);

  &:hover {
    transform: translateY(-2px);
    box-shadow: var(--sombra);
  }

  &:focus-visible {
    @include foco;
  }
}

.mini {
  flex-shrink: 0;
  width: 64px;
  height: 64px;
  overflow: hidden;
  border-radius: var(--r-sm);
  background: linear-gradient(135deg, var(--noche), color-mix(in srgb, var(--acento) 70%, var(--noche)));

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.cuerpo {
  display: grid;
  gap: 2px;
  flex: 1;
  min-width: 0;
}

.hora {
  color: var(--acento);
  font-size: var(--t-sm);
  font-weight: 800;
}

.titulo {
  display: -webkit-box;
  overflow: hidden;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  font-weight: 700;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.meta {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  font-size: var(--t-sm);
  color: var(--tinta-suave);
}

.etiqueta {
  flex-shrink: 0;
  padding: 3px 9px;
  border-radius: var(--r-full);
  background: var(--acento);
  color: var(--sobre-foto);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

@media (max-width: 560px) {
  .etiqueta {
    display: none;
  }
}
</style>
