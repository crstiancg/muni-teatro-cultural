<template>
  <q-list v-if="integrantes.length" separator bordered class="rounded-borders">
    <q-item v-for="i in integrantes" :key="i.id">
      <q-item-section avatar>
        <q-avatar color="primary" text-color="white" size="36px">
          {{ i.nombre?.charAt(0) }}
        </q-avatar>
      </q-item-section>

      <q-item-section>
        <q-item-label class="text-weight-medium">
          {{ i.nombre_completo }}
          <q-badge v-if="i.es_representante" color="primary" class="q-ml-xs">Representante</q-badge>
          <q-badge v-if="i.persona_id" color="positive" outline class="q-ml-xs">
            Artista registrado
          </q-badge>
        </q-item-label>
        <!-- el DNI solo se ve en el panel (representante y admin), nunca en el portal -->
        <q-item-label caption>DNI {{ i.dni }}<template v-if="i.rol"> · {{ i.rol }}</template></q-item-label>
      </q-item-section>

      <q-item-section v-if="editable" side>
        <div class="row no-wrap q-gutter-xs">
          <q-btn flat dense round size="sm" icon="edit" color="primary" @click="emit('editar', i)">
            <q-tooltip>Editar</q-tooltip>
          </q-btn>
          <q-btn
            v-if="!i.es_representante"
            flat
            dense
            round
            size="sm"
            icon="delete"
            color="negative"
            @click="emit('quitar', i)"
          >
            <q-tooltip>Quitar</q-tooltip>
          </q-btn>
        </div>
      </q-item-section>
    </q-item>
  </q-list>
  <div v-else class="text-grey-6 text-center q-pa-md">Todavía no hay integrantes.</div>
</template>

<script setup>
defineProps({
  integrantes: { type: Array, default: () => [] },
  // representante: puede editar y quitar; admin: solo lectura
  editable: { type: Boolean, default: false },
})
const emit = defineEmits(['editar', 'quitar'])
</script>
