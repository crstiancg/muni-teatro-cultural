<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Ficha de registro {{ $codigo }}</title>
@php
    // Paleta por tema (config registro.tema / REGISTRO_TEMA). Solo cambia el color:
    // el layout es el mismo, así las dos versiones siempre entran en una hoja.
    $t = [
        'negro' => ['acento' => '#111111', 'linea' => '#c4c4c4', 'suave' => '#f1f1f1', 'col' => '#2b2b2b', 'etiqueta' => '#6a6a6a', 'codigo' => '#111111'],
        'azul' => ['acento' => '#004173', 'linea' => '#a9bdd0', 'suave' => '#e8eff6', 'col' => '#0d3558', 'etiqueta' => '#5b6c7c', 'codigo' => '#004173'],
    ][$config['tema']] ?? null;
    $t ??= ['acento' => '#004173', 'linea' => '#a9bdd0', 'suave' => '#e8eff6', 'col' => '#0d3558', 'etiqueta' => '#5b6c7c', 'codigo' => '#004173'];
@endphp
<style>
    /* dompdf: CSS 2.1 y tablas (sin flex ni grid). Formato de formulario oficial:
       grilla bordeada, etiqueta chica arriba del valor, bandas por sección.
       REGLA: todo entra en UNA hoja A4 (las listas se cortan en 3 + "y N más"). */
    @page { margin: 13mm 13mm 17mm; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.2pt; color: #1b1b1b; line-height: 1.24; }
    table { width: 100%; border-collapse: collapse; }
    td, th { vertical-align: top; }

    /* marco doble y marca de agua: lo que distingue un documento de una pantalla impresa */
    .marco { position: fixed; top: -6mm; left: -6mm; right: -6mm; bottom: -10mm; border: 1.6pt solid {{ $t['acento'] }}; border-radius: 10px; }
    .marco-int { position: fixed; top: -4.8mm; left: -4.8mm; right: -4.8mm; bottom: -8.8mm; border: .5pt solid {{ $t['acento'] }}; border-radius: 7px; }
    .agua { position: fixed; top: 80mm; left: 42mm; width: 100mm; height: 100mm; opacity: .06; }

    /* ---------- membrete ---------- */
    .membrete td { vertical-align: middle; }
    .membrete .logo { width: 25mm; }
    .membrete .logo img { width: 20mm; height: 20mm; }
    .membrete .centro { text-align: center; padding: 0 3mm; }
    .entidad { font-family: 'DejaVu Serif', serif; font-size: 12pt; font-weight: bold; letter-spacing: .4pt; }
    .dependencia { font-family: 'DejaVu Serif', serif; font-size: 8.5pt; color: #444; }

    /* recuadro del N.° de registro, arriba a la derecha como en los formatos del Estado */
    /* mismo criterio que las secciones: cabecera y cuerpo apilados */
    .nro-cab { background: {{ $t['acento'] }}; border: 1pt solid {{ $t['acento'] }}; border-radius: 10px 10px 0 0; color: #fff; text-align: center; font-size: 6.5pt; font-weight: bold; letter-spacing: .8pt; padding: 2pt 4pt; }
    .nro-cuerpo { border: 1pt solid {{ $t['acento'] }}; border-top: 0; border-radius: 0 0 10px 10px; }
    .nro td { text-align: center; padding: 2pt 4pt; }
    .nro .codigo { font-family: 'DejaVu Sans Mono', monospace; font-size: 15pt; font-weight: bold; color: {{ $t['codigo'] }}; letter-spacing: 1pt; padding: 3pt 4pt; }
    .nro .estado { border-top: .5pt solid {{ $t['linea'] }}; font-size: 7.5pt; font-weight: bold; letter-spacing: .6pt; }
    .vigente { color: #1d6b35; }
    .no-vigente { color: #9b1c2c; }
    .en-revision { color: #8a5a00; }

    .regla { border-top: 1.4pt solid {{ $t['acento'] }}; border-bottom: .5pt solid {{ $t['acento'] }}; height: 1.2pt; margin: 4pt 0 5pt; }
    .titulo { text-align: center; font-family: 'DejaVu Serif', serif; font-size: 13.5pt; font-weight: bold; letter-spacing: .6pt; }
    .subtitulo { text-align: center; font-size: 7.5pt; color: #555; font-style: italic; margin: 1pt 0 4pt; }

    /* ---------- secciones: caja redondeada + grilla interna ----------
       dompdf no redondea tablas con border-collapse, así que la caja pone el borde
       exterior redondeado y las celdas solo dibujan las líneas internas
       (arriba e izquierda; la primera columna no lleva línea izquierda). */
    /* banda y cuerpo son dos piezas APILADAS, no una dentro de otra: cada esquina
       redondeada la dibuja un solo elemento. Anidadas (banda redondeada dentro de una
       caja redondeada) dompdf deja una ranura blanca en la curva y un escalón en el borde. */
    .caja { margin-top: 3.5pt; page-break-inside: avoid; }
    .banda { background: {{ $t['acento'] }}; border: .7pt solid {{ $t['acento'] }}; border-radius: 10px 10px 0 0; color: #fff; font-size: 7.6pt; font-weight: bold; letter-spacing: .7pt; padding: 2.4pt 6pt; }
    .cuerpo { border: .7pt solid {{ $t['acento'] }}; border-top: 0; border-radius: 0 0 10px 10px; }
    .sec td, .sec th { border: 0; border-top: .5pt solid {{ $t['linea'] }}; border-left: .5pt solid {{ $t['linea'] }}; padding: 1.5pt 4.5pt; }
    .sec td:first-child, .sec th:first-child { border-left: 0; }
    /* la primera fila va pegada a la banda: sin línea arriba */
    .sec tr:first-child td, .sec tr:first-child th { border-top: 0; }
    .sec .col { background: {{ $t['suave'] }}; color: {{ $t['col'] }}; font-size: 6.4pt; font-weight: bold; letter-spacing: .4pt; text-transform: uppercase; text-align: left; }
    .sec .borde-izq { border-left: .5pt solid {{ $t['linea'] }}; }
    .l { display: block; font-size: 6.2pt; color: {{ $t['etiqueta'] }}; text-transform: uppercase; letter-spacing: .4pt; margin-bottom: .5pt; }
    .v { font-size: 8.6pt; }
    .anio { text-align: center; width: 11mm; }
    .vacio { color: #858585; font-style: italic; }
    .mas { text-align: right; color: {{ $t['etiqueta'] }}; font-size: 6.9pt; font-style: italic; }

    .foto { width: 29mm; text-align: center; vertical-align: middle !important; padding: 3pt !important; }
    .foto img { width: 19mm; height: 25mm; border: .5pt solid {{ $t['linea'] }}; }
    .foto .sin { display: inline-block; width: 19mm; height: 25mm; border: .5pt dashed {{ $t['linea'] }}; border-radius: 6px; color: #999; font-size: 7pt; padding-top: 13mm; }

    .qr { width: 27mm; text-align: center; vertical-align: middle !important; }
    .qr img { width: 23mm; height: 23mm; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 7.1pt; color: {{ $t['codigo'] }}; }

    /* ---------- firmas ---------- */
    .declaracion { font-size: 6.9pt; color: #555; margin-top: 5pt; }
    .firmas { margin-top: 8mm; page-break-inside: avoid; }
    .firmas td { width: 50%; text-align: center; padding: 0 10mm; }
    .firmas .linea { border-top: .6pt solid #1b1b1b; padding-top: 2pt; font-size: 7.5pt; font-weight: bold; }
    .firmas .sub { font-size: 6.7pt; color: #666; }

    .pie { position: fixed; bottom: -4.5mm; left: 0; right: 0; font-size: 6.2pt; color: #777; }
    .pie td { vertical-align: bottom; }
</style>
</head>
<body>
@php
    $zona = $config['zona_horaria'];
    $fecha = fn ($f) => $f?->copy()->timezone($zona)->format('d/m/Y');
    $claseEstado = match ($estado) { 'Vigente' => 'vigente', 'En revisión' => 'en-revision', default => 'no-vigente' };
    $vacio = fn ($v, $texto = 'No registrado') => filled($v) ? e($v) : '<span class="vacio">' . $texto . '</span>';
@endphp

<div class="marco"></div>
<div class="marco-int"></div>
@if ($logo)<img class="agua" src="{{ $logo }}" alt="">@endif

<table class="pie">
    <tr>
        <td>Sistema del Registro de Agentes Culturales ({{ $config['prefijo'] }}) · {{ $config['entidad'] }}</td>
        <td style="text-align: right">Ficha {{ $codigo }} · Emitida el {{ $emitido_en->copy()->timezone($zona)->format('d/m/Y \a \l\a\s H:i') }}</td>
    </tr>
</table>

{{-- ================= membrete ================= --}}
<table class="membrete">
    <tr>
        <td class="logo">@if ($logo)<img src="{{ $logo }}" alt="Consejo Provincial de Cultura">@endif</td>
        <td class="centro">
            <div class="entidad">{{ mb_strtoupper($config['entidad']) }}</div>
            <div class="dependencia">{{ $config['gerencia'] }}</div>
            <div class="dependencia">{{ $config['consejo'] }}</div>
        </td>
        <td style="width: 46mm">
            <div class="nro-cab">N.° DE REGISTRO</div>
            <div class="nro-cuerpo">
                <table class="nro">
                    <tr><td class="codigo">{{ $codigo }}</td></tr>
                    <tr><td class="estado {{ $claseEstado }}">{{ mb_strtoupper($estado) }}</td></tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<div class="regla"></div>
<div class="titulo">FICHA DE REGISTRO DE AGENTE CULTURAL</div>
<div class="subtitulo">{{ $config['nombre_registro'] }}</div>

{{-- ================= I. identificación ================= --}}
<div class="caja"><div class="banda">I. IDENTIFICACIÓN DEL AGENTE CULTURAL</div><div class="cuerpo"><table class="sec">
    {{-- 3 filas de 3 datos: alcanza el alto de la foto sin estirar la sección --}}
    <tr>
        <td colspan="3"><span class="l">Apellidos y nombres</span><span class="v"><strong>{{ $persona->nombre_completo }}</strong></span></td>
        <td class="foto" rowspan="{{ $agrupaciones->isNotEmpty() ? 4 : 3 }}">
            @if ($foto)
                <img src="{{ $foto }}" alt="Foto">
            @else
                <span class="sin">FOTO</span>
            @endif
        </td>
    </tr>
    <tr>
        <td style="width: 22%"><span class="l">DNI</span><span class="v">{{ $persona->dni }}</span></td>
        <td style="width: 34%"><span class="l">Tipo de agente</span><span class="v">{{ $tipo_agente }}</span></td>
        <td><span class="l">Residencia</span><span class="v">{!! $vacio($distrito) !!}</span></td>
    </tr>
    <tr>
        <td><span class="l">Comisión principal</span><span class="v">{!! $vacio($comision) !!}</span></td>
        <td><span class="l">Especialidad</span><span class="v">{!! $vacio($especialidad) !!}</span></td>
        <td><span class="l">Comisión alternativa</span><span class="v">{!! $vacio($comision_alternativa, 'Ninguna') !!}</span></td>
    </tr>
    @if ($agrupaciones->isNotEmpty())
        <tr>
            <td colspan="3"><span class="l">Integra las agrupaciones</span><span class="v">{{ $agrupaciones->implode(', ') }}</span></td>
        </tr>
    @endif
</table></div></div>

{{-- ================= II. formación ================= --}}
<div class="caja"><div class="banda">II. FORMACIÓN ACADÉMICA</div><div class="cuerpo"><table class="sec">
    <tr>
        <th class="col" style="width: 25%">Nivel / grado</th>
        <th class="col" style="width: 27%">Carrera o especialidad</th>
        <th class="col">Institución</th>
        <th class="col anio">Año</th>
    </tr>
    @forelse ($formaciones as $f)
        <tr>
            <td>{{ $f['nivel'] ?: '—' }}</td>
            <td>{{ $f['carrera'] ?: '—' }}</td>
            <td>{{ $f['institucion'] }}</td>
            <td class="anio">{{ $f['anio'] ?? '—' }}</td>
        </tr>
    @empty
        <tr><td colspan="4" class="vacio">No registra formación académica (formación autodidacta o tradicional).</td></tr>
    @endforelse
    @if ($total_formaciones > $formaciones->count())
        <tr><td colspan="4" class="mas">y {{ $total_formaciones - $formaciones->count() }} registro(s) más en su portafolio</td></tr>
    @endif
</table></div></div>

{{-- ================= III. capacitaciones ================= --}}
<div class="caja"><div class="banda">III. CAPACITACIONES Y RECONOCIMIENTOS</div><div class="cuerpo"><table class="sec">
    <tr>
        <th class="col" style="width: 17%">Tipo</th>
        <th class="col" style="width: 42%">Denominación</th>
        <th class="col">Institución</th>
        <th class="col anio">Año</th>
    </tr>
    @forelse ($capacitaciones as $c)
        <tr>
            <td>{{ $c['tipo'] }}</td>
            <td>{{ $c['nombre'] }}@if ($c['horas']) <span style="color: #5b6b72">({{ $c['horas'] }} h)</span>@endif</td>
            <td>{!! $vacio($c['institucion'], '—') !!}</td>
            <td class="anio">{{ $c['anio'] ?? '—' }}</td>
        </tr>
    @empty
        <tr><td colspan="4" class="vacio">No registra capacitaciones ni reconocimientos.</td></tr>
    @endforelse
    @if ($total_capacitaciones > $capacitaciones->count())
        <tr><td colspan="4" class="mas">y {{ $total_capacitaciones - $capacitaciones->count() }} registro(s) más en su portafolio</td></tr>
    @endif
</table></div></div>

{{-- ================= IV. trayectoria ================= --}}
<div class="caja"><div class="banda">IV. TRAYECTORIA Y ACTIVIDADES CULTURALES</div><div class="cuerpo"><table class="sec">
    <tr>
        <td style="width: 30%">
            <span class="l">Trayectoria acreditada desde</span>
            <span class="v">{!! $trayectoria_desde ? e($trayectoria_desde) : '<span class="vacio">Sin certificados con fecha</span>' !!}</span>
            <span class="l" style="margin-top: 3pt">Actividades públicas</span>
            <span class="v">{{ $total_actividades }}</span>
        </td>
        <td>
            <span class="l">Actividades realizadas (más recientes)</span>
            @forelse ($actividades as $a)
                <div class="v">{{ $loop->iteration }}. {{ $a['titulo'] }}</div>
            @empty
                <span class="vacio">No registra actividades públicas.</span>
            @endforelse
            @if ($total_actividades > $actividades->count())
                <div class="mas" style="text-align: left">y {{ $total_actividades - $actividades->count() }} más en su portafolio</div>
            @endif
        </td>
    </tr>
</table></div></div>

{{-- ================= V. registro y verificación ================= --}}
<div class="caja"><div class="banda">V. DATOS DEL REGISTRO Y VERIFICACIÓN</div><div class="cuerpo"><table class="sec">
    <tr>
        <td class="qr" rowspan="2"><img src="{{ $qr }}" alt="QR de verificación"></td>
        <td style="width: 18%"><span class="l">Fecha de inscripción</span><span class="v">{{ $fecha($fecha_inscripcion) ?? '—' }}</span></td>
        <td style="width: 18%"><span class="l">Fecha de validación</span><span class="v">{{ $fecha($fecha_validacion) ?? '—' }}</span></td>
        <td style="width: 19%"><span class="l">Última actualización</span><span class="v">{{ $fecha($ultima_actualizacion) ?? '—' }}</span></td>
        <td><span class="l">Validado por</span><span class="v">{!! $vacio($validado_por, '—') !!}</span></td>
    </tr>
    <tr>
        {{-- primera en el HTML pero segunda a la vista (el QR ocupa la columna 1): lleva línea izquierda --}}
        <td colspan="4" class="borde-izq">
            <span class="l">Consulta de autenticidad</span>
            <div style="margin-bottom: 2pt">
                Escanee el código QR o ingrese al enlace para confirmar que la inscripción
                <strong>{{ $codigo }}</strong> se encuentra vigente. Si el estado consultado difiere
                del impreso, prevalece el del sistema.
            </div>
            <div class="mono">{{ $verificacion_url }}</div>
            @if ($portafolio_url)
                <div style="margin-top: 2pt"><span style="color: #5b6b72">Portafolio público:</span> <span class="mono">{{ $portafolio_url }}</span></div>
            @endif
        </td>
    </tr>
</table></div></div>

<div class="declaracion">
    El agente cultural declara que la información consignada es veraz y autoriza su verificación por la
    {{ $config['entidad'] }}.
</div>

<table class="firmas">
    <tr>
        <td>
            <div class="linea">{{ $persona->nombre_completo }}</div>
            <div class="sub">Agente cultural · DNI {{ $persona->dni }}</div>
        </td>
        <td>
            <div class="linea">Firma y sello</div>
            <div class="sub">{{ $config['gerencia'] }}</div>
        </td>
    </tr>
</table>

</body>
</html>
