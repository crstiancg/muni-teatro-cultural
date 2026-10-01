import { api } from '@/boot/axios'

class AgrupacionService {
  // ---------- panel del artista ----------
  static async mias() {
    return (await api.get('/api/mis-agrupaciones')).data
  }

  static async mia(id) {
    return (await api.get(`/api/mis-agrupaciones/${id}`)).data
  }

  static async crear(datos) {
    return (await api.post('/api/mis-agrupaciones', datos)).data
  }

  static async actualizar(id, datos) {
    return (await api.put(`/api/mis-agrupaciones/${id}`, datos)).data
  }

  static async eliminarMia(id) {
    return await api.delete(`/api/mis-agrupaciones/${id}`)
  }

  static async enviarRevision(id) {
    return (await api.post(`/api/mis-agrupaciones/${id}/enviar-revision`)).data
  }

  // { registrado, dni, nombre, apellido_paterno, apellido_materno }
  static async consultarDni(dni) {
    return (await api.get(`/api/mis-agrupaciones/consulta-dni/${dni}`)).data
  }

  static async agregarIntegrante(id, datos) {
    return (await api.post(`/api/mis-agrupaciones/${id}/integrantes`, datos)).data
  }

  static async actualizarIntegrante(id, integranteId, datos) {
    return (await api.put(`/api/mis-agrupaciones/${id}/integrantes/${integranteId}`, datos)).data
  }

  static async quitarIntegrante(id, integranteId) {
    return await api.delete(`/api/mis-agrupaciones/${id}/integrantes/${integranteId}`)
  }

  static async transferirRepresentante(id, integranteId) {
    return (await api.put(`/api/mis-agrupaciones/${id}/integrantes/${integranteId}/representante`)).data
  }

  // ---------- admin ----------
  static async listar(params) {
    return (await api.get('/api/agrupaciones', { params })).data
  }

  static async ver(id) {
    return (await api.get(`/api/agrupaciones/${id}`)).data
  }

  static async aprobar(id) {
    return (await api.put(`/api/agrupaciones/${id}/aprobar`)).data
  }

  static async observar(id, observacion) {
    return (await api.put(`/api/agrupaciones/${id}/observar`, { observacion })).data
  }

  static async eliminar(id) {
    return await api.delete(`/api/agrupaciones/${id}`)
  }

  // rescate de una agrupación sin representante activo
  static async asignarRepresentante(id, integranteId) {
    return (await api.put(`/api/agrupaciones/${id}/representante/${integranteId}`)).data
  }

  // ---------- portal ----------
  // { data, meta: { pagina, ultima_pagina, total } }
  static async publicas(params) {
    return (await api.get('/api/publico/agrupaciones', { params })).data
  }

  static async publica(slug) {
    return (await api.get(`/api/publico/agrupaciones/${slug}`)).data
  }
}

export default AgrupacionService
