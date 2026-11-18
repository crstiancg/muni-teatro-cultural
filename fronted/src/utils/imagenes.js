// srcset con las dos versiones WebP que arma el backend (Archivo::generarVersiones):
// miniatura de 600 px y media de 1200 px. El navegador elige la justa según el
// ancho en pantalla (atributo sizes) y la densidad: un celular común baja la de
// 600, una pantalla retina la de 1200, y nadie baja el original de 2400 px.
export function srcsetWebp(miniatura, media) {
  if (!miniatura || !media || miniatura === media) return undefined
  return `${miniatura} 600w, ${media} 1200w`
}
