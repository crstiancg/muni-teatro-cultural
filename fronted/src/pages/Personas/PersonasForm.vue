<template>
  <q-card :style="{ width: '100%', maxWidth: $q.screen.gt.sm ? '55vw' : '100vw' }">
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
      <q-card-section class="q-pa-md">
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-md-4">
            <q-input
              dense
              outlined
              v-model="form.persona.dni"
              :loading="form.validating"
              label="DNI *"
              maxlength="8"
              @change="form.validate('persona.dni')"
              :error="form.invalid('persona.dni')"
              :class="form.invalid('persona.dni') ? 'q-mb-sm' : ''"
            >
              <template v-slot:prepend>
                <q-icon name="badge" />
              </template>
              <template v-slot:error>
                <div>{{ form.errors['persona.dni'] }}</div>
              </template>
            </q-input>
          </div>

          <div class="col-12 col-md-4">
            <q-input
              dense
              outlined
              v-model="form.persona.nombre"
              label="Nombres *"
              @change="form.validate('persona.nombre')"
              :error="form.invalid('persona.nombre')"
              :class="form.invalid('persona.nombre') ? 'q-mb-sm' : ''"
            >
              <template v-slot:error>
                <div>{{ form.errors['persona.nombre'] }}</div>
              </template>
            </q-input>
          </div>

          <div class="col-12 col-md-4">
            <q-input
              dense
              outlined
              v-model="form.persona.apellido_paterno"
              label="Apellido paterno *"
              @change="form.validate('persona.apellido_paterno')"
              :error="form.invalid('persona.apellido_paterno')"
              :class="form.invalid('persona.apellido_paterno') ? 'q-mb-sm' : ''"
            >
              <template v-slot:error>
                <div>{{ form.errors['persona.apellido_paterno'] }}</div>
              </template>
            </q-input>
          </div>

          <div class="col-12 col-md-4">
            <q-input
              dense
              outlined
              v-model="form.persona.apellido_materno"
              label="Apellido materno *"
              @change="form.validate('persona.apellido_materno')"
              :error="form.invalid('persona.apellido_materno')"
              :class="form.invalid('persona.apellido_materno') ? 'q-mb-sm' : ''"
            >
              <template v-slot:error>
                <div>{{ form.errors['persona.apellido_materno'] }}</div>
              </template>
            </q-input>
          </div>

          <div class="col-12 col-md-4">
            <q-input
              dense
              outlined
              v-model="form.persona.correo"
              type="email"
              label="Correo * (será su usuario para ingresar)"
              @change="form.validate('persona.correo')"
              :error="form.invalid('persona.correo')"
              :class="form.invalid('persona.correo') ? 'q-mb-sm' : ''"
            >
              <template v-slot:prepend>
                <q-icon name="mail" />
              </template>
              <template v-slot:error>
                <div>{{ form.errors['persona.correo'] }}</div>
              </template>
            </q-input>
          </div>

          <div class="col-12 col-md-4">
            <q-select
              dense
              outlined
              emit-value
              map-options
              v-model="form.persona.genero"
              :options="opcionesGenero"
              label="Género"
              clearable
            />
          </div>

          <div class="col-12 col-md-4">
            <q-select
              dense
              outlined
              emit-value
              map-options
              v-model="form.persona.estado_civil"
              :options="opcionesEstadoCivil"
              label="Estado civil"
              clearable
            />
          </div>

          <div class="col-12 col-md-4">
            <q-input dense outlined v-model="form.persona.fecha_nacimiento" type="date" label="Fecha de nacimiento" />
          </div>

          <div class="col-12 col-md-4">
            <q-input dense outlined v-model="form.persona.celular" label="Celular" maxlength="9" />
          </div>

          <div class="col-12 col-md-4">
            <q-input
              dense
              outlined
              v-model="form.persona.celular_emergencia"
              label="Celular de emergencia"
              maxlength="9"
            />
          </div>

          <div class="col-12 col-md-4">
            <q-input dense outlined v-model="form.persona.direccion" label="Dirección" />
          </div>

          <div class="col-12">
            <div class="text-caption text-grey-7 q-mb-xs">Lugar de nacimiento</div>
            <UbigeoCascadeSelect v-model="form.persona.ubigeo_cod_nacimiento" />
          </div>

          <div class="col-12">
            <div class="text-caption text-grey-7 q-mb-xs">Lugar de residencia</div>
            <UbigeoCascadeSelect v-model="form.persona.ubigeo_cod_residencia" />
          </div>

          <div class="col-12">
            <div class="text-caption text-grey-7 q-mb-xs">Comisión</div>
            <ComisionCascadeSelect v-model="form.persona.codigo_comision" />
          </div>

          <div class="col-12">
            <div class="text-caption text-grey-7 q-mb-xs">Comisión alternativa</div>
            <ComisionCascadeSelect v-model="form.persona.codigo_comision_alternativo" />
          </div>
        </div>

        <div v-if="!props.id" class="text-caption text-grey-6 q-mt-sm">
          <q-icon name="info" size="14px" class="q-mr-xs" />
          Se crea automáticamente un usuario para que esta persona pueda ingresar al sistema. Contraseña por
          defecto: su DNI.
        </div>
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
import { useForm } from 'laravel-precognition-vue'
import UbigeoCascadeSelect from '@/components/UbigeoCascadeSelect.vue'
import ComisionCascadeSelect from '@/components/ComisionCascadeSelect.vue'
import formPersona from './FormPersona'

const emits = defineEmits(['save'])
const props = defineProps({
  title: String,
  id: { type: Number, default: null },
})

const opcionesGenero = [
  { label: 'Masculino', value: 'masculino' },
  { label: 'Femenino', value: 'femenino' },
  { label: 'Sin especificar', value: 'sin especificar' },
]

const opcionesEstadoCivil = [
  { label: 'Soltero', value: 'soltero' },
  { label: 'Casado', value: 'casado' },
  { label: 'Divorciado', value: 'divorciado' },
  { label: 'Viudo', value: 'viudo' },
]

const form = props.id
  ? useForm('put', 'api/personas/' + props.id, formPersona)
  : useForm('post', 'api/personas', formPersona)

const submit = () => {
  // el backend solo actualiza el email del usuario si detecta que cambió
  form.persona.correo_modificado = form.persona.correo !== form.persona.correo_original

  form
    .submit()
    .then(() => {
      form.reset()
      emits('save')
    })
    .catch(() => {})
}

defineExpose({ form })
</script>
