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
            <img :src="actividades[0].imagen_url" :alt="actividades[0].descripcion || ''" />
          </button>

          <button
            v-for="(act, i) in actividades.slice(1, 5)"
            :key="act.id"
            class="pieza"
            @click="abrirVisor(i + 1)"
          >
            <img :src="act.imagen_url" :alt="act.descripcion || ''" loading="lazy" />
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
            <p class="texto">
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
                <p>{{ act.descripcion || 'Actividad sin descripción' }}</p>
              </li>
            </ul>
          </section>
        </div>

        <!-- tarjeta de contacto -->
        <aside class="tarjeta">
          <div class="tarjeta-cabecera">
            <span class="avatar" :style="{ '--acento': comision.color }">
              {{ inicial }}
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
            <div v-if="persona.dni">
              <dt><IdCard :size="15" /> Documento</dt>
              <dd>{{ persona.dni }}</dd>
            </div>
          </dl>

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
    <div
      v-if="visorAbierto"
      class="visor"
      role="dialog"
      aria-modal="true"
      aria-label="Fotografía"
      @click.self="cerrarVisor"
    >
      <div class="visor-barra">
        <span>{{ visorIndex + 1 }} / {{ actividades.length }}</span>
        <button aria-label="Cerrar" @click="cerrarVisor"><X :size="21" /></button>
      </div>

      <button class="visor-nav izq" aria-label="Anterior" @click="navegar(-1)">
        <ChevronLeft :size="26" />
      </button>

      <figure class="visor-figura">
        <img
          :src="actividades[visorIndex].imagen_url"
          :alt="actividades[visorIndex].descripcion || ''"
        />
        <figcaption v-if="actividades[visorIndex].descripcion">
          {{ actividades[visorIndex].descripcion }}
        </figcaption>
      </figure>

      <button class="visor-nav der" aria-label="Siguiente" @click="navegar(1)">
        <ChevronRight :size="26" />
      </button>
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
  IdCard,
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
} from 'lucide-vue-next'
import PersonaPublicaService from '@/services/PersonaPublicaService'
import { INSTITUCION, comisionDe } from '@/config/institucion'
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

async function compartir() {
  try {
    await navigator.clipboard.writeText(window.location.href)
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
    align-items: center;
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

/* ══════════ VISOR ══════════ */
.visor {
  position: fixed;
  inset: 0;
  z-index: 3000;
  background: rgba(12, 18, 27, 0.95);
  display: grid;
  place-items: center;
  padding: 72px 60px 40px;
}

.visor-barra {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px;
  color: rgba(253, 251, 247, 0.85);
  font-size: var(--t-sm);
  font-weight: 600;

  button {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
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
}

.visor-figura {
  margin: 0;
  max-width: 100%;
  text-align: center;

  img {
    max-width: 100%;
    max-height: 74vh;
    border-radius: var(--r-md);
    display: block;
    margin: 0 auto;
  }

  figcaption {
    margin-top: 16px;
    color: rgba(253, 251, 247, 0.82);
    font-size: var(--t-sm);
    max-width: 60ch;
    margin-inline: auto;
  }
}

.visor-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
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

.visor-nav.izq {
  left: 16px;
}

.visor-nav.der {
  right: 16px;
}

@media (max-width: 640px) {
  .visor {
    padding: 72px 16px 90px;
  }

  .visor-nav {
    top: auto;
    bottom: 22px;
    transform: none;
  }

  .visor-nav.izq {
    left: 28%;
  }

  .visor-nav.der {
    right: 28%;
  }
}
</style>
