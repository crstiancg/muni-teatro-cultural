<template>
  <q-card :style="{ width: '100%', maxWidth: $q.screen.gt.sm ? '40vw' : '100vw' }">
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
          v-if="tipo === 'familia' && !id && grupoFijo"
          dense
          outlined
          disable
          class="q-mb-sm"
          :model-value="grupoFijo.nombre"
          label="Grupo"
        >
          <template v-slot:prepend>
            <q-icon name="folder" />
          </template>
        </q-input>

        <q-select
          v-else-if="tipo === 'familia' && !id"
          dense
          outlined
          class="q-mb-sm"
          v-model="grupoSeleccionado"
          :options="grupos"
          option-label="nombre"
          label="Grupo *"
          :loading="loadingGrupos"
        >
          <template v-slot:prepend>
            <q-icon name="folder" />
          </template>
        </q-select>

        <q-input
          dense
          outlined
          v-model="form.comision.nombre"
          :loading="form.validating"
          label="Nombre *"
          @change="form.validate('comision.nombre')"
          :error="form.invalid('comision.nombre')"
          :class="form.invalid('comision.nombre') ? 'q-mb-sm' : ''"
        >
          <template v-slot:prepend>
            <q-icon name="groups" />
          </template>
          <template v-slot:error>
            <div>{{ form.errors['comision.nombre'] }}</div>
          </template>
        </q-input>
      </q-card-section>
      <q-separator />

      <q-card-actions align="right">
        <q-btn label="Cancelar" flat v-close-popup></q-btn>
        <q-btn
          outline
          label="Guardar"
          :loading="form.processing"
          type="submit"
          color="positive"
        ></q-btn>
      </q-card-actions>
    </q-form>
  </q-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useForm } from 'laravel-precognition-vue'
import ComisionService from '@/services/ComisionService'

const emits = defineEmits(['save'])
const props = defineProps({
  title: String,
  id: { type: Number, default: null },
  // 'grupo' | 'familia' — solo importa al crear, no al editar (editar solo renombra)
  tipo: { type: String, default: 'grupo' },
  // si se crea la familia desde un grupo ya expandido, viene fijo (no hay que elegirlo)
  grupoFijo: { type: Object, default: null },
})

const grupos = ref([])
const grupoSeleccionado = ref(null)
const loadingGrupos = ref(false)

const form = props.id
  ? useForm('put', 'api/comisiones/' + props.id, { comision: { nombre: '' } })
  : useForm('post', props.tipo === 'grupo' ? 'api/grupos' : 'api/familias', {
      comision: { nombre: '', cod_grupo: '' },
    })

const submit = () => {
  if (props.tipo === 'familia' && !props.id) {
    form.comision.cod_grupo = props.grupoFijo?.cod_grupo ?? grupoSeleccionado.value?.cod_grupo
  }

  form
    .submit()
    .then(() => {
      form.reset()
      emits('save')
    })
    .catch(() => {})
}

async function cargarGrupos() {
  loadingGrupos.value = true
  const { data } = await ComisionService.getData({
    params: { tipo: 'grupo', rowsPerPage: 0, order_by: 'nombre' },
  })
  grupos.value = data
  loadingGrupos.value = false
}

onMounted(() => {
  if (props.tipo === 'familia' && !props.id && !props.grupoFijo) cargarGrupos()
})

defineExpose({ form })
</script>
