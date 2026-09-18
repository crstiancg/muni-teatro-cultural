<template>
  <q-dialog v-model="formPersona" persistent>
    <PersonasForm :key="editId" :title="title" :id="editId" ref="personasFormRef" @save="save"></PersonasForm>
  </q-dialog>
  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el icon="home" />
        <q-breadcrumbs-el label="Personas" icon="badge" />
      </q-breadcrumbs>
    </div>
    <q-separator />
    <div class="q-gutter-xs q-pa-sm">
      <q-btn
        outline
        color="primary"
        :disable="loading"
        :label="$q.screen.lt.sm ? '' : 'Agregar'"
        icon-right="add"
        @click="abrirCrear"
      />
    </div>
    <q-card class="q-ma-sm" flat :bordered="!$q.dark.isActive">
      <q-table
        flat
        :bordered="!$q.dark.isActive"
        :rows-per-page-options="[7, 10, 15]"
        class="my-sticky-header-table htable q-ma-sm"
        title="LISTA DE PERSONAS"
        ref="tableRef"
        :rows="rows"
        :columns="columns"
        row-key="id"
        v-model:pagination="pagination"
        :loading="loading"
        :filter="filter"
        binary-state-sort
        @request="onRequest"
      >
        <template v-slot:top-right>
          <q-input
            active-class="text-white"
            standout="bg-primary"
            color="white"
            dense
            debounce="500"
            v-model="filter"
            placeholder="Buscar"
          >
            <template v-slot:append>
              <q-icon name="search" />
            </template>
          </q-input>
        </template>
        <template v-slot:header="props">
          <q-tr :props="props">
            <q-th auto-width />
            <q-th v-for="col in props.cols" :key="col.name" :props="props">
              {{ col.label }}
            </q-th>
            <q-th auto-width> Acciones </q-th>
          </q-tr>
        </template>

        <template v-slot:body="props">
          <q-tr :props="props" class="cursor-pointer" @click="verDetalle(props.row.id)">
            <q-td auto-width>
              <!-- TODO: cuando esté la tabla polimórfica de archivos, mostrar la foto real si existe -->
              <q-avatar color="primary" text-color="white" size="32px">
                {{ inicial(props.row.nombre_completo) }}
              </q-avatar>
            </q-td>
            <q-td v-for="col in props.cols" :key="col.name" :props="props">
              {{ col.value }}
            </q-td>
            <q-td auto-width>
              <q-btn
                size="sm"
                text-color="cyan-8"
                color="cyan-1"
                outline
                round
                @click.stop="editar(props.row.id)"
                icon="edit"
                class="q-mr-xs"
              />
              <q-btn
                size="sm"
                text-color="red-13"
                color="red-1"
                outline
                round
                @click.stop="eliminar(props.row.id)"
                icon="delete"
              />
            </q-td>
          </q-tr>
        </template>
      </q-table>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import PersonaService from '@/services/PersonaService'
import PersonasForm from '@/pages/Personas/PersonasForm.vue'

const $q = useQuasar()
const router = useRouter()
const columns = [
  { name: 'dni', label: 'DNI', aling: 'center', field: (row) => row.dni, sortable: true },
  { name: 'nombre_completo', label: 'Nombre completo', aling: 'center', field: (row) => row.nombre_completo, sortable: true },
  { name: 'correo', label: 'Correo', aling: 'center', field: (row) => row.correo, sortable: true },
  { name: 'celular', label: 'Celular', aling: 'center', field: (row) => row.celular, sortable: false },
]

const tableRef = ref()
const formPersona = ref(false)
const personasFormRef = ref()
const title = ref('')
const editId = ref(null)
const rows = ref([])
const filter = ref('')
const loading = ref(false)
const pagination = ref({
  sortBy: 'id',
  descending: true,
  page: 1,
  rowsPerPage: 9,
  rowsNumber: 10,
})

function inicial(nombreCompleto) {
  return (nombreCompleto?.charAt(0) || '?').toUpperCase()
}

function verDetalle(id) {
  router.push({ name: 'PersonaDetalle', params: { id } })
}

function abrirCrear() {
  formPersona.value = true
  title.value = 'Añadir Persona'
  editId.value = null
}

async function onRequest(props) {
  const { page, rowsPerPage, sortBy, descending } = props.pagination
  const filter = props.filter
  loading.value = true

  const fetchCount = rowsPerPage === 0 ? 0 : rowsPerPage
  const order_by = descending ? '-' + sortBy : sortBy
  const { data, total = 0 } = await PersonaService.getData({
    params: { rowsPerPage: fetchCount, page, search: filter, order_by },
  })

  rows.value.splice(0, rows.value.length, ...data)
  !total ? (pagination.value.rowsNumber = data.length) : (pagination.value.rowsNumber = total)
  pagination.value.page = page
  pagination.value.rowsPerPage = rowsPerPage
  pagination.value.sortBy = sortBy
  pagination.value.descending = descending
  loading.value = false
}

onMounted(() => {
  tableRef.value.requestServerInteraction()
})

const save = () => {
  formPersona.value = false
  tableRef.value.requestServerInteraction()
  $q.notify({
    type: 'positive',
    message: 'Guardado con Exito.',
    position: 'top-right',
    progress: true,
    timeout: 1000,
  })
}

async function editar(id) {
  title.value = 'Editar Persona'
  formPersona.value = true
  editId.value = id
  const persona = await PersonaService.get(id)
  await nextTick()
  personasFormRef.value.form.setData({
    persona: {
      id: persona.id,
      dni: persona.dni,
      nombre: persona.nombre,
      apellido_paterno: persona.apellido_paterno,
      apellido_materno: persona.apellido_materno,
      genero: persona.genero,
      fecha_nacimiento: persona.fecha_nacimiento,
      direccion: persona.direccion,
      estado_civil: persona.estado_civil,
      celular: persona.celular,
      celular_emergencia: persona.celular_emergencia,
      correo: persona.correo,
      // snapshot para que el form detecte si el correo cambió antes de guardar
      correo_original: persona.correo,
      correo_modificado: false,
      ubigeo_cod_nacimiento: persona.ubigeo_cod_nacimiento,
      ubigeo_cod_residencia: persona.ubigeo_cod_residencia,
      codigo_comision: persona.codigo_comision,
      codigo_comision_alternativo: persona.codigo_comision_alternativo,
    },
  })
}

async function eliminar(id) {
  $q.dialog({
    title: '¿Estas seguro de eliminar este registro?',
    message: 'Este proceso es irreversible.',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    await PersonaService.delete(id)
    tableRef.value.requestServerInteraction()
    $q.notify({
      type: 'positive',
      message: 'Eliminado con Exito.',
      position: 'top-right',
      progress: true,
      timeout: 1000,
    })
  })
}
</script>
