<template>
  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el><Home size="16" /></q-breadcrumbs-el>
        <q-breadcrumbs-el label="Mis agrupaciones"><UsersRound size="16" class="q-ml-xs" /></q-breadcrumbs-el>
      </q-breadcrumbs>
    </div>
    <q-separator />

    <div class="q-pa-md">
      <div class="row items-center justify-between q-col-gutter-md q-mb-md">
        <div class="col-12 col-sm">
          <div class="text-h6 text-weight-bold">Mis agrupaciones</div>
          <div class="text-caption texto-secundario">
            Las agrupaciones que representas o donde figuras como integrante.
          </div>
        </div>
        <div class="col-12 col-sm-auto row items-center q-gutter-sm justify-end">
          <!-- tope por persona: config/agrupaciones.php en el backend -->
          <span v-if="!cargando" class="cupo">
            Representas {{ representadas }} de {{ limite }}
          </span>
          <q-btn unelevated no-caps color="primary" :disable="!puedeCrear" @click="mostrarCrear = true">
            <Plus :size="16" class="q-mr-xs" /> Crear agrupación
            <q-tooltip v-if="!puedeCrear && !cargando">
              Ya representas el máximo de {{ limite }} {{ limite === 1 ? 'agrupación' : 'agrupaciones' }}
            </q-tooltip>
          </q-btn>
        </div>
      </div>

      <div v-if="cargando" class="grilla">
        <q-skeleton v-for="n in 2" :key="n" height="280px" class="rounded-borders" />
      </div>

      <div v-else-if="errorCarga" class="text-center q-pa-lg">
        <div class="q-mb-sm">{{ errorCarga }}</div>
        <q-btn outline no-caps color="primary" label="Reintentar" @click="cargar" />
      </div>

      <q-card v-else-if="!agrupaciones.length" flat bordered class="tarjeta text-center q-pa-xl">
        <UsersRound :size="40" class="texto-secundario" />
        <div class="text-subtitle1 text-weight-bold q-mt-sm">Todavía no figuras en ninguna agrupación</div>
        <div class="text-body2 texto-secundario q-mb-md">
          Si diriges o representas un conjunto, créalo, carga a sus integrantes y envíalo a revisión
          para que aparezca en el portal.
        </div>
        <q-btn unelevated no-caps color="primary" label="Crear agrupación" @click="mostrarCrear = true" />
      </q-card>

      <div v-else class="grilla">
        <q-card v-for="a in agrupaciones" :key="a.id" flat bordered class="tarjeta">
          <!-- portada (o el color de la marca si no tiene) con el logo encima -->
          <div class="portada">
            <img v-if="a.portada_url" :src="a.portada_url" alt="" />
            <q-chip dense square :color="estadoPerfil(a.estado).color" text-color="white" class="chip-estado">
              {{ estadoPerfil(a.estado).label }}
            </q-chip>
          </div>

          <q-card-section class="cuerpo">
            <q-avatar size="64px" color="primary" text-color="white" rounded class="logo">
              <img v-if="a.logo?.miniatura_url" :src="a.logo.miniatura_url" />
              <template v-else>{{ a.nombre.charAt(0) }}</template>
            </q-avatar>

            <div class="text-h6 text-weight-bold ellipsis">{{ a.nombre }}</div>
            <div class="text-body2 texto-secundario ellipsis">{{ a.comision || 'Sin comisión' }}</div>

            <div class="row items-center q-gutter-sm q-mt-sm">
              <q-chip v-if="a.es_representante" dense square outline color="primary" class="q-ma-none">
                Representante
              </q-chip>
              <q-chip v-else dense square outline class="q-ma-none">{{ a.rol || 'Integrante' }}</q-chip>
              <span class="dato"><Users :size="14" /> {{ a.total_integrantes }}</span>
              <span class="dato"><Images :size="14" /> {{ a.total_actividades }}</span>
            </div>

            <!-- qué sigue, según el estado: el representante sabe qué hacer sin entrar -->
            <div v-if="a.es_representante" class="siguiente">
              <template v-if="a.estado === 'aprobado'">
                <CircleCheck :size="16" class="text-positive" /> Publicada en el portal
              </template>
              <template v-else-if="a.estado === 'pendiente'">
                <Clock :size="16" class="text-blue-7" /> En revisión del administrador
              </template>
              <template v-else-if="a.estado === 'observado'">
                <MessageSquareWarning :size="16" class="text-orange-9" />
                <span class="ellipsis-2-lines">Observada: {{ a.observacion }}</span>
              </template>
              <template v-else-if="a.faltan.length">
                <Circle :size="16" class="texto-secundario" />
                <span>Falta: {{ a.faltan.join(', ') }}</span>
              </template>
              <template v-else>
                <Send :size="16" class="text-primary" /> Lista para enviar a revisión
              </template>
            </div>
            <div v-else class="siguiente texto-secundario">
              <Info :size="16" /> La gestiona su representante
            </div>
          </q-card-section>

          <q-card-actions class="q-px-md q-pb-md q-pt-none">
            <q-btn
              v-if="a.es_representante"
              unelevated
              no-caps
              color="primary"
              class="col"
              :to="{ name: 'MiAgrupacion', params: { id: a.id } }"
            >
              Gestionar
            </q-btn>
            <q-btn
              v-if="a.estado === 'aprobado'"
              outline
              no-caps
              color="primary"
              class="col"
              :to="{ name: 'AgrupacionPublica', params: { slug: a.slug } }"
              target="_blank"
            >
              <ExternalLink :size="15" class="q-mr-xs" /> Ver en el portal
            </q-btn>
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="mostrarCrear">
      <q-card :style="{ width: '100%', maxWidth: '680px' }">
        <q-card-section class="row items-center">
          <div class="text-h6">Crear agrupación</div>
          <q-space />
          <q-btn v-close-popup flat round dense icon="close" />
        </q-card-section>
        <q-separator />
        <q-card-section>
          <AgrupacionDatosForm @guardado="alCrear" />
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  Home,
  UsersRound,
  Users,
  Images,
  Plus,
  CircleCheck,
  Circle,
  Clock,
  Send,
  Info,
  MessageSquareWarning,
  ExternalLink,
} from 'lucide-vue-next'
import AgrupacionDatosForm from '@/components/AgrupacionDatosForm.vue'
import AgrupacionService from '@/services/AgrupacionService'
import { estadoPerfil } from '@/config/estadosPerfil'

const router = useRouter()
const agrupaciones = ref([])
const representadas = ref(0)
const limite = ref(0)
const cargando = ref(true)
const errorCarga = ref('')
const mostrarCrear = ref(false)

// el backend también lo valida: esto solo evita un botón que va a fallar
const puedeCrear = computed(() => !cargando.value && representadas.value < limite.value)

async function cargar() {
  cargando.value = true
  errorCarga.value = ''
  try {
    const r = await AgrupacionService.mias()
    agrupaciones.value = r.agrupaciones
    representadas.value = r.representadas
    limite.value = r.limite
  } catch (error) {
    errorCarga.value = error.response?.data?.message || 'No se pudieron cargar tus agrupaciones.'
  } finally {
    cargando.value = false
  }
}

// recién creada: se va directo a cargar logo, portada e integrantes
function alCrear(agrupacion) {
  mostrarCrear.value = false
  router.push({ name: 'MiAgrupacion', params: { id: agrupacion.id } })
}

onMounted(cargar)
</script>

<style scoped>
.texto-secundario {
  opacity: 0.68;
}

.grilla {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 16px;
}

.tarjeta {
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.cupo {
  font-size: 0.85rem;
  font-weight: 600;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(128, 128, 128, 0.12);
}

.portada {
  position: relative;
  aspect-ratio: 16 / 6;
  background: linear-gradient(135deg, #004173, #2a6fb0);
}

.portada img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.chip-estado {
  position: absolute;
  top: 10px;
  right: 10px;
  margin: 0;
  font-weight: 700;
}

.cuerpo {
  flex: 1;
}

/* el logo se monta sobre el borde de la portada */
.logo {
  margin-top: -48px;
  margin-bottom: 8px;
  border: 3px solid white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.logo img {
  object-fit: cover;
}

.dato {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.85rem;
  opacity: 0.75;
}

.siguiente {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-top: 12px;
  padding: 10px 12px;
  border-radius: 10px;
  background: rgba(128, 128, 128, 0.08);
  font-size: 0.85rem;
  overflow-wrap: anywhere;
}
</style>
