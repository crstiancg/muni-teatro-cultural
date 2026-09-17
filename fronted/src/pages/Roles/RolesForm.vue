<template>
  <q-card :style="{ width: '100%', maxWidth: $q.screen.gt.sm ? '50vw' : '100vw' }">
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
          v-model="form.rol.name"
          :loading="form.validating"
          label="Nombre"
          @change="form.validate('rol.name')"
          :error="form.invalid('rol.name')"
          :class="form.invalid('rol.name') ? 'q-mb-sm' : ''"
        >
          <template v-slot:prepend>
            <q-icon name="vpn_key" />
          </template>
          <template v-slot:error>
            <div>{{ form.errors['rol.name'] }}</div>
          </template>
        </q-input>

        <div class="text-caption text-grey-7 q-mt-md q-mb-xs">Permisos</div>
        <q-scroll-area style="height: 360px; max-width: 100%">
          <q-list bordered separator class="rounded-borders">
            <q-item v-for="p in permisos" :key="p.id" tag="label" clickable v-ripple class="q-py-sm">
              <q-item-section avatar>
                <q-toggle
                  v-model="form.rol.permisosSelected"
                  :val="p.id"
                  color="primary"
                  checked-icon="check"
                  unchecked-icon="clear"
                  keep-color
                />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-medium">{{ p.description }}</q-item-label>
                <q-item-label caption class="text-grey-7">{{ p.name }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-scroll-area>
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
import { onMounted, ref } from 'vue'
import { useForm } from 'laravel-precognition-vue'
import formRol from './FormRol'
import PermisoService from '@/services/PermisoService'

const emits = defineEmits(['save'])
const permisos = ref([])

const props = defineProps({
  title: String,
  id: { type: Number, default: null },
})

const form = ref(
  props.id ? useForm('put', 'api/roles/' + props.id, formRol) : useForm('post', 'api/roles', formRol),
)

async function cargarPermisos() {
  const { data } = await PermisoService.getData({ params: { rowsPerPage: 0, order_by: 'id' } })
  permisos.value = data
}

const submit = () => {
  form.value
    .submit()
    .then(() => {
      form.value.reset()
      emits('save')
    })
    .catch(() => {})
}

onMounted(() => {
  cargarPermisos()
})

defineExpose({ form })
</script>
