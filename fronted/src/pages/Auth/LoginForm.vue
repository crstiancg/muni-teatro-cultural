<template>
  <div class="login">
    <!-- ---------- formulario ---------- -->
    <section class="lado-form">
      <div class="form-envoltura">
        <router-link :to="{ name: 'Inicio' }" class="volver">
          <ArrowLeft :size="16" /> Volver al portal
        </router-link>

        <div class="marca">
          <span class="marca-logo"><Drama :size="22" /></span>
          <span class="marca-texto">
            <span class="marca-nombre">CPC</span>
            <span class="marca-bajada">Registro de agentes culturales · Puno</span>
          </span>
        </div>

        <h1 class="titulo">Bienvenido de nuevo</h1>
        <p class="bajada">Ingresa con tu correo y tu contraseña para entrar al panel.</p>

        <q-form class="campos" @submit="login">
          <div class="campo">
            <label for="login-correo">Correo electrónico</label>
            <q-input
              v-model="formLogin.email"
              for="login-correo"
              outlined
              type="email"
              autocomplete="username"
              placeholder="nombre@correo.com"
            >
              <template #prepend><Mail :size="18" /></template>
            </q-input>
          </div>

          <div class="campo">
            <label for="login-clave">Contraseña</label>
            <q-input
              v-model="formLogin.password"
              for="login-clave"
              outlined
              :type="isPwd ? 'password' : 'text'"
              autocomplete="current-password"
            >
              <template #prepend><LockKeyhole :size="18" /></template>
              <template #append>
                <button
                  type="button"
                  class="ver-clave"
                  :aria-label="isPwd ? 'Mostrar contraseña' : 'Ocultar contraseña'"
                  @click="isPwd = !isPwd"
                >
                  <component :is="isPwd ? Eye : EyeOff" :size="18" />
                </button>
              </template>
            </q-input>
          </div>

          <q-btn
            type="submit"
            unelevated
            no-caps
            color="primary"
            :loading="loading"
            class="ingresar"
          >
            Ingresar al panel
          </q-btn>
        </q-form>

        <p class="pie">
          Municipalidad Provincial de Puno · Oficina de Tecnologías de la Información
        </p>
      </div>
    </section>

    <!-- ---------- agenda real (o una foto de Puno si no hay eventos) ---------- -->
    <aside class="lado-visual" aria-label="Próximos eventos de la agenda cultural">
      <template v-if="actual">
        <div class="fondo-afiche" :style="{ backgroundImage: `url(${actual.imagen_url})` }" />
        <div class="evento">
          <img
            :key="actual.id"
            :src="actual.imagen_url"
            :alt="`Afiche de ${actual.titulo}`"
            class="afiche"
          />
          <div class="evento-texto">
            <span class="etiqueta">Próximo en la agenda</span>
            <h2 class="evento-titulo">{{ actual.titulo }}</h2>
            <p class="evento-dato">
              {{ cuandoTexto(actual).fecha }} · {{ cuandoTexto(actual).horario }}
            </p>
            <p v-if="actual.lugar" class="evento-dato">{{ actual.lugar }}</p>
          </div>
        </div>
        <div v-if="eventos.length > 1" class="puntos">
          <button
            v-for="(e, i) in eventos"
            :key="e.id"
            type="button"
            class="punto"
            :class="{ activo: i === indice }"
            :aria-label="`Ver ${e.titulo}`"
            :aria-pressed="i === indice"
            @click="irA(i)"
          />
        </div>
      </template>

      <template v-else>
        <div class="foto" />
        <div class="lema">
          <span class="etiqueta">Consejo Provincial de Cultura</span>
          <h2 class="evento-titulo">
            Los artistas y la agenda cultural de Puno, en un solo lugar.
          </h2>
        </div>
      </template>
    </aside>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useQuasar } from 'quasar'
import { ArrowLeft, Drama, Mail, LockKeyhole, Eye, EyeOff } from 'lucide-vue-next'
import { useUserStore } from '@/stores/user-store'
import EventoService from '@/services/EventoService'
import { cuandoTexto } from '@/utils/fechasAgenda'

const $q = useQuasar()
const emits = defineEmits(['logued'])
const userStore = useUserStore()

const loading = ref(false)
const formLogin = ref({ email: '', password: '' })
const isPwd = ref(true)

// ---------- próximos eventos reales (solo los que tienen afiche) ----------
const eventos = ref([])
const indice = ref(0)
const actual = computed(() => eventos.value[indice.value] ?? null)
let temporizador = null

const sinMovimiento = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

function irA(i) {
  indice.value = i
  reiniciarTemporizador()
}

function reiniciarTemporizador() {
  clearInterval(temporizador)
  if (sinMovimiento || eventos.value.length < 2) return
  temporizador = setInterval(() => {
    indice.value = (indice.value + 1) % eventos.value.length
  }, 7000)
}

onMounted(async () => {
  try {
    const r = await EventoService.proximos(6)
    eventos.value = (r.data ?? r).filter((e) => e.imagen_url).slice(0, 4)
    reiniciarTemporizador()
  } catch {
    // sin agenda: queda la foto de Puno
  }
})

onBeforeUnmount(() => clearInterval(temporizador))

const login = async () => {
  loading.value = true
  try {
    await userStore.login(formLogin.value.email, formLogin.value.password)
    emits('logued')
    $q.notify({
      position: 'top',
      type: 'positive',
      message: 'Sesión iniciada con éxito',
      timeout: 1000,
    })
  } catch (e) {
    $q.notify({
      position: 'top',
      type: 'negative',
      // 429: LimitarIntentosLogin bloqueó por demasiados intentos fallidos
      message: !e.response
        ? 'Sin conexión con el servidor'
        : e.response.status === 429
          ? e.response.data.message
          : 'Correo o contraseña incorrectos',
    })
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login {
  width: 100%;
  min-height: 100vh;
  display: grid;
  grid-template-columns: minmax(380px, 480px) minmax(0, 1fr);
  background: var(--panel-fondo);
  color: var(--panel-texto);
}

/* ---------- formulario ---------- */
.lado-form {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px clamp(20px, 4vw, 48px);
}

.form-envoltura {
  width: 100%;
  max-width: 360px;
}

.volver {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 40px;
  color: var(--panel-texto-suave);
  font-size: 0.88rem;
  font-weight: 500;
  text-decoration: none;
}

.volver:hover {
  color: var(--panel-texto);
}

.marca {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 32px;
}

.marca-logo {
  display: grid;
  place-items: center;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: var(--panel-acento);
  color: var(--panel-sobre-acento);
  flex-shrink: 0;
}

.marca-texto {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.marca-nombre {
  font-family: 'Fraunces', Georgia, serif;
  font-weight: 800;
  font-size: 1.3rem;
  letter-spacing: -0.01em;
}

.marca-bajada {
  font-size: 0.78rem;
  color: var(--panel-texto-suave);
}

.titulo {
  margin: 0 0 8px;
  font-family: 'Fraunces', Georgia, serif;
  font-size: 2.1rem;
  font-weight: 800;
  line-height: 1.1;
  letter-spacing: -0.015em;
}

.bajada {
  margin: 0 0 28px;
  color: var(--panel-texto-suave);
  line-height: 1.55;
}

.campos {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.campo label {
  display: block;
  margin-bottom: 6px;
  font-size: 0.82rem;
  font-weight: 600;
}

.campo :deep(.q-field__control) {
  height: 48px;
  background: var(--panel-superficie);
}

.ver-clave {
  display: grid;
  place-items: center;
  padding: 4px;
  border: 0;
  background: none;
  color: var(--panel-texto-suave);
  cursor: pointer;
  border-radius: 6px;
}

.ver-clave:hover {
  color: var(--panel-texto);
}

.ingresar {
  height: 50px;
  margin-top: 8px;
  font-size: 0.98rem;
  border-radius: 12px !important;
}

.pie {
  margin: 32px 0 0;
  font-size: 0.75rem;
  color: var(--panel-texto-suave);
  line-height: 1.5;
}

/* ---------- visual ---------- */
.lado-visual {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px;
  background: #1c1c1c;
  color: #ede6d8;
}

/* el mismo afiche, desenfocado, como ambiente de fondo */
.fondo-afiche {
  position: absolute;
  inset: -40px;
  background-size: cover;
  background-position: center;
  filter: blur(40px) saturate(1.1);
  opacity: 0.35;
  transition: background-image 0.6s;
}

.evento {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: flex-end;
  gap: 32px;
  max-width: 760px;
}

/* el afiche se ve entero: los afiches son verticales y recortarlos los arruina */
.afiche {
  width: min(320px, 34vw);
  max-height: 72vh;
  object-fit: contain;
  border-radius: 12px;
  box-shadow: 0 30px 60px -20px rgba(0, 0, 0, 0.7);
  animation: aparecer 0.5s ease-out;
}

.evento-texto {
  min-width: 0;
  padding-bottom: 8px;
}

.etiqueta {
  display: inline-block;
  margin-bottom: 14px;
  padding: 4px 12px;
  border-radius: 999px;
  background: #b3261e;
  color: #f7f2e9;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.evento-titulo {
  margin: 0 0 10px;
  font-family: 'Fraunces', Georgia, serif;
  font-size: clamp(1.5rem, 2.4vw, 2.2rem);
  font-weight: 800;
  line-height: 1.12;
  letter-spacing: -0.01em;
  overflow-wrap: anywhere;
}

.evento-dato {
  margin: 0;
  color: rgba(237, 230, 216, 0.78);
  line-height: 1.6;
}

.puntos {
  position: absolute;
  left: 48px;
  bottom: 32px;
  z-index: 1;
  display: flex;
  gap: 8px;
}

.punto {
  width: 8px;
  height: 8px;
  padding: 0;
  border: 0;
  border-radius: 999px;
  background: rgba(237, 230, 216, 0.4);
  cursor: pointer;
  transition: width 0.2s;
}

.punto.activo {
  width: 24px;
  background: #ede6d8;
}

.punto:focus-visible {
  outline: 2px solid #e8776c;
  outline-offset: 3px;
}

/* sin eventos: foto de Puno con un velo carbón */
.foto {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(180deg, rgba(28, 28, 28, 0.15), rgba(28, 28, 28, 0.85)),
    url('/images/wallpaper_puno.png') center / cover;
}

.lema {
  position: relative;
  z-index: 1;
  align-self: flex-end;
  max-width: 560px;
  margin-right: auto;
}

@keyframes aparecer {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
}

@media (prefers-reduced-motion: reduce) {
  .afiche {
    animation: none;
  }
}

/* tablet y celular: el formulario primero; la agenda no se muestra */
@media (max-width: 900px) {
  .login {
    grid-template-columns: 1fr;
  }

  .lado-visual {
    display: none;
  }

  .lado-form {
    align-items: flex-start;
    padding-top: 32px;
  }

  .volver {
    margin-bottom: 28px;
  }
}
</style>
