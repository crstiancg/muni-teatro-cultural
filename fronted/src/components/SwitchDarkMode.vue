<script setup>
import { useQuasar } from 'quasar'
import { ref } from 'vue'

const $q = useQuasar()
const darkMode = ref($q.dark.isActive)

function changeDarkMode() {
  $q.dark.set(darkMode.value)
}
</script>

<template>
  <q-toggle
    dense
    v-model="darkMode"
    color="primary"
    @update:model-value="changeDarkMode()"
    size="lg"
    checked-icon="mdi-moon-waxing-crescent"
    unchecked-icon="mdi-white-balance-sunny"
  />
</template>
