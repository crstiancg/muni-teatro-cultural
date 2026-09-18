<template>
  <q-card-section v-if="cargando" class="text-center q-pa-lg">
    <q-spinner color="primary" size="40px" />
  </q-card-section>

  <template v-else>
    <q-card-section class="text-center q-pb-none">
      <q-avatar size="72px" color="primary" text-color="white" class="text-h5">
        {{ inicial }}
      </q-avatar>
      <div class="text-h6 q-mt-sm">{{ nombreCompleto || formUsuario.usuario.name }}</div>
    </q-card-section>

      <q-card-section>
        <div class="text-subtitle1 text-weight-bold q-mb-xs">Información de Usuario</div>
        <div class="text-caption text-grey-6 q-mb-sm">
          Podés actualizar tu nombre de usuario y correo electrónico. Si cambiás el correo, vas a tener que volver a
          iniciar sesión con el nuevo.
        </div>
  
        <q-form @submit.prevent="submitUsuario" class="row q-col-gutter-sm items-start">
          <div class="col-12 col-sm-6">
            <q-input
              dense
              outlined
              v-model="formUsuario.usuario.name"
              label="Nombre de Usuario *"
              @change="formUsuario.validate('usuario.name')"
              :error="formUsuario.invalid('usuario.name')"
              :class="formUsuario.invalid('usuario.name') ? 'q-mb-sm' : ''"
            >
              <template v-slot:error>
                <div>{{ formUsuario.errors['usuario.name'] }}</div>
              </template>
            </q-input>
          </div>
          <div class="col-12 col-sm-6">
            <q-input
              dense
              outlined
              v-model="formUsuario.usuario.email"
              type="email"
              label="Email *"
              @change="formUsuario.validate('usuario.email')"
              :error="formUsuario.invalid('usuario.email')"
              :class="formUsuario.invalid('usuario.email') ? 'q-mb-sm' : ''"
            >
              <template v-slot:error>
                <div>{{ formUsuario.errors['usuario.email'] }}</div>
              </template>
            </q-input>
          </div>
          <div class="col-12" align="right">
            <q-btn
              unelevated
              no-caps
              color="primary"
              label="Guardar Cambios"
              type="submit"
              :loading="formUsuario.processing"
            />
          </div>
        </q-form>
      </q-card-section>

    <template v-if="mostrarPassword">
    <q-separator />
    <q-card-section class="q-pa-md">
      <div class="text-subtitle1 text-weight-bold q-mb-xs">Cambiar Contraseña</div>
      <div class="text-caption text-grey-6 q-mb-sm">Ingresá tu contraseña actual y la nueva contraseña.</div>

      <q-form @submit.prevent="submitPassword" class="row q-col-gutter-sm items-start">
        <div class="col-12 col-sm-4">
          <q-input
            dense
            outlined
            v-model="formPassword.password.actual"
            :type="mostrarActual ? 'text' : 'password'"
            label="Contraseña actual *"
            @change="formPassword.validate('password.actual')"
            :error="formPassword.invalid('password.actual')"
            :class="formPassword.invalid('password.actual') ? 'q-mb-sm' : ''"
          >
            <template v-slot:append>
              <q-icon
                :name="mostrarActual ? 'visibility_off' : 'visibility'"
                class="cursor-pointer"
                @click="mostrarActual = !mostrarActual"
              />
            </template>
            <template v-slot:error>
              <div>{{ formPassword.errors['password.actual'] }}</div>
            </template>
          </q-input>
        </div>
        <div class="col-12 col-sm-4">
          <q-input
            dense
            outlined
            v-model="formPassword.password.nueva"
            :type="mostrarNueva ? 'text' : 'password'"
            label="Nueva contraseña *"
            @change="formPassword.validate('password.nueva')"
            :error="formPassword.invalid('password.nueva')"
            :class="formPassword.invalid('password.nueva') ? 'q-mb-sm' : ''"
          >
            <template v-slot:append>
              <q-icon
                :name="mostrarNueva ? 'visibility_off' : 'visibility'"
                class="cursor-pointer"
                @click="mostrarNueva = !mostrarNueva"
              />
            </template>
            <template v-slot:error>
              <div>{{ formPassword.errors['password.nueva'] }}</div>
            </template>
          </q-input>
        </div>
        <div class="col-12 col-sm-4">
          <q-input
            dense
            outlined
            v-model="formPassword.password.nueva_confirmation"
            :type="mostrarConfirmar ? 'text' : 'password'"
            label="Confirmar contraseña *"
            @change="formPassword.validate('password.nueva_confirmation')"
            :error="formPassword.invalid('password.nueva_confirmation')"
            :class="formPassword.invalid('password.nueva_confirmation') ? 'q-mb-sm' : ''"
          >
            <template v-slot:append>
              <q-icon
                :name="mostrarConfirmar ? 'visibility_off' : 'visibility'"
                class="cursor-pointer"
                @click="mostrarConfirmar = !mostrarConfirmar"
              />
            </template>
            <template v-slot:error>
              <div>{{ formPassword.errors['password.nueva_confirmation'] }}</div>
            </template>
          </q-input>
        </div>

        <div class="col-12">
          <div v-for="req in requisitosPassword" :key="req.label" class="row items-center q-mb-xs">
            <q-icon
              :name="req.cumple ? 'check_circle' : 'radio_button_unchecked'"
              :color="req.cumple ? 'positive' : 'grey-5'"
              size="18px"
              class="q-mr-xs req-icon"
              :class="{ 'req-icon--pop': req.cumple }"
            />
            <span class="text-caption req-texto" :class="req.cumple ? 'text-positive' : 'text-grey-7'">
              {{ req.label }}
            </span>
          </div>
        </div>

        <div class="col-12" align="right">
          <q-btn
            unelevated
            no-caps
            color="primary"
            label="Guardar Cambios"
            type="submit"
            :loading="formPassword.processing"
          />
        </div>
      </q-form>
    </q-card-section>
    </template>

    <template v-if="tienePersona">
      <q-separator />

      <q-form @submit.prevent="submit">
        <q-card-section class="q-pa-md">
          <div class="text-subtitle1 text-weight-bold q-mb-xs">Información Personal</div>
          <div class="text-caption text-grey-6 q-mb-md">
            <q-icon name="info" size="14px" class="q-mr-xs" />
            Revisá y completá tus datos. Podés cerrar esta ventana sin guardar y te la volvemos a mostrar la próxima
            vez que ingreses.
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-4">
              <q-input
                dense
                outlined
                readonly
                v-model="form.persona.dni"
                :loading="form.validating"
                label="DNI"
                maxlength="8"
                :error="form.invalid('persona.dni')"
                :class="form.invalid('persona.dni') ? 'q-mb-sm' : ''"
              >
                <template v-slot:prepend>
                  <q-icon name="badge" />
                </template>
                <template v-slot:error>
                  <div>{{ form.errors['persona.dni'] }}</div>
                </template>
              </q-input>
            </div>

            <div class="col-12 col-md-4">
              <q-input
                dense
                outlined
                readonly
                v-model="form.persona.nombre"
                label="Nombres"
                :error="form.invalid('persona.nombre')"
                :class="form.invalid('persona.nombre') ? 'q-mb-sm' : ''"
              >
                <template v-slot:error>
                  <div>{{ form.errors['persona.nombre'] }}</div>
                </template>
              </q-input>
            </div>

            <div class="col-12 col-md-4">
              <q-input
                dense
                outlined
                readonly
                v-model="form.persona.apellido_paterno"
                label="Apellido paterno"
                :error="form.invalid('persona.apellido_paterno')"
                :class="form.invalid('persona.apellido_paterno') ? 'q-mb-sm' : ''"
              >
                <template v-slot:error>
                  <div>{{ form.errors['persona.apellido_paterno'] }}</div>
                </template>
              </q-input>
            </div>

            <div class="col-12 col-md-4">
              <q-input
                dense
                outlined
                readonly
                v-model="form.persona.apellido_materno"
                label="Apellido materno"
                :error="form.invalid('persona.apellido_materno')"
                :class="form.invalid('persona.apellido_materno') ? 'q-mb-sm' : ''"
              >
                <template v-slot:error>
                  <div>{{ form.errors['persona.apellido_materno'] }}</div>
                </template>
              </q-input>
            </div>

            <div class="col-12 col-md-4">
              <q-input
                dense
                outlined
                v-model="form.persona.correo"
                type="email"
                label="Correo * (tu usuario para ingresar)"
                @change="form.validate('persona.correo')"
                :error="form.invalid('persona.correo')"
                :class="form.invalid('persona.correo') ? 'q-mb-sm' : ''"
              >
                <template v-slot:prepend>
                  <q-icon name="mail" />
                </template>
                <template v-slot:error>
                  <div>{{ form.errors['persona.correo'] }}</div>
                </template>
              </q-input>
            </div>

            <div class="col-12 col-md-4">
              <q-select
                dense
                outlined
                emit-value
                map-options
                v-model="form.persona.genero"
                :options="opcionesGenero"
                label="Género"
                clearable
              />
            </div>

            <div class="col-12 col-md-4">
              <q-select
                dense
                outlined
                emit-value
                map-options
                v-model="form.persona.estado_civil"
                :options="opcionesEstadoCivil"
                label="Estado civil"
                clearable
              />
            </div>

            <div class="col-12 col-md-4">
              <q-input dense outlined v-model="form.persona.fecha_nacimiento" type="date" label="Fecha de nacimiento" />
            </div>

            <div class="col-12 col-md-4">
              <q-input dense outlined v-model="form.persona.celular" label="Celular" maxlength="9" />
            </div>

            <div class="col-12 col-md-4">
              <q-input
                dense
                outlined
                v-model="form.persona.celular_emergencia"
                label="Celular de emergencia"
                maxlength="9"
              />
            </div>

            <div class="col-12 col-md-4">
              <q-input dense outlined v-model="form.persona.direccion" label="Dirección" />
            </div>

            <div class="col-12">
              <div class="text-caption text-grey-7 q-mb-xs">Lugar de nacimiento</div>
              <UbigeoCascadeSelect v-model="form.persona.ubigeo_cod_nacimiento" />
            </div>

            <div class="col-12">
              <div class="text-caption text-grey-7 q-mb-xs">Lugar de residencia</div>
              <UbigeoCascadeSelect v-model="form.persona.ubigeo_cod_residencia" />
            </div>

            <div class="col-12">
              <div class="text-caption text-grey-7 q-mb-xs">Comisión</div>
              <ComisionCascadeSelect v-model="form.persona.codigo_comision" />
            </div>

            <div class="col-12">
              <div class="text-caption text-grey-7 q-mb-xs">Comisión alternativa</div>
              <ComisionCascadeSelect v-model="form.persona.codigo_comision_alternativo" />
            </div>

            <div class="col-12" align="right">
              <q-btn
                unelevated
                no-caps
                color="primary"
                label="Guardar Cambios"
                type="submit"
                :loading="form.processing"
              />
            </div>
          </div>
        </q-card-section>
      </q-form>
    </template>
  </template>
</template>

<script setup>
import { useForm } from 'laravel-precognition-vue'
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import UbigeoCascadeSelect from '@/components/UbigeoCascadeSelect.vue'
import ComisionCascadeSelect from '@/components/ComisionCascadeSelect.vue'
import MiInformacionService from '@/services/MiInformacionService'
import formPerfil, { formUsuarioPerfil, formPasswordPerfil } from './FormPerfil'

const $q = useQuasar()
const emits = defineEmits(['save'])
const props = defineProps({
  // si ya se cargó desde afuera (AdminLayout), no la volvemos a pedir
  dataInicial: { type: Object, default: null },
  // en el recordatorio de login no queremos mostrar el cambio de contraseña
  mostrarPassword: { type: Boolean, default: true },
})
const cargando = ref(!props.dataInicial)
const tienePersona = ref(false)

const opcionesGenero = [
  { label: 'Masculino', value: 'masculino' },
  { label: 'Femenino', value: 'femenino' },
  { label: 'Sin especificar', value: 'sin especificar' },
]

const opcionesEstadoCivil = [
  { label: 'Soltero', value: 'soltero' },
  { label: 'Casado', value: 'casado' },
  { label: 'Divorciado', value: 'divorciado' },
  { label: 'Viudo', value: 'viudo' },
]

const form = useForm('put', 'api/mi-informacion', structuredClone(formPerfil))
const formUsuario = useForm('put', 'api/mi-usuario', structuredClone(formUsuarioPerfil))
const formPassword = useForm('put', 'api/mi-password', structuredClone(formPasswordPerfil))

const mostrarActual = ref(false)
const mostrarNueva = ref(false)
const mostrarConfirmar = ref(false)

const requisitosPassword = computed(() => {
  const pass = formPassword.password.nueva || ''
  return [
    { label: 'Al menos 8 caracteres', cumple: pass.length >= 8 },
    { label: 'Una letra mayúscula', cumple: /[A-Z]/.test(pass) },
    { label: 'Una letra minúscula', cumple: /[a-z]/.test(pass) },
    { label: 'Un número', cumple: /[0-9]/.test(pass) },
  ]
})

const nombreCompleto = computed(() =>
  [form.persona.nombre, form.persona.apellido_paterno, form.persona.apellido_materno].filter(Boolean).join(' '),
)
const inicial = computed(
  () => (form.persona.nombre?.charAt(0) || formUsuario.usuario.name?.charAt(0) || '?').toUpperCase(),
)

const submitUsuario = () => {
  formUsuario
    .submit()
    .then(() => {
      $q.notify({
        type: 'positive',
        message: 'Usuario actualizado con éxito.',
        position: 'top-right',
        progress: true,
        timeout: 1500,
      })
    })
    .catch(() => {})
}

const submitPassword = () => {
  formPassword
    .submit()
    .then(() => {
      formPassword.reset()
      $q.notify({
        type: 'positive',
        message: 'Contraseña actualizada con éxito.',
        position: 'top-right',
        progress: true,
        timeout: 1500,
      })
    })
    .catch(() => {})
}

const submit = () => {
  form.persona.correo_modificado = form.persona.correo !== form.persona.correo_original

  form
    .submit()
    .then(() => {
      form.persona.correo_original = form.persona.correo
      form.persona.correo_modificado = false
      emits('save')
      $q.notify({
        type: 'positive',
        message: 'Datos actualizados con éxito.',
        position: 'top-right',
        progress: true,
        timeout: 1500,
      })
    })
    .catch(() => {})
}

function hidratar({ usuario, persona }) {
  if (usuario) {
    formUsuario.setData({ usuario: { name: usuario.name, email: usuario.email } })
  }

  tienePersona.value = !!persona
  if (!persona) return

  form.setData({
    persona: {
      dni: persona.dni,
      nombre: persona.nombre,
      apellido_paterno: persona.apellido_paterno,
      apellido_materno: persona.apellido_materno,
      correo: persona.correo,
      correo_original: persona.correo,
      correo_modificado: false,
      genero: persona.genero,
      estado_civil: persona.estado_civil,
      fecha_nacimiento: persona.fecha_nacimiento,
      celular: persona.celular,
      celular_emergencia: persona.celular_emergencia,
      direccion: persona.direccion,
      ubigeo_cod_nacimiento: persona.ubigeo_cod_nacimiento,
      ubigeo_cod_residencia: persona.ubigeo_cod_residencia,
      codigo_comision: persona.codigo_comision,
      codigo_comision_alternativo: persona.codigo_comision_alternativo,
    },
  })
}

onMounted(async () => {
  if (props.dataInicial) {
    hidratar(props.dataInicial)
    cargando.value = false
    return
  }

  const datos = await MiInformacionService.get()
  hidratar(datos)
  cargando.value = false
})
</script>

<style scoped>
.req-icon {
  transition: color 0.25s ease;
}

.req-icon--pop {
  animation: req-pop 0.35s ease;
}

.req-texto {
  transition: color 0.25s ease;
}

@keyframes req-pop {
  0% {
    transform: scale(0.6);
  }
  50% {
    transform: scale(1.3);
  }
  100% {
    transform: scale(1);
  }
}
</style>
