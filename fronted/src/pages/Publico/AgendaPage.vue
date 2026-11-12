<template>
  <!--
    Agenda cultural como cartelera: grilla de afiches grandes con la fecha
    encima (como Gran Teatro Nacional o Joinnus). Arriba solo el mes y filtros.
  -->
  <q-page class="agenda publico">
    <div class="contenedor">
      <header class="cabeza">
        <div>
          <p class="lugar">Cartelera de {{ INSTITUCION.ciudad }}</p>
          <h1>Agenda cultural</h1>
        </div>

        <div class="mes-nav">
          <button class="flecha" aria-label="Mes anterior" @click="moverMes(-1)">
            <ChevronLeft :size="20" />
          </button>
          <button
            class="mes-boton"
            :aria-expanded="calAbierto"
            aria-haspopup="dialog"
            title="Elegir un día"
            @click="calAbierto = !calAbierto"
          >
            <CalendarDays :size="18" aria-hidden="true" />
            <h2 class="mes" aria-live="polite">{{ tituloMes(`${mes}-01`) }}</h2>
            <ChevronDown :size="16" class="mes-abre" :class="{ girado: calAbierto }" aria-hidden="true" />

            <!-- compu: globito debajo del mes -->
            <q-menu
              v-if="!enCelular"
              v-model="calAbierto"
              no-parent-event
              anchor="bottom middle"
              self="top middle"
              :offset="[0, 10]"
              class="menu-calendario"
            >
              <MiniCalendario :mes="mes" :eventos="ordenados" :seleccionado="dia" :hoy="hoyTexto" @elegir="elegirDia" @mover="moverMes" />
            </q-menu>
          </button>
          <button class="flecha" aria-label="Mes siguiente" @click="moverMes(1)">
            <ChevronRight :size="20" />
          </button>
        </div>
      </header>

      <!-- celular: panel desde abajo, más fácil con el dedo -->
      <q-dialog v-if="enCelular" v-model="calAbierto" position="bottom">
        <div class="hoja-calendario">
          <MiniCalendario :mes="mes" :eventos="ordenados" :seleccionado="dia" :hoy="hoyTexto" @elegir="elegirDia" @mover="moverMes" />
        </div>
      </q-dialog>

      <nav class="filtros" aria-label="Filtrar eventos">
        <button class="chip" :class="{ activo: !grupo }" :aria-pressed="!grupo" @click="grupo = null">Todo</button>
        <button
          v-for="(c, cod) in COMISIONES"
          :key="cod"
          class="chip"
          :class="{ activo: grupo === cod }"
          :aria-pressed="grupo === cod"
          :style="{ '--acento': c.color }"
          @click="grupo = grupo === cod ? null : cod"
        >
          <span class="punto" aria-hidden="true" />{{ c.corto }}
        </button>
        <span class="separador" aria-hidden="true" />
        <button class="chip gratis" :class="{ activo: soloGratis }" :aria-pressed="soloGratis" @click="soloGratis = !soloGratis">
          Ingreso libre
        </button>
      </nav>

      <div class="barra">
        <div class="barra-izq">
          <button v-if="dia" class="chip activo dia-elegido" :aria-label="`Quitar el día ${tituloDia(dia)}`" @click="dia = null">
            {{ tituloDia(dia) }} <X :size="15" aria-hidden="true" />
          </button>
          <p v-if="!cargando" class="resumen">
            <template v-if="visibles.length">
              {{ visibles.length }} {{ visibles.length === 1 ? 'evento' : 'eventos' }}
              <template v-if="!dia">{{ esMesActual && !mostrarPasados ? 'desde hoy' : `en ${nombreMes}` }}</template>
            </template>
          </p>
        </div>
        <div class="barra-acciones">
          <button v-if="pasadosOcultos" class="enlace" @click="mostrarPasados = true">
            Ver los que ya pasaron ({{ pasadosOcultos }})
          </button>
          <button v-if="!esMesActual" class="enlace" @click="irAHoy">Volver a este mes</button>
        </div>
      </div>

      <div v-if="cargando" class="cargando"><q-spinner size="28px" color="primary" /></div>

      <template v-else-if="visibles.length">
      <section v-for="s in semanas" :key="s.lunes" class="semana" :aria-label="s.titulo">
      <h2 v-if="s.titulo" class="semana-titulo">{{ s.titulo }}</h2>
      <ul class="cartelera">
        <li v-for="e in s.eventos" :key="e.id">
          <router-link
            :to="{ name: 'EventoPublico', params: { evento: e.ruta } }"
            class="cartel"
            :class="{ terminado: yaPaso(e) }"
            :style="{ '--acento': comisionDe(e.cod_grupo).color }"
          >
            <span class="afiche">
              <img :src="imagenDe(e)" alt="" loading="lazy" :class="{ generica: !e.imagen_url }" />
              <span class="sello-fecha" aria-hidden="true">
                <b>{{ hojaCalendario(e).dia }}</b>
                <small>{{ hojaCalendario(e).mes }}</small>
              </span>
              <span v-if="e.flag_destacado" class="sello-destacado"><Star :size="12" /> Destacado</span>
            </span>

            <span class="cuerpo">
              <span class="disciplina">{{ comisionDe(e.cod_grupo).corto }}</span>
              <span class="titulo">{{ e.titulo }}</span>
              <span class="dato">
                <CalendarDays :size="15" aria-hidden="true" />
                {{ cuandoCorto(e) }}
              </span>
              <span class="dato">
                <component :is="e.modalidad === 'virtual' ? MonitorPlay : MapPin" :size="15" aria-hidden="true" />
                {{ e.modalidad === 'virtual' ? 'En línea' : e.lugar }}
              </span>
              <span class="precio" :class="{ libre: e.flag_gratuito }">
                {{ e.flag_gratuito ? 'Ingreso libre' : `S/ ${Number(e.costo).toFixed(2)}` }}
              </span>
            </span>
          </router-link>
        </li>
      </ul>
      </section>

      <button v-if="restantes" class="ver-mas" @click="limite = Infinity">
        Ver {{ restantes === 1 ? 'el evento restante' : `los ${restantes} eventos restantes` }} de {{ nombreMes }}
      </button>
      </template>

      <div v-else class="vacio">
        <p class="vacio-titulo">
          No hay eventos{{ grupo ? ` de ${COMISIONES[grupo]?.corto.toLowerCase()}` : '' }}{{ soloGratis ? ' con ingreso libre' : '' }}
          <template v-if="dia">el {{ tituloDia(dia).toLowerCase() }}.</template>
          <template v-else>{{ pasadosOcultos ? 'desde hoy' : '' }} en {{ nombreMes }}.</template>
        </p>
        <p class="vacio-texto">La agenda se actualiza a medida que la municipalidad aprueba nuevas propuestas.</p>
        <div class="vacio-acciones">
          <button v-if="!totalSiguiente" class="btn-tinta" @click="moverMes(1)">
            Ver {{ nombreMesSiguiente }} <ArrowRight :size="16" />
          </button>
          <button v-if="grupo || soloGratis" class="enlace" @click="limpiarFiltros">Quitar filtros</button>
        </div>
      </div>

      <!-- que no se pierda lo que viene: el mes siguiente avisa cuántos eventos trae -->
      <button v-if="!cargando && totalSiguiente" class="siguiente" @click="moverMes(1)">
        <span>
          <small>Sigue en la agenda</small>
          <strong>{{ totalSiguiente }} {{ totalSiguiente === 1 ? 'evento' : 'eventos' }} en {{ nombreMesSiguiente }}</strong>
        </span>
        <span class="siguiente-ir">Ver {{ nombreMesSiguiente }} <ArrowRight :size="18" /></span>
      </button>

      <aside class="propon">
        <div>
          <h2>¿Organizas un evento cultural en {{ INSTITUCION.ciudad }}?</h2>
          <p>
            Los artistas y agrupaciones del registro pueden proponer sus funciones, conciertos y talleres
            desde su panel. La municipalidad los revisa antes de publicarlos en esta agenda.
          </p>
        </div>
        <router-link :to="{ name: 'Login' }" class="btn-propon">
          Proponer un evento <ArrowRight :size="17" />
        </router-link>
      </aside>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import {
  ArrowRight,
  CalendarDays,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  MapPin,
  MonitorPlay,
  Star,
  X,
} from 'lucide-vue-next'
import EventoService from '@/services/EventoService'
import MiniCalendario from '@/components/agenda/MiniCalendario.vue'
import { INSTITUCION, COMISIONES, comisionDe } from '@/config/institucion'
import {
  aFecha,
  cercania,
  cuandoTexto,
  desdeTexto,
  diaDe,
  esDeVariosDias,
  hojaCalendario,
  hora,
  hoy,
  ocurreEl,
  tituloDia,
  tituloMes,
} from '@/utils/fechasAgenda'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const enCelular = computed(() => $q.screen.lt.sm)

const hoyTexto = hoy()

// el estado vive en la URL: "/agenda?mes=2026-11&grupo=02" se puede compartir
const mes = ref(/^\d{4}-\d{2}$/.test(route.query.mes) ? route.query.mes : hoyTexto.slice(0, 7))
const grupo = ref(route.query.grupo || null)
const soloGratis = ref(route.query.gratis === '1')
const mostrarPasados = ref(false)
// día elegido en el calendario chico ("2026-11-22"); solo vale dentro del mes que se ve
const dia = ref(
  /^\d{4}-\d{2}-\d{2}$/.test(route.query.dia ?? '') && route.query.dia.startsWith(mes.value) ? route.query.dia : null,
)
const calAbierto = ref(false)

const eventos = ref([])
const cargando = ref(false)

const primerDia = computed(() => desdeTexto(`${mes.value}-01`))
const ultimoDia = computed(() => new Date(primerDia.value.getFullYear(), primerDia.value.getMonth() + 1, 0))
const esMesActual = computed(() => mes.value === hoyTexto.slice(0, 7))
const nombreMes = computed(() => tituloMes(`${mes.value}-01`).split(' ')[0].toLowerCase())
const nombreMesSiguiente = computed(() => {
  const d = new Date(primerDia.value.getFullYear(), primerDia.value.getMonth() + 1, 1)
  return tituloMes(aFecha(d)).split(' ')[0].toLowerCase()
})

const yaPaso = (e) => diaDe(e.fecha_fin || e.fecha_inicio) < hoyTexto

// "ingreso libre" se filtra acá: el backend ya filtra mes y disciplina
const ordenados = computed(() =>
  (soloGratis.value ? eventos.value.filter((e) => e.flag_gratuito) : eventos.value)
    .slice()
    .sort((a, b) => a.fecha_inicio.localeCompare(b.fecha_inicio)),
)

// en el mes en curso se empieza por hoy: lo que ya pasó queda a un clic
const pasadosOcultos = computed(() =>
  esMesActual.value && !mostrarPasados.value && !dia.value ? ordenados.value.filter(yaPaso).length : 0,
)
const visibles = computed(() => {
  if (dia.value) return ordenados.value.filter((e) => ocurreEl(e, dia.value))
  return pasadosOcultos.value ? ordenados.value.filter((e) => !yaPaso(e)) : ordenados.value
})

// un mes cargado (30+ eventos) no se muestra de golpe: 12 afiches y "ver más"
const POR_TANDA = 12
const limite = ref(POR_TANDA)
const restantes = computed(() => Math.max(0, visibles.value.length - limite.value))

// los afiches se agrupan por semana para que una lista larga se pueda recorrer
const fmtDiaMes = new Intl.DateTimeFormat('es-PE', { day: 'numeric', month: 'long' })
function lunesDe(ymd) {
  const d = desdeTexto(ymd)
  d.setDate(d.getDate() - ((d.getDay() + 6) % 7))
  return aFecha(d)
}
const sumarDias = (ymd, n) => {
  const d = desdeTexto(ymd)
  d.setDate(d.getDate() + n)
  return aFecha(d)
}
const lunesHoy = lunesDe(hoyTexto)

function tituloSemana(lunes) {
  if (lunes === lunesHoy) return 'Esta semana'
  if (lunes === sumarDias(lunesHoy, 7)) return 'La próxima semana'
  // recortada al mes que se está viendo: "Del 1 al 8 de noviembre"
  const desde = lunes < `${mes.value}-01` ? `${mes.value}-01` : lunes
  const hastaSemana = sumarDias(lunes, 6)
  const hasta = hastaSemana > aFecha(ultimoDia.value) ? aFecha(ultimoDia.value) : hastaSemana
  if (desde === hasta) return capitalizar(fmtDiaMes.format(desdeTexto(desde)))
  return `Del ${desdeTexto(desde).getDate()} al ${fmtDiaMes.format(desdeTexto(hasta))}`
}

// con pocos eventos los títulos de semana solo dejan huecos: una sola grilla
const AGRUPAR_DESDE = 9

const semanas = computed(() => {
  const tanda = visibles.value.slice(0, limite.value)
  if (visibles.value.length < AGRUPAR_DESDE) return [{ lunes: 'mes', titulo: null, eventos: tanda }]

  const grupos = new Map()
  for (const e of tanda) {
    // un festival que empezó antes cuenta desde hoy (o desde el 1 del mes)
    let base = diaDe(e.fecha_inicio)
    if (base < `${mes.value}-01`) base = `${mes.value}-01`
    if (pasadosOcultos.value && base < hoyTexto) base = hoyTexto
    const lunes = lunesDe(base)
    if (!grupos.has(lunes)) grupos.set(lunes, { lunes, titulo: tituloSemana(lunes), eventos: [] })
    grupos.get(lunes).eventos.push(e)
  }
  return [...grupos.values()]
})

const capitalizar = (t) => t.charAt(0).toUpperCase() + t.slice(1)

// cuántos eventos trae el mes siguiente (con los mismos filtros)
const siguiente = ref([])
const totalSiguiente = computed(
  () => (soloGratis.value ? siguiente.value.filter((e) => e.flag_gratuito) : siguiente.value).length,
)

// sin afiche propio se usa la foto de su disciplina
const imagenDe = (e) => e.imagen_url || e.imagen_miniatura_url || comisionDe(e.cod_grupo).foto || '/images/com-01.jpg'

// "Sábado · 16:00", "Hoy · 19:30" o "Del 15 al 18 de noviembre"
function cuandoCorto(e) {
  if (esDeVariosDias(e)) return cuandoTexto(e).fecha
  if (yaPaso(e)) return `${cuandoTexto(e).fecha} · ${hora(e.fecha_inicio)}`
  return `${cercania(e)} · ${hora(e.fecha_inicio)}`
}

// ---------- datos y navegación ----------

async function cargar() {
  cargando.value = true
  limite.value = POR_TANDA
  const p = primerDia.value
  const pedir = (desde, hasta) =>
    EventoService.publicos({ desde: aFecha(desde), hasta: aFecha(hasta), grupo: grupo.value || undefined })
      .then((r) => r.data)
      .catch(() => [])

  const [delMes, delSiguiente] = await Promise.all([
    pedir(p, ultimoDia.value),
    pedir(new Date(p.getFullYear(), p.getMonth() + 1, 1), new Date(p.getFullYear(), p.getMonth() + 2, 0)),
  ])
  eventos.value = delMes
  // un festival que cruza de mes ya aparece en este: no se cuenta dos veces
  const ids = new Set(delMes.map((e) => e.id))
  siguiente.value = delSiguiente.filter((e) => !ids.has(e.id))
  cargando.value = false
}

function sincronizarUrl() {
  router.replace({
    query: {
      mes: mes.value,
      ...(grupo.value ? { grupo: grupo.value } : {}),
      ...(soloGratis.value ? { gratis: '1' } : {}),
      ...(dia.value ? { dia: dia.value } : {}),
    },
  })
}

function moverMes(delta) {
  const d = new Date(primerDia.value.getFullYear(), primerDia.value.getMonth() + delta, 1)
  mes.value = aFecha(d).slice(0, 7)
  mostrarPasados.value = false
  dia.value = null
}

function irAHoy() {
  mes.value = hoyTexto.slice(0, 7)
  mostrarPasados.value = false
  dia.value = null
}

function elegirDia(fecha) {
  dia.value = fecha
  calAbierto.value = false
}

function limpiarFiltros() {
  grupo.value = null
  soloGratis.value = false
  dia.value = null
}

watch([mes, grupo], () => {
  sincronizarUrl()
  cargar()
})
watch([soloGratis, dia], () => {
  limite.value = POR_TANDA
  sincronizarUrl()
})

onMounted(() => {
  // links viejos "/agenda?evento=ID" (cuando el detalle era un diálogo): a la página del evento
  if (/^\d+$/.test(route.query.evento ?? '')) {
    router.replace({ name: 'EventoPublico', params: { evento: route.query.evento } })
    return
  }

  document.title = `Agenda cultural de ${INSTITUCION.ciudad} | RACC Puno`
  cargar()
})
</script>

<style scoped lang="scss">
.agenda {
  background: var(--papel);
  color: var(--tinta);
  padding-bottom: var(--seccion-y);
}

.contenedor {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 0 var(--gutter);
}

/* ---------- cabecera ---------- */
.cabeza {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 18px 32px;
  padding: clamp(32px, 5vw, 56px) 0 24px;
}

.lugar {
  @include kicker(var(--rojo));
  margin: 0 0 8px;
}

h1 {
  @include display(800);
  font-size: var(--t-xl);
  line-height: 1;
  letter-spacing: -0.01em;
  margin: 0;
}

.mes-nav {
  display: flex;
  align-items: center;
  gap: 4px;
}

/* el nombre del mes abre el calendario chico */
.mes-boton {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 40px;
  padding: 0 14px;
  border: 0;
  border-radius: var(--r-full);
  background: none;
  color: var(--tinta);
  cursor: pointer;
  transition: background var(--transicion);

  &:hover,
  &[aria-expanded='true'] {
    background: var(--crema);
  }
  &:focus-visible {
    @include foco;
  }
}

.mes {
  font: 700 var(--t-md) var(--fuente-texto);
  margin: 0;
  white-space: nowrap;
}

.mes-abre {
  color: var(--tinta-suave);
  transition: transform var(--transicion);

  &.girado {
    transform: rotate(180deg);
  }
}

:global(.menu-calendario) {
  border-radius: var(--r-md) !important;
  box-shadow: var(--sombra-alta) !important;
}

.hoja-calendario {
  width: 100%;
  background: #fff;
  border-radius: var(--r-lg) var(--r-lg) 0 0 !important;
}

.barra-izq {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px 14px;
}

.dia-elegido {
  text-transform: none;
}

.flecha {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 1px solid var(--borde);
  background: #fff;
  color: var(--tinta);
  cursor: pointer;
  transition: background var(--transicion);

  &:hover {
    background: var(--crema);
  }
  &:focus-visible {
    @include foco;
  }
}

/* ---------- filtros ---------- */
.filtros {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.chip {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  height: 36px;
  padding: 0 14px;
  border: 1px solid var(--borde);
  border-radius: var(--r-full);
  background: #fff;
  font: 600 var(--t-sm) var(--fuente-texto);
  color: var(--tinta);
  white-space: nowrap;
  cursor: pointer;
  transition:
    background var(--transicion),
    border-color var(--transicion),
    color var(--transicion);

  &:hover {
    border-color: var(--tinta-tenue);
  }
  &.activo {
    background: var(--tinta);
    border-color: var(--tinta);
    color: #fff;
  }
  &:focus-visible {
    @include foco;
  }

  .punto {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--acento);
  }
}

.chip.gratis.activo {
  background: var(--lago);
  border-color: var(--lago);
}

.separador {
  width: 1px;
  height: 22px;
  margin: 0 4px;
  background: var(--borde);
}

.barra {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px 20px;
  min-height: 24px;
  margin: 26px 0 18px;
}

.resumen {
  margin: 0;
  font-size: var(--t-sm);
  color: var(--tinta-suave);
}

.barra-acciones {
  display: flex;
  gap: 20px;
}

.enlace {
  background: none;
  border: 0;
  padding: 0;
  font: 600 var(--t-sm) var(--fuente-texto);
  color: var(--tinta);
  text-decoration: underline;
  text-underline-offset: 3px;
  cursor: pointer;

  &:hover {
    color: var(--rojo);
  }
}

/* ---------- semanas ---------- */
.semana + .semana {
  margin-top: clamp(40px, 5vw, 56px);
}

.semana-titulo {
  display: flex;
  align-items: center;
  gap: 16px;
  margin: 0 0 20px;
  font: 700 var(--t-md) var(--fuente-texto);

  &::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--borde);
  }
}

.ver-mas {
  display: block;
  margin: 40px auto 0;
  padding: 13px 26px;
  border: 1px solid var(--tinta);
  border-radius: var(--r-full);
  background: transparent;
  font: 700 var(--t-sm) var(--fuente-texto);
  color: var(--tinta);
  cursor: pointer;
  transition: background var(--transicion), color var(--transicion);

  &:hover {
    background: var(--tinta);
    color: #fff;
  }
  &:focus-visible {
    @include foco;
  }
}

.siguiente {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px 24px;
  width: 100%;
  margin-top: clamp(48px, 6vw, 72px);
  padding: 22px 0;
  border: 0;
  border-top: 1px solid var(--borde);
  border-bottom: 1px solid var(--borde);
  background: none;
  text-align: left;
  color: var(--tinta);
  cursor: pointer;

  small {
    display: block;
    @include kicker(var(--tinta-suave));
    margin-bottom: 6px;
  }
  strong {
    @include display(700);
    font-size: var(--t-lg);
  }
  &:hover .siguiente-ir {
    color: var(--rojo);
  }
  &:focus-visible {
    @include foco;
  }
}

.siguiente-ir {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font: 700 var(--t-sm) var(--fuente-texto);
  transition: color var(--transicion);
}

/* ---------- cartelera ---------- */
.cartelera {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 250px), 1fr));
  gap: 40px 24px;
}

.cartel {
  display: flex;
  flex-direction: column;
  height: 100%;
  color: inherit;
  text-decoration: none;

  &:hover img {
    transform: scale(1.04);
  }
  &:hover .titulo {
    text-decoration: underline;
    text-decoration-thickness: 2px;
    text-underline-offset: 4px;
    text-decoration-color: var(--acento);
  }
  &:focus-visible {
    @include foco;
    border-radius: var(--r-md);
  }

  &.terminado .afiche img {
    filter: grayscale(0.7);
    opacity: 0.75;
  }
}

.afiche {
  position: relative;
  display: block;
  aspect-ratio: 4 / 5;
  border-radius: var(--r-md);
  overflow: hidden;
  background: var(--crema);

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.5s ease;
  }
  /* foto genérica de la disciplina: más apagada para que no parezca el afiche */
  img.generica {
    filter: saturate(0.6) brightness(0.85);
  }
}

.sello-fecha {
  position: absolute;
  top: 12px;
  left: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 54px;
  padding: 7px 8px 6px;
  border-radius: var(--r-sm);
  background: #fff;
  box-shadow: var(--sombra);
  line-height: 1;

  b {
    @include display(800);
    font-size: 1.6rem;
    color: var(--tinta);
  }
  small {
    margin-top: 3px;
    font: 700 0.7rem var(--fuente-texto);
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--rojo);
  }
}

.sello-destacado {
  position: absolute;
  top: 12px;
  right: 12px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 5px 10px;
  border-radius: var(--r-full);
  background: var(--oro);
  color: var(--tinta);
  font: 700 0.72rem var(--fuente-texto);
}

.cuerpo {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
  padding-top: 16px;
}

.disciplina {
  font: 700 0.74rem var(--fuente-texto);
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--acento);
}

.titulo {
  @include display(700);
  font-size: 1.3rem;
  line-height: 1.2;
  margin-bottom: 4px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.dato {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: var(--t-sm);
  color: var(--tinta-suave);

  svg {
    flex-shrink: 0;
  }
}

.precio {
  margin-top: auto;
  padding-top: 10px;
  font: 700 var(--t-sm) var(--fuente-texto);
  color: var(--tinta);

  &.libre {
    color: var(--lago);
  }
}

/* ---------- vacío ---------- */
.cargando {
  display: grid;
  place-items: center;
  padding: 80px 0;
}

.vacio {
  padding: clamp(40px, 7vw, 80px) 0;
  max-width: 620px;
}

.vacio-titulo {
  @include display(700);
  font-size: var(--t-lg);
  line-height: 1.2;
  margin: 0 0 10px;
}

.vacio-texto {
  color: var(--tinta-suave);
  margin: 0 0 24px;
}

.vacio-acciones {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px 20px;
}

.btn-tinta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 22px;
  border: 0;
  border-radius: var(--r-full);
  background: var(--tinta);
  color: var(--papel);
  font: 700 var(--t-sm) var(--fuente-texto);
  cursor: pointer;

  &:focus-visible {
    @include foco;
  }
}

/* ---------- propón un evento ---------- */
.propon {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 20px 32px;
  margin-top: clamp(56px, 8vw, 96px);
  padding: clamp(24px, 4vw, 40px);
  background: var(--crema);
  border-radius: var(--r-lg);

  h2 {
    @include display(800);
    font-size: var(--t-lg);
    line-height: 1.15;
    margin: 0 0 8px;
  }
  p {
    margin: 0;
    max-width: 60ch;
    color: var(--tinta-suave);
    line-height: 1.6;
  }
}

.btn-propon {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 13px 24px;
  border-radius: var(--r-full);
  background: var(--rojo);
  color: #fff;
  font: 700 var(--t-sm) var(--fuente-texto);
  text-decoration: none;
  white-space: nowrap;
  transition: background var(--transicion);

  &:hover {
    background: var(--rojo-hondo);
  }
  &:focus-visible {
    @include foco;
  }
}

/* ---------- celular ---------- */
@media (max-width: 600px) {
  .cabeza {
    flex-direction: column;
    align-items: flex-start;
  }
  .mes-nav {
    width: 100%;
    justify-content: space-between;
  }

  /* los filtros se deslizan de costado en vez de ocupar 4 filas */
  .filtros {
    flex-wrap: nowrap;
    overflow-x: auto;
    scrollbar-width: none;
    margin-inline: calc(var(--gutter) * -1);
    padding-inline: var(--gutter);

    &::-webkit-scrollbar {
      display: none;
    }
  }
  .separador {
    flex-shrink: 0;
  }

  .cartelera {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 28px 14px;
  }
  .sello-fecha {
    top: 8px;
    left: 8px;
    min-width: 44px;
    padding: 5px 6px;

    b {
      font-size: 1.25rem;
    }
  }
  .sello-destacado {
    display: none;
  }
  .cuerpo {
    padding-top: 10px;
    gap: 4px;
  }
  .titulo {
    font-size: 1.05rem;
    -webkit-line-clamp: 3;
  }
  .dato {
    font-size: var(--t-xs);
    align-items: flex-start;
  }
}
</style>
