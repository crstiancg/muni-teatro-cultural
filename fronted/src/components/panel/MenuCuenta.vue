<template>
  <q-btn flat round dense class="cuenta" :aria-label="`Cuenta de ${nombre}`">
    <span class="avatar">{{ inicial }}</span>

    <q-menu anchor="bottom right" self="top right" :offset="[0, 8]" class="menu-cuenta">
      <div class="quien">
        <span class="avatar avatar--grande">{{ inicial }}</span>
        <div class="quien-texto">
          <div class="quien-nombre">{{ nombre }}</div>
          <div class="quien-dato">{{ correo }}</div>
          <div v-if="rol" class="quien-rol">{{ rol }}</div>
        </div>
      </div>

      <q-list dense class="opciones">
        <q-item v-close-popup clickable :to="{ name: 'Perfil' }">
          <q-item-section avatar><UserRound :size="17" /></q-item-section>
          <q-item-section>Mi perfil</q-item-section>
        </q-item>

        <!-- el tema se cambia acá; se recuerda en este navegador -->
        <q-item clickable @click="emit('cambiar-tema')">
          <q-item-section avatar>
            <component :is="oscuro ? Sun : Moon" :size="17" />
          </q-item-section>
          <q-item-section>{{ oscuro ? 'Tema claro' : 'Tema oscuro' }}</q-item-section>
        </q-item>

        <q-separator class="q-my-xs" />

        <q-item v-close-popup clickable class="salir" @click="emit('salir')">
          <q-item-section avatar><LogOut :size="17" /></q-item-section>
          <q-item-section>Cerrar sesión</q-item-section>
        </q-item>
      </q-list>
    </q-menu>
  </q-btn>
</template>

<script setup>
import { computed } from 'vue'
import { UserRound, Sun, Moon, LogOut } from 'lucide-vue-next'

const props = defineProps({
  nombre: { type: String, default: 'Usuario' },
  correo: { type: String, default: '' },
  rol: { type: String, default: '' },
  oscuro: { type: Boolean, default: false },
})
const emit = defineEmits(['cambiar-tema', 'salir'])

const inicial = computed(() => props.nombre?.trim().charAt(0).toUpperCase() || '?')
</script>

<style scoped>
.avatar {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--panel-primario);
  color: var(--panel-sobre-primario);
  font-weight: 700;
  font-size: 0.85rem;
}

.avatar--grande {
  width: 40px;
  height: 40px;
  font-size: 1rem;
  flex-shrink: 0;
}
</style>

<!-- el q-menu se monta fuera del componente (teleport): estilos sin scope -->
<style>
.menu-cuenta {
  width: 260px;
  max-width: 92vw;
  border: 1px solid var(--panel-borde);
}

.menu-cuenta .quien {
  display: flex;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--panel-borde);
}

.menu-cuenta .quien-texto {
  min-width: 0;
}

.menu-cuenta .quien-nombre {
  font-weight: 700;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.menu-cuenta .quien-dato {
  font-size: 0.8rem;
  color: var(--panel-texto-suave);
  overflow-wrap: anywhere;
}

.menu-cuenta .quien-rol {
  display: inline-block;
  margin-top: 6px;
  padding: 1px 8px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  background: var(--panel-superficie-honda);
  color: var(--panel-texto);
}

.menu-cuenta .opciones {
  padding: 6px 0;
}

.menu-cuenta .q-item__section--avatar {
  min-width: 32px;
  color: var(--panel-texto-suave);
}

/* cerrar sesión: neutro; rojo recién al pasar el mouse */
.menu-cuenta .salir:hover,
.menu-cuenta .salir:hover .q-item__section--avatar {
  color: var(--panel-acento);
}
</style>
