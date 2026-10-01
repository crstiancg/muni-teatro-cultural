<template>
  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el><Home size="16" /></q-breadcrumbs-el>
        <q-breadcrumbs-el label="Mis agrupaciones" :to="{ name: 'MisAgrupaciones' }" />
        <q-breadcrumbs-el :label="agrupacion?.nombre || '...'" />
      </q-breadcrumbs>
    </div>
    <q-separator />

    <div v-if="cargando && !agrupacion" class="text-center q-pa-lg">
      <q-spinner color="primary" size="40px" />
    </div>

    <div v-else-if="errorCarga" class="text-center q-pa-lg">
      <div class="q-mb-sm">{{ errorCarga }}</div>
      <q-btn outline no-caps color="primary" label="Reintentar" @click="cargar" />
    </div>

    <div v-else-if="agrupacion" class="row q-col-gutter-md q-ma-sm">
      <!-- izquierda: identidad, estado e historial -->
      <div class="col-12 col-md-4">
        <q-card flat :bordered="!$q.dark.isActive">
          <q-card-section class="text-center">
            <FotoPerfilUploader
              :base-path="`mis-agrupaciones/${agrupacion.id}`"
              :inicial="agrupacion.nombre.charAt(0)"
              size="120px"
              v-model="agrupacion.logo"
              @update:model-value="cargar"
            />
            <div class="text-h6 q-mt-sm">{{ agrupacion.nombre }}</div>
            <div class="text-caption text-grey-7">{{ agrupacion.comision || 'Sin comisión' }}</div>
          </q-card-section>
        </q-card>

        <q-card flat :bordered="!$q.dark.isActive" class="q-mt-md">
          <q-card-section class="column q-gutter-sm">
            <div class="row items-center justify-between no-wrap">
              <div class="text-subtitle1 text-weight-bold">En el portal</div>
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
              <strong>Observación del administrador:</strong> {{ agrupacion.observacion }}
            </q-banner>

            <!-- mismo criterio que Agrupacion::requisitos en el backend -->
            <q-list dense>
              <q-item v-for="r in agrupacion.requisitos" :key="r.clave" class="q-px-none">
                <q-item-section avatar style="min-width: 28px">
                  <CircleCheck v-if="r.cumple" :size="18" class="text-positive" />
                  <Circle v-else :size="18" class="text-grey-5" />
                </q-item-section>
                <q-item-section>
                  <q-item-label :class="{ 'text-grey-7': r.cumple }">
                    {{ r.label }}
                    <span v-if="!r.cumple && r.obligatorio" class="obligatorio">Obligatorio</span>
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>

            <q-btn
              v-if="agrupacion.estado === 'aprobado'"
              outline
              no-caps
              color="primary"
              :to="{ name: 'AgrupacionPublica', params: { slug: agrupacion.slug } }"
              target="_blank"
              label="Ver en el portal"
            />
            <div v-else-if="agrupacion.estado === 'pendiente'" class="text-caption text-grey-7">
              Un administrador la está revisando. Te avisaremos en la campanita.
            </div>
            <q-btn
              v-else
              unelevated
              no-caps
              color="primary"
              :disable="faltanObligatorios > 0"
              :loading="enviando"
              :label="agrupacion.estado === 'observado' ? 'Reenviar a revisión' : 'Enviar a revisión'"
              @click="enviar"
            />
          </q-card-section>
        </q-card>

        <q-card flat :bordered="!$q.dark.isActive" class="q-mt-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-sm">Historial de revisiones</div>
            <HistorialRevisiones :revisiones="agrupacion.revisiones" />
          </q-card-section>
        </q-card>
      </div>

      <!-- derecha: datos e integrantes -->
      <div class="col-12 col-md-8">
        <q-card flat :bordered="!$q.dark.isActive" class="q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold">Portada</div>
            <div class="text-caption text-grey-7 q-mb-sm">
              La imagen grande que encabeza la página de la agrupación en el portal.
            </div>
            <PortadaUploader :base-path="`mis-agrupaciones/${agrupacion.id}`" v-model="agrupacion.portada" />
          </q-card-section>
        </q-card>

        <q-card flat :bordered="!$q.dark.isActive">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold q-mb-md">Datos de la agrupación</div>
            <AgrupacionDatosForm :key="agrupacion.updated_at" :agrupacion="agrupacion" @guardado="(a) => (agrupacion = a)" />
          </q-card-section>
        </q-card>

        <q-card flat :bordered="!$q.dark.isActive" class="q-mt-md">
          <q-card-section>
            <div class="row items-center justify-between q-mb-md">
              <div>
                <div class="text-subtitle1 text-weight-bold">
                  Integrantes ({{ agrupacion.integrantes.length }})
                </div>
                <div class="text-caption text-grey-7">
                  En el portal se muestran nombre y rol; el DNI nunca se publica.
                </div>
              </div>
              <q-btn unelevated no-caps color="primary" @click="abrirIntegrante(null)">
                <Plus :size="16" class="q-mr-xs" /> Agregar
              </q-btn>
            </div>
            <IntegrantesTabla
              editable
              transferible
              :integrantes="agrupacion.integrantes"
              @editar="abrirIntegrante"
              @quitar="quitar"
              @transferir="transferir"
            />
          </q-card-section>
        </q-card>

        <q-card flat :bordered="!$q.dark.isActive" class="q-mt-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold">Actividades realizadas</div>
            <div class="text-caption text-grey-7 q-mb-md">
              Presentaciones, concursos y eventos de la agrupación. Las públicas se ven en el portal.
            </div>
            <!-- mismo CRUD que las actividades de un artista (endpoints equivalentes) -->
            <ActividadGallery
              :base-path="`mis-agrupaciones/${agrupacion.id}/actividades`"
              v-model="agrupacion.actividades"
            />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="mostrarIntegrante">
      <IntegranteDialog
        :agrupacion-id="agrupacion.id"
        :integrante="integranteEditado"
        :publicada="agrupacion.estado === 'aprobado'"
        @guardado="alGuardarIntegrante"
      />
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import { Home, Plus, Circle, CircleCheck } from 'lucide-vue-next'
import FotoPerfilUploader from '@/components/FotoPerfilUploader.vue'
import HistorialRevisiones from '@/components/HistorialRevisiones.vue'
import AgrupacionDatosForm from '@/components/AgrupacionDatosForm.vue'
import IntegrantesTabla from '@/components/IntegrantesTabla.vue'
import IntegranteDialog from '@/components/IntegranteDialog.vue'
import PortadaUploader from '@/components/PortadaUploader.vue'
import ActividadGallery from '@/components/ActividadGallery.vue'
import AgrupacionService from '@/services/AgrupacionService'
import { estadoPerfil } from '@/config/estadosPerfil'
import { useNotify } from '@/composables/useNotify'

const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const { notifySuccess, notifyError } = useNotify()
const id = Number(route.params.id)

const agrupacion = ref(null)
const cargando = ref(true)
const errorCarga = ref('')
const enviando = ref(false)
const mostrarIntegrante = ref(false)
const integranteEditado = ref(null)

const estado = computed(() => estadoPerfil(agrupacion.value?.estado))
const faltanObligatorios = computed(
  () => (agrupacion.value?.requisitos || []).filter((r) => r.obligatorio && !r.cumple).length,
)

// se recarga entera tras cada cambio que mueve el checklist (logo, integrantes)
async function cargar() {
  cargando.value = true
  errorCarga.value = ''
  try {
    agrupacion.value = await AgrupacionService.mia(id)
  } catch (error) {
    errorCarga.value = error.response?.data?.message || 'No se pudo cargar la agrupación.'
  } finally {
    cargando.value = false
  }
}

async function enviar() {
  enviando.value = true
  try {
    agrupacion.value = await AgrupacionService.enviarRevision(id)
    notifySuccess('Agrupación enviada a revisión.')
  } catch (error) {
    notifyError(error.response?.data?.message || 'No se pudo enviar.')
  } finally {
    enviando.value = false
  }
}

function abrirIntegrante(integrante) {
  integranteEditado.value = integrante
  mostrarIntegrante.value = true
}

function alGuardarIntegrante() {
  mostrarIntegrante.value = false
  cargar()
}

function quitar(integrante) {
  $q.dialog({
    title: 'Quitar integrante',
    message:
      `¿Quitar a ${integrante.nombre_completo} de la agrupación?` +
      (agrupacion.value.estado === 'aprobado'
        ? ' La agrupación volverá a revisión y dejará de verse en el portal hasta que la aprueben.'
        : ''),
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await AgrupacionService.quitarIntegrante(id, integrante.id)
      notifySuccess('Integrante quitado.')
      cargar()
    } catch (error) {
      notifyError(error.response?.data?.message || 'No se pudo quitar.')
    }
  })
}

// ceder la gestión: después de esto ya no puede administrarla
function transferir(integrante) {
  $q.dialog({
    title: 'Transferir la representación',
    message: `${integrante.nombre_completo} pasará a ser el representante y tú dejarás de gestionar esta agrupación. ¿Continuar?`,
    cancel: { label: 'Cancelar', flat: true, noCaps: true },
    ok: { label: 'Sí, transferir', color: 'primary', noCaps: true },
    persistent: true,
  }).onOk(async () => {
    try {
      await AgrupacionService.transferirRepresentante(id, integrante.id)
      notifySuccess(`${integrante.nombre_completo} ahora es el representante.`)
      router.push({ name: 'MisAgrupaciones' })
    } catch (error) {
      notifyError(error.response?.data?.message || 'No se pudo transferir.')
    }
  })
}

onMounted(cargar)
</script>

<style scoped>
.observacion {
  overflow-wrap: anywhere;
  word-break: break-word;
}

.obligatorio {
  margin-left: 6px;
  padding: 1px 6px;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 700;
  color: #b3243a;
  background: rgba(179, 36, 58, 0.1);
}
</style>
