<template>
  <div>
    <div class="row items-center q-gutter-sm">
      <q-chip dense square :color="actual.color" text-color="white" class="text-weight-bold">
        {{ actual.label }}
      </q-chip>
      <span class="text-caption text-grey-7">{{ actual.ayuda[modo] }}</span>
    </div>

    <q-banner
      v-if="persona.estado === 'observado' && persona.observacion"
      dense
      rounded
      class="bg-orange-1 text-orange-10 q-mt-sm"
    >
      <strong>Observación del administrador:</strong> {{ persona.observacion }}
    </q-banner>

    <div class="q-mt-sm">
      <!-- artista: solo puede enviar desde borrador u observado -->
      <q-btn
        v-if="modo === 'artista' && ['borrador', 'observado'].includes(persona.estado)"
        unelevated
        no-caps
        color="primary"
        label="Enviar a revisión"
        :loading="procesando"
        @click="enviar"
      >
        <Send :size="16" class="q-ml-xs" />
      </q-btn>

      <!-- admin: puede aprobar u observar en cualquier estado que no sea el mismo -->
      <template v-if="modo === 'admin'">
        <q-btn
          v-if="persona.estado !== 'aprobado'"
          unelevated
          no-caps
          color="positive"
          label="Aprobar"
          class="q-mr-sm"
          :loading="procesando"
          @click="aprobar"
        />
        <q-btn
          outline
          no-caps
          color="orange-9"
          :label="persona.estado === 'aprobado' ? 'Despublicar con observación' : 'Observar'"
          :disable="procesando"
          @click="observar"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { Send } from 'lucide-vue-next'
import PerfilPublicoService from '@/services/PerfilPublicoService'
import { useNotify } from '@/composables/useNotify'

defineProps({
  // "artista" (Mi CV) o "admin" (detalle de persona)
  modo: { type: String, default: 'artista' },
})
const persona = defineModel({ type: Object, required: true })

const ESTADOS = {
  borrador: {
    label: 'Borrador',
    color: 'grey-7',
    ayuda: {
      artista: 'Completa tu perfil y envíalo a revisión para aparecer en el portal.',
      admin: 'Todavía no envió su perfil a revisión.',
    },
  },
  pendiente: {
    label: 'En revisión',
    color: 'blue-7',
    ayuda: {
      artista: 'Un administrador está revisando tu perfil.',
      admin: 'Envió su perfil: revísalo y apruébalo u obsérvalo.',
    },
  },
  aprobado: {
    label: 'Publicado',
    color: 'positive',
    ayuda: {
      artista: 'Tu perfil aparece en el portal. Tus cambios se publican al guardar.',
      admin: 'Aparece en el portal público.',
    },
  },
  observado: {
    label: 'Observado',
    color: 'orange-9',
    ayuda: {
      artista: 'Corrige lo indicado y vuelve a enviarlo.',
      admin: 'Esperando que corrija lo observado.',
    },
  },
}

const $q = useQuasar()
const { notifySuccess, notifyError } = useNotify()
const procesando = ref(false)
const actual = computed(() => ESTADOS[persona.value.estado] || ESTADOS.borrador)

async function ejecutar(accion, mensaje) {
  procesando.value = true
  try {
    persona.value = { ...persona.value, ...(await accion()) }
    notifySuccess(mensaje)
  } catch (error) {
    notifyError(error.response?.data?.message || 'No se pudo completar la acción.')
  } finally {
    procesando.value = false
  }
}

const enviar = () =>
  ejecutar(() => PerfilPublicoService.enviarRevision(), 'Perfil enviado a revisión.')

const aprobar = () =>
  ejecutar(() => PerfilPublicoService.aprobar(persona.value.id), 'Perfil aprobado y publicado.')

function observar() {
  $q.dialog({
    title: 'Observar perfil',
    message: 'Indica qué debe corregir. La persona recibirá este mensaje.',
    prompt: { model: '', type: 'textarea', isValid: (v) => v.trim().length > 0 },
    cancel: true,
    persistent: true,
  }).onOk((observacion) =>
    ejecutar(
      () => PerfilPublicoService.observar(persona.value.id, observacion.trim()),
      'Observación enviada.',
    ),
  )
}
</script>
