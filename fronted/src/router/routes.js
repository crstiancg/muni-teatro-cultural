const routes = [
  {
    path: '/login',
    component: () => import('@/layouts/AuthLayout.vue'),
    children: [
      {
        path: '',
        name: 'Login',
        component: () => import('@/pages/Auth/LoginPage.vue'),
      },
    ],
  },

  {
    path: '/',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/pages/Dashboard/DashboardPage.vue'),
      },
      {
        path: 'usuarios',
        name: 'Usuarios',
        component: () => import('@/pages/Usuarios/UsuariosList.vue'),
      },
      {
        path: 'roles',
        name: 'Roles',
        component: () => import('@/pages/Roles/RolesList.vue'),
      },
      {
        path: 'permisos',
        name: 'Permisos',
        component: () => import('@/pages/Permisos/PermisosList.vue'),
      },
      {
        path: 'profesiones',
        name: 'Profesiones',
        component: () => import('@/pages/Profesiones/ProfesionesList.vue'),
      },
      {
        path: 'personas',
        name: 'Personas',
        component: () => import('@/pages/Personas/PersonasList.vue'),
      },
      {
        path: 'comisiones',
        name: 'Comisiones',
        component: () => import('@/pages/Comisiones/ComisionesList.vue'),
      },
      {
        path: 'perfil',
        name: 'Perfil',
        component: () => import('@/pages/Perfil/PerfilPage.vue'),
      },
    ],
  },

  {
    path: '/:catchAll(.*)*',
    component: () => import('@/pages/ErrorNotFound.vue'),
  },
]

export default routes
