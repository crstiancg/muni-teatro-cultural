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

      <!-- apariencia: se guarda en la cuenta y se aplica al instante -->
      <div class="apariencia">
        <div class="grupo">
          <span class="grupo-titulo">Modo</span>
          <div class="segmento" role="group" aria-label="Modo">
            <button
              v-for="m in MODOS"
              :key="m.valor"
              type="button"
              class="segmento-opcion"
              :aria-pressed="tema.modo === m.valor"
              @click="elegir('modo', m.valor)"
            >
              <component :is="m.icono" :size="15" /> {{ m.label }}
            </button>
          </div>
        </div>

        <div class="grupo">
          <span class="grupo-titulo">Color</span>
          <div class="muestras" role="group" aria-label="Color de acento">
            <button
              v-for="a in ACENTOS"
              :key="a.valor"
              type="button"
              class="muestra"
              :style="{ '--muestra': a.muestra }"
              :aria-label="a.label"
              :aria-pressed="tema.acento === a.valor"
              :title="a.label"
              @click="elegir('acento', a.valor)"
            >
              <Check v-if="tema.acento === a.valor" :size="14" :stroke-width="3" />
            </button>
          </div>
        </div>

        <div class="grupo">
          <span class="grupo-titulo">Neutro</span>
          <div class="segmento" role="group" aria-label="Tono de los grises">
            <button
              v-for="n in NEUTROS"
              :key="n.valor"
              type="button"
              class="segmento-opcion"
              :aria-pressed="tema.neutro === n.valor"
              @click="elegir('neutro', n.valor)"
            >
              <span class="punto-neutro" :style="{ background: n.muestra }" /> {{ n.label }}
            </button>
          </div>
        </div>
      </div>

      <q-list dense class="opciones">
        <q-item v-close-popup clickable :to="{ name: 'Perfil' }">
          <q-item-section avatar><UserRound :size="17" /></q-item-section>
          <q-item-section>Mi perfil</q-item-section>
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
import { UserRound, Sun, Moon, LogOut, Check } from 'lucide-vue-next'
import { ACENTOS, NEUTROS, TEMA_POR_DEFECTO } from '@/composables/useTemaPanel'

const props = defineProps({
  nombre: { type: String, default: 'Usuario' },
  correo: { type: String, default: '' },
  rol: { type: String, default: '' },
  // { acento, neutro, modo }
  tema: { type: Object, default: () => TEMA_POR_DEFECTO },
})
// cambiar-tema: cambio parcial, p. ej. { acento: 'azul' }
const emit = defineEmits(['cambiar-tema', 'salir'])

const MODOS = [
  { valor: 'claro', label: 'Claro', icono: Sun },
  { valor: 'oscuro', label: 'Oscuro', icono: Moon },
]

const inicial = computed(() => props.nombre?.trim().charAt(0).toUpperCase() || '?')

function elegir(clave, valor) {
  if (props.tema[clave] !== valor) emit('cambiar-tema', { [clave]: valor })
}
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
  width: 280px;
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

.menu-cuenta .apariencia {
  display: grid;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--panel-borde);
}

.menu-cuenta .grupo {
  display: grid;
  gap: 6px;
}

.menu-cuenta .grupo-titulo {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--panel-texto-suave);
}

.menu-cuenta .segmento {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4px;
  padding: 3px;
  border-radius: 10px;
  background: var(--panel-superficie-honda);
}

.menu-cuenta .segmento-opcion {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 6px 8px;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: var(--panel-texto-suave);
  font: inherit;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}

.menu-cuenta .segmento-opcion[aria-pressed='true'] {
  background: var(--panel-superficie);
  color: var(--panel-texto);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
}

.menu-cuenta .muestras {
  display: flex;
  gap: 8px;
}

.menu-cuenta .muestra {
  display: grid;
  place-items: center;
  width: 28px;
  height: 28px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: var(--muestra);
  color: #ffffff;
  cursor: pointer;
  transition: transform 0.12s;
}

.menu-cuenta .muestra:hover {
  transform: scale(1.08);
}

.menu-cuenta .muestra[aria-pressed='true'] {
  box-shadow:
    0 0 0 2px var(--panel-superficie),
    0 0 0 4px var(--muestra);
}

.menu-cuenta .punto-neutro {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 1px solid var(--panel-borde);
}

.menu-cuenta :is(.segmento-opcion, .muestra):focus-visible {
  outline: 2px solid var(--panel-acento);
  outline-offset: 2px;
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
