<template>
  <aside class="panel" aria-live="polite">
    <header class="panel-cabeza">
      <p class="dia-semana">{{ hojaDia.diaSemana }}</p>
      <div class="dia-fecha">
        <span class="dia-numero">{{ hojaDia.dia }}</span>
        <span class="dia-mes">{{ hojaDia.mesLargo }}</span>
      </div>
      <p class="conteo">
        {{ eventos.length ? `${eventos.length} ${eventos.length === 1 ? 'evento' : 'eventos'}` : 'Sin eventos' }}
        <template v-if="fecha === hoyTexto"> · hoy</template>
      </p>
    </header>

    <div v-if="eventos.length" class="cards">
      <router-link
        v-for="e in eventos"
        :key="e.id"
        :to="{ name: 'EventoPublico', params: { evento: e.ruta } }"
        class="card"
        :style="{ '--acento': comisionDe(e.cod_grupo).color }"
      >
        <span class="card-afiche" :style="e.imagen_url ? { '--fondo': `url('${e.imagen_url}')` } : null">
          <!-- el afiche entero (contain) sobre su versión desenfocada -->
          <img v-if="e.imagen_url" :src="e.imagen_url" alt="" loading="lazy" />
          <span class="card-hora">{{ horaEnDia(e, fecha) }}</span>
        </span>
        <span class="card-cuerpo">
          <span v-if="e.comision" class="card-disciplina">{{ comisionDe(e.cod_grupo).corto }}</span>
          <span class="card-titulo">{{ e.titulo }}</span>
          <span class="card-meta">
            <MapPin :size="13" />
            {{ e.modalidad === 'virtual' ? 'Virtual' : e.lugar }} ·
            {{ e.flag_gratuito ? 'Ingreso libre' : `S/ ${e.costo}` }}
          </span>
        </span>
      </router-link>
    </div>

    <div v-else class="vacio">
      <CalendarX :size="28" />
      <p>No hay eventos este día.</p>
      <!-- en vez de un callejón sin salida: el próximo evento del mes -->
      <router-link
        v-if="siguiente"
        :to="{ name: 'EventoPublico', params: { evento: siguiente.ruta } }"
        class="siguiente"
        :style="{ '--acento': comisionDe(siguiente.cod_grupo).color }"
      >
        <span class="siguiente-label">Próximo evento</span>
        <span class="siguiente-fecha">{{ cuandoTexto(siguiente).fecha }}</span>
        <span class="siguiente-titulo">{{ siguiente.titulo }}</span>
        <span class="siguiente-ver">Ver evento <ArrowRight :size="14" /></span>
      </router-link>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { ArrowRight, CalendarX, MapPin } from 'lucide-vue-next'
import { comisionDe } from '@/config/institucion'
import { cuandoTexto, desdeTexto, horaEnDia, hoy } from '@/utils/fechasAgenda'

const props = defineProps({
  fecha: { type: String, required: true }, // "YYYY-MM-DD"
  eventos: { type: Array, default: () => [] },
  // primer evento después de este día (para no dejar el panel vacío)
  siguiente: { type: Object, default: null },
})

const hoyTexto = hoy()

const hojaDia = computed(() => {
  const d = desdeTexto(props.fecha)
  const fmt = (opciones) => new Intl.DateTimeFormat('es-PE', opciones).format(d)
  const diaSemana = fmt({ weekday: 'long' })
  return {
    diaSemana: diaSemana.charAt(0).toUpperCase() + diaSemana.slice(1),
    dia: d.getDate(),
    mesLargo: fmt({ month: 'long', year: 'numeric' }),
  }
})
</script>

<style scoped lang="scss">
.panel {
  overflow: hidden;
  border: 1px solid var(--borde);
  border-radius: var(--r-lg);
  background: var(--blanco);
}

.panel-cabeza {
  padding: 22px 22px 18px;
  background: var(--noche);
  color: var(--sobre-foto);
}

.dia-semana {
  @include kicker(var(--oro-vivo));
  margin: 0 0 6px;
}

.dia-fecha {
  display: flex;
  align-items: baseline;
  gap: 10px;
}

.dia-numero {
  @include display(800);
  font-size: 3.2rem;
  line-height: 1;
}

.dia-mes {
  @include display(700);
  font-size: var(--t-md);
  opacity: 0.85;
}

.conteo {
  margin: 8px 0 0;
  font-size: var(--t-sm);
  opacity: 0.7;
}

.cards {
  display: grid;
  gap: 14px;
  padding: 16px;
}

.card {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid var(--borde);
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

.card-afiche {
  position: relative;
  display: block;
  aspect-ratio: 16 / 10;
  overflow: hidden;
  background: linear-gradient(135deg, var(--noche), color-mix(in srgb, var(--acento) 70%, var(--noche)));

  &::before {
    content: '';
    position: absolute;
    inset: -24px;
    background: var(--fondo, none) center / cover no-repeat;
    filter: blur(22px) brightness(0.5);
  }

  /* absoluto + contain: entra siempre entero */
  img {
    position: absolute;
    inset: 10px;
    width: calc(100% - 20px);
    height: calc(100% - 20px);
    object-fit: contain;
  }
}

.card-hora {
  position: absolute;
  top: 10px;
  left: 10px;
  padding: 4px 10px;
  border-radius: var(--r-full);
  background: var(--blanco);
  box-shadow: var(--sombra);
  color: var(--tinta);
  font-size: var(--t-sm);
  font-weight: 800;
}

.card-cuerpo {
  display: grid;
  gap: 4px;
  padding: 12px 14px 14px;
}

.card-disciplina {
  color: var(--acento);
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.card-titulo {
  display: -webkit-box;
  overflow: hidden;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  font-weight: 700;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.card-meta {
  display: flex;
  align-items: center;
  gap: 5px;
  color: var(--tinta-suave);
  font-size: var(--t-sm);

  svg {
    flex-shrink: 0;
    color: var(--acento);
  }
}

.vacio {
  display: grid;
  justify-items: center;
  gap: 8px;
  padding: 28px 20px 24px;
  color: var(--tinta-suave);
  text-align: center;

  p {
    margin: 0 0 6px;
  }
}

.siguiente {
  display: grid;
  gap: 3px;
  width: 100%;
  padding: 14px 16px;
  border: 1px solid var(--borde);
  border-left: 4px solid var(--acento);
  border-radius: var(--r-md);
  background: color-mix(in srgb, var(--acento) 6%, var(--blanco));
  color: var(--tinta);
  text-align: left;
  text-decoration: none;
  transition: box-shadow var(--transicion);

  &:hover {
    box-shadow: var(--sombra);
  }

  &:focus-visible {
    @include foco;
  }
}

.siguiente-label {
  color: var(--tinta-suave);
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.siguiente-fecha {
  color: var(--acento);
  font-size: var(--t-sm);
  font-weight: 800;
}

.siguiente-titulo {
  display: -webkit-box;
  overflow: hidden;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  font-weight: 700;
  line-height: 1.3;
}

.siguiente-ver {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: 4px;
  color: var(--rojo);
  font-size: var(--t-sm);
  font-weight: 800;
}
</style>
