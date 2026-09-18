const formPerfil = {
  persona: {
    dni: '',
    nombre: '',
    apellido_paterno: '',
    apellido_materno: '',
    correo: '',
    correo_modificado: false,
    correo_original: '',
    genero: null,
    estado_civil: null,
    fecha_nacimiento: '',
    celular: '',
    celular_emergencia: '',
    direccion: '',
    ubigeo_cod_nacimiento: null,
    ubigeo_cod_residencia: null,
    codigo_comision: null,
    codigo_comision_alternativo: null,
  },
}

export const formUsuarioPerfil = {
  usuario: { name: '', email: '' },
}

export const formPasswordPerfil = {
  password: { actual: '', nueva: '', nueva_confirmation: '' },
}

export default formPerfil
