<template>
  <div class="row q-col-gutter-sm">
    <div class="col-12 col-md-4">
      <q-select
        dense
        outlined
        v-model="selDep"
        :options="departamentos"
        option-label="nombre"
        label="Departamento"
        clearable
        :loading="loadingDep"
        @update:model-value="onDepChange"
      />
    </div>
    <div class="col-12 col-md-4">
      <q-select
        dense
        outlined
        v-model="selProv"
        :options="provincias"
        option-label="nombre"
        label="Provincia"
        clearable
        :disable="!selDep"
        :loading="loadingProv"
        @update:model-value="onProvChange"
      />
    </div>
    <div class="col-12 col-md-4">
      <q-select
        dense
        outlined
        v-model="selDist"
        :options="distritos"
        option-label="nombre"
        label="Distrito"
        clearable
        :disable="!selProv"
        :loading="loadingDist"
        @update:model-value="onDistChange"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import UbigeoService from '@/services/UbigeoService'

const props = defineProps({
  modelValue: { type: String, default: null },
})
const emit = defineEmits(['update:modelValue'])

const departamentos = ref([])
const provincias = ref([])
const distritos = ref([])

const selDep = ref(null)
const selProv = ref(null)
const selDist = ref(null)

const loadingDep = ref(false)
const loadingProv = ref(false)
const loadingDist = ref(false)

// evita que la carga programática (al editar) dispare los watchers de cascada
let hydrating = false

async function cargarDepartamentos() {
  loadingDep.value = true
  const { data } = await UbigeoService.getData({
    params: { tipo: 'departamento', rowsPerPage: 0, order_by: 'nombre' },
  })
  departamentos.value = data
  loadingDep.value = false
}

async function cargarProvincias(codDep) {
  loadingProv.value = true
  const { data } = await UbigeoService.getData({
    params: { tipo: 'provincia', cod_dep: codDep, rowsPerPage: 0, order_by: 'nombre' },
  })
  provincias.value = data
  loadingProv.value = false
}

async function cargarDistritos(codDep, codProv) {
  loadingDist.value = true
  const { data } = await UbigeoService.getData({
    params: {
      tipo: 'distrito',
      cod_dep: codDep,
      cod_prov: codProv,
      rowsPerPage: 0,
      order_by: 'nombre',
    },
  })
  distritos.value = data
  loadingDist.value = false
}

function onDepChange(dep) {
  selProv.value = null
  selDist.value = null
  provincias.value = []
  distritos.value = []
  if (!hydrating) emit('update:modelValue', null)
  if (dep) cargarProvincias(dep.cod_dep)
}

function onProvChange(prov) {
  selDist.value = null
  distritos.value = []
  if (!hydrating) emit('update:modelValue', null)
  if (prov) cargarDistritos(prov.cod_dep, prov.cod_prov)
}

function onDistChange(dist) {
  emit('update:modelValue', dist?.codigo ?? null)
}

// Del código guardado solo conocemos el distrito, así que hay que reconstruir
// hacia arriba el departamento y la provincia a los que pertenece.
async function initFromCodigo(codigo) {
  if (!codigo) return
  hydrating = true
  try {
    const distrito = await UbigeoService.get(codigo)
    const codDepartamento = distrito.cod_dep + '0000'
    const codProvincia = distrito.cod_dep + distrito.cod_prov + '00'

    const [departamento, provincia] = await Promise.all([
      UbigeoService.get(codDepartamento),
      UbigeoService.get(codProvincia),
    ])

    if (!departamentos.value.length) await cargarDepartamentos()
    selDep.value = departamento

    await cargarProvincias(distrito.cod_dep)
    selProv.value = provincia

    await cargarDistritos(distrito.cod_dep, distrito.cod_prov)
    selDist.value = distrito
  } finally {
    hydrating = false
  }
}

watch(
  () => props.modelValue,
  (codigo) => {
    if (codigo === (selDist.value?.codigo ?? null)) return

    if (!codigo) {
      selDep.value = null
      selProv.value = null
      selDist.value = null
      provincias.value = []
      distritos.value = []
      return
    }

    initFromCodigo(codigo)
  },
  { immediate: true },
)

onMounted(() => {
  if (!props.modelValue) cargarDepartamentos()
})
</script>
