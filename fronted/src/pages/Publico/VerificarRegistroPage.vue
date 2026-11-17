<template>
  <q-page class="verificar-page publico">
    <header class="cabecera">
      <div class="contenedor">
        <p class="kicker">Consulta de autenticidad</p>
        <h1>Verificación de ficha de registro</h1>
        <p class="bajada">{{ INSTITUCION.entidad }} · {{ INSTITUCION.ciudad }}</p>
      </div>
    </header>

    <div class="contenedor cuerpo">
      <div v-if="cargando" class="tarjeta centrado" role="status">
        <q-spinner size="32px" />
        <p>Consultando el registro…</p>
      </div>

      <div v-else-if="error" class="tarjeta centrado" role="alert">
        <SearchX :size="40" class="icono-vacio" />
        <h2>{{ error === 404 ? 'No encontramos este registro' : 'No se pudo consultar' }}</h2>
        <p>
          {{
            error === 404
              ? 'El código QR no corresponde a ninguna ficha emitida. Si la ficha es auténtica, revisa que el enlace esté completo.'
              : 'Hubo un problema de conexión. Intenta de nuevo en unos minutos.'
          }}
        </p>
      </div>

      <article v-else class="tarjeta">
        <div class="estado" :class="registro.vigente ? 'ok' : 'no'">
          <component :is="registro.vigente ? BadgeCheck : ShieldAlert" :size="28" />
          <div>
            <strong>{{ registro.vigente ? 'Registro vigente' : `Registro ${registro.estado.toLowerCase()}` }}</strong>
            <span>
              {{
                registro.vigente
                  ? 'Esta persona está inscrita y habilitada en el Registro de Agentes Culturales.'
                  : 'La inscripción existe, pero hoy no está vigente. La ficha impresa no acredita la inscripción.'
              }}
            </span>
          </div>
        </div>

        <div class="persona">
          <img v-if="registro.foto_url" :src="registro.foto_url" :alt="registro.nombre_completo" class="foto" />
          <div>
            <p class="codigo">{{ registro.codigo }}</p>
            <h2 class="nombre">{{ registro.nombre_completo }}</h2>
            <p class="tipo">{{ registro.tipo_agente }}</p>
          </div>
        </div>

        <dl class="datos">
          <div>
            <dt>Comisión principal</dt>
            <dd>{{ registro.comision || '—' }}</dd>
          </div>
          <div>
            <dt>Especialidad</dt>
            <dd>{{ registro.especialidad || '—' }}</dd>
          </div>
          <div>
            <dt>Fecha de inscripción</dt>
            <dd>{{ fecha(registro.registrado_en) }}</dd>
          </div>
          <div>
            <dt>Consultado el</dt>
            <dd>{{ consultadoEn }}</dd>
          </div>
        </dl>

        <router-link
          v-if="registro.perfil_slug"
          :to="{ name: 'ConsejeroDetallePublico', params: { slug: registro.perfil_slug } }"
          class="ver-perfil"
        >
          Ver su portafolio <ArrowRight :size="16" />
        </router-link>
      </article>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { BadgeCheck, ShieldAlert, SearchX, ArrowRight } from 'lucide-vue-next'
import { INSTITUCION, tituloPagina } from '@/config/institucion'
import FichaRegistroService from '@/services/FichaRegistroService'

const route = useRoute()
const cargando = ref(true)
const error = ref(null)
const registro = ref(null)
const consultadoEn = new Date().toLocaleString('es-PE', { dateStyle: 'long', timeStyle: 'short' })

// "2026-11-17" es una fecha sin hora: new Date() la leería como UTC y en Perú
// mostraría el día anterior
function fecha(iso) {
  if (!iso) return '—'
  const [a, m, d] = iso.split('-').map(Number)
  return new Date(a, m - 1, d).toLocaleDateString('es-PE', { dateStyle: 'long' })
}

onMounted(async () => {
  document.title = tituloPagina('Verificación de ficha de registro')
  try {
    registro.value = await FichaRegistroService.verificar(route.params.token)
  } catch (e) {
    error.value = e.response?.status === 404 ? 404 : 'red'
  } finally {
    cargando.value = false
  }
})
</script>

<style scoped lang="scss">
.verificar-page {
  background: var(--papel);
  color: var(--tinta);
  padding-bottom: var(--seccion-y);
}

.contenedor {
  max-width: 680px;
  margin: 0 auto;
  padding: 0 var(--gutter);
}

.cabecera {
  background: var(--noche);
  color: var(--sobre-foto);
  padding: clamp(40px, 6vw, 64px) 0 clamp(56px, 8vw, 80px);
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
  margin: 0;
  opacity: 0.8;
}

/* la tarjeta se monta sobre el borde de la cabecera */
.cuerpo {
  margin-top: -36px;
}

.tarjeta {
  background: var(--blanco);
  border: 1px solid var(--borde);
  border-radius: 16px;
  padding: clamp(20px, 4vw, 32px);
  box-shadow: 0 12px 32px rgb(22 32 46 / 8%);
}

.centrado {
  text-align: center;
  color: var(--tinta-suave);

  h2 {
    @include display(700);
    font-size: var(--t-md);
    color: var(--tinta);
    margin: 12px 0 6px;
  }

  p {
    margin: 8px 0 0;
    line-height: 1.6;
  }
}

.icono-vacio {
  color: var(--tinta-tenue);
}

.estado {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 14px 16px;
  border-radius: 12px;
  margin-bottom: 24px;

  strong {
    display: block;
    font-size: var(--t-md);
  }

  span {
    font-size: var(--t-sm);
    line-height: 1.5;
  }

  svg {
    flex-shrink: 0;
  }

  &.ok {
    background: #e6f4ea;
    color: #1e6b34;
    border: 1px solid #b7dcc1;
  }

  &.no {
    background: #fbe9eb;
    color: var(--rojo-hondo);
    border: 1px solid #efc1c8;
  }
}

.persona {
  display: flex;
  gap: 16px;
  align-items: center;
  margin-bottom: 24px;
}

.foto {
  width: 84px;
  height: 84px;
  border-radius: 12px;
  object-fit: cover;
  flex-shrink: 0;
}

.codigo {
  font-family: ui-monospace, 'SFMono-Regular', Consolas, monospace;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: var(--lago);
  margin: 0 0 2px;
}

.nombre {
  @include display(800);
  font-size: var(--t-lg);
  margin: 0;
  line-height: 1.15;
  overflow-wrap: anywhere;
}

.tipo {
  margin: 4px 0 0;
  color: var(--tinta-suave);
  font-size: var(--t-sm);
}

.datos {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px 24px;
  margin: 0;
  padding-top: 20px;
  border-top: 1px solid var(--borde);

  dt {
    font-size: var(--t-xs);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--tinta-suave);
  }

  dd {
    margin: 2px 0 0;
    font-weight: 600;
  }

  @media (max-width: 480px) {
    grid-template-columns: 1fr;
  }
}

.ver-perfil {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 24px;
  font-weight: 600;
  color: var(--rojo);
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }

  &:focus-visible {
    @include foco;
  }
}
</style>
