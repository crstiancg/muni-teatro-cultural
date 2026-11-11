// estados de un evento de la agenda (eventos.estado): mismos colores que el perfil
// y las agrupaciones, para que "En revisión" / "Observado" se lean igual en todo el panel
export const ESTADOS_EVENTO = {
  pendiente: { label: 'En revisión', color: 'blue-7' },
  aprobado: { label: 'Publicado', color: 'positive' },
  observado: { label: 'Observado', color: 'orange-9' },
  cancelado: { label: 'Cancelado', color: 'grey-7' },
}

export const estadoEvento = (estado) => ESTADOS_EVENTO[estado] || ESTADOS_EVENTO.pendiente

export const opcionesEstadoEvento = Object.entries(ESTADOS_EVENTO).map(([value, { label }]) => ({
  label,
  value,
}))
