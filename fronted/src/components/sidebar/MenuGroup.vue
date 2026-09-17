<script>
export default { name: 'MenuGroup' }
</script>

<script setup>
import { computed } from 'vue'
import MenuItem from './MenuItem.vue'

const props = defineProps({
  label: { type: String, required: true },
  caption: { type: String, default: '' },
  icon: { type: [Object, Function, String], default: null },
  children: { type: Array, default: () => [] },
  activeName: { type: String, default: '' },
  defaultOpen: { type: Boolean, default: false },
})

function hasActiveDescendant(item, activeName) {
  if (item.type === 'group') {
    return (item.children || []).some((c) => hasActiveDescendant(c, activeName))
  }
  return item.routeName === activeName
}

const isAnyChildActive = computed(() =>
  props.children.some((child) => hasActiveDescendant(child, props.activeName)),
)
</script>

<template>
  <q-expansion-item
    dark
    :label="label"
    :caption="caption"
    :content-inset-level="0.3"
    :default-opened="defaultOpen || isAnyChildActive"
    expand-icon-class="text-white"
    style="max-width: 100%; white-space: initial"
  >
    <template v-for="(child, idx) in children" :key="child.routeName || `group-${idx}`">
      <MenuGroup
        v-if="child.type === 'group'"
        :label="child.label"
        :caption="child.caption"
        :icon="child.icon"
        :children="child.children"
        :active-name="activeName"
        :default-open="child.defaultOpen"
      />
      <MenuItem
        v-else
        :label="child.label"
        :route-name="child.routeName"
        :icon="child.icon"
        :active-name="activeName"
      />
    </template>
  </q-expansion-item>
</template>
