<template>
  <q-page class="directorio publico">
    <!-- ══════════ CABECERA ══════════ -->
    <header class="cabecera">
      <div class="cabecera-fondo">
        <img src="/images/wall2.jpg" alt="" />
        <div class="cabecera-velo" />
      </div>

      <div class="cabecera-cuerpo">
        <nav class="miga" aria-label="Ruta">
          <router-link :to="{ name: 'Inicio' }">Inicio</router-link>
          <ChevronRight :size="14" />
          <span>Artistas</span>
        </nav>

        <h1>Artistas del registro</h1>
        <p class="cabecera-bajada">
          Músicos, danzantes, bordadores, mascareros y narradores de {{ INSTITUCION.ciudad }}.
        </p>

        <form class="buscador" @submit.prevent="aplicarBusqueda">
          <Search :size="19" />
          <input
            v-model="termino"
            type="search"
            placeholder="Busca por nombre, familia o disciplina"
            aria-label="Buscar artista"
          />
          <button type="submit">Buscar</button>
        </form>
      </div>
    </header>

    <!-- ══════════ CATEGORÍAS ══════════ -->
    <nav class="categorias" aria-label="Filtrar por disciplina">
      <div class="categorias-pista">
        <button class="categoria" :class="{ activa: !grupo }" @click="cambiarGrupo(null)">
          <LayoutGrid :size="21" />
          <span class="categoria-nombre">Todas</span>
          <span class="categoria-num">{{ totalGeneral }}</span>
        </button>

        <button
          v-for="g in opcionesGrupo"
          :key="g.value"
          class="categoria"
          :class="{ activa: grupo === g.value }"
          :style="{ '--acento': g.color }"
          @click="cambiarGrupo(g.value)"
        >
          <component :is="g.icono" :size="21" />
          <span class="categoria-nombre">{{ g.label }}</span>
          <span class="categoria-num">{{ g.total }}</span>
        </button>
      </div>
    </nav>

    <!-- ══════════ RESULTADOS ══════════ -->
    <section class="resultados">
      <div class="barra">
        <p class="conteo">
          <template v-if="cargando">Buscando…</template>
          <template v-else>
            <strong>{{ meta.total }}</strong>
            {{ meta.total === 1 ? 'artista' : 'artistas' }}
            <template v-if="grupo">en {{ comisionDe(grupo).corto }}</template>
            <template v-if="busquedaActiva">para «{{ busquedaActiva }}»</template>
          </template>
        </p>

        <button v-if="hayFiltros" class="limpiar" @click="limpiar">
          <RotateCcw :size="15" /> Quitar filtros
        </button>
      </div>

      <div v-if="cargando" class="grid">
        <div v-for="n in 12" :key="n" class="esqueleto" />
      </div>

      <div v-else-if="!consejeros.length" class="vacio">
        <UserRoundSearch :size="38" />
        <h2>No encontramos artistas con ese filtro</h2>
        <p>Prueba con otro nombre o mira todas las disciplinas.</p>
        <button class="btn-rojo" @click="limpiar">Ver todos los artistas</button>
      </div>

      <template v-else>
        <div class="grid">
          <ArtistaTarjeta
            v-for="(persona, i) in consejeros"
            :key="persona.id"
            v-revelar="(i % 12) * 40"
            :artista="persona"
          />
        </div>

        <div v-if="meta.pagina < meta.ultima_pagina" class="mas">
          <button class="btn-linea" :disabled="cargandoMas" @click="cargarMas">
            {{ cargandoMas ? 'Cargando…' : 'Ver más artistas' }}
          </button>
          <p class="mas-dato">Mostrando {{ consejeros.length }} de {{ meta.total }}</p>
        </div>
      </template>
    </section>
  </q-page>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  Search,
  ChevronRight,
  UserRoundSearch,
  RotateCcw,
  LayoutGrid,
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
import { INSTITUCION, comisionDe } from '@/config/institucion'
import ArtistaTarjeta from '@/components/ArtistaTarjeta.vue'

const router = useRouter()
const route = useRoute()

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

// filtro y búsqueda viven en la URL: el enlace filtrado se puede compartir
const grupo = ref(route.query.grupo || null)
const termino = ref(route.query.buscar || '')
const busquedaActiva = ref(route.query.buscar || '')

const consejeros = ref([])
const opcionesGrupo = ref([])
const meta = reactive({ pagina: 1, ultima_pagina: 1, total: 0 })
const cargando = ref(true)
const cargandoMas = ref(false)

const hayFiltros = computed(() => Boolean(grupo.value || busquedaActiva.value))
const totalGeneral = computed(() => opcionesGrupo.value.reduce((n, g) => n + g.total, 0))

function sincronizarUrl() {
  const query = {}
  if (grupo.value) query.grupo = grupo.value
  if (busquedaActiva.value) query.buscar = busquedaActiva.value
  router.replace({ query })
}

async function cargar(pagina = 1) {
  if (pagina === 1) cargando.value = true
  else cargandoMas.value = true

  const respuesta = await PersonaPublicaService.getData({
    buscar: busquedaActiva.value || undefined,
    grupo: grupo.value || undefined,
    page: pagina,
  })

  consejeros.value = pagina === 1 ? respuesta.data : [...consejeros.value, ...respuesta.data]
  Object.assign(meta, respuesta.meta)

  cargando.value = false
  cargandoMas.value = false
}

function aplicarBusqueda() {
  busquedaActiva.value = termino.value.trim()
  sincronizarUrl()
  cargar()
}

function cambiarGrupo(valor) {
  grupo.value = valor
  sincronizarUrl()
  cargar()
}

function limpiar() {
  grupo.value = null
  termino.value = ''
  busquedaActiva.value = ''
  sincronizarUrl()
  cargar()
}

function cargarMas() {
  cargar(meta.pagina + 1)
}

onMounted(async () => {
  const grupos = await PersonaPublicaService.getGrupos()
  opcionesGrupo.value = grupos.map((g) => ({
    label: comisionDe(g.cod_grupo).corto,
    color: comisionDe(g.cod_grupo).color,
    icono: iconosPorGrupo[g.cod_grupo] || Sparkles,
    value: g.cod_grupo,
    total: g.consejeros ?? 0,
  }))
  await cargar()
})
</script>

<style scoped lang="scss">
.directorio {
  background-color: var(--papel);
  background-image: var(--patron);
  color: var(--tinta);
  font-family: var(--fuente-texto);
}

/* ══════════ CABECERA ══════════ */
.cabecera {
  position: relative;
  padding: clamp(38px, 5vw, 62px) var(--gutter) clamp(42px, 5vw, 58px);
  overflow: hidden;
}

.cabecera-fondo {
  position: absolute;
  inset: 0;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
}

.cabecera-velo {
  position: absolute;
  inset: 0;
  background: linear-gradient(to right, rgba(22, 32, 46, 0.92) 0%, rgba(22, 32, 46, 0.6) 100%);
}

.cabecera-cuerpo {
  position: relative;
  max-width: var(--ancho);
  margin: 0 auto;
}

.miga {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: var(--t-xs);
  color: rgba(253, 251, 247, 0.7);
  margin-bottom: 14px;

  a {
    color: rgba(253, 251, 247, 0.7);
    text-decoration: none;

    &:hover {
      color: var(--oro-vivo);
    }

    &:focus-visible {
      @include foco;
    }
  }
}

.cabecera h1 {
  @include display(800);
  font-size: var(--t-2xl);
  line-height: 1;
  color: var(--sobre-foto);
  margin: 0 0 12px;
}

.cabecera-bajada {
  color: rgba(253, 251, 247, 0.86);
  font-size: var(--t-md);
  line-height: 1.6;
  max-width: 52ch;
  margin: 0 0 26px;
}

.buscador {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--blanco);
  border-radius: var(--r-full);
  padding: 7px 7px 7px 20px;
  max-width: 560px;
  box-shadow: var(--sombra-alta);

  > svg {
    color: var(--tinta-suave);
    flex: none;
  }

  input {
    flex: 1;
    min-width: 0;
    border: none;
    background: none;
    outline: none;
    font-family: var(--fuente-texto);
    font-size: var(--t-base);
    color: var(--tinta);
    padding: 11px 0;

    &::placeholder {
      color: var(--tinta-tenue);
    }
  }

  button {
    flex: none;
    border: none;
    cursor: pointer;
    background: var(--rojo);
    color: #fff;
    font-family: var(--fuente-texto);
    font-size: var(--t-sm);
    font-weight: 700;
    padding: 12px 24px;
    border-radius: var(--r-full);
    transition: background var(--transicion);

    &:hover {
      background: var(--rojo-hondo);
    }

    &:focus-visible {
      @include foco;
    }
  }
}

/* ══════════ CATEGORÍAS ══════════
   Una fila de disciplinas con su ícono, su nombre y cuántos artistas tiene.
   La activa se marca con color y una línea abajo: el color nunca va solo. */
.categorias {
  position: sticky;
  top: 66px;
  z-index: 60;
  background: rgba(251, 248, 243, 0.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid var(--borde);
}

.categorias-pista {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 6px var(--gutter);
  display: flex;
  gap: 4px;
  overflow-x: auto;
  scrollbar-width: none;

  &::-webkit-scrollbar {
    display: none;
  }
}

.categoria {
  position: relative;
  flex: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  min-width: 92px;
  padding: 14px 12px 12px;
  border: none;
  background: none;
  cursor: pointer;
  color: var(--tinta-suave);
  font-family: var(--fuente-texto);
  opacity: 0.72;
  transition:
    opacity var(--transicion),
    color var(--transicion);

  &::after {
    content: '';
    position: absolute;
    left: 12px;
    right: 12px;
    bottom: 0;
    height: 2px;
    border-radius: 2px;
    background: transparent;
    transition: background var(--transicion);
  }

  &:hover {
    opacity: 1;
    color: var(--tinta);

    &::after {
      background: var(--borde-fuerte);
    }
  }

  &.activa {
    opacity: 1;
    color: var(--acento, var(--tinta));

    &::after {
      background: var(--acento, var(--tinta));
    }
  }

  &:focus-visible {
    @include foco;
  }
}

.categoria-nombre {
  font-size: 0.74rem;
  font-weight: 700;
  text-align: center;
  line-height: 1.2;
}

.categoria-num {
  font-size: 0.66rem;
  font-variant-numeric: tabular-nums;
  opacity: 0.7;
}

/* ══════════ RESULTADOS ══════════ */
.resultados {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: clamp(24px, 3vw, 34px) var(--gutter) clamp(56px, 8vw, 90px);
}

.barra {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 22px;
  flex-wrap: wrap;
}

.conteo {
  font-size: var(--t-sm);
  color: var(--tinta-suave);
  margin: 0;

  strong {
    color: var(--tinta);
    font-size: var(--t-md);
  }
}

.limpiar {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  background: none;
  border: 1px solid var(--borde-fuerte);
  border-radius: var(--r-full);
  color: var(--rojo);
  font-family: var(--fuente-texto);
  font-size: var(--t-sm);
  font-weight: 700;
  cursor: pointer;
  padding: 8px 16px;
  transition: all var(--transicion);

  &:hover {
    border-color: var(--rojo);
    background: var(--blanco);
  }

  &:focus-visible {
    @include foco;
  }
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(168px, 1fr));
  gap: clamp(12px, 1.6vw, 20px);
}

.esqueleto {
  aspect-ratio: 4 / 5;
  border-radius: var(--r-md);
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

@media (prefers-reduced-motion: reduce) {
  .esqueleto {
    animation: none;
  }
}

.vacio {
  text-align: center;
  padding: clamp(48px, 8vw, 90px) var(--gutter);
  color: var(--tinta-suave);
  background: var(--blanco);
  border: 1px solid var(--borde);
  border-radius: var(--r-lg);

  svg {
    color: var(--tinta-tenue);
  }

  h2 {
    @include display(700);
    font-size: 1.3rem;
    color: var(--tinta);
    margin: 16px 0 8px;
  }

  p {
    margin: 0 0 22px;
    font-size: var(--t-base);
  }
}

.mas {
  text-align: center;
  margin-top: 38px;
}

.mas-dato {
  margin: 12px 0 0;
  font-size: var(--t-xs);
  color: var(--tinta-tenue);
}

.btn-linea,
.btn-rojo {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  font-family: var(--fuente-texto);
  font-size: var(--t-sm);
  font-weight: 700;
  padding: 14px 30px;
  border-radius: var(--r-full);
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

  &:hover:not(:disabled) {
    border-color: var(--rojo);
    color: var(--rojo);
  }

  &:disabled {
    opacity: 0.6;
    cursor: default;
  }
}

.btn-rojo {
  background: var(--rojo);
  color: #fff;
  border: none;

  &:hover {
    background: var(--rojo-hondo);
  }
}
</style>
