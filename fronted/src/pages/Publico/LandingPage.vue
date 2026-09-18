<template>
  <q-page class="landing publico">
    <!-- ============ HERO ============ -->
    <section class="hero">
      <!-- el poster ocupa toda la pantalla; los dos img se superponen
           durante el cambio, por eso el fundido cruzado -->
      <div class="hero-fondo">
        <Transition name="poster">
          <img
            v-if="posterActual"
            :key="posterActual.id"
            :src="posterActual.imagen_url"
            :alt="posterActual.descripcion"
            class="hero-img"
          />
        </Transition>
      </div>
      <div class="hero-velo" />

      <div class="hero-contenido">
        <div class="hero-texto">
          <p class="kicker">
            ARTE QUE NOS IDENTIFICA.<br />
            CULTURA QUE NOS UNE.
          </p>

          <h1 class="titulo">
            IMPULSAMOS<br />
            <span class="oro">NUESTRA CULTURA</span>
          </h1>
          <span class="barra-oro" />

          <p class="bajada">
            Somos una comunidad de artistas, gestores y portadores de tradición que trabajan por
            mantener viva la identidad cultural de nuestra región.
          </p>

          <div class="hero-botones">
            <router-link :to="{ name: 'ConsejerosPublico' }" class="btn-oro">
              Ver artistas <ArrowRight :size="16" />
            </router-link>

            <button class="btn-fantasma" @click="irA('comisiones')">
              <span class="play"><Play :size="13" fill="currentColor" /></span>
              Conocer más
            </button>
          </div>
        </div>

        <div v-if="posters.length > 1" class="poster-puntos">
          <button
            v-for="(p, i) in posters"
            :key="p.id"
            class="poster-punto"
            :class="{ activo: i === posterIndex }"
            :aria-label="`Poster ${i + 1}`"
            @click="posterIndex = i"
          />
        </div>
      </div>

      <!-- franja de datos reales -->
      <div class="stats">
        <div v-for="dato in datos" :key="dato.label" class="stat">
          <component :is="dato.icono" :size="26" class="stat-icono" />
          <div>
            <div class="stat-numero">{{ dato.valor }}</div>
            <div class="stat-label">{{ dato.label }}</div>
            <div class="stat-desc">{{ dato.desc }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ COMISIONES ============ -->
    <section id="comisiones" class="seccion">
      <p class="kicker-oro">Nuestras comisiones</p>
      <h2 class="titulo-seccion con-sub">CREAR. PRESERVAR. COMPARTIR.</h2>
      <p class="subtitulo-seccion">
        Cada comisión especializada reúne y acompaña el trabajo de los artistas de su disciplina.
      </p>

      <div class="grid-comisiones">
        <router-link
          v-for="g in grupos"
          :key="g.cod_grupo"
          :to="{ name: 'ConsejerosPublico', query: { grupo: g.cod_grupo } }"
          class="tarjeta"
        >
          <img v-if="g.imagen_url" :src="g.imagen_url" :alt="g.nombre" class="tarjeta-foto" loading="lazy" />
          <div v-else class="tarjeta-foto tarjeta-foto-vacia"><Images :size="30" /></div>

          <span class="tarjeta-velo" />

          <div class="tarjeta-info">
            <h3 class="tarjeta-titulo">
              <component :is="iconoDe(g.cod_grupo)" :size="18" class="tarjeta-icono" />
              {{ nombreCorto(g.nombre) }}
            </h3>
            <p class="tarjeta-desc">
              {{ g.consejeros }}
              {{ g.consejeros === 1 ? 'artista registrado' : 'artistas registrados' }} en esta comisión.
            </p>
          </div>

          <span class="tarjeta-flecha"><ArrowRight :size="18" /></span>
        </router-link>
      </div>
    </section>

    <!-- ============ GALERIA ============ -->
    <section v-if="destacadas.length" class="seccion seccion-galeria">
      <div class="galeria-encabezado">
        <div>
          <p class="kicker-oro">Galería</p>
          <h2 class="titulo-seccion">LO QUE ESTAMOS HACIENDO</h2>
        </div>
        <router-link :to="{ name: 'ConsejerosPublico' }" class="link-simple">
          Ver todo <ArrowRight :size="14" />
        </router-link>
      </div>

      <div class="galeria-grid">
        <router-link
          v-for="act in destacadas"
          :key="act.id"
          :to="{ name: 'ConsejeroDetallePublico', params: { slug: act.persona_slug } }"
          class="galeria-item"
        >
          <img :src="act.imagen_url" :alt="act.descripcion" loading="lazy" />
          <div class="galeria-velo">
            <div class="galeria-nombre">{{ act.persona_nombre }}</div>
          </div>
        </router-link>
      </div>
    </section>

    <!-- ============ NOSOTROS ============ -->
    <section id="nosotros" class="seccion seccion-nosotros">
      <div class="nosotros-foto">
        <img v-if="fotoNosotros" :src="fotoNosotros" alt="Actividad cultural" loading="lazy" />
        <div v-else class="nosotros-foto-vacia"><Sparkles :size="34" /></div>
      </div>

      <div class="nosotros-texto">
        <p class="kicker-oro">Nosotros</p>
        <h2 class="titulo-seccion alineado">
          MÁS QUE UNA INSTITUCIÓN,<br />
          SOMOS COMUNIDAD.
        </h2>
        <p class="parrafo">
          Reunimos a las comisiones especializadas que sostienen la música, la danza, el teatro, la
          literatura, las artes plásticas y los saberes tradicionales de nuestra región.
        </p>
        <p class="parrafo">
          Cada artista registrado suma su trabajo a una memoria colectiva que se construye todos los días.
        </p>
        <router-link :to="{ name: 'ConsejerosPublico' }" class="btn-oro">
          Conocer a la comunidad <ArrowRight :size="16" />
        </router-link>
      </div>
    </section>

    <!-- ============ CONTACTO ============ -->
    <section class="contacto">
      <div v-for="item in contacto" :key="item.label" class="contacto-item">
        <component :is="item.icono" :size="20" class="contacto-icono" />
        <div>
          <div class="contacto-label">{{ item.label }}</div>
          <div class="contacto-valor">
            <div v-for="linea in item.valor" :key="linea">{{ linea }}</div>
          </div>
        </div>
      </div>
    </section>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import {
  ArrowRight, Play, Users, Layers, Images, Sparkles,
  Music, PersonStanding, Drama, BookOpen, Shirt, Video, Palette, Megaphone,
  MapPin, Phone, Mail, Clock,
} from 'lucide-vue-next'
import PersonaPublicaService from '@/services/PersonaPublicaService'

const stats = ref({ consejeros: 0, comisiones: 0, grupos: 0, actividades: 0 })
const grupos = ref([])
const destacadas = ref([])

// cada grupo tiene su ícono según su disciplina; si aparece uno nuevo, cae en Sparkles
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

// los nombres vienen como "Comision especializada de danza" — en las tarjetas
// sobra el prefijo, ocupa toda la línea y se repite en las 8
function nombreCorto(nombre) {
  return (nombre || '').replace(/^comisi[oó]n\s+especializada\s+de\s+/i, '').trim() || nombre
}

const datos = computed(() => [
  {
    icono: Layers,
    valor: stats.value.grupos,
    label: 'Comisiones especializadas',
    desc: 'Una por cada disciplina artística.',
  },
  {
    icono: Users,
    valor: stats.value.consejeros,
    label: 'Artistas registrados',
    desc: 'Portadores de nuestra identidad cultural.',
  },
  {
    icono: Images,
    valor: stats.value.actividades,
    label: 'Actividades publicadas',
    desc: 'Trabajo cultural documentado y abierto.',
  },
  {
    icono: Sparkles,
    valor: stats.value.comisiones,
    label: 'Familias culturales',
    desc: 'Agrupaciones dentro de cada comisión.',
  },
])

// posters del hero: se muestran completos en su marco y el mismo
// archivo se reusa, borroso, como fondo de la sección
const posters = computed(() => destacadas.value.slice(0, 5))
const posterIndex = ref(0)
const posterActual = computed(() => posters.value[posterIndex.value] || null)

let posterTimer
function rotarPosters() {
  clearInterval(posterTimer)
  if (posters.value.length < 2) return
  posterTimer = setInterval(() => {
    posterIndex.value = (posterIndex.value + 1) % posters.value.length
  }, 6000)
}

onBeforeUnmount(() => clearInterval(posterTimer))

const fotoNosotros = computed(() => destacadas.value[3]?.imagen_url || destacadas.value[0]?.imagen_url || null)

// TODO: reemplazar por los datos institucionales reales
const contacto = [
  { icono: MapPin, label: 'Dirección', valor: ['Completar dirección', 'Puno, Perú'] },
  { icono: Phone, label: 'Teléfono', valor: ['Completar teléfono'] },
  { icono: Mail, label: 'Correo', valor: ['Completar correo'] },
  { icono: Clock, label: 'Atención', valor: ['Lun - Vie: 8:00 - 16:00', 'Sábado: 8:00 - 12:00'] },
]

function irA(id) {
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}

onMounted(async () => {
  const data = await PersonaPublicaService.getPortada()
  stats.value = data.stats
  grupos.value = data.grupos
  destacadas.value = data.destacadas
  rotarPosters()
})
</script>

<style scoped>
.landing {
  background: #000;
  color: #fff;
  font-family: 'Inter', sans-serif;
}

.oro {
  color: #00a7e5;
}

/* ============ HERO ============ */
.hero {
  position: relative;
  /* ocupa toda la pantalla descontando la altura del header fijo */
  min-height: calc(100vh - 72px);
  min-height: calc(100svh - 72px);
  display: flex;
  flex-direction: column;
  padding: 48px 28px 0;
  overflow: hidden;
}

/* el poster cubre toda la pantalla */
.hero-fondo {
  position: absolute;
  inset: 0;
}

.hero-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
}

/* oscurece lo justo: fuerte donde va el texto y en los bordes (header y
   franja de datos), liviano a la derecha para que el poster se vea */
.hero-velo {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(
      90deg,
      rgba(0, 0, 0, 0.94) 0%,
      rgba(0, 0, 0, 0.82) 32%,
      rgba(0, 0, 0, 0.45) 62%,
      rgba(0, 0, 0, 0.3) 100%
    ),
    linear-gradient(to bottom, rgba(0, 0, 0, 0.55) 0%, rgba(0, 0, 0, 0.1) 30%, rgba(0, 0, 0, 0.75) 100%);
}

/* el margin auto (arriba y abajo) centra el bloque en el espacio libre
   y empuja la franja de datos contra el borde inferior del hero */
.hero-contenido {
  position: relative;
  width: 100%;
  max-width: 1180px;
  margin: auto;
  padding: 32px 0 56px;
}

.hero-texto {
  max-width: 620px;
}

.poster-enter-active,
.poster-leave-active {
  transition: opacity 0.9s ease;
}

.poster-enter-from,
.poster-leave-to {
  opacity: 0;
}

.poster-puntos {
  display: flex;
  gap: 8px;
  margin-top: 40px;
}

.poster-punto {
  width: 7px;
  height: 7px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.35);
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease;
}

.poster-punto.activo {
  background: #00a7e5;
  transform: scale(1.3);
}

.kicker {
  font-size: 0.92rem;
  font-weight: 700;
  line-height: 1.7;
  letter-spacing: 0.04em;
  color: #e9e9e9;
  margin: 0 0 18px;
}

.titulo {
  font-size: clamp(2.6rem, 7vw, 5rem);
  font-weight: 900;
  line-height: 1.02;
  letter-spacing: -0.02em;
  margin: 0;
}

.barra-oro {
  display: block;
  width: 96px;
  height: 5px;
  background: #00a7e5;
  margin: 26px 0 26px;
}

.bajada {
  max-width: 440px;
  color: #b4b4b4;
  font-size: 0.95rem;
  line-height: 1.75;
  margin: 0 0 32px;
}

.hero-botones {
  display: flex;
  align-items: center;
  gap: 18px;
  flex-wrap: wrap;
}

.btn-oro {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  background: #00a7e5;
  color: #101010;
  text-decoration: none;
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 15px 26px;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease;
}

.btn-oro:hover {
  background: #00a8e5ce;
  transform: translateY(-2px);
}

.btn-fantasma {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  background: none;
  border: none;
  color: #fff;
  font-family: inherit;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  cursor: pointer;
  padding: 8px 0;
}

.play {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid #4a4a4a;
  color: #fff;
  transition: border-color 0.2s ease, color 0.2s ease;
}

.btn-fantasma:hover .play {
  border-color: #00a7e5;
  color: #00a7e5;
}

/* franja de stats */
.stats {
  position: relative;
  max-width: 1180px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr;
  background: rgba(20, 20, 20, 0.92);
  border: 1px solid #262626;
  border-radius: 6px;
  transform: translateY(1px);
}

@media (min-width: 640px) {
  .stats {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1000px) {
  .stats {
    grid-template-columns: repeat(4, 1fr);
  }
}

.stat {
  display: flex;
  gap: 14px;
  padding: 26px 24px;
  border-bottom: 1px solid #262626;
}

.stat:last-child {
  border-bottom: none;
}

@media (min-width: 1000px) {
  .stat {
    border-bottom: none;
    border-right: 1px solid #262626;
  }
  .stat:last-child {
    border-right: none;
  }
}

.stat-icono {
  color: #00a7e5;
  flex: none;
  margin-top: 2px;
}

.stat-numero {
  font-size: 1.6rem;
  font-weight: 800;
  line-height: 1;
  color: #fff;
}

.stat-label {
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  color: #00a7e5;
  margin-top: 5px;
}

.stat-desc {
  font-size: 0.78rem;
  color: #949494;
  line-height: 1.5;
  margin-top: 5px;
}

/* ============ secciones ============ */
.seccion {
  max-width: 1180px;
  margin: 0 auto;
  padding: 88px 28px;
}

.kicker-oro {
  color: #00a7e5;
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  text-align: center;
  margin: 0 0 12px;
}

.titulo-seccion {
  font-size: clamp(1.5rem, 3.4vw, 2.2rem);
  font-weight: 800;
  letter-spacing: 0.01em;
  text-align: center;
  margin: 0 0 48px;
}

.titulo-seccion.alineado {
  text-align: left;
  margin-bottom: 22px;
}

/* cuando debajo va un subtítulo, el título no necesita todo ese aire */
.titulo-seccion.con-sub {
  margin-bottom: 14px;
}

/* comisiones */
.subtitulo-seccion {
  text-align: center;
  color: #9a9a9a;
  font-size: 0.9rem;
  line-height: 1.7;
  max-width: 440px;
  margin: 0 auto 46px;
}

.grid-comisiones {
  display: grid;
  grid-template-columns: 1fr;
  gap: 22px;
}

@media (min-width: 880px) {
  .grid-comisiones {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* tarjeta grande: la foto ocupa todo y el texto va encima */
.tarjeta {
  position: relative;
  display: block;
  aspect-ratio: 4/3;
  border-radius: 12px;
  overflow: hidden;
  text-decoration: none;
  transition: border-color 0.25s ease, transform 0.25s ease;
}

@media (min-width: 880px) {
  .tarjeta {
    aspect-ratio: 16/9;
  }
}

.tarjeta:hover {
  border-color: #00a7e5;
  transform: translateY(-4px);
}

.tarjeta-foto {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  filter: grayscale(35%);
  transition: transform 0.5s ease, filter 0.3s ease;
}

.tarjeta:hover .tarjeta-foto {
  transform: scale(1.05);
  filter: grayscale(0);
}

.tarjeta-foto-vacia {
  display: grid;
  place-items: center;
  background: #1c1c1c;
  color: #3d3d3d;
}

.tarjeta-velo {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgba(0, 0, 0, 0.93) 0%,
    rgba(0, 0, 0, 0.7) 32%,
    rgba(0, 0, 0, 0.2) 62%,
    transparent 100%
  );
}

.tarjeta-info {
  position: absolute;
  left: 22px;
  right: 82px;
  bottom: 20px;
}

.tarjeta-titulo {
  display: flex;
  align-items: center;
  gap: 9px;
  font-size: 0.95rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #fff;
  line-height: 1.3;
  margin: 0 0 7px;
}

.tarjeta-icono {
  color: #00a7e5;
  flex: none;
}

.tarjeta-desc {
  font-size: 0.82rem;
  color: #c2c2c2;
  line-height: 1.55;
  margin: 0;
}

.tarjeta-flecha {
  position: absolute;
  right: 20px;
  bottom: 20px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #00a7e5;
  color: #fff;
  display: grid;
  place-items: center;
  transition: background 0.2s ease, transform 0.2s ease;
}

.tarjeta:hover .tarjeta-flecha {
  background: #00a8e5ce;
  transform: translateX(3px);
}

/* galeria */
.seccion-galeria {
  border-top: 1px solid #1a1a1a;
}

.galeria-encabezado {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 40px;
}

.galeria-encabezado .kicker-oro,
.galeria-encabezado .titulo-seccion {
  text-align: left;
  margin-bottom: 0;
}

.link-simple {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  color: #b4b4b4;
  text-decoration: none;
  font-size: 0.73rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  white-space: nowrap;
}

.link-simple:hover {
  color: #00a7e5;
}

.galeria-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}

@media (min-width: 700px) {
  .galeria-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

.galeria-item {
  position: relative;
  aspect-ratio: 1;
  overflow: hidden;
  border-radius: 4px;
  display: block;
}

.galeria-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  filter: grayscale(40%);
  transition: transform 0.45s ease, filter 0.3s ease;
}

.galeria-item:hover img {
  transform: scale(1.07);
  filter: grayscale(0);
}

.galeria-velo {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: flex-end;
  padding: 12px;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.85), transparent 55%);
}

.galeria-nombre {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #fff;
}

/* nosotros */
.seccion-nosotros {
  border-top: 1px solid #1a1a1a;
  display: grid;
  grid-template-columns: 1fr;
  gap: 44px;
  align-items: center;
}

@media (min-width: 900px) {
  .seccion-nosotros {
    grid-template-columns: 1fr 1fr;
    gap: 64px;
  }
}

.nosotros-foto {
  aspect-ratio: 4/3;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid #232323;
  background: #141414;
}

.nosotros-foto img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  filter: grayscale(25%);
}

.nosotros-foto-vacia {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
  color: #3d3d3d;
}

.nosotros-texto .kicker-oro {
  text-align: left;
}

.parrafo {
  color: #a8a8a8;
  font-size: 0.93rem;
  line-height: 1.8;
  margin: 0 0 16px;
}

.nosotros-texto .btn-oro {
  margin-top: 14px;
}

/* contacto */
.contacto {
  border-top: 1px solid #1a1a1a;
  background: #0d0d0d;
  display: grid;
  grid-template-columns: 1fr;
  max-width: 1180px;
  margin: 0 auto;
}

@media (min-width: 640px) {
  .contacto {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1000px) {
  .contacto {
    grid-template-columns: repeat(4, 1fr);
  }
}

.contacto-item {
  display: flex;
  gap: 13px;
  padding: 30px 26px;
}

.contacto-icono {
  color: #00a7e5;
  flex: none;
  margin-top: 2px;
}

.contacto-label {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #fff;
  margin-bottom: 6px;
}

.contacto-valor {
  font-size: 0.83rem;
  color: #949494;
  line-height: 1.6;
}
</style>
