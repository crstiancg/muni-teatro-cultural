<template>
  <div>
    <div class="text-caption text-grey-7">
      ¿Tienes alguna discapacidad?{{ obligatorio ? ' *' : '' }}
    </div>
    <q-option-group v-model="tiene" :options="opcionesTiene" type="radio" inline dense />
    <div v-if="errorTiene" class="text-negative text-caption">{{ errorTiene }}</div>

    <q-select
      v-if="tiene"
      class="q-mt-sm"
      dense
      outlined
      emit-value
      map-options
      v-model="tipo"
      :options="opcionesTipo"
      :label="`Tipo de discapacidad${obligatorio ? ' *' : ''}`"
      :error="!!errorTipo"
      :error-message="errorTipo"
    />

    <div class="text-caption text-grey-6 q-mt-xs">
      <q-icon name="lock" size="12px" class="q-mr-xs" />
      Dato privado: solo lo ve la municipalidad, nunca se muestra en tu perfil público.
    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue'

// Dato sensible (Ley 29733). Los valores tienen que coincidir con
// Persona::TIPOS_DISCAPACIDAD del backend.
const tiene = defineModel('tiene', { type: Boolean, default: null })
const tipo = defineModel('tipo', { type: String, default: null })

defineProps({
  obligatorio: { type: Boolean, default: false },
  errorTiene: { type: String, default: null },
  errorTipo: { type: String, default: null },
})

const opcionesTiene = [
  { label: 'Sí', value: true },
  { label: 'No', value: false },
]

const opcionesTipo = [
  { label: 'Física', value: 'fisica' },
  { label: 'Visual', value: 'visual' },
  { label: 'Auditiva', value: 'auditiva' },
  { label: 'Intelectual', value: 'intelectual' },
  { label: 'Psicosocial', value: 'psicosocial' },
  { label: 'Múltiple', value: 'multiple' },
]

// si pasa a "No", el tipo no tiene sentido (el backend también lo limpia)
watch(tiene, (valor) => {
  if (!valor) tipo.value = null
})
</script>
