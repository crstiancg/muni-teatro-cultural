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

    <div v-if="!itemsVisibles.length" class="text-caption text-grey-6 q-ma-sm">
      Todavía no hay actividades registradas.
    </div>

    <div v-else class="row q-col-gutter-md q-ma-sm">
      <div v-for="item in itemsVisibles" :key="item.id" class="col-6 col-sm-4 col-md-3">
        <q-card flat bordered class="gallery-card" :class="{ 'gallery-card--anulado': !item.flag_activo }">
          <q-img :src="item.imagen_url" ratio="1" fit="cover" class="gallery-img">
            <template v-slot:error>
              <div class="absolute-full flex flex-center bg-grey-3">
                <ImageOff size="32" color="#9e9e9e" />
              </div>
            </template>
          </q-img>

          <q-card-section class="q-pa-sm">
            <div class="text-caption gallery-desc">{{ item.descripcion }}</div>
          </q-card-section>

          <q-card-actions class="q-pa-xs" align="right">
            <q-btn dense flat round size="sm" v-if="!item.flag_publico">
              <EyeOff size="16" />
              <q-tooltip>No se muestra en la galería pública</q-tooltip>
            </q-btn>
            <q-space />
            <q-btn dense flat round size="sm" @click="abrirEditar(item)">
              <Pencil size="16" />
            </q-btn>
            <q-btn
              dense
              flat
              round
              size="sm"
              :color="item.flag_activo ? 'grey-7' : 'positive'"
              @click="item.flag_activo ? anular(item) : reactivar(item)"
            >
              <ToggleRight v-if="item.flag_activo" size="18" />
              <ToggleLeft v-else size="18" />
              <q-tooltip>{{ item.flag_activo ? 'Click para anular' : 'Click para reactivar' }}</q-tooltip>
            </q-btn>
            <q-btn dense flat round size="sm" color="negative" @click="eliminarPermanente(item)">
              <Trash2 size="16" />
              <q-tooltip>Eliminar definitivamente</q-tooltip>
            </q-btn>
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="dialogo" persistent>
      <ActividadForm :key="itemEditando?.id || 'nuevo'" :base-path="basePath" :item="itemEditando" @save="alGuardar" />
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { Plus, Pencil, ToggleRight, ToggleLeft, EyeOff, ImageOff, Trash2 } from 'lucide-vue-next'
import ActividadForm from '@/components/ActividadForm.vue'
import ActividadService from '@/services/ActividadService'

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
  $q.notify({ type: 'positive', message: 'Guardado con éxito.', position: 'top-right', timeout: 1500 })
}

function reemplazar(actualizado) {
  const idx = items.value.findIndex((i) => i.id === actualizado.id)
  if (idx !== -1) items.value.splice(idx, 1, actualizado)
  emit('update:modelValue', items.value)
}

function anular(item) {
  $q.dialog({
    title: '¿Anular esta actividad?',
    message: 'Deja de contar para el perfil, pero podés reactivarla cuando quieras.',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    const actualizado = await ActividadService.delete(props.basePath, item.id)
    reemplazar(actualizado.data ?? { ...item, flag_activo: false })
    $q.notify({ type: 'positive', message: 'Anulada con éxito.', position: 'top-right', timeout: 1000 })
  })
}

async function reactivar(item) {
  const actualizado = await ActividadService.reactivar(props.basePath, item.id)
  reemplazar(actualizado)
  $q.notify({ type: 'positive', message: 'Reactivada con éxito.', position: 'top-right', timeout: 1000 })
}

function eliminarPermanente(item) {
  $q.dialog({
    title: '¿Eliminar definitivamente?',
    message: 'Esta acción NO se puede deshacer. Se borra el registro y la imagen para siempre.',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    await ActividadService.eliminarPermanente(props.basePath, item.id)
    items.value = items.value.filter((i) => i.id !== item.id)
    emit('update:modelValue', items.value)
    $q.notify({ type: 'positive', message: 'Eliminado definitivamente.', position: 'top-right', timeout: 1000 })
  })
}
</script>

<style scoped>
.gallery-card {
  border-radius: 10px;
  overflow: hidden;
}

.gallery-card--anulado {
  opacity: 0.5;
}

.gallery-desc {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 2.5em;
}
</style>
