// ============================================================================
// Datos institucionales del sitio público.
//
// Todo lo que diga PENDIENTE hay que confirmarlo con la municipalidad antes de
// publicar. Está centralizado acá a propósito: antes estos textos vivían
// sueltos dentro de LandingPage.vue con un `// TODO` al lado y se publicaba
// "Completar dirección" en la portada.
// ============================================================================

export const INSTITUCION = {
  // PENDIENTE: confirmar el nombre oficial y de qué entidad depende
  nombre: 'RACC',
  entidad: 'Registro de Agentes Culturales del Consejo Provincial de Cultura',
  ciudad: 'Puno',
  region: 'Puno, Perú',

  // Puno es Capital Folclórica del Perú por Ley 24325; la Festividad de la
  // Virgen de la Candelaria es Patrimonio Cultural Inmaterial de la Humanidad
  // (UNESCO, 2014). Son los dos datos que anclan la identidad del sitio.
  lema: 'Puno, Capital Folclórica del Perú',

  // solo datos, sin etiquetas: el footer pone el ícono/etiqueta y Privacidad
  // los usa dentro de una oración ("con domicilio en …")
  contacto: {
    direccion: ['Jr. Deustua N.° 458', 'Puno, Perú'],
    telefono: ['(051) 601000'],
    correo: ['alcaldia@munipuno.gob.pe'],
    horario: ['Lunes a viernes: 8:00 – 16:00'],
    // horario: ['Lunes a viernes: 8:00 – 16:00', 'Sábado: 8:00 – 12:00'],
  },
}

// Nombre corto y color de cada comisión. El backend las devuelve como
// "Comision especializada de artes escenicas - teatro", que en una tarjeta
// ocupa tres líneas y se repite idéntico en las ocho.
export const COMISIONES = {
  '01': { corto: 'Música', color: 'var(--com-01)', foto: '/images/com-01.jpg' },
  '02': { corto: 'Danza', color: 'var(--com-02)', foto: '/images/com-02.jpg' },
  '03': { corto: 'Artes escénicas', color: 'var(--com-03)', foto: '/images/com-03.jpg' },
  '04': { corto: 'Literatura y oralidad', color: 'var(--com-04)', foto: '/images/com-04.jpg' },
  '05': { corto: 'Bordado y trajes típicos', color: 'var(--com-05)', foto: '/images/com-05.jpg' },
  '06': { corto: 'Audiovisuales', color: 'var(--com-06)', foto: '/images/com-06.jpg' },
  '07': { corto: 'Artes plásticas', color: 'var(--com-07)', foto: '/images/com-07.jpg' },
  '08': { corto: 'Gestión cultural', color: 'var(--com-08)', foto: '/images/com-08.jpg' },
}

export function comisionDe(codGrupo) {
  return COMISIONES[codGrupo] || { corto: 'Cultura', color: 'var(--oro)' }
}

// Link para compartir un perfil: lo sirve el backend (/compartir/{slug}) con las
// etiquetas Open Graph, así WhatsApp/Facebook muestran foto y nombre. Una
// persona que lo abre es redirigida al perfil del portal.
// En producción QCLI_API_BACKEND_URL va vacío (front y API en el mismo dominio,
// rutas relativas): WhatsApp necesita una URL absoluta, así que se usa el dominio actual.
export function urlCompartirPerfil(slug) {
  const base = String(import.meta.env.QCLI_API_BACKEND_URL || window.location.origin).replace(/\/$/, '')
  return `${base}/compartir/${slug}`
}
