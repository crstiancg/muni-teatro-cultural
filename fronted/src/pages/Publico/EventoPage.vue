<template>
  <q-page class="evento-page publico">
    <div v-if="cargando" class="estado"><q-spinner size="32px" color="primary" /></div>

    <div v-else-if="!evento" class="estado contenedor">
      <CalendarX :size="36" />
      <h1 class="no-encontrado">Este evento no está disponible</h1>
      <p>Puede que haya sido cancelado o que el enlace esté mal escrito.</p>
      <router-link :to="{ name: 'AgendaPublica' }" class="accion principal">Ver la agenda cultural</router-link>
    </div>

    <template v-else>
      <!-- cabecera: afiche + datos clave -->
      <section class="hero" :style="{ '--acento': acento }">
        <div class="contenedor">
          <router-link :to="{ name: 'AgendaPublica', query: { mes: evento.fecha_inicio.slice(0, 7) } }" class="volver">
            <ArrowLeft :size="16" /> Agenda cultural
          </router-link>

          <div class="hero-grid" :class="{ 'sin-afiche': !evento.imagen_url }">
            <!-- el afiche se muestra ENTERO: suele traer fecha, hora y lugar escritos -->
            <figure
              v-if="evento.imagen_url"
              class="cartel"
              :style="{ '--fondo': `url('${evento.imagen_url}')` }"
              @click="zoom = true"
            >
              <img :src="evento.imagen_url" :alt="`Afiche: ${evento.titulo}`" />
              <figcaption class="ampliar"><Expand :size="14" /> Ver afiche completo</figcaption>
            </figure>

            <div class="info">
              <span v-if="evento.comision" class="etiqueta">{{ evento.comision }}</span>
              <h1>{{ evento.titulo }}</h1>

              <div class="cuando">
                <div class="hoja" aria-hidden="true">
                  <span class="hoja-mes">{{ hoja.mes }}</span>
                  <span class="hoja-dia">{{ hoja.dia }}</span>
                </div>
                <div class="cuando-texto">
                  <strong>{{ cuando.fecha }}</strong>
                  <span><Clock :size="15" /> {{ cuando.horario }}</span>
                </div>
              </div>

              <ul class="datos">
                <li v-if="evento.modalidad === 'virtual'">
                  <MonitorPlay :size="20" />
                  <span><strong>Evento virtual</strong></span>
                </li>
                <li v-else>
                  <MapPin :size="20" />
                  <span>
                    <strong>{{ evento.lugar }}</strong>
                    <span v-if="evento.direccion" class="sub">{{ evento.direccion }}</span>
                  </span>
                </li>
                <li>
                  <Ticket :size="20" />
                  <span><strong>{{ evento.flag_gratuito ? 'Ingreso libre' : `S/ ${evento.costo}` }}</strong></span>
                </li>
                <li>
                  <Users :size="20" />
                  <span>
                    <span class="sub">Organiza</span>
                    <router-link v-if="rutaOrganizador" :to="rutaOrganizador" class="organizador">
                      {{ evento.organizador.nombre }}
                    </router-link>
                    <strong v-else>{{ INSTITUCION.nombre }}</strong>
                  </span>
                </li>
              </ul>

              <div class="acciones">
                <a :href="urlCalendario" target="_blank" rel="noopener" class="accion principal">
                  <CalendarPlus :size="16" /> Agregar a mi calendario
                </a>
                <a v-if="evento.modalidad === 'presencial'" :href="urlMapa" target="_blank" rel="noopener" class="accion">
                  <Navigation :size="16" /> Cómo llegar
                </a>
                <a v-if="evento.enlace" :href="evento.enlace" target="_blank" rel="noopener" class="accion">
                  <ExternalLink :size="16" /> {{ evento.modalidad === 'virtual' ? 'Ver transmisión' : 'Más información' }}
                </a>
                <button class="accion" @click="compartir">
                  <component :is="copiado ? Check : Share2" :size="16" />
                  {{ copiado ? 'Enlace copiado' : 'Compartir' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- descripción + columna práctica (ubicación, organiza, vas a ir) -->
      <section class="contenedor cuerpo" :class="{ 'sin-descripcion': !evento.descripcion }" :style="{ '--acento': acento }">
        <article v-if="evento.descripcion" class="acerca">
          <h2>Sobre el evento</h2>
          <!-- HTML sanitizado en el backend al guardar (HTMLPurifier), por eso v-html es seguro -->
          <div class="descripcion" v-html="evento.descripcion" />
        </article>

        <aside class="lateral">
          <div v-if="evento.modalidad === 'presencial'" class="bloque">
            <h3><MapPin :size="15" /> Ubicación</h3>
            <p class="bloque-titulo">{{ evento.lugar }}</p>
            <p class="sub">{{ [evento.direccion, INSTITUCION.ciudad].filter(Boolean).join(', ') }}</p>

            <!-- el mapa de Google trae cookies: se carga recién cuando la persona lo pide -->
            <div class="mapa">
              <iframe
                v-if="mapaVisible"
                :src="urlMapaEmbebido"
                :title="`Mapa: ${evento.lugar}`"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
              />
              <button v-else class="mapa-cargar" @click="mapaVisible = true">
                <MapIcon :size="22" />
                Ver mapa
                <small>Se carga desde Google Maps</small>
              </button>
            </div>

            <a :href="urlMapa" target="_blank" rel="noopener" class="enlace">
              Cómo llegar <ExternalLink :size="14" />
            </a>
          </div>

          <div v-else class="bloque">
            <h3><MonitorPlay :size="15" /> Transmisión</h3>
            <p class="bloque-titulo">Evento virtual</p>
            <p class="sub">Puedes verlo desde donde estés.</p>
            <a v-if="evento.enlace" :href="evento.enlace" target="_blank" rel="noopener" class="accion principal ancho">
              <ExternalLink :size="16" /> Ver transmisión
            </a>
          </div>

          <div class="bloque">
            <h3><Users :size="15" /> Organiza</h3>
            <router-link v-if="rutaOrganizador" :to="rutaOrganizador" class="bloque-titulo organizador">
              {{ evento.organizador.nombre }}
            </router-link>
            <template v-else>
              <p class="bloque-titulo">{{ INSTITUCION.nombre }}</p>
              <p class="sub">{{ INSTITUCION.entidad }}</p>
            </template>
          </div>

          <div class="bloque">
            <h3><CalendarPlus :size="15" /> ¿Vas a ir?</h3>
            <p class="sub">Guárdalo en tu calendario o compártelo con quien te acompañe.</p>
            <div class="acciones columna">
              <a :href="urlCalendario" target="_blank" rel="noopener" class="accion principal">
                <CalendarPlus :size="16" /> Agregar a mi calendario
              </a>
              <button class="accion" @click="compartir">
                <component :is="copiado ? Check : Share2" :size="16" />
                {{ copiado ? 'Enlace copiado' : 'Compartir' }}
              </button>
            </div>
          </div>
        </aside>
      </section>

      <!-- seguir navegando la agenda -->
      <section class="contenedor mas">
        <div class="mas-cabecera">
          <h2>{{ otros.length ? 'Más eventos' : 'No hay otros eventos próximos' }}</h2>
          <router-link :to="{ name: 'AgendaPublica' }" class="ver-todo">
            Ver agenda completa <ArrowRight :size="16" />
          </router-link>
        </div>

        <div v-if="otros.length" class="grilla">
          <router-link
            v-for="o in otros"
            :key="o.id"
            :to="{ name: 'EventoPublico', params: { evento: o.ruta } }"
            class="card"
            :style="{ '--acento': comisionDe(o.cod_grupo).color }"
          >
            <span class="card-img">
              <img v-if="o.imagen_miniatura_url" :src="o.imagen_miniatura_url" alt="" loading="lazy" />
              <span class="card-fecha">
                <b>{{ hojaCalendario(o).dia }}</b>{{ hojaCalendario(o).mes }}
              </span>
            </span>
            <span class="card-cuerpo">
              <span v-if="o.comision" class="card-disciplina">{{ comisionDe(o.cod_grupo).corto }}</span>
              <span class="card-titulo">{{ o.titulo }}</span>
              <span class="card-meta">
                {{ hora(o.fecha_inicio) }} · {{ o.modalidad === 'virtual' ? 'Virtual' : o.lugar }}
              </span>
            </span>
          </router-link>
        </div>
      </section>

      <!-- afiche a pantalla completa, para poder leerlo -->
      <q-dialog v-model="zoom" maximized transition-show="fade" transition-hide="fade">
        <div class="visor" @click="zoom = false">
          <button class="cerrar" aria-label="Cerrar afiche"><X :size="20" /></button>
          <img :src="evento.imagen_url" :alt="`Afiche: ${evento.titulo}`" />
        </div>
      </q-dialog>
    </template>
  </q-page>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft,
  ArrowRight,
  CalendarPlus,
  CalendarX,
  Check,
  Clock,
  Expand,
  ExternalLink,
  Map as MapIcon,
  MapPin,
  MonitorPlay,
  Navigation,
  Share2,
  Ticket,
  Users,
  X,
} from 'lucide-vue-next'
import EventoService from '@/services/EventoService'
import { INSTITUCION, comisionDe } from '@/config/institucion'
import { cuandoTexto, hojaCalendario, hora, urlGoogleCalendar } from '@/utils/fechasAgenda'

const route = useRoute()
const router = useRouter()

const evento = ref(null)
const otros = ref([])
const cargando = ref(true)
const zoom = ref(false)
const copiado = ref(false)
const mapaVisible = ref(false)

const acento = computed(() => comisionDe(evento.value?.cod_grupo).color)
const hoja = computed(() => hojaCalendario(evento.value))
const cuando = computed(() => cuandoTexto(evento.value))

const lugarCompleto = computed(() =>
  [evento.value.lugar, evento.value.direccion, INSTITUCION.ciudad].filter(Boolean).join(', '),
)

const urlMapa = computed(
  () => `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(lugarCompleto.value)}`,
)

// embebido por búsqueda de texto: no necesita API key ni coordenadas
const urlMapaEmbebido = computed(
  () => `https://www.google.com/maps?q=${encodeURIComponent(lugarCompleto.value)}&output=embed`,
)

// la URL de esta página: el backend le pone afiche y título para WhatsApp/Facebook
const urlPagina = computed(() => {
  const { href } = router.resolve({ name: 'EventoPublico', params: { evento: evento.value.ruta } })
  return new URL(href, window.location.origin).toString()
})

const urlCalendario = computed(() =>
  urlGoogleCalendar(evento.value, {
    lugar: evento.value.modalidad === 'virtual' ? evento.value.enlace || 'Virtual' : lugarCompleto.value,
    detalle: `${evento.value.descripcion_texto || ''}\n\n${urlPagina.value}`.trim(),
  }),
)

const rutaOrganizador = computed(() => {
  const o = evento.value.organizador
  if (o?.tipo === 'persona' && o.slug) return { name: 'ConsejeroDetallePublico', params: { slug: o.slug } }
  if (o?.tipo === 'agrupacion' && o.slug) return { name: 'AgrupacionPublica', params: { slug: o.slug } }
  return null
})

// en el celular abre el menú nativo (WhatsApp, etc.); en la compu copia el enlace
async function compartir() {
  try {
    if (navigator.share) {
      await navigator.share({ title: evento.value.titulo, text: cuando.value.fecha, url: urlPagina.value })
      return
    }
    await navigator.clipboard.writeText(urlPagina.value)
    copiado.value = true
    setTimeout(() => (copiado.value = false), 2200)
  } catch {
    // cerró el menú de compartir o el navegador bloqueó el portapapeles: nada que hacer
  }
}

async function cargar() {
  // "12-noche-de-rock" → 12
  const id = parseInt(route.params.evento, 10)
  cargando.value = true
  zoom.value = false
  mapaVisible.value = false
  try {
    const r = await EventoService.publico(id)
    evento.value = r.data
    otros.value = r.otros
    document.title = `${r.data.titulo} · Agenda cultural`
    // si el título cambió o el link vino solo con el id, se corrige la URL sin recargar
    if (route.params.evento !== r.data.ruta) {
      router.replace({ name: 'EventoPublico', params: { evento: r.data.ruta } })
    }
  } catch {
    evento.value = null
    otros.value = []
    document.title = 'Evento no disponible · Agenda cultural'
  } finally {
    cargando.value = false
  }
}

// desde "Más eventos" se navega a otro evento sin desmontar la página
watch(
  () => route.params.evento,
  (nuevo, anterior) => {
    if (nuevo && parseInt(nuevo, 10) !== parseInt(anterior, 10)) cargar()
  },
  { immediate: true },
)
</script>

<style scoped lang="scss">
.evento-page {
  background: var(--papel);
  color: var(--tinta);
  padding-bottom: var(--seccion-y);
}

.contenedor {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 0 var(--gutter);
}

.estado {
  display: grid;
  justify-items: center;
  gap: 12px;
  padding: 96px var(--gutter);
  color: var(--tinta-suave);
  text-align: center;

  p {
    margin: 0 0 8px;
  }
}

.no-encontrado {
  @include display(700);
  margin: 0;
  color: var(--tinta);
  font-size: var(--t-lg);
}

/* ---------- cabecera ---------- */
.hero {
  padding: 28px 0 48px;
  background: linear-gradient(180deg, color-mix(in srgb, var(--acento) 10%, var(--papel)) 0%, var(--papel) 100%);
}

.volver {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 24px;
  color: var(--tinta-suave);
  font-size: var(--t-sm);
  font-weight: 700;
  text-decoration: none;

  &:hover {
    color: var(--tinta);
  }

  &:focus-visible {
    @include foco;
  }
}

.hero-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
  gap: clamp(28px, 4vw, 56px);
  align-items: start;

  &.sin-afiche {
    grid-template-columns: minmax(0, 760px);
  }
}

.cartel {
  position: relative;
  display: grid;
  place-items: center;
  margin: 0;
  min-height: 320px;
  max-height: 82vh;
  overflow: hidden;
  border-radius: var(--r-lg);
  background: var(--noche);
  box-shadow: var(--sombra-alta);
  cursor: zoom-in;

  /* el mismo afiche, desenfocado, rellena los costados sin recortar el original */
  &::before {
    content: '';
    position: absolute;
    inset: -30px;
    background: var(--fondo) center / cover no-repeat;
    filter: blur(28px) brightness(0.5) saturate(1.2);
  }

  img {
    position: relative;
    max-width: 100%;
    max-height: 82vh;
    object-fit: contain;
  }

  &:hover .ampliar {
    opacity: 1;
  }
}

.ampliar {
  position: absolute;
  bottom: 14px;
  left: 50%;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: var(--r-full);
  background: rgba(22, 32, 46, 0.78);
  color: #fff;
  font-size: var(--t-xs);
  font-weight: 600;
  white-space: nowrap;
  transform: translateX(-50%);
  opacity: 0.85;
  transition: opacity var(--transicion);
}

/* la info acompaña al afiche mientras se scrollea */
.info {
  position: sticky;
  top: 96px;
}

.etiqueta {
  display: inline-block;
  margin-bottom: 14px;
  padding: 4px 11px;
  border-radius: var(--r-full);
  background: var(--acento);
  color: var(--sobre-foto);
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

h1 {
  @include display(800);
  margin: 0 0 26px;
  font-size: clamp(1.6rem, 1.1rem + 1.6vw, 2.5rem);
  line-height: 1.15;
  text-wrap: balance;
  overflow-wrap: anywhere;
}

.cuando {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  padding: 16px;
  border: 1px solid var(--borde);
  border-radius: var(--r-md);
  background: var(--blanco);
}

.hoja {
  display: grid;
  flex-shrink: 0;
  width: 66px;
  overflow: hidden;
  border-radius: var(--r-sm);
  background: var(--blanco);
  box-shadow: var(--sombra);
  text-align: center;
}

.hoja-mes {
  padding: 3px 0;
  background: var(--acento);
  color: var(--sobre-foto);
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.hoja-dia {
  @include display(800);
  padding: 5px 0 7px;
  font-size: 1.85rem;
  line-height: 1;
}

.cuando-texto {
  display: grid;
  gap: 4px;

  strong {
    font-size: var(--t-md);
  }

  span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--tinta-suave);
    font-size: var(--t-sm);
    font-weight: 600;

    svg {
      flex-shrink: 0;
    }
  }
}

.datos {
  display: grid;
  gap: 16px;
  margin: 0 0 28px;
  padding: 0;
  list-style: none;

  li {
    display: flex;
    align-items: flex-start;
    gap: 12px;

    > svg {
      flex-shrink: 0;
      margin-top: 1px;
      color: var(--acento);
    }

    > span {
      display: grid;
      gap: 1px;
    }
  }

  .sub {
    color: var(--tinta-suave);
    font-size: var(--t-sm);
  }
}

.organizador {
  color: var(--rojo);
  font-weight: 700;
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

.acciones {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.accion {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 11px 18px;
  border: 1px solid var(--borde-fuerte);
  border-radius: var(--r-full);
  background: var(--blanco);
  color: var(--tinta);
  font: inherit;
  font-size: var(--t-sm);
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
  transition: all var(--transicion);

  &:hover {
    border-color: var(--tinta);
  }

  &.principal {
    border-color: var(--rojo);
    background: var(--rojo);
    color: #fff;

    &:hover {
      border-color: var(--rojo-hondo);
      background: var(--rojo-hondo);
    }
  }

  &:focus-visible {
    @include foco;
  }
}

/* ---------- descripción + columna lateral ---------- */
.cuerpo {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 340px;
  gap: 28px;
  align-items: start;
  /* aire entre la cabecera (afiche + datos) y la descripción */
  padding-top: clamp(32px, 4vw, 56px);

  /* sin descripción: los bloques prácticos en fila */
  &.sin-descripcion {
    grid-template-columns: minmax(0, 1fr);

    .lateral {
      position: static;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    }
  }
}

.acerca {
  padding: clamp(24px, 3vw, 40px);
  border: 1px solid var(--borde);
  border-radius: var(--r-lg);
  background: var(--blanco);

  h2 {
    @include display(800);
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0 0 20px;
    font-size: var(--t-lg);

    /* barrita del color de la disciplina */
    &::before {
      content: '';
      width: 5px;
      height: 1.1em;
      border-radius: var(--r-full);
      background: var(--acento);
    }
  }
}

/* el HTML viene del editor del panel: se le da ritmo de lectura */
.descripcion {
  max-width: 68ch;
  color: var(--tinta);
  font-size: 1.05rem;
  line-height: 1.8;
  overflow-wrap: anywhere;

  :deep(p),
  :deep(div) {
    margin: 0 0 0.55em;
  }

  :deep(b),
  :deep(strong) {
    font-weight: 700;
  }

  :deep(a) {
    color: var(--rojo);
    font-weight: 600;
    text-underline-offset: 3px;
  }

  :deep(ul),
  :deep(ol) {
    margin: 0 0 0.9em;
    padding-left: 1.3em;
  }

  :deep(li) {
    margin: 0.25em 0;
  }

  :deep(blockquote) {
    margin: 0 0 0.9em;
    padding: 4px 0 4px 16px;
    border-left: 3px solid var(--acento);
    color: var(--tinta-suave);
  }
}

.lateral {
  display: grid;
  gap: 16px;
  position: sticky;
  top: 96px;
}

.bloque {
  padding: 20px;
  border: 1px solid var(--borde);
  border-radius: var(--r-md);
  background: var(--blanco);

  h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 12px;
    color: var(--tinta-suave);
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;

    svg {
      color: var(--acento);
    }
  }

  .sub {
    margin: 2px 0 0;
    color: var(--tinta-suave);
    font-size: var(--t-sm);
  }
}

.bloque-titulo {
  display: block;
  margin: 0;
  font-size: var(--t-md);
  font-weight: 700;
}

.mapa {
  margin: 14px 0 12px;
  aspect-ratio: 4 / 3;
  overflow: hidden;
  border: 1px solid var(--borde);
  border-radius: var(--r-sm);

  iframe {
    display: block;
    width: 100%;
    height: 100%;
    border: 0;
  }
}

.mapa-cargar {
  display: grid;
  place-content: center;
  justify-items: center;
  gap: 4px;
  width: 100%;
  height: 100%;
  border: 0;
  /* cuadrícula suave que sugiere un plano */
  background:
    linear-gradient(var(--borde) 1px, transparent 1px) 0 0 / 28px 28px,
    linear-gradient(90deg, var(--borde) 1px, transparent 1px) 0 0 / 28px 28px,
    var(--papel);
  color: var(--tinta);
  font: inherit;
  font-weight: 700;
  cursor: pointer;

  svg {
    color: var(--acento);
  }

  small {
    color: var(--tinta-suave);
    font-size: var(--t-xs);
    font-weight: 500;
  }

  &:hover {
    background-color: var(--blanco);
  }

  &:focus-visible {
    @include foco;
  }
}

.enlace {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--rojo);
  font-size: var(--t-sm);
  font-weight: 700;
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

.acciones.columna {
  flex-direction: column;
  margin-top: 14px;

  .accion {
    justify-content: center;
  }
}

.accion.ancho {
  justify-content: center;
  width: 100%;
  margin-top: 14px;
}

/* ---------- más eventos ---------- */
.mas {
  margin-top: 56px;
  padding-top: 40px;
  border-top: 1px solid var(--borde);
}

.mas-cabecera {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 20px;

  h2 {
    @include display(800);
    margin: 0;
    font-size: var(--t-lg);
  }
}

.ver-todo {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--rojo);
  font-weight: 700;
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

.grilla {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
  gap: 20px;
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
    transform: translateY(-3px);
    box-shadow: var(--sombra);
  }

  &:hover .card-img img {
    transform: scale(1.04);
  }

  &:focus-visible {
    @include foco;
  }
}

/* en la grilla el afiche es una vista previa: se puede recortar */
.card-img {
  position: relative;
  aspect-ratio: 4 / 3;
  overflow: hidden;
  background: linear-gradient(135deg, var(--noche), color-mix(in srgb, var(--acento) 70%, var(--noche)));

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
  }
}

.card-fecha {
  position: absolute;
  top: 10px;
  left: 10px;
  display: grid;
  justify-items: center;
  min-width: 48px;
  padding: 4px 8px 6px;
  border-radius: var(--r-sm);
  background: var(--blanco);
  box-shadow: var(--sombra);
  font-size: 0.66rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;

  b {
    @include display(800);
    font-size: 1.35rem;
    letter-spacing: 0;
    line-height: 1.1;
  }
}

.card-cuerpo {
  display: grid;
  gap: 4px;
  padding: 14px 16px 16px;
}

.card-disciplina {
  color: var(--acento);
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.card-titulo {
  display: -webkit-box;
  overflow: hidden;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  font-weight: 700;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.card-meta {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  color: var(--tinta-suave);
  font-size: var(--t-sm);
}

/* ---------- visor ---------- */
.visor {
  position: relative;
  display: grid;
  place-items: center;
  padding: 24px;
  background: rgba(10, 14, 20, 0.95);
  cursor: zoom-out;

  img {
    max-width: 100%;
    max-height: calc(100vh - 48px);
    object-fit: contain;
  }
}

.cerrar {
  position: absolute;
  top: 14px;
  right: 14px;
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  border: 0;
  border-radius: var(--r-full);
  background: rgba(255, 255, 255, 0.14);
  color: #fff;
  cursor: pointer;

  &:focus-visible {
    @include foco;
  }
}

/* ---------- celular y tablet: una columna ---------- */
@media (max-width: 899px) {
  .hero {
    padding-top: 18px;
  }

  .hero-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .cartel {
    max-height: 70vh;

    img {
      max-height: 70vh;
    }
  }

  .info {
    position: static;
  }

  .accion {
    flex: 1 1 auto;
    justify-content: center;
  }

  .cuerpo {
    grid-template-columns: minmax(0, 1fr);
  }

  .lateral {
    position: static;
  }
}
</style>
