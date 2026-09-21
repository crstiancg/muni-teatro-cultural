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
  nombre: 'Teatro Cultural',
  entidad: 'Municipalidad Provincial de Puno',
  ciudad: 'Puno',
  region: 'Puno, Perú',

  // Puno es Capital Folclórica del Perú por Ley 24325; la Festividad de la
  // Virgen de la Candelaria es Patrimonio Cultural Inmaterial de la Humanidad
  // (UNESCO, 2014). Son los dos datos que anclan la identidad del sitio.
  lema: 'Puno, Capital Folclórica del Perú',

  contacto: {
    direccion: ['PENDIENTE: dirección', 'Puno, Perú'],
    telefono: ['PENDIENTE: teléfono'],
    correo: ['PENDIENTE: correo'],
    horario: ['Lunes a viernes: 8:00 – 16:00', 'Sábado: 8:00 – 12:00'],
  },
}

// Fotos del cartel de portada. Son fotos curadas de Puno que ya venían en
// public/images: el cartel institucional NO usa las actividades que suben los
// artistas, porque son fotos de celular, de calidad impredecible, y pueden no
// existir. Las actividades tienen su lugar en la galería.
// PENDIENTE: sumar fotos propias de la Candelaria (parada, bordadores, sikuris).
export const FOTOS_CARTEL = [
  {
    id: 'c1',
    imagen_url: '/images/wallpaper_puno.png',
    descripcion: 'Danzantes y balsa de totora frente al lago Titicaca',
  },
  { id: 'c2', imagen_url: '/images/wall1.jpg', descripcion: 'Islas flotantes de los Uros' },
  { id: 'c3', imagen_url: '/images/wall2.jpg', descripcion: 'Paisaje de Puno' },
  { id: 'c4', imagen_url: '/images/wall3.jpg', descripcion: 'Paisaje de Puno' },
]

// Nombre corto y color de cada comisión. El backend las devuelve como
// "Comision especializada de artes escenicas - teatro", que en una tarjeta
// ocupa tres líneas y se repite idéntico en las ocho.
export const COMISIONES = {
  '01': { corto: 'Música', color: 'var(--com-01)' },
  '02': { corto: 'Danza', color: 'var(--com-02)' },
  '03': { corto: 'Artes escénicas', color: 'var(--com-03)' },
  '04': { corto: 'Literatura y oralidad', color: 'var(--com-04)' },
  '05': { corto: 'Bordado y trajes típicos', color: 'var(--com-05)' },
  '06': { corto: 'Audiovisuales', color: 'var(--com-06)' },
  '07': { corto: 'Artes plásticas', color: 'var(--com-07)' },
  '08': { corto: 'Gestión cultural', color: 'var(--com-08)' },
}

export function comisionDe(codGrupo) {
  return COMISIONES[codGrupo] || { corto: 'Cultura', color: 'var(--oro)' }
}
