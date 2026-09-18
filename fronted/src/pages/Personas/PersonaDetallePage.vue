<template>
  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el><Home size="16" /></q-breadcrumbs-el>
        <q-breadcrumbs-el label="Personas" :to="{ name: 'Personas' }"><Contact size="16" class="q-mr-xs" /></q-breadcrumbs-el>
        <q-breadcrumbs-el :label="persona?.nombre_completo || '...'" />
      </q-breadcrumbs>
    </div>
    <q-separator />

    <div v-if="cargando" class="text-center q-pa-lg">
      <q-spinner color="primary" size="40px" />
    </div>

    <div v-else class="row q-col-gutter-md q-ma-sm">
      <div class="col-12 col-md-3">
        <q-card flat :bordered="!$q.dark.isActive">
          <q-card-section class="text-center">
            <q-avatar size="88px" color="primary" text-color="white" class="text-h4">
              {{ inicial }}
            </q-avatar>
            <div class="text-subtitle1 text-weight-bold q-mt-md">{{ persona?.dni }}</div>
            <div class="text-body2">{{ persona?.nombre_completo }}</div>
            <div class="text-caption text-grey-7">{{ persona?.correo }}</div>
            <q-btn outline no-caps rounded color="primary" label="Editar" class="q-mt-md" @click="abrirEditar">
              <Pencil size="16" class="q-ml-xs" />
            </q-btn>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-9">
        <q-card flat :bordered="!$q.dark.isActive" class="q-mb-md">
          <q-card-section class="q-pa-md">
            <div class="text-subtitle1 text-weight-bold q-mb-md">
              Formación Académica, Grado Académico y/o Nivel de Estudio Alcanzado
            </div>
            <CurriculumVitaeList :base-path="`personas/${personaId}/curriculum-vitaes`" v-model="curriculum" />
          </q-card-section>
        </q-card>

        <q-card flat :bordered="!$q.dark.isActive" class="q-mb-md">
          <q-card-section class="q-pa-md">
            <div class="text-subtitle1 text-weight-bold q-mb-md">Capacitaciones y Reconocimientos</div>
            <CapacitacionList :base-path="`personas/${personaId}/capacitaciones`" v-model="capacitaciones" />
          </q-card-section>
        </q-card>

        <q-card flat :bordered="!$q.dark.isActive">
          <q-card-section class="q-pa-md">
            <div class="text-subtitle1 text-weight-bold q-mb-md">Actividades Realizadas</div>
            <ActividadGallery :base-path="`personas/${personaId}/actividades`" v-model="actividades" />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="mostrarEditar" persistent>
      <PersonasForm ref="personasFormRef" :key="personaId" title="Editar Persona" :id="personaId" @save="alGuardarPersona" />
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { Home, Contact, Pencil } from 'lucide-vue-next'
import CurriculumVitaeList from '@/components/CurriculumVitaeList.vue'
import CapacitacionList from '@/components/CapacitacionList.vue'
import ActividadGallery from '@/components/ActividadGallery.vue'
import PersonasForm from '@/pages/Personas/PersonasForm.vue'
import PersonaService from '@/services/PersonaService'

const $q = useQuasar()
const route = useRoute()
const personaId = Number(route.params.id)

const cargando = ref(true)
const persona = ref(null)
const curriculum = ref([])
const capacitaciones = ref([])
const actividades = ref([])
const mostrarEditar = ref(false)
const personasFormRef = ref()

const inicial = computed(() => (persona.value?.nombre?.charAt(0) || '?').toUpperCase())

async function cargar() {
  const datos = await PersonaService.get(personaId)
  persona.value = datos
  curriculum.value = datos.formaciones_academicas || []
  capacitaciones.value = datos.capacitaciones || []
  actividades.value = datos.actividades || []
  cargando.value = false
}

async function abrirEditar() {
  mostrarEditar.value = true
  await nextTick()
  personasFormRef.value.form.setData({
    persona: {
      id: persona.value.id,
      dni: persona.value.dni,
      nombre: persona.value.nombre,
      apellido_paterno: persona.value.apellido_paterno,
      apellido_materno: persona.value.apellido_materno,
      genero: persona.value.genero,
      fecha_nacimiento: persona.value.fecha_nacimiento,
      direccion: persona.value.direccion,
      estado_civil: persona.value.estado_civil,
      celular: persona.value.celular,
      celular_emergencia: persona.value.celular_emergencia,
      correo: persona.value.correo,
      // snapshot para que el form detecte si el correo cambió antes de guardar
      correo_original: persona.value.correo,
      correo_modificado: false,
      ubigeo_cod_nacimiento: persona.value.ubigeo_cod_nacimiento,
      ubigeo_cod_residencia: persona.value.ubigeo_cod_residencia,
      codigo_comision: persona.value.codigo_comision,
      codigo_comision_alternativo: persona.value.codigo_comision_alternativo,
    },
  })
}

function alGuardarPersona() {
  mostrarEditar.value = false
  cargar()
}

onMounted(cargar)
</script>
