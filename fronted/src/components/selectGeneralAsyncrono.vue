<template>
  <p v-if="debug">{{ model }}</p>
  <q-select
    :readonly="readonly"
    :disable="disable"
    ref="RefSelect"
    outlined
    dense
    color="primary"
    options-selected-class="text-deep-orange"
    :label="label"
    clearable
    use-input
    hide-selected
    fill-input
    input-debounce="500"
    :option-value="optionValue"
    :option-label="optionLabel"
    v-model="model"
    :loading="isLoading"
    :options="options"
    @filter="filterFn"
    @virtual-scroll="onScroll"
    @update:model-value="(e) => onUpdate(e)"
    @input-value="onInputValue"
    @new-value="addNewOption"
    @clear="(v) => onClear(v)"
    new-value-mode="add-unique"
    :error="error"
  >
    <template v-slot:error>
      <div>{{ errorMessages }}</div>
    </template>
    <template v-slot:prepend>
      <q-icon :name="prependIcon" />
    </template>
    <!-- Si el padre define el slot "option" manda el suyo; si no, el render por defecto. -->
    <template v-if="$slots.option" v-slot:option="scope">
      <slot name="option" v-bind="scope" />
    </template>
    <template v-slot:no-option="scope">
      <q-item v-if="add" clickable @click="customAdd(scope.inputValue, scope.doneFn)" focused>
        <q-item-section>
          {{ valorInput }}
        </q-item-section>
        <q-item-section class="text-green" side> Presiona Enter o Click para agregar </q-item-section>
      </q-item>
      <q-item v-else>
        <q-item-section side> No se encontró ningún resultado. </q-item-section>
      </q-item>
    </template>
    <template v-slot:after v-if="showAddButton">
      <q-btn
        :label="$q.screen.lt.sm ? '' : addButtonLabel"
        :color="addButtonColor"
        :icon-right="addButtonIcon"
        @click="customAdd(valorInput, () => {})"
      />
    </template>
  </q-select>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue'
import { useNotify } from '@/composables/useNotify'

const { notifySuccess, notifyError } = useNotify()

const props = defineProps({
  label: { type: String, default: 'Seleccionar' },
  optionValue: { type: String, default: 'id' },
  optionLabel: { type: [String, Function], default: 'nombre' },
  filterFields: { type: Array, default: () => ['nombre'] },
  prependIcon: { type: String, default: 'list' },
  initialOptions: { type: Array, default: () => [] },
  serviceApi: { type: Function, required: true },
  paramsApi: { type: Object, required: false },
  debug: { type: Boolean, default: false },
  disable: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  error: { type: Boolean, default: false },
  errorMessages: { type: String },
  add: { type: Boolean, default: true },
  custonGet: { type: String },
  showAddButton: { type: Boolean, default: false },
  addButtonLabel: { type: String, default: 'Agregar' },
  addButtonIcon: { type: String, default: 'add' },
  addButtonColor: { type: String, default: 'primary' },
  excludeIds: { type: Array, default: () => [] },
})

const RefSelect = ref(null)
const model = defineModel()
const id = defineModel('id')

const options = ref(props.initialOptions)
const response = ref(null)
const isLoading = ref(false)
const page = ref(1)
const totalPage = ref(0)
const needle = ref(null)
const valorInput = ref('')

const emit = defineEmits(['add-new-value', 'clear', 'custom-add', 'option-selected'])

onMounted(async () => {
  if (!model.value && id.value) {
    try {
      const res = await props.serviceApi.get(id.value)
      if (props.custonGet) {
        model.value = res[props.custonGet]
        id.value = res[props.custonGet].id
      } else {
        model.value = res
        id.value = res.id
      }
    } catch {
      // sin resultado, se deja el select vacío
    }
  }
})

const setLoading = (value) => {
  isLoading.value = value
}

function onUpdate(e) {
  id.value = e?.[props.optionValue]
  emit('option-selected', e)
}

const refreshData = async () => {
  const params = {
    sort_by: 'id',
    direction: 'desc',
    ...props.paramsApi,
  }
  try {
    response.value = await props.serviceApi.getData({
      params: { ...params, search: needle.value, page: page.value },
    })
  } catch (error) {
    notifyError('Error al obtener datos')
    console.error(error)
  }
}

const filterFn = async (val, update, abort) => {
  try {
    setLoading(true)
    needle.value = val
    page.value = 1
    await refreshData()

    const filteredOptions = response.value.data.filter((v) => {
      if (props.excludeIds.includes(v[props.optionValue])) return false
      return props.filterFields.some((field) =>
        String(v[field] ?? '')
          .toLowerCase()
          .includes(needle.value.toLowerCase()),
      )
    })

    update(() => {
      options.value = filteredOptions
      totalPage.value = response.value.last_page
    })
  } catch (error) {
    notifyError('Error en la función de filtrado')
    console.error('Error en la función de filtrado:', error)
    abort()
  } finally {
    setLoading(false)
  }
}

const onScroll = async ({ index, ref }) => {
  const lastIndex = options.value.length - 1
  if (isLoading.value !== true && page.value < totalPage.value && index === lastIndex) {
    setLoading(true)
    page.value++
    await refreshData()
    options.value.push(...response.value.data)
    nextTick(() => {
      ref.refresh()
      setLoading(false)
    })
  }
}

const onInputValue = (e) => {
  valorInput.value = e
}

const addNewOption = async (inputValue, doneFn) => {
  try {
    setLoading(true)
    const res = await props.serviceApi.save({ nombre: valorInput.value })
    doneFn(res, 'add-unique')
    emit('add-new-value', res)
    notifySuccess('Datos guardados correctamente!')
  } catch (error) {
    notifyError(error)
    console.error(error)
  } finally {
    setLoading(false)
  }
}

const customAdd = () => {
  emit('custom-add', valorInput.value)
}

const onClear = (v) => {
  emit('clear', v)
}
</script>
