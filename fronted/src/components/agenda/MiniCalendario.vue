<template>
  <!-- calendario chico de la agenda: se abre desde el nombre del mes -->
  <div class="mini" role="group" :aria-label="`Calendario de ${titulo}`">
    <div class="mini-cabeza">
      <button class="mini-flecha" aria-label="Mes anterior" @click="emit('mover', -1)">
        <ChevronLeft :size="18" />
      </button>
      <strong>{{ titulo }}</strong>
      <button class="mini-flecha" aria-label="Mes siguiente" @click="emit('mover', 1)">
        <ChevronRight :size="18" />
      </button>
    </div>

    <div class="mini-grilla">
      <span v-for="(l, i) in ['L', 'M', 'M', 'J', 'V', 'S', 'D']" :key="i" class="mini-sem" aria-hidden="true">{{ l }}</span>
      <span v-for="n in huecos" :key="`h${n}`" aria-hidden="true" />
      <button
        v-for="d in dias"
        :key="d.fecha"
        class="mini-dia"
        :class="{ con: d.colores.length, hoy: d.fecha === hoy, elegido: d.fecha === seleccionado, pasado: d.fecha < hoy }"
        :aria-disabled="!d.colores.length"
        :aria-pressed="d.fecha === seleccionado"
        :tabindex="d.colores.length ? 0 : -1"
        :aria-label="`${tituloDia(d.fecha)}: ${d.total || 'sin'} ${d.total === 1 ? 'evento' : 'eventos'}`"
        @click="d.colores.length && emit('elegir', d.fecha === seleccionado ? null : d.fecha)"
      >
        {{ d.numero }}
        <span class="mini-puntos" aria-hidden="true">
          <i v-for="c in d.colores" :key="c" :style="{ background: c }" />
        </span>
      </button>
    </div>

    <p class="mini-nota">Los días con punto tienen eventos.</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { comisionDe } from '@/config/institucion'
import { desdeTexto, ocurreEl, tituloDia, tituloMes } from '@/utils/fechasAgenda'

const props = defineProps({
  mes: { type: String, required: true }, // "2026-11"
  eventos: { type: Array, default: () => [] },
  seleccionado: { type: String, default: null },
  hoy: { type: String, required: true },
})
const emit = defineEmits(['elegir', 'mover'])

const titulo = computed(() => tituloMes(`${props.mes}-01`))

// la semana empieza en lunes
const huecos = computed(() => (desdeTexto(`${props.mes}-01`).getDay() + 6) % 7)

const dias = computed(() => {
  const inicio = desdeTexto(`${props.mes}-01`)
  const total = new Date(inicio.getFullYear(), inicio.getMonth() + 1, 0).getDate()
  return Array.from({ length: total }, (_, i) => {
    const fecha = `${props.mes}-${String(i + 1).padStart(2, '0')}`
    const delDia = props.eventos.filter((e) => ocurreEl(e, fecha))
    return {
      fecha,
      numero: i + 1,
      total: delDia.length,
      colores: [...new Set(delDia.map((e) => comisionDe(e.cod_grupo).color))].slice(0, 3),
    }
  })
})
</script>

<style scoped lang="scss">
.mini {
  width: 300px;
  max-width: 100%;
  padding: 16px;
  color: var(--tinta);
  font-family: var(--fuente-texto);
}

.mini-cabeza {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;

  strong {
    font-size: var(--t-base);
  }
}

.mini-flecha {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 50%;
  background: none;
  color: var(--tinta);
  cursor: pointer;

  &:hover {
    background: var(--crema);
  }
  &:focus-visible {
    @include foco;
  }
}

.mini-grilla {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 2px;
}

.mini-sem {
  padding-bottom: 6px;
  text-align: center;
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--tinta-tenue);
}

.mini-dia {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 38px;
  border: 0;
  border-radius: var(--r-sm);
  background: none;
  font: 500 var(--t-sm) var(--fuente-texto);
  font-variant-numeric: tabular-nums;
  color: var(--tinta-tenue);
  cursor: default;

  &.con {
    font-weight: 700;
    color: var(--tinta);
    cursor: pointer;

    &:hover {
      background: var(--crema);
    }
  }
  &.pasado {
    opacity: 0.5;
  }
  &.hoy {
    box-shadow: inset 0 0 0 1.5px var(--tinta);
  }
  &.elegido {
    background: var(--tinta);
    color: #fff;
    opacity: 1;

    &:hover {
      background: var(--tinta);
    }
  }
  &:focus-visible {
    @include foco;
  }
}

.mini-puntos {
  display: flex;
  gap: 2px;
  height: 4px;
  margin-top: 2px;

  i {
    width: 4px;
    height: 4px;
    border-radius: 50%;
  }
}

.mini-nota {
  margin: 12px 0 0;
  font-size: var(--t-xs);
  color: var(--tinta-suave);
}

/* en el panel de abajo del celular se aprovecha todo el ancho */
@media (max-width: 599px) {
  .mini {
    width: 100%;
    padding: 20px var(--gutter) 24px;
  }
  .mini-dia {
    height: 44px;
  }
}
</style>
