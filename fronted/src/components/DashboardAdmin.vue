<template>
  <div v-if="cargando" class="text-center q-pa-md">
    <q-spinner color="primary" size="32px" />
  </div>

  <div v-else-if="datos">
    <!-- conteos por estado: cada uno abre la lista ya filtrada -->
    <div class="row q-col-gutter-md q-mb-md">
      <div v-for="(total, estado) in datos.estados" :key="estado" class="col-6 col-md-3">
        <q-card
          flat
          bordered
          class="cursor-pointer tarjeta-estado"
          :class="{ 'tarjeta-estado--alerta': estado === 'pendiente' && total > 0 }"
          @click="irALista(estado)"
        >
          <q-card-section>
            <div class="text-caption text-grey-7">{{ estadoPerfil(estado).label }}</div>
            <div class="text-h4 text-weight-bold" :class="`text-${estadoPerfil(estado).color}`">
              {{ total }}
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-md">
      <div class="col-12 col-md-5">
        <q-card flat bordered class="full-height">
          <q-card-section class="row items-center justify-between">
            <div class="text-subtitle1 text-weight-bold">Solicitudes pendientes</div>
            <q-btn
              flat
              dense
              no-caps
              color="primary"
              label="Ver bandeja"
              @click="irALista('pendiente')"
            />
          </q-card-section>
          <q-separator />

          <q-list v-if="datos.pendientes.length" separator>
            <q-item
              v-for="p in datos.pendientes"
              :key="p.id"
              clickable
              :to="{ name: 'PersonaDetalle', params: { id: p.id } }"
            >
              <q-item-section avatar>
                <q-avatar color="primary" text-color="white" size="36px">
                  <img v-if="p.foto?.url" :src="p.foto.url" style="object-fit: cover" />
                  <template v-else>{{ p.nombre_completo?.charAt(0) }}</template>
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ p.nombre_completo }}</q-item-label>
                <q-item-label caption>Enviado el {{ fecha(p.updated_at) }}</q-item-label>
              </q-item-section>
              <q-item-section side><ChevronRight :size="18" /></q-item-section>
            </q-item>
          </q-list>
          <div v-else class="text-center text-grey-6 q-pa-lg">
            No hay solicitudes pendientes. ¡Todo al día!
          </div>
        </q-card>
      </div>

      <div class="col-12 col-md-7">
        <q-card flat bordered class="full-height">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold">Últimas actividades subidas</div>
          </q-card-section>
          <q-separator />

          <div v-if="datos.actividades.length" class="row q-col-gutter-sm q-pa-sm">
            <div v-for="a in datos.actividades" :key="a.id" class="col-6 col-sm-3">
              <router-link
                :to="{ name: 'PersonaDetalle', params: { id: a.persona_id } }"
                class="actividad"
              >
                <q-img v-if="a.imagen_url" :src="a.imagen_url" :ratio="1" class="rounded-borders" />
                <div v-else class="sin-imagen rounded-borders flex flex-center">
                  <ImageOff :size="22" />
                </div>
                <div class="text-caption ellipsis q-mt-xs">{{ a.persona?.nombre_completo }}</div>
              </router-link>
            </div>
          </div>
          <div v-else class="text-center text-grey-6 q-pa-lg">Todavía no hay actividades.</div>
        </q-card>
      </div>
    </div>
  </div>

  <div v-else class="text-center q-pa-md">
    <div class="q-mb-sm">No se pudo cargar el resumen.</div>
    <q-btn outline no-caps color="primary" label="Reintentar" @click="cargar" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { date } from 'quasar'
import { ChevronRight, ImageOff } from 'lucide-vue-next'
import DashboardService from '@/services/DashboardService'
import { estadoPerfil } from '@/config/estadosPerfil'

const router = useRouter()
const datos = ref(null)
const cargando = ref(true)

const fecha = (valor) => date.formatDate(valor, 'DD/MM/YYYY HH:mm')

// PersonasList lee ?estado= para arrancar con ese filtro
const irALista = (estado) => router.push({ name: 'Personas', query: { estado } })

async function cargar() {
  cargando.value = true
  try {
    datos.value = await DashboardService.admin()
  } catch {
    datos.value = null
  } finally {
    cargando.value = false
  }
}

onMounted(cargar)
</script>

<style scoped>
.tarjeta-estado {
  transition: transform 0.15s ease;
}

.tarjeta-estado:hover {
  transform: translateY(-2px);
}

.tarjeta-estado--alerta {
  border-color: var(--q-primary);
  border-width: 2px;
}

.actividad {
  color: inherit;
  text-decoration: none;
}

.sin-imagen {
  aspect-ratio: 1;
  background: rgba(128, 128, 128, 0.12);
  color: grey;
}
</style>
