<?php
/**
 * ACTUALIZACIÓN v10 — EuryGo: Versión ES del curso Spanish Language & Flamenco
 *
 * Inserta el par en español del curso 'spanish-language-flamenco' (creado en
 * update_v8 solo en EN). Vincula ambos cursos por cursos.traduccion_id para
 * que el switch ES/EN del header navegue entre ellos.
 *
 *  - Slug ES: espanol-y-flamenco
 *  - Slug EN: spanish-language-flamenco (preexistente)
 *  - Mismas 2 ediciones: 5-16 oct 2026 + 1-12 feb 2027
 *  - Mismo precio (480 €), duración (10 días), plazas (15)
 *  - Programa de 40 filas (10 días x 4 bloques) en español
 *  - Bloques OPTIONAL (Cultural + Accommodation) en español al final de
 *    cursos.descripcion, entre sentinels para que cursos/curso.php los
 *    separe correctamente al renderizar What is Included
 *
 * USO:
 *   /admin/setup/update_v10.php                       → PREVIEW (no escribe)
 *   /admin/setup/update_v10.php?mode=apply&confirm=YES  → APPLY (transacción)
 *
 * Requiere sesión admin. BORRAR del servidor tras ejecutar.
 *
 * ⚠ Antes de APPLY, hacer backup de la BD:
 *     /admin/setup/backup-db.php?tablas=cursos,cursos_programa,cursos_ediciones
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

$db   = get_db();
$mode = ($_GET['mode'] ?? 'preview') === 'apply' && ($_GET['confirm'] ?? '') === 'YES' ? 'apply' : 'preview';

const SLUG_ES = 'espanol-y-flamenco';
const SLUG_EN = 'spanish-language-flamenco';
const MARK_START = '<!-- EURYGO_OPTIONALS_V8_START -->';
const MARK_END   = '<!-- EURYGO_OPTIONALS_V8_END -->';

// ───────────────────────────────────────────────────────────────────────────
//  CONTENIDO ES
// ───────────────────────────────────────────────────────────────────────────

function descripcion_flamenco_es(): string {
    return <<<'HTML'
<h2>Descripción del curso</h2>
<p>Un curso de dos semanas que combina el aprendizaje del español como lengua extranjera con la inmersión en el flamenco — la expresión cultural más viva de Andalucía. Dos disciplinas que se complementan: la lengua, y el arte que mejor la expresa.</p>

<p>Durante dos semanas en Jerez de la Frontera — la cuna misma del flamenco — trabajarás cada día con el español que de verdad se usa en un centro educativo español: vocabulario del aula, conversaciones de sala de profesores, comunicación con familias y el día a día de un docente en activo. Y cada jornada se cierra con un taller práctico de flamenco: ritmo, cuerpo y escena, impartido por artistas flamencos profesionales de Jerez.</p>

<h3>¿Por qué combinar español con flamenco?</h3>
<p>Porque el flamenco aquí no es un extra turístico — es una herramienta pedagógica real. El ritmo, la expresión corporal y la improvisación son competencias transferibles directamente al aula. Cualquier cosa que puedas mover con palmas, la puedes enseñar. Cualquier cosa que puedas improvisar en escena, la puedes hacer delante de 28 adolescentes un lunes por la mañana.</p>

<h3>Objetivos de aprendizaje</h3>
<ul>
<li>Mejorar la competencia comunicativa en español (A2-B1) en contextos educativos reales</li>
<li>Dominar el vocabulario del entorno escolar español: aula, sala de profesores, familias</li>
<li>Descubrir las raíces, los palos y la estructura rítmica del flamenco en su cuna</li>
<li>Adquirir herramientas prácticas de ritmo, expresión corporal e improvisación transferibles a cualquier aula</li>
<li>Cerrar el curso con una mini-actuación grupal de flamenco — una experiencia única para llevar a casa</li>
</ul>

<h3>¿Qué incluye?</h3>
<ul>
<li>40 horas de formación en dos semanas (español + flamenco, lunes a viernes)</li>
<li>Material didáctico, recursos digitales y un dossier específico de flamenco</li>
<li>Certificado Europass de asistencia</li>
<li>Seguro de responsabilidad civil</li>
<li>Guía cultural de bienvenida a Jerez y Andalucía</li>
</ul>

<h3>¿Qué NO incluye?</h3>
<ul>
<li>Alojamiento y manutención (opcional, presupuestado aparte — ver abajo)</li>
<li>Transporte internacional</li>
<li>Seguro médico de viaje</li>
</ul>

<p><strong>Nota:</strong> Este curso es financiable al 100% con fondos Erasmus+ KA1. EuryGo te asesora en la solicitud si lo necesitas. No se requiere español previo más allá del nivel A2 — y absolutamente ninguna experiencia previa de flamenco.</p>
HTML;
}

function bloque_opcional_html_es(): string {
    return MARK_START . "\n"
        . '<section style="margin-top:3rem; padding:1.5rem 1.75rem; background:linear-gradient(135deg,#fef9e7,#fffdf3); border:2px dashed #d97706; border-radius:14px;">'
        . '<div style="display:inline-block; background:#d97706; color:#fff; padding:0.35rem 0.85rem; border-radius:6px; font-size:0.7rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:1rem;">★ Opcional · Add-on</div>'
        . '<h2 style="margin:0 0 0.5rem 0; color:#7c2d12;">Programa Cultural</h2>'
        . '<p style="margin-top:0; font-style:italic; color:#92400e;">Tres experiencias de tarde cuidadosamente seleccionadas que convierten esta formación en un viaje al alma de Andalucía. Bajo petición, presupuestadas aparte — no incluidas en el precio del curso.</p>'
        . '<div style="display:grid; grid-template-columns:1fr; gap:0.9rem; margin-top:1rem;">'
        . '<div style="background:#fff; padding:1rem 1.25rem; border-radius:10px; border-left:4px solid #d97706;">'
        . '<h3 style="margin:0 0 0.4rem 0; font-size:1.05rem;">Real Escuela Andaluza del Arte Ecuestre</h3>'
        . '<p style="margin:0; font-size:0.92rem;"><strong>Miércoles tarde</strong> · <strong>18,00 €</strong></p>'
        . '<p style="margin:0.4rem 0 0 0;">Exhibición única de caballos jerezanos y doma clásica en uno de los espectáculos más elegantes de España. Una velada de las que se recuerdan durante años.</p>'
        . '</div>'
        . '<div style="background:#fff; padding:1rem 1.25rem; border-radius:10px; border-left:4px solid #d97706;">'
        . '<h3 style="margin:0 0 0.4rem 0; font-size:1.05rem;">Visita y cata en González Byass</h3>'
        . '<p style="margin:0; font-size:0.92rem;"><strong>Jueves tarde</strong> · <strong>33,50 €</strong></p>'
        . '<p style="margin:0.4rem 0 0 0;">Visita al complejo monumental de una de las bodegas más emblemáticas de Jerez, con degustación guiada de los famosos vinos que dieron nombre a la ciudad.</p>'
        . '</div>'
        . '<div style="background:#fff; padding:1rem 1.25rem; border-radius:10px; border-left:4px solid #d97706;">'
        . '<h3 style="margin:0 0 0.4rem 0; font-size:1.05rem;">Excursión cultural — Cádiz o Sevilla</h3>'
        . '<p style="margin:0; font-size:0.92rem;"><strong>Viernes tarde</strong> · <strong>45,00 – 50,00 € aprox.</strong> (transporte incluido)</p>'
        . '<p style="margin:0.4rem 0 0 0;">Escapada a elegir entre dos joyas del sur de España: Cádiz, la ciudad habitada más antigua de Europa occidental — o Sevilla, la deslumbrante capital de Andalucía. Patrimonio impresionante y autenticidad andaluza garantizada.</p>'
        . '</div>'
        . '</div>'
        . '<p style="margin-top:1rem; padding-top:0.75rem; border-top:1px solid #fde68a; font-size:0.85rem; color:#92400e;"><em>El programa y las actividades están sujetos a posibles cambios.</em></p>'
        . '</section>'
        . "\n"
        . '<section style="margin-top:1.5rem; padding:1.5rem 1.75rem; background:linear-gradient(135deg,#ecfeff,#f0f9ff); border:2px dashed #0284c7; border-radius:14px;">'
        . '<div style="display:inline-block; background:#0284c7; color:#fff; padding:0.35rem 0.85rem; border-radius:6px; font-size:0.7rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:1rem;">★ Opcional · Add-on</div>'
        . '<h2 style="margin:0 0 0.5rem 0; color:#0c4a6e;">Alojamiento y traslados</h2>'
        . '<p>Llegar a una ciudad nueva debería ser el inicio de una aventura, no un rompecabezas logístico. A través de nuestra agencia de viajes colaboradora coordinamos alojamiento en hoteles seleccionados, recogidas en aeropuerto y traslados — adaptados a tus fechas, presupuesto y tamaño de grupo.</p>'
        . '<p style="font-weight:600; color:#0c4a6e;">Nosotros nos ocupamos de la logística para que tú te centres en la experiencia.</p>'
        . '<p style="font-size:0.85rem; color:#64748b; margin-bottom:0;">Bajo petición, gestionado a través de agencia colaboradora. Presupuestado y facturado aparte. No incluido en el precio del curso.</p>'
        . '</section>'
        . "\n" . MARK_END;
}

// ───────────────────────────────────────────────────────────────────────────
//  PROGRAMA ES — 10 días × 4 bloques diarios
//  Devuelve filas para INSERT en cursos_programa.
// ───────────────────────────────────────────────────────────────────────────
function programa_flamenco_es(): array {
    $filas = [];
    $orden = 0;

    $coffee_titulo = 'Coffee break & networking';
    $coffee_desc   = 'Pausa para café e intercambio informal entre docentes europeos — el momento donde nacen muchas colaboraciones Erasmus+.';
    $bloque_coffee = function() use (&$orden, $coffee_titulo, $coffee_desc, &$filas, &$dia_actual) {
        return ['dia' => $dia_actual, 'titulo' => $coffee_titulo, 'descripcion' => $coffee_desc, 'horario' => '11:00 – 11:30', 'tipo' => 'actividad', 'orden' => ++$orden];
    };

    // ─── DÍA 1 — Bienvenida
    $dia_actual = 1;
    $filas[] = ['dia' => 1, 'titulo' => 'FASE 1 — Bienvenida e Introducciones', 'descripcion' => 'Una sesión cálida de apertura: cada participante presenta su centro educativo, su metodología de trabajo y su región de origen. Un espacio de networking Erasmus+ donde nacen las conexiones que duran más allá del curso. (Aprox. 10 minutos por participante.)', 'horario' => '09:00 – 11:00', 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = $bloque_coffee();
    $filas[] = ['dia' => 1, 'titulo' => 'Local Welcome Guide + nivelación de español', 'descripcion' => 'Bienvenida cultural del equipo de EuryGo y una nivelación relajada: dónde está cada participante en español (A2–B1), qué espera del curso y un primer baño en la lengua que viviremos durante dos semanas.', 'horario' => '11:30 – 12:30', 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = ['dia' => 1, 'titulo' => 'Flamenco — Sesión introductoria', 'descripcion' => '¿Qué es el flamenco? Primer contacto con sus raíces en Jerez, los principales palos y el triángulo cuerpo-ritmo-voz que será nuestra caja de herramientas durante las dos semanas.', 'horario' => '12:30 – 13:30', 'tipo' => 'sesion', 'orden' => ++$orden];

    // ─── DÍAS 2-4 (mar-jue semana 1) — Fundamentos
    $temas_w1 = [
        2 => ['Vocabulario profesional docente: el aula en español', 'Práctica oral, role-playing, conversación guiada', 'Palmas y compás — primera incursión rítmica'],
        3 => ['Rutinas y horarios escolares en español', 'Práctica comunicativa: situaciones de aula', 'Los palos del flamenco — bulerías, soleá, alegrías'],
        4 => ['Expresiones cotidianas y vida en Jerez', 'Conversación libre + corrección guiada', 'Compás de 12 tiempos: el corazón del flamenco'],
    ];
    foreach ([2,3,4] as $d) {
        $dia_actual = $d;
        $filas[] = ['dia' => $d, 'titulo' => 'Español — Sesión de la mañana', 'descripcion' => $temas_w1[$d][0] . '. Vocabulario y comunicación oral orientada al contexto educativo y profesional, con material que vas a usar en tu día a día docente.', 'horario' => '09:00 – 11:00', 'tipo' => 'sesion', 'orden' => ++$orden];
        $filas[] = $bloque_coffee();
        $filas[] = ['dia' => $d, 'titulo' => 'Español — Consolidación', 'descripcion' => $temas_w1[$d][1] . '. Activación inmediata de los contenidos de la mañana mediante actividades comunicativas y dinámicas en parejas.', 'horario' => '11:30 – 12:30', 'tipo' => 'sesion', 'orden' => ++$orden];
        $filas[] = ['dia' => $d, 'titulo' => 'Flamenco — Sesión práctica', 'descripcion' => $temas_w1[$d][2] . '. Taller práctico diario: ritmo, palmas y trabajo de conjunto. Herramientas que puedes trasladar a la música y al movimiento de tu propia aula.', 'horario' => '12:30 – 13:30', 'tipo' => 'sesion', 'orden' => ++$orden];
    }

    // ─── DÍA 5 (vie semana 1) — Consolidación de la semana
    $dia_actual = 5;
    $filas[] = ['dia' => 5, 'titulo' => 'Español — Consolidación de la semana 1', 'descripcion' => 'Repaso integral de la semana: lo aprendido sobre el aula española, vocabulario docente y rutinas. Mini-evaluación formativa amistosa y preparación para la profundización en la semana 2.', 'horario' => '09:00 – 11:00', 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = $bloque_coffee();
    $filas[] = ['dia' => 5, 'titulo' => 'Español — Taller de narrativa oral', 'descripcion' => 'Taller comunicativo: cada participante cuenta una historia de su aula o de su semana en Jerez en español. Fluidez, expresividad y vocabulario en acción.', 'horario' => '11:30 – 12:30', 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = ['dia' => 5, 'titulo' => 'Flamenco — Primera secuencia coreografiada', 'descripcion' => 'Cerramos la semana 1 hilando palmas, compás y los primeros pasos de movimiento en una secuencia coreográfica corta. La base sobre la que construiremos la actuación final.', 'horario' => '12:30 – 13:30', 'tipo' => 'sesion', 'orden' => ++$orden];

    // ─── DÍAS 6-8 (lun-mié semana 2) — Profundización
    $temas_w2 = [
        6 => ['Comunicación con familias en español', 'Role-playing: tutorías difíciles', 'El cante — escuchar y entender la voz del flamenco'],
        7 => ['Describir tu metodología docente en español', 'Presentaciones cortas en español + feedback', 'La guitarra flamenca — su papel y su lenguaje'],
        8 => ['El sistema educativo español explicado en español', 'Debate guiado: comparativa con tu sistema', 'El baile — expresión corporal e improvisación'],
    ];
    foreach ([6,7,8] as $d) {
        $dia_actual = $d;
        $filas[] = ['dia' => $d, 'titulo' => 'Español — Sesión de la mañana', 'descripcion' => $temas_w2[$d][0] . '. Lengua avanzada aplicada a situaciones reales de un centro educativo español.', 'horario' => '09:00 – 11:00', 'tipo' => 'sesion', 'orden' => ++$orden];
        $filas[] = $bloque_coffee();
        $filas[] = ['dia' => $d, 'titulo' => 'Español — Consolidación', 'descripcion' => $temas_w2[$d][1] . '. Práctica intensiva para asentar la confianza al hablar en contextos profesionales reales.', 'horario' => '11:30 – 12:30', 'tipo' => 'sesion', 'orden' => ++$orden];
        $filas[] = ['dia' => $d, 'titulo' => 'Flamenco — Sesión práctica', 'descripcion' => $temas_w2[$d][2] . '. Cómo el ritmo, la expresión corporal y la improvisación se convierten en competencias transferibles a tu aula.', 'horario' => '12:30 – 13:30', 'tipo' => 'sesion', 'orden' => ++$orden];
    }

    // ─── DÍA 9 (jue semana 2) — Ensayo general
    $dia_actual = 9;
    $filas[] = ['dia' => 9, 'titulo' => 'Español — Consolidación final', 'descripcion' => 'Conversación avanzada, vocabulario profesional consolidado y reflexión final sobre el progreso. Cada participante prepara una intervención corta para el día 10.', 'horario' => '09:00 – 11:00', 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = $bloque_coffee();
    $filas[] = ['dia' => 9, 'titulo' => 'Español — Práctica oral libre', 'descripcion' => 'Conversación abierta: discusión en grupo sobre lo aprendido, sobre Jerez, sobre lo que cada uno se lleva. Hablar puro, confianza pura.', 'horario' => '11:30 – 12:30', 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = ['dia' => 9, 'titulo' => 'Flamenco — Ensayo general', 'descripcion' => 'Ensayo general de la actuación grupal del día siguiente. Ajuste de compás, coreografía y dinámicas de grupo. Última oportunidad para pulir antes de la mini-actuación final.', 'horario' => '12:30 – 13:30', 'tipo' => 'sesion', 'orden' => ++$orden];

    // ─── DÍA 10 (vie semana 2) — Cierre
    $dia_actual = 10;
    $filas[] = ['dia' => 10, 'titulo' => 'Repaso lingüístico final y conversación libre', 'descripcion' => 'Repaso final, conversación libre y reflexión sobre el viaje de dos semanas. Cada participante comparte su intervención preparada — en español, claro.', 'horario' => '09:00 – 11:00', 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = $bloque_coffee();
    $filas[] = ['dia' => 10, 'titulo' => 'Mini-actuación grupal de flamenco', 'descripcion' => 'El momento que llevamos dos semanas construyendo. Los participantes muestran en grupo lo aprendido: compás, palmas, movimiento y expresión. Cámaras encendidas — esto se va a casa con vosotros.', 'horario' => '11:30 – 12:30', 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = ['dia' => 10, 'titulo' => 'Reflexión final y certificación Europass', 'descripcion' => 'Reflexión final guiada, evaluación del curso y entrega de los certificados Europass. El sello oficial que convierte estas dos semanas en CPD reconocido para tu carrera y tu centro.', 'horario' => '12:30 – 13:30', 'tipo' => 'sesion', 'orden' => ++$orden];

    return $filas;
}

// ───────────────────────────────────────────────────────────────────────────
//  PREVIEW — cargar estado actual
// ───────────────────────────────────────────────────────────────────────────

$curso_en = $db->prepare("SELECT id, titulo, traduccion_id FROM cursos WHERE slug = :s LIMIT 1");
$curso_en->execute([':s' => SLUG_EN]);
$curso_en = $curso_en->fetch();

$curso_es = $db->prepare("SELECT id, titulo, traduccion_id FROM cursos WHERE slug = :s LIMIT 1");
$curso_es->execute([':s' => SLUG_ES]);
$curso_es = $curso_es->fetch();

$preview_problemas = [];
if (!$curso_en) {
    $preview_problemas[] = "No se encuentra el curso EN con slug '" . SLUG_EN . "'. Ejecuta antes update_v8.php.";
}

// ───────────────────────────────────────────────────────────────────────────
//  APPLY
// ───────────────────────────────────────────────────────────────────────────

$log = [];
$errores_apply = [];

if ($mode === 'apply' && empty($preview_problemas)) {
    try {
        $db->beginTransaction();

        if ($curso_es) {
            $log[] = "ℹ Curso ES ya existe (id={$curso_es['id']}). Se actualizan textos y programa para mantener idempotencia.";
            $es_id = (int)$curso_es['id'];

            // Reescribir descripción, extracto y campos clave
            $desc = descripcion_flamenco_es() . "\n\n" . bloque_opcional_html_es();
            $upd = $db->prepare("UPDATE cursos SET titulo=:t, idioma='es', extracto=:e, descripcion=:d, precio=480.00, duracion_dias=10, plazas=15, fecha_inicio=:fi, fecha_fin=:ff, ubicacion=:ub, estado='publicado', meta_title=:mt, meta_description=:md WHERE id=:id");
            $upd->execute([
                ':t'  => 'Español y Flamenco',
                ':e'  => 'Dos semanas de inmersión en Jerez combinando español orientado al contexto educativo (A2–B1) con talleres diarios de flamenco. La lengua y el arte que mejor la expresa — nacido en las mismas calles donde estudiarás.',
                ':d'  => $desc,
                ':fi' => '2026-10-05',
                ':ff' => '2026-10-16',
                ':ub' => 'Jerez de la Frontera, Cádiz, España',
                ':mt' => 'Curso de Español y Flamenco para Docentes en Jerez — Erasmus+ KA1 | EuryGo',
                ':md' => 'Dos semanas de inmersión KA1 en Jerez para docentes europeos: español del contexto educativo más talleres diarios de flamenco en su cuna. 480 € · Certificado Europass.',
                ':id' => $es_id,
            ]);
        } else {
            $log[] = "→ Insertando curso ES nuevo...";
            $desc = descripcion_flamenco_es() . "\n\n" . bloque_opcional_html_es();
            $ins = $db->prepare("INSERT INTO cursos (titulo, slug, idioma, extracto, descripcion, precio, duracion_dias, plazas, fecha_inicio, fecha_fin, ubicacion, estado, destacado, meta_title, meta_description) VALUES (:t,:s,'es',:e,:d,480.00,10,15,:fi,:ff,:ub,'publicado',0,:mt,:md)");
            $ins->execute([
                ':t'  => 'Español y Flamenco',
                ':s'  => SLUG_ES,
                ':e'  => 'Dos semanas de inmersión en Jerez combinando español orientado al contexto educativo (A2–B1) con talleres diarios de flamenco. La lengua y el arte que mejor la expresa — nacido en las mismas calles donde estudiarás.',
                ':d'  => $desc,
                ':fi' => '2026-10-05',
                ':ff' => '2026-10-16',
                ':ub' => 'Jerez de la Frontera, Cádiz, España',
                ':mt' => 'Curso de Español y Flamenco para Docentes en Jerez — Erasmus+ KA1 | EuryGo',
                ':md' => 'Dos semanas de inmersión KA1 en Jerez para docentes europeos: español del contexto educativo más talleres diarios de flamenco en su cuna. 480 € · Certificado Europass.',
            ]);
            $es_id = (int)$db->lastInsertId();
            $log[] = "✓ Curso ES creado con id={$es_id}.";
        }

        // Reescribir programa (DELETE + INSERT) — idempotente
        $db->prepare("DELETE FROM cursos_programa WHERE curso_id = :id")->execute([':id' => $es_id]);
        $ins_p = $db->prepare("INSERT INTO cursos_programa (curso_id, dia, titulo, descripcion, horario, tipo, orden) VALUES (:cid,:dia,:t,:d,:h,:tipo,:o)");
        foreach (programa_flamenco_es() as $r) {
            $ins_p->execute([':cid' => $es_id, ':dia' => $r['dia'], ':t' => $r['titulo'], ':d' => $r['descripcion'], ':h' => $r['horario'], ':tipo' => $r['tipo'], ':o' => $r['orden']]);
        }
        $log[] = "✓ Programa reescrito: 40 filas (10 días × 4 bloques).";

        // Reescribir ediciones (DELETE + INSERT) — idempotente
        $db->prepare("DELETE FROM cursos_ediciones WHERE curso_id = :id")->execute([':id' => $es_id]);
        $ins_e = $db->prepare("INSERT INTO cursos_ediciones (curso_id, fecha_inicio, fecha_fin, plazas_totales, plazas_disponibles, estado, destacada) VALUES (:cid,:fi,:ff,15,15,'abierta',:dest)");
        $ins_e->execute([':cid' => $es_id, ':fi' => '2026-10-05', ':ff' => '2026-10-16', ':dest' => 1]);
        $ins_e->execute([':cid' => $es_id, ':fi' => '2027-02-01', ':ff' => '2027-02-12', ':dest' => 0]);
        $log[] = "✓ Ediciones reescritas: 5-16 oct 2026 (destacada) + 1-12 feb 2027.";

        // Vincular ES <-> EN con traduccion_id
        $en_id = (int)$curso_en['id'];
        $db->prepare("UPDATE cursos SET traduccion_id = :tid WHERE id = :id")->execute([':tid' => $en_id, ':id' => $es_id]);
        $db->prepare("UPDATE cursos SET traduccion_id = :tid WHERE id = :id")->execute([':tid' => $es_id, ':id' => $en_id]);
        $log[] = "✓ Vinculadas traducciones: ES(id={$es_id}) <-> EN(id={$en_id}).";

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        $errores_apply[] = $e->getMessage() . "\n" . $e->getTraceAsString();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Update v10 — Flamenco ES</title>
  <meta name="robots" content="noindex, nofollow">
  <style>
    body { font-family: system-ui, sans-serif; max-width: 1000px; margin: 32px auto; padding: 0 20px; color:#1f2937; }
    h1 { color:#0c4a6e; border-bottom:3px solid #0284c7; padding-bottom:8px; }
    h2 { margin-top: 28px; color:#0c4a6e; }
    .box { padding:14px 18px; border-radius:10px; margin:12px 0; }
    .ok    { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
    .warn  { background:#fffbeb; border:1px solid #fde68a; color:#92400e; }
    .err   { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
    .info  { background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; }
    table  { width:100%; border-collapse:collapse; margin:8px 0 16px; font-size:0.88rem; }
    th,td  { padding:6px 10px; border:1px solid #e5e7eb; text-align:left; vertical-align:top; }
    th     { background:#f1f5f9; font-weight:600; }
    .btn-apply { display:inline-block; padding:14px 28px; background:#dc2626; color:#fff; font-weight:700; border-radius:8px; text-decoration:none; font-size:1.05rem; margin-top:12px; }
    .btn-apply:hover { background:#991b1b; }
    code { background:#f1f5f9; padding:1px 6px; border-radius:4px; font-size:0.85rem; }
    pre  { background:#f8fafc; padding:10px 12px; border-radius:6px; overflow-x:auto; font-size:0.82rem; }
  </style>
</head>
<body>
  <h1>Update v10 — Curso Flamenco en español</h1>

  <?php if ($mode === 'apply'): ?>
    <?php if (empty($errores_apply)): ?>
      <div class="box ok"><strong>✓ APPLY completado.</strong> Todos los cambios commiteados en transacción.</div>
      <h2>Log</h2>
      <ol>
        <?php foreach ($log as $l): ?>
          <li><?= htmlspecialchars($l) ?></li>
        <?php endforeach; ?>
      </ol>
      <div class="box warn"><strong>IMPORTANTE:</strong> borra este archivo del servidor:<br><code>/admin/setup/update_v10.php</code></div>
      <div class="box info">URLs públicas a verificar:<br>
        <strong>ES:</strong> <code>https://www.eurygo.com/cursos/<?= SLUG_ES ?>/</code><br>
        <strong>EN:</strong> <code>https://www.eurygo.com/en/cursos/<?= SLUG_EN ?>/</code><br>
        El botón ES/EN en el header debería navegar entre ambos.
      </div>
    <?php else: ?>
      <div class="box err"><strong>✗ APPLY abortado y revertido (ROLLBACK).</strong><br><pre><?= htmlspecialchars(implode("\n\n", $errores_apply)) ?></pre></div>
    <?php endif; ?>
  <?php else: ?>
    <?php if (!empty($preview_problemas)): ?>
      <?php foreach ($preview_problemas as $p): ?>
        <div class="box err"><?= htmlspecialchars($p) ?></div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="box info">
        <strong>Modo PREVIEW.</strong> No se ha modificado nada. Revisa abajo lo que va a hacer.
        <br><br><a class="btn-apply" href="?mode=apply&amp;confirm=YES" onclick="return confirm('¿Aplicar los cambios? Crea/actualiza el curso ES y vincula traducciones.');">▶ Aplicar cambios ahora</a>
      </div>
    <?php endif; ?>

    <h2>Estado actual</h2>
    <table>
      <tr><th>Curso</th><th>ID</th><th>Título</th><th>traduccion_id</th></tr>
      <tr><td>EN (<code><?= SLUG_EN ?></code>)</td>
          <td><?= $curso_en ? (int)$curso_en['id'] : '<em>no encontrado</em>' ?></td>
          <td><?= $curso_en ? htmlspecialchars($curso_en['titulo']) : '—' ?></td>
          <td><?= $curso_en ? ($curso_en['traduccion_id'] ?? '<em>NULL</em>') : '—' ?></td></tr>
      <tr><td>ES (<code><?= SLUG_ES ?></code>)</td>
          <td><?= $curso_es ? (int)$curso_es['id'] : '<em>no existe — se creará</em>' ?></td>
          <td><?= $curso_es ? htmlspecialchars($curso_es['titulo']) : '—' ?></td>
          <td><?= $curso_es ? ($curso_es['traduccion_id'] ?? '<em>NULL</em>') : '—' ?></td></tr>
    </table>

    <h2>Lo que hará APPLY</h2>
    <ol>
      <li>Si el curso ES no existe → INSERT con título "Español y Flamenco", precio 480 €, 10 días, 15 plazas, estado publicado.</li>
      <li>Si ya existe → UPDATE de todos los campos (titulo, descripcion, fechas, ubicación, SEO) — idempotente.</li>
      <li>Reescribir `cursos_programa` para el curso ES: 40 filas (10 días × 4 bloques).</li>
      <li>Reescribir `cursos_ediciones`: 5-16 oct 2026 (destacada) + 1-12 feb 2027.</li>
      <li>Vincular ambas filas: `cursos.traduccion_id` ES↔EN para que el switch ES/EN del header navegue entre los dos.</li>
      <li>Añadir al final de la descripción los bloques OPTIONAL en español (Programa Cultural + Alojamiento) — entre sentinels EURYGO_OPTIONALS_V8 para que el split de <code>cursos/curso.php</code> los separe del "What is Included".</li>
    </ol>

    <h2>Vista previa del programa que se insertará</h2>
    <table>
      <tr><th>Día</th><th>Horario</th><th>Tipo</th><th>Título</th></tr>
      <?php foreach (programa_flamenco_es() as $r): ?>
        <tr><td><?= $r['dia'] ?></td><td><code><?= htmlspecialchars($r['horario']) ?></code></td><td><?= $r['tipo'] ?></td><td><?= htmlspecialchars($r['titulo']) ?></td></tr>
      <?php endforeach; ?>
    </table>

    <h2>Bloques OPTIONAL ES (vista previa)</h2>
    <?= bloque_opcional_html_es() ?>
  <?php endif; ?>

  <hr style="margin:32px 0;">
  <p style="font-size:0.8rem; color:#94a3b8;">EuryGo Update v10 — 2026-05-25</p>
</body>
</html>
