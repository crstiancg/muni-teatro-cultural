<template>
  <!-- visor de PDF dentro del sitio: en Android un PDF en iframe no se ve,
       así que se dibuja con pdf.js (vue-pdf-embed), todas las páginas -->
  <q-dialog v-model="abierto" :maximized="$q.screen.lt.md" @hide="emit('cerrar')">
    <q-card class="visor column no-wrap" :class="{ completo: $q.screen.lt.md }">
      <q-card-section class="row items-center no-wrap q-py-sm q-px-md barra">
        <div class="col ellipsis">
          <div class="text-subtitle1 text-weight-bold ellipsis">{{ documento?.titulo }}</div>
          <div v-if="documento?.fecha" class="text-caption text-grey-7">{{ fechaLarga(documento.fecha) }}</div>
        </div>
        <q-btn
          flat
          no-caps
          :href="documento ? DocumentoService.url(documento.slug, { descargar: true }) : undefined"
          class="q-mr-xs gt-xs"
          label="Descargar"
        >
          <Download :size="16" class="q-ml-xs" />
        </q-btn>
        <q-btn flat round dense v-close-popup aria-label="Cerrar"><X :size="18" /></q-btn>
      </q-card-section>

      <q-separator />

      <div class="col hoja-fondo">
        <div v-if="error" class="aviso">
          <p>No se pudo mostrar el documento en este momento.</p>
          <a :href="documento?.url_original" target="_blank" rel="noopener">Abrirlo en el portal oficial</a>
        </div>
        <div v-else class="hoja">
          <VuePdfEmbed
            v-if="documento"
            :source="fuente"
            @rendered="cargando = false"
            @loading-failed="fallo"
            @rendering-failed="fallo"
          />
          <div v-if="cargando" class="cargando"><q-spinner size="32px" /></div>
        </div>
      </div>

      <q-separator />
      <div class="pie row items-center justify-between q-px-md q-py-xs">
        <span class="text-caption text-grey-7">Fuente: portal oficial de la Municipalidad Provincial de Puno</span>
        <a :href="documento?.url_original" target="_blank" rel="noopener" class="text-caption">
          Ver original <ExternalLink :size="12" />
        </a>
      </div>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Download, ExternalLink, X } from 'lucide-vue-next'
import VuePdfEmbed from 'vue-pdf-embed'
import DocumentoService from '@/services/DocumentoService'
import { fuentePdf } from '@/utils/pdf'

// documento: { slug, titulo, fecha, url_original } o null (cerrado)
const props = defineProps({ documento: { type: Object, default: null } })
const emit = defineEmits(['cerrar'])

const abierto = ref(false)
const cargando = ref(true)
const error = ref(false)
// computed: un objeto nuevo en cada render haría que el visor recargue el PDF
const fuente = computed(() => (props.documento ? fuentePdf(DocumentoService.url(props.documento.slug)) : null))

watch(
  () => props.documento,
  (doc) => {
    abierto.value = !!doc
    cargando.value = true
    error.value = false
  },
)

function fallo() {
  cargando.value = false
  error.value = true
}

// "2024-03-12" es una fecha sin hora: new Date() la leería como UTC (día anterior en Perú)
function fechaLarga(iso) {
  const [a, m, d] = iso.split('-').map(Number)
  return new Date(a, m - 1, d).toLocaleDateString('es-PE', { dateStyle: 'long' })
}
</script>

<style scoped>
.visor {
  width: min(900px, 96vw);
  max-width: none;
  height: 92vh;
}

.visor.completo {
  width: 100vw;
  height: 100%;
}

.barra,
.pie {
  flex-shrink: 0;
}

.hoja-fondo {
  overflow-y: auto;
  background: #e6e8ea;
  padding: 20px 16px;
}

.hoja {
  position: relative;
  max-width: 760px;
  min-height: 300px;
  margin: 0 auto;
  background: #fff;
  box-shadow: 0 4px 18px rgb(0 0 0 / 18%);
}

/* separación entre páginas del PDF */
.hoja :deep(.vue-pdf-embed > div) {
  margin-bottom: 8px;
}

.cargando {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
}

.aviso {
  max-width: 420px;
  margin: 60px auto;
  text-align: center;
  color: #4a5560;
}

.pie a {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

@media (max-width: 599px) {
  .hoja-fondo {
    padding: 8px;
  }
}
</style>
