<template>
  <q-dialog v-model="formComision" persistent>
    <ComisionesForm
      :key="editId + '-' + tipoForm + '-' + (grupoActivo?.cod_grupo ?? '')"
      :title="title"
      :id="editId"
      :tipo="tipoForm"
      :grupo-fijo="grupoActivo"
      ref="comisionesFormRef"
      @save="save"
    ></ComisionesForm>
  </q-dialog>
  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el icon="home" />
        <q-breadcrumbs-el label="Comisiones" icon="groups" />
      </q-breadcrumbs>
    </div>
    <q-separator />
    <div class="q-gutter-xs q-pa-sm">
      <q-btn outline color="primary" :disable="loading" label="Agregar Grupo" icon-right="add" @click="abrirCrearGrupo" />
    </div>
    <q-card class="q-ma-sm" flat :bordered="!$q.dark.isActive">
      <q-table
        flat
        :bordered="!$q.dark.isActive"
        :rows-per-page-options="[7, 10, 15]"
        class="my-sticky-header-table htable q-ma-sm"
        title="LISTA DE GRUPOS"
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
          <q-tr :props="props">
            <q-td auto-width>
              <q-btn
                size="sm"
                text-color="primary"
                unelevated
                round
                :icon="props.expand ? 'remove' : 'add'"
                @click="toggleExpand(props)"
              />
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
                @click="editarGrupo(props.row)"
                icon="edit"
                class="q-mr-xs"
              />
              <q-btn
                size="sm"
                text-color="red-13"
                color="red-1"
                outline
                round
                @click="eliminar(props.row)"
                icon="delete"
              />
            </q-td>
          </q-tr>

          <q-tr v-show="props.expand" :props="props">
            <q-td colspan="100%">
              <div class="q-pa-sm">
                <div class="row items-center q-mb-sm">
                  <div class="text-subtitle2  text-weight-bold">{{ props.row.nombre }}</div>
                  <q-space />
                  <q-btn
                    outline
                    size="sm"
                    color="primary"
                    icon-right="add"
                    label="Agregar familia"
                    @click="abrirCrearFamilia(props.row)"
                  />
                </div>

                <div v-if="cargandoFamilias[props.row.cod_grupo]" class="text-caption text-grey-6 q-pa-sm">
                  Cargando...
                </div>
                <div
                  v-else-if="!(familiasPorGrupo[props.row.cod_grupo] || []).length"
                  class="text-caption text-grey-5 q-pa-sm"
                >
                  Sin familias en este grupo.
                </div>
                <q-card :flat="$q.dark.isActive" v-else class="shadow-1 q-pa-sm">
                  <q-list separator>
                    <q-item v-for="familia in familiasPorGrupo[props.row.cod_grupo]" :key="familia.id">
                      <q-item-section avatar>
                        <q-avatar color="secondary" text-color="white" size="28px">{{ familia.cod_familia }}</q-avatar>
                      </q-item-section>
                      <q-item-section>{{ familia.nombre }}</q-item-section>
                      <q-item-section side>
                        <div class="row items-center q-gutter-xs">
                          <q-btn
                            size="sm"
                            text-color="cyan-8"
                            round
                            unelevated
                            icon="edit"
                            @click="editarFamilia(familia)"
                          />
                          <q-btn
                            size="sm"
                            text-color="red-13"
                            round
                            unelevated
                            icon="delete"
                            @click="eliminar(familia)"
                          />
                        </div>
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-card>
              </div>
            </q-td>
          </q-tr>
        </template>
      </q-table>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import ComisionService from '@/services/ComisionService'
import ComisionesForm from '@/pages/Comisiones/ComisionesForm.vue'

const $q = useQuasar()
const columns = [
  { name: 'codigo', label: 'Código', aling: 'center', field: (row) => row.codigo, sortable: true },
  { name: 'nombre', label: 'Grupo', aling: 'center', field: (row) => row.nombre, sortable: true },
]

const tableRef = ref()
const formComision = ref(false)
const comisionesFormRef = ref()
const title = ref('')
const editId = ref(null)
const tipoForm = ref('grupo')
const grupoActivo = ref(null)
const rows = ref([])
const filter = ref('')
const loading = ref(false)
const pagination = ref({
  sortBy: 'codigo',
  descending: false,
  page: 1,
  rowsPerPage: 9,
  rowsNumber: 10,
})

const familiasPorGrupo = reactive({})
const cargandoFamilias = reactive({})

function abrirCrearGrupo() {
  formComision.value = true
  tipoForm.value = 'grupo'
  editId.value = null
  grupoActivo.value = null
  title.value = 'Añadir Grupo'
}

function abrirCrearFamilia(grupo) {
  formComision.value = true
  tipoForm.value = 'familia'
  editId.value = null
  grupoActivo.value = grupo
  title.value = `Añadir Familia en "${grupo.nombre}"`
}

async function toggleExpand(props) {
  props.expand = !props.expand
  if (props.expand && !familiasPorGrupo[props.row.cod_grupo]) {
    await cargarFamilias(props.row.cod_grupo)
  }
}

async function cargarFamilias(codGrupo) {
  cargandoFamilias[codGrupo] = true
  const { data } = await ComisionService.getData({
    params: { tipo: 'familia', cod_grupo: codGrupo, rowsPerPage: 0, order_by: 'nombre' },
  })
  familiasPorGrupo[codGrupo] = data
  cargandoFamilias[codGrupo] = false
}

// solo grupos en la tabla principal; las familias se cargan al expandir
async function onRequest(props) {
  const { page, rowsPerPage, sortBy, descending } = props.pagination
  const filter = props.filter
  loading.value = true

  const fetchCount = rowsPerPage === 0 ? 0 : rowsPerPage
  const order_by = descending ? '-' + sortBy : sortBy
  const { data, total = 0 } = await ComisionService.getData({
    params: { tipo: 'grupo', rowsPerPage: fetchCount, page, search: filter, order_by },
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

const save = async () => {
  formComision.value = false
  // si la familia guardada/editada pertenece a un grupo cuyas familias ya
  // estaban cargadas (expandido), la volvemos a pedir para que se vea al toque
  if (grupoActivo.value) await cargarFamilias(grupoActivo.value.cod_grupo)
  tableRef.value.requestServerInteraction()
  $q.notify({
    type: 'positive',
    message: 'Guardado con Exito.',
    position: 'top-right',
    progress: true,
    timeout: 1000,
  })
}

async function editarGrupo(row) {
  title.value = 'Editar Grupo'
  tipoForm.value = 'grupo'
  editId.value = row.id
  grupoActivo.value = null
  formComision.value = true
  await nextTick()
  comisionesFormRef.value.form.setData({ comision: { nombre: row.nombre } })
}

async function editarFamilia(familia) {
  title.value = 'Editar Familia'
  tipoForm.value = 'familia'
  editId.value = familia.id
  // solo para saber qué grupo refrescar al guardar; no se muestra en el form
  // porque al editar (id presente) el campo "Grupo" no se renderiza
  grupoActivo.value = { cod_grupo: familia.cod_grupo }
  formComision.value = true
  await nextTick()
  comisionesFormRef.value.form.setData({ comision: { nombre: familia.nombre } })
}

async function eliminar(row) {
  $q.dialog({
    title: '¿Estas seguro de eliminar este registro?',
    message:
      row.tipo === 'grupo'
        ? 'Solo se puede eliminar un grupo si no tiene familias.'
        : 'Este proceso es irreversible.',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await ComisionService.delete(row.id)
      if (row.tipo === 'familia') await cargarFamilias(row.cod_grupo)
      tableRef.value.requestServerInteraction()
      $q.notify({
        type: 'positive',
        message: 'Eliminado con Exito.',
        position: 'top-right',
        progress: true,
        timeout: 1000,
      })
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message ?? 'No se pudo eliminar.',
        position: 'top-right',
        timeout: 2500,
      })
    }
  })
}
</script>
