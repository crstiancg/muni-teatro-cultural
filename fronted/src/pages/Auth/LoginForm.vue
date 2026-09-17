<template>
  <div class="login-page">
    <div class="login-card">
      <div class="lc-form">
        <div class="lc-form-inner">
          <div class="lc-brand">
            <q-icon size="24px" color="primary" name="theater_comedy" />
            <span>Teatro Cultural</span>
          </div>

          <h1>Bienvenido de nuevo</h1>
          <p class="lc-sub">Ingresá tus credenciales para acceder al panel administrativo.</p>

          <q-form @submit="login" class="lc-fields">
            <div class="lc-field">
              <label>Correo electrónico</label>
              <q-input
                v-model="formLogin.email"
                outlined
                dense
                placeholder="correo@municipalidad.gob.pe"
              >
                <template #prepend>
                  <q-icon name="mail_outline" color="grey-5" size="20px" />
                </template>
              </q-input>
            </div>

            <div class="lc-field">
              <label>Contraseña</label>
              <q-input
                v-model="formLogin.password"
                outlined
                dense
                :type="isPwd ? 'password' : 'text'"
                placeholder="********"
              >
                <template #prepend>
                  <q-icon name="lock_outline" color="grey-5" size="20px" />
                </template>
                <template #append>
                  <q-icon
                    :name="isPwd ? 'visibility_off' : 'visibility'"
                    color="grey-4"
                    size="20px"
                    class="cursor-pointer"
                    @click="isPwd = !isPwd"
                  />
                </template>
              </q-input>
            </div>

            <q-btn type="submit" unelevated no-caps :loading="loading" class="lc-btn">
              Iniciar sesión
            </q-btn>
          </q-form>

          <p class="lc-footer">Sistema exclusivo para personal autorizado</p>
        </div>
      </div>

      <div class="lc-visual">
        <q-carousel
          v-model="slide"
          animated
          autoplay
          infinite
          transition-prev="fade"
          transition-next="fade"
          swipeable
          class="lc-carousel"
        >
          <q-carousel-slide
            v-for="(evento, idx) in eventos"
            :key="idx"
            :name="idx"
            class="lc-slide"
            :style="{ backgroundImage: `url(${evento.imagen})` }"
          >
            <div class="lc-slide-overlay"></div>
          </q-carousel-slide>
        </q-carousel>

        <div class="lc-headline">
          <span class="lc-tag">Próximo evento</span>
          <h2>{{ eventos[slide].titulo }}</h2>
          <p>{{ eventos[slide].fecha }} · {{ eventos[slide].lugar }}</p>
        </div>

        <div class="lc-dots">
          <span
            v-for="(evento, idx) in eventos"
            :key="idx"
            class="dot"
            :class="{ active: slide === idx }"
            @click="slide = idx"
          ></span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useQuasar } from 'quasar'
import { ref } from 'vue'
import { useUserStore } from '@/stores/user-store'

const loading = ref(false)
const $q = useQuasar()
const emits = defineEmits(['logued'])
const userStore = useUserStore()
const formLogin = ref({ email: '', password: '' })
const isPwd = ref(true)
const slide = ref(0)

// TODO: reemplazar por los próximos eventos reales (endpoint del backend)
const eventos = [
  {
    imagen: '/images/wallpaper_puno.png',
    titulo: 'Festival de Teatro Regional',
    fecha: '12 de octubre',
    lugar: 'Teatro Municipal',
  },
  {
    // TODO placeholder: reemplazar por foto real de la municipalidad
    imagen: '/images/wall1.jpg',
    titulo: 'Noche de Danzas Folklóricas',
    fecha: '20 de octubre',
    lugar: 'Plaza de Armas',
  },
  {
    // TODO placeholder: reemplazar por foto real de la municipalidad
    imagen: '/images/wall2.jpg',
    titulo: 'Concierto Sinfónico Municipal',
    fecha: '5 de noviembre',
    lugar: 'Teatro Municipal',
  },
  {
    // TODO placeholder: reemplazar por foto real de la municipalidad
    imagen: '/images/wall3.jpg',
    titulo: 'Concierto Sinfónico Municipal',
    fecha: '5 de noviembre',
    lugar: 'Teatro Municipal',
  },
]

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
      message: e.response ? 'Credenciales incorrectas' : 'Sin conexión con el servidor',
    })
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  width: 100%;
  min-height: 100vh;
  display: flex;
}

.login-card {
  width: 100%;
  min-height: 100vh;
  display: flex;
}

/* --- form panel --- */
.lc-form {
  flex: 0 0 420px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

body.body--dark .lc-form {
  background: #000000;
}

.lc-form-inner {
  width: 100%;
  max-width: 320px;
}

.lc-brand {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  font-size: 1.25rem;
  color: var(--q-primary);
  margin-bottom: 32px;
  font-family: cursive, sans-serif;
}

.lc-form-inner h1 {
  font-size: 1.6rem;
  font-weight: 800;
  margin: 0 0 8px;
  letter-spacing: -0.3px;
}

.lc-sub {
  font-size: 0.85rem;
  color: #6b7280;
  margin: 0 0 28px;
  line-height: 1.6;
}

.lc-fields {
  display: flex;
  flex-direction: column;
}

.lc-field {
  margin-bottom: 16px;
}

.lc-field label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
  letter-spacing: 0.02em;
}

.lc-input :deep(.q-field__control) {
  border-radius: 10px;
  height: 48px;
  background: #fff;
}

.lc-btn {
  width: 100%;
  height: 52px;
  border-radius: 12px !important;
  background: linear-gradient(200deg, var(--q-primary) 0%, var(--q-secondary) 100%) !important;
  color: #fff !important;
  font-size: 0.95rem !important;
  font-weight: 700 !important;
  letter-spacing: 0.02em;
  margin-top: 4px;
}

.lc-footer {
  font-size: 0.73rem;
  color: #9ca3af;
  text-align: center;
  margin-top: 24px;
}

/* --- visual panel --- */
.lc-visual {
  flex: 1;
  position: relative;
  overflow: hidden;
}

.lc-carousel {
  width: 100%;
  height: 100%;
}

.lc-slide {
  background-size: cover;
  background-position: center;
}

.lc-slide-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(200deg, rgba(10, 12, 20, 0.05) 0%, rgba(10, 12, 20, 0.55) 100%);
}

.lc-headline {
  position: absolute;
  left: 32px;
  right: 32px;
  bottom: 50px;
  z-index: 3;
  color: #fff;
}

.lc-tag {
  display: inline-block;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 99px;
  padding: 5px 14px;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.03em;
  margin-bottom: 12px;
}

.lc-headline h2 {
  font-size: 1.7rem;
  font-weight: 800;
  line-height: 1.25;
  margin: 0 0 6px;
}

.lc-headline p {
  font-size: 0.85rem;
  opacity: 0.85;
  margin: 0;
}

.lc-dots {
  position: absolute;
  left: 24px;
  bottom: 24px;
  z-index: 3;
  display: flex;
  gap: 6px;
}

.dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.5);
  cursor: pointer;
}

.dot.active {
  width: 20px;
  border-radius: 4px;
  background: #fff;
}

/* --- responsive --- */
@media (max-width: 900px) {
  .login-page {
    padding: 0;
  }

  .login-card {
    flex-direction: column;
    border-radius: 0;
    height: auto;
    min-height: 100vh;
    box-shadow: none;
  }

  .lc-form {
    flex: 0 0 auto;
    order: 2;
    padding: 32px 24px 40px;
  }

  .lc-form-inner {
    max-width: none;
  }

  .lc-visual {
    flex: 0 0 260px;
    order: 1;
  }

  .lc-headline h2 {
    font-size: 1.3rem;
  }
}
</style>
