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
    component: () => import('@/layouts/PublicoLayout.vue'),
    children: [{ path: '', name: 'Inicio', component: () => import('@/pages/Publico/LandingPage.vue') }],
  },

  {
    path: '/',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
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
        path: 'personas/:id',
        name: 'PersonaDetalle',
        component: () => import('@/pages/Personas/PersonaDetallePage.vue'),
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
      {
        path: 'curriculum-vitae',
        name: 'CurriculumVitae',
        component: () => import('@/pages/Perfil/CurriculumVitaePage.vue'),
      },
    ],
  },

  {
    path: '/consejeros',
    component: () => import('@/layouts/PublicoLayout.vue'),
    children: [
      { path: '', name: 'ConsejerosPublico', component: () => import('@/pages/Publico/ConsejerosPage.vue') },
      { path: ':slug', name: 'ConsejeroDetallePublico', component: () => import('@/pages/Publico/ConsejeroDetallePage.vue') },
      { path: ':slug/galeria', name: 'ConsejeroGaleriaPublico', component: () => import('@/pages/Publico/ConsejeroGaleriaPage.vue') },
    ],
  },

  {
    path: '/:catchAll(.*)*',
    component: () => import('@/pages/ErrorNotFound.vue'),
  },
]

export default routes
