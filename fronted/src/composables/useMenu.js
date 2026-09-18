import { computed } from 'vue'
import { LayoutDashboard, Users, Shield, KeyRound, Contact, FolderTree, Briefcase } from 'lucide-vue-next'
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
    {
      type: 'item',
      label: 'Personas',
      routeName: 'Personas',
      icon: Contact,
      permission: 'admin-personas-index',
    },
    {
      type: 'item',
      label: 'Comisiones',
      routeName: 'Comisiones',
      icon: FolderTree,
      permission: 'admin-comisiones-index',
    },
    {
      type: 'item',
      label: 'Profesiones',
      routeName: 'Profesiones',
      icon: Briefcase,
      permission: 'admin-profesiones-index',
    },
    {
      type: 'group',
      label: 'Administración',
      caption: 'Usuarios y accesos',
      icon: Shield,
      children: [
        { type: 'item', label: 'Usuarios', routeName: 'Usuarios', icon: Users, permission: 'admin-usuarios-index' },
        { type: 'item', label: 'Roles', routeName: 'Roles', icon: Shield, permission: 'admin-roles-index' },
        { type: 'item', label: 'Permisos', routeName: 'Permisos', icon: KeyRound, permission: 'admin-permisos-index' },
      ],
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
