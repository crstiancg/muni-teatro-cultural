<template>
  <div v-if="!revisiones.length" class="text-caption text-grey-6">
    Aún no hay revisiones del perfil público.
  </div>

  <q-timeline v-else dense color="primary" class="q-my-none">
    <q-timeline-entry
      v-for="revision in revisiones"
      :key="revision.id"
      :color="ACCIONES[revision.accion]?.color"
      :subtitle="fecha(revision.created_at)"
    >
      <template #title>
        <span class="text-body2 text-weight-bold">{{ ACCIONES[revision.accion]?.label }}</span>
      </template>

      <div v-if="revision.observacion" class="observacion text-body2">
        {{ revision.observacion }}
      </div>
      <div v-if="revision.usuario" class="text-caption text-grey-6">
        por {{ revision.usuario.name }}
      </div>
    </q-timeline-entry>
  </q-timeline>
</template>

<script setup>
import { date } from 'quasar'

defineProps({
  // viene de persona.revisiones (backend: Persona::revisiones), más reciente primero
  revisiones: { type: Array, default: () => [] },
})

const ACCIONES = {
  enviado: { label: 'Enviado a revisión', color: 'blue-7' },
  aprobado: { label: 'Aprobado', color: 'positive' },
  observado: { label: 'Observado', color: 'orange-9' },
}

const fecha = (valor) => date.formatDate(valor, 'DD/MM/YYYY HH:mm')
</script>

<style scoped>
.observacion {
  white-space: pre-line;
  border-left: 3px solid var(--q-warning);
  padding-left: 8px;
  margin-bottom: 2px;
}
</style>
