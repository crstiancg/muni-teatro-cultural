<template>
  <q-page class="consejo-page publico">
    <header class="cabecera">
      <div class="contenedor">
        <p class="kicker">Institucionalidad cultural</p>
        <h1>Consejo Provincial de Cultura de {{ INSTITUCION.ciudad }}</h1>
        <p class="bajada">
          Un espacio de representación y participación de los agentes culturales de la provincia,
          creado por la Ordenanza Municipal N.° 010-2019-C/MPP del 25 de abril de 2019.
        </p>
      </div>
    </header>

    <article class="contenedor cuerpo">
      <!-- ---------- el Consejo ---------- -->
      <section>
        <h2>Fortalecimiento de la institucionalidad cultural</h2>
        <p>
          El Consejo Provincial de Cultura de {{ INSTITUCION.ciudad }}, instituido mediante la Ordenanza
          Municipal N.° 010-2019-C/MPP, del 25 de abril de 2019, constituye un espacio de representación y
          participación de los agentes culturales de la provincia, orientado a promover, preservar y
          fortalecer las diversas expresiones artísticas y culturales de {{ INSTITUCION.ciudad }}.
        </p>
        <p>
          Entre sus principales gestiones destaca el impulso a la implementación de la
          <strong>Gerencia de Cultura y Turismo</strong> de la Municipalidad Provincial de
          {{ INSTITUCION.ciudad }}, una demanda del sector cultural destinada a fortalecer la
          institucionalidad, la planificación y la gestión de políticas públicas en beneficio de los
          artistas, gestores y organizaciones culturales. Este proceso se desarrolló durante dos gestiones
          consecutivas del Consejo.
        </p>
      </section>

      <!-- ---------- gestiones ---------- -->
      <section>
        <h2>Gestiones del Consejo</h2>
        <div class="gestiones">
          <article v-for="g in GESTIONES" :key="g.anio" class="gestion">
            <div class="gestion-anio">{{ g.anio }}</div>
            <p class="gestion-resolucion">
              Reconocida mediante <strong>{{ g.resolucion }}</strong>, del {{ g.fecha }}.
            </p>
            <dl class="cargos">
              <div v-for="c in g.cargos" :key="c.cargo">
                <dt>{{ c.cargo }}</dt>
                <dd>{{ c.nombre }}</dd>
              </div>
            </dl>
            <p class="gestion-logro">{{ g.logro }}</p>
            <button type="button" class="btn-doc" @click="ver(g.documento)">
              <FileText :size="16" /> Ver resolución
            </button>
          </article>
        </div>
        <p>
          De esta manera, la implementación de la Gerencia de Cultura y Turismo representa un logro de
          continuidad y articulación entre dos gestiones del Consejo Provincial de Cultura, sus comisiones
          especializadas, los representantes de los agentes culturales y la Municipalidad Provincial de
          {{ INSTITUCION.ciudad }}.
        </p>
      </section>

      <!-- ---------- Gerencia ---------- -->
      <section>
        <h2>Gerencia de Cultura y Turismo</h2>
        <p>
          La Gerencia de Cultura y Turismo es el órgano de línea de la Municipalidad Provincial de
          {{ INSTITUCION.ciudad }} encargado de promover, planificar y ejecutar políticas, programas y
          proyectos relacionados con el desarrollo cultural, artístico, patrimonial y turístico de la
          provincia. Su implementación constituye un avance en el fortalecimiento de la institucionalidad
          cultural de {{ INSTITUCION.ciudad }}, impulsado mediante gestiones administrativas y la
          participación del Consejo Provincial de Cultura.
        </p>

        <h3>Gestión 2026</h3>
        <ul class="equipo">
          <li v-for="m in EQUIPO_GERENCIA" :key="m.cargo">
            <span class="equipo-cargo">{{ m.cargo }}</span>
            <span class="equipo-nombre">{{ m.nombre }}</span>
          </li>
        </ul>

        <div class="contacto">
          <p>
            <MapPin :size="16" aria-hidden="true" />
            <span>
              {{ INSTITUCION.atencion.direccion }}, {{ INSTITUCION.ciudad }} —
              {{ INSTITUCION.atencion.referencia }}
            </span>
          </p>
          <p>
            <Phone :size="16" aria-hidden="true" />
            <a :href="`tel:${INSTITUCION.atencion.telefono.replace(/[^\d+]/g, '')}`">{{ INSTITUCION.atencion.telefono }}</a>
          </p>
          <p>
            <Facebook :size="16" aria-hidden="true" />
            <a :href="INSTITUCION.atencion.facebook" target="_blank" rel="noopener">Facebook de la Gerencia</a>
          </p>
        </div>
      </section>

      <!-- ---------- reconocimiento ---------- -->
      <section class="reconocimiento">
        <h2>Reconocimiento a la labor cultural</h2>
        <p>
          El <strong>26 de octubre de 2024</strong>, los integrantes del Consejo Provincial de Cultura de
          {{ INSTITUCION.ciudad }} recibieron un reconocimiento otorgado por el congresista Flavio Cruz
          Mamani, en mérito a su contribución a la preservación, promoción y difusión del patrimonio
          cultural de la provincia. Este reconocimiento destacó el compromiso de los representantes
          culturales con el fortalecimiento de la identidad puneña y su labor en beneficio del desarrollo
          cultural de la región.
        </p>
      </section>

      <!-- ---------- registro ---------- -->
      <section>
        <h2>Registro de Agentes Culturales</h2>
        <p>
          El Registro y Reconocimiento de los Agentes Culturales, aprobado mediante la Ordenanza Municipal
          N.° 010-2019-C/MPP, constituye una herramienta fundamental para identificar, reconocer y promover
          la participación de artistas, gestores, colectivos y organizaciones culturales de la provincia.
          Su fortalecimiento permite consolidar una comunidad cultural organizada, representativa y
          participativa, capaz de contribuir a la formulación de políticas públicas, el desarrollo de
          proyectos y la promoción de las industrias culturales y creativas de {{ INSTITUCION.ciudad }}.
        </p>
        <p>
          Cada agente cultural aprobado recibe un código de registro y una ficha con un código QR que
          cualquiera puede escanear para confirmar que su inscripción está vigente.
        </p>
        <router-link :to="{ name: 'ConsejerosPublico' }" class="btn-principal">
          Conocer a la comunidad <ArrowRight :size="17" />
        </router-link>
      </section>

      <!-- ---------- documentos ---------- -->
      <section>
        <h2>Resoluciones y ordenanzas</h2>
        <div v-if="cargandoDocs" class="text-center q-pa-md"><q-spinner size="28px" /></div>
        <p v-else-if="!documentos.length" class="vacio">No se pudieron cargar los documentos. Intenta más tarde.</p>
        <ul v-else class="documentos">
          <li v-for="d in documentos" :key="d.slug" class="documento">
            <FileText :size="22" class="documento-icono" aria-hidden="true" />
            <div class="documento-texto">
              <strong>{{ d.titulo }}</strong>
              <span v-if="d.fecha || d.descripcion" class="documento-detalle">
                {{ [d.fecha && fechaLarga(d.fecha), d.descripcion].filter(Boolean).join(' · ') }}
              </span>
            </div>
            <div class="documento-acciones">
              <button type="button" class="btn-doc" @click="ver(d.slug)"><Eye :size="16" /> Ver</button>
              <a :href="d.url_original" target="_blank" rel="noopener" class="enlace-original" title="Ver en el portal oficial">
                <ExternalLink :size="16" /><span class="sr-only">Ver en el portal oficial</span>
              </a>
            </div>
          </li>
        </ul>
      </section>
    </article>

    <VisorDocumento :documento="abierto" @cerrar="abierto = null" />
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ArrowRight, Eye, ExternalLink, Facebook, FileText, MapPin, Phone } from 'lucide-vue-next'
import { INSTITUCION, tituloPagina } from '@/config/institucion'
import DocumentoService from '@/services/DocumentoService'
import VisorDocumento from '@/components/VisorDocumento.vue'

// Contenido institucional (texto oficial del Consejo). documento = slug de
// backend/config/documentos.php: el PDF se abre en el visor del sitio.
const GESTIONES = [
  {
    anio: 2024,
    resolucion: 'Resolución de Alcaldía N.° 277-2024-MPP/A',
    fecha: '12 de marzo de 2024',
    documento: 'ra-277-2024',
    cargos: [
      { cargo: 'Presidente', nombre: 'Melvin Otto Quispe Churquipa' },
      { cargo: 'Primera vicepresidenta', nombre: 'Martha Viviana Herrada de la Cruz' },
      { cargo: 'Segundo vicepresidente', nombre: 'Edward William Mendoza Aduvire' },
    ],
    logro: 'Impulsó las gestiones y solicitudes para concretar la implementación de la Gerencia de Cultura y Turismo.',
  },
  {
    anio: 2025,
    resolucion: 'Resolución de Alcaldía N.° 0571-2025-MPP/A',
    fecha: '5 de mayo de 2025',
    documento: 'ra-0571-2025',
    cargos: [
      { cargo: 'Presidente', nombre: 'Gustavo Andrés Zea Zea' },
      { cargo: 'Primer vicepresidente', nombre: 'Carlos Daniel Acero Velásquez' },
      { cargo: 'Segunda vicepresidenta', nombre: 'Flor Ángela Quispe Calsin' },
    ],
    logro: 'Dio continuidad al fortalecimiento institucional y consolidó la implementación de la Gerencia de Cultura y Turismo.',
  },
]

const EQUIPO_GERENCIA = [
  { cargo: 'Gerente de Cultura y Turismo', nombre: 'Gustavo Andrés Zea Zea' },
  { cargo: 'Subgerente de Cultura, Arte y Folklore', nombre: 'Reynaldo Condori Escarcena' },
  { cargo: 'Subgerente de Turismo y Comercio Exterior', nombre: 'Briseida Daysi Pauro Pino' },
  { cargo: 'Subgerente de Patrimonio Cultural y Complejos Culturales', nombre: 'Glenny Xiomara Ávila Flor' },
]

const documentos = ref([])
const cargandoDocs = ref(true)
const abierto = ref(null)

// por slug: las gestiones abren su resolución aunque la lista todavía no cargó
function ver(slug) {
  abierto.value = documentos.value.find((d) => d.slug === slug) ?? { slug, titulo: 'Documento', fecha: null, url_original: null }
}

function fechaLarga(iso) {
  const [a, m, d] = iso.split('-').map(Number)
  return new Date(a, m - 1, d).toLocaleDateString('es-PE', { dateStyle: 'long' })
}

onMounted(async () => {
  document.title = tituloPagina('Consejo Provincial de Cultura y Gerencia de Cultura y Turismo')
  try {
    documentos.value = await DocumentoService.listar()
  } catch {
    documentos.value = []
  } finally {
    cargandoDocs.value = false
  }
})
</script>

<style scoped lang="scss">
.consejo-page {
  background: var(--papel);
  color: var(--tinta);
  padding-bottom: var(--seccion-y);
}

.contenedor {
  max-width: 880px;
  margin: 0 auto;
  padding: 0 var(--gutter);
}

.cabecera {
  background: var(--noche);
  color: var(--sobre-foto);
  padding: clamp(48px, 7vw, 80px) 0 clamp(32px, 5vw, 48px);
}

.kicker {
  @include kicker(var(--oro-vivo));
  margin: 0 0 8px;
}

h1 {
  @include display(800);
  font-size: var(--t-xl);
  margin: 0 0 10px;
}

.bajada {
  margin: 0;
  opacity: 0.8;
  line-height: 1.6;
  max-width: 640px;
}

.cuerpo {
  padding-top: clamp(32px, 5vw, 48px);
  line-height: 1.75;
  color: var(--tinta-suave);

  section {
    margin-bottom: clamp(36px, 5vw, 52px);
  }

  h2 {
    @include display(700);
    font-size: var(--t-lg);
    color: var(--tinta);
    margin: 0 0 12px;
    line-height: 1.2;
  }

  h3 {
    @include kicker(var(--rojo));
    margin: 24px 0 10px;
  }

  p {
    margin: 0 0 12px;
  }

  strong {
    color: var(--tinta);
  }
}

/* ---------- gestiones: dos tarjetas lado a lado ---------- */
.gestiones {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin: 8px 0 20px;

  @media (max-width: 700px) {
    grid-template-columns: 1fr;
  }
}

.gestion {
  display: flex;
  flex-direction: column;
  background: var(--blanco);
  border: 1px solid var(--borde);
  border-radius: 14px;
  padding: 20px;
  line-height: 1.55;
}

.gestion-anio {
  @include display(800);
  font-size: 2rem;
  color: var(--rojo);
  line-height: 1;
  margin-bottom: 8px;
}

.gestion-resolucion {
  font-size: var(--t-sm);
}

.cargos {
  margin: 4px 0 12px;

  div {
    padding: 6px 0;
    border-top: 1px solid var(--borde);
  }

  dt {
    font-size: var(--t-xs);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--tinta-suave);
  }

  dd {
    margin: 0;
    color: var(--tinta);
    font-weight: 600;
  }
}

.gestion-logro {
  font-size: var(--t-sm);
  flex: 1;
}

/* ---------- equipo de la Gerencia ---------- */
.equipo {
  list-style: none;
  padding: 0;
  margin: 0 0 20px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;

  @media (max-width: 700px) {
    grid-template-columns: 1fr;
  }

  li {
    background: var(--blanco);
    border: 1px solid var(--borde);
    border-radius: 12px;
    padding: 12px 14px;
    line-height: 1.4;
  }
}

.equipo-cargo {
  display: block;
  font-size: var(--t-xs);
  color: var(--tinta-suave);
}

.equipo-nombre {
  display: block;
  color: var(--tinta);
  font-weight: 700;
}

.contacto p {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 6px;

  svg {
    flex-shrink: 0;
    margin-top: 5px;
    color: var(--rojo);
  }
}

.reconocimiento {
  border-left: 3px solid var(--oro);
  padding-left: 18px;
}

/* ---------- documentos ---------- */
.documentos {
  list-style: none;
  padding: 0;
  margin: 0;
  border: 1px solid var(--borde);
  border-radius: 14px;
  background: var(--blanco);
  overflow: hidden;
}

.documento {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  line-height: 1.45;

  & + & {
    border-top: 1px solid var(--borde);
  }

  @media (max-width: 560px) {
    flex-wrap: wrap;
  }
}

.documento-icono {
  flex-shrink: 0;
  color: var(--rojo);
}

.documento-texto {
  flex: 1;
  min-width: 0;

  strong {
    display: block;
  }
}

.documento-detalle {
  display: block;
  font-size: var(--t-sm);
}

.documento-acciones {
  display: flex;
  align-items: center;
  gap: 6px;

  @media (max-width: 560px) {
    width: 100%;
    justify-content: flex-end;
  }
}

/* ---------- botones ---------- */
.btn-doc {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  align-self: flex-start;
  border: 1px solid var(--borde-fuerte);
  background: var(--blanco);
  color: var(--tinta);
  border-radius: 999px;
  padding: 7px 14px;
  font: inherit;
  font-size: var(--t-sm);
  font-weight: 600;
  cursor: pointer;
  transition: background var(--transicion), border-color var(--transicion);

  &:hover {
    background: var(--crema);
    border-color: var(--tinta-tenue);
  }

  &:focus-visible {
    @include foco;
  }
}

.enlace-original {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  color: var(--tinta-suave);

  &:hover {
    background: var(--crema);
    color: var(--tinta);
  }

  &:focus-visible {
    @include foco;
  }
}

.btn-principal {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-top: 6px;
  background: var(--rojo);
  color: #fff;
  border-radius: 999px;
  padding: 11px 20px;
  font-weight: 700;
  text-decoration: none;
  transition: background var(--transicion);

  &:hover {
    background: var(--rojo-hondo);
  }

  &:focus-visible {
    @include foco;
  }
}

.vacio {
  font-style: italic;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
}
</style>
