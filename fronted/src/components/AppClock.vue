<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useQuasar } from 'quasar'

const $q = useQuasar()

const now = ref(new Date())
let intervalId = null

function tick() {
  now.value = new Date()
}

onMounted(() => {
  tick()
  intervalId = setInterval(tick, 1000)
})

onUnmounted(() => {
  if (intervalId) {
    clearInterval(intervalId)
    intervalId = null
  }
})

const fecha = computed(() =>
  now.value.toLocaleDateString('es-PE', {
    weekday: 'long',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }),
)

const hora = computed(() =>
  now.value.toLocaleTimeString('es-PE', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true,
  }),
)

const isSmall = computed(() => $q.screen.lt.md)
</script>

<template>
  <div class="app-clock row items-center no-wrap text-caption">
    <span v-if="!isSmall" class="q-mr-sm text-capitalize"> {{ fecha }} — </span>
    <span class="text-weight-medium">{{ hora }}</span>
  </div>
</template>

<style lang="scss" scoped>
.app-clock {
  font-variant-numeric: tabular-nums;
}
</style>
