<template>
  <div class="column items-center">
    <q-avatar
      :size="size"
      color="primary"
      text-color="white"
      class="foto-avatar cursor-pointer"
      @click="elegirArchivo"
    >
      <img v-if="foto?.url" :src="foto.url" alt="Foto de perfil" />
      <span v-else :style="{ fontSize: `calc(${size} / 2.5)` }">{{ inicial }}</span>

      <div class="foto-overlay flex flex-center" :class="{ 'foto-overlay--activo': subiendo }">
        <q-spinner v-if="subiendo" color="white" size="24px" />
        <Camera v-else size="22" />
      </div>
    </q-avatar>

    <div class="row q-gutter-xs q-mt-xs">
      <q-btn
        flat
        dense
        no-caps
        size="sm"
        color="primary"
        :label="foto ? 'Cambiar foto' : 'Subir foto'"
        :disable="subiendo"
        @click="elegirArchivo"
      />
      <q-btn
        v-if="foto"
        flat
        dense
        no-caps
        size="sm"
        color="negative"
        label="Quitar"
        :disable="subiendo"
        @click="confirmarEliminar"
      />
    </div>

    <!-- input nativo oculto: el avatar y el botón hacen de disparador -->
    <input
      ref="inputRef"
      type="file"
      accept="image/jpeg,image/png,image/webp"
      class="hidden"
      @change="alElegir"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useQuasar } from 'quasar'
import { Camera } from 'lucide-vue-next'
import FotoService from '@/services/FotoService'
import { useNotify } from '@/composables/useNotify'

const props = defineProps({
  // "personas/{id}" o "mi-informacion"
  basePath: { type: String, required: true },
  inicial: { type: String, default: '?' },
  size: { type: String, default: '88px' },
})

const foto = defineModel({ type: Object, default: null })

const $q = useQuasar()
const { notifySuccess, notifyError } = useNotify()
const inputRef = ref()
const subiendo = ref(false)

// mismo límite que StorePersonaFotoRequest (5 MB): se corta antes de subir
const MAX_BYTES = 5 * 1024 * 1024

function elegirArchivo() {
  if (!subiendo.value) inputRef.value.click()
}

async function alElegir(evento) {
  const archivo = evento.target.files?.[0]
  // se limpia para que elegir el mismo archivo otra vez vuelva a disparar change
  evento.target.value = ''
  if (!archivo) return

  if (archivo.size > MAX_BYTES) {
    notifyError('La foto no puede pesar más de 5 MB.')
    return
  }

  subiendo.value = true
  try {
    foto.value = await FotoService.subir(props.basePath, archivo)
    notifySuccess('Foto actualizada.')
  } catch (error) {
    notifyError(error.response?.data?.errors?.foto?.[0] || 'No se pudo subir la foto.')
  } finally {
    subiendo.value = false
  }
}

function confirmarEliminar() {
  $q.dialog({
    title: 'Quitar foto',
    message: '¿Seguro que quieres quitar la foto de perfil?',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    subiendo.value = true
    try {
      await FotoService.eliminar(props.basePath)
      foto.value = null
      notifySuccess('Foto eliminada.')
    } catch {
      notifyError('No se pudo quitar la foto.')
    } finally {
      subiendo.value = false
    }
  })
}
</script>

<style scoped>
.foto-avatar {
  position: relative;
  overflow: hidden;
}

.foto-avatar img {
  object-fit: cover;
}

.foto-overlay {
  position: absolute;
  inset: 0;
  color: white;
  background: rgba(0, 0, 0, 0.45);
  opacity: 0;
  transition: opacity 0.2s ease;
}

.foto-avatar:hover .foto-overlay,
.foto-overlay--activo {
  opacity: 1;
}
</style>
