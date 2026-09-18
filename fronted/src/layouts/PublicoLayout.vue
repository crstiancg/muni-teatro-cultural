<template>
  <q-layout view="hHh lpR fFf" class="publico-root">
    <header class="topbar">
      <router-link :to="{ name: 'Inicio' }" class="brand">
        <span class="brand-mark" aria-hidden="true">
          <i /><i /><i />
        </span>
        <span class="brand-text">
          <span class="brand-nombre">TEATRO<span class="oro">CULTURAL</span></span>
          <span class="brand-sub">arte y comunidad</span>
        </span>
      </router-link>

      <nav class="nav" :class="{ abierto: menuAbierto }">
        <router-link :to="{ name: 'Inicio' }" class="nav-link" @click="menuAbierto = false">Inicio</router-link>
        <router-link :to="{ name: 'ConsejerosPublico' }" class="nav-link" @click="menuAbierto = false">
          Artistas
        </router-link>
        <router-link :to="{ name: 'Login' }" class="cta" @click="menuAbierto = false">Ingresar</router-link>
      </nav>

      <button class="menu-btn" @click="menuAbierto = !menuAbierto" aria-label="Menú">
        <Menu v-if="!menuAbierto" :size="22" />
        <X v-else :size="22" />
      </button>
    </header>

    <q-page-container>
      <router-view />
    </q-page-container>

    <footer class="footbar">
      <span>© {{ anio }} Teatro Cultural</span>
      <span class="footbar-sep">·</span>
      <span>Municipalidad</span>
    </footer>
  </q-layout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Menu, X } from 'lucide-vue-next'

const anio = new Date().getFullYear()
const menuAbierto = ref(false)

// la barra de scroll se oculta solo mientras se está en el sitio público
onMounted(() => document.documentElement.classList.add('sin-barra-scroll'))
onBeforeUnmount(() => document.documentElement.classList.remove('sin-barra-scroll'))
</script>

<style scoped>
.publico-root {
  background: #0a0a0a;
}

/* ---------- topbar ---------- */
.topbar {
  position: sticky;
  top: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 28px;
  background: rgba(10, 10, 10, 0.92);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid #1d1d1d;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
}

.brand-mark {
  display: flex;
  align-items: flex-end;
  gap: 3px;
  height: 22px;
}

.brand-mark i {
  display: block;
  width: 3px;
  background: #00a7e5;
  border-radius: 2px;
}

.brand-mark i:nth-child(1) {
  height: 12px;
}
.brand-mark i:nth-child(2) {
  height: 22px;
}
.brand-mark i:nth-child(3) {
  height: 16px;
}

.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}

.brand-nombre {
  color: #fff;
  font-weight: 800;
  font-size: 1rem;
  letter-spacing: 0.04em;
}

.oro {
  color: #00a7e5;
}

.brand-sub {
  color: #8a8a8a;
  font-size: 0.6rem;
  letter-spacing: 0.22em;
  text-transform: uppercase;
}

.nav {
  display: flex;
  align-items: center;
  gap: 30px;
}

.nav-link {
  color: #b9b9b9;
  text-decoration: none;
  font-size: 0.78rem;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  padding: 6px 0;
  border-bottom: 2px solid transparent;
  transition: color 0.2s ease, border-color 0.2s ease;
}

.nav-link:hover {
  color: #fff;
}

.nav-link.router-link-exact-active {
  color: #00a7e5;
  border-bottom-color: #00a7e5;
}

.cta {
  background: #00a7e5;
  color: #101010;
  text-decoration: none;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 11px 22px;
  border-radius: 4px;
  transition: background 0.2s ease, transform 0.2s ease;
}

.cta:hover {
  background: #00a7e5;
  transform: translateY(-1px);
}

.menu-btn {
  display: none;
  background: none;
  border: none;
  color: #fff;
  cursor: pointer;
  padding: 4px;
}

@media (max-width: 720px) {
  .topbar {
    padding: 14px 18px;
    flex-wrap: wrap;
  }

  .menu-btn {
    display: block;
  }

  .nav {
    display: none;
    width: 100%;
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
    padding: 18px 2px 6px;
  }

  .nav.abierto {
    display: flex;
  }

  .cta {
    width: 100%;
    text-align: center;
  }
}

/* ---------- footer ---------- */
.footbar {
  background: #0a0a0a;
  border-top: 1px solid #1d1d1d;
  color: #6a6a6a;
  text-align: center;
  font-size: 0.75rem;
  letter-spacing: 0.06em;
  padding: 26px 16px;
}

.footbar-sep {
  margin: 0 8px;
  color: #3a3a3a;
}
</style>
