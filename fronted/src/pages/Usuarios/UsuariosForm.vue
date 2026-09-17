<template>
  <!-- Edición rápida del permiso sin salir del form de usuario -->
  <q-dialog v-model="dialogPermiso" persistent>
    <PermisosForm
      :key="permisoEditId"
      title="Editar Permiso"
      :id="permisoEditId"
      ref="permisosFormRef"
      @save="onPermisoGuardado"
    />
  </q-dialog>

  <q-card :style="{ width: '100%', maxWidth: $q.screen.gt.sm ? '60vw' : '100vw' }">
    <q-card-section class="bg-primary">
      <div class="row text-white">
        <div class="text-h6">{{ title }}</div>
        <q-space />
        <q-btn v-close-popup round size="sm" unelevated>
          <q-icon name="close" />
        </q-btn>
      </div>
    </q-card-section>

    <q-form @submit.prevent="submit">
      <q-card-section class="q-pa-md">
        <q-input
          dense
          outlined
          v-model="form.name"
          :loading="form.validating"
          label="Nombre *"
          @update:model-value="form.validate('name')"
          :error="form.invalid('name')"
          :class="form.invalid('name') ? 'q-mb-sm' : ''"
        >
          <template v-slot:prepend>
            <q-icon name="person" />
          </template>
          <template v-slot:error>
            <div>{{ form.errors.name }}</div>
          </template>
        </q-input>

        <q-input
          dense
          outlined
          v-model="form.email"
          label="Email *"
          type="email"
          @change="form.validate('email')"
          :error="form.invalid('email')"
          :class="form.invalid('email') ? 'q-mb-sm' : ''"
        >
          <template v-slot:prepend>
            <q-icon name="mail" />
          </template>
          <template v-slot:error>
            <div>{{ form.errors.email }}</div>
          </template>
        </q-input>

        <q-input
          dense
          outlined
          v-model="form.password"
          :type="isPwd ? 'password' : 'text'"
          :label="props.id ? 'Contraseña (dejar en blanco para no cambiar)' : 'Contraseña *'"
          @change="form.validate('password')"
          :error="form.invalid('password')"
          :class="form.invalid('password') ? 'q-mb-sm' : ''"
        >
          <template v-slot:append>
            <q-icon :name="isPwd ? 'visibility_off' : 'visibility'" class="cursor-pointer" @click="isPwd = !isPwd" />
          </template>
          <template v-slot:prepend>
            <q-icon name="lock" />
          </template>
          <template v-slot:error>
            <div>{{ form.errors.password }}</div>
          </template>
        </q-input>

        <div class="row q-col-gutter-sm q-mt-xs">
          <div class="col-12 col-md-6">
            <q-card class="q-pa-md" flat bordered>
              <div class="q-mb-md text-center">Roles</div>
              <q-list bordered separator>
                <q-item v-for="rol in roles" :key="rol.id" clickable v-ripple dense>
                  <q-item-section>
                    <q-toggle
                      keep-color
                      v-model="form.rolesSelected"
                      :label="rol.name"
                      color="secondary"
                      :val="rol.id"
                      dense
                    />
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <div class="col-12 col-md-6">
            <q-card class="q-pa-md" flat bordered>
              <div class="q-mb-sm text-center">Permisos directos</div>
              <SelectGeneralAsync
                :key="permisoSelectKey"
                :add="false"
                option-label="description"
                :filter-fields="['description', 'name']"
                :service-api="PermisoService"
                option-value="id"
                label="Agregar permiso"
                prepend-icon="vpn_key"
                v-model="newPermiso"
                v-model:id="newPermisoId"
                :exclude-ids="form.permisosSelected"
                @option-selected="storePermiso"
              >
                <template #option="{ itemProps, opt }">
                  <q-item v-bind="itemProps" dense>
                    <q-item-section avatar>
                      <q-avatar size="32px" color="secondary" text-color="white" icon="vpn_key" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>{{ opt.description }}</q-item-label>
                      <q-item-label caption class="text-grey-6">{{ opt.name }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </SelectGeneralAsync>

              <div class="text-caption text-grey-7 q-mt-md q-mb-xs">
                Permisos asignados ({{ filteredPermisos.length }})
              </div>
              <div v-if="!filteredPermisos.length" class="text-caption text-grey-5 q-py-sm">
                Sin permisos directos asignados
              </div>
              <q-list v-else separator dense>
                <q-item v-for="p in filteredPermisos" :key="p.id" dense>
                  <q-item-section avatar>
                    <q-avatar size="32px" color="secondary" text-color="white" icon="vpn_key" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ p.description }}</q-item-label>
                    <q-item-label caption class="text-grey-6">{{ p.name }}</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-btn
                      flat
                      round
                      dense
                      size="sm"
                      icon="edit"
                      color="grey-7"
                      class="q-mr-xs"
                      @click="editarPermiso(p.id)"
                    >
                      <q-tooltip>Editar permiso</q-tooltip>
                    </q-btn>
                    <q-toggle keep-color v-model="form.permisosSelected" color="secondary" :val="p.id" dense />
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <div class="col-12">
            <q-card class="q-pa-md" flat bordered>
              <div class="q-mb-sm text-center">
                Permisos heredados del rol
                <span class="text-caption text-grey-6">({{ permisosHeredados.length }})</span>
              </div>
              <div v-if="!permisosHeredados.length" class="text-caption text-grey-5 text-center q-py-sm">
                Asigná un rol para ver los permisos que hereda
              </div>
              <div v-else class="row q-col-gutter-sm">
                <div v-for="p in permisosHeredados" :key="p.id" class="col-12 col-sm-6 col-md-4">
                  <div class="permiso-tile row items-center no-wrap">
                    <q-avatar size="28px" color="grey-3" text-color="grey-7" icon="vpn_key" class="q-mr-sm" />
                    <div class="col overflow-hidden">
                      <div class="permiso-tile__label ellipsis">{{ p.description || p.name }}</div>
                      <div class="permiso-tile__slug ellipsis">{{ p.name }}</div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="text-caption text-grey-6 q-mt-sm">
                <q-icon name="info" size="14px" class="q-mr-xs" />
                Estos se editan desde el rol, no desde acá.
              </div>
            </q-card>
          </div>
        </div>
      </q-card-section>
      <q-separator />

      <q-card-actions align="right">
        <q-btn label="Cancelar" flat v-close-popup></q-btn>
        <q-btn outline label="Guardar" :loading="form.processing" type="submit" color="positive"></q-btn>
      </q-card-actions>
    </q-form>
  </q-card>
</template>

<script setup>
import { useForm } from 'laravel-precognition-vue'
import { onMounted, ref, computed } from 'vue'
import RolService from '@/services/RolService'
import PermisoService from '@/services/PermisoService'
import PermisosForm from '@/pages/Permisos/PermisosForm.vue'
import SelectGeneralAsync from '@/components/selectGeneralAsyncrono.vue'

const isPwd = ref(true)
const roles = ref([])
const listPermisos = ref([])
const emits = defineEmits(['save'])

const props = defineProps({
  title: String,
  id: { type: Number, default: null },
})

const form = props.id
  ? useForm('put', 'api/usuarios/' + props.id, {
      name: '',
      email: '',
      password: '',
      rolesSelected: [],
      permisosSelected: [],
    })
  : useForm('post', 'api/usuarios', {
      name: '',
      email: '',
      password: '',
      rolesSelected: [],
      permisosSelected: [],
    })

const filteredPermisos = computed(() => listPermisos.value.filter((p) => form.permisosSelected.includes(p.id)))

// Permisos que el usuario hereda por los roles asignados. Se editan desde el
// rol, no desde acá — solo lectura, para que quede claro qué puede hacer.
const permisosHeredados = computed(() => {
  const seleccionados = roles.value.filter((r) => form.rolesSelected.includes(r.id))
  const vistos = new Set()
  const permisos = []
  for (const rol of seleccionados) {
    for (const p of rol.permissions ?? []) {
      if (!vistos.has(p.id)) {
        vistos.add(p.id)
        permisos.push(p)
      }
    }
  }
  return permisos
})

const dialogPermiso = ref(false)
const permisoEditId = ref(null)
const permisosFormRef = ref(null)

async function editarPermiso(id) {
  permisoEditId.value = id
  dialogPermiso.value = true
  const permiso = await PermisoService.get(id)
  permisosFormRef.value.form.setData({ permiso })
}

async function onPermisoGuardado() {
  const id = permisoEditId.value
  dialogPermiso.value = false
  permisoEditId.value = null

  // refrescamos el permiso en la lista de directos para que el texto
  // nuevo se vea sin recargar el form entero
  const actualizado = await PermisoService.get(id)
  const i = listPermisos.value.findIndex((p) => p.id === id)
  if (i !== -1) listPermisos.value[i] = actualizado
}

const newPermiso = ref(null)
const newPermisoId = ref(null)
const permisoSelectKey = ref(0)

const storePermiso = (permiso) => {
  if (permiso && !form.permisosSelected.includes(permiso.id)) {
    form.permisosSelected.push(permiso.id)
    listPermisos.value.push(permiso)
  }
  newPermiso.value = null
  newPermisoId.value = null
  permisoSelectKey.value++
}

const setPermisoObjects = (permisos) => {
  listPermisos.value = permisos
}

async function cargarRoles() {
  const { data } = await RolService.getData({ params: { rowsPerPage: 0, order_by: 'id' } })
  roles.value = data
}

const submit = () => {
  if (form.password === '') {
    delete form.password
  }
  form
    .submit()
    .then(() => {
      form.reset()
      emits('save')
    })
    .catch(() => {})
}

onMounted(() => {
  cargarRoles()
})

defineExpose({ form, setPermisoObjects })
</script>

<style lang="scss" scoped>
.permiso-tile {
  padding: 6px 10px;
  border-radius: 8px;
  background: rgba(0, 0, 0, 0.035);
}

.permiso-tile__label {
  font-size: 13px;
  line-height: 1.25;
}

.permiso-tile__slug {
  font-size: 11px;
  line-height: 1.2;
  color: #9e9e9e;
}
</style>
