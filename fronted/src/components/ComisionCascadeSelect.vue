<template>
  <div class="row q-col-gutter-sm">
    <div class="col-12 col-md-6">
      <q-select
        dense
        outlined
        v-model="selGrupo"
        :options="grupos"
        option-label="nombre"
        label="Grupo"
        clearable
        :loading="loadingGrupo"
        @update:model-value="onGrupoChange"
      />
    </div>
    <div class="col-12 col-md-6">
      <q-select
        dense
        outlined
        v-model="selFamilia"
        :options="familias"
        option-label="nombre"
        label="Comisión (Familia)"
        clearable
        :disable="!selGrupo"
        :loading="loadingFamilia"
        @update:model-value="onFamiliaChange"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import ComisionService from '@/services/ComisionService'

const props = defineProps({
  modelValue: { type: String, default: null },
})
const emit = defineEmits(['update:modelValue'])

const grupos = ref([])
const familias = ref([])

const selGrupo = ref(null)
const selFamilia = ref(null)

const loadingGrupo = ref(false)
const loadingFamilia = ref(false)

// evita que la carga programática (al editar) dispare los watchers de cascada
let hydrating = false

async function cargarGrupos() {
  loadingGrupo.value = true
  const { data } = await ComisionService.getData({ params: { tipo: 'grupo', rowsPerPage: 0, order_by: 'nombre' } })
  grupos.value = data
  loadingGrupo.value = false
}

async function cargarFamilias(codGrupo) {
  loadingFamilia.value = true
  const { data } = await ComisionService.getData({
    params: { tipo: 'familia', cod_grupo: codGrupo, rowsPerPage: 0, order_by: 'nombre' },
  })
  familias.value = data
  loadingFamilia.value = false
}

function onGrupoChange(grupo) {
  selFamilia.value = null
  familias.value = []
  if (!hydrating) emit('update:modelValue', null)
  if (grupo) cargarFamilias(grupo.cod_grupo)
}

function onFamiliaChange(familia) {
  emit('update:modelValue', familia?.codigo ?? null)
}

// Del código guardado solo conocemos la familia, así que hay que reconstruir
// hacia arriba a qué grupo pertenece.
async function initFromCodigo(codigo) {
  if (!codigo) return
  hydrating = true
  try {
    const familia = await ComisionService.get(codigo)
    const codGrupoCompleto = familia.cod_grupo + '00'
    const grupo = await ComisionService.get(codGrupoCompleto)

    if (!grupos.value.length) await cargarGrupos()
    selGrupo.value = grupo

    await cargarFamilias(familia.cod_grupo)
    selFamilia.value = familia
  } finally {
    hydrating = false
  }
}

watch(
  () => props.modelValue,
  (codigo) => {
    if (codigo === (selFamilia.value?.codigo ?? null)) return

    if (!codigo) {
      selGrupo.value = null
      selFamilia.value = null
      familias.value = []
      return
    }

    initFromCodigo(codigo)
  },
  { immediate: true },
)

onMounted(() => {
  if (!props.modelValue) cargarGrupos()
})
</script>
