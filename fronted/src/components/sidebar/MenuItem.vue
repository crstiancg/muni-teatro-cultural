<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const props = defineProps({
  label: { type: String, required: true },
  routeName: { type: String, required: true },
  icon: { type: [Object, Function, String], default: null },
  activeName: { type: String, default: '' },
})

const route = useRoute()
const router = useRouter()

const isActive = computed(() => {
  const resolved = router.resolve({ name: props.routeName })
  return route.path === resolved.path || (resolved.path !== '/' && route.path.startsWith(resolved.path + '/'))
})
const isLucide = computed(() => props.icon && typeof props.icon !== 'string')
</script>

<template>
  <q-item
    :to="{ name: routeName }"
    :active="isActive"
    clickable
    v-ripple
    dense
    active-class="my-menu-link"
    class="q-ma-xs q-pa-sm menu-item-link"
  >
    <q-item-section side>
      <component v-if="isLucide" :is="icon" stroke-width="1.5" size="22px" color="white" />
      <q-icon v-else-if="icon" :name="icon" color="white" size="22px" />
    </q-item-section>

    <q-item-section>
      <span class="text-white" :class="isActive ? 'text-weight-bold' : 'text-weight-medium'">
        {{ label }}
      </span>
    </q-item-section>
  </q-item>
</template>

<style lang="scss" scoped>
.menu-item-link {
  border-radius: 10px;
}
</style>
