<template>
  <q-dialog v-model="formUser" persistent>
    <UsuariosForm
      :key="editId"
      :title="title"
      :id="editId"
      ref="usuariosFormRef"
      @save="save"
    ></UsuariosForm>
  </q-dialog>
  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el icon="home" />
        <q-breadcrumbs-el label="Usuarios" icon="person" />
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
        title="LISTA DE USUARIOS"
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
            <q-th v-for="col in props.cols" :key="col.name" :props="props">
              {{ col.label }}
            </q-th>
            <q-th auto-width> Acciones </q-th>
          </q-tr>
        </template>

        <template v-slot:body="props">
          <q-tr :props="props">
            <q-td v-for="col in props.cols" :key="col.name" :props="props">
              {{ col.value }}
            </q-td>
            <q-td auto-width>
              <q-btn
                size="sm"
                outline
                color="green"
                round
                @click="editar(props.row.id)"
                icon="edit"
                class="q-mr-xs"
              />
              <q-btn
                size="sm"
                outline
                color="red"
                round
                @click="eliminar(props.row.id)"
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
import UserService from '@/services/UserService'
import UsuariosForm from '@/pages/Usuarios/UsuariosForm.vue'

const $q = useQuasar()
const columns = [
  { name: 'id', label: 'ID', aling: 'center', field: (row) => row.id, sortable: true },
  { name: 'name', label: 'USUARIO', aling: 'center', field: (row) => row.name, sortable: true },
  { name: 'email', label: 'EMAIL', aling: 'center', field: (row) => row.email, sortable: true },
]

const tableRef = ref()
const formUser = ref(false)
const usuariosFormRef = ref()
const title = ref('')
const editId = ref(null)
const rows = ref([])
const filter = ref('')
const loading = ref(false)
const pagination = ref({
  sortBy: 'id',
  descending: true,
  page: 1,
  rowsPerPage: 7,
  rowsNumber: 10,
})

function abrirCrear() {
  formUser.value = true
  title.value = 'Añadir Usuario'
  editId.value = null
}

async function onRequest(props) {
  const { page, rowsPerPage, sortBy, descending } = props.pagination
  const filter = props.filter
  loading.value = true

  const fetchCount = rowsPerPage === 0 ? 0 : rowsPerPage
  const order_by = descending ? '-' + sortBy : sortBy
  const { data, total = 0 } = await UserService.getData({
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
  formUser.value = false
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
  title.value = 'Editar Usuario'
  formUser.value = true
  editId.value = id
  const row = await UserService.get(id)
  await nextTick()
  usuariosFormRef.value.form.setData({
    name: row.user.name,
    email: row.user.email,
    rolesSelected: row.rolesSelected,
    permisosSelected: row.permisosSelected,
  })
  usuariosFormRef.value.setPermisoObjects(row.permissionsData ?? [])
}

async function eliminar(id) {
  $q.dialog({
    title: '¿Estas seguro de eliminar este registro?',
    message: 'Este proceso es irreversible.',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    await UserService.delete(id)
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
