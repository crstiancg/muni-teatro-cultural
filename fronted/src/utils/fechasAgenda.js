// Fechas de la agenda cultural. El backend manda "hora de pared" de la ciudad
// como texto ("2026-11-14 20:00", sin zona horaria): se trabaja con strings y
// con Date LOCALES construidos a mano, nunca con new Date("...Z").

export const ZONA_CIUDAD = 'America/Lima'

const dosDigitos = (n) => String(n).padStart(2, '0')

export const aFecha = (d) => `${d.getFullYear()}-${dosDigitos(d.getMonth() + 1)}-${dosDigitos(d.getDate())}`

export function desdeTexto(ymd) {
  const [a, m, d] = ymd.split('-').map(Number)
  return new Date(a, m - 1, d)
}

// "2026-11-14 20:00" → Date local
function aDate(valor) {
  const [fecha, hora = '00:00'] = valor.split(' ')
  const [h, min] = hora.split(':').map(Number)
  const d = desdeTexto(fecha)
  d.setHours(h, min)
  return d
}

export const hoy = () => aFecha(new Date())

export const hora = (valor) => valor?.slice(11, 16) ?? ''

export const diaDe = (valor) => valor.slice(0, 10)

const capitalizar = (t) => t.charAt(0).toUpperCase() + t.slice(1)

const fmtDia = new Intl.DateTimeFormat('es-PE', { weekday: 'long', day: 'numeric', month: 'long' })
const fmtMes = new Intl.DateTimeFormat('es-PE', { month: 'long', year: 'numeric' })
const fmtMesCorto = new Intl.DateTimeFormat('es-PE', { month: 'short' })
const fmtDiaSemana = new Intl.DateTimeFormat('es-PE', { weekday: 'long' })
const fmtDiaMes = new Intl.DateTimeFormat('es-PE', { day: 'numeric', month: 'long' })

export const tituloMes = (ymd) => capitalizar(fmtMes.format(desdeTexto(ymd)))

export function tituloDia(ymd) {
  const texto = capitalizar(fmtDia.format(desdeTexto(ymd)))
  return ymd === hoy() ? `Hoy · ${texto}` : texto
}

// un evento ocupa todos los días entre su inicio y su fin (festivales)
export function ocurreEl(evento, ymd) {
  const inicio = diaDe(evento.fecha_inicio)
  const fin = diaDe(evento.fecha_fin || evento.fecha_inicio)
  return inicio <= ymd && ymd <= fin
}

export const esDeVariosDias = (e) => !!e.fecha_fin && diaDe(e.fecha_fin) !== diaDe(e.fecha_inicio)

// en los días siguientes de un festival no tiene sentido repetir la hora de inicio
export const horaEnDia = (e, ymd) => (!ymd || diaDe(e.fecha_inicio) === ymd ? hora(e.fecha_inicio) : 'Sigue')

// "En curso" / "Hoy" / "Mañana" / "Sábado": se lee más rápido que una fecha
export function cercania(e) {
  const hoyTexto = hoy()
  const inicio = diaDe(e.fecha_inicio)
  const fin = diaDe(e.fecha_fin || e.fecha_inicio)
  const manana = desdeTexto(hoyTexto)
  manana.setDate(manana.getDate() + 1)

  if (inicio < hoyTexto && fin >= hoyTexto) return 'En curso'
  if (inicio === hoyTexto) return 'Hoy'
  if (inicio === aFecha(manana)) return 'Mañana'
  return capitalizar(fmtDiaSemana.format(desdeTexto(inicio)))
}

// para la "hoja de calendario" del detalle
export function hojaCalendario(e) {
  const d = desdeTexto(diaDe(e.fecha_inicio))
  return {
    dia: d.getDate(),
    mes: fmtMesCorto.format(d).replace('.', ''),
    diaSemana: capitalizar(fmtDiaSemana.format(d)),
  }
}

// "Miércoles 11 de noviembre" + "16:00 – 20:00", o "Del 10 al 12 de noviembre" + "Desde las 10:00"
export function cuandoTexto(e) {
  const inicio = desdeTexto(diaDe(e.fecha_inicio))
  if (esDeVariosDias(e)) {
    const fin = desdeTexto(diaDe(e.fecha_fin))
    const mismoMes = inicio.getMonth() === fin.getMonth()
    return {
      fecha: mismoMes
        ? `Del ${inicio.getDate()} al ${fmtDiaMes.format(fin)}`
        : `Del ${fmtDiaMes.format(inicio)} al ${fmtDiaMes.format(fin)}`,
      horario: `Desde las ${hora(e.fecha_inicio)} hasta las ${hora(e.fecha_fin)}`,
    }
  }
  return {
    fecha: capitalizar(fmtDia.format(inicio)),
    horario: e.fecha_fin ? `${hora(e.fecha_inicio)} – ${hora(e.fecha_fin)}` : `${hora(e.fecha_inicio)}`,
  }
}

// marcas de auditoría (created_at, revisado_en...): vienen en ISO/UTC, NO son hora
// de pared como las fechas del evento, así que se convierten a la zona de la ciudad.
// "10 nov, 08:30 p. m."
const fmtMarca = new Intl.DateTimeFormat('es-PE', {
  day: 'numeric',
  month: 'short',
  hour: '2-digit',
  minute: '2-digit',
  timeZone: ZONA_CIUDAD,
})
export const marcaTiempo = (iso) => (iso ? fmtMarca.format(new Date(iso)).replace('.', '') : null)

// Google Calendar: sin fecha de fin se asume una duración de 2 horas
export function urlGoogleCalendar(e, { lugar, detalle }) {
  const fmt = (d) =>
    `${d.getFullYear()}${dosDigitos(d.getMonth() + 1)}${dosDigitos(d.getDate())}T${dosDigitos(d.getHours())}${dosDigitos(d.getMinutes())}00`
  const inicio = aDate(e.fecha_inicio)
  const fin = e.fecha_fin ? aDate(e.fecha_fin) : new Date(inicio.getTime() + 2 * 60 * 60 * 1000)
  const params = new URLSearchParams({
    action: 'TEMPLATE',
    text: e.titulo,
    dates: `${fmt(inicio)}/${fmt(fin)}`,
    ctz: ZONA_CIUDAD,
    details: detalle,
    location: lugar,
  })
  return `https://calendar.google.com/calendar/render?${params}`
}
