<template>
  <q-page class="page publico">
    <div v-if="cargando" class="cargando">Cargando...</div>

    <template v-else-if="persona">
      <nav class="breadcrumb">
        <router-link :to="{ name: 'ConsejerosPublico' }" class="crumb">
          <Home :size="14" /> Inicio
        </router-link>
        <ChevronRight :size="14" class="crumb-sep" />
        <router-link :to="{ name: 'ConsejerosPublico' }" class="crumb">Consejeros</router-link>
        <ChevronRight :size="14" class="crumb-sep" />
        <span class="crumb crumb-actual">{{ persona.nombre_completo }}</span>
      </nav>

      <section class="hero">
        <div class="badge">
          <Award :size="14" /> Miembro de {{ persona.comision || 'una comisión' }}
        </div>
        <h1 class="nombre">
          <span class="nombre-claro">{{ persona.nombre }}</span>
          <span class="nombre-oscuro">{{ persona.apellidos }}</span>
        </h1>
      </section>

      <div class="cuerpo">
        <div class="columnas">
          <!-- columna izquierda -->
          <div class="col-izq">
            <div class="tarjeta foto-tarjeta">
              <div class="foto-placeholder">{{ inicial }}</div>
              <div class="foto-banner">Teatro Cultural</div>
            </div>
            <div class="chip-comision">Comisión — {{ persona.comision }}</div>

            <div class="tarjeta">
              <div class="tarjeta-header">
                <Award :size="16" /> Comisiones Asignadas
              </div>
              <ul class="lista-comisiones">
                <li><span class="dot" />{{ persona.comision }}</li>
                <li v-if="persona.comision_alternativo">
                  <span class="dot" />{{ persona.comision_alternativo }}
                  <span class="etiqueta">alternativa</span>
                </li>
              </ul>
            </div>

            <div v-if="persona.actividades?.length" class="tarjeta">
              <div class="tarjeta-header entre">
                <span><Images :size="16" /> Galería de Fotos</span>
                <router-link :to="{ name: 'ConsejeroGaleriaPublico', params: { id: persona.id } }" class="ver-todas">
                  Ver todas <ChevronRight :size="14" />
                </router-link>
              </div>
              <div class="mini-grid">
                <img
                  v-for="act in persona.actividades.slice(0, 4)"
                  :key="act.id"
                  :src="act.imagen_url"
                  class="mini-thumb"
                  loading="lazy"
                />
              </div>
            </div>
          </div>

          <!-- columna derecha -->
          <div class="col-der">
            <div class="tarjeta">
              <div class="tarjeta-header">
                <User :size="16" /> Información Personal
              </div>

              <div class="datos-grid">
                <div v-if="persona.dni" class="dato">
                  <div class="dato-label"><IdCard :size="13" /> Documento de Identidad</div>
                  <div class="dato-valor">{{ persona.dni }}</div>
                </div>

                <div v-if="persona.celular" class="dato">
                  <div class="dato-label"><Phone :size="13" /> Teléfono</div>
                  <div class="dato-valor">+51 {{ persona.celular }}</div>
                </div>

                <div v-if="persona.correo" class="dato dato-ancho">
                  <div class="dato-label"><Mail :size="13" /> Correo</div>
                  <div class="dato-valor">
                    <a :href="`mailto:${persona.correo}`" class="dato-enlace">{{ persona.correo }}</a>
                  </div>
                </div>
              </div>
            </div>

            <div class="tarjeta">
              <div class="tarjeta-header entre">
                <span><Newspaper :size="16" /> Actividades Recientes</span>
                <router-link
                  v-if="persona.actividades?.length"
                  :to="{ name: 'ConsejeroGaleriaPublico', params: { id: persona.id } }"
                  class="ver-todas"
                >
                  Ver todas <ChevronRight :size="14" />
                </router-link>
              </div>

              <div v-if="!persona.actividades?.length" class="vacio">Todavía no hay actividades publicadas.</div>

              <div v-else class="lista-actividades">
                <div v-for="act in persona.actividades.slice(0, 3)" :key="act.id" class="actividad-item">
                  <div class="actividad-cabecera">
                    <span class="dot" />
                    <span class="actividad-titulo">{{ act.descripcion.slice(0, 90) }}</span>
                  </div>
                  <div class="actividad-fecha">
                    <Calendar :size="13" /> {{ formatoFecha(act.created_at) }}
                  </div>
                </div>

                <router-link
                  :to="{ name: 'ConsejeroGaleriaPublico', params: { id: persona.id } }"
                  class="boton-ver-todas"
                >
                  Ver todas las actividades ({{ persona.actividades.length }}) <ChevronRight :size="16" />
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="cargando">No se encontró este consejero.</div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Home, ChevronRight, Award, Images, Newspaper, Calendar, User, IdCard, Phone, Mail } from 'lucide-vue-next'
import PersonaPublicaService from '@/services/PersonaPublicaService'

const route = useRoute()
const cargando = ref(true)
const persona = ref(null)

const inicial = computed(() => (persona.value?.nombre?.charAt(0) || '?').toUpperCase())

function formatoFecha(fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(async () => {
  try {
    persona.value = await PersonaPublicaService.get(route.params.id)
  } catch {
    persona.value = null
  } finally {
    cargando.value = false
  }
})
</script>

<style scoped>
.page {
  background: #f7f5f0;
  font-family: 'Inter', sans-serif;
}

.cargando {
  text-align: center;
  color: #999;
  padding: 80px 24px;
}

/* ---------- breadcrumb ---------- */
.breadcrumb {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 14px 24px;
  background: #f7f5f0;
  border-bottom: 1px solid #ddd8cc;
  font-size: 0.82rem;
}

.crumb {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #777;
  text-decoration: none;
  transition: color 0.15s ease;
}

.crumb:hover {
  color: #111;
}

.crumb-actual {
  color: #111;
  font-weight: 600;
}

.crumb-sep {
  color: #bbb;
}

/* ---------- hero ---------- */
.hero {
  background: #0a0a0a;
  padding: 40px 24px 56px;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.1);
  color: #ccc;
  border-radius: 100px;
  padding: 6px 16px;
  font-size: 0.72rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-bottom: 16px;
}

.nombre {
  font-family: 'Inter', sans-serif;
  font-weight: 300;
  font-size: clamp(1.8rem, 4.5vw, 3rem);
  line-height: 1.15;
  margin: 0;
}

.nombre-claro {
  color: #f5f5f0;
  font-weight: 700;
}

.nombre-oscuro {
  color: #999;
  font-weight: 300;
  margin-left: 10px;
}

/* ---------- body ---------- */
.cuerpo {
  max-width: 1100px;
  margin: 0 auto;
  padding: 32px 24px 72px;
}

.columnas {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
}

@media (min-width: 900px) {
  .columnas {
    grid-template-columns: 320px 1fr;
  }
}

.tarjeta {
  background: #fff;
  border-radius: 10px;
  border: 1px solid #ddd8cc;
  padding: 20px;
  margin-bottom: 20px;
}

.tarjeta-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 0.92rem;
  color: #111;
  margin-bottom: 16px;
}

.tarjeta-header.entre {
  justify-content: space-between;
}

.ver-todas {
  display: flex;
  align-items: center;
  gap: 2px;
  font-size: 0.75rem;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: #777;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.15s ease;
}

.ver-todas:hover {
  color: #111;
}

/* foto */
.foto-tarjeta {
  padding: 0;
  overflow: hidden;
}

.foto-placeholder {
  aspect-ratio: 3/4;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(160deg, #2a2a2a, #0a0a0a);
  color: #f5f5f0;
  font-family: 'Inter', sans-serif;
  font-size: 3.5rem;
  font-weight: 700;
}

.foto-banner {
  background: #111;
  color: #ccc;
  text-align: center;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  padding: 10px;
}

.chip-comision {
  text-align: center;
  font-size: 0.72rem;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: #555;
  background: transparent;
  border: 1px solid #ddd8cc;
  border-radius: 100px;
  padding: 6px 12px;
  margin: -8px auto 20px;
  width: fit-content;
}

/* información personal */
.datos-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px 24px;
}

@media (min-width: 560px) {
  .datos-grid {
    grid-template-columns: 1fr 1fr;
  }
  .dato-ancho {
    grid-column: 1 / -1;
  }
}

.dato-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  color: #888;
  margin-bottom: 4px;
}

.dato-valor {
  font-size: 0.9rem;
  font-weight: 600;
  color: #111;
  word-break: break-word;
}

.dato-enlace {
  color: #111;
  text-decoration: none;
  border-bottom: 1px solid #ddd8cc;
}

.dato-enlace:hover {
  border-color: #111;
}

/* comisiones */
.lista-comisiones {
  list-style: none;
  margin: 0;
  padding: 0;
  font-size: 0.88rem;
  color: #333;
}

.lista-comisiones li {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 0;
}

.dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #111;
  flex: none;
}

.etiqueta {
  font-size: 0.68rem;
  color: #999;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

/* mini galeria */
.mini-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.mini-thumb {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  border-radius: 6px;
  display: block;
}

/* actividades */
.vacio {
  color: #999;
  font-size: 0.88rem;
}

.actividad-item {
  padding: 12px 0;
  border-bottom: 1px solid #eee;
}

.actividad-cabecera {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.actividad-titulo {
  font-weight: 600;
  font-size: 0.86rem;
  color: #111;
  letter-spacing: 0.01em;
  line-height: 1.4;
}

.actividad-fecha {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #999;
  font-size: 0.75rem;
  margin-top: 4px;
  margin-left: 14px;
}

.boton-ver-todas {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  margin-top: 16px;
  padding: 10px;
  border-radius: 100px;
  border: 1px solid #ddd8cc;
  color: #333;
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.boton-ver-todas:hover {
  border-color: #111;
  background: #111;
  color: #f5f5f0;
}
</style>
