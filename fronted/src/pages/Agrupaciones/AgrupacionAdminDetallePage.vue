<template>
  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el><Home size="16" /></q-breadcrumbs-el>
        <q-breadcrumbs-el label="Agrupaciones" :to="{ name: 'AgrupacionesAdmin' }" />
        <q-breadcrumbs-el :label="agrupacion?.nombre || '...'" />
      </q-breadcrumbs>
    </div>
    <q-separator />

    <div v-if="cargando" class="text-center q-pa-lg">
      <q-spinner color="primary" size="40px" />
    </div>

    <div v-else-if="!agrupacion" class="text-center q-pa-lg">
      <div class="q-mb-sm">No se pudo cargar la agrupación.</div>
      <q-btn outline no-caps color="primary" label="Reintentar" @click="cargar" />
    </div>

    <div v-else class="row q-col-gutter-md q-ma-sm">
      <div class="col-12 col-md-4">
        <q-card flat :bordered="!$q.dark.isActive">
          <q-card-section class="text-center">
            <q-avatar size="120px" color="primary" text-color="white" rounded class="text-h3">
              <img v-if="agrupacion.logo?.url" :src="agrupacion.logo.url" style="object-fit: cover" />
              <template v-else>{{ agrupacion.nombre.charAt(0) }}</template>
            </q-avatar>
            <div class="text-h6 q-mt-sm">{{ agrupacion.nombre }}</div>
            <div class="text-caption text-grey-7">{{ agrupacion.comision || 'Sin comisión' }}</div>
          </q-card-section>
        </q-card>

        <!-- mismas reglas que AgrupacionController::aprobar/observar -->
        <q-card flat :bordered="!$q.dark.isActive" class="q-mt-md">
          <q-card-section class="column q-gutter-sm">
            <div class="row items-center justify-between no-wrap">
              <div class="text-subtitle1 text-weight-bold">Revisión</div>
              <q-chip dense square :color="estado.color" text-color="white" class="q-ma-none">
                {{ estado.label }}
              </q-chip>
            </div>
            <q-banner
              v-if="agrupacion.estado === 'observado' && agrupacion.observacion"
              dense
              rounded
              class="bg-orange-1 text-orange-10 observacion"
            >
              <strong>Observación:</strong> {{ agrupacion.observacion }}
            </q-banner>
            <template v-if="userStore.hasPermission('admin-agrupaciones-aprobar')">
              <q-btn
                v-if="agrupacion.estado === 'pendiente'"
                unelevated
                no-caps
                color="positive"
                label="Aprobar"
                :loading="procesando"
                @click="aprobar"
              />
              <q-btn
                v-if="['pendiente', 'aprobado'].includes(agrupacion.estado)"
                outline
                no-caps
                color="orange-9"
                :label="agrupacion.estado === 'aprobado' ? 'Despublicar con observación' : 'Observar'"
                :disable="procesando"
                @click="observar"
              />
            </template>
            <div v-if="['borrador', 'observado'].includes(agrupacion.estado)" class="text-caption text-grey-7">
              Esperando que el representante la envíe a revisión.
            </div>
          </q-card-section>
        </q-card>

        <q-card flat :bordered="!$q.dark.isActive" class="q-mt-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-sm">Historial de revisiones</div>
            <HistorialRevisiones :revisiones="agrupacion.revisiones" />
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-8">
        <q-card flat :bordered="!$q.dark.isActive">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-sm">Descripción</div>
            <!-- HTML sanitizado en el backend al guardar -->
            <div v-if="agrupacion.descripcion" class="descripcion" v-html="agrupacion.descripcion" />
            <div v-else class="text-grey-6">Sin descripción.</div>

            <div v-if="redes.length" class="row q-gutter-sm q-mt-md">
              <q-btn
                v-for="r in redes"
                :key="r.clave"
                outline
                dense
                no-caps
                color="primary"
                :href="r.url"
                target="_blank"
                :label="r.clave"
              />
            </div>
          </q-card-section>
        </q-card>

        <q-card flat :bordered="!$q.dark.isActive" class="q-mt-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              Integrantes ({{ agrupacion.integrantes.length }})
            </div>
            <IntegrantesTabla :integrantes="agrupacion.integrantes" />
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { Home } from 'lucide-vue-next'
import HistorialRevisiones from '@/components/HistorialRevisiones.vue'
import IntegrantesTabla from '@/components/IntegrantesTabla.vue'
import AgrupacionService from '@/services/AgrupacionService'
import { estadoPerfil } from '@/config/estadosPerfil'
import { useNotify } from '@/composables/useNotify'
import { useUserStore } from '@/stores/user-store'

const $q = useQuasar()
const route = useRoute()
const userStore = useUserStore()
const { notifySuccess, notifyError } = useNotify()
const id = Number(route.params.id)

const agrupacion = ref(null)
const cargando = ref(true)
const procesando = ref(false)

const estado = computed(() => estadoPerfil(agrupacion.value?.estado))
const redes = computed(() =>
  Object.entries(agrupacion.value?.redes_sociales || {}).map(([clave, url]) => ({ clave, url })),
)

async function cargar() {
  cargando.value = true
  try {
    agrupacion.value = await AgrupacionService.ver(id)
  } catch {
    agrupacion.value = null
  } finally {
    cargando.value = false
  }
}

async function ejecutar(accion, mensaje) {
  procesando.value = true
  try {
    agrupacion.value = await accion()
    notifySuccess(mensaje)
  } catch (error) {
    notifyError(error.response?.data?.message || 'No se pudo completar la acción.')
  } finally {
    procesando.value = false
  }
}

const aprobar = () => ejecutar(() => AgrupacionService.aprobar(id), 'Agrupación aprobada y publicada.')

function observar() {
  $q.dialog({
    title: 'Observar agrupación',
    message: 'Indica qué debe corregir. El representante recibirá este mensaje.',
    prompt: { model: '', type: 'textarea', isValid: (v) => v.trim().length > 0 },
    cancel: true,
    persistent: true,
  }).onOk((texto) => ejecutar(() => AgrupacionService.observar(id, texto.trim()), 'Observación enviada.'))
}

onMounted(cargar)
</script>

<style scoped>
.observacion,
.descripcion {
  overflow-wrap: anywhere;
  word-break: break-word;
}

.descripcion :deep(p),
.descripcion :deep(div) {
  margin: 0 0 0.6em;
}
</style>
