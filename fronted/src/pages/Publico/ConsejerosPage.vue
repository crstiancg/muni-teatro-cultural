<template>
  <q-page class="page publico">
    <section class="hero">
      <h1 class="hero-title">Conocé a nuestros<br />consejeros y sus actividades</h1>

      <div v-if="destacadas.length" class="fan-row">
        <div
          v-for="(act, i) in destacadas"
          :key="act.id"
          class="fan-card"
          :style="estiloFan(i)"
          @click="verPerfil(act.persona_id)"
        >
          <img :src="act.imagen_url" class="fan-img" loading="lazy" />
          <div class="fan-caption">
            <div class="fan-desc">{{ act.descripcion }}</div>
            <div class="fan-name">{{ act.persona_nombre }}</div>
          </div>
        </div>
      </div>
    </section>

    <section class="listado">
      <div class="controls">
        <div class="search-box">
          <Search :size="18" class="search-icon" />
          <input v-model="buscar" type="text" placeholder="Buscar por nombre" class="search-input" />
        </div>

        <div class="filtros">
          <button
            class="filtro-chip"
            :class="{ activo: !grupo }"
            @click="grupo = null"
          >
            Todos
          </button>
          <button
            v-for="g in opcionesGrupo"
            :key="g.value"
            class="filtro-chip"
            :class="{ activo: grupo === g.value }"
            @click="grupo = grupo === g.value ? null : g.value"
          >
            {{ g.label }}
          </button>
        </div>
      </div>

      <div v-if="cargando" class="grid">
        <div v-for="n in 8" :key="n" class="skeleton-tile" />
      </div>

      <div v-else-if="!consejeros.length" class="vacio">
        <Users :size="40" />
        <p>No se encontraron consejeros con ese filtro.</p>
      </div>

      <div v-else class="grid">
        <div v-for="persona in consejeros" :key="persona.id" class="tile" @click="verPerfil(persona.id)">
          <div class="tile-avatar">{{ inicial(persona.nombre_completo) }}</div>
          <div class="tile-info">
            <div class="tile-name">{{ persona.nombre_completo }}</div>
            <div class="tile-comision">{{ persona.comision || 'Sin comisión' }}</div>
          </div>
          <ArrowRight :size="18" class="tile-arrow" />
        </div>
      </div>
    </section>
  </q-page>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Search, Users, ArrowRight } from 'lucide-vue-next'
import PersonaPublicaService from '@/services/PersonaPublicaService'

const router = useRouter()
const route = useRoute()
const buscar = ref('')
// el filtro arranca desde la URL para que los enlaces de la portada lleguen filtrados
const grupo = ref(route.query.grupo || null)
const consejeros = ref([])
const opcionesGrupo = ref([])
const destacadas = ref([])
const cargando = ref(true)

function inicial(nombre) {
  return (nombre?.charAt(0) || '?').toUpperCase()
}

function estiloFan(i) {
  const centro = (destacadas.value.length - 1) / 2
  const distancia = i - centro
  const rotacion = distancia * 6
  const traslado = Math.abs(distancia) * 14
  return {
    transform: `rotate(${rotacion}deg) translateY(${traslado}px)`,
    zIndex: 100 - Math.abs(distancia),
  }
}

function verPerfil(id) {
  router.push({ name: 'ConsejeroDetallePublico', params: { id } })
}

let debounceId
watch(buscar, () => {
  clearTimeout(debounceId)
  debounceId = setTimeout(cargar, 400)
})

// el filtro también se refleja en la URL, así se puede compartir el enlace filtrado
watch(grupo, (valor) => {
  router.replace({ query: valor ? { grupo: valor } : {} })
  cargar()
})

async function cargar() {
  cargando.value = true
  consejeros.value = await PersonaPublicaService.getData({ buscar: buscar.value || undefined, grupo: grupo.value || undefined })
  cargando.value = false
}

onMounted(async () => {

  const [grupos, actividades] = await Promise.all([
    PersonaPublicaService.getGrupos(),
    PersonaPublicaService.getDestacadas(),
  ])
  opcionesGrupo.value = grupos.map((g) => ({ label: g.nombre, value: g.cod_grupo }))
  destacadas.value = actividades.slice(0, 5)
  await cargar()
})
</script>

<style scoped>
.page {
  background: #f7f5f0;
  font-family: 'Inter', sans-serif;
}

/* ---------- hero ---------- */
.hero {
  background: #0a0a0a;
  padding: 72px 24px 64px;
  text-align: center;
}

.hero-title {
  font-family: 'Inter', sans-serif;
  font-weight: 300;
  font-size: clamp(1.7rem, 4vw, 2.6rem);
  line-height: 1.3;
  color: #f5f5f0;
  max-width: 620px;
  margin: 0 auto 56px;
}

.fan-row {
  display: flex;
  justify-content: center;
  align-items: flex-end;
  padding: 0 16px;
}

.fan-card {
  width: 160px;
  height: 220px;
  flex: none;
  margin: 0 -18px;
  border-radius: 6px;
  overflow: hidden;
  position: relative;
  cursor: pointer;
  box-shadow: 0 14px 34px rgba(0, 0, 0, 0.55);
  transition: transform 0.25s ease;
}

.fan-card:hover {
  transform: translateY(-10px) rotate(0deg) !important;
  z-index: 200 !important;
}

.fan-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.fan-caption {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 30px 12px 12px;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.88), transparent);
}

.fan-desc {
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  font-size: 0.85rem;
  color: #fff;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.fan-name {
  font-size: 0.68rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: #bbb;
  margin-top: 4px;
}

@media (max-width: 700px) {
  .fan-row {
    flex-wrap: wrap;
    gap: 8px;
  }
  .fan-card {
    width: 120px;
    height: 170px;
    margin: 4px;
    transform: none !important;
  }
}

/* ---------- listado ---------- */
.listado {
  max-width: 1080px;
  margin: 0 auto;
  padding: 56px 24px 80px;
}

.controls {
  margin-bottom: 40px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 2px solid #ddd8cc;
  padding: 10px 2px;
  max-width: 420px;
  transition: border-color 0.2s ease;
}

.search-box:focus-within {
  border-color: #111;
}

.search-icon {
  color: #999;
  flex: none;
}

.search-input {
  border: none;
  outline: none;
  background: transparent;
  font-family: 'Inter', sans-serif;
  font-size: 0.95rem;
  width: 100%;
  color: #111;
}

.filtros {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 20px;
}

.filtro-chip {
  border: 1px solid #ddd8cc;
  background: transparent;
  color: #555;
  padding: 6px 14px;
  border-radius: 100px;
  font-size: 0.78rem;
  letter-spacing: 0.02em;
  cursor: pointer;
  transition: all 0.15s ease;
  font-family: 'Inter', sans-serif;
}

.filtro-chip:hover {
  border-color: #111;
  color: #111;
}

.filtro-chip.activo {
  background: #111;
  border-color: #111;
  color: #f5f5f0;
}

/* ---------- grid ---------- */
.grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 2px;
  background: #ddd8cc;
}

@media (min-width: 700px) {
  .grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.tile {
  background: #f7f5f0;
  padding: 28px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.tile:hover {
  background: #fff;
}

.tile:hover .tile-arrow {
  transform: translateX(4px);
  opacity: 1;
}

.tile-avatar {
  flex: none;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #111;
  color: #f5f5f0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 1rem;
}

.tile-info {
  flex: 1;
  min-width: 0;
}

.tile-name {
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 1.02rem;
  color: #111;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tile-comision {
  font-size: 0.72rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #888;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tile-arrow {
  flex: none;
  color: #999;
  opacity: 0;
  transition: all 0.2s ease;
}

.skeleton-tile {
  background: #eeebe0;
  height: 100px;
  animation: pulse 1.4s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.vacio {
  text-align: center;
  color: #999;
  padding: 60px 0;
}
</style>
