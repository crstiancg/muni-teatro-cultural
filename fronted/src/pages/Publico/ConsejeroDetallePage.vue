<template>
  <q-page class="perfil publico">
    <div v-if="cargando" class="cargando">
      <div class="cargando-titulo" />
      <div class="cargando-mosaico" />
    </div>

    <template v-else-if="persona">
      <!-- ══════════ TÍTULO ══════════ -->
      <header class="encabezado" :style="{ '--acento': comision.color }">
        <nav class="miga" aria-label="Ruta">
          <router-link :to="{ name: 'Inicio' }">Inicio</router-link>
          <ChevronRight :size="14" />
          <router-link :to="{ name: 'ConsejerosPublico' }">Artistas</router-link>
          <ChevronRight :size="14" />
          <span>{{ persona.nombre_completo }}</span>
        </nav>

        <div class="encabezado-fila">
          <div>
            <span class="etiqueta">
              <component :is="iconoComision" :size="14" />
              {{ comision.corto }}
            </span>
            <h1>{{ persona.nombre_completo }}</h1>
            <div class="meta">
              <span><Sparkles :size="15" /> {{ persona.comision }}</span>
              <span><MapPin :size="15" /> {{ INSTITUCION.ciudad }}, Perú</span>
              <span>
                <Images :size="15" /> {{ actividades.length }}
                {{ actividades.length === 1 ? 'actividad' : 'actividades' }}
              </span>
            </div>
          </div>

          <div class="encabezado-acciones">
            <button class="accion" @click="compartir">
              <Share2 :size="16" /> {{ copiado ? '¡Enlace copiado!' : 'Compartir' }}
            </button>
          </div>
        </div>
      </header>

      <!-- ══════════ MOSAICO DE FOTOS ══════════ -->
      <section v-if="actividades.length" class="mosaico-zona">
        <div class="mosaico" :class="`piezas-${Math.min(actividades.length, 5)}`">
          <button class="pieza principal" @click="abrirVisor(0)">
            <img
              :src="actividades[0].imagen_url"
              :alt="actividades[0].titulo || actividades[0].descripcion_texto || ''"
            />
          </button>

          <button
            v-for="(act, i) in actividades.slice(1, 5)"
            :key="act.id"
            class="pieza"
            @click="abrirVisor(i + 1)"
          >
            <img :src="act.imagen_url" :alt="act.titulo || act.descripcion_texto || ''" loading="lazy" />
          </button>
        </div>

        <button v-if="actividades.length > 1" class="ver-fotos" @click="abrirVisor(0)">
          <LayoutGrid :size="16" /> Ver las {{ actividades.length }} fotos
        </button>
      </section>

      <div v-else class="sin-fotos">
        <ImageOff :size="30" />
        <p>Este artista todavía no publicó actividades.</p>
      </div>

      <!-- ══════════ CUERPO ══════════ -->
      <div class="cuerpo">
        <div class="columna">
          <section class="bloque">
            <h2>Sobre su trabajo</h2>
            <!-- HTML sanitizado en el backend al guardar (HTMLPurifier), por eso v-html es seguro -->
            <div v-if="persona.biografia" class="texto biografia" v-html="persona.biografia" />
            <p v-else class="texto">
              <strong>{{ persona.nombre }}</strong> integra la comisión de
              <strong>{{ comision.corto.toLowerCase() }}</strong> del registro cultural de
              {{ INSTITUCION.ciudad }}, dentro de la familia <strong>{{ persona.comision }}</strong
              >. Su trabajo forma parte de la memoria colectiva que sostiene la Festividad de la
              Virgen de la Candelaria.
            </p>
          </section>

          <section v-if="actividades.length" class="bloque">
            <div class="bloque-head">
              <h2>Actividades documentadas</h2>
              <router-link
                v-if="actividades.length > 4"
                :to="{ name: 'ConsejeroGaleriaPublico', params: { slug: persona.slug } }"
                class="enlace"
              >
                Ver la galería completa <ArrowRight :size="15" />
              </router-link>
            </div>

            <ul class="lista-actividades">
              <li v-for="(act, i) in actividades.slice(0, 4)" :key="act.id">
                <button class="act-foto" @click="abrirVisor(i)">
                  <img :src="act.imagen_url" alt="" loading="lazy" />
                </button>
                <div class="act-texto">
                  <p class="act-titulo">{{ act.titulo || 'Actividad' }}</p>
                  <!-- con formato (negritas, listas): HTML sanitizado en el backend -->
                  <div v-if="act.descripcion" class="act-html" v-html="act.descripcion" />
                </div>
              </li>
            </ul>
          </section>
        </div>

        <!-- tarjeta de contacto -->
        <aside class="tarjeta">
          <div class="tarjeta-cabecera">
            <span class="avatar" :style="{ '--acento': comision.color }">
              <img v-if="persona.foto_url" :src="persona.foto_url" :alt="persona.nombre_completo" />
              <template v-else>{{ inicial }}</template>
            </span>
            <div>
              <p class="tarjeta-nombre">{{ persona.nombre_completo }}</p>
              <p class="tarjeta-familia">{{ persona.comision }}</p>
            </div>
          </div>

          <dl class="datos">
            <div v-if="persona.celular">
              <dt><Phone :size="15" /> Celular</dt>
              <dd>
                <a :href="`tel:${persona.celular}`">{{ persona.celular }}</a>
              </dd>
            </div>
            <div v-if="persona.correo">
              <dt><Mail :size="15" /> Correo</dt>
              <dd>
                <a :href="`mailto:${persona.correo}`">{{ persona.correo }}</a>
              </dd>
            </div>
          </dl>

          <div v-if="redes.length" class="redes">
            <a
              v-for="red in redes"
              :key="red.clave"
              :href="red.url"
              target="_blank"
              rel="noopener nofollow"
              :title="red.label"
              :aria-label="red.label"
            >
              <component :is="red.icono" :size="18" />
            </a>
          </div>

          <div v-if="persona.agrupaciones?.length" class="integra">
            <p class="integra-titulo">Integra</p>
            <router-link
              v-for="a in persona.agrupaciones"
              :key="a.slug"
              :to="{ name: 'AgrupacionPublica', params: { slug: a.slug } }"
              class="integra-item"
            >
              <span class="integra-nombre">{{ a.nombre }}</span>
              <span v-if="a.rol" class="integra-rol">{{ a.rol }}</span>
            </router-link>
          </div>

          <div v-if="persona.comision_alternativo" class="alterna">
            <span class="punto" />
            También en <strong>{{ persona.comision_alternativo }}</strong>
          </div>

          <router-link
            :to="{ name: 'ConsejerosPublico', query: { grupo: persona.cod_grupo } }"
            class="btn-linea"
          >
            Ver toda la disciplina <ArrowRight :size="15" />
          </router-link>
        </aside>
      </div>

      <!-- ══════════ RELACIONADOS ══════════ -->
      <section v-if="relacionados.length" class="relacionados">
        <div class="relacionados-inner">
          <div class="bloque-head">
            <div>
              <p class="kicker">Sigue explorando</p>
              <h2>Otros artistas de {{ comision.corto }}</h2>
            </div>
            <router-link
              :to="{ name: 'ConsejerosPublico', query: { grupo: persona.cod_grupo } }"
              class="enlace"
            >
              Ver todos <ArrowRight :size="15" />
            </router-link>
          </div>

          <div class="grid-relacionados">
            <ArtistaTarjeta v-for="otro in relacionados" :key="otro.id" :artista="otro" />
          </div>
        </div>
      </section>
    </template>

    <!-- ══════════ VISOR ══════════ -->
    <!-- panel lateral (como Instagram/Facebook en escritorio): la foto usa todo el
         alto y la descripción se lee completa al costado, con su propio scroll.
         En celular el panel pasa abajo. -->
    <div v-if="visorAbierto" class="visor" role="dialog" aria-modal="true" aria-label="Fotografía">
      <button class="visor-cerrar" aria-label="Cerrar" @click="cerrarVisor"><X :size="21" /></button>

      <div class="visor-escena" @click.self="cerrarVisor">
        <img
          class="visor-img"
          :src="actividades[visorIndex].imagen_url"
          :alt="actividades[visorIndex].titulo || actividades[visorIndex].descripcion_texto || ''"
        />
        <button class="visor-nav izq" aria-label="Anterior" @click="navegar(-1)">
          <ChevronLeft :size="26" />
        </button>
        <button class="visor-nav der" aria-label="Siguiente" @click="navegar(1)">
          <ChevronRight :size="26" />
        </button>
      </div>

      <aside class="visor-panel">
        <div class="visor-autor">
          <span class="visor-avatar" :style="{ '--acento': comision.color }">
            <img v-if="persona.foto_url" :src="persona.foto_url" alt="" />
            <template v-else>{{ inicial }}</template>
          </span>
          <div class="visor-autor-texto">
            <p class="visor-nombre">{{ persona.nombre_completo }}</p>
            <p class="visor-familia">{{ persona.comision }}</p>
          </div>
        </div>

        <div class="visor-descripcion">
          <h3 v-if="actividades[visorIndex].titulo" class="visor-titulo">
            {{ actividades[visorIndex].titulo }}
          </h3>
          <!-- HTML sanitizado en el backend al guardar (App\Support\Html::limpio) -->
          <div
            v-if="actividades[visorIndex].descripcion"
            class="visor-html"
            v-html="actividades[visorIndex].descripcion"
          />
          <p v-else class="visor-sin-texto">Esta fotografía no tiene descripción.</p>
        </div>

        <div class="visor-pie">Foto {{ visorIndex + 1 }} de {{ actividades.length }}</div>
      </aside>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  ChevronRight,
  ChevronLeft,
  ArrowRight,
  Images,
  ImageOff,
  LayoutGrid,
  Sparkles,
  MapPin,
  Phone,
  Mail,
  Share2,
  X,
  Music,
  PersonStanding,
  Drama,
  BookOpen,
  Shirt,
  Video,
  Palette,
  Megaphone,
  Facebook,
  Instagram,
  Music2,
  Youtube,
  Globe,
} from 'lucide-vue-next'
import PersonaPublicaService from '@/services/PersonaPublicaService'
import { INSTITUCION, comisionDe, urlCompartirPerfil } from '@/config/institucion'
import ArtistaTarjeta from '@/components/ArtistaTarjeta.vue'

const route = useRoute()

const persona = ref(null)
const relacionados = ref([])
const cargando = ref(true)
const copiado = ref(false)

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

const comision = computed(() => comisionDe(persona.value?.cod_grupo))
const iconoComision = computed(() => iconosPorGrupo[persona.value?.cod_grupo] || Sparkles)
const actividades = computed(() => persona.value?.actividades || [])
const inicial = computed(() => (persona.value?.nombre?.charAt(0) || '?').toUpperCase())

// mismas claves que Persona::REDES en el backend; solo se muestran las cargadas
const REDES = [
  { clave: 'facebook', label: 'Facebook', icono: Facebook },
  { clave: 'instagram', label: 'Instagram', icono: Instagram },
  { clave: 'tiktok', label: 'TikTok', icono: Music2 },
  { clave: 'youtube', label: 'YouTube', icono: Youtube },
  { clave: 'web', label: 'Página web', icono: Globe },
]
const redes = computed(() =>
  REDES.filter((r) => persona.value?.redes_sociales?.[r.clave]).map((r) => ({
    ...r,
    url: persona.value.redes_sociales[r.clave],
  })),
)

async function compartir() {
  try {
    // el link del backend trae la vista previa (Open Graph) para WhatsApp/Facebook
    await navigator.clipboard.writeText(urlCompartirPerfil(persona.value.slug))
    copiado.value = true
    setTimeout(() => (copiado.value = false), 2200)
  } catch {
    // si el navegador bloquea el portapapeles no se hace nada: el visitante
    // siempre puede copiar la URL de la barra de direcciones
  }
}

// ---------- visor ----------
const visorAbierto = ref(false)
const visorIndex = ref(0)

function abrirVisor(i) {
  visorIndex.value = i
  visorAbierto.value = true
  document.body.style.overflow = 'hidden'
}

function cerrarVisor() {
  visorAbierto.value = false
  document.body.style.overflow = ''
}

function navegar(paso) {
  const total = actividades.value.length
  visorIndex.value = (visorIndex.value + paso + total) % total
}

function teclas(e) {
  if (!visorAbierto.value) return
  if (e.key === 'Escape') cerrarVisor()
  if (e.key === 'ArrowRight') navegar(1)
  if (e.key === 'ArrowLeft') navegar(-1)
}

onMounted(() => window.addEventListener('keydown', teclas))
onBeforeUnmount(() => {
  window.removeEventListener('keydown', teclas)
  document.body.style.overflow = ''
})

async function cargar(slug) {
  cargando.value = true
  relacionados.value = []
  persona.value = await PersonaPublicaService.get(slug)
  cargando.value = false

  if (persona.value?.cod_grupo) {
    const respuesta = await PersonaPublicaService.getData({
      grupo: persona.value.cod_grupo,
      por_pagina: 5,
    })
    relacionados.value = respuesta.data.filter((p) => p.slug !== persona.value.slug).slice(0, 4)
  }
}

onMounted(() => cargar(route.params.slug))
watch(
  () => route.params.slug,
  (slug) => slug && cargar(slug),
)
</script>

<style scoped lang="scss">
.perfil {
  background: var(--papel);
  color: var(--tinta);
  font-family: var(--fuente-texto);
}

/* ── carga ── */
.cargando {
  max-width: var(--ancho);
  margin: 40px auto;
  padding: 0 var(--gutter);
}

.cargando-titulo {
  height: 42px;
  width: 50%;
  border-radius: var(--r-sm);
  background: var(--crema-hondo);
  margin-bottom: 24px;
}

.cargando-mosaico {
  height: 380px;
  border-radius: var(--r-lg);
  background: var(--crema-hondo);
}

/* ══════════ ENCABEZADO ══════════ */
.encabezado {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: clamp(22px, 3vw, 34px) var(--gutter) clamp(16px, 2vw, 22px);
}

.miga {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  font-size: var(--t-xs);
  color: var(--tinta-suave);
  margin-bottom: 16px;

  a {
    color: var(--tinta-suave);
    text-decoration: none;

    &:hover {
      color: var(--rojo);
    }

    &:focus-visible {
      @include foco;
    }
  }
}

.encabezado-fila {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  flex-wrap: wrap;
}

.etiqueta {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  background: color-mix(in srgb, var(--acento) 12%, var(--blanco));
  color: var(--acento);
  font-size: 0.72rem;
  font-weight: 700;
  padding: 6px 14px;
  border-radius: var(--r-full);
  margin-bottom: 12px;
}

.encabezado h1 {
  @include display(800);
  font-size: var(--t-2xl);
  line-height: 1.04;
  margin: 0 0 12px;
  color: var(--tinta);
}

.meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 20px;

  span {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: var(--t-sm);
    color: var(--tinta-suave);

    svg {
      color: var(--acento);
    }
  }
}

.accion {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 1px solid var(--borde-fuerte);
  background: var(--blanco);
  color: var(--tinta);
  font-family: var(--fuente-texto);
  font-size: var(--t-sm);
  font-weight: 700;
  padding: 10px 18px;
  border-radius: var(--r-full);
  cursor: pointer;
  transition: all var(--transicion);

  &:hover {
    border-color: var(--rojo);
    color: var(--rojo);
  }

  &:focus-visible {
    @include foco;
  }
}

/* ══════════ MOSAICO ══════════ */
.mosaico-zona {
  position: relative;
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 0 var(--gutter);
}

/* una pieza grande a la izquierda y hasta cuatro chicas a la derecha, el
   patrón que usan las fichas de alojamiento y de tours */
.mosaico {
  display: grid;
  grid-template-columns: 1fr;
  gap: 8px;
  border-radius: var(--r-lg);
  overflow: hidden;
  height: clamp(260px, 42vw, 420px);
}

@media (min-width: 760px) {
  /* el armado cambia según cuántas fotos haya, para que nunca quede un hueco */
  .piezas-1 {
    grid-template-columns: 1fr;
  }

  .piezas-2 {
    grid-template-columns: 1fr 1fr;
  }

  .piezas-3 {
    grid-template-columns: 2fr 1fr;
    grid-template-rows: 1fr 1fr;

    .principal {
      grid-row: 1 / span 2;
    }
  }

  .piezas-4 {
    grid-template-columns: 2fr 1fr;
    grid-template-rows: repeat(3, 1fr);

    .principal {
      grid-row: 1 / span 3;
    }
  }

  .piezas-5 {
    grid-template-columns: 2fr 1fr 1fr;
    grid-template-rows: 1fr 1fr;

    .principal {
      grid-column: 1;
      grid-row: 1 / span 2;
    }
  }
}

.pieza {
  position: relative;
  padding: 0;
  border: none;
  overflow: hidden;
  cursor: zoom-in;
  background: var(--crema);
  min-height: 0;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.6s ease;
  }

  &:hover img {
    transform: scale(1.05);
  }

  &:focus-visible {
    @include foco;
  }
}

.ver-fotos {
  position: absolute;
  right: calc(var(--gutter) + 14px);
  bottom: 14px;
  z-index: 2;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: var(--blanco);
  border: 1px solid var(--borde-fuerte);
  color: var(--tinta);
  font-family: var(--fuente-texto);
  font-size: var(--t-sm);
  font-weight: 700;
  padding: 10px 18px;
  border-radius: var(--r-sm);
  cursor: pointer;
  box-shadow: var(--sombra);

  &:hover {
    background: var(--crema);
  }

  &:focus-visible {
    @include foco;
  }
}

.sin-fotos {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 44px var(--gutter);
  text-align: center;
  color: var(--tinta-suave);

  svg {
    color: var(--tinta-tenue);
  }

  p {
    margin: 12px 0 0;
  }
}

/* ══════════ CUERPO ══════════ */
.cuerpo {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: clamp(34px, 5vw, 56px) var(--gutter);
  display: grid;
  grid-template-columns: 1fr;
  gap: 32px;
}

@media (min-width: 900px) {
  .cuerpo {
    grid-template-columns: minmax(0, 1fr) 330px;
    gap: 48px;
    align-items: start;
  }
}

.bloque {
  padding-bottom: 28px;
  margin-bottom: 28px;
  border-bottom: 1px solid var(--borde);

  &:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
  }

  h2 {
    @include display(700);
    font-size: var(--t-lg);
    margin: 0 0 14px;
    color: var(--tinta);
  }
}

.bloque-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 18px;

  h2 {
    margin: 0;
  }
}

.kicker {
  @include kicker;
  margin: 0 0 8px;
}

.texto {
  font-size: var(--t-base);
  line-height: 1.8;
  color: var(--tinta-suave);
  margin: 0;
  max-width: 62ch;

  strong {
    color: var(--tinta);
    font-weight: 600;
  }
}

.biografia {
  :deep(p),
  :deep(div) {
    margin: 0 0 0.8em;
  }

  :deep(ul),
  :deep(ol) {
    padding-left: 1.3em;
    margin: 0 0 0.8em;
  }

  :deep(blockquote) {
    margin: 0 0 0.8em;
    padding-left: 1em;
    border-left: 3px solid var(--acento, currentColor);
    font-style: italic;
  }

  :deep(a) {
    color: var(--rojo);
  }
}

.redes {
  display: flex;
  gap: 8px;
  margin: 0 0 16px;

  a {
    display: grid;
    place-items: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 1px solid color-mix(in srgb, var(--tinta) 15%, transparent);
    color: var(--tinta);
    transition:
      color 0.2s ease,
      border-color 0.2s ease;

    &:hover {
      color: var(--rojo);
      border-color: var(--rojo);
    }
  }
}

.enlace {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: var(--t-sm);
  font-weight: 700;
  color: var(--rojo);
  text-decoration: none;
  white-space: nowrap;

  &:hover {
    color: var(--rojo-hondo);
  }

  &:focus-visible {
    @include foco;
  }
}

.lista-actividades {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 14px;

  li {
    display: flex;
    gap: 16px;
    /* arriba: con una descripción larga la foto no queda flotando en el medio */
    align-items: flex-start;
    background: var(--blanco);
    border: 1px solid var(--borde);
    border-radius: var(--r-md);
    padding: 12px;
  }

  p {
    margin: 0;
    font-size: var(--t-sm);
    line-height: 1.6;
    color: var(--tinta-suave);
  }
}

.act-texto {
  min-width: 0;
  font-size: var(--t-sm);
  line-height: 1.6;
  color: var(--tinta-suave);
  overflow-wrap: anywhere;
}

.lista-actividades .act-titulo {
  margin: 0 0 2px;
  font-weight: 700;
  color: var(--tinta);
}

/* el HTML del editor llega por v-html: los estilos scoped necesitan :deep */
.act-html {
  :deep(p),
  :deep(div),
  :deep(blockquote) {
    margin: 0 0 0.5em;
  }

  :deep(strong),
  :deep(b) {
    font-weight: 700;
    color: var(--tinta);
  }

  :deep(ul),
  :deep(ol) {
    margin: 0 0 0.5em;
    padding-left: 1.2em;
  }

  :deep(blockquote) {
    padding-left: 0.8em;
    border-left: 3px solid var(--borde);
    font-style: italic;
  }

  :deep(a) {
    color: var(--rojo);
  }
}

.act-foto {
  flex: none;
  width: 92px;
  height: 72px;
  padding: 0;
  border: none;
  border-radius: var(--r-sm);
  overflow: hidden;
  cursor: zoom-in;
  background: var(--crema);

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  &:focus-visible {
    @include foco;
  }
}

/* ── tarjeta de contacto ── */
.tarjeta {
  background: var(--blanco);
  border: 1px solid var(--borde);
  border-radius: var(--r-lg);
  padding: 22px;
  box-shadow: var(--sombra);
}

@media (min-width: 900px) {
  .tarjeta {
    position: sticky;
    top: 90px;
  }
}

.tarjeta-cabecera {
  display: flex;
  align-items: center;
  gap: 13px;
  padding-bottom: 18px;
  border-bottom: 1px solid var(--borde);
  margin-bottom: 18px;
}

.avatar {
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
  flex: none;
  border-radius: 50%;
  background: color-mix(in srgb, var(--acento) 15%, var(--blanco));
  color: var(--acento);
  @include display(800);
  font-size: 1.3rem;
  overflow: hidden;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.tarjeta-nombre {
  @include display(700);
  font-size: 1rem;
  margin: 0;
  color: var(--tinta);
  line-height: 1.2;
}

.tarjeta-familia {
  font-size: var(--t-xs);
  color: var(--tinta-suave);
  margin: 3px 0 0;
}

.datos {
  margin: 0 0 18px;

  > div + div {
    margin-top: 13px;
    padding-top: 13px;
    border-top: 1px solid var(--borde);
  }

  dt {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: var(--t-xs);
    color: var(--tinta-suave);
    margin-bottom: 4px;

    svg {
      color: var(--rojo);
    }
  }

  dd {
    margin: 0;
    font-size: var(--t-sm);
    color: var(--tinta);
    font-weight: 600;
    word-break: break-word;

    a {
      color: var(--tinta);
      text-decoration: none;

      &:hover {
        color: var(--rojo);
      }

      &:focus-visible {
        @include foco;
      }
    }
  }
}

.alterna {
  display: flex;
  align-items: center;
  gap: 9px;
  font-size: var(--t-xs);
  color: var(--tinta-suave);
  background: var(--crema);
  border-radius: var(--r-sm);
  padding: 10px 12px;
  margin-bottom: 18px;

  strong {
    color: var(--tinta);
  }
}

.punto {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--tinta-tenue);
  flex: none;
}

.btn-linea {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  width: 100%;
  border: 1px solid var(--borde-fuerte);
  background: var(--papel);
  color: var(--tinta);
  font-family: var(--fuente-texto);
  font-size: var(--t-sm);
  font-weight: 700;
  text-decoration: none;
  padding: 13px 20px;
  border-radius: var(--r-full);
  transition: all var(--transicion);

  &:hover {
    border-color: var(--rojo);
    color: var(--rojo);
  }

  &:focus-visible {
    @include foco;
  }
}

/* ── relacionados ── */
.relacionados {
  background: var(--crema);
  border-top: 1px solid var(--borde);
}

.relacionados-inner {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: clamp(44px, 6vw, 72px) var(--gutter);
}

.grid-relacionados {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: clamp(12px, 2vw, 20px);
}

/* agrupaciones donde figura el artista */
.integra {
  margin: 0 0 16px;
  display: grid;
  gap: 6px;
}

.integra-titulo {
  margin: 0;
  font-size: var(--t-xs);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--tinta-suave);
}

.integra-item {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  padding: 8px 10px;
  border: 1px solid var(--borde);
  border-radius: var(--r-sm);
  text-decoration: none;
  color: var(--tinta);
  transition: border-color var(--transicion);

  &:hover {
    border-color: var(--rojo);
  }

  &:focus-visible {
    @include foco;
  }
}

.integra-nombre {
  font-weight: 700;
  font-size: var(--t-sm);
  overflow-wrap: anywhere;
}

.integra-rol {
  flex: none;
  font-size: var(--t-xs);
  color: var(--tinta-suave);
}

/* ══════════ VISOR ══════════ */
.visor {
  position: fixed;
  inset: 0;
  z-index: 3000;
  /* casi opaco + desenfoque: la página de atrás no compite con la foto */
  background: rgba(10, 14, 20, 0.97);
  backdrop-filter: blur(6px);
  display: grid;
  grid-template-columns: minmax(0, 1fr) 360px;
}

.visor-cerrar,
.visor-nav {
  display: grid;
  place-items: center;
  border-radius: 50%;
  border: 1px solid rgba(253, 251, 247, 0.3);
  background: rgba(253, 251, 247, 0.12);
  color: var(--sobre-foto);
  cursor: pointer;

  &:hover {
    background: rgba(253, 251, 247, 0.26);
  }

  &:focus-visible {
    @include foco;
  }
}

.visor-cerrar {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 2;
  width: 42px;
  height: 42px;
}

/* la foto toma todo el alto disponible, centrada */
.visor-escena {
  position: relative;
  min-width: 0;
  min-height: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 88px;
}

.visor-img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  border-radius: var(--r-md);
  display: block;
}

.visor-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 48px;
  height: 48px;
}

.visor-nav.izq {
  left: 20px;
}

.visor-nav.der {
  right: 20px;
}

.visor-panel {
  min-height: 0;
  display: flex;
  flex-direction: column;
  background: rgba(253, 251, 247, 0.04);
  border-left: 1px solid rgba(253, 251, 247, 0.1);
  color: rgba(253, 251, 247, 0.88);
}

.visor-autor {
  display: flex;
  align-items: center;
  gap: 12px;
  /* deja lugar al botón de cerrar, que flota arriba a la derecha */
  padding: 20px 72px 18px 22px;
  border-bottom: 1px solid rgba(253, 251, 247, 0.1);
}

.visor-avatar {
  display: grid;
  place-items: center;
  width: 42px;
  height: 42px;
  flex: none;
  border-radius: 50%;
  overflow: hidden;
  background: color-mix(in srgb, var(--acento) 35%, transparent);
  color: var(--sobre-foto);
  font-weight: 800;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.visor-autor-texto {
  min-width: 0;
}

.visor-nombre {
  margin: 0;
  font-weight: 700;
  font-size: var(--t-sm);
  color: var(--sobre-foto);
  line-height: 1.3;
}

.visor-familia {
  margin: 2px 0 0;
  font-size: var(--t-xs);
  color: rgba(253, 251, 247, 0.6);
}

/* la descripción completa, con su propio scroll */
.visor-descripcion {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 18px 22px;
  font-size: var(--t-sm);
  line-height: 1.7;
  overflow-wrap: anywhere;
}

.visor-titulo {
  margin: 0 0 10px;
  font-size: var(--t-base);
  font-weight: 700;
  line-height: 1.35;
  color: var(--sobre-foto);
}

/* el HTML del editor llega por v-html: los estilos scoped necesitan :deep */
.visor-html {
  :deep(p),
  :deep(div),
  :deep(blockquote) {
    margin: 0 0 0.7em;
  }

  :deep(ul),
  :deep(ol) {
    margin: 0 0 0.7em;
    padding-left: 1.3em;
  }

  :deep(blockquote) {
    padding-left: 0.9em;
    border-left: 3px solid rgba(253, 251, 247, 0.3);
    font-style: italic;
  }

  :deep(a) {
    color: var(--oro-vivo);
  }
}

.visor-sin-texto {
  color: rgba(253, 251, 247, 0.5);
  font-style: italic;
}

.visor-pie {
  padding: 14px 22px;
  border-top: 1px solid rgba(253, 251, 247, 0.1);
  font-size: var(--t-xs);
  font-weight: 600;
  color: rgba(253, 251, 247, 0.6);
}

/* celular y tablet: la foto arriba, el panel abajo con alto máximo */
@media (max-width: 900px) {
  .visor {
    grid-template-columns: 1fr;
    grid-template-rows: minmax(0, 1fr) auto;
  }

  .visor-escena {
    padding: 64px 16px 16px;
  }

  .visor-nav {
    width: 40px;
    height: 40px;
  }

  .visor-nav.izq {
    left: 8px;
  }

  .visor-nav.der {
    right: 8px;
  }

  .visor-panel {
    max-height: 38vh;
    border-left: 0;
    border-top: 1px solid rgba(253, 251, 247, 0.1);
  }

  .visor-autor {
    padding: 14px 16px 12px;
  }

  .visor-descripcion {
    padding: 12px 16px;
  }

  .visor-pie {
    padding: 10px 16px;
  }
}
</style>
