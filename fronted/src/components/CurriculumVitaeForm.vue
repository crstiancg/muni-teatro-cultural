<template>
  <q-card :style="{ width: '100%', maxWidth: $q.screen.gt.sm ? '35vw' : '100vw' }">
    <q-card-section class="row items-center">
      <div class="text-h6">{{ props.item ? 'Editar' : 'Agregar' }} Grado Académico</div>
      <q-space />
      <q-btn v-close-popup flat round dense size="sm" style="border-radius: 100% !important;">
        <X size="16" />
      </q-btn>
    </q-card-section>
    <q-separator />

    <q-form @submit.prevent="submit">
      <q-card-section class="q-gutter-sm">
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-sm-6">
            <q-select
              dense
              outlined
              emit-value
              map-options
              v-model="form.formacion.tipo"
              :options="opcionesTipo"
              label="Tipo *"
              @update:model-value="form.validate('formacion.tipo')"
              :error="form.invalid('formacion.tipo')"
              :error-message="form.errors['formacion.tipo']"
            />
          </div>
          <div class="col-12 col-sm-6">
            <q-select
              dense
              outlined
              emit-value
              map-options
              clearable
              v-model="form.formacion.nivel_alcanzado"
              :options="opcionesNivelAlcanzado"
              label="Nivel Alcanzado"
              @update:model-value="form.validate('formacion.nivel_alcanzado')"
              :error="form.invalid('formacion.nivel_alcanzado')"
              :error-message="form.errors['formacion.nivel_alcanzado']"
            />
          </div>
          <div class="col-12">
            <q-input
              dense
              outlined
              v-model="form.formacion.centro_estudios"
              label="Centro de Estudio *"
              @change="form.validate('formacion.centro_estudios')"
              :error="form.invalid('formacion.centro_estudios')"
              :error-message="form.errors['formacion.centro_estudios']"
            />
          </div>
          <div class="col-12">
            <q-input dense outlined v-model="form.formacion.profesion" label="Profesión" />
          </div>
          <div class="col-12 col-sm-6">
            <q-input dense outlined v-model="form.formacion.folio" label="N° Folio" />
          </div>
          <div class="col-12 col-sm-6">
            <q-input
              dense
              outlined
              v-model="form.formacion.fecha_expedicion"
              type="date"
              label="Fecha de Expedición"
            />
          </div>
          <div class="col-12">
            <q-file
              dense
              outlined
              v-model="form.formacion.archivo"
              label="Seleccionar Archivo (PDF o imagen, máx. 5MB)"
              accept=".pdf,.jpg,.jpeg,.png"
              :error="form.invalid('formacion.archivo')"
              :error-message="form.errors['formacion.archivo']"
              clearable
            >
              <template v-slot:prepend>
                <Paperclip size="18" />
              </template>
            </q-file>
          </div>

          <div class="col-12" v-if="previewUrl">
            <div class="text-caption text-grey-7 q-mb-xs">Vista previa</div>
            <div class="preview-box">
              <VuePdfEmbed v-if="esPdf" :source="previewUrl" :page="1" />
              <img v-else :src="previewUrl" class="preview-img" />
            </div>
          </div>
        </div>
        <div
          v-if="props.item && !form.formacion.archivo && props.item.archivo_nombre_original"
          class="text-caption text-grey-7"
        >
          Ya tiene un archivo: {{ props.item.archivo_nombre_original }}. Si no seleccionás uno nuevo, se conserva el
          actual.
        </div>
      </q-card-section>

      <q-separator />
      <q-card-actions align="right">
        <q-btn label="Cerrar" flat v-close-popup />
        <q-btn label="Guardar" color="positive" outline type="submit" :loading="form.processing" />
      </q-card-actions>
    </q-form>
  </q-card>
</template>

<script setup>
import { computed, ref, watch, onBeforeUnmount } from 'vue'
import { useForm } from 'laravel-precognition-vue'
import { X, Paperclip } from 'lucide-vue-next'
import VuePdfEmbed from 'vue-pdf-embed'

const props = defineProps({
  basePath: { type: String, required: true },
  item: { type: Object, default: null },
})
const emit = defineEmits(['save'])

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

// el backend expone alta y edición como POST (no PUT): PHP no llena $_FILES
// en un request PUT con multipart/form-data, así que ambas rutas son POST.
const url = props.item ? `api/${props.basePath}/${props.item.id}` : `api/${props.basePath}`

const form = useForm('post', url, {
  formacion: {
    tipo: props.item?.tipo ?? null,
    nivel_alcanzado: props.item?.nivel_alcanzado ?? null,
    centro_estudios: props.item?.centro_estudios ?? '',
    profesion: props.item?.profesion ?? '',
    folio: props.item?.folio ?? '',
    fecha_expedicion: props.item?.fecha_expedicion ?? '',
    archivo: null,
  },
})

const submit = () => {
  form
    .submit()
    .then((response) => {
      emit('save', response.data)
    })
    .catch(() => {})
}

// vista previa: si eligió un archivo nuevo, lo mostramos desde un object URL;
// si no eligió nada nuevo pero ya tenía uno guardado, mostramos ese
const objectUrl = ref(null)

watch(
  () => form.formacion.archivo,
  (archivo) => {
    if (objectUrl.value) {
      URL.revokeObjectURL(objectUrl.value)
      objectUrl.value = null
    }
    if (archivo instanceof File) {
      objectUrl.value = URL.createObjectURL(archivo)
    }
  },
)

onBeforeUnmount(() => {
  if (objectUrl.value) URL.revokeObjectURL(objectUrl.value)
})

const previewUrl = computed(() => objectUrl.value || (!form.formacion.archivo ? props.item?.archivo_url : null))

const esPdf = computed(() => {
  if (form.formacion.archivo instanceof File) return form.formacion.archivo.type === 'application/pdf'
  return !!previewUrl.value && previewUrl.value.toLowerCase().endsWith('.pdf')
})
</script>

<style scoped>
.preview-box {
  border-radius: 10px;
  overflow: hidden;
  max-height: 420px;
  overflow-y: auto;
  box-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
  display: flex;
  justify-content: center;
  background: #f5f5f5;
}

.preview-img {
  max-width: 100%;
  display: block;
}
</style>
