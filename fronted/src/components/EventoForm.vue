<template>
  <q-card :style="{ width: '100%', maxWidth: $q.screen.gt.sm ? '760px' : '100vw' }">
    <q-card-section class="row items-center">
      <div class="text-h6">
        {{ props.item ? 'Editar evento' : esArtista ? 'Proponer evento' : 'Nuevo evento' }}
      </div>
      <q-space />
      <q-btn v-close-popup flat round dense size="sm" style="border-radius: 100% !important">
        <X size="16" />
      </q-btn>
    </q-card-section>
    <q-separator />

    <q-form @submit.prevent="submit">
      <q-card-section>
        <!-- el artista tiene que saber ANTES de guardar que esto pasa por revisión -->
        <div
          v-if="esArtista"
          class="aviso"
          :class="{ 'aviso--observado': props.item?.estado === 'observado' }"
        >
          <template v-if="props.item?.estado === 'observado'">
            <strong>El administrador lo observó:</strong> {{ props.item.observacion }}
            <div>Corrígelo y guarda: se vuelve a enviar a revisión.</div>
          </template>
          <template v-else-if="props.item?.estado === 'aprobado'">
            Este evento ya está publicado. Si lo modificas, <strong>vuelve a revisión</strong> y
            deja de verse en la agenda hasta que el administrador lo apruebe de nuevo.
          </template>
          <template v-else>
            El administrador revisa cada evento antes de publicarlo en la agenda cultural. Te
            avisaremos cuando lo apruebe u observe.
          </template>
        </div>

        <div class="row q-col-gutter-sm">
          <!-- solo al crear: después el evento sigue siendo de quien lo propuso -->
          <div v-if="esArtista && !props.item && props.agrupaciones.length" class="col-12">
            <q-select
              dense
              outlined
              emit-value
              map-options
              v-model="form.evento.agrupacion_id"
              :options="opcionesOrganizador"
              label="Organiza"
              :error="form.invalid('evento.agrupacion_id')"
              :error-message="form.errors['evento.agrupacion_id']"
            />
          </div>

          <div class="col-12">
            <q-input
              dense
              outlined
              v-model="form.evento.titulo"
              label="Título *"
              maxlength="150"
              counter
              @change="form.validate('evento.titulo')"
              :error="form.invalid('evento.titulo')"
              :error-message="form.errors['evento.titulo']"
            />
          </div>

          <!-- datetime-local: "hora de pared" de la ciudad, se guarda tal cual -->
          <div class="col-12 col-md-6">
            <q-input
              dense
              outlined
              stack-label
              type="datetime-local"
              v-model="form.evento.fecha_inicio"
              label="Inicio *"
              :error="form.invalid('evento.fecha_inicio')"
              :error-message="form.errors['evento.fecha_inicio']"
            />
          </div>
          <div class="col-12 col-md-6">
            <q-input
              dense
              outlined
              stack-label
              type="datetime-local"
              v-model="form.evento.fecha_fin"
              label="Fin (opcional, p. ej. festivales de varios días)"
              :min="form.evento.fecha_inicio || undefined"
              :error="form.invalid('evento.fecha_fin')"
              :error-message="form.errors['evento.fecha_fin']"
            />
          </div>

          <div class="col-12">
            <q-option-group
              v-model="form.evento.modalidad"
              :options="opcionesModalidad"
              type="radio"
              inline
              dense
            />
          </div>

          <template v-if="form.evento.modalidad === 'presencial'">
            <div class="col-12 col-md-6">
              <q-input
                dense
                outlined
                v-model="form.evento.lugar"
                label="Lugar * (p. ej. Teatro Municipal)"
                maxlength="150"
                :error="form.invalid('evento.lugar')"
                :error-message="form.errors['evento.lugar']"
              />
            </div>
            <div class="col-12 col-md-6">
              <q-input
                dense
                outlined
                v-model="form.evento.direccion"
                label="Dirección"
                maxlength="255"
                :error="form.invalid('evento.direccion')"
                :error-message="form.errors['evento.direccion']"
              />
            </div>
          </template>

          <div class="col-12">
            <q-input
              dense
              outlined
              v-model="form.evento.enlace"
              type="url"
              :label="
                form.evento.modalidad === 'virtual'
                  ? 'Enlace de la transmisión *'
                  : 'Enlace con más información o entradas (opcional)'
              "
              :error="form.invalid('evento.enlace')"
              :error-message="form.errors['evento.enlace']"
            />
          </div>

          <div class="col-12">
            <div class="text-caption text-grey-7 q-mb-xs">
              {{
                esArtista
                  ? 'Disciplina (si no eliges, se usa tu comisión)'
                  : 'Disciplina (opcional)'
              }}
            </div>
            <ComisionCascadeSelect v-model="form.evento.codigo_comision" />
            <div v-if="form.invalid('evento.codigo_comision')" class="text-negative text-caption">
              {{ form.errors['evento.codigo_comision'] }}
            </div>
          </div>

          <div class="col-12 col-md-6">
            <q-toggle
              v-model="form.evento.flag_gratuito"
              :true-value="1"
              :false-value="0"
              label="Ingreso libre"
            />
          </div>
          <div v-if="!form.evento.flag_gratuito" class="col-12 col-md-6">
            <q-input
              dense
              outlined
              type="number"
              min="0"
              step="0.50"
              prefix="S/"
              v-model="form.evento.costo"
              label="Costo *"
              :error="form.invalid('evento.costo')"
              :error-message="form.errors['evento.costo']"
            />
          </div>

          <div class="col-12">
            <div class="text-caption text-grey-7 q-mb-xs">Descripción</div>
            <q-editor
              v-model="form.evento.descripcion"
              min-height="7rem"
              placeholder="De qué se trata, quiénes participan, recomendaciones..."
              :toolbar="[
                ['bold', 'italic', 'underline'],
                ['unordered', 'ordered'],
                ['link'],
                ['removeFormat', 'undo', 'redo'],
              ]"
            />
            <div
              v-if="form.invalid('evento.descripcion')"
              class="text-negative text-caption q-mt-xs"
            >
              {{ form.errors['evento.descripcion'] }}
            </div>
          </div>

          <div class="col-12">
            <q-file
              dense
              outlined
              v-model="form.evento.imagen"
              label="Afiche o imagen (JPG o PNG, máx. 5MB)"
              accept=".jpg,.jpeg,.png"
              :error="form.invalid('evento.imagen')"
              :error-message="form.errors['evento.imagen']"
              clearable
            >
              <template v-slot:prepend>
                <ImageIcon size="18" />
              </template>
            </q-file>
            <div v-if="previewUrl" class="preview-box">
              <img :src="previewUrl" class="preview-img" />
            </div>
            <div v-else-if="props.item?.imagen_nombre_original" class="text-caption text-grey-7">
              Ya tiene una imagen: {{ props.item.imagen_nombre_original }}. Si no seleccionas una
              nueva, se conserva la actual.
            </div>
          </div>

          <div v-if="!esArtista" class="col-12">
            <q-checkbox
              v-model="form.evento.flag_destacado"
              :true-value="1"
              :false-value="0"
              label="Destacar en el portal"
            />
          </div>
        </div>
      </q-card-section>

      <q-separator />
      <q-card-actions align="right">
        <q-btn label="Cerrar" flat v-close-popup />
        <q-btn
          :label="esArtista ? 'Enviar a revisión' : 'Guardar'"
          color="positive"
          outline
          no-caps
          type="submit"
          :loading="form.processing"
        />
      </q-card-actions>
    </q-form>
  </q-card>
</template>

<script setup>
import { computed, ref, watch, onBeforeUnmount } from 'vue'
import { useForm } from 'laravel-precognition-vue'
import { X, Image as ImageIcon } from 'lucide-vue-next'
import ComisionCascadeSelect from '@/components/ComisionCascadeSelect.vue'

const props = defineProps({
  item: { type: Object, default: null },
  // 'admin' (eventos de la municipalidad, se publican directo) o
  // 'artista' (Mis eventos: entra pendiente y lo revisa el admin)
  modo: { type: String, default: 'admin' },
  // modo artista: las agrupaciones aprobadas que representa [{ id, nombre }]
  agrupaciones: { type: Array, default: () => [] },
})
const emit = defineEmits(['save'])

const esArtista = props.modo === 'artista'

const opcionesOrganizador = computed(() => [
  { label: 'Yo (a mi nombre)', value: null },
  ...props.agrupaciones.map((a) => ({ label: `Agrupación: ${a.nombre}`, value: a.id })),
])

const opcionesModalidad = [
  { label: 'Presencial', value: 'presencial' },
  { label: 'Virtual', value: 'virtual' },
]

// el backend devuelve "YYYY-MM-DD HH:mm"; el input datetime-local usa "YYYY-MM-DDTHH:mm"
const aInput = (valor) => (valor ? valor.replace(' ', 'T') : '')

// alta y edición por POST: PHP no llena $_FILES en un PUT multipart
const base = esArtista ? 'api/mis-eventos' : 'api/eventos'
const url = props.item ? `${base}/${props.item.id}` : base

const form = useForm('post', url, {
  evento: {
    titulo: props.item?.titulo ?? '',
    descripcion: props.item?.descripcion ?? '',
    fecha_inicio: aInput(props.item?.fecha_inicio),
    fecha_fin: aInput(props.item?.fecha_fin),
    modalidad: props.item?.modalidad ?? 'presencial',
    lugar: props.item?.lugar ?? '',
    direccion: props.item?.direccion ?? '',
    enlace: props.item?.enlace ?? '',
    codigo_comision: props.item?.codigo_comision ?? null,
    flag_gratuito: props.item ? (props.item.flag_gratuito ? 1 : 0) : 1,
    costo: props.item?.costo ?? '',
    flag_destacado: props.item?.flag_destacado ? 1 : 0,
    imagen: null,
    agrupacion_id: null,
  },
})

const submit = () => {
  form
    .submit()
    .then((response) => emit('save', response.data))
    .catch(() => {})
}

// vista previa: imagen nueva desde un object URL, o la que ya tenía guardada
const objectUrl = ref(null)

watch(
  () => form.evento.imagen,
  (imagen) => {
    if (objectUrl.value) {
      URL.revokeObjectURL(objectUrl.value)
      objectUrl.value = null
    }
    if (imagen instanceof File) {
      objectUrl.value = URL.createObjectURL(imagen)
    }
  },
)

onBeforeUnmount(() => {
  if (objectUrl.value) URL.revokeObjectURL(objectUrl.value)
})

const previewUrl = computed(
  () => objectUrl.value || (!form.evento.imagen ? props.item?.imagen_url : null),
)
</script>

<style scoped>
.aviso {
  margin-bottom: 12px;
  padding: 10px 12px;
  border-radius: 10px;
  font-size: 0.85rem;
  background: var(--panel-superficie-honda);
}

.aviso--observado {
  background: var(--panel-acento-suave);
}

.preview-box {
  margin-top: 8px;
  border-radius: 10px;
  overflow: hidden;
  max-height: 260px;
  display: flex;
  justify-content: center;
  background: #f5f5f5;
}

.preview-img {
  max-width: 100%;
  max-height: 260px;
  object-fit: contain;
  display: block;
}
</style>
