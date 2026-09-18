<template>
  <q-card :style="{ width: '100%', maxWidth: $q.screen.gt.sm ? '30vw' : '100vw' }">
    <q-card-section class="row items-center">
      <div class="text-h6">{{ props.item ? 'Editar' : 'Agregar' }} Actividad</div>
      <q-space />
      <q-btn v-close-popup flat round dense size="sm" style="border-radius: 100% !important;">
        <X size="16" />
      </q-btn>
    </q-card-section>
    <q-separator />

    <q-form @submit.prevent="submit">
      <q-card-section class="q-gutter-sm">
        <q-input
          dense
          outlined
          type="textarea"
          v-model="form.actividad.descripcion"
          label="Descripción *"
          autogrow
          @change="form.validate('actividad.descripcion')"
          :error="form.invalid('actividad.descripcion')"
          :error-message="form.errors['actividad.descripcion']"
        />

        <q-file
          dense
          outlined
          v-model="form.actividad.imagen"
          label="Seleccionar Imagen (JPG o PNG, máx. 5MB)"
          accept=".jpg,.jpeg,.png"
          :error="form.invalid('actividad.imagen')"
          :error-message="form.errors['actividad.imagen']"
          clearable
        >
          <template v-slot:prepend>
            <ImageIcon size="18" />
          </template>
        </q-file>

        <div v-if="previewUrl" class="preview-box">
          <img :src="previewUrl" class="preview-img" />
        </div>
        <div
          v-else-if="props.item?.imagen_nombre_original"
          class="text-caption text-grey-7"
        >
          Ya tiene una imagen: {{ props.item.imagen_nombre_original }}. Si no seleccionás una nueva, se conserva la
          actual.
        </div>

        <q-checkbox
          v-model="form.actividad.flag_publico"
          :true-value="1"
          :false-value="0"
          label="Mostrar en la galería pública"
        />
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
import { X, Image as ImageIcon } from 'lucide-vue-next'

const props = defineProps({
  basePath: { type: String, required: true },
  item: { type: Object, default: null },
})
const emit = defineEmits(['save'])

// el backend expone alta y edición como POST (no PUT): PHP no llena $_FILES
// en un request PUT con multipart/form-data, así que ambas rutas son POST.
const url = props.item ? `api/${props.basePath}/${props.item.id}` : `api/${props.basePath}`

const form = useForm('post', url, {
  actividad: {
    descripcion: props.item?.descripcion ?? '',
    imagen: null,
    flag_publico: props.item?.flag_publico ? 1 : props.item ? 0 : 1,
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

// vista previa: si eligió una imagen nueva, la mostramos desde un object URL;
// si no eligió nada nuevo pero ya tenía una guardada, mostramos esa
const objectUrl = ref(null)

watch(
  () => form.actividad.imagen,
  (imagen) => {
    if (objectUrl.value) {
      URL.revokeObjectURL(objectUrl.value)
      objectUrl.value = null
    }
    if (imagen instanceof File) {
      objectUrl.value = URL.createObjectURL(imagen)
    }
  },
)

onBeforeUnmount(() => {
  if (objectUrl.value) URL.revokeObjectURL(objectUrl.value)
})

const previewUrl = computed(() => objectUrl.value || (!form.actividad.imagen ? props.item?.imagen_url : null))
</script>

<style scoped>
.preview-box {
  border-radius: 10px;
  overflow: hidden;
  max-height: 300px;
  display: flex;
  justify-content: center;
  background: #f5f5f5;
}

.preview-img {
  max-width: 100%;
  max-height: 300px;
  object-fit: contain;
  display: block;
}
</style>
