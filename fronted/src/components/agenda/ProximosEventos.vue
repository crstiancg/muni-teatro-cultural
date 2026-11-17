<template>
  <!-- el más cercano en grande + los siguientes en lista: formato de agenda, no otra grilla -->
  <div class="proximos" :class="{ solo: !resto.length }">
    <router-link
      v-revelar
      :to="{ name: 'EventoPublico', params: { evento: principal.ruta } }"
      class="principal"
      :style="{ '--acento': comisionDe(principal.cod_grupo).color }"
    >
      <span
        class="afiche"
        :style="principal.imagen_url ? { '--fondo': `url('${principal.imagen_url}')` } : null"
      >
        <!-- el afiche ENTERO (contain): suele traer la información escrita -->
        <img v-if="principal.imagen_url" :src="principal.imagen_url" :alt="`Afiche: ${principal.titulo}`" loading="lazy" />
        <!-- solo si es urgente: un "Miércoles" suelto confunde cuando falta más de una semana -->
        <span v-if="urgente(principal)" class="urgente">{{ cercania(principal) }}</span>
      </span>

      <span class="principal-cuerpo">
        <span class="principal-meta">
          <span v-if="principal.comision" class="disciplina">{{ comisionDe(principal.cod_grupo).corto }}</span>
          <span v-if="principal.flag_destacado" class="destacado"><Star :size="12" /> Destacado</span>
        </span>

        <h3>{{ principal.titulo }}</h3>

        <span class="fecha">
          <span class="hoja" aria-hidden="true">
            <span class="hoja-mes">{{ hojaCalendario(principal).mes }}</span>
            <b>{{ hojaCalendario(principal).dia }}</b>
          </span>
          <span class="fecha-texto">
            <strong>{{ cuandoTexto(principal).fecha }}</strong>
            <span>{{ cuandoTexto(principal).horario }}</span>
          </span>
        </span>

        <span class="lugar">
          <MapPin :size="15" />
          {{ principal.modalidad === 'virtual' ? 'Evento virtual' : principal.lugar }}
          <template v-if="principal.flag_gratuito"> · Ingreso libre</template>
        </span>

        <span v-if="principal.descripcion_texto" class="resumen">{{ principal.descripcion_texto }}</span>

        <span class="ver">Ver evento <ArrowRight :size="16" /></span>
      </span>
    </router-link>

    <ol v-if="resto.length" class="lista">
      <li v-for="(e, i) in resto" :key="e.id" v-revelar="(i + 1) * 90">
        <router-link
          :to="{ name: 'EventoPublico', params: { evento: e.ruta } }"
          class="fila"
          :style="{ '--acento': comisionDe(e.cod_grupo).color }"
        >
          <span class="hoja" aria-hidden="true">
            <span class="hoja-mes">{{ hojaCalendario(e).mes }}</span>
            <b>{{ hojaCalendario(e).dia }}</b>
          </span>
          <span class="fila-cuerpo">
            <span class="fila-cuando">{{ cercania(e) }} · {{ hora(e.fecha_inicio) }}</span>
            <span class="fila-titulo">{{ e.titulo }}</span>
            <span class="fila-lugar">{{ e.modalidad === 'virtual' ? 'Virtual' : e.lugar }}</span>
          </span>
          <img v-if="e.imagen_miniatura_url" :src="e.imagen_miniatura_url" alt="" loading="lazy" class="fila-mini" />
        </router-link>
      </li>
    </ol>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ArrowRight, MapPin, Star } from 'lucide-vue-next'
import { comisionDe } from '@/config/institucion'
import { cercania, cuandoTexto, hojaCalendario, hora } from '@/utils/fechasAgenda'

const props = defineProps({
  // ya ordenados por fecha (GET publico/eventos/proximos); al menos uno
  eventos: { type: Array, required: true },
})

const principal = computed(() => props.eventos[0])
const resto = computed(() => props.eventos.slice(1))

const urgente = (e) => ['En curso', 'Hoy', 'Mañana'].includes(cercania(e))
</script>

<style scoped lang="scss">
.proximos {
  display: grid;
  grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
  gap: clamp(16px, 2.4vw, 28px);
  align-items: start;

  &.solo {
    grid-template-columns: minmax(0, 1fr);
  }
}

/* ---------- el más cercano ---------- */
.principal {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid var(--borde);
  border-radius: var(--r-lg);
  background: var(--blanco);
  color: inherit;
  text-decoration: none;
  transition:
    transform var(--transicion),
    box-shadow var(--transicion);

  &:hover {
    transform: translateY(-3px);
    box-shadow: var(--sombra-alta);
  }

  &:hover .ver {
    gap: 10px;
  }

  &:focus-visible {
    @include foco;
  }
}

/* un solo evento: horizontal, afiche con alto controlado y texto al lado */
.solo .principal {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr);
}

.afiche {
  position: relative;
  display: block;
  /* más cuadrado que 16:10: los afiches suelen ser cuadrados o verticales y
     en un marco tan apaisado quedaban encajonados con franjas negras */
  aspect-ratio: 4 / 3;
  overflow: hidden;
  /* sin afiche: el color de la disciplina */
  background: linear-gradient(135deg, var(--noche), color-mix(in srgb, var(--acento) 70%, var(--noche)));

  /* el mismo afiche, desenfocado, rellena los costados sin recortar el original */
  &::before {
    content: '';
    position: absolute;
    inset: -30px;
    background: var(--fondo, none) center / cover no-repeat;
    filter: blur(26px) brightness(0.75) saturate(1.1);
  }

  /* absoluto + contain: entra siempre entero, sin depender de alturas en % */
  img {
    position: absolute;
    inset: 16px;
    width: calc(100% - 32px);
    height: calc(100% - 32px);
    object-fit: contain;
    filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.4));
  }
}

.solo .afiche {
  aspect-ratio: auto;
  height: 100%;
  min-height: clamp(320px, 34vw, 460px);
}

.urgente {
  position: absolute;
  top: 14px;
  left: 14px;
  padding: 6px 14px;
  border-radius: var(--r-full);
  background: var(--rojo);
  color: #fff;
  font-size: var(--t-sm);
  font-weight: 800;
  box-shadow: var(--sombra);
}

.principal-cuerpo {
  display: grid;
  gap: 12px;
  padding: 24px 26px 26px;
  align-content: start;
}

.solo .principal-cuerpo {
  align-content: center;
  padding: clamp(28px, 3.5vw, 48px);
}

.principal-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;

  &:empty {
    display: none;
  }
}

.disciplina,
.destacado {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.disciplina {
  color: var(--acento);
}

.destacado {
  color: var(--oro);
}

h3 {
  @include display(800);
  display: -webkit-box;
  overflow: hidden;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  margin: 0;
  font-size: clamp(1.3rem, 1rem + 0.9vw, 1.75rem);
  line-height: 1.2;
  overflow-wrap: anywhere;
}

.fecha {
  display: flex;
  align-items: center;
  gap: 14px;
}

.fecha-texto {
  display: grid;
  gap: 2px;

  strong {
    font-size: var(--t-md);
  }

  span {
    color: var(--tinta-suave);
    font-size: var(--t-sm);
    font-weight: 600;
  }
}

.lugar {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--tinta-suave);
  font-size: var(--t-sm);

  svg {
    flex-shrink: 0;
    color: var(--acento);
  }
}

.resumen {
  display: -webkit-box;
  overflow: hidden;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  color: var(--tinta-suave);
  line-height: 1.6;
}

.ver {
  display: inline-flex;
  align-items: center;
  justify-self: start;
  gap: 6px;
  margin-top: 4px;
  color: var(--rojo);
  font-size: var(--t-sm);
  font-weight: 800;
  transition: gap var(--transicion);
}

/* ---------- los siguientes ---------- */
.lista {
  display: grid;
  gap: 12px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.fila {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px;
  border: 1px solid var(--borde);
  border-radius: var(--r-md);
  background: var(--blanco);
  color: inherit;
  text-decoration: none;
  transition:
    transform var(--transicion),
    box-shadow var(--transicion),
    border-color var(--transicion);

  &:hover {
    transform: translateX(4px);
    border-color: var(--acento);
    box-shadow: var(--sombra);
  }

  &:focus-visible {
    @include foco;
  }
}

.hoja {
  display: grid;
  flex-shrink: 0;
  width: 56px;
  overflow: hidden;
  border-radius: var(--r-sm);
  background: var(--blanco);
  box-shadow: var(--sombra);
  text-align: center;

  b {
    @include display(800);
    padding: 3px 0 5px;
    font-size: 1.5rem;
    line-height: 1.05;
  }
}

.hoja-mes {
  padding: 2px 0;
  background: var(--acento);
  color: var(--sobre-foto);
  font-size: 0.64rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.fila-cuerpo {
  display: grid;
  gap: 2px;
  flex: 1;
  min-width: 0;
}

.fila-cuando {
  color: var(--acento);
  font-size: var(--t-xs);
  font-weight: 800;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.fila-titulo {
  display: -webkit-box;
  overflow: hidden;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  font-weight: 700;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.fila-lugar {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  color: var(--tinta-suave);
  font-size: var(--t-sm);
}

.fila-mini {
  flex-shrink: 0;
  width: 64px;
  height: 64px;
  border-radius: var(--r-sm);
  object-fit: cover;
}

/* ---------- celular y tablet ---------- */
@media (max-width: 899px) {
  .proximos,
  .solo .principal {
    grid-template-columns: minmax(0, 1fr);
  }

  .afiche,
  .solo .afiche {
    height: auto;
    min-height: 0;
    aspect-ratio: 4 / 3;
  }

  .principal-cuerpo,
  .solo .principal-cuerpo {
    padding: 22px 20px 24px;
  }
}

@media (max-width: 480px) {
  .fila-mini {
    display: none;
  }
}
</style>
