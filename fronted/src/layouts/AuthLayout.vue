<template>
  <q-layout>
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { useClasePanel } from '@/composables/useClasePanel'

// el login es la puerta del panel: usa su misma identidad (carbón, hueso y rojo)
useClasePanel()
</script>
