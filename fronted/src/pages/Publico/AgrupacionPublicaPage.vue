<template>
  <q-page class="agrupacion-page">
    <div v-if="cargando" class="estado-carga">Cargando…</div>
    <div v-else-if="!agrupacion" class="estado-carga">
      No se encontró esta agrupación.
      <router-link :to="{ name: 'AgrupacionesPublico' }" class="volver">Ver todas las agrupaciones</router-link>
    </div>

    <template v-else>
      <!-- portada: la imagen de la agrupación a todo el ancho, con el nombre encima -->
      <header class="hero" :style="{ '--acento': comision.color }">
        <img v-if="agrupacion.portada_url" :src="agrupacion.portada_url" alt="" class="hero-img" />
        <div class="hero-velo" />
        <div class="contenedor hero-contenido">
          <router-link :to="{ name: 'AgrupacionesPublico' }" class="migas">
            <ArrowLeft :size="15" /> Agrupaciones
          </router-link>
          <div class="hero-fila">
            <div class="logo">
              <img v-if="agrupacion.logo_url" :src="agrupacion.logo_url" :alt="agrupacion.nombre" />
              <span v-else>{{ agrupacion.nombre.charAt(0) }}</span>
            </div>
            <div class="hero-texto">
              <span class="etiqueta">{{ comision.corto }}</span>
              <h1>{{ agrupacion.nombre }}</h1>
              <p class="comision">{{ agrupacion.comision }}</p>
            </div>
          </div>
        </div>
      </header>

      <!-- franja de datos rápidos -->
      <div class="franja">
        <div class="contenedor franja-fila">
          <div class="dato">
            <strong>{{ agrupacion.integrantes.length }}</strong>
            <span>integrantes</span>
          </div>
          <div class="dato">
            <strong>{{ agrupacion.actividades.length }}</strong>
            <span>actividades</span>
          </div>
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
        </div>
      </div>

      <div class="contenedor cuerpo">
        <section v-if="agrupacion.descripcion" class="bloque">
          <h2>Sobre la agrupación</h2>
          <!-- HTML sanitizado en el backend al guardar (App\Support\Html::limpio) -->
          <div class="descripcion" v-html="agrupacion.descripcion" />
        </section>

        <section v-if="agrupacion.actividades.length" class="bloque">
          <h2>Actividades realizadas <span class="cuenta">{{ agrupacion.actividades.length }}</span></h2>
          <div class="actividades">
            <button
              v-for="(act, i) in agrupacion.actividades"
              :key="act.id"
              class="actividad"
              @click="abrirVisor(i)"
            >
              <div class="actividad-foto">
                <img
                  v-if="act.imagen_miniatura_url"
                  :src="act.imagen_miniatura_url"
                  :alt="act.titulo || act.descripcion_texto"
                  loading="lazy"
                />
              </div>
              <div class="actividad-texto">
                <p class="actividad-titulo">{{ act.titulo || 'Actividad' }}</p>
                <p v-if="act.descripcion_texto" class="actividad-desc">{{ act.descripcion_texto }}</p>
              </div>
            </button>
          </div>
        </section>

        <section class="bloque">
          <h2>Integrantes <span class="cuenta">{{ agrupacion.integrantes.length }}</span></h2>
          <ul class="integrantes">
            <li
              v-for="(i, n) in integrantesOrdenados"
              :key="n"
              :class="{ representante: i.es_representante }"
            >
              <span class="avatar" :style="{ '--acento': comision.color }">
                <img v-if="i.foto_url" :src="i.foto_url" :alt="i.nombre_completo" />
                <template v-else>{{ iniciales(i.nombre_completo) }}</template>
              </span>
              <div class="integrante-texto">
                <!-- los artistas registrados y publicados enlazan a su perfil -->
                <router-link
                  v-if="i.slug"
                  :to="{ name: 'ConsejeroDetallePublico', params: { slug: i.slug } }"
                  class="nombre enlace"
                >
                  {{ i.nombre_completo }}
                </router-link>
                <span v-else class="nombre">{{ i.nombre_completo }}</span>
                <span class="rol">{{ i.rol || 'Integrante' }}</span>
              </div>
              <span v-if="i.es_representante" class="sello">Representante</span>
            </li>
          </ul>
        </section>
      </div>

      <!-- visor de actividades -->
      <q-dialog v-model="visorAbierto" maximized transition-show="fade" transition-hide="fade">
        <div v-if="actividadActual" class="visor" @click.self="visorAbierto = false">
          <button class="visor-cerrar" aria-label="Cerrar" @click="visorAbierto = false"><X :size="21" /></button>
          <div class="visor-escena" @click.self="visorAbierto = false">
            <img v-if="actividadActual.imagen_url" :src="actividadActual.imagen_url" :alt="actividadActual.titulo" />
            <button v-if="agrupacion.actividades.length > 1" class="visor-nav izq" aria-label="Anterior" @click="navegar(-1)">
              <ChevronLeft :size="26" />
            </button>
            <button v-if="agrupacion.actividades.length > 1" class="visor-nav der" aria-label="Siguiente" @click="navegar(1)">
              <ChevronRight :size="26" />
            </button>
          </div>
          <aside class="visor-panel">
            <p class="visor-agrupacion">{{ agrupacion.nombre }}</p>
            <h3>{{ actividadActual.titulo || 'Actividad' }}</h3>
            <div v-if="actividadActual.descripcion" class="visor-desc" v-html="actividadActual.descripcion" />
            <p class="visor-pie">Foto {{ visorIndex + 1 }} de {{ agrupacion.actividades.length }}</p>
          </aside>
        </div>
      </q-dialog>
    </template>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  ArrowLeft,
  ChevronLeft,
  ChevronRight,
  X,
  Facebook,
  Instagram,
  Music2,
  Youtube,
  Globe,
} from 'lucide-vue-next'
import AgrupacionService from '@/services/AgrupacionService'
import { comisionDe } from '@/config/institucion'

const route = useRoute()
const agrupacion = ref(null)
const cargando = ref(true)

const comision = computed(() => comisionDe(agrupacion.value?.cod_grupo))

// representante primero, después los artistas con perfil, después el resto
const integrantesOrdenados = computed(() =>
  [...(agrupacion.value?.integrantes || [])].sort(
    (a, b) => Number(b.es_representante) - Number(a.es_representante) || Number(!!b.slug) - Number(!!a.slug),
  ),
)

const iniciales = (nombre) =>
  nombre
    .split(' ')
    .slice(0, 2)
    .map((p) => p.charAt(0))
    .join('')
    .toUpperCase()

// mismas claves que Persona::REDES en el backend; solo se muestran las cargadas
const REDES = [
  { clave: 'facebook', label: 'Facebook', icono: Facebook },
  { clave: 'instagram', label: 'Instagram', icono: Instagram },
  { clave: 'tiktok', label: 'TikTok', icono: Music2 },
  { clave: 'youtube', label: 'YouTube', icono: Youtube },
  { clave: 'web', label: 'Página web', icono: Globe },
]
const redes = computed(() =>
  REDES.filter((r) => agrupacion.value?.redes_sociales?.[r.clave]).map((r) => ({
    ...r,
    url: agrupacion.value.redes_sociales[r.clave],
  })),
)

// ---------- visor ----------
const visorAbierto = ref(false)
const visorIndex = ref(0)
const actividadActual = computed(() => agrupacion.value?.actividades[visorIndex.value])

function abrirVisor(i) {
  visorIndex.value = i
  visorAbierto.value = true
}

function navegar(paso) {
  const total = agrupacion.value.actividades.length
  visorIndex.value = (visorIndex.value + paso + total) % total
}

onMounted(async () => {
  try {
    agrupacion.value = await AgrupacionService.publica(route.params.slug)
    document.title = `${agrupacion.value.nombre} · Agrupación`
  } catch {
    agrupacion.value = null
  } finally {
    cargando.value = false
  }
})
</script>

<style scoped lang="scss">
.agrupacion-page {
  background: var(--papel);
  color: var(--tinta);
  padding-bottom: var(--seccion-y);
}

.contenedor {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 0 var(--gutter);
}

.estado-carga {
  padding: 120px var(--gutter);
  text-align: center;
  color: var(--tinta-suave);
  display: grid;
  gap: 12px;
  justify-items: center;
}

.volver {
  color: var(--rojo);
  font-weight: 700;
}

/* ---------- portada ---------- */
.hero {
  position: relative;
  min-height: clamp(300px, 42vw, 460px);
  display: flex;
  align-items: flex-end;
  overflow: hidden;
  /* sin portada: un fondo con el color de la disciplina */
  background: linear-gradient(135deg, var(--noche), color-mix(in srgb, var(--acento) 70%, var(--noche)));
}

.hero-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hero-velo {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(22, 32, 46, 0.92) 0%, rgba(22, 32, 46, 0.35) 55%, rgba(22, 32, 46, 0.15) 100%);
}

.hero-contenido {
  position: relative;
  width: 100%;
  padding-top: 24px;
  padding-bottom: clamp(24px, 4vw, 40px);
  color: var(--sobre-foto);
}

.migas {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: clamp(48px, 10vw, 120px);
  color: var(--sobre-foto);
  font-size: var(--t-sm);
  font-weight: 600;
  text-decoration: none;
  opacity: 0.85;

  &:hover {
    opacity: 1;
  }
}

.hero-fila {
  display: flex;
  align-items: flex-end;
  gap: clamp(16px, 3vw, 28px);
}

.logo {
  flex: none;
  width: clamp(88px, 12vw, 136px);
  aspect-ratio: 1;
  border-radius: var(--r-lg);
  overflow: hidden;
  display: grid;
  place-items: center;
  background: var(--blanco);
  color: var(--acento);
  border: 4px solid var(--blanco);
  box-shadow: var(--sombra-alta);
  @include display(800);
  font-size: 2.8rem;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.hero-texto {
  min-width: 0;
}

.etiqueta {
  display: inline-block;
  padding: 4px 10px;
  border-radius: var(--r-full);
  background: var(--acento);
  color: var(--sobre-foto);
  font-size: var(--t-xs);
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

h1 {
  @include display(800);
  font-size: var(--t-xl);
  line-height: 1.05;
  margin: 10px 0 6px;
  overflow-wrap: anywhere;
}

.comision {
  margin: 0;
  font-size: var(--t-base);
  opacity: 0.85;
}

/* ---------- franja ---------- */
.franja {
  background: var(--blanco);
  border-bottom: 1px solid var(--borde);
}

.franja-fila {
  display: flex;
  align-items: center;
  gap: clamp(20px, 4vw, 48px);
  padding-top: 16px;
  padding-bottom: 16px;
  flex-wrap: wrap;
}

.dato {
  display: flex;
  align-items: baseline;
  gap: 8px;

  strong {
    @include display(800);
    font-size: 1.6rem;
  }

  span {
    color: var(--tinta-suave);
    font-size: var(--t-sm);
  }
}

.redes {
  margin-left: auto;
  display: flex;
  gap: 8px;

  a {
    display: grid;
    place-items: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 1px solid var(--borde-fuerte);
    color: var(--tinta);
    transition:
      color var(--transicion),
      border-color var(--transicion);

    &:hover {
      color: var(--rojo);
      border-color: var(--rojo);
    }

    &:focus-visible {
      @include foco;
    }
  }
}

/* ---------- cuerpo ---------- */
.cuerpo {
  padding-top: clamp(32px, 5vw, 56px);
  display: grid;
  gap: clamp(40px, 6vw, 64px);
}

.bloque h2 {
  @include display(800);
  font-size: var(--t-lg);
  margin: 0 0 20px;
  display: flex;
  align-items: baseline;
  gap: 10px;
}

.cuenta {
  font-family: var(--fuente-texto);
  font-size: var(--t-sm);
  font-weight: 700;
  color: var(--tinta-suave);
}

.descripcion {
  max-width: 70ch;
  font-size: var(--t-base);
  line-height: 1.8;
  color: var(--tinta-suave);
  overflow-wrap: anywhere;

  :deep(p),
  :deep(div),
  :deep(blockquote) {
    margin: 0 0 0.8em;
  }

  :deep(strong),
  :deep(b) {
    color: var(--tinta);
  }

  :deep(ul),
  :deep(ol) {
    padding-left: 1.3em;
    margin: 0 0 0.8em;
  }

  :deep(a) {
    color: var(--rojo);
  }
}

/* ---------- actividades ---------- */
.actividades {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 18px;
}

.actividad {
  all: unset;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  background: var(--blanco);
  border: 1px solid var(--borde);
  border-radius: var(--r-md);
  overflow: hidden;
  transition:
    transform var(--transicion),
    box-shadow var(--transicion);

  &:hover {
    transform: translateY(-3px);
    box-shadow: var(--sombra);
  }

  &:hover img {
    transform: scale(1.04);
  }

  &:focus-visible {
    @include foco;
  }
}

.actividad-foto {
  aspect-ratio: 4 / 3;
  overflow: hidden;
  background: var(--crema);

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
  }
}

.actividad-texto {
  padding: 14px 16px 16px;
}

.actividad-titulo {
  margin: 0 0 4px;
  font-weight: 700;
  color: var(--tinta);
  overflow-wrap: anywhere;
}

.actividad-desc {
  margin: 0;
  font-size: var(--t-sm);
  line-height: 1.55;
  color: var(--tinta-suave);
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  overflow-wrap: anywhere;
}

/* ---------- integrantes ---------- */
.integrantes {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 12px;

  li {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: var(--blanco);
    border: 1px solid var(--borde);
    border-radius: var(--r-md);
  }

  li.representante {
    border-color: var(--rojo);
    box-shadow: inset 4px 0 0 var(--rojo);
  }
}

.avatar {
  flex: none;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
  display: grid;
  place-items: center;
  background: color-mix(in srgb, var(--acento) 15%, var(--blanco));
  color: var(--acento);
  font-weight: 800;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.integrante-texto {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nombre {
  font-weight: 700;
  font-size: var(--t-sm);
  color: var(--tinta);
  overflow-wrap: anywhere;
  text-transform: capitalize;
}

.enlace {
  text-decoration: none;

  &:hover {
    color: var(--rojo);
    text-decoration: underline;
  }
}

.rol {
  font-size: var(--t-xs);
  color: var(--tinta-suave);
  text-transform: capitalize;
}

.sello {
  position: absolute;
  top: -9px;
  right: 12px;
  padding: 2px 8px;
  border-radius: var(--r-full);
  background: var(--rojo);
  color: var(--sobre-foto);
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.03em;
}

/* ---------- visor ---------- */
.visor {
  position: relative;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 360px;
  width: 100%;
  height: 100%;
  background: rgba(10, 14, 20, 0.97);
  color: rgba(253, 251, 247, 0.88);
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
}

.visor-cerrar {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 2;
  width: 42px;
  height: 42px;
}

.visor-escena {
  position: relative;
  min-height: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 88px;

  img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: var(--r-md);
  }
}

.visor-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 48px;
  height: 48px;

  &.izq {
    left: 20px;
  }

  &.der {
    right: 20px;
  }
}

.visor-panel {
  min-height: 0;
  overflow-y: auto;
  padding: 24px 22px;
  border-left: 1px solid rgba(253, 251, 247, 0.1);
  display: flex;
  flex-direction: column;
  gap: 10px;

  h3 {
    margin: 0;
    font-size: var(--t-md);
    font-weight: 700;
    color: var(--sobre-foto);
    padding-right: 48px;
    overflow-wrap: anywhere;
  }
}

.visor-agrupacion {
  margin: 0;
  font-size: var(--t-xs);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: rgba(253, 251, 247, 0.55);
}

.visor-desc {
  font-size: var(--t-sm);
  line-height: 1.7;
  overflow-wrap: anywhere;

  :deep(p),
  :deep(div) {
    margin: 0 0 0.7em;
  }

  :deep(a) {
    color: var(--oro-vivo);
  }
}

.visor-pie {
  margin: auto 0 0;
  font-size: var(--t-xs);
  color: rgba(253, 251, 247, 0.55);
}

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

    &.izq {
      left: 8px;
    }

    &.der {
      right: 8px;
    }
  }

  .visor-panel {
    max-height: 38vh;
    border-left: 0;
    border-top: 1px solid rgba(253, 251, 247, 0.1);
  }
}

@media (max-width: 600px) {
  .hero-fila {
    flex-direction: column;
    align-items: flex-start;
  }

  .redes {
    margin-left: 0;
  }
}
</style>
