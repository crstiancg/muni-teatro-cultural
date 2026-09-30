<template>
  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el><Home size="16" /></q-breadcrumbs-el>
        <q-breadcrumbs-el label="Curriculum Vitae"
          ><GraduationCap size="16" class="q-mr-xs"
        /></q-breadcrumbs-el>
      </q-breadcrumbs>
    </div>
    <q-separator />

    <div v-if="cargando" class="text-center q-pa-lg">
      <q-spinner color="primary" size="40px" />
    </div>

    <div v-else-if="errorCarga" class="text-center q-pa-lg">
      <div class="text-body1 q-mb-sm">No se pudo cargar la información.</div>
      <q-btn outline no-caps color="primary" label="Reintentar" @click="cargar" />
    </div>

    <div v-else class="row q-col-gutter-md q-ma-sm">
      <div class="col-12 col-md-3">
        <q-card flat :bordered="!$q.dark.isActive">
          <q-card-section class="text-center">
            <div class="text-subtitle1 text-weight-bold q-mb-md">CURRICULUM VITAE</div>
            <!-- un admin sin ficha de persona no tiene dónde guardar la foto -->
            <FotoPerfilUploader
              v-if="persona"
              base-path="mi-informacion"
              size="150px"
              :inicial="inicial"
              v-model="persona.foto"
            />
            <q-avatar v-else size="150px" color="primary" text-color="white" class="text-h4">
              {{ inicial }}
            </q-avatar>
            <div class="text-subtitle1 text-weight-bold q-mt-md">{{ persona?.dni }}</div>
            <div class="text-h6">{{ persona?.nombre_completo }}</div>
            <q-btn
              outline
              no-caps
              rounded
              color="primary"
              label="Editar"
              class="q-mt-md"
              @click="mostrarEditar = true"
            >
              <Pencil size="16" class="q-ml-xs" />
            </q-btn>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-9">
        <q-card v-if="persona" flat :bordered="!$q.dark.isActive" class="q-mb-md">
          <q-card-section class="q-pa-md">
            <div class="text-subtitle1 text-weight-bold q-mb-sm">Perfil público — Sobre su trabajo</div>
            <EstadoPerfilPublico modo="artista" v-model="persona" class="q-mb-md" />
            <PerfilPublicoForm base-path="mi-informacion" v-model="persona" />
          </q-card-section>
        </q-card>

        <q-card flat :bordered="!$q.dark.isActive" class="q-mb-md">
          <q-card-section class="q-pa-md">
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              Formación Académica, Grado Académico y/o Nivel de Estudio Alcanzado
            </div>
            <CurriculumVitaeList
              base-path="mi-informacion/curriculum-vitaes"
              v-model="curriculum"
            />
          </q-card-section>
        </q-card>

        <q-card flat :bordered="!$q.dark.isActive" class="q-mb-md">
          <q-card-section class="q-pa-md">
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              Capacitaciones y Reconocimientos
            </div>
            <CapacitacionList base-path="mi-informacion/capacitaciones" v-model="capacitaciones" />
          </q-card-section>
        </q-card>

        <q-card flat :bordered="!$q.dark.isActive">
          <q-card-section class="q-pa-md">
            <div class="text-subtitle1 text-weight-bold q-mb-md">Actividades Realizadas</div>
            <ActividadGallery base-path="mi-informacion/actividades" v-model="actividades" />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="mostrarEditar">
      <MiInformacionDialog :data-inicial="datosPerfil" @save="alGuardarPerfil" />
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { Home, GraduationCap, Pencil } from 'lucide-vue-next'
import CurriculumVitaeList from '@/components/CurriculumVitaeList.vue'
import CapacitacionList from '@/components/CapacitacionList.vue'
import ActividadGallery from '@/components/ActividadGallery.vue'
import MiInformacionDialog from '@/components/MiInformacionDialog.vue'
import FotoPerfilUploader from '@/components/FotoPerfilUploader.vue'
import EstadoPerfilPublico from '@/components/EstadoPerfilPublico.vue'
import PerfilPublicoForm from '@/components/PerfilPublicoForm.vue'
import MiInformacionService from '@/services/MiInformacionService'

const $q = useQuasar()
const cargando = ref(true)
const errorCarga = ref(false)
const persona = ref(null)
const curriculum = ref([])
const capacitaciones = ref([])
const actividades = ref([])
const datosPerfil = ref(null)
const mostrarEditar = ref(false)

const inicial = computed(() => (persona.value?.nombre?.charAt(0) || '?').toUpperCase())

async function cargar() {
  cargando.value = true
  errorCarga.value = false
  try {
    const datos = await MiInformacionService.get()
    datosPerfil.value = datos
    persona.value = datos.persona
    curriculum.value = datos.persona?.formaciones_academicas || []
    capacitaciones.value = datos.persona?.capacitaciones || []
    actividades.value = datos.persona?.actividades || []
  } catch {
    // sin esto la página se queda con el spinner para siempre si falla el backend
    errorCarga.value = true
  } finally {
    cargando.value = false
  }
}

function alGuardarPerfil() {
  mostrarEditar.value = false
  cargar()
}

onMounted(cargar)
</script>
