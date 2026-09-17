<template>
  <q-page>
    <div class="q-pa-md q-gutter-sm">
      <q-breadcrumbs>
        <q-breadcrumbs-el icon="home" />
        <q-breadcrumbs-el label="Mi Perfil" icon="account_circle" />
      </q-breadcrumbs>
    </div>
    <q-separator />
    <q-card class="q-ma-sm" flat :bordered="!$q.dark.isActive">
      <PerfilContenido />
    </q-card>
  </q-page>
</template>

<script setup>
import { useQuasar } from 'quasar'
import PerfilContenido from '@/pages/Perfil/PerfilContenido.vue'

const $q = useQuasar()
</script>
