<template>
  <q-page class="agenda-page publico">
    <header class="cabecera">
      <div class="contenedor">
        <p class="kicker">Agenda cultural</p>
        <h1>Calendario</h1>
        <p class="bajada">
          Funciones, conciertos, ferias y talleres que se viven en {{ INSTITUCION.ciudad }}. Elige un
          día para ver qué hay.
        </p>
      </div>
    </header>

    <div class="contenedor">
      <!-- navegación del mes + cambio de vista -->
      <div class="barra">
        <div class="mes-nav">
          <button class="icono" aria-label="Mes anterior" @click="moverMes(-1)">
            <ChevronLeft :size="20" />
          </button>
          <h2 class="mes-titulo">{{ tituloMes(`${mes}-01`) }}</h2>
          <button class="icono" aria-label="Mes siguiente" @click="moverMes(1)">
            <ChevronRight :size="20" />
          </button>
          <button v-if="!esMesActual" class="btn-hoy" @click="irAHoy">Hoy</button>
        </div>

        <div class="vistas" role="tablist" aria-label="Vista">
          <button role="tab" :aria-selected="vista === 'mes'" :class="{ activo: vista === 'mes' }" @click="cambiarVista('mes')">
            <CalendarDays :size="16" /> Mes
          </button>
          <button role="tab" :aria-selected="vista === 'lista'" :class="{ activo: vista === 'lista' }" @click="cambiarVista('lista')">
            <List :size="16" /> Lista
          </button>
        </div>
      </div>

      <!-- filtro por disciplina: mismo criterio que artistas y agrupaciones -->
      <div class="filtros">
        <button class="filtro" :class="{ activo: !grupo }" @click="grupo = null">Todas</button>
        <button
          v-for="(c, cod) in COMISIONES"
          :key="cod"
          class="filtro"
          :class="{ activo: grupo === cod }"
          :style="{ '--acento': c.color }"
          @click="grupo = cod"
        >
          {{ c.corto }}
        </button>
      </div>

      <div v-if="cargando" class="cargando"><q-spinner size="28px" color="primary" /></div>

      <!-- VISTA MES: calendario + panel del día al costado (abajo en celular) -->
      <div v-else-if="vista === 'mes'" class="vista-mes">
        <CalendarioMes v-model:dia-elegido="diaElegido" :mes="mes" :eventos="eventos" />
        <PanelDia class="panel-dia" :fecha="diaElegido" :eventos="eventosDelDia" :siguiente="siguiente" />
      </div>

      <!-- VISTA LISTA: agrupada por día -->
      <div v-else-if="diasConEventos.length" class="por-dia">
        <section v-for="dia in diasConEventos" :key="dia.fecha">
          <h3>{{ tituloDia(dia.fecha) }}</h3>
          <div class="lista">
            <EventoTarjeta v-for="e in dia.eventos" :key="e.id" :evento="e" :dia="dia.fecha" />
          </div>
        </section>
      </div>

      <div v-if="!cargando && !eventos.length" class="vacio">
        <CalendarX :size="32" />
        <p>
          {{ grupo ? 'No hay eventos de esta disciplina en ' : 'Todavía no hay eventos en ' }}
          {{ tituloMes(`${mes}-01`).toLowerCase() }}.
        </p>
      </div>
    </div>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { CalendarDays, CalendarX, ChevronLeft, ChevronRight, List } from 'lucide-vue-next'
import EventoService from '@/services/EventoService'
import EventoTarjeta from '@/components/agenda/EventoTarjeta.vue'
import CalendarioMes from '@/components/agenda/CalendarioMes.vue'
import PanelDia from '@/components/agenda/PanelDia.vue'
import { INSTITUCION, COMISIONES } from '@/config/institucion'
import { aFecha, desdeTexto, diaDe, hoy, ocurreEl, tituloDia, tituloMes } from '@/utils/fechasAgenda'

const route = useRoute()
const router = useRouter()

const hoyTexto = hoy()

// el estado vive en la URL: "/agenda?mes=2026-11&grupo=02&evento=7" se puede compartir
const mes = ref(/^\d{4}-\d{2}$/.test(route.query.mes) ? route.query.mes : hoyTexto.slice(0, 7))
const grupo = ref(route.query.grupo || null)
const vista = ref(route.query.vista === 'lista' ? 'lista' : 'mes')
const diaElegido = ref(mes.value === hoyTexto.slice(0, 7) ? hoyTexto : `${mes.value}-01`)

const eventos = ref([])
const cargando = ref(false)

const primerDia = computed(() => desdeTexto(`${mes.value}-01`))
const ultimoDia = computed(() => new Date(primerDia.value.getFullYear(), primerDia.value.getMonth() + 1, 0))
const esMesActual = computed(() => mes.value === hoyTexto.slice(0, 7))

const diasDelMes = computed(() =>
  Array.from({ length: ultimoDia.value.getDate() }, (_, i) => {
    const fecha = `${mes.value}-${String(i + 1).padStart(2, '0')}`
    return { fecha, numero: i + 1, eventos: eventos.value.filter((e) => ocurreEl(e, fecha)) }
  }),
)

const diasConEventos = computed(() => diasDelMes.value.filter((d) => d.eventos.length))
const eventosDelDia = computed(() => diasDelMes.value.find((d) => d.fecha === diaElegido.value)?.eventos ?? [])

// para el panel de un día vacío: el primer evento que empieza después (vienen ordenados)
const siguiente = computed(() => eventos.value.find((e) => diaDe(e.fecha_inicio) > diaElegido.value) ?? null)

async function cargar() {
  cargando.value = true
  try {
    const r = await EventoService.publicos({
      desde: aFecha(primerDia.value),
      hasta: aFecha(ultimoDia.value),
      grupo: grupo.value || undefined,
    })
    eventos.value = r.data
  } catch {
    eventos.value = []
  } finally {
    cargando.value = false
  }
}

function sincronizarUrl() {
  router.replace({
    query: {
      mes: mes.value,
      ...(grupo.value ? { grupo: grupo.value } : {}),
      ...(vista.value === 'lista' ? { vista: 'lista' } : {}),
    },
  })
}

function moverMes(delta) {
  const d = new Date(primerDia.value.getFullYear(), primerDia.value.getMonth() + delta, 1)
  mes.value = aFecha(d).slice(0, 7)
  diaElegido.value = esMesActual.value ? hoyTexto : `${mes.value}-01`
}

function irAHoy() {
  mes.value = hoyTexto.slice(0, 7)
  diaElegido.value = hoyTexto
}

function cambiarVista(v) {
  vista.value = v
  sincronizarUrl()
}

watch([mes, grupo], () => {
  sincronizarUrl()
  cargar()
})

onMounted(() => {
  // links viejos "/agenda?evento=ID" (cuando el detalle era un diálogo): a la página del evento
  if (/^\d+$/.test(route.query.evento ?? '')) {
    router.replace({ name: 'EventoPublico', params: { evento: route.query.evento } })
    return
  }

  document.title = 'Agenda cultural · Calendario'
  cargar()
})
</script>

<style scoped lang="scss">
.agenda-page {
  background: var(--papel);
  color: var(--tinta);
  padding-bottom: var(--seccion-y);
}

.contenedor {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 0 var(--gutter);
}

.cabecera {
  background: var(--noche);
  color: var(--sobre-foto);
  padding: clamp(48px, 7vw, 88px) 0 clamp(40px, 6vw, 64px);
}

.kicker {
  @include kicker(var(--oro-vivo));
  margin: 0 0 8px;
}

h1 {
  @include display(800);
  font-size: var(--t-xl);
  margin: 0 0 10px;
}

.bajada {
  margin: 0;
  max-width: 56ch;
  opacity: 0.8;
}

.barra {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-top: 28px;
}

.mes-nav {
  display: flex;
  align-items: center;
  gap: 6px;
}

.mes-titulo {
  @include display(800);
  font-size: var(--t-lg);
  margin: 0 6px;
  min-width: 9ch;
  text-align: center;
}

.icono,
.btn-hoy,
.vistas button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid var(--borde-fuerte);
  background: var(--blanco);
  color: var(--tinta);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transicion);

  &:hover {
    border-color: var(--tinta);
  }

  &:focus-visible {
    @include foco;
  }
}

.icono {
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: var(--r-full);
}

.btn-hoy {
  padding: 7px 14px;
  border-radius: var(--r-full);
  font-size: var(--t-sm);
}

.vistas {
  display: inline-flex;

  button {
    padding: 8px 14px;
    font-size: var(--t-sm);

    &:first-child {
      border-radius: var(--r-full) 0 0 var(--r-full);
    }

    &:last-child {
      border-radius: 0 var(--r-full) var(--r-full) 0;
      margin-left: -1px;
    }

    &.activo {
      background: var(--tinta);
      border-color: var(--tinta);
      color: var(--sobre-foto);
    }
  }
}

.filtros {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 18px 0 20px;
}

.filtro {
  padding: 8px 14px;
  border-radius: var(--r-full);
  border: 1px solid var(--borde-fuerte);
  background: var(--blanco);
  color: var(--tinta);
  font: inherit;
  font-size: var(--t-sm);
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transicion);

  &:hover {
    border-color: var(--acento, var(--tinta));
  }

  &.activo {
    background: var(--acento, var(--tinta));
    border-color: var(--acento, var(--tinta));
    color: var(--sobre-foto);
  }

  &:focus-visible {
    @include foco;
  }
}

.cargando {
  display: flex;
  justify-content: center;
  padding: 64px 0;
}

/* celular: las 9 disciplinas en una fila que se desliza, no 4 renglones */
@media (max-width: 700px) {
  .filtros {
    flex-wrap: nowrap;
    overflow-x: auto;
    margin: 0 calc(var(--gutter) * -1);
    padding-left: var(--gutter);
    padding-right: var(--gutter);
    scrollbar-width: none;

    &::-webkit-scrollbar {
      display: none;
    }
  }

  .filtro {
    flex-shrink: 0;
  }
}

/* --- vista mes: calendario + panel del día --- */
.vista-mes {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 24px;
  align-items: start;
}

/* el panel acompaña mientras se recorre el mes */
.panel-dia {
  position: sticky;
  top: 96px;
}

@media (max-width: 1099px) {
  .vista-mes {
    grid-template-columns: minmax(0, 1fr);
  }

  .panel-dia {
    position: static;
  }
}

/* --- listas --- */
.por-dia section {
  padding-top: 28px;
}

h3 {
  @include display(700);
  font-size: var(--t-md);
  margin: 0 0 12px;
}

.lista {
  display: grid;
  gap: 10px;
}


.vacio {
  display: grid;
  justify-items: center;
  gap: 8px;
  padding: 48px 16px;
  color: var(--tinta-suave);
  text-align: center;
}
</style>
