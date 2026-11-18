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
        unelevated
        color="primary"
        :disable="loading"
        label="Agregar"
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
            <q-td v-for="col in props.cols" :key="col.name" :props="props" :data-label="col.label">
              <template v-if="col.name === 'estado'">
                <q-chip v-if="props.row.de_baja" dense square color="grey-7">
                  De baja
                  <q-tooltip>{{ props.row.baja_motivo }}</q-tooltip>
                </q-chip>
                <q-chip v-else dense square color="positive">Activo</q-chip>
              </template>
              <template v-else>{{ col.value }}</template>
            </q-td>
            <q-td auto-width>
              <BotonAccion tipo="editar" @click="editar(props.row.id)" />
              <template v-if="puedeDarDeBaja && props.row.id !== userStore.getId">
                <BotonAccion v-if="props.row.de_baja" tipo="reactivar" @click="reactivar(props.row)" />
                <BotonAccion v-else tipo="baja" @click="darDeBaja(props.row)" />
              </template>
              <BotonAccion tipo="eliminar" @click="eliminar(props.row.id)" />
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
import BotonAccion from '@/components/panel/BotonAccion.vue'
import { useUserStore } from '@/stores/user-store'

const $q = useQuasar()
const userStore = useUserStore()
// uno no puede darse de baja a sí mismo (se quedaría afuera): el botón no se muestra
const puedeDarDeBaja = userStore.hasPermission('admin-usuarios-baja')
const columns = [
  { name: 'name', label: 'USUARIO', align: 'left', field: (row) => row.name, sortable: true },
  { name: 'email', label: 'EMAIL', align: 'left', field: (row) => row.email, sortable: true },
  { name: 'estado', label: 'ESTADO', align: 'left', field: (row) => (row.de_baja ? 'De baja' : 'Activo') },
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

// La baja cierra la sesión del usuario en TODOS sus dispositivos al instante y
// le bloquea el login. No borra nada: se puede reactivar. El motivo queda guardado.
function darDeBaja(usuario) {
  $q.dialog({
    title: `Dar de baja a ${usuario.name}`,
    message:
      'Se cerrará su sesión en todos los dispositivos y no podrá volver a ingresar hasta que lo reactives. No se borra nada.',
    prompt: {
      model: '',
      type: 'textarea',
      label: 'Motivo (obligatorio)',
      counter: true,
      maxlength: 500,
      isValid: (motivo) => motivo.trim().length >= 3,
    },
    ok: { label: 'Dar de baja', color: 'negative', unelevated: true, noCaps: true },
    cancel: { label: 'Cancelar', flat: true, noCaps: true },
    persistent: true,
  }).onOk(async (motivo) => {
    await UserService.darDeBaja(usuario.id, motivo.trim())
    tableRef.value.requestServerInteraction()
    $q.notify({ type: 'positive', message: `${usuario.name} fue dado de baja y su sesión se cerró en todos sus dispositivos.`, position: 'top-right' })
  })
}

function reactivar(usuario) {
  $q.dialog({
    title: `Reactivar a ${usuario.name}`,
    message: 'Podrá volver a ingresar con su correo y contraseña.',
    ok: { label: 'Reactivar', color: 'primary', unelevated: true, noCaps: true },
    cancel: { label: 'Cancelar', flat: true, noCaps: true },
    persistent: true,
  }).onOk(async () => {
    await UserService.reactivar(usuario.id)
    tableRef.value.requestServerInteraction()
    $q.notify({ type: 'positive', message: `${usuario.name} fue reactivado.`, position: 'top-right' })
  })
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
