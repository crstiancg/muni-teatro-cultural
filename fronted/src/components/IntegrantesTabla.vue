<template>
  <div v-if="integrantes.length">
    <!-- resumen: cuántos hay y cuántos son agentes culturales registrados -->
    <div class="row q-gutter-sm q-mb-md">
      <q-chip dense square outline>
        <Users :size="14" class="q-mr-xs" /> {{ integrantes.length }} integrantes
      </q-chip>
      <q-chip v-if="registrados" dense square outline color="positive">
        <BadgeCheck :size="14" class="q-mr-xs" />
        {{ registrados }} {{ registrados === 1 ? 'agente cultural registrado' : 'agentes culturales registrados' }}
      </q-chip>
    </div>

    <div class="grilla">
      <div
        v-for="i in ordenados"
        :key="i.id"
        class="integrante"
        :class="{ 'integrante--representante': i.es_representante }"
      >
        <q-avatar size="52px" :style="{ background: colorDe(i), color: 'white' }" class="avatar">
          <img v-if="i.persona?.foto?.miniatura_url" :src="i.persona.foto.miniatura_url" :alt="i.nombre_completo" />
          <template v-else>{{ iniciales(i) }}</template>
        </q-avatar>

        <div class="cuerpo">
          <!-- agente cultural registrado: lleva a su CV en el panel -->
          <router-link
            v-if="enlazarCv && i.persona_id && userStore.hasPermission('admin-personas-index')"
            :to="{ name: 'PersonaDetalle', params: { id: i.persona_id } }"
            class="nombre nombre--link"
          >
            {{ i.nombre_completo }}
            <q-tooltip>Ver su currículum</q-tooltip>
          </router-link>
          <div v-else class="nombre">{{ i.nombre_completo }}</div>

          <div class="rol">{{ i.rol || 'Integrante' }}</div>

          <div class="pie">
            <!-- el DNI solo se ve en el panel (representante y admin), nunca en el portal -->
            <span class="dni">DNI {{ i.dni }}</span>
            <q-badge v-if="i.es_representante" color="primary">Representante</q-badge>
            <q-badge v-if="i.persona_id" color="positive" outline>
              <BadgeCheck :size="11" class="q-mr-xs" /> Registrado
            </q-badge>
          </div>
        </div>

        <div v-if="editable || puedeTransferir(i)" class="acciones">
          <q-btn
            v-if="puedeTransferir(i)"
            flat
            dense
            round
            size="sm"
            color="primary"
            @click="emit('transferir', i)"
          >
            <Crown :size="15" />
            <q-tooltip>Hacer representante</q-tooltip>
          </q-btn>
          <q-btn v-if="editable" flat dense round size="sm" icon="edit" color="primary" @click="emit('editar', i)">
            <q-tooltip>Editar</q-tooltip>
          </q-btn>
          <q-btn
            v-if="editable && !i.es_representante"
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
      </div>
    </div>
  </div>

  <div v-else class="vacio">
    <Users :size="28" />
    <div>Todavía no hay integrantes.</div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Users, BadgeCheck, Crown } from 'lucide-vue-next'
import { useUserStore } from '@/stores/user-store'

const props = defineProps({
  integrantes: { type: Array, default: () => [] },
  // representante: puede editar y quitar; admin: solo lectura
  editable: { type: Boolean, default: false },
  // admin: el nombre de un agente cultural registrado lleva a su CV
  enlazarCv: { type: Boolean, default: false },
  // muestra "Hacer representante" en los artistas registrados que no lo son
  transferible: { type: Boolean, default: false },
})
const emit = defineEmits(['editar', 'quitar', 'transferir'])

// solo un artista registrado puede ser representante: necesita cuenta para gestionarla
const puedeTransferir = (i) => props.transferible && i.persona_id && !i.es_representante
const userStore = useUserStore()

// representante primero, después registrados, después el resto por apellido
const ordenados = computed(() =>
  [...props.integrantes].sort(
    (a, b) =>
      Number(b.es_representante) - Number(a.es_representante) ||
      Number(!!b.persona_id) - Number(!!a.persona_id) ||
      (a.apellido_paterno || '').localeCompare(b.apellido_paterno || ''),
  ),
)

const registrados = computed(() => props.integrantes.filter((i) => i.persona_id).length)

const iniciales = (i) => `${i.nombre?.charAt(0) || ''}${i.apellido_paterno?.charAt(0) || ''}`.toUpperCase()

// un tono estable por persona (mismo DNI = mismo color), dentro de la familia de la marca
const TONOS = ['#004173', '#2a6fb0', '#0b6a73', '#1f7a5a', '#6a4c93', '#9a4a16']
const colorDe = (i) => TONOS.at(Number(String(i.dni).slice(-2)) % TONOS.length)
</script>

<style scoped>
.grilla {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 12px;
}

.integrante {
  position: relative;
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 14px;
  border: 1px solid rgba(128, 128, 128, 0.22);
  border-radius: 12px;
  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease;
}

.integrante:hover {
  border-color: rgba(128, 128, 128, 0.4);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
}

.integrante--representante {
  border: 2px solid var(--q-primary);
  background: rgba(0, 65, 115, 0.04);
}

.avatar {
  flex: none;
  font-weight: 700;
}

.avatar img {
  object-fit: cover;
}

.cuerpo {
  flex: 1;
  min-width: 0;
}

.nombre {
  font-weight: 600;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.nombre--link {
  display: inline-block;
  color: var(--q-primary);
  text-decoration: none;
}

.nombre--link:hover {
  text-decoration: underline;
}

.rol {
  margin-top: 2px;
  font-size: 0.85rem;
  font-weight: 500;
  opacity: 0.8;
  text-transform: capitalize;
}

.pie {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
}

.dni {
  font-size: 0.75rem;
  opacity: 0.6;
}

.acciones {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.vacio {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 32px 16px;
  opacity: 0.6;
}
</style>
