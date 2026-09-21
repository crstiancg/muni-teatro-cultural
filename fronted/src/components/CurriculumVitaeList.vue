<template>
  <div>
    <div class="row items-center q-mb-md q-mr-sm">
      <q-btn
        outline
        no-caps
        :color="mostrarAnulados ? 'primary' : 'grey-7'"
        :label="mostrarAnulados ? 'Ocultar Anulados' : 'Mostrar Anulados'"
        @click="mostrarAnulados = !mostrarAnulados"
      />
      <q-space />
      <q-btn outline color="primary" no-caps label="Agregar" @click="abrirCrear">
        <Plus size="18" class="q-ml-xs" />
      </q-btn>
    </div>

    <q-card class="q-ma-sm" flat :bordered="!$q.dark.isActive">
      <q-table
        flat
        bordered
        :rows="itemsVisibles"
        :columns="columnas"
        row-key="id"
        class="q-ma-sm"
        :rows-per-page-options="[5, 10, 25]"
        no-data-label="Todavía no hay formación académica registrada."
      >
        <template v-slot:body-cell-id="props">
          <q-td :props="props" auto-width>
            <q-btn
              dense
              flat
              round
              size="sm"
              :color="props.row.flag_activo ? 'primary' : 'grey-5'"
              @click="props.row.flag_activo ? anular(props.row) : reactivar(props.row)"
            >
              <ToggleRight v-if="props.row.flag_activo" size="20" />
              <ToggleLeft v-else size="20" />
              <q-tooltip>{{
                props.row.flag_activo ? 'Click para anular' : 'Click para reactivar'
              }}</q-tooltip>
            </q-btn>
            {{ props.row.id }}
          </q-td>
        </template>

        <template v-slot:body-cell-tipo="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ etiqueta(opcionesTipo, props.row.tipo) }}</div>
            <div class="text-caption text-grey-7">
              {{ etiqueta(opcionesNivelAlcanzado, props.row.nivel_alcanzado) }}
            </div>
          </q-td>
        </template>

        <template v-slot:body-cell-profesion="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ props.row.profesion || '-' }}</div>
            <div class="text-caption text-grey-7">{{ props.row.centro_estudios }}</div>
          </q-td>
        </template>

        <template v-slot:body-cell-archivo="props">
          <q-td :props="props" auto-width>
            <q-btn
              v-if="props.row.archivo_url"
              dense
              flat
              round
              color="primary"
              type="a"
              :href="props.row.archivo_url"
              target="_blank"
            >
              <FileText size="18" />
              <q-tooltip>{{ props.row.archivo_nombre_original || 'Ver archivo' }}</q-tooltip>
            </q-btn>
            <span v-else class="text-grey-5">-</span>
          </q-td>
        </template>

        <template v-slot:body-cell-acciones="props">
          <q-td :props="props" auto-width>
            <q-btn dense flat round @click="abrirEditar(props.row)">
              <Pencil size="18" />
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-banner dense rounded class="bg-grey-2 text-grey-8 text-caption q-mt-sm q-ma-sm">
      <template v-slot:avatar>
        <TriangleAlert :size="20" color="#616161" />
      </template>
      (*) IMPORTANTE: En caso de postular a un puesto que requiera formación técnica completa o
      universitaria completa, deberá declarar la fecha exacta de egreso o bachiller de la formación
      académica correspondiente para contabilizar los años de experiencia general y especifica, de
      lo contrario se contabilizará desde la fecha de la Expedición del Título correspondiente. (**)
      De consignar en Nivel Alcanzado - EN CURSO - indicar el CICLO O SEMESTRE alcanzado en la celda
      de Fecha de Expedición del Grado / Título Dejar los espacios en blanco para a la formación
      académica que no aplique para el perfil.
    </q-banner>

    <q-dialog v-model="dialogo" persistent>
      <CurriculumVitaeForm
        :key="itemEditando?.id || 'nuevo'"
        :base-path="basePath"
        :item="itemEditando"
        @save="alGuardar"
      />
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { Plus, ToggleRight, ToggleLeft, FileText, Pencil, TriangleAlert } from 'lucide-vue-next'
import CurriculumVitaeForm from '@/components/CurriculumVitaeForm.vue'
import FormacionAcademicaService from '@/services/FormacionAcademicaService'

const props = defineProps({
  basePath: { type: String, required: true },
  modelValue: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue'])

const $q = useQuasar()
const items = ref([...props.modelValue])
const mostrarAnulados = ref(false)

watch(
  () => props.modelValue,
  (val) => (items.value = [...(val || [])]),
)

const itemsVisibles = computed(() =>
  mostrarAnulados.value ? items.value : items.value.filter((i) => i.flag_activo !== false),
)

const columnas = [
  { name: 'id', label: 'Id', field: 'id', align: 'left' },
  { name: 'tipo', label: 'Tipo / Nivel Alcanzado', field: 'tipo', align: 'left' },
  { name: 'profesion', label: 'Profesión / Centro de Estudio', field: 'profesion', align: 'left' },
  {
    name: 'fecha_expedicion',
    label: 'Fecha de Expedición',
    field: 'fecha_expedicion',
    align: 'left',
  },
  { name: 'folio', label: 'Folios', field: 'folio', align: 'left' },
  { name: 'archivo', label: 'Archivo', field: 'archivo_url', align: 'center' },
  { name: 'acciones', label: 'Acciones', field: 'id', align: 'center' },
]

const opcionesTipo = [
  { label: 'Primaria', value: 'primaria' },
  { label: 'Secundaria', value: 'secundaria' },
  { label: 'Técnica Básica (1 a 2 años)', value: 'tecnica_basica' },
  { label: 'Técnica Superior (3 a más años)', value: 'tecnica_superior' },
  { label: 'Universitaria', value: 'universitaria' },
  { label: 'Maestría', value: 'maestria' },
  { label: 'Doctorado', value: 'doctorado' },
]

const opcionesNivelAlcanzado = [
  { label: 'Egresado', value: 'egresado' },
  { label: 'Técnico', value: 'tecnico' },
  { label: 'Bachiller', value: 'bachiller' },
  { label: 'Titulado/Licenciado', value: 'titulado' },
  { label: 'Maestría', value: 'maestria' },
  { label: 'Doctorado', value: 'doctorado' },
]

function etiqueta(opciones, valor) {
  return opciones.find((o) => o.value === valor)?.label || valor || '-'
}

const dialogo = ref(false)
const itemEditando = ref(null)

function abrirCrear() {
  itemEditando.value = null
  dialogo.value = true
}

function abrirEditar(item) {
  itemEditando.value = item
  dialogo.value = true
}

function alGuardar(resultado) {
  const idx = items.value.findIndex((i) => i.id === resultado.id)
  if (idx === -1) {
    items.value.push(resultado)
  } else {
    items.value.splice(idx, 1, resultado)
  }
  emit('update:modelValue', items.value)

  dialogo.value = false
  $q.notify({
    type: 'positive',
    message: 'Guardado con éxito.',
    position: 'top-right',
    timeout: 1500,
  })
}

function reemplazar(actualizado) {
  const idx = items.value.findIndex((i) => i.id === actualizado.id)
  if (idx !== -1) items.value.splice(idx, 1, actualizado)
  emit('update:modelValue', items.value)
}

function anular(item) {
  $q.dialog({
    title: '¿Anular este registro?',
    message: 'Deja de contar para el perfil, pero podés reactivarlo cuando quieras.',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    const actualizado = await FormacionAcademicaService.delete(props.basePath, item.id)
    reemplazar(actualizado.data ?? { ...item, flag_activo: false })
    $q.notify({
      type: 'positive',
      message: 'Anulado con éxito.',
      position: 'top-right',
      timeout: 1000,
    })
  })
}

async function reactivar(item) {
  const actualizado = await FormacionAcademicaService.reactivar(props.basePath, item.id)
  reemplazar(actualizado)
  $q.notify({
    type: 'positive',
    message: 'Reactivado con éxito.',
    position: 'top-right',
    timeout: 1000,
  })
}
</script>

<style scoped>
.q-table {
  border-radius: 10px;
  overflow: hidden;
}

.q-banner {
  border-radius: 10px;
}
</style>
