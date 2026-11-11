import { computed } from 'vue'
import {
  LayoutDashboard,
  Users,
  Shield,
  KeyRound,
  Contact,
  FolderTree,
  GraduationCap,
  Briefcase,
  School,
  BookOpen,
  UsersRound,
  CalendarDays,
  UserRound,
} from 'lucide-vue-next'
import { useUserStore } from '@/stores/user-store'

// Menú lateral del panel, agrupado por secciones. Cada ítem puede llevar:
//   permission  → solo se muestra con ese permiso
//   hideForRole → se oculta para ese rol (las secciones del artista no son del admin)
//   contador    → clave de /api/panel/contadores: cuántos pendientes hay en la sección
// Una sección sin ítems visibles no se muestra.
const SECCIONES = [
  {
    titulo: null,
    items: [{ label: 'Inicio', routeName: 'Dashboard', icon: LayoutDashboard }],
  },
  {
    titulo: 'Mi espacio',
    items: [
      // el admin llega a su perfil desde el menú de la cuenta (avatar)
      { label: 'Mi perfil', routeName: 'Perfil', icon: UserRound, hideForRole: 'Administrador' },
      {
        label: 'Curriculum Vitae',
        routeName: 'CurriculumVitae',
        icon: GraduationCap,
        hideForRole: 'Administrador',
      },
      {
        label: 'Mis agrupaciones',
        routeName: 'MisAgrupaciones',
        icon: UsersRound,
        hideForRole: 'Administrador',
      },
      {
        label: 'Mis eventos',
        routeName: 'MisEventos',
        icon: CalendarDays,
        hideForRole: 'Administrador',
      },
    ],
  },
  {
    titulo: 'Gestión',
    items: [
      {
        label: 'Personas',
        routeName: 'Personas',
        icon: Contact,
        permission: 'admin-personas-index',
        contador: 'personas',
      },
      {
        label: 'Agrupaciones',
        routeName: 'AgrupacionesAdmin',
        icon: UsersRound,
        permission: 'admin-agrupaciones-index',
        contador: 'agrupaciones',
      },
      {
        label: 'Agenda cultural',
        routeName: 'EventosAdmin',
        icon: CalendarDays,
        permission: 'admin-eventos-index',
        contador: 'eventos',
      },
    ],
  },
  {
    titulo: 'Catálogos',
    items: [
      {
        label: 'Comisiones',
        routeName: 'Comisiones',
        icon: FolderTree,
        permission: 'admin-comisiones-index',
      },
      {
        label: 'Profesiones',
        routeName: 'Profesiones',
        icon: Briefcase,
        permission: 'admin-profesiones-index',
      },
      {
        label: 'Universidades',
        routeName: 'Universidades',
        icon: School,
        permission: 'admin-universidades-index',
      },
      {
        label: 'Carreras',
        routeName: 'Carreras',
        icon: BookOpen,
        permission: 'admin-carreras-index',
      },
    ],
  },
  {
    titulo: 'Accesos',
    items: [
      { label: 'Usuarios', routeName: 'Usuarios', icon: Users, permission: 'admin-usuarios-index' },
      { label: 'Roles', routeName: 'Roles', icon: Shield, permission: 'admin-roles-index' },
      {
        label: 'Permisos',
        routeName: 'Permisos',
        icon: KeyRound,
        permission: 'admin-permisos-index',
      },
    ],
  },
]

export function useMenu() {
  const auth = useUserStore()

  const visible = (item) =>
    (!item.permission || auth.hasPermission(item.permission)) &&
    !(item.hideForRole && auth.roles?.includes(item.hideForRole))

  const secciones = computed(() =>
    SECCIONES.map((s) => ({ ...s, items: s.items.filter(visible) })).filter((s) => s.items.length),
  )

  return { secciones }
}
