// estados del perfil público (personas.estado): mismo orden que el flujo
export const ESTADOS_PERFIL = {
  borrador: { label: 'Borrador', color: 'grey-7' },
  pendiente: { label: 'En revisión', color: 'blue-7' },
  aprobado: { label: 'Publicado', color: 'positive' },
  observado: { label: 'Observado', color: 'orange-9' },
}

export const estadoPerfil = (estado) => ESTADOS_PERFIL[estado] || ESTADOS_PERFIL.borrador
