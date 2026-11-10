<template>
  <div>
    <div class="text-caption text-grey-7 q-mb-sm">
      Así te presentarás en el portal público. Cuenta a qué te dedicas, tu trayectoria y tus
      logros.
    </div>

    <q-editor
      v-model="biografia"
      min-height="10rem"
      placeholder="Escribe sobre tu trabajo..."
      :toolbar="[
        ['bold', 'italic', 'underline'],
        ['unordered', 'ordered', 'quote'],
        ['link'],
        ['removeFormat', 'undo', 'redo'],
      ]"
    />
    <div v-if="errores.biografia" class="text-negative text-caption q-mt-xs">
      {{ errores.biografia }}
    </div>

    <div class="text-subtitle2 text-weight-bold q-mt-md q-mb-sm">Redes sociales</div>
    <div class="row q-col-gutter-sm">
      <div v-for="red in REDES" :key="red.clave" class="col-12 col-sm-6">
        <q-input
          dense
          outlined
          v-model="redes[red.clave]"
          :label="red.label"
          :placeholder="red.placeholder"
          :error="!!errores[`redes_sociales.${red.clave}`]"
          :error-message="errores[`redes_sociales.${red.clave}`]"
        >
          <template #prepend><component :is="red.icono" :size="18" /></template>
        </q-input>
      </div>
    </div>

    <div class="text-right q-mt-sm">
      <q-btn
        unelevated
        no-caps
        color="primary"
        label="Guardar perfil público"
        :loading="guardando"
        @click="guardar"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Facebook, Instagram, Music2, Youtube, Globe } from 'lucide-vue-next'
import PerfilPublicoService from '@/services/PerfilPublicoService'
import { useNotify } from '@/composables/useNotify'

const props = defineProps({
  // "personas/{id}" o "mi-informacion"
  basePath: { type: String, required: true },
})

// la persona completa: se actualiza con lo que devuelve el backend (ya sanitizado)
const persona = defineModel({ type: Object, required: true })

// mismas claves que Persona::REDES en el backend
const REDES = [
  { clave: 'facebook', label: 'Facebook', icono: Facebook, placeholder: 'https://facebook.com/...' },
  { clave: 'instagram', label: 'Instagram', icono: Instagram, placeholder: 'https://instagram.com/...' },
  { clave: 'tiktok', label: 'TikTok', icono: Music2, placeholder: 'https://tiktok.com/@...' },
  { clave: 'youtube', label: 'YouTube', icono: Youtube, placeholder: 'https://youtube.com/@...' },
  { clave: 'web', label: 'Página web', icono: Globe, placeholder: 'https://...' },
]

const { notifySuccess, notifyError } = useNotify()
const biografia = ref(persona.value.biografia || '')
const redes = reactive({ ...(persona.value.redes_sociales || {}) })
const errores = ref({})
const guardando = ref(false)

async function guardar() {
  guardando.value = true
  errores.value = {}
  try {
    const actualizada = await PerfilPublicoService.guardar(props.basePath, {
      biografia: biografia.value,
      redes_sociales: redes,
    })
    persona.value = { ...persona.value, ...actualizada }
    biografia.value = actualizada.biografia || ''
    notifySuccess('Perfil público guardado.')
  } catch (error) {
    const lista = error.response?.data?.errors || {}
    errores.value = Object.fromEntries(Object.entries(lista).map(([k, v]) => [k, v[0]]))
    notifyError('Revisa los datos del perfil público.')
  } finally {
    guardando.value = false
  }
}
</script>
