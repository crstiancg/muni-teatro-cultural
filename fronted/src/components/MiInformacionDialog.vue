<template>
  <q-card style="width: 100%; max-width: 700px">
    <q-scroll-area style="height: 75vh">
      <PerfilContenido
        :data-inicial="dataInicial"
        :mostrar-password="false"
        :obligatorio="obligatorio"
        @save="emits('save')"
      />
    </q-scroll-area>

    <!-- con datos incompletos no hay "Cerrar": se sale guardando -->
    <template v-if="!obligatorio">
      <q-separator />
      <q-card-actions align="right">
        <q-btn label="Cerrar" flat v-close-popup></q-btn>
      </q-card-actions>
    </template>
  </q-card>
</template>

<script setup>
import PerfilContenido from '@/pages/Perfil/PerfilContenido.vue'

const emits = defineEmits(['save'])
defineProps({
  dataInicial: { type: Object, default: null },
  obligatorio: { type: Boolean, default: false },
})
</script>
