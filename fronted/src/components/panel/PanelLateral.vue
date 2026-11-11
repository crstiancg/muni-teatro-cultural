<template>
  <nav class="lateral" aria-label="Menú del panel">
    <router-link :to="{ name: 'Dashboard' }" class="marca">
      <span class="marca-logo"><Drama :size="20" /></span>
      <span class="marca-texto">
        <span class="marca-nombre">CPC</span>
        <span class="marca-bajada">Gestión cultural · Puno</span>
      </span>
    </router-link>

    <div class="secciones">
      <section v-for="(seccion, i) in secciones" :key="seccion.titulo ?? i" class="seccion">
        <h2 v-if="seccion.titulo" class="seccion-titulo">{{ seccion.titulo }}</h2>
        <router-link
          v-for="item in seccion.items"
          :key="item.routeName"
          :to="{ name: item.routeName }"
          class="item"
          :class="{ activo: estaActivo(item) }"
          :aria-current="estaActivo(item) ? 'page' : undefined"
        >
          <component :is="item.icon" :size="18" class="item-icono" />
          <span class="item-texto">{{ item.label }}</span>
          <span
            v-if="item.contador && contadores[item.contador]"
            class="contador"
            :aria-label="`${contadores[item.contador]} pendientes`"
          >
            {{ contadores[item.contador] }}
          </span>
        </router-link>
      </section>
    </div>

    <a :href="urlPortal" target="_blank" rel="noopener" class="portal">
      <ExternalLink :size="15" /> Ver el portal público
    </a>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Drama, ExternalLink } from 'lucide-vue-next'

defineProps({
  secciones: { type: Array, required: true },
  // { eventos: 3, agrupaciones: 1, ... }
  contadores: { type: Object, default: () => ({}) },
})

const route = useRoute()
const router = useRouter()

const urlPortal = computed(() => router.resolve({ name: 'Inicio' }).href)

// activo también en las pantallas hijas (gestion/eventos/12 → Agenda cultural)
function estaActivo(item) {
  const ruta = router.resolve({ name: item.routeName }).path
  return route.path === ruta || (ruta !== '/' && route.path.startsWith(ruta + '/'))
}
</script>

<style scoped>
.lateral {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: var(--panel-lateral);
  color: var(--panel-texto);
  border-right: 1px solid var(--panel-borde);
}

.marca {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 18px 18px 14px;
  color: inherit;
  text-decoration: none;
}

.marca-logo {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: var(--panel-acento);
  color: var(--panel-sobre-acento);
  flex-shrink: 0;
}

.marca-texto {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.marca-nombre {
  font-family: 'Fraunces', Georgia, serif;
  font-weight: 800;
  font-size: 1.15rem;
  letter-spacing: -0.01em;
}

.marca-bajada {
  font-size: 0.75rem;
  color: var(--panel-texto-suave);
}

.secciones {
  flex: 1;
  overflow-y: auto;
  padding: 4px 10px 12px;
}

.seccion + .seccion {
  margin-top: 14px;
}

.seccion-titulo {
  margin: 0;
  padding: 0 10px 6px;
  font-size: 0.68rem;
  line-height: 1.4;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--panel-texto-suave);
}

.item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 8px;
  color: inherit;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  transition: background 0.12s;
}

.item + .item {
  margin-top: 2px;
}

.item:hover {
  background: var(--panel-hover);
}

.item-icono {
  flex-shrink: 0;
  color: var(--panel-texto-suave);
}

.item-texto {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item.activo {
  background: var(--panel-superficie);
  font-weight: 700;
  box-shadow: 0 1px 2px rgba(28, 28, 28, 0.08);
}

/* el activo se marca con superficie y peso; el rojo queda para marca y pendientes */
.item.activo .item-icono {
  color: var(--panel-texto);
}

.contador {
  display: inline-grid;
  place-items: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 999px;
  background: var(--panel-acento);
  color: var(--panel-sobre-acento);
  font-size: 0.72rem;
  font-weight: 700;
  line-height: 1;
}

.portal {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 10px 12px;
  padding: 10px;
  border-top: 1px solid var(--panel-borde);
  color: var(--panel-texto-suave);
  font-size: 0.82rem;
  text-decoration: none;
}

.portal:hover {
  color: var(--panel-texto);
}
</style>
