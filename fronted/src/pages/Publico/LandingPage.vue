<template>
  <q-page class="landing publico">
    <!-- ══════════ ① PORTADA ══════════ -->
    <section class="portada">
      <div class="portada-fondo">
        <Transition name="fundido">
          <img
            v-if="fotoActual"
            :key="fotoActual.id"
            :src="fotoActual.imagen_url"
            :alt="fotoActual.descripcion"
          />
        </Transition>
        <div class="portada-velo" />
      </div>

      <div class="portada-cuerpo">
        <p class="portada-sello">
          <span class="sello-linea" />
          {{ INSTITUCION.lema }}
        </p>

        <h1 class="portada-titulo">
          Conoce a quienes<br />
          <em>hacen la fiesta</em>
        </h1>

        <p class="portada-bajada">
          El registro vivo de artistas, comisiones y familias culturales de
          {{ INSTITUCION.ciudad }}.
        </p>

        <!-- buscador segmentado: a quién busco + en qué disciplina -->
        <form class="buscador" @submit.prevent="buscarArtistas">
          <div class="segmento">
            <label for="q-artista">Artista</label>
            <input
              id="q-artista"
              v-model="termino"
              type="search"
              placeholder="Nombre, familia o disciplina"
              autocomplete="off"
              @focus="sugerenciasVisibles = true"
              @blur="ocultarSugerencias"
            />
          </div>

          <span class="divisor" aria-hidden="true" />

          <div class="segmento segmento-select">
            <label for="q-disciplina">Disciplina</label>
            <div class="select-caja">
              <select id="q-disciplina" v-model="grupoElegido">
                <option value="">Todas</option>
                <option v-for="g in grupos" :key="g.cod_grupo" :value="g.cod_grupo">
                  {{ comisionDe(g.cod_grupo).corto }}
                </option>
              </select>
              <ChevronDown :size="16" />
            </div>
          </div>

          <button type="submit" class="buscador-btn" aria-label="Buscar">
            <Search :size="20" />
          </button>

          <!-- sugerencias al enfocar, como en los buscadores de viaje -->
          <Transition name="caer">
            <div v-if="sugerenciasVisibles" class="sugerencias">
              <p class="sugerencias-titulo">Búsquedas frecuentes</p>
              <ul>
                <li v-for="a in atajos" :key="a">
                  <button type="button" @mousedown.prevent="buscarRapido(a)">
                    <TrendingUp :size="15" />
                    {{ a }}
                  </button>
                </li>
              </ul>
            </div>
          </Transition>
        </form>
      </div>

      <button class="portada-scroll" aria-label="Ver más" @click="irA('comisiones')">
        <span>Descubre</span>
        <ChevronDown :size="18" />
      </button>

      <div v-if="fotos.length > 1" class="portada-puntos">
        <button
          v-for="(f, i) in fotos"
          :key="f.id"
          :class="{ activo: i === fotoIndex }"
          :aria-label="`Fotografía ${i + 1}`"
          @click="fotoIndex = i"
        />
      </div>
    </section>

    <!-- ══════════ ② FRANJA DE DANZAS ══════════ -->
    <div class="franja" aria-hidden="true">
      <div class="franja-pista">
        <span v-for="n in 2" :key="n" class="franja-grupo">
          <template v-for="d in danzas" :key="`${n}-${d}`">
            <span class="franja-item">{{ d }}</span>
            <span class="franja-sep">◆</span>
          </template>
        </span>
      </div>
    </div>

    <!-- ══════════ ③ COMISIONES — acordeón ══════════ -->
    <section id="comisiones" class="comisiones">
      <span class="cenefa cenefa-arriba" aria-hidden="true" />

      <div class="seccion">
        <header v-revelar class="seccion-head">
          <div>
            <p class="kicker">Explora por disciplina</p>
            <h2 class="titulo">Ocho comisiones,<br />una sola fiesta</h2>
          </div>

          <div class="head-lado">
            <p class="subtitulo">
              Desde las bandas de sikuris hasta quienes bordan los trajes de luces: cada comisión
              reúne el trabajo de los artistas de su disciplina.
            </p>
            <p class="head-dato">
              <strong>{{ grupos.length || 8 }}</strong> disciplinas
              <span aria-hidden="true">·</span>
              <strong>{{ stats.consejeros }}</strong> artistas
            </p>
          </div>
        </header>

        <!-- Las ocho caben en una sola vista: la activa se abre y muestra su
             foto, las demás quedan como lomos con el nombre en vertical. -->
        <div
          v-revelar
          class="acordeon"
          @mouseleave="acordeonPausado = false"
          @mouseenter="acordeonPausado = true"
        >
          <template v-if="grupos.length">
            <router-link
              v-for="(g, i) in grupos"
              :key="g.cod_grupo"
              :to="{ name: 'ConsejerosPublico', query: { grupo: g.cod_grupo } }"
              class="hoja"
              :class="{ abierta: i === hojaActiva }"
              :style="{ '--acento': comisionDe(g.cod_grupo).color }"
              :aria-label="`${comisionDe(g.cod_grupo).corto}: ${g.consejeros} artistas`"
              @mouseenter="hojaActiva = i"
              @focus="hojaActiva = i"
            >
              <img v-if="g.imagen_url" :src="g.imagen_url" alt="" loading="lazy" />
              <div v-else class="hoja-sin-foto">
                <component :is="iconoDe(g.cod_grupo)" :size="30" />
              </div>

              <span class="hoja-velo" />

              <!-- lomo: lo que se ve cuando está cerrada -->
              <span class="hoja-lomo">
                <span class="hoja-num">{{ String(i + 1).padStart(2, '0') }}</span>
                <span class="hoja-vertical">{{ comisionDe(g.cod_grupo).corto }}</span>
              </span>

              <!-- contenido: lo que aparece al abrirse -->
              <span class="hoja-cara">
                <span class="hoja-icono">
                  <component :is="iconoDe(g.cod_grupo)" :size="18" />
                </span>
                <span class="hoja-nombre">{{ comisionDe(g.cod_grupo).corto }}</span>
                <span class="hoja-dato">
                  {{ g.consejeros }} {{ g.consejeros === 1 ? 'artista' : 'artistas' }}
                </span>
                <span class="hoja-cta">Ver artistas <ArrowRight :size="15" /></span>
              </span>
            </router-link>
          </template>

          <div v-else class="acordeon-cargando">
            <div v-for="n in 8" :key="n" class="esqueleto-hoja" />
          </div>
        </div>
      </div>

      <span class="cenefa cenefa-abajo" aria-hidden="true" />
    </section>

    <!-- ══════════ ④ ARTISTAS ══════════ -->
    <section v-if="artistas.length" class="artistas">
      <div class="seccion">
        <header v-revelar class="seccion-head">
          <div>
            <p class="kicker">Portadores de tradición</p>
            <h2 class="titulo">Artistas del registro</h2>
          </div>

          <router-link :to="{ name: 'ConsejerosPublico' }" class="btn-linea">
            Ver los {{ stats.consejeros }} artistas <ArrowRight :size="16" />
          </router-link>
        </header>

        <div class="artistas-layout">
          <router-link
            v-if="destacado"
            v-revelar
            :to="{ name: 'ConsejeroDetallePublico', params: { slug: destacado.slug } }"
            class="destacado"
            :style="{ '--acento': comisionDe(destacado.cod_grupo).color }"
          >
            <img v-if="destacado.imagen_url" :src="destacado.imagen_url" alt="" loading="lazy" />
            <span class="destacado-velo" />
            <div class="destacado-texto">
              <span class="destacado-etiqueta">{{ comisionDe(destacado.cod_grupo).corto }}</span>
              <h3>{{ destacado.nombre_completo }}</h3>
              <p>{{ destacado.comision }}</p>
              <span class="destacado-cta">Ver su trabajo <ArrowRight :size="16" /></span>
            </div>
          </router-link>

          <div class="artistas-lista">
            <ArtistaTarjeta
              v-for="(a, i) in acompanan"
              :key="a.id"
              v-revelar="i * 60"
              :artista="a"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ ⑤ CANDELARIA — pantalla completa, en claro ══════════ -->
    <section class="candelaria">
      <span class="cenefa cenefa-arriba" aria-hidden="true" />

      <div class="candelaria-inner">
        <div v-revelar class="candelaria-texto">
          <p class="kicker">Febrero en {{ INSTITUCION.ciudad }}</p>
          <h2>La Candelaria es <em>Patrimonio</em> de la Humanidad</h2>
          <p class="parrafo">
            Cada febrero más de doscientos conjuntos salen a las calles. La UNESCO la declaró
            Patrimonio Cultural Inmaterial en 2014 — para nosotros es el trabajo de todo un año.
          </p>

          <dl class="datos">
            <div>
              <dt>2014</dt>
              <dd>Declaratoria UNESCO</dd>
            </div>
            <div>
              <dt>+200</dt>
              <dd>Conjuntos en la parada</dd>
            </div>
            <div>
              <dt>18</dt>
              <dd>Días de festividad</dd>
            </div>
          </dl>

          <router-link :to="{ name: 'ConsejerosPublico' }" class="btn-rojo">
            Conocer a la comunidad <ArrowRight :size="17" />
          </router-link>
        </div>

        <figure v-revelar="120" class="candelaria-foto">
          <img
            :src="fotoCandelaria"
            alt="Danzantes de la Festividad de la Virgen de la Candelaria junto al lago Titicaca"
            loading="lazy"
          />
        </figure>
      </div>

      <span class="cenefa cenefa-abajo" aria-hidden="true" />
    </section>

    <!-- ══════════ ⑥ GALERÍA ══════════ -->
    <section v-if="destacadas.length" class="galeria-zona">
      <div class="seccion">
        <header v-revelar class="seccion-head">
          <div>
            <p class="kicker">Galería</p>
            <h2 class="titulo">Lo que estamos haciendo</h2>
          </div>
          <router-link :to="{ name: 'ConsejerosPublico' }" class="btn-linea">
            Ver todo <ArrowRight :size="16" />
          </router-link>
        </header>

        <div class="mosaico">
          <router-link
            v-for="(act, i) in destacadas"
            :key="act.id"
            v-revelar="i * 50"
            :to="{ name: 'ConsejeroDetallePublico', params: { slug: act.persona_slug } }"
            class="mosaico-item"
            :class="`m-${i}`"
          >
            <img :src="act.imagen_url" :alt="act.descripcion || ''" loading="lazy" />
            <span class="mosaico-pie">{{ act.persona_nombre }}</span>
          </router-link>
        </div>
      </div>
    </section>

    <!-- ══════════ ⑦ CIERRE ══════════ -->
    <section class="cierre">
      <img src="/images/wall3.jpg" alt="" loading="lazy" />
      <span class="cierre-velo" />
      <span class="cenefa cenefa-arriba" aria-hidden="true" />

      <div v-revelar class="cierre-texto">
        <p class="cierre-sello">Registro abierto</p>
        <h2>¿Eres artista<br />en {{ INSTITUCION.ciudad }}?</h2>
        <p class="cierre-bajada">
          Súmate al registro y publica tu trabajo para que la comunidad y quienes visitan
          {{ INSTITUCION.ciudad }} puedan encontrarte.
        </p>

        <router-link :to="{ name: 'Login' }" class="cierre-btn">
          Acceder al registro <ArrowRight :size="18" />
        </router-link>

        <p v-if="stats.consejeros" class="cierre-dato">
          <strong>{{ stats.consejeros }}</strong> artistas ya forman parte
        </p>
      </div>
    </section>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowRight,
  ChevronDown,
  Search,
  TrendingUp,
  Sparkles,
  Music,
  PersonStanding,
  Drama,
  BookOpen,
  Shirt,
  Video,
  Palette,
  Megaphone,
} from 'lucide-vue-next'
import PersonaPublicaService from '@/services/PersonaPublicaService'
import { INSTITUCION, FOTOS_CARTEL, comisionDe } from '@/config/institucion'
import ArtistaTarjeta from '@/components/ArtistaTarjeta.vue'

const router = useRouter()

const stats = ref({ consejeros: 0, comisiones: 0, grupos: 0, actividades: 0 })
const grupos = ref([])
const destacadas = ref([])
const artistas = ref([])
const termino = ref('')
const grupoElegido = ref('')
const sugerenciasVisibles = ref(false)

const danzas = [
  'Sikuris',
  'Diablada',
  'Morenada',
  'Caporales',
  'Llamerada',
  'Waca Waca',
  'Kullawada',
  'Tinkus',
  'Pinkillada',
  'Estudiantina',
  'Rey Moreno',
  'Tobas',
]

const atajos = ['Sikuris', 'Bordadores', 'Danza de luces', 'Mascareros', 'Estudiantina']

const iconosPorGrupo = {
  '01': Music,
  '02': PersonStanding,
  '03': Drama,
  '04': BookOpen,
  '05': Shirt,
  '06': Video,
  '07': Palette,
  '08': Megaphone,
}

function iconoDe(codGrupo) {
  return iconosPorGrupo[codGrupo] || Sparkles
}

function buscarArtistas() {
  const query = {}
  if (termino.value.trim()) query.buscar = termino.value.trim()
  if (grupoElegido.value) query.grupo = grupoElegido.value
  router.push({ name: 'ConsejerosPublico', query })
}

function buscarRapido(texto) {
  termino.value = texto
  sugerenciasVisibles.value = false
  buscarArtistas()
}

// el blur llega antes que el click de la sugerencia: se espera un instante
function ocultarSugerencias() {
  setTimeout(() => (sugerenciasVisibles.value = false), 120)
}

const destacado = computed(() => artistas.value[0] || null)
const acompanan = computed(() => artistas.value.slice(1, 7))

// ---------- portada ----------
const fotos = computed(() => FOTOS_CARTEL)
const fotoIndex = ref(0)
const fotoActual = computed(() => fotos.value[fotoIndex.value] || null)
const fotoCandelaria = '/images/wallpaper_puno.png'

let temporizador

function rotarFotos() {
  clearInterval(temporizador)
  if (fotos.value.length < 2) return
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  temporizador = setInterval(() => {
    fotoIndex.value = (fotoIndex.value + 1) % fotos.value.length
  }, 7000)
}

// ---------- acordeón de comisiones ----------
// La hoja abierta va rotando sola; se detiene con el mouse encima o cuando
// alguien navega con el teclado, y no arranca si el sistema pide menos
// animación (ahí queda abierta la primera).
const hojaActiva = ref(0)
const acordeonPausado = ref(false)
let acordeonTimer

function girarAcordeon() {
  clearInterval(acordeonTimer)
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  acordeonTimer = setInterval(() => {
    if (acordeonPausado.value || !grupos.value.length) return
    hojaActiva.value = (hojaActiva.value + 1) % grupos.value.length
  }, 3600)
}

function irA(id) {
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}

onBeforeUnmount(() => {
  clearInterval(temporizador)
  clearInterval(acordeonTimer)
})

onMounted(async () => {
  const data = await PersonaPublicaService.getPortada()
  stats.value = data.stats
  grupos.value = data.grupos
  destacadas.value = data.destacadas
  artistas.value = data.artistas || []
  rotarFotos()
  await nextTick()
  girarAcordeon()
})
</script>

<style scoped lang="scss">
.landing {
  background: var(--papel);
  color: var(--tinta);
  font-family: var(--fuente-texto);
}

/* ═══════════ piezas compartidas ═══════════ */
.kicker {
  @include kicker;
  margin: 0 0 12px;
}

.titulo {
  @include display(800);
  font-size: var(--t-xl);
  line-height: 1;
  color: var(--tinta);
  margin: 0;
}

.subtitulo {
  color: var(--tinta-suave);
  font-size: var(--t-md);
  line-height: 1.7;
  max-width: 46ch;
  margin: 0;
}

.seccion {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 0 var(--gutter);
}

.seccion-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 28px;
  margin-bottom: 40px;
  flex-wrap: wrap;
}

.head-lado {
  display: flex;
  align-items: flex-end;
  gap: 24px;
  flex-wrap: wrap;
}

.btn-linea,
.btn-rojo {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  font-family: var(--fuente-texto);
  font-size: var(--t-sm);
  font-weight: 700;
  text-decoration: none;
  padding: 14px 26px;
  border-radius: var(--r-full);
  white-space: nowrap;
  cursor: pointer;
  transition: all var(--transicion);

  &:focus-visible {
    @include foco;
  }
}

.btn-linea {
  border: 1px solid var(--borde-fuerte);
  color: var(--tinta);
  background: var(--blanco);

  &:hover {
    border-color: var(--rojo);
    color: var(--rojo);
  }
}

.btn-rojo {
  background: var(--rojo);
  color: #fff;
  border: none;

  &:hover {
    background: var(--rojo-hondo);
    transform: translateY(-2px);
  }
}

/* ═══════════ ① PORTADA ═══════════ */
.portada {
  position: relative;
  min-height: calc(100vh - 66px);
  min-height: calc(100svh - 66px);
  display: flex;
  align-items: center;
  padding: clamp(64px, 8vw, 96px) var(--gutter) clamp(104px, 13vh, 150px);
  overflow: hidden;
}

.portada-fondo {
  position: absolute;
  inset: 0;

  img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
}

.portada-velo {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(
      to right,
      rgba(22, 32, 46, 0.88) 0%,
      rgba(22, 32, 46, 0.5) 52%,
      rgba(22, 32, 46, 0.2) 100%
    ),
    linear-gradient(to top, rgba(22, 32, 46, 0.62) 0%, transparent 42%);
}

.fundido-enter-active,
.fundido-leave-active {
  transition: opacity 1.2s ease;
}

.fundido-enter-from,
.fundido-leave-to {
  opacity: 0;
}

.portada-cuerpo {
  position: relative;
  width: 100%;
  max-width: var(--ancho);
  margin: 0 auto;
}

.portada-sello {
  @include kicker(var(--oro-vivo));
  display: inline-flex;
  align-items: center;
  gap: 12px;
  margin: 0 0 20px;
}

.sello-linea {
  width: 34px;
  height: 2px;
  background: var(--oro-vivo);
}

.portada-titulo {
  @include display(900);
  font-size: var(--t-3xl);
  line-height: 0.92;
  letter-spacing: -0.025em;
  color: var(--sobre-foto);
  margin: 0 0 20px;
  max-width: 15ch;
  text-shadow: 0 2px 40px rgba(22, 32, 46, 0.35);

  em {
    font-style: italic;
    color: var(--oro-vivo);
    font-variation-settings:
      'SOFT' 40,
      'WONK' 1;
  }
}

.portada-bajada {
  color: rgba(253, 251, 247, 0.9);
  font-size: clamp(1rem, 0.9rem + 0.5vw, 1.25rem);
  line-height: 1.6;
  max-width: 46ch;
  margin: 0 0 36px;
}

/* ── buscador segmentado ── */
.buscador {
  position: relative;
  display: flex;
  align-items: stretch;
  gap: 4px;
  background: var(--blanco);
  border-radius: var(--r-full);
  padding: 8px 8px 8px 24px;
  max-width: 660px;
  box-shadow: var(--sombra-alta);
}

.segmento {
  display: flex;
  flex-direction: column;
  justify-content: center;
  flex: 1;
  min-width: 0;
  padding: 4px 0;

  label {
    @include kicker(var(--tinta));
    font-size: 0.6rem;
    letter-spacing: 0.14em;
    margin-bottom: 2px;
  }

  input,
  select {
    border: none;
    background: none;
    outline: none;
    font-family: var(--fuente-texto);
    font-size: var(--t-base);
    color: var(--tinta);
    width: 100%;
    min-width: 0;
    padding: 0;
    cursor: pointer;

    &::placeholder {
      color: var(--tinta-tenue);
    }
  }
}

.segmento-select {
  flex: 0 0 34%;
}

.select-caja {
  display: flex;
  align-items: center;
  gap: 6px;

  select {
    appearance: none;
    cursor: pointer;
  }

  svg {
    color: var(--tinta-suave);
    flex: none;
  }
}

.divisor {
  width: 1px;
  background: var(--borde);
  margin: 6px 12px;
  flex: none;
}

.buscador-btn {
  flex: none;
  display: grid;
  place-items: center;
  width: 56px;
  height: 56px;
  border: none;
  border-radius: 50%;
  background: var(--rojo);
  color: #fff;
  cursor: pointer;
  transition:
    background var(--transicion),
    transform var(--transicion);

  &:hover {
    background: var(--rojo-hondo);
    transform: scale(1.05);
  }

  &:focus-visible {
    @include foco;
  }
}

.sugerencias {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;
  right: 0;
  background: var(--blanco);
  border: 1px solid var(--borde);
  border-radius: var(--r-md);
  box-shadow: var(--sombra-alta);
  padding: 14px 10px 10px;
  z-index: 20;

  ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }

  button {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: var(--crema);
    border: none;
    border-radius: var(--r-full);
    color: var(--tinta);
    font-family: var(--fuente-texto);
    font-size: var(--t-sm);
    font-weight: 600;
    padding: 9px 15px;
    cursor: pointer;
    transition: background var(--transicion);

    svg {
      color: var(--rojo);
    }

    &:hover {
      background: var(--crema-hondo);
    }
  }
}

.sugerencias-titulo {
  @include kicker(var(--tinta-suave));
  font-size: 0.6rem;
  margin: 0 8px 10px;
}

.caer-enter-active,
.caer-leave-active {
  transition:
    opacity 0.22s ease,
    transform 0.22s ease;
}

.caer-enter-from,
.caer-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.portada-scroll {
  position: absolute;
  left: 50%;
  bottom: 52px;
  transform: translateX(-50%);
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  background: none;
  border: none;
  cursor: pointer;
  color: rgba(253, 251, 247, 0.82);
  font-family: var(--fuente-texto);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.16em;
  text-transform: uppercase;

  svg {
    animation: flotar 2.2s ease-in-out infinite;
  }

  &:hover {
    color: var(--oro-vivo);
  }

  &:focus-visible {
    @include foco;
  }
}

@keyframes flotar {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(5px);
  }
}

.portada-puntos {
  position: absolute;
  right: var(--gutter);
  bottom: 54px;
  display: flex;
  gap: 8px;
  z-index: 2;

  button {
    width: 26px;
    height: 4px;
    padding: 0;
    border: none;
    border-radius: var(--r-full);
    background: rgba(253, 251, 247, 0.4);
    cursor: pointer;
    transition: all var(--transicion);

    &.activo {
      background: var(--oro-vivo);
      width: 42px;
    }

    &:focus-visible {
      @include foco;
    }
  }
}

@media (max-width: 760px) {
  .buscador {
    flex-wrap: wrap;
    border-radius: var(--r-lg);
    padding: 16px;
    gap: 10px;
  }

  .segmento,
  .segmento-select {
    flex: 1 1 100%;
    border: 1px solid var(--borde);
    border-radius: var(--r-sm);
    padding: 8px 12px;
  }

  .divisor {
    display: none;
  }

  .buscador-btn {
    width: 100%;
    height: 48px;
    border-radius: var(--r-sm);
  }

  .portada-puntos,
  .portada-scroll {
    display: none;
  }
}

@media (max-height: 680px) {
  .portada-scroll {
    display: none;
  }
}

/* ═══════════ ② FRANJA ═══════════ */
.franja {
  background: var(--tinta);
  color: var(--sobre-foto);
  padding: 14px 0;
  overflow: hidden;
  white-space: nowrap;
}

.franja-pista {
  display: inline-flex;
  animation: correr 48s linear infinite;
}

.franja-grupo {
  display: inline-flex;
  align-items: center;
}

.franja-item {
  @include display(700);
  font-size: 1.05rem;
  padding: 0 18px;
  font-style: italic;
}

.franja-sep {
  font-size: 0.5rem;
  color: var(--oro-vivo);
}

@keyframes correr {
  from {
    transform: translateX(0);
  }
  to {
    transform: translateX(-50%);
  }
}

@media (prefers-reduced-motion: reduce) {
  .franja-pista {
    animation: none;
  }

  .portada-scroll svg {
    animation: none;
  }
}

/* ═══════════ ③ COMISIONES ═══════════ */
.comisiones {
  position: relative;
  padding: calc(var(--seccion-y) + 20px) 0;
  background-color: var(--papel);
  background-image: var(--patron);
  /* el velo del encabezado se extiende hacia los lados: se recorta acá para
     que no genere scroll horizontal en pantallas chicas */
  overflow: hidden;
}

/* el encabezado se apoya en un velo del color del papel: el aguayo sigue ahí,
   pero deja de competir con el texto */
.comisiones .seccion-head {
  position: relative;
  z-index: 1;

  &::before {
    content: '';
    position: absolute;
    inset: -28px -20px;
    background: radial-gradient(ellipse at center, var(--papel) 56%, transparent 100%);
    z-index: -1;
  }
}

.head-dato {
  font-size: var(--t-sm);
  color: var(--tinta-suave);
  margin: 0;
  white-space: nowrap;

  strong {
    @include display(800);
    font-size: 1.5rem;
    color: var(--rojo);
    margin-right: 4px;
  }

  span {
    margin: 0 8px;
    color: var(--borde-fuerte);
  }
}

/* ── acordeón ── */
.acordeon {
  display: flex;
  gap: 10px;
  height: clamp(320px, 46vh, 440px);
}

.hoja {
  position: relative;
  flex: 1 1 0;
  min-width: 0;
  border-radius: var(--r-lg);
  overflow: hidden;
  text-decoration: none;
  background: var(--crema);
  transition: flex-grow 0.65s cubic-bezier(0.22, 0.61, 0.36, 1);

  > img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  &:focus-visible {
    @include foco;
  }

  &.abierta {
    flex-grow: 5;
  }
}

.hoja-sin-foto {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  color: var(--tinta-tenue);
}

.hoja-velo {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgba(22, 32, 46, 0.94) 0%,
    rgba(22, 32, 46, 0.62) 40%,
    rgba(22, 32, 46, 0.34) 100%
  );
  transition: background 0.5s ease;
}

.hoja.abierta .hoja-velo {
  background: linear-gradient(
    to top,
    rgba(22, 32, 46, 0.92) 2%,
    rgba(22, 32, 46, 0.42) 46%,
    rgba(22, 32, 46, 0.1) 100%
  );
}

/* lomo: el nombre en vertical mientras la hoja está cerrada */
.hoja-lomo {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  padding: 18px 0;
  opacity: 1;
  transition: opacity 0.35s ease;
}

.hoja.abierta .hoja-lomo {
  opacity: 0;
  pointer-events: none;
}

.hoja-num {
  @include display(900);
  font-size: 1.1rem;
  color: rgba(253, 251, 247, 0.5);
}

.hoja-vertical {
  @include display(700);
  font-size: 1.05rem;
  color: var(--sobre-foto);
  writing-mode: vertical-rl;
  transform: rotate(180deg);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-height: 74%;
}

/* cara: lo que aparece al abrirse */
.hoja-cara {
  position: absolute;
  inset: auto 0 0 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  padding: 24px;
  opacity: 0;
  transform: translateY(10px);
  transition:
    opacity 0.45s ease 0.12s,
    transform 0.45s ease 0.12s;
}

.hoja.abierta .hoja-cara {
  opacity: 1;
  transform: none;
}

.hoja-icono {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--acento);
  color: #fff;
  margin-bottom: 14px;
}

.hoja-nombre {
  @include display(800);
  font-size: clamp(1.4rem, 2vw, 1.85rem);
  line-height: 1.04;
  color: var(--sobre-foto);
  margin-bottom: 6px;
  white-space: nowrap;
}

.hoja-dato {
  font-size: var(--t-sm);
  color: rgba(253, 251, 247, 0.82);
  margin-bottom: 16px;
  white-space: nowrap;
}

.hoja-cta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: var(--t-sm);
  font-weight: 700;
  color: var(--noche);
  background: var(--oro-vivo);
  padding: 11px 20px;
  border-radius: var(--r-full);
  white-space: nowrap;
  transition: transform var(--transicion);
}

.hoja:hover .hoja-cta {
  transform: translateX(3px);
}

.acordeon-cargando {
  display: flex;
  gap: 10px;
  width: 100%;
}

.esqueleto-hoja {
  flex: 1;
  border-radius: var(--r-lg);
  background: linear-gradient(100deg, var(--crema) 30%, var(--crema-hondo) 50%, var(--crema) 70%);
  background-size: 220% 100%;
  animation: brillo 1.5s ease-in-out infinite;
}

@keyframes brillo {
  from {
    background-position: 140% 0;
  }
  to {
    background-position: -40% 0;
  }
}

/* en pantallas chicas el acordeón se acuesta: las hojas se apilan y la activa
   crece hacia abajo */
@media (max-width: 900px) {
  .acordeon {
    flex-direction: column;
    height: auto;
    gap: 8px;
  }

  .hoja {
    flex: none;
    height: 74px;
    transition: height 0.55s cubic-bezier(0.22, 0.61, 0.36, 1);

    &.abierta {
      height: 260px;
    }
  }

  .hoja-lomo {
    flex-direction: row;
    align-items: center;
    justify-content: flex-start;
    gap: 14px;
    padding: 0 20px;
  }

  .hoja-vertical {
    writing-mode: horizontal-tb;
    transform: none;
    max-height: none;
  }

  .hoja-cara {
    padding: 18px 20px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .hoja,
  .hoja-velo,
  .hoja-lomo,
  .hoja-cara {
    transition: none;
  }

  .esqueleto-hoja {
    animation: none;
  }
}

/* ═══════════ ④ ARTISTAS ═══════════ */
.artistas {
  padding: var(--seccion-y) 0;
  background: var(--crema);
  border-block: 1px solid var(--borde);
}

.artistas-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: clamp(16px, 2vw, 22px);
}

@media (min-width: 900px) {
  .artistas-layout {
    grid-template-columns: 5fr 7fr;
  }
}

.destacado {
  position: relative;
  display: block;
  min-height: 360px;
  border-radius: var(--r-lg);
  overflow: hidden;
  text-decoration: none;
  transition:
    transform var(--transicion),
    box-shadow var(--transicion);

  &:hover {
    transform: translateY(-5px);
    box-shadow: var(--sombra-alta);
  }

  &:focus-visible {
    @include foco;
  }

  > img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    position: absolute;
    inset: 0;
    transition: transform 0.9s ease;
  }

  &:hover > img {
    transform: scale(1.05);
  }
}

.destacado-velo {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgba(22, 32, 46, 0.94) 4%,
    rgba(22, 32, 46, 0.4) 46%,
    transparent 100%
  );
}

.destacado-texto {
  position: absolute;
  inset: auto 0 0 0;
  padding: clamp(22px, 3vw, 32px);

  h3 {
    @include display(800);
    font-size: clamp(1.5rem, 2.6vw, 2.1rem);
    color: var(--sobre-foto);
    margin: 0 0 6px;
    line-height: 1.05;
  }

  p {
    color: rgba(253, 251, 247, 0.85);
    font-size: var(--t-sm);
    margin: 0 0 16px;
  }
}

.destacado-etiqueta {
  display: inline-block;
  background: var(--blanco);
  color: var(--acento);
  font-size: 0.68rem;
  font-weight: 700;
  padding: 5px 12px;
  border-radius: var(--r-full);
  margin-bottom: 12px;
}

.destacado-cta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: var(--t-sm);
  font-weight: 700;
  color: var(--oro-vivo);
}

.artistas-lista {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: clamp(12px, 1.6vw, 18px);
}

@media (min-width: 620px) {
  .artistas-lista {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* ═══════════ ⑤ CANDELARIA ═══════════ */
/* Sección completa, en claro: ocupa la pantalla entera y se enmarca con dos
   cenefas de aguayo, arriba y abajo. */
.candelaria {
  position: relative;
  min-height: 100vh;
  min-height: 100svh;
  display: flex;
  align-items: center;
  background: var(--blanco);
  border-block: 1px solid var(--borde);
  padding: clamp(56px, 8vh, 96px) var(--gutter);
}

.cenefa {
  position: absolute;
  left: 0;
  right: 0;
  height: 30px;
  background-image: var(--patron-cenefa);
  /* el desplazamiento encuadra justo la franja de llamas del aguayo */
  background-position: 0 -6px;
  background-repeat: repeat-x;
}

.cenefa-arriba {
  top: 0;
}

.cenefa-abajo {
  bottom: 0;
}

.candelaria-inner {
  width: 100%;
  max-width: var(--ancho);
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr;
  gap: clamp(32px, 5vw, 64px);
  align-items: center;
}

@media (min-width: 900px) {
  .candelaria-inner {
    grid-template-columns: 1fr 1fr;
  }
}

.candelaria-texto {
  .kicker {
    color: var(--rojo);
  }

  h2 {
    @include display(800);
    font-size: var(--t-xl);
    line-height: 1.02;
    color: var(--tinta);
    margin: 0 0 20px;

    em {
      font-style: italic;
      color: var(--rojo);
      font-variation-settings:
        'SOFT' 40,
        'WONK' 1;
    }
  }
}

.parrafo {
  font-size: var(--t-md);
  line-height: 1.8;
  color: var(--tinta-suave);
  max-width: 48ch;
  margin: 0 0 32px;
}

.datos {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
  margin: 0 0 34px;
  border-top: 1px solid var(--borde);
  padding-top: 24px;

  dt {
    @include display(800);
    font-size: clamp(1.7rem, 3vw, 2.4rem);
    color: var(--rojo);
    line-height: 1;
  }

  dd {
    margin: 8px 0 0;
    font-size: var(--t-xs);
    color: var(--tinta-suave);
    line-height: 1.4;
  }
}

/* La foto se recorta con una máscara de papel arrancado: sin marco ni sombra,
   el borde mordido hace todo el trabajo. */
.candelaria-foto {
  margin: 0;
  position: relative;
  aspect-ratio: 4 / 3;
  max-height: 70svh;
  /* sin sombra ni marco: el recorte hace todo el trabajo */

  img {
    position: relative;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: 58% center;
    display: block;
    -webkit-mask: var(--rasgado-foto) center / 100% 100% no-repeat;
    mask: var(--rasgado-foto) center / 100% 100% no-repeat;
  }
}

@media (min-width: 900px) {
  .candelaria-foto {
    aspect-ratio: 5 / 6;
  }
}

/* ═══════════ ⑥ GALERÍA ═══════════ */
.galeria-zona {
  padding: var(--seccion-y) 0;
  background-color: var(--papel);
  background-image: var(--patron);
}

.mosaico {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  grid-auto-rows: 150px;
  gap: 12px;
}

@media (min-width: 760px) {
  .mosaico {
    grid-template-columns: repeat(4, 1fr);
    grid-auto-rows: 185px;
    grid-auto-flow: dense;
  }

  .m-0,
  .m-5 {
    grid-column: span 2;
    grid-row: span 2;
  }
  .m-3 {
    grid-column: span 2;
  }
  .m-4 {
    grid-row: span 2;
  }
}

.mosaico-item {
  position: relative;
  overflow: hidden;
  border-radius: var(--r-md);
  display: block;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.7s ease;
  }

  &:hover img {
    transform: scale(1.07);
  }

  &:focus-visible {
    @include foco;
  }
}

.mosaico-pie {
  position: absolute;
  inset: auto 0 0 0;
  padding: 28px 13px 12px;
  background: linear-gradient(to top, rgba(22, 32, 46, 0.92), transparent);
  color: var(--sobre-foto);
  font-size: var(--t-xs);
  font-weight: 600;
}

/* ═══════════ ⑦ CIERRE ═══════════ */
/* Una banda a sangre completa, sin caja ni tarjetas: la foto llega a los dos
   bordes de la pantalla y el mensaje va encima. */
.cierre {
  position: relative;
  min-height: clamp(420px, 62vh, 560px);
  display: flex;
  align-items: center;
  overflow: hidden;

  > img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
}

.cierre-velo {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(
      to right,
      rgba(22, 32, 46, 0.93) 0%,
      rgba(22, 32, 46, 0.6) 58%,
      rgba(22, 32, 46, 0.35) 100%
    ),
    linear-gradient(to top, rgba(22, 32, 46, 0.5) 0%, transparent 46%);
}

.cierre-texto {
  position: relative;
  width: 100%;
  max-width: var(--ancho);
  margin: 0 auto;
  padding: clamp(48px, 7vw, 80px) var(--gutter);

  h2 {
    @include display(800);
    font-size: var(--t-xl);
    line-height: 1;
    color: var(--sobre-foto);
    margin: 0 0 16px;
  }
}

.cierre-sello {
  @include kicker(var(--oro-vivo));
  display: inline-flex;
  align-items: center;
  gap: 10px;
  margin: 0 0 16px;

  &::before {
    content: '';
    width: 30px;
    height: 2px;
    background: var(--oro-vivo);
  }
}

.cierre-bajada {
  color: rgba(253, 251, 247, 0.88);
  font-size: var(--t-md);
  line-height: 1.7;
  max-width: 46ch;
  margin: 0 0 30px;
}

.cierre-btn {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: var(--oro-vivo);
  color: var(--noche);
  font-family: var(--fuente-texto);
  font-size: var(--t-base);
  font-weight: 700;
  text-decoration: none;
  padding: 16px 30px;
  border-radius: var(--r-full);
  transition:
    transform var(--transicion),
    box-shadow var(--transicion);

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 26px rgba(22, 32, 46, 0.3);
  }

  &:focus-visible {
    @include foco;
  }
}

.cierre-dato {
  margin: 22px 0 0;
  font-size: var(--t-sm);
  color: rgba(253, 251, 247, 0.78);

  strong {
    @include display(800);
    font-size: 1.35rem;
    color: var(--oro-vivo);
    margin-right: 6px;
  }
}
</style>
