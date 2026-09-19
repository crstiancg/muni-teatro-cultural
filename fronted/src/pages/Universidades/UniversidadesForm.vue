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
          v-model="form.universidad.nombre"
          :loading="form.validating"
          label="Nombre"
          @change="form.validate('universidad.nombre')"
          :error="form.invalid('universidad.nombre')"
          :class="form.invalid('universidad.nombre') ? 'q-mb-sm' : ''"
        >
          <template v-slot:prepend>
            <q-icon name="school" />
          </template>
          <template v-slot:error>
            <div>{{ form.errors['universidad.nombre'] }}</div>
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
import formUniversidad from './FormUniversidad'

const emits = defineEmits(['save'])
const props = defineProps({
  title: String,
  id: { type: Number, default: null },
})

const form = ref(
  props.id
    ? useForm('put', 'api/universidades/' + props.id, formUniversidad)
    : useForm('post', 'api/universidades', formUniversidad),
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