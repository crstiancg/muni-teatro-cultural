<template>
  <!-- Acción de fila (editar, eliminar...): ícono + texto para que se identifique
       sin adivinar. Eliminar/quitar van SIEMPRE en el rojo de peligro, que no
       depende del acento elegido por el usuario. -->
  <button
    type="button"
    class="accion"
    :class="[`accion--${def.variante}`, { 'accion--compacta': compacto }]"
    :aria-label="texto"
  >
    <component :is="def.icono" :size="15" :stroke-width="2.2" class="accion-icono" />
    <span v-if="!compacto" class="accion-texto">{{ texto }}</span>
    <q-tooltip v-if="compacto">{{ texto }}</q-tooltip>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { Pencil, Trash2, UserMinus } from 'lucide-vue-next'

const TIPOS = {
  editar: { etiqueta: 'Editar', icono: Pencil, variante: 'neutra' },
  eliminar: { etiqueta: 'Eliminar', icono: Trash2, variante: 'peligro' },
  // sacar a alguien de un grupo: no borra a la persona, pero igual es destructivo
  quitar: { etiqueta: 'Quitar', icono: UserMinus, variante: 'peligro' },
}

const props = defineProps({
  tipo: {
    type: String,
    required: true, // la lista va literal: defineProps se compila fuera del setup y no ve TIPOS
    validator: (v) => ['editar', 'eliminar', 'quitar'].includes(v),
  },
  // texto propio si "Editar"/"Eliminar" no alcanza (p. ej. "Editar permiso")
  etiqueta: { type: String, default: '' },
  // solo ícono (con tooltip): para espacios muy chicos, no para tablas
  compacto: { type: Boolean, default: false },
})

const def = computed(() => TIPOS[props.tipo])
const texto = computed(() => props.etiqueta || def.value.etiqueta)
</script>

<style scoped>
.accion {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 30px;
  padding: 0 10px;
  border-radius: 8px;
  border: 1px solid;
  font: inherit;
  font-size: 0.8rem;
  font-weight: 600;
  line-height: 1;
  white-space: nowrap;
  cursor: pointer;
  transition:
    background 0.12s,
    border-color 0.12s;
}

.accion + .accion {
  margin-left: 6px;
}

.accion--compacta {
  width: 30px;
  padding: 0;
  justify-content: center;
}

.accion--neutra {
  color: var(--panel-texto);
  background: var(--panel-superficie);
  border-color: var(--panel-borde);
}

.accion--neutra:hover {
  background: var(--panel-hover);
  border-color: var(--panel-texto-suave);
}

.accion--peligro {
  color: var(--panel-peligro);
  background: var(--panel-peligro-suave);
  border-color: color-mix(in srgb, var(--panel-peligro) 30%, transparent);
}

.accion--peligro:hover {
  background: color-mix(in srgb, var(--panel-peligro) 16%, transparent);
  border-color: var(--panel-peligro);
}

.accion:focus-visible {
  outline: 2px solid var(--panel-acento);
  outline-offset: 2px;
}

.accion:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* celular: solo el ícono (el nombre sigue en aria-label para lectores de pantalla) */
@media (max-width: 599px) {
  .accion:not(.accion--compacta) {
    width: 34px;
    height: 34px;
    padding: 0;
    justify-content: center;
  }

  .accion-texto {
    display: none;
  }
}
</style>
