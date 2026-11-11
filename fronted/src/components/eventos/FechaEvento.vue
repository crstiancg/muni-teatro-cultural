<template>
  <!-- fecha como hoja de agenda: "SÁB 14 NOV", "HOY 10 NOV" o "SÁB 5–7 DIC" -->
  <div class="fecha" :class="{ 'fecha--hoy': esHoy, 'fecha--chica': chica }">
    <span class="fecha-semana">{{ esHoy ? 'Hoy' : hoja.diaSemana.slice(0, 3) }}</span>
    <span class="fecha-dia">{{ diaTexto }}</span>
    <span class="fecha-mes">{{ hoja.mes }}</span>
    <span v-if="hasta" class="fecha-hasta">al {{ hasta }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { hojaCalendario, esDeVariosDias, diaDe, desdeTexto, hoy } from '@/utils/fechasAgenda'

const props = defineProps({
  evento: { type: Object, required: true },
  chica: { type: Boolean, default: false },
})

const hoja = computed(() => hojaCalendario(props.evento))
const esHoy = computed(() => diaDe(props.evento.fecha_inicio) === hoy())
const fin = computed(() =>
  esDeVariosDias(props.evento) ? desdeTexto(diaDe(props.evento.fecha_fin)) : null,
)
const mismoMes = computed(
  () =>
    fin.value && fin.value.getMonth() === desdeTexto(diaDe(props.evento.fecha_inicio)).getMonth(),
)
// mismo mes: "5–7 DIC". Si cruza de mes, "30 NOV" + "al 31 dic" ("30–31 NOV" sería falso)
const diaTexto = computed(() =>
  mismoMes.value ? `${hoja.value.dia}–${fin.value.getDate()}` : hoja.value.dia,
)
const fmtHasta = new Intl.DateTimeFormat('es-PE', { day: 'numeric', month: 'short' })
const hasta = computed(() =>
  fin.value && !mismoMes.value ? fmtHasta.format(fin.value).replace('.', '') : null,
)
</script>

<style scoped>
/* --azul lo define el contenedor (cambia con el modo oscuro) */
.fecha {
  display: flex;
  flex-direction: column;
  align-items: center;
  line-height: 1.1;
}

.fecha-semana,
.fecha-mes {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  opacity: 0.6;
}

.fecha-dia {
  font-size: 1.7rem;
  font-weight: 800;
  white-space: nowrap;
}

.fecha-hasta {
  margin-top: 2px;
  font-size: 0.65rem;
  font-weight: 600;
  white-space: nowrap;
  opacity: 0.6;
}

.fecha--chica .fecha-dia {
  font-size: 1.4rem;
}

.fecha--hoy .fecha-semana,
.fecha--hoy .fecha-dia {
  color: var(--azul, var(--q-primary));
  opacity: 1;
}

@media (max-width: 599px) {
  .fecha-dia {
    font-size: 1.4rem;
  }
}
</style>
