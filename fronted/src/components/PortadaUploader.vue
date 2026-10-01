<template>
  <div>
    <!-- vista previa en la misma proporción en que se ve en el portal -->
    <div class="portada" :class="{ 'portada--vacia': !portada?.url }" @click="elegirArchivo">
      <img v-if="portada?.url" :src="portada.url" alt="Portada" />
      <div v-else class="portada-vacia">
        <ImagePlus :size="28" />
        <span>Subir portada</span>
        <small>Imagen horizontal, idealmente 1600 × 600 px</small>
      </div>
      <div class="portada-overlay" :class="{ activo: subiendo }">
        <q-spinner v-if="subiendo" color="white" size="28px" />
        <Camera v-else :size="24" />
      </div>
    </div>

    <div v-if="portada" class="row q-gutter-xs q-mt-xs justify-end">
      <q-btn flat dense no-caps size="sm" color="primary" label="Cambiar" :disable="subiendo" @click="elegirArchivo" />
      <q-btn flat dense no-caps size="sm" color="negative" label="Quitar" :disable="subiendo" @click="quitar" />
    </div>

    <input ref="inputRef" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="alElegir" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useQuasar } from 'quasar'
import { Camera, ImagePlus } from 'lucide-vue-next'
import { api } from '@/boot/axios'
import { useNotify } from '@/composables/useNotify'

const props = defineProps({
  // "mis-agrupaciones/{id}": sube a /api/{basePath}/portada
  basePath: { type: String, required: true },
})
const portada = defineModel({ type: Object, default: null })

const $q = useQuasar()
const { notifySuccess, notifyError } = useNotify()
const inputRef = ref()
const subiendo = ref(false)
// mismo límite que StorePersonaFotoRequest (5 MB)
const MAX_BYTES = 5 * 1024 * 1024

function elegirArchivo() {
  if (!subiendo.value) inputRef.value.click()
}

async function alElegir(evento) {
  const archivo = evento.target.files?.[0]
  evento.target.value = ''
  if (!archivo) return
  if (archivo.size > MAX_BYTES) {
    notifyError('La portada no puede pesar más de 5 MB.')
    return
  }

  subiendo.value = true
  try {
    const datos = new FormData()
    datos.append('foto', archivo)
    portada.value = (await api.post(`/api/${props.basePath}/portada`, datos)).data
    notifySuccess('Portada actualizada.')
  } catch (error) {
    notifyError(error.response?.data?.errors?.foto?.[0] || 'No se pudo subir la portada.')
  } finally {
    subiendo.value = false
  }
}

function quitar() {
  $q.dialog({ title: 'Quitar portada', message: '¿Seguro que quieres quitar la portada?', cancel: true }).onOk(
    async () => {
      subiendo.value = true
      try {
        await api.delete(`/api/${props.basePath}/portada`)
        portada.value = null
        notifySuccess('Portada eliminada.')
      } catch {
        notifyError('No se pudo quitar la portada.')
      } finally {
        subiendo.value = false
      }
    },
  )
}
</script>

<style scoped>
.portada {
  position: relative;
  aspect-ratio: 8 / 3;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  background: rgba(128, 128, 128, 0.1);
}

.portada img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.portada--vacia {
  border: 2px dashed rgba(128, 128, 128, 0.35);
}

.portada-vacia {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  opacity: 0.7;
  text-align: center;
  padding: 8px;
}

.portada-overlay {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  color: white;
  background: rgba(0, 0, 0, 0.4);
  opacity: 0;
  transition: opacity 0.2s ease;
}

.portada:hover .portada-overlay,
.portada-overlay.activo {
  opacity: 1;
}
</style>
