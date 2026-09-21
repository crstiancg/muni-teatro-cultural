<template>
  <router-link
    :to="{ name: 'ConsejeroDetallePublico', params: { slug: artista.slug } }"
    class="artista"
    :style="{ '--acento': comision.color }"
  >
    <img v-if="artista.imagen_url" :src="artista.imagen_url" alt="" loading="lazy" />
    <div v-else class="sin-foto"><UserRound :size="30" /></div>

    <span class="velo" />

    <span class="etiqueta">{{ comision.corto }}</span>

    <span v-if="artista.total_actividades" class="fotos">
      <Images :size="12" />
      {{ artista.total_actividades }}
    </span>

    <div class="texto">
      <h3>{{ artista.nombre_completo }}</h3>
      <p>{{ artista.comision || 'Sin comisión' }}</p>
    </div>
  </router-link>
</template>

<script setup>
import { computed } from 'vue'
import { UserRound, Images } from 'lucide-vue-next'
import { comisionDe } from '@/config/institucion'

const props = defineProps({
  artista: { type: Object, required: true },
})

const comision = computed(() => comisionDe(props.artista.cod_grupo))
</script>

<style scoped lang="scss">
/* Sin barra blanca: la foto ocupa toda la tarjeta y el nombre va encima,
   igual que en la pieza destacada. Menos ruido, más imagen. */
.artista {
  position: relative;
  display: block;
  aspect-ratio: 4 / 5;
  border-radius: var(--r-md);
  overflow: hidden;
  text-decoration: none;
  background: var(--crema);
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
    transition: transform 0.8s ease;
  }

  &:hover > img {
    transform: scale(1.06);
  }
}

.sin-foto {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
  color: var(--tinta-tenue);
}

/* el degradado sostiene el texto sobre cualquier foto, clara u oscura */
.velo {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgba(22, 32, 46, 0.92) 0%,
    rgba(22, 32, 46, 0.55) 32%,
    rgba(22, 32, 46, 0.08) 62%,
    transparent 100%
  );
}

.etiqueta {
  position: absolute;
  left: 10px;
  top: 10px;
  max-width: calc(100% - 20px);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  background: var(--blanco);
  color: var(--acento);
  font-size: 0.66rem;
  font-weight: 700;
  padding: 5px 11px;
  border-radius: var(--r-full);
}

.fotos {
  position: absolute;
  right: 10px;
  bottom: 12px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(22, 32, 46, 0.68);
  backdrop-filter: blur(4px);
  color: var(--sobre-foto);
  font-size: 0.66rem;
  font-weight: 600;
  padding: 5px 9px;
  border-radius: var(--r-full);
}

.texto {
  position: absolute;
  inset: auto 0 0 0;
  padding: 16px 14px 14px;

  h3 {
    @include display(700);
    font-size: 1.02rem;
    line-height: 1.16;
    color: var(--sobre-foto);
    margin: 0 0 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  p {
    font-size: var(--t-xs);
    color: rgba(253, 251, 247, 0.82);
    margin: 0;
    padding-right: 44px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}
</style>
