<template>
  <div class="calendario" role="grid" :aria-label="tituloMes(`${mes}-01`)">
    <div
      v-for="(d, i) in DIAS_SEMANA"
      :key="d"
      class="dia-semana"
      :class="{ finde: i >= 5 }"
      role="columnheader"
    >
      {{ d }}
    </div>

    <template v-for="c in celdas" :key="c.fecha">
      <!-- días del mes anterior/siguiente: completan la semana, no se eligen -->
      <span v-if="!c.delMes" class="celda fuera" aria-hidden="true">
        <span class="numero">{{ c.numero }}</span>
      </span>

      <button
        v-else
        class="celda"
        role="gridcell"
        :class="{
          hoy: c.fecha === hoyTexto,
          elegido: c.fecha === diaElegido,
          pasado: c.fecha < hoyTexto,
          finde: c.finde,
          'con-eventos': c.eventos.length,
          'con-afiche': !!c.afiche,
        }"
        :style="estiloCelda(c)"
        :aria-label="`${tituloDia(c.fecha)}: ${c.eventos.length} ${c.eventos.length === 1 ? 'evento' : 'eventos'}`"
        :aria-selected="c.fecha === diaElegido"
        @click="diaElegido = c.fecha"
      >
        <span class="cabeza">
          <span class="numero">{{ c.numero }}</span>
          <span v-if="c.fecha === hoyTexto" class="marca-hoy">Hoy</span>
        </span>

        <span v-if="c.eventos.length" class="eventos">
          <span
            v-for="e in c.eventos.slice(0, 2)"
            :key="e.id"
            class="chip"
            :style="{ '--acento': comisionDe(e.cod_grupo).color }"
          >
            <b>{{ horaEnDia(e, c.fecha) }}</b> {{ e.titulo }}
          </span>
          <span v-if="c.eventos.length > 2" class="mas">+{{ c.eventos.length - 2 }} más</span>
        </span>

        <!-- en celular las celdas son chicas: solo puntos -->
        <span v-if="c.eventos.length" class="puntos" aria-hidden="true">
          <span
            v-for="e in c.eventos.slice(0, 3)"
            :key="e.id"
            class="punto"
            :style="{ background: comisionDe(e.cod_grupo).color }"
          />
        </span>
      </button>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { comisionDe } from '@/config/institucion'
import { aFecha, desdeTexto, horaEnDia, hoy, ocurreEl, tituloDia, tituloMes } from '@/utils/fechasAgenda'

const props = defineProps({
  mes: { type: String, required: true }, // "YYYY-MM"
  eventos: { type: Array, default: () => [] },
})
const diaElegido = defineModel('diaElegido', { type: String, required: true })

const DIAS_SEMANA = ['lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom']
const hoyTexto = hoy()

// semanas completas (lunes a domingo): se rellenan con días del mes vecino
const celdas = computed(() => {
  const primero = desdeTexto(`${props.mes}-01`)
  const diasMes = new Date(primero.getFullYear(), primero.getMonth() + 1, 0).getDate()
  const huecos = (primero.getDay() + 6) % 7 // getDay(): 0 = domingo
  const total = Math.ceil((huecos + diasMes) / 7) * 7

  return Array.from({ length: total }, (_, i) => {
    const d = new Date(primero.getFullYear(), primero.getMonth(), 1 - huecos + i)
    const fecha = aFecha(d)
    const delMes = fecha.startsWith(props.mes)
    const eventos = delMes ? props.eventos.filter((e) => ocurreEl(e, fecha)) : []
    return {
      fecha,
      numero: d.getDate(),
      delMes,
      finde: i % 7 >= 5,
      eventos,
      afiche: eventos.find((e) => e.imagen_miniatura_url)?.imagen_miniatura_url ?? null,
    }
  })
})

const estiloCelda = (c) => ({
  '--acento': c.eventos.length ? comisionDe(c.eventos[0].cod_grupo).color : undefined,
  '--afiche': c.afiche ? `url('${c.afiche}')` : undefined,
})
</script>

<style scoped lang="scss">
.calendario {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 6px;
}

.dia-semana {
  padding: 4px 0 8px;
  color: var(--tinta-suave);
  font-size: var(--t-xs);
  font-weight: 800;
  letter-spacing: 0.08em;
  text-align: center;
  text-transform: uppercase;

  &.finde {
    color: var(--rojo);
  }
}

.celda {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-height: 118px;
  padding: 8px;
  overflow: hidden;
  border: 1px solid var(--borde);
  border-radius: var(--r-md);
  background: var(--blanco);
  color: var(--tinta);
  font: inherit;
  text-align: left;
  cursor: pointer;
  isolation: isolate;
  transition:
    border-color var(--transicion),
    box-shadow var(--transicion),
    transform var(--transicion);

  &:hover:not(.fuera) {
    border-color: var(--borde-fuerte);
    box-shadow: var(--sombra);
    transform: translateY(-2px);
  }

  &:focus-visible {
    @include foco;
  }

  /* fin de semana: tono cálido, cuando no hay afiche que lo tape */
  &.finde:not(.con-afiche) {
    background: color-mix(in srgb, var(--crema) 55%, var(--blanco));
  }

  /* días ya pasados: atenuados */
  &.pasado {
    .numero {
      color: var(--tinta-tenue);
    }

    &.con-afiche::before {
      filter: grayscale(0.7);
    }
  }

  /* con eventos pero sin afiche: el color de la disciplina */
  &.con-eventos:not(.con-afiche) {
    border-color: color-mix(in srgb, var(--acento) 35%, var(--borde));
    background: color-mix(in srgb, var(--acento) 9%, var(--blanco));
  }

  /* con afiche: la imagen de fondo, oscurecida para que se lea el texto */
  &.con-afiche {
    border-color: transparent;
    color: #fff;

    &::before,
    &::after {
      content: '';
      position: absolute;
      inset: 0;
      z-index: -1;
    }

    &::before {
      background: var(--afiche) center / cover no-repeat;
      transition: transform 0.5s ease;
    }

    &::after {
      background: linear-gradient(180deg, rgba(22, 32, 46, 0.25) 0%, rgba(22, 32, 46, 0.88) 100%);
    }

    &:hover::before {
      transform: scale(1.07);
    }

    /* sobre la imagen el número necesita fondo propio para leerse */
    .numero {
      background: rgba(22, 32, 46, 0.72);
      color: #fff;
      backdrop-filter: blur(4px);
    }
  }

  &.hoy {
    border-color: var(--rojo);
    box-shadow: inset 0 0 0 1px var(--rojo);

    .numero {
      background: var(--rojo);
      color: #fff;
    }
  }

  &.elegido {
    border-color: var(--tinta);
    box-shadow:
      0 0 0 2px var(--papel),
      0 0 0 4px var(--tinta);
  }

  /* días del mes vecino */
  &.fuera {
    background: transparent;
    border-style: dashed;
    cursor: default;

    .numero {
      color: var(--tinta-tenue);
      font-weight: 500;
    }
  }
}

.cabeza {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 4px;
}

.numero {
  display: inline-grid;
  place-items: center;
  width: 28px;
  height: 28px;
  border-radius: var(--r-full);
  font-size: var(--t-sm);
  font-weight: 800;
}

.marca-hoy {
  padding: 2px 7px;
  border-radius: var(--r-full);
  background: var(--rojo);
  color: #fff;
  font-size: 0.6rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.eventos {
  display: grid;
  gap: 4px;
  min-width: 0;
  margin-top: auto; /* abajo de la celda: arriba se ve el afiche */
}

/* hasta 2 renglones: un título largo se lee, no queda "PUNO IMPU…" */
.chip {
  display: -webkit-box;
  overflow: hidden;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  padding: 3px 7px;
  border-left: 3px solid var(--acento);
  border-radius: 4px;
  background: color-mix(in srgb, var(--acento) 14%, var(--blanco));
  color: var(--tinta);
  font-size: 0.7rem;
  line-height: 1.3;
  overflow-wrap: anywhere;

  b {
    font-weight: 800;
  }
}

.con-afiche .chip {
  background: rgba(255, 255, 255, 0.16);
  color: #fff;
  backdrop-filter: blur(4px);
}

.mas {
  font-size: 0.7rem;
  font-weight: 700;
  opacity: 0.85;
}

.puntos {
  display: none;
  gap: 3px;
  justify-content: center;
}

.punto {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

/* ---------- celular: celdas chicas, puntos en vez de chips ---------- */
@media (max-width: 700px) {
  .calendario {
    gap: 4px;
  }

  .celda {
    align-items: center;
    min-height: 58px;
    padding: 5px 2px;
    border-radius: var(--r-sm);

    /* el afiche en una celda de 50px no se entiende: el color de la disciplina */
    &.con-afiche {
      border-color: color-mix(in srgb, var(--acento) 35%, var(--borde));
      background: color-mix(in srgb, var(--acento) 12%, var(--blanco));
      color: var(--tinta);

      &::before,
      &::after {
        display: none;
      }

      .numero {
        color: var(--tinta);
      }
    }
  }

  .cabeza {
    justify-content: center;
  }

  .marca-hoy,
  .eventos {
    display: none;
  }

  .puntos {
    display: flex;
    margin-top: auto;
  }
}
</style>
