<template>
  <q-page class="privacidad-page">
    <header class="cabecera">
      <div class="contenedor">
        <p class="kicker">Protección de datos personales</p>
        <h1>Política de privacidad</h1>
        <p class="bajada">
          Cómo el {{ INSTITUCION.nombre }} trata los datos personales de artistas, agentes culturales e
          integrantes de agrupaciones, conforme a la Ley N.° 29733 y su reglamento.
        </p>
      </div>
    </header>

    <!-- PENDIENTE: que el área legal de la municipalidad revise y apruebe este texto -->
    <article class="contenedor texto">
      <section>
        <h2>1. Responsable</h2>
        <p>
          El responsable del tratamiento es el {{ INSTITUCION.entidad }} ({{ INSTITUCION.nombre }}),
          con domicilio en {{ INSTITUCION.contacto.direccion.join(', ') }}.
        </p>
      </section>

      <section>
        <h2>2. Qué datos tratamos</h2>
        <ul>
          <li>
            <strong>Artistas y agentes culturales registrados:</strong> DNI, nombres y apellidos, fecha de
            nacimiento, género, estado civil, dirección, ubigeo, celular, correo, comisión, foto, biografía,
            redes sociales, formación académica, capacitaciones y actividades.
          </li>
          <li>
            <strong>Integrantes de agrupaciones:</strong> DNI, nombres, apellidos y rol dentro de la
            agrupación. Los registra el representante de la agrupación, quien declara contar con su
            consentimiento.
          </li>
          <li>
            <strong>Visitas al portal:</strong> solo se guarda cuántas veces se visita cada perfil por día. Para no
            contar dos veces la misma visita, durante 30 minutos se conserva un código generado a partir de la
            dirección IP y el navegador (un resumen, no la dirección en sí), que luego se descarta.
          </li>
          <li>
            <strong>Inicio de sesión:</strong> para frenar intentos de adivinar contraseñas, los intentos fallidos se
            registran durante un minuto junto con el correo y la dirección IP desde donde se hicieron.
          </li>
        </ul>
      </section>

      <section>
        <h2>3. Para qué los usamos</h2>
        <p>
          Para llevar el registro municipal de agentes culturales, revisar y publicar sus perfiles y los de sus
          agrupaciones, y difundir la actividad cultural de {{ INSTITUCION.ciudad }}.
        </p>
      </section>

      <section>
        <h2>4. Qué se publica en el portal</h2>
        <ul>
          <li>
            <strong>De los artistas aprobados:</strong> nombre, comisión, foto, biografía, redes sociales,
            actividades públicas, y el celular y el correo de contacto.
          </li>
          <li><strong>De los integrantes de agrupaciones:</strong> solo nombre y rol.</li>
          <li>
            <strong>Nunca se publica el DNI</strong>, ni la dirección, la fecha de nacimiento u otros datos de la
            ficha.
          </li>
        </ul>
        <p>Un perfil solo aparece en el portal después de que un administrador lo revisa y aprueba.</p>
      </section>

      <section>
        <h2>5. Con quién los compartimos</h2>
        <p>
          No vendemos ni cedemos datos personales. Para autocompletar nombres a partir del DNI se consulta el
          servicio de identificación de RENIEC a través de un proveedor autorizado.
        </p>
      </section>

      <section>
        <h2>6. Tus derechos</h2>
        <p>
          Puedes ejercer tus derechos de <strong>acceso, rectificación, cancelación y oposición</strong>
          (derechos ARCO). Los artistas registrados pueden corregir sus datos desde su perfil. Para cualquier
          otra solicitud, incluida la de un integrante que quiera ser retirado de una agrupación, escribe a
          {{ INSTITUCION.contacto.correo.join(', ') }} indicando tu nombre y DNI.
        </p>
      </section>

      <section>
        <h2>7. Seguridad</h2>
        <p>
          El acceso al sistema es con usuario y contraseña, con límite de intentos, y cada persona solo puede
          ver y modificar lo que le corresponde según su rol.
        </p>
      </section>

      <p class="actualizado">Última actualización: {{ ACTUALIZADO }}</p>
    </article>
  </q-page>
</template>

<script setup>
import { onMounted } from 'vue'
import { INSTITUCION } from '@/config/institucion'

// cambiar cuando se modifique el texto
const ACTUALIZADO = '1 de octubre de 2026'

onMounted(() => {
  document.title = `Política de privacidad · ${INSTITUCION.nombre}`
})
</script>

<style scoped lang="scss">
.privacidad-page {
  background: var(--papel);
  color: var(--tinta);
  padding-bottom: var(--seccion-y);
}

.contenedor {
  max-width: 820px;
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
}

.texto {
  padding-top: clamp(32px, 5vw, 48px);
  line-height: 1.75;
  color: var(--tinta-suave);

  section {
    margin-bottom: 32px;
  }

  h2 {
    @include display(700);
    font-size: var(--t-md);
    color: var(--tinta);
    margin: 0 0 8px;
  }

  strong {
    color: var(--tinta);
  }

  ul {
    padding-left: 1.2em;
    margin: 0 0 8px;
  }

  li {
    margin-bottom: 6px;
  }
}

.actualizado {
  font-size: var(--t-sm);
  font-style: italic;
}
</style>
