<template>
  <q-layout view="hHh lpR fFf" class="publico-root">
    <a href="#contenido" class="saltar">Saltar al contenido</a>

    <header class="topbar">
      <div class="topbar-inner">
        <router-link :to="{ name: 'Inicio' }" class="brand">
          <!-- monograma: las tres cañas del siku, que es el instrumento que
               abre la Candelaria. Reemplazable por el escudo municipal. -->
          <span class="brand-mark" aria-hidden="true"><i /><i /><i /></span>
          <span class="brand-text">
            <span class="brand-nombre">{{ INSTITUCION.nombre }}</span>
            <span class="brand-sub">{{ INSTITUCION.ciudad }} · Capital Folclórica</span>
          </span>
        </router-link>

        <nav class="nav" :class="{ abierto: menuAbierto }" aria-label="Principal">
          <router-link :to="{ name: 'Inicio' }" class="nav-link" @click="menuAbierto = false">
            Inicio
          </router-link>
          <router-link
            :to="{ name: 'ConsejerosPublico' }"
            class="nav-link"
            @click="menuAbierto = false"
          >
            Artistas
          </router-link>
          <router-link
            :to="{ name: 'Inicio', hash: '#comisiones' }"
            class="nav-link"
            @click="menuAbierto = false"
          >
            Comisiones
          </router-link>
          <router-link :to="{ name: 'Login' }" class="cta" @click="menuAbierto = false">
            Ingresar
          </router-link>
        </nav>

        <button
          class="menu-btn"
          :aria-expanded="menuAbierto"
          aria-label="Menú"
          @click="menuAbierto = !menuAbierto"
        >
          <Menu v-if="!menuAbierto" :size="22" />
          <X v-else :size="22" />
        </button>
      </div>
    </header>

    <q-page-container id="contenido">
      <router-view />
    </q-page-container>

    <footer class="footbar">
      <span class="footbar-cenefa" aria-hidden="true" />

      <div class="footbar-inner">
        <div class="foot-marca">
          <div class="foot-nombre">{{ INSTITUCION.nombre }}</div>
          <p class="foot-lema">{{ INSTITUCION.lema }}</p>
          <p class="foot-texto">
            El registro de artistas, comisiones y familias culturales que sostienen la música, la
            danza, el bordado y la palabra de {{ INSTITUCION.ciudad }}.
          </p>
        </div>

        <nav class="foot-col" aria-label="Pie de página">
          <h2>Explorar</h2>
          <router-link :to="{ name: 'Inicio' }">Inicio</router-link>
          <router-link :to="{ name: 'ConsejerosPublico' }">Artistas</router-link>
          <router-link :to="{ name: 'Inicio', hash: '#comisiones' }">Comisiones</router-link>
          <router-link :to="{ name: 'Login' }">Ingresar</router-link>
        </nav>

        <div class="foot-col">
          <h2>Contacto</h2>
          <p v-for="linea in INSTITUCION.contacto.direccion" :key="linea">{{ linea }}</p>
          <p v-for="linea in INSTITUCION.contacto.telefono" :key="linea">{{ linea }}</p>
          <p v-for="linea in INSTITUCION.contacto.correo" :key="linea">{{ linea }}</p>
        </div>

        <div class="foot-col">
          <h2>Atención</h2>
          <p v-for="linea in INSTITUCION.contacto.horario" :key="linea">{{ linea }}</p>
        </div>
      </div>

      <div class="footbar-legal">
        <span>© {{ anio }} {{ INSTITUCION.entidad }}</span>
        <span class="sep" aria-hidden="true">·</span>
        <span>{{ INSTITUCION.region }}</span>
      </div>
    </footer>
  </q-layout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Menu, X } from 'lucide-vue-next'
import { INSTITUCION } from '@/config/institucion'

const anio = new Date().getFullYear()
const menuAbierto = ref(false)

// la barra de scroll se oculta solo mientras se está en el sitio público
onMounted(() => document.documentElement.classList.add('sin-barra-scroll'))
onBeforeUnmount(() => document.documentElement.classList.remove('sin-barra-scroll'))
</script>

<style scoped lang="scss">
.publico-root {
  background: var(--papel);
}

.saltar {
  position: absolute;
  left: -9999px;
  z-index: 2000;
  padding: 12px 18px;
  background: var(--rojo);
  color: #fff;
  font-family: var(--fuente-texto);
  font-weight: 700;
  text-decoration: none;
  border-radius: var(--r-sm);

  &:focus {
    left: 16px;
    top: 16px;
  }
}

/* ---------- topbar ---------- */
.topbar {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: rgba(251, 248, 243, 0.92);
  backdrop-filter: blur(14px);
  border-bottom: 1px solid var(--borde);
}

.topbar-inner {
  max-width: var(--ancho);
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 13px var(--gutter);
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;

  &:focus-visible {
    @include foco;
  }
}

/* tres cañas de siku, de distinto largo */
.brand-mark {
  display: flex;
  align-items: flex-end;
  gap: 3px;
  height: 26px;

  i {
    display: block;
    width: 4px;
    border-radius: 2px;
    background: var(--oro);
    transition: height var(--transicion);
  }

  i:nth-child(1) {
    height: 14px;
  }
  i:nth-child(2) {
    height: 26px;
    background: var(--rojo);
  }
  i:nth-child(3) {
    height: 20px;
    background: var(--lago);
  }
}

.brand:hover .brand-mark i:nth-child(1) {
  height: 20px;
}
.brand:hover .brand-mark i:nth-child(3) {
  height: 26px;
}

.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.15;
}

.brand-nombre {
  @include display(800);
  font-size: 1.15rem;
  color: var(--tinta);
}

.brand-sub {
  @include kicker(var(--tinta-suave));
  font-size: 0.62rem;
  letter-spacing: 0.14em;
  margin-top: 3px;
}

.nav {
  display: flex;
  align-items: center;
  gap: 28px;
}

.nav-link {
  font-family: var(--fuente-texto);
  font-size: var(--t-sm);
  font-weight: 600;
  color: var(--tinta-suave);
  text-decoration: none;
  padding: 6px 0;
  position: relative;
  transition: color var(--transicion);

  &::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 0;
    height: 2px;
    background: var(--rojo);
    transition: width var(--transicion);
  }

  &:hover,
  &.router-link-exact-active {
    color: var(--tinta);
  }

  &:hover::after,
  &.router-link-exact-active::after {
    width: 100%;
  }

  &:focus-visible {
    @include foco;
  }
}

.cta {
  font-family: var(--fuente-texto);
  font-size: var(--t-xs);
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #fff;
  background: var(--rojo);
  text-decoration: none;
  padding: 11px 22px;
  border-radius: var(--r-full);
  transition:
    background var(--transicion),
    transform var(--transicion);

  &:hover {
    background: var(--rojo-hondo);
    transform: translateY(-1px);
  }

  &:focus-visible {
    @include foco;
  }
}

.menu-btn {
  display: none;
  background: none;
  border: 1px solid var(--borde-fuerte);
  border-radius: var(--r-sm);
  color: var(--tinta);
  padding: 7px;
  cursor: pointer;

  &:focus-visible {
    @include foco;
  }
}

@media (max-width: 820px) {
  .menu-btn {
    display: grid;
    place-items: center;
  }

  .nav {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    flex-direction: column;
    align-items: stretch;
    gap: 0;
    background: var(--blanco);
    border-bottom: 1px solid var(--borde);
    box-shadow: var(--sombra);
    padding: 8px var(--gutter) 20px;
    display: none;

    &.abierto {
      display: flex;
    }
  }

  .nav-link {
    padding: 14px 0;
    border-bottom: 1px solid var(--borde);
    font-size: var(--t-md);
  }

  .cta {
    margin-top: 16px;
    text-align: center;
  }
}

/* ---------- footer ---------- */
/* El pie recoge el contacto, que antes vivía en cuatro tarjetas sueltas:
   acá es texto con jerarquía, sin cajas. */
.footbar {
  position: relative;
  background: var(--noche);
  color: rgba(253, 251, 247, 0.72);
  font-family: var(--fuente-texto);
}

.footbar-cenefa {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 30px;
  background-image: var(--patron-oro);
  background-position: 0 -6px;
  background-repeat: repeat-x;
  opacity: 0.85;
}

.footbar-inner {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: clamp(58px, 7vw, 82px) var(--gutter) 34px;
  display: grid;
  grid-template-columns: 1fr;
  gap: 34px;
}

@media (min-width: 760px) {
  .footbar-inner {
    grid-template-columns: 1.6fr 1fr 1.2fr 1fr;
    gap: 40px;
  }
}

.foot-nombre {
  @include display(800);
  font-size: 1.6rem;
  color: var(--sobre-foto);
}

.foot-lema {
  @include kicker(var(--oro-vivo));
  margin: 8px 0 16px;
}

.foot-texto {
  font-size: var(--t-sm);
  line-height: 1.75;
  max-width: 42ch;
  margin: 0;
  color: rgba(253, 251, 247, 0.62);
}

.foot-col {
  display: flex;
  flex-direction: column;
  gap: 9px;

  h2 {
    @include kicker(var(--sobre-foto));
    font-size: 0.66rem;
    margin: 0 0 6px;
  }

  a,
  p {
    color: rgba(253, 251, 247, 0.72);
    text-decoration: none;
    font-size: var(--t-sm);
    line-height: 1.5;
    margin: 0;
    width: fit-content;
  }

  a {
    font-weight: 600;
    transition: color var(--transicion);

    &:hover {
      color: var(--oro-vivo);
    }

    &:focus-visible {
      @include foco;
    }
  }
}

.footbar-legal {
  max-width: var(--ancho);
  margin: 0 auto;
  padding: 20px var(--gutter) 34px;
  border-top: 1px solid rgba(253, 251, 247, 0.14);
  font-size: var(--t-xs);
  color: rgba(253, 251, 247, 0.5);
}

.sep {
  margin: 0 8px;
}
</style>
