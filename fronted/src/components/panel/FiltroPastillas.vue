<template>
  <!-- mismo patrón que los filtros del portal (Agenda): pastillas; la activa va
       llena con los colores invertidos. En el celular se deslizan en una fila sin
       barra de scroll visible, hasta el borde de la pantalla, así se nota que sigue. -->
  <div class="pastillas" role="group" :aria-label="etiqueta">
    <button
      v-for="op in opciones"
      :key="String(op.value)"
      type="button"
      class="pastilla"
      :class="{ activa: op.value === modelValue }"
      :aria-pressed="op.value === modelValue"
      @click="elegir(op.value)"
    >
      <span v-if="op.color" class="punto" :class="`text-${op.color}`" aria-hidden="true" />
      {{ op.label }}
    </button>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: [String, Number, null], default: null },
  // [{ label, value, color? }] — color: nombre de color de Quasar (p. ej. 'blue-7')
  opciones: { type: Array, required: true },
  etiqueta: { type: String, default: 'Filtrar' },
})
const emit = defineEmits(['update:modelValue'])

function elegir(valor) {
  if (valor !== props.modelValue) emit('update:modelValue', valor)
}
</script>

<style scoped>
.pastillas {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  min-width: 0;
}

.pastilla {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 7px 14px;
  border-radius: 999px;
  border: 1px solid var(--panel-borde);
  background: var(--panel-superficie);
  color: var(--panel-texto);
  font: inherit;
  font-size: 0.86rem;
  font-weight: 600;
  line-height: 1.2;
  white-space: nowrap;
  cursor: pointer;
  transition:
    border-color 0.15s,
    background 0.15s,
    color 0.15s;
}

.pastilla:hover {
  border-color: var(--panel-texto-suave);
}

.pastilla.activa {
  background: var(--panel-primario);
  border-color: var(--panel-primario);
  color: var(--panel-sobre-primario);
}

.pastilla:focus-visible {
  outline: 2px solid var(--panel-acento);
  outline-offset: 2px;
}

.punto {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: currentColor;
  flex-shrink: 0;
}

/* celular: una sola fila que se desliza, sin barra visible, que llega al borde */
@media (max-width: 700px) {
  .pastillas {
    flex-wrap: nowrap;
    overflow-x: auto;
    margin-left: calc(var(--panel-gutter) * -1);
    margin-right: calc(var(--panel-gutter) * -1);
    padding: 2px var(--panel-gutter);
    scrollbar-width: none;
  }

  .pastillas::-webkit-scrollbar {
    display: none;
  }

  .pastilla {
    flex-shrink: 0;
  }
}
</style>
