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
        path: 'universidades',
        name: 'Universidades',
        component: () => import('@/pages/Universidades/UniversidadesList.vue'),
      },
      {
        path: 'carreras',
        name: 'Carreras',
        component: () => import('@/pages/Carreras/CarrerasList.vue'),
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
      {
        path: 'mis-agrupaciones',
        name: 'MisAgrupaciones',
        component: () => import('@/pages/Agrupaciones/MisAgrupacionesPage.vue'),
      },
      {
        path: 'mis-agrupaciones/:id',
        name: 'MiAgrupacion',
        component: () => import('@/pages/Agrupaciones/MiAgrupacionPage.vue'),
      },
      {
        // /gestion/...: /agrupaciones es del portal público (directorio y páginas)
        path: 'gestion/agrupaciones',
        name: 'AgrupacionesAdmin',
        component: () => import('@/pages/Agrupaciones/AgrupacionesAdminPage.vue'),
      },
      {
        path: 'gestion/agrupaciones/:id',
        name: 'AgrupacionAdminDetalle',
        component: () => import('@/pages/Agrupaciones/AgrupacionAdminDetallePage.vue'),
      },
      {
        // el artista propone sus eventos; el admin los revisa en gestion/eventos
        path: 'mis-eventos',
        name: 'MisEventos',
        component: () => import('@/pages/Eventos/MisEventosPage.vue'),
      },
      {
        // /gestion/...: /agenda es la del portal público
        path: 'gestion/eventos',
        name: 'EventosAdmin',
        component: () => import('@/pages/Eventos/EventosList.vue'),
      },
      {
        // revisión de un evento: afiche, datos, organizador, historial y aprobar/observar
        path: 'gestion/eventos/:id',
        name: 'EventoAdminDetalle',
        component: () => import('@/pages/Eventos/EventoRevisionPage.vue'),
      },
    ],
  },

  {
    path: '/privacidad',
    component: () => import('@/layouts/PublicoLayout.vue'),
    children: [{ path: '', name: 'Privacidad', component: () => import('@/pages/Publico/PrivacidadPage.vue') }],
  },

  {
    path: '/agrupaciones',
    component: () => import('@/layouts/PublicoLayout.vue'),
    children: [
      { path: '', name: 'AgrupacionesPublico', component: () => import('@/pages/Publico/AgrupacionesPage.vue') },
      { path: ':slug', name: 'AgrupacionPublica', component: () => import('@/pages/Publico/AgrupacionPublicaPage.vue') },
    ],
  },

  {
    path: '/agenda',
    component: () => import('@/layouts/PublicoLayout.vue'),
    children: [
      { path: '', name: 'AgendaPublica', component: () => import('@/pages/Publico/AgendaPage.vue') },
      // ":evento" = "{id}-{slug}": se busca por el id, el slug es solo para que se lea
      { path: ':evento', name: 'EventoPublico', component: () => import('@/pages/Publico/EventoPage.vue') },
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
