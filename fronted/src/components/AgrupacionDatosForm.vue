<template>
  <q-form class="column q-gutter-md" @submit.prevent="guardar">
    <q-input
      dense
      outlined
      v-model="datos.nombre"
      label="Nombre de la agrupación *"
      maxlength="150"
      :error="!!errores.nombre"
      :error-message="errores.nombre"
    />

    <div>
      <div class="text-caption text-grey-7 q-mb-xs">Comisión a la que pertenece</div>
      <ComisionCascadeSelect v-model="datos.codigo_comision" />
      <div v-if="errores.codigo_comision" class="text-negative text-caption">{{ errores.codigo_comision }}</div>
    </div>

    <!-- solo al crear: el rol de quien la crea (queda como representante) -->
    <q-input
      v-if="!agrupacion"
      dense
      outlined
      v-model="datos.rol"
      label="Tu rol en la agrupación (ej. Director)"
      maxlength="60"
    />

    <div>
      <div class="text-caption text-grey-7 q-mb-xs">Descripción: historia, estilo, trayectoria</div>
      <!-- mismo editor que "Sobre su trabajo"; el HTML se sanitiza en el backend -->
      <q-editor
        v-model="datos.descripcion"
        min-height="8rem"
        placeholder="Cuenta sobre la agrupación..."
        :toolbar="[
          ['bold', 'italic', 'underline'],
          ['unordered', 'ordered', 'quote'],
          ['link'],
          ['removeFormat', 'undo', 'redo'],
        ]"
      />
      <div v-if="errores.descripcion" class="text-negative text-caption">{{ errores.descripcion }}</div>
    </div>

    <div>
      <div class="text-caption text-grey-7 q-mb-xs">Redes sociales</div>
      <div class="row q-col-gutter-sm">
        <div v-for="red in REDES" :key="red.clave" class="col-12 col-sm-6">
          <q-input
            dense
            outlined
            v-model="datos.redes_sociales[red.clave]"
            :label="red.label"
            :placeholder="red.placeholder"
            :error="!!errores[`redes_sociales.${red.clave}`]"
            :error-message="errores[`redes_sociales.${red.clave}`]"
          >
            <template #prepend><component :is="red.icono" :size="18" /></template>
          </q-input>
        </div>
      </div>
    </div>

    <div class="text-right">
      <q-btn
        unelevated
        no-caps
        color="primary"
        type="submit"
        :label="agrupacion ? 'Guardar cambios' : 'Crear agrupación'"
        :loading="guardando"
      />
    </div>
  </q-form>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Facebook, Instagram, Music2, Youtube, Globe } from 'lucide-vue-next'
import ComisionCascadeSelect from '@/components/ComisionCascadeSelect.vue'
import AgrupacionService from '@/services/AgrupacionService'
import { useNotify } from '@/composables/useNotify'

const props = defineProps({
  // null = crear; si viene, se edita
  agrupacion: { type: Object, default: null },
})
const emit = defineEmits(['guardado'])

// mismas claves que Persona::REDES en el backend
const REDES = [
  { clave: 'facebook', label: 'Facebook', icono: Facebook, placeholder: 'https://facebook.com/...' },
  { clave: 'instagram', label: 'Instagram', icono: Instagram, placeholder: 'https://instagram.com/...' },
  { clave: 'tiktok', label: 'TikTok', icono: Music2, placeholder: 'https://tiktok.com/@...' },
  { clave: 'youtube', label: 'YouTube', icono: Youtube, placeholder: 'https://youtube.com/@...' },
  { clave: 'web', label: 'Página web', icono: Globe, placeholder: 'https://...' },
]

const { notifySuccess, notifyError } = useNotify()
const datos = reactive({
  nombre: props.agrupacion?.nombre ?? '',
  codigo_comision: props.agrupacion?.codigo_comision ?? null,
  descripcion: props.agrupacion?.descripcion ?? '',
  redes_sociales: { ...(props.agrupacion?.redes_sociales || {}) },
  rol: '',
})
const errores = ref({})
const guardando = ref(false)

async function guardar() {
  guardando.value = true
  errores.value = {}
  try {
    const resultado = props.agrupacion
      ? await AgrupacionService.actualizar(props.agrupacion.id, datos)
      : await AgrupacionService.crear(datos)
    notifySuccess(props.agrupacion ? 'Cambios guardados.' : 'Agrupación creada.')
    emit('guardado', resultado)
  } catch (error) {
    const lista = error.response?.data?.errors || {}
    errores.value = Object.fromEntries(Object.entries(lista).map(([k, v]) => [k, v[0]]))
    notifyError(error.response?.data?.message || 'No se pudo guardar.')
  } finally {
    guardando.value = false
  }
}
</script>
