<template>
  <q-layout>
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { useClasePanel } from '@/composables/useClasePanel'

// el login es la puerta del panel: usa su identidad (con el tema por defecto)
useClasePanel()
</script>
