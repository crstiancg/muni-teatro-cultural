<template>
  <div>
    <div class="text-caption text-grey-7 q-mb-sm">
      Obligatorios para enviar tu perfil a revisión. Estos números no se muestran en tu perfil
      público: ahí solo aparece un check.
    </div>
    <div class="row q-col-gutter-sm">
      <div v-for="reg in REGISTROS" :key="reg.clave" class="col-12 col-sm-6">
        <q-input
          dense
          outlined
          v-model="registros[reg.clave]"
          :label="reg.label"
          :maxlength="reg.maxlength"
          :error="!!errores[`registros.${reg.clave}`]"
          :error-message="errores[`registros.${reg.clave}`]"
        />
      </div>
    </div>

    <div class="text-right q-mt-sm">
      <q-btn
        unelevated
        no-caps
        color="primary"
        label="Guardar registros"
        :loading="guardando"
        @click="guardar"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import PerfilPublicoService from '@/services/PerfilPublicoService'
import { useNotify } from '@/composables/useNotify'

// la persona completa: se actualiza con lo que devuelve el backend
const persona = defineModel({ type: Object, required: true })

// mismas claves que Persona::REGISTROS en el backend
const REGISTROS = [
  { clave: 'ruc', label: 'RUC', maxlength: 11 },
  { clave: 'rnp', label: 'RNP', maxlength: 50 },
  { clave: 'rna', label: 'RNA', maxlength: 50 },
  { clave: 'rentoca', label: 'RENTOCA', maxlength: 50 },
]

const { notifySuccess, notifyError } = useNotify()
const registros = reactive({ ...(persona.value.registros || {}) })
const errores = ref({})
const guardando = ref(false)

async function guardar() {
  guardando.value = true
  errores.value = {}
  try {
    const actualizada = await PerfilPublicoService.guardarRegistros({ registros })
    persona.value = { ...persona.value, ...actualizada }
    notifySuccess('Registros guardados.')
  } catch (error) {
    const lista = error.response?.data?.errors || {}
    errores.value = Object.fromEntries(Object.entries(lista).map(([k, v]) => [k, v[0]]))
    notifyError('Revisa los datos de los registros.')
  } finally {
    guardando.value = false
  }
}
</script>