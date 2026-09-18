<template>
  <q-page class="page publico">
    <div v-if="cargando" class="cargando">Cargando...</div>

    <template v-else-if="persona">
      <div class="cabecera">
        <router-link :to="{ name: 'ConsejeroDetallePublico', params: { slug: persona.slug } }" class="volver">
          <ArrowLeft :size="18" /> Volver
        </router-link>

        <div class="persona-mini">
          <div class="mini-avatar">{{ inicial }}</div>
          <div>
            <div class="mini-nombre">{{ persona.nombre_completo }}</div>
            <div class="mini-sub">Galería de fotos</div>
          </div>
        </div>

        <div class="contador">{{ persona.actividades?.length || 0 }} fotografías</div>
      </div>

      <div v-if="!persona.actividades?.length" class="vacio">Todavía no hay actividades publicadas.</div>

      <div v-else class="grid">
        <div v-for="act in persona.actividades" :key="act.id" class="tile" @click="verImagen(act)">
          <img :src="act.imagen_url" class="tile-img" loading="lazy" />
          <div class="tile-overlay">
            <div class="tile-desc">{{ act.descripcion }}</div>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="cargando">No se encontró este consejero.</div>

    <q-dialog v-model="mostrarImagen">
      <div class="lightbox">
        <img :src="imagenSeleccionada?.imagen_url" class="lightbox-img" />
        <div class="lightbox-desc">{{ imagenSeleccionada?.descripcion }}</div>
      </div>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft } from 'lucide-vue-next'
import PersonaPublicaService from '@/services/PersonaPublicaService'

const route = useRoute()
const cargando = ref(true)
const persona = ref(null)
const mostrarImagen = ref(false)
const imagenSeleccionada = ref(null)

const inicial = computed(() => (persona.value?.nombre?.charAt(0) || '?').toUpperCase())

function verImagen(act) {
  imagenSeleccionada.value = act
  mostrarImagen.value = true
}

onMounted(async () => {
  try {
    persona.value = await PersonaPublicaService.get(route.params.slug)
  } catch {
    persona.value = null
  } finally {
    cargando.value = false
  }
})
</script>

<style scoped>
.page {
  background: #f4f5f7;
  font-family: 'Inter', sans-serif;
  min-height: 100vh;
}

.cargando,
.vacio {
  text-align: center;
  color: #999;
  padding: 80px 24px;
}

.cabecera {
  background: #fff;
  border-bottom: 1px solid #e8e9ee;
  padding: 18px 24px;
  display: flex;
  align-items: center;
  gap: 24px;
  flex-wrap: wrap;
}

.volver {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #5b7fc7;
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 500;
}

.persona-mini {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
}

.mini-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #0d1f4a;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 0.9rem;
}

.mini-nombre {
  font-weight: 600;
  font-size: 0.88rem;
  color: #1a1a2e;
}

.mini-sub {
  font-size: 0.75rem;
  color: #999;
}

.contador {
  font-size: 0.8rem;
  color: #999;
}

.grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 4px;
  padding: 4px;
}

@media (min-width: 700px) {
  .grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    padding: 24px;
  }
}

.tile {
  position: relative;
  aspect-ratio: 1;
  cursor: pointer;
  overflow: hidden;
}

.tile-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.25s ease;
}

.tile:hover .tile-img {
  transform: scale(1.05);
}

.tile-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.75), transparent 60%);
  display: flex;
  align-items: flex-end;
  padding: 10px;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.tile:hover .tile-overlay {
  opacity: 1;
}

.tile-desc {
  color: #fff;
  font-size: 0.75rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.lightbox {
  background: #000;
  padding: 0;
}

.lightbox-img {
  max-width: 90vw;
  max-height: 80vh;
  display: block;
}

.lightbox-desc {
  color: #ccc;
  font-size: 0.85rem;
  padding: 14px 18px;
}
</style>
