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
//   permission → solo se muestra con ese permiso. TODO el menú se decide así,
//                también "Mi espacio" (permisos artista-* del rol Artista).
//                NUNCA por el nombre del rol: antes se ocultaba con el rol
//                "Administrador" escrito a mano y el rol nuevo "Administrador de
//                la Gerencia" veía "Mi espacio" sin ser artista.
//   contador   → clave de /api/panel/contadores: cuántos pendientes hay en la sección
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
      { label: 'Mi perfil', routeName: 'Perfil', icon: UserRound, permission: 'artista-perfil' },
      {
        label: 'Curriculum Vitae',
        routeName: 'CurriculumVitae',
        icon: GraduationCap,
        permission: 'artista-perfil',
      },
      {
        label: 'Mis agrupaciones',
        routeName: 'MisAgrupaciones',
        icon: UsersRound,
        permission: 'artista-agrupaciones',
      },
      {
        label: 'Mis eventos',
        routeName: 'MisEventos',
        icon: CalendarDays,
        permission: 'artista-eventos',
      },
    ],
  },
  {
    titulo: 'Gestión',
    items: [
      {
        label: 'Agentes culturales',
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

  const visible = (item) => !item.permission || auth.hasPermission(item.permission)

  const secciones = computed(() =>
    SECCIONES.map((s) => ({ ...s, items: s.items.filter(visible) })).filter((s) => s.items.length),
  )

  return { secciones }
}
