<template>
  <q-card :style="{ width: '100%', maxWidth: '520px' }">
    <q-card-section class="row items-center">
      <div class="text-h6">{{ integrante ? 'Editar' : 'Agregar' }} integrante</div>
      <q-space />
      <q-btn v-close-popup flat round dense icon="close" />
    </q-card-section>
    <q-separator />

    <q-form @submit.prevent="guardar">
      <q-card-section class="column q-gutter-sm">
        <!-- el representante solo cambia su rol: sus datos son los de su ficha -->
        <template v-if="!soloRol">
          <q-input
            dense
            outlined
            v-model="datos.dni"
            label="DNI *"
            maxlength="8"
            :loading="consultando"
            hint="Con el DNI se completan nombre y apellidos desde RENIEC"
            :error="!!errores.dni"
            :error-message="errores.dni"
            @update:model-value="alEscribirDni"
          >
            <template #prepend><q-icon name="badge" /></template>
          </q-input>

          <q-banner v-if="registrado" dense rounded class="bg-blue-1 text-blue-10">
            Es un artista registrado: su perfil quedará vinculado a la agrupación.
          </q-banner>

          <q-input
            dense
            outlined
            v-model="datos.nombre"
            label="Nombres *"
            :error="!!errores.nombre"
            :error-message="errores.nombre"
          />
          <div class="row q-col-gutter-sm">
            <div class="col-6">
              <q-input
                dense
                outlined
                v-model="datos.apellido_paterno"
                label="Apellido paterno *"
                :error="!!errores.apellido_paterno"
                :error-message="errores.apellido_paterno"
              />
            </div>
            <div class="col-6">
              <q-input dense outlined v-model="datos.apellido_materno" label="Apellido materno" />
            </div>
          </div>
        </template>

        <q-input
          dense
          outlined
          v-model="datos.rol"
          label="Rol (ej. Músico, Danzante, Director)"
          maxlength="60"
          :error="!!errores.rol"
          :error-message="errores.rol"
        />
      </q-card-section>

      <q-card-actions align="right" class="q-pa-md">
        <q-btn v-close-popup flat no-caps label="Cancelar" />
        <q-btn unelevated no-caps color="primary" type="submit" label="Guardar" :loading="guardando" />
      </q-card-actions>
    </q-form>
  </q-card>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import AgrupacionService from '@/services/AgrupacionService'
import { useNotify } from '@/composables/useNotify'

const props = defineProps({
  agrupacionId: { type: Number, required: true },
  // null = agregar
  integrante: { type: Object, default: null },
})
const emit = defineEmits(['guardado'])

const { notifySuccess, notifyError, notifyAlert } = useNotify()
const soloRol = computed(() => !!props.integrante?.es_representante)
const datos = reactive({
  dni: props.integrante?.dni ?? '',
  nombre: props.integrante?.nombre ?? '',
  apellido_paterno: props.integrante?.apellido_paterno ?? '',
  apellido_materno: props.integrante?.apellido_materno ?? '',
  rol: props.integrante?.rol ?? '',
})
const errores = ref({})
const guardando = ref(false)
const consultando = ref(false)
const registrado = ref(!!props.integrante?.persona_id)

// al completar los 8 dígitos se buscan los datos (primero la base, después RENIEC)
async function alEscribirDni(valor) {
  registrado.value = false
  if (!/^\d{8}$/.test(valor || '')) return

  consultando.value = true
  try {
    const r = await AgrupacionService.consultarDni(valor)
    datos.nombre = r.nombre
    datos.apellido_paterno = r.apellido_paterno
    datos.apellido_materno = r.apellido_materno
    registrado.value = r.registrado
  } catch (error) {
    // RENIEC caída o DNI inexistente: se completa a mano, nunca bloquea
    notifyAlert(error.response?.data?.message || 'No se pudo consultar el DNI. Completa los datos a mano.')
  } finally {
    consultando.value = false
  }
}

async function guardar() {
  guardando.value = true
  errores.value = {}
  try {
    const resultado = props.integrante
      ? await AgrupacionService.actualizarIntegrante(props.agrupacionId, props.integrante.id, datos)
      : await AgrupacionService.agregarIntegrante(props.agrupacionId, datos)
    notifySuccess(props.integrante ? 'Integrante actualizado.' : 'Integrante agregado.')
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
