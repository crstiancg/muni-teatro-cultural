<template>
  <q-card :style="{ width: '100%', maxWidth: $q.screen.gt.sm ? '40vw' : '100vw' }">
    <q-card-section class="bg-primary">
      <div class="row text-white">
        <div class="text-h6">{{ title }}</div>
        <q-space />
        <q-btn v-close-popup round size="sm" unelevated>
          <q-icon name="close" />
        </q-btn>
      </div>
    </q-card-section>

    <q-form @submit.prevent="submit">
      <q-card-section class="q-pb-md">
        <q-input
          dense
          outlined
          v-model="form.carrera.nombre"
          :loading="form.validating"
          label="Nombre"
          @change="form.validate('carrera.nombre')"
          :error="form.invalid('carrera.nombre')"
          :class="form.invalid('carrera.nombre') ? 'q-mb-sm' : ''"
        >
          <template v-slot:prepend>
            <q-icon name="menu_book" />
          </template>
          <template v-slot:error>
            <div>{{ form.errors['carrera.nombre'] }}</div>
          </template>
        </q-input>
      </q-card-section>
      <q-separator />

      <q-card-actions align="right">
        <q-btn label="Cancelar" flat v-close-popup></q-btn>
        <q-btn outline label="Guardar" :loading="form.processing" type="submit" color="positive"></q-btn>
      </q-card-actions>
    </q-form>
  </q-card>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from 'laravel-precognition-vue'
import formCarrera from './FormCarrera'

const emits = defineEmits(['save'])
const props = defineProps({
  title: String,
  id: { type: Number, default: null },
})

const form = ref(
  props.id
    ? useForm('put', 'api/carreras/' + props.id, formCarrera)
    : useForm('post', 'api/carreras', formCarrera),
)

const submit = () => {
  form.value
    .submit()
    .then(() => {
      form.value.reset()
      emits('save')
    })
    .catch(() => {})
}

defineExpose({ form })
</script>