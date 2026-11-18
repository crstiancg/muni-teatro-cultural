<template>
  <div>
    <template v-if="persona?.codigo_registro">
      <div class="row items-center q-gutter-sm q-mb-sm">
        <span class="codigo">{{ persona.codigo_registro }}</span>
        <q-chip dense square :color="estado.color" class="text-weight-bold">{{ estado.label }}</q-chip>
      </div>
      <p class="text-caption text-grey-7 q-mb-md">
        {{ estado.ayuda }}
      </p>
      <q-btn unelevated no-caps color="primary" label="Ver ficha" :loading="cargando" @click="abrir">
        <FileText :size="16" class="q-ml-xs" />
      </q-btn>
    </template>

    <p v-else class="text-body2 text-grey-7 q-mb-none">
      {{
        personaId
          ? 'Se genera al aprobar su perfil por primera vez, con el siguiente código correlativo.'
          : 'Tu ficha de registro y tu código (CPC0001, CPC0002...) se generan cuando se apruebe tu perfil.'
      }}
    </p>

    <!-- vista previa: se ve la ficha tal cual antes de bajarla o imprimirla -->
    <q-dialog v-model="abierto" :maximized="$q.screen.lt.md" @hide="liberar">
      <q-card class="visor column no-wrap" :class="{ completo: $q.screen.lt.md }">
        <q-card-section class="row items-center q-py-sm q-px-md barra">
          <div class="col">
            <div class="text-subtitle1 text-weight-bold">Ficha de registro</div>
            <div class="text-caption text-grey-7">{{ persona?.codigo_registro }}</div>
          </div>
          <q-btn flat no-caps label="Imprimir" class="q-mr-xs" @click="imprimir">
            <Printer :size="16" class="q-ml-xs" />
          </q-btn>
          <q-btn unelevated no-caps color="primary" label="Descargar" class="q-mr-sm" @click="descargar">
            <FileDown :size="16" class="q-ml-xs" />
          </q-btn>
          <q-btn flat round dense v-close-popup aria-label="Cerrar"><X :size="18" /></q-btn>
        </q-card-section>

        <q-separator />

        <div class="col hoja-fondo">
          <div class="hoja">
            <VuePdfEmbed v-if="url" :source="fuentePreview" :page="1" @rendered="renderizando = false" />
            <div v-if="renderizando" class="absolute-center"><q-spinner size="32px" /></div>
          </div>
        </div>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'
import { useQuasar } from 'quasar'
import { FileText, FileDown, Printer, X } from 'lucide-vue-next'
import VuePdfEmbed from 'vue-pdf-embed'
import { fuentePdf } from '@/utils/pdf'
import FichaRegistroService from '@/services/FichaRegistroService'

// personaId: vista del admin (la de esa persona). Sin él: la propia.
const props = defineProps({
  persona: { type: Object, default: null },
  personaId: { type: [Number, String], default: null },
})

const $q = useQuasar()
const cargando = ref(false)
const renderizando = ref(false)
const abierto = ref(false)
const blob = ref(null)
const url = ref(null)

const nombreArchivo = computed(() => `ficha-registro-${props.persona?.codigo_registro}.pdf`)

// mismo criterio que el backend (Persona::registroVigente): vigente = hoy aprobado
const estado = computed(() => {
  if (props.persona?.estado === 'aprobado') {
    return { label: 'Vigente', color: 'positive', ayuda: 'Inscripción vigente en el Registro de Agentes Culturales.' }
  }
  if (props.persona?.estado === 'pendiente') {
    return { label: 'En revisión', color: 'warning', ayuda: 'El código se conserva; la ficha figura en revisión hasta que se resuelva.' }
  }
  return { label: 'No vigente', color: 'negative', ayuda: 'El código se conserva, pero la ficha figura como no vigente hasta una nueva aprobación.' }
})

// se pide cada vez que se abre: la ficha refleja el estado y los datos de hoy
async function abrir() {
  cargando.value = true
  try {
    blob.value = await FichaRegistroService.obtener(props.personaId)
    url.value = URL.createObjectURL(blob.value)
    renderizando.value = true
    abierto.value = true
  } catch (error) {
    // 401/403 ya los avisa el interceptor de axios
    if (![401, 403].includes(error.response?.status)) {
      $q.notify({ type: 'negative', message: 'No se pudo generar la ficha. Intenta de nuevo.' })
    }
  } finally {
    cargando.value = false
  }
}

function descargar() {
  FichaRegistroService.guardar(blob.value, nombreArchivo.value)
}

// el visor de PDF del navegador ya trae imprimir: se abre ahí y se lanza el diálogo
function imprimir() {
  const ventana = window.open(url.value, '_blank')
  if (!ventana) {
    $q.notify({ type: 'warning', message: 'El navegador bloqueó la ventana. Usa "Descargar" e imprime el PDF.' })
  }
}

function liberar() {
  if (url.value) URL.revokeObjectURL(url.value)
  url.value = null
  blob.value = null
}

onBeforeUnmount(liberar)

// con wasmUrl: sin él, los PDF escaneados salen en blanco (ver src/utils/pdf.js)
const fuentePreview = computed(() => fuentePdf(url.value))
</script>

<style scoped>
.codigo {
  font-family: ui-monospace, 'SFMono-Regular', Consolas, monospace;
  font-size: 1.35rem;
  font-weight: 700;
  letter-spacing: 0.06em;
}

.visor {
  width: min(900px, 96vw);
  max-width: none;
  height: 92vh;
}

.visor.completo {
  width: 100vw;
  height: 100%;
}

.barra {
  flex-shrink: 0;
}

/* la hoja sobre gris, como un visor de documentos */
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

@media (max-width: 599px) {
  .hoja-fondo {
    padding: 8px;
  }
}
</style>
