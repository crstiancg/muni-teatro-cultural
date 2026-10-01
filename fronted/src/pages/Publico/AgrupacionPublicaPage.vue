<template>
  <q-page class="agrupacion-page">
    <div v-if="cargando" class="estado-carga">Cargando…</div>
    <div v-else-if="!agrupacion" class="estado-carga">No se encontró esta agrupación.</div>

    <template v-else>
      <!-- cabecera: logo, nombre y comisión -->
      <header class="cabecera">
        <div class="contenedor cabecera-fila">
          <div class="logo" :style="{ '--acento': comision.color }">
            <img v-if="agrupacion.logo_url" :src="agrupacion.logo_url" :alt="agrupacion.nombre" />
            <span v-else>{{ agrupacion.nombre.charAt(0) }}</span>
          </div>
          <div class="cabecera-texto">
            <p class="kicker">Agrupación</p>
            <h1>{{ agrupacion.nombre }}</h1>
            <p class="comision">
              <span class="punto" :style="{ background: comision.color }" />
              {{ agrupacion.comision }}
            </p>
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
      </header>

      <div class="contenedor cuerpo">
        <section v-if="agrupacion.descripcion" class="bloque">
          <h2>Sobre la agrupación</h2>
          <!-- HTML sanitizado en el backend al guardar (App\Support\Html::limpio) -->
          <div class="descripcion" v-html="agrupacion.descripcion" />
        </section>

        <section class="bloque">
          <h2>Integrantes <span class="cuenta">{{ agrupacion.integrantes.length }}</span></h2>
          <ul class="integrantes">
            <li v-for="(i, n) in agrupacion.integrantes" :key="n">
              <span class="inicial" :style="{ '--acento': comision.color }">{{ i.nombre_completo.charAt(0) }}</span>
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
                <span class="rol">
                  {{ i.rol || 'Integrante' }}<template v-if="i.es_representante"> · Representante</template>
                </span>
              </div>
            </li>
          </ul>
        </section>
      </div>
    </template>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Facebook, Instagram, Music2, Youtube, Globe } from 'lucide-vue-next'
import AgrupacionService from '@/services/AgrupacionService'
import { comisionDe } from '@/config/institucion'

const route = useRoute()
const agrupacion = ref(null)
const cargando = ref(true)

const comision = computed(() => comisionDe(agrupacion.value?.cod_grupo))

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
}

.cabecera {
  background: var(--crema);
  border-bottom: 1px solid var(--borde);
  padding: clamp(40px, 6vw, 72px) 0;
}

.cabecera-fila {
  display: flex;
  align-items: center;
  gap: clamp(20px, 3vw, 36px);
}

.logo {
  flex: none;
  width: clamp(96px, 14vw, 150px);
  aspect-ratio: 1;
  border-radius: var(--r-lg);
  overflow: hidden;
  display: grid;
  place-items: center;
  background: color-mix(in srgb, var(--acento) 18%, var(--blanco));
  color: var(--acento);
  box-shadow: var(--sombra);
  @include display(800);
  font-size: 3rem;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.cabecera-texto {
  min-width: 0;
}

.kicker {
  @include kicker;
  margin: 0 0 6px;
}

h1 {
  @include display(800);
  font-size: var(--t-xl);
  line-height: 1.05;
  margin: 0 0 10px;
  overflow-wrap: anywhere;
}

.comision {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  color: var(--tinta-suave);
  font-size: var(--t-base);
}

.punto {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.redes {
  display: flex;
  gap: 8px;
  margin-top: 16px;

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

.cuerpo {
  padding-top: clamp(32px, 5vw, 56px);
  display: grid;
  gap: clamp(32px, 5vw, 56px);
}

.bloque h2 {
  @include display(800);
  font-size: var(--t-lg);
  margin: 0 0 18px;
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
  max-width: 68ch;
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

.integrantes {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 12px;

  li {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: var(--blanco);
    border: 1px solid var(--borde);
    border-radius: var(--r-md);
  }
}

.inicial {
  flex: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: color-mix(in srgb, var(--acento) 15%, var(--blanco));
  color: var(--acento);
  font-weight: 800;
}

.integrante-texto {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.nombre {
  font-weight: 700;
  font-size: var(--t-sm);
  color: var(--tinta);
  overflow-wrap: anywhere;
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
}

@media (max-width: 600px) {
  .cabecera-fila {
    flex-direction: column;
    text-align: center;
  }

  .comision,
  .redes {
    justify-content: center;
  }
}
</style>
