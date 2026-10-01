<template>
  <q-page class="agrupaciones-page">
    <header class="cabecera">
      <div class="contenedor">
        <p class="kicker">Registro cultural</p>
        <h1>Agrupaciones</h1>
        <p class="bajada">
          Conjuntos, comparsas y elencos que mantienen viva la cultura de {{ INSTITUCION.ciudad }}.
        </p>

        <div class="buscador">
          <Search :size="18" />
          <input v-model="buscar" type="search" placeholder="Buscar una agrupación" @input="buscarConPausa" />
        </div>
      </div>
    </header>

    <div class="contenedor">
      <!-- filtro por disciplina: mismo criterio que el directorio de artistas -->
      <div class="filtros">
        <button class="filtro" :class="{ activo: !grupo }" @click="elegirGrupo(null)">Todas</button>
        <button
          v-for="(c, cod) in COMISIONES"
          :key="cod"
          class="filtro"
          :class="{ activo: grupo === cod }"
          :style="{ '--acento': c.color }"
          @click="elegirGrupo(cod)"
        >
          {{ c.corto }}
        </button>
      </div>

      <p v-if="!cargando || agrupaciones.length" class="total">
        {{ total }} {{ total === 1 ? 'agrupación' : 'agrupaciones' }}
      </p>

      <div v-if="agrupaciones.length" class="grilla">
        <router-link
          v-for="a in agrupaciones"
          :key="a.slug"
          :to="{ name: 'AgrupacionPublica', params: { slug: a.slug } }"
          class="tarjeta"
          :style="{ '--acento': comisionDe(a.cod_grupo).color }"
        >
          <div class="tarjeta-portada">
            <img v-if="a.portada_url" :src="a.portada_url" alt="" loading="lazy" />
            <span class="tarjeta-etiqueta">{{ comisionDe(a.cod_grupo).corto }}</span>
          </div>
          <div class="tarjeta-cuerpo">
            <span class="tarjeta-logo">
              <img v-if="a.logo_url" :src="a.logo_url" :alt="a.nombre" loading="lazy" />
              <template v-else>{{ a.nombre.charAt(0) }}</template>
            </span>
            <h2>{{ a.nombre }}</h2>
            <p class="tarjeta-comision">{{ a.comision }}</p>
            <p class="tarjeta-integrantes"><Users :size="14" /> {{ a.total_integrantes }} integrantes</p>
          </div>
        </router-link>
      </div>

      <div v-else-if="!cargando" class="vacio">
        <Users :size="32" />
        <p>
          {{ buscar || grupo ? 'No hay agrupaciones que coincidan.' : 'Todavía no hay agrupaciones publicadas.' }}
        </p>
      </div>

      <div class="mas">
        <q-spinner v-if="cargando" size="28px" color="primary" />
        <button v-else-if="pagina < ultimaPagina" class="btn-mas" @click="cargar(pagina + 1)">
          Cargar más
        </button>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Search, Users } from 'lucide-vue-next'
import AgrupacionService from '@/services/AgrupacionService'
import { INSTITUCION, COMISIONES, comisionDe } from '@/config/institucion'

const route = useRoute()
const router = useRouter()

const agrupaciones = ref([])
const cargando = ref(false)
const pagina = ref(1)
const ultimaPagina = ref(1)
const total = ref(0)
// el filtro vive en la URL: se puede compartir "/agrupaciones?grupo=02"
const grupo = ref(route.query.grupo || null)
const buscar = ref('')

async function cargar(p = 1) {
  cargando.value = true
  try {
    const r = await AgrupacionService.publicas({
      page: p,
      grupo: grupo.value || undefined,
      buscar: buscar.value || undefined,
    })
    agrupaciones.value = p === 1 ? r.data : [...agrupaciones.value, ...r.data]
    pagina.value = r.meta.pagina
    ultimaPagina.value = r.meta.ultima_pagina
    total.value = r.meta.total
  } catch {
    agrupaciones.value = p === 1 ? [] : agrupaciones.value
  } finally {
    cargando.value = false
  }
}

function elegirGrupo(cod) {
  grupo.value = cod
  router.replace({ query: cod ? { grupo: cod } : {} })
  cargar(1)
}

let pausa = null
function buscarConPausa() {
  clearTimeout(pausa)
  pausa = setTimeout(() => cargar(1), 350)
}

onMounted(() => {
  document.title = 'Agrupaciones · Registro cultural'
  cargar(1)
})
</script>

<style scoped lang="scss">
.agrupaciones-page {
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
  margin: 0 0 24px;
  max-width: 56ch;
  opacity: 0.8;
}

.buscador {
  display: flex;
  align-items: center;
  gap: 10px;
  max-width: 460px;
  padding: 12px 16px;
  border-radius: var(--r-full);
  background: rgba(253, 251, 247, 0.1);
  border: 1px solid rgba(253, 251, 247, 0.25);

  input {
    flex: 1;
    border: 0;
    outline: 0;
    background: none;
    color: inherit;
    font: inherit;

    &::placeholder {
      color: rgba(253, 251, 247, 0.6);
    }
  }
}

.filtros {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 24px 0 8px;
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

.total {
  margin: 8px 0 18px;
  font-size: var(--t-sm);
  color: var(--tinta-suave);
}

.grilla {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
  gap: 20px;
}

.tarjeta {
  display: flex;
  flex-direction: column;
  background: var(--blanco);
  border: 1px solid var(--borde);
  border-radius: var(--r-md);
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition:
    transform var(--transicion),
    box-shadow var(--transicion);

  &:hover {
    transform: translateY(-3px);
    box-shadow: var(--sombra);
  }

  &:hover .tarjeta-portada img {
    transform: scale(1.04);
  }

  &:focus-visible {
    @include foco;
  }
}

.tarjeta-portada {
  position: relative;
  aspect-ratio: 16 / 7;
  overflow: hidden;
  /* sin portada: el color de la disciplina */
  background: linear-gradient(135deg, var(--noche), color-mix(in srgb, var(--acento) 70%, var(--noche)));

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
  }
}

.tarjeta-etiqueta {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 3px 9px;
  border-radius: var(--r-full);
  background: var(--acento);
  color: var(--sobre-foto);
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.tarjeta-cuerpo {
  position: relative;
  padding: 0 18px 18px;
}

.tarjeta-logo {
  display: grid;
  place-items: center;
  width: 64px;
  height: 64px;
  margin-top: -32px;
  margin-bottom: 10px;
  border-radius: var(--r-md);
  overflow: hidden;
  background: var(--blanco);
  border: 3px solid var(--blanco);
  box-shadow: var(--sombra);
  color: var(--acento);
  @include display(800);
  font-size: 1.6rem;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

h2 {
  @include display(700);
  font-size: var(--t-md);
  margin: 0 0 4px;
  line-height: 1.25;
  overflow-wrap: anywhere;
}

.tarjeta-comision {
  margin: 0 0 10px;
  font-size: var(--t-sm);
  color: var(--tinta-suave);
}

.tarjeta-integrantes {
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 0;
  font-size: var(--t-xs);
  font-weight: 600;
  color: var(--tinta-suave);
}

.vacio {
  display: grid;
  justify-items: center;
  gap: 8px;
  padding: 64px 16px;
  color: var(--tinta-suave);
  text-align: center;
}

.mas {
  display: flex;
  justify-content: center;
  padding-top: 32px;
}

.btn-mas {
  padding: 12px 28px;
  border-radius: var(--r-full);
  border: 1px solid var(--borde-fuerte);
  background: var(--blanco);
  color: var(--tinta);
  font: inherit;
  font-weight: 700;
  cursor: pointer;

  &:hover {
    border-color: var(--rojo);
    color: var(--rojo);
  }
}
</style>
