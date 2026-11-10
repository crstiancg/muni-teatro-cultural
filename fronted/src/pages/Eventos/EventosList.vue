<template>
  <q-dialog v-model="mostrarForm" persistent>
    <EventoForm :key="formKey" :item="eventoEditando" @save="alGuardar" />
  </q-dialog>

  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el icon="home" />
        <q-breadcrumbs-el label="Agenda cultural" icon="event" />
      </q-breadcrumbs>
    </div>
    <q-separator />

    <div class="row items-center q-gutter-sm q-pa-sm">
      <q-btn
        outline
        color="primary"
        :disable="loading"
        :label="$q.screen.lt.sm ? '' : 'Nuevo evento'"
        icon-right="add"
        @click="abrirCrear"
      />
      <q-btn
        flat
        no-caps
        color="primary"
        icon="open_in_new"
        label="Ver agenda pública"
        :to="{ name: 'AgendaPublica' }"
        target="_blank"
      />
    </div>

    <q-card class="q-ma-sm" flat :bordered="!$q.dark.isActive">
      <q-table
        flat
        :bordered="!$q.dark.isActive"
        table-header-class="my-custom"
        :rows-per-page-options="[10, 20, 50]"
        class="my-sticky-header-table htable q-ma-sm"
        title="EVENTOS"
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
          <div class="row q-gutter-sm">
            <q-select
              dense
              outlined
              emit-value
              map-options
              clearable
              v-model="estado"
              :options="opcionesEstado"
              label="Estado"
              style="min-width: 150px"
              @update:model-value="recargar"
            />
            <q-input dense outlined debounce="500" v-model="filter" placeholder="Buscar">
              <template v-slot:append>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
        </template>

        <template v-slot:body-cell-estado="props">
          <q-td :props="props">
            <q-chip
              dense
              square
              :color="colorEstado[props.row.estado]"
              text-color="white"
              :label="props.row.estado"
            />
          </q-td>
        </template>

        <template v-slot:body-cell-acciones="props">
          <q-td :props="props" auto-width>
            <q-btn
              size="sm"
              text-color="cyan-8"
              color="cyan-1"
              outline
              round
              icon="edit"
              class="q-mr-xs"
              @click="editar(props.row.id)"
            >
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
            <q-btn
              v-if="props.row.estado !== 'cancelado'"
              size="sm"
              text-color="orange-9"
              color="orange-1"
              outline
              round
              icon="event_busy"
              class="q-mr-xs"
              @click="cancelar(props.row)"
            >
              <q-tooltip>Cancelar (deja de verse en el portal)</q-tooltip>
            </q-btn>
            <q-btn
              size="sm"
              text-color="red-13"
              color="red-1"
              outline
              round
              icon="delete"
              @click="eliminar(props.row)"
            >
              <q-tooltip>Eliminar definitivamente</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import EventoService from '@/services/EventoService'
import EventoForm from '@/components/EventoForm.vue'
import { useNotify } from '@/composables/useNotify'

const $q = useQuasar()
const { notifySuccess, notifyError } = useNotify()

// "2026-11-14 20:00" → "sáb 14 nov 2026, 20:00"
const formatoFecha = new Intl.DateTimeFormat('es-PE', {
  weekday: 'short',
  day: 'numeric',
  month: 'short',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
})
const mostrarFecha = (valor) => (valor ? formatoFecha.format(new Date(valor.replace(' ', 'T'))) : '')

const columns = [
  { name: 'fecha_inicio', label: 'Fecha', align: 'left', field: 'fecha_inicio', format: mostrarFecha, sortable: true },
  { name: 'titulo', label: 'Título', align: 'left', field: 'titulo', sortable: true },
  {
    name: 'lugar',
    label: 'Lugar',
    align: 'left',
    field: (row) => (row.modalidad === 'virtual' ? 'Virtual' : row.lugar),
  },
  { name: 'comision', label: 'Disciplina', align: 'left', field: (row) => row.comision?.nombre ?? '—' },
  { name: 'estado', label: 'Estado', align: 'center', field: 'estado', sortable: true },
  { name: 'acciones', label: 'Acciones', align: 'center', field: 'id' },
]

const opcionesEstado = [
  { label: 'Publicado', value: 'aprobado' },
  { label: 'Pendiente', value: 'pendiente' },
  { label: 'Observado', value: 'observado' },
  { label: 'Cancelado', value: 'cancelado' },
]
const colorEstado = { aprobado: 'positive', pendiente: 'orange-8', observado: 'deep-orange', cancelado: 'grey-7' }

const tableRef = ref()
const rows = ref([])
const filter = ref('')
const estado = ref(null)
const loading = ref(false)
const pagination = ref({ sortBy: 'fecha_inicio', descending: true, page: 1, rowsPerPage: 10, rowsNumber: 10 })

const mostrarForm = ref(false)
const eventoEditando = ref(null)
const formKey = ref(0)

async function onRequest(props) {
  const { page, rowsPerPage, sortBy, descending } = props.pagination
  loading.value = true
  try {
    const { data, total = 0 } = await EventoService.getData({
      params: {
        rowsPerPage,
        page,
        search: props.filter,
        order_by: descending ? '-' + sortBy : sortBy,
        estado: estado.value || undefined,
      },
    })
    rows.value.splice(0, rows.value.length, ...data)
    pagination.value = { ...props.pagination, rowsNumber: total || data.length }
  } finally {
    loading.value = false
  }
}

const recargar = () => tableRef.value.requestServerInteraction()

onMounted(recargar)

function abrirCrear() {
  eventoEditando.value = null
  formKey.value++
  mostrarForm.value = true
}

async function editar(id) {
  eventoEditando.value = await EventoService.get(id)
  formKey.value++
  await nextTick()
  mostrarForm.value = true
}

function alGuardar() {
  mostrarForm.value = false
  recargar()
  notifySuccess('Evento guardado.')
}

function cancelar(evento) {
  $q.dialog({
    title: '¿Cancelar este evento?',
    message: `"${evento.titulo}" dejará de verse en la agenda pública. Seguirá en esta lista como cancelado.`,
    cancel: { label: 'Volver', flat: true, noCaps: true },
    ok: { label: 'Sí, cancelar evento', color: 'orange-9', noCaps: true },
    persistent: true,
  }).onOk(async () => {
    try {
      await EventoService.cancelar(evento.id)
      recargar()
      notifySuccess('Evento cancelado.')
    } catch (error) {
      notifyError(error.response?.data?.message || 'No se pudo cancelar el evento.')
    }
  })
}

function eliminar(evento) {
  $q.dialog({
    title: '¿Eliminar este evento?',
    message: `Se borrará "${evento.titulo}" y su imagen. Este proceso es irreversible. Si solo no se va a realizar, mejor cancélalo.`,
    cancel: { label: 'Volver', flat: true, noCaps: true },
    ok: { label: 'Eliminar', color: 'negative', noCaps: true },
    persistent: true,
  }).onOk(async () => {
    try {
      await EventoService.delete(evento.id)
      recargar()
      notifySuccess('Evento eliminado.')
    } catch (error) {
      notifyError(error.response?.data?.message || 'No se pudo eliminar el evento.')
    }
  })
}
</script>
