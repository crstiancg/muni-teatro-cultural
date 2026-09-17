import { computed } from 'vue'
import { LayoutDashboard } from 'lucide-vue-next'
import { useUserStore } from '@/stores/user-store'

export function useMenu() {
  const auth = useUserStore()

  const menuItems = [
    {
      type: 'item',
      label: 'Dashboard',
      routeName: 'Dashboard',
      icon: LayoutDashboard,
    },
  ]

  function filterItem(item) {
    if (item.permission && !auth.hasPermission(item.permission)) return null
    if (item.role && !auth.roles?.includes(item.role)) return null

    if (item.type === 'group') {
      const visibleChildren = (item.children || []).map(filterItem).filter(Boolean)
      if (visibleChildren.length === 0) return null
      return { ...item, children: visibleChildren }
    }

    return item
  }

  const menu = computed(() => menuItems.map(filterItem).filter(Boolean))

  return { menu }
}
