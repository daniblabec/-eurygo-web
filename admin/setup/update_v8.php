<?php
/**
 * ACTUALIZACIÓN v8 — EuryGo: Estandarización de cursos KA1 (mayo 2026)
 *
 * Brief 2026-05-19:
 *  T1. Estandarizar horario diario (3 bloques mañana, sin tardes) en TODOS
 *      los cursos publicados excepto el nuevo "Spanish Language & Flamenco".
 *  T2. Eliminar referencias a "school visit / job shadowing / visitas a
 *      colegios" en programa, extracto y descripción.
 *  T3. Reestructurar el Día 1 con Welcome & Introductions + Local Welcome
 *      Guide en todos los cursos.
 *  T4. Añadir secciones OPTIONAL (Cultural Programme + Accommodation) al
 *      final de la descripción de cada curso.
 *  T5. Crear el nuevo curso "Spanish Language & Flamenco" (slug
 *      spanish-language-flamenco, 480 €, 2 semanas, 2 ediciones).
 *
 *  + Miércoles mañana 11:30–13:30: "Cultural visit to the historic centre
 *    of Jerez" (en todos los cursos excepto el de flamenco).
 *
 * USO:
 *   /admin/setup/update_v8.php                  → PREVIEW (no escribe nada)
 *   /admin/setup/update_v8.php?mode=apply&confirm=YES   → ejecuta (transacción)
 *
 *  Requiere sesión de admin. BORRAR ESTE ARCHIVO DEL SERVIDOR DESPUÉS DE
 *  EJECUTAR EN APPLY.
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requiere_login();

$db   = get_db();
$mode = ($_GET['mode'] ?? 'preview') === 'apply' && ($_GET['confirm'] ?? '') === 'YES' ? 'apply' : 'preview';

// ───────────────────────────────────────────────────────────────────────────
//  CONSTANTES DE HORARIO
// ───────────────────────────────────────────────────────────────────────────
const HORARIO_M1 = '09:00 – 11:00';
const HORARIO_BR = '11:00 – 11:30';
const HORARIO_M2 = '11:30 – 13:30';
const SLUG_FLAMENCO = 'spanish-language-flamenco';
const MARK_START = '<!-- EURYGO_OPTIONALS_V8_START -->';
const MARK_END   = '<!-- EURYGO_OPTIONALS_V8_END -->';

// ───────────────────────────────────────────────────────────────────────────
//  Contenido del bloque "Coffee break & networking" (idéntico en todos)
// ───────────────────────────────────────────────────────────────────────────
function bloque_coffee(string $idioma): array {
    return $idioma === 'en'
        ? ['Coffee break & networking', 'Pause for coffee and informal exchanges with fellow European teachers — where many Erasmus+ collaborations are born.', HORARIO_BR, 'actividad']
        : ['Coffee break & networking', 'Pausa para café e intercambio informal entre docentes europeos — el momento donde nacen muchas colaboraciones Erasmus+.', HORARIO_BR, 'actividad'];
}

function bloque_visita_jerez(string $idioma): array {
    return $idioma === 'en'
        ? ['Cultural visit to the historic centre of Jerez', 'A guided walk through the old quarter of Jerez de la Frontera: Cathedral, Alcázar, sherry-soaked patios and the streets where flamenco was born. A mid-week immersion that connects the morning training with the soul of the city.', HORARIO_M2, 'actividad']
        : ['Visita cultural al centro histórico de Jerez', 'Recorrido guiado por el casco antiguo de Jerez de la Frontera: Catedral, Alcázar, patios con aroma a Jerez y las calles donde nació el flamenco. Una inmersión a mitad de semana que conecta la formación de la mañana con el alma de la ciudad.', HORARIO_M2, 'actividad'];
}

// ───────────────────────────────────────────────────────────────────────────
//  DÍA 1 — Welcome & Local Welcome Guide (igual para todos los cursos)
// ───────────────────────────────────────────────────────────────────────────
function dia_1_welcome(string $idioma): array {
    if ($idioma === 'en') {
        return [
            ['PHASE 1 — Welcome & Introductions', 'A warm opening session: each participant presents their school, their teaching methodology and their region of origin. The Erasmus+ networking space where the connections that outlast the course are born. (Allow approx. 10 minutes per participant.)', HORARIO_M1, 'sesion'],
            bloque_coffee('en'),
            ['PHASE 2 — Local Welcome Guide', 'Your first guided dive into Andalusian life: gastronomy, traditions, must-see places and insider recommendations to make the most of every moment in Jerez and Andalusia. The EuryGo team will be by your side for this cultural welcome.', HORARIO_M2, 'sesion'],
        ];
    }
    return [
        ['FASE 1 — Bienvenida e Introducciones', 'Una sesión cálida de apertura: cada participante presenta su centro educativo, su metodología de trabajo y su región de origen. Un espacio de networking Erasmus+ donde nacen las conexiones que duran más allá del curso. (Aprox. 10 minutos por participante.)', HORARIO_M1, 'sesion'],
        bloque_coffee('es'),
        ['FASE 2 — Local Welcome Guide', 'Tu primera inmersión guiada en la vida andaluza: gastronomía, tradiciones, lugares imprescindibles y recomendaciones para disfrutar al máximo de Jerez y Andalucía. El equipo de EuryGo te acompañará en esta bienvenida cultural.', HORARIO_M2, 'sesion'],
    ];
}

// ───────────────────────────────────────────────────────────────────────────
//  DÍA DE CIERRE (último día) — igual en todos los cursos de 5 días
// ───────────────────────────────────────────────────────────────────────────
function dia_cierre(string $idioma, string $tema_morning_en, string $tema_morning_es): array {
    if ($idioma === 'en') {
        return [
            ["Final session — {$tema_morning_en}", 'Final consolidation: integrating everything learned during the week into a personal action plan you can implement on Monday back at your school. Group reflection and final Q&A with our certified trainers.', HORARIO_M1, 'sesion'],
            bloque_coffee('en'),
            ['Closing & Europass certification', 'Course evaluation, peer feedback and Europass certificate ceremony. A celebration of the journey — and the official validation that turns this week into recognised CPD for your career and your school.', HORARIO_M2, 'sesion'],
        ];
    }
    return [
        ["Sesión final — {$tema_morning_es}", 'Consolidación final: integrar todo lo aprendido durante la semana en un plan de acción personal aplicable en tu centro desde el lunes. Reflexión grupal y ronda final de preguntas con nuestros formadores certificados.', HORARIO_M1, 'sesion'],
        bloque_coffee('es'),
        ['Cierre y certificación Europass', 'Evaluación del curso, feedback entre pares y entrega de certificados Europass. Una celebración del recorrido — y la validación oficial que convierte esta semana en CPD reconocido para tu carrera y tu centro.', HORARIO_M2, 'sesion'],
    ];
}

// ───────────────────────────────────────────────────────────────────────────
//  CONTENIDO CURADO POR CURSO CONOCIDO (slugs reconocidos)
//  Estructura: para cada slug, devuelve un array de bloques (titulo,
//  descripcion) ordenados de día 2 mañana-1, día 2 mañana-2, día 3 mañana-1,
//  día 4 mañana-1, día 4 mañana-2, día 5 morning-final-tema.
//  El miércoles 11:30–13:30 SIEMPRE es la visita a Jerez (no se solicita).
//  El día 5 11:30–13:30 SIEMPRE es cierre+certificación.
// ───────────────────────────────────────────────────────────────────────────
function contenido_curado(string $slug): ?array {
    $map = [
        // ───────── ES: Sistema Educativo Español ─────────
        'sistema-educativo-espanol' => [
            'd2m1' => ['Estructura del sistema educativo español', 'Panorámica del sistema: etapas (Infantil, Primaria, ESO, Bachillerato, FP), titularidad (pública, concertada, privada) y las reformas que están dando forma al aula actual. Marco LOMLOE y competencias clave en contexto real.'],
            'd2m2' => ['La profesión docente en España', 'Acceso a la función pública, formación inicial y desarrollo profesional continuo. Roles del equipo directivo, departamentos didácticos y orientación. Mesa redonda con docentes en activo de centros de Jerez.'],
            'd3m1' => ['Diseño curricular y evaluación competencial', 'Cómo se traduce la LOMLOE en programaciones reales: situaciones de aprendizaje, criterios competenciales y evaluación formativa. Taller práctico con ejemplos del aula española.'],
            'd4m1' => ['Innovación y metodologías activas en el aula español', 'ABP, aprendizaje cooperativo y flipped classroom tal como se aplican en centros españoles. Vives la metodología primero como aprendiz, luego diseñas una unidad para tu propia asignatura.'],
            'd4m2' => ['Familia, comunidad e inclusión', 'Cómo se construye la relación familia-escuela en España, el papel del AMPA, los protocolos de convivencia y la educación inclusiva del sistema. Comparativa con tu sistema de origen.'],
            'd5_tema_en' => 'Comparative reflection on European school systems',
            'd5_tema_es' => 'Reflexión comparada de sistemas educativos europeos',
        ],
        'spanish-education-system' => [
            'd2m1' => ['The Spanish education system: structure & stages', 'A clear overview: early years, primary, ESO, Bachillerato and VET. Public, state-subsidised and private schools. The LOMLOE reform and key competences as they actually land in the classroom.'],
            'd2m2' => ['The teaching profession in Spain', 'Entry into public teaching, initial training and continuous professional development. Leadership teams, subject departments and guidance services. Round-table with serving teachers from Jerez schools.'],
            'd3m1' => ['Curriculum design & competence-based assessment', 'How LOMLOE translates into real schemes of work: learning situations, competence criteria and formative assessment. Practical workshop with real examples from Spanish classrooms.'],
            'd4m1' => ['Active methodologies in Spanish schools', 'PBL, cooperative learning and flipped classroom as they are applied in Spain. Experience the methodology first as a learner, then design a unit for your own subject.'],
            'd4m2' => ['Families, community and inclusion', 'How the family-school partnership is built in Spain, the role of parent associations, coexistence protocols and the inclusive model. Comparison with your home system.'],
            'd5_tema_en' => 'Comparative reflection on European school systems',
            'd5_tema_es' => 'Reflexión comparada de sistemas educativos europeos',
        ],
        // ───────── ES: Español para Docentes ─────────
        'espanol-para-docentes' => [
            'd2m1' => ['Español del aula: vocabulario y comunicación', 'Inmersión en el lenguaje cotidiano del centro educativo: el aula, las asignaturas, la rutina escolar. Vocabulario práctico que vas a usar la próxima vez que un alumno te diga "no entiendo".'],
            'd2m2' => ['Taller de conversación: situaciones reales docentes', 'Role-playing intensivo: reuniones de claustro, tutorías, intercambios con el equipo directivo. Salir del miedo a hablar y entrar en la fluidez profesional.'],
            'd3m1' => ['Gramática en contexto educativo', 'Estructuras clave (subjuntivo, condicionales, conectores) trabajadas exclusivamente a través de textos y situaciones del entorno escolar. Gramática útil, no gramática de manual.'],
            'd4m1' => ['Comunicación con familias en español', 'Cómo redactar mensajes, emails y notas a familias. Cómo conducir una tutoría difícil en español. Casos reales y prácticas guiadas.'],
            'd4m2' => ['Producción escrita profesional', 'Informes, actas, programaciones: el español formal del centro educativo. Plantillas, conectores y ejemplos comentados.'],
            'd5_tema_en' => 'Free conversation & oral fluency consolidation',
            'd5_tema_es' => 'Conversación libre y consolidación de la fluidez oral',
        ],
        'spanish-language-for-teachers' => [
            'd2m1' => ['Classroom Spanish: vocabulary and core communication', 'Immersive vocabulary of the school environment: classroom routines, subjects, daily life. The practical Spanish you will use the next time a student says "no entiendo".'],
            'd2m2' => ['Conversation workshop: real teaching scenarios', 'Intensive role-play: staff meetings, parent tutorials, exchanges with the leadership team. Step out of the fear of speaking and into professional fluency.'],
            'd3m1' => ['Grammar in educational context', 'Key structures (subjunctive, conditionals, connectors) worked exclusively through texts and situations from school life. Useful grammar, not textbook grammar.'],
            'd4m1' => ['Communicating with families in Spanish', 'How to write messages, emails and notes to families. How to lead a difficult parent meeting in Spanish. Real cases and guided practice.'],
            'd4m2' => ['Professional written production', 'Reports, minutes, schemes of work: the formal Spanish of the educational centre. Templates, connectors and annotated examples.'],
            'd5_tema_en' => 'Free conversation & oral fluency consolidation',
            'd5_tema_es' => 'Conversación libre y consolidación de la fluidez oral',
        ],
        // ───────── ES: Educación Inclusiva ─────────
        'educacion-inclusiva-espana' => [
            'd2m1' => ['Marco español de inclusión: LOMLOE y atención a la diversidad', 'Panorámica del modelo inclusivo español: legislación, equipos de orientación, protocolos de detección. Comparativa estructurada con otros sistemas europeos.'],
            'd2m2' => ['Diseño Universal para el Aprendizaje (DUA) — taller práctico', 'Sesión 100% práctica: los participantes diseñan una actividad accesible para todo el alumnado aplicando los tres principios del DUA. Aplicable a tu aula desde el lunes.'],
            'd3m1' => ['Necesidades educativas especiales: protocolos y recursos', 'Sesión monográfica sobre TEA, TDAH, dislexia, altas capacidades y discapacidad sensorial/motora en el contexto escolar español. Herramientas de evaluación y recursos de intervención reales.'],
            'd4m1' => ['Convivencia y mediación entre iguales', 'Programas de convivencia, mediación entre iguales y protocolos anti-bullying en el sistema español. Taller: diseñar un plan de convivencia inclusivo para tu centro.'],
            'd4m2' => ['Educación emocional e inclusión social', 'La dimensión emocional de la inclusión: programas de educación emocional, atención al alumnado en riesgo y construcción de comunidades educativas seguras.'],
            'd5_tema_en' => 'Action plan presentations & peer feedback',
            'd5_tema_es' => 'Presentación de planes de acción y feedback entre pares',
        ],
        'inclusive-education-spain' => [
            'd2m1' => ['Spanish inclusion framework: LOMLOE & diversity protocols', 'Overview of the Spanish inclusive model: legislation, guidance teams, detection protocols. Structured comparison with other European systems.'],
            'd2m2' => ['Universal Design for Learning (UDL) — hands-on workshop', '100% practical session: participants design an accessible activity for all students applying the three UDL principles. Ready to bring back to your classroom on Monday.'],
            'd3m1' => ['Special educational needs: protocols and resources', 'Focused session on ASD, ADHD, dyslexia, gifted students and sensory/motor disabilities in the Spanish school context. Real assessment tools and intervention resources.'],
            'd4m1' => ['Coexistence and peer mediation', 'Coexistence programmes, peer mediation and anti-bullying protocols in the Spanish system. Workshop: designing an inclusive coexistence plan for your own school.'],
            'd4m2' => ['Emotional education and social inclusion', 'The emotional dimension of inclusion: emotional education programmes, support for at-risk students and the building of safe educational communities.'],
            'd5_tema_en' => 'Action plan presentations & peer feedback',
            'd5_tema_es' => 'Presentación de planes de acción y feedback entre pares',
        ],
    ];
    return $map[$slug] ?? null;
}

// ───────────────────────────────────────────────────────────────────────────
//  Programa GENÉRICO (fallback) para slugs no reconocidos
// ───────────────────────────────────────────────────────────────────────────
function contenido_generico(string $titulo_curso, string $idioma): array {
    if ($idioma === 'en') {
        return [
            'd2m1' => ['Core topics — morning session', "Deep dive into the central themes of \"{$titulo_curso}\", delivered by our certified KA1 trainers. Theory, real classroom examples and structured discussion."],
            'd2m2' => ['Hands-on workshop', 'Practical, applied work in small groups: each participant produces materials and ideas they can take back to their own classroom.'],
            'd3m1' => ['Methodology & best practice', 'Structured exploration of pedagogical best practice connected to the course topic, with international examples and Spanish case studies.'],
            'd4m1' => ['Applied case studies', 'Analysis of real classroom situations. Participants work in mixed-nationality teams to design solutions and share approaches.'],
            'd4m2' => ['Collaborative project work', 'Group project session: build a teaching resource, action plan or unit you can implement on Monday back at your school.'],
            'd5_tema_en' => 'Personal action plan',
            'd5_tema_es' => 'Plan de acción personal',
        ];
    }
    return [
        'd2m1' => ['Contenidos centrales — sesión de la mañana', "Inmersión en los temas centrales de \"{$titulo_curso}\", impartida por nuestros formadores KA1 certificados. Teoría, ejemplos reales de aula y debate estructurado."],
        'd2m2' => ['Taller práctico', 'Trabajo aplicado en grupos pequeños: cada participante produce materiales e ideas para llevarse directamente a su aula.'],
        'd3m1' => ['Metodología y buenas prácticas', 'Exploración estructurada de buenas prácticas pedagógicas conectadas al tema del curso, con ejemplos internacionales y casos españoles.'],
        'd4m1' => ['Estudios de caso aplicados', 'Análisis de situaciones reales de aula. Los participantes trabajan en equipos multinacionales para diseñar soluciones y compartir enfoques.'],
        'd4m2' => ['Trabajo colaborativo en proyecto', 'Sesión de proyecto en grupo: construir un recurso docente, plan de acción o unidad aplicable desde el lunes en tu centro.'],
        'd5_tema_en' => 'Personal action plan',
        'd5_tema_es' => 'Plan de acción personal',
    ];
}

// ───────────────────────────────────────────────────────────────────────────
//  Genera el programa COMPLETO para un curso estándar de 5 días
//  $contenido es el array de contenido_curado() o contenido_generico()
//  Devuelve array de filas listas para INSERT en cursos_programa.
// ───────────────────────────────────────────────────────────────────────────
function generar_programa_5_dias(array $contenido, string $idioma): array {
    $filas = [];
    $orden = 0;
    $coffee = bloque_coffee($idioma);
    $visita = bloque_visita_jerez($idioma);

    // ── Día 1 — Welcome (3 bloques)
    foreach (dia_1_welcome($idioma) as $b) {
        $filas[] = ['dia' => 1, 'titulo' => $b[0], 'descripcion' => $b[1], 'horario' => $b[2], 'tipo' => $b[3], 'orden' => ++$orden];
    }

    // ── Día 2 — Tema del curso (3 bloques)
    $filas[] = ['dia' => 2, 'titulo' => $contenido['d2m1'][0], 'descripcion' => $contenido['d2m1'][1], 'horario' => HORARIO_M1, 'tipo' => 'sesion',    'orden' => ++$orden];
    $filas[] = ['dia' => 2, 'titulo' => $coffee[0],            'descripcion' => $coffee[1],            'horario' => $coffee[2],  'tipo' => $coffee[3], 'orden' => ++$orden];
    $filas[] = ['dia' => 2, 'titulo' => $contenido['d2m2'][0], 'descripcion' => $contenido['d2m2'][1], 'horario' => HORARIO_M2, 'tipo' => 'sesion',    'orden' => ++$orden];

    // ── Día 3 — Miércoles: tema + visita cultural Jerez (3 bloques)
    $filas[] = ['dia' => 3, 'titulo' => $contenido['d3m1'][0], 'descripcion' => $contenido['d3m1'][1], 'horario' => HORARIO_M1, 'tipo' => 'sesion',    'orden' => ++$orden];
    $filas[] = ['dia' => 3, 'titulo' => $coffee[0],            'descripcion' => $coffee[1],            'horario' => $coffee[2],  'tipo' => $coffee[3], 'orden' => ++$orden];
    $filas[] = ['dia' => 3, 'titulo' => $visita[0],            'descripcion' => $visita[1],            'horario' => $visita[2],  'tipo' => $visita[3], 'orden' => ++$orden];

    // ── Día 4 — Tema del curso (3 bloques)
    $filas[] = ['dia' => 4, 'titulo' => $contenido['d4m1'][0], 'descripcion' => $contenido['d4m1'][1], 'horario' => HORARIO_M1, 'tipo' => 'sesion',    'orden' => ++$orden];
    $filas[] = ['dia' => 4, 'titulo' => $coffee[0],            'descripcion' => $coffee[1],            'horario' => $coffee[2],  'tipo' => $coffee[3], 'orden' => ++$orden];
    $filas[] = ['dia' => 4, 'titulo' => $contenido['d4m2'][0], 'descripcion' => $contenido['d4m2'][1], 'horario' => HORARIO_M2, 'tipo' => 'sesion',    'orden' => ++$orden];

    // ── Día 5 — Cierre (3 bloques)
    $tema_morning = $idioma === 'en' ? $contenido['d5_tema_en'] : $contenido['d5_tema_es'];
    foreach (dia_cierre($idioma, $contenido['d5_tema_en'], $contenido['d5_tema_es']) as $b) {
        $filas[] = ['dia' => 5, 'titulo' => $b[0], 'descripcion' => $b[1], 'horario' => $b[2], 'tipo' => $b[3], 'orden' => ++$orden];
    }

    return $filas;
}

// ───────────────────────────────────────────────────────────────────────────
//  Bloques HTML "Optional · Add-on" (al final de cursos.descripcion)
// ───────────────────────────────────────────────────────────────────────────
function bloque_opcional_html(string $idioma): string {
    if ($idioma === 'en') {
        return MARK_START . "\n"
            . '<section style="margin-top:3rem; padding:1.5rem 1.75rem; background:linear-gradient(135deg,#fef9e7,#fffdf3); border:2px dashed #d97706; border-radius:14px;">'
            . '<div style="display:inline-block; background:#d97706; color:#fff; padding:0.35rem 0.85rem; border-radius:6px; font-size:0.7rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:1rem;">★ Optional · Add-on</div>'
            . '<h2 style="margin:0 0 0.5rem 0; color:#7c2d12;">Cultural Programme</h2>'
            . '<p style="margin-top:0; font-style:italic; color:#92400e;">Three carefully curated afternoon experiences that turn this training into a journey through the soul of Andalusia. Available on request, quoted and booked separately — not included in the course price.</p>'
            . '<div style="display:grid; grid-template-columns:1fr; gap:0.9rem; margin-top:1rem;">'
            . '<div style="background:#fff; padding:1rem 1.25rem; border-radius:10px; border-left:4px solid #d97706;">'
            . '<h3 style="margin:0 0 0.4rem 0; font-size:1.05rem;">Real Escuela Andaluza del Arte Ecuestre</h3>'
            . '<p style="margin:0; font-size:0.92rem;"><strong>Wednesday afternoon</strong> · <strong>18,00 €</strong></p>'
            . '<p style="margin:0.4rem 0 0 0;">A unique exhibition of pure-bred Jerez horses performing classical dressage — one of Spain\'s most elegant cultural spectacles, and an evening you will talk about for years.</p>'
            . '</div>'
            . '<div style="background:#fff; padding:1rem 1.25rem; border-radius:10px; border-left:4px solid #d97706;">'
            . '<h3 style="margin:0 0 0.4rem 0; font-size:1.05rem;">González Byass Winery Tour &amp; Tasting</h3>'
            . '<p style="margin:0; font-size:0.92rem;"><strong>Thursday afternoon</strong> · <strong>33,50 €</strong></p>'
            . '<p style="margin:0.4rem 0 0 0;">Step inside the monumental cellars of one of the most iconic wineries in Jerez. Centuries of tradition, followed by a guided tasting of the world-famous sherry wines that gave this city its name.</p>'
            . '</div>'
            . '<div style="background:#fff; padding:1rem 1.25rem; border-radius:10px; border-left:4px solid #d97706;">'
            . '<h3 style="margin:0 0 0.4rem 0; font-size:1.05rem;">Cultural Day Trip — Cádiz or Seville</h3>'
            . '<p style="margin:0; font-size:0.92rem;"><strong>Friday afternoon</strong> · <strong>45,00 – 50,00 € approx.</strong> (transport included)</p>'
            . '<p style="margin:0.4rem 0 0 0;">Choose between two jewels of Southern Spain: Cádiz, the oldest continuously inhabited city in Western Europe — or Seville, the dazzling capital of Andalusia. Either way, stunning heritage and authentic Andalusian soul guaranteed.</p>'
            . '</div>'
            . '</div>'
            . '<p style="margin-top:1rem; padding-top:0.75rem; border-top:1px solid #fde68a; font-size:0.85rem; color:#92400e;"><em>Schedule and activities are subject to change.</em></p>'
            . '</section>'
            . "\n"
            . '<section style="margin-top:1.5rem; padding:1.5rem 1.75rem; background:linear-gradient(135deg,#ecfeff,#f0f9ff); border:2px dashed #0284c7; border-radius:14px;">'
            . '<div style="display:inline-block; background:#0284c7; color:#fff; padding:0.35rem 0.85rem; border-radius:6px; font-size:0.7rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:1rem;">★ Optional · Add-on</div>'
            . '<h2 style="margin:0 0 0.5rem 0; color:#0c4a6e;">Accommodation &amp; Transfers</h2>'
            . '<p>Arriving in a new city should be the start of an adventure, not a logistics puzzle. Through our partner travel agency we coordinate selected accommodation, airport pickups and transfers — tailored to your dates, budget and group size.</p>'
            . '<p style="font-weight:600; color:#0c4a6e;">We handle the logistics so you can focus on the experience.</p>'
            . '<p style="font-size:0.85rem; color:#64748b; margin-bottom:0;">Available on request through our partner agency. Quoted and invoiced separately. Not included in the course price.</p>'
            . '</section>'
            . "\n" . MARK_END;
    }
    // ES
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
//  Limpiar referencias a "school visit / job shadowing / visitas a colegios"
//  + retirar bloque OPTIONAL previo si existe (idempotencia)
// ───────────────────────────────────────────────────────────────────────────
function limpiar_descripcion(string $html): string {
    // Quitar bloque OPTIONAL previo (marca a marca) para que sea idempotente
    $html = preg_replace('/' . preg_quote(MARK_START, '/') . '.*?' . preg_quote(MARK_END, '/') . '/s', '', $html);

    // Quitar líneas <li> que mencionen visitas a colegios / job shadowing
    $patrones_li = [
        '/<li>[^<]*(school visit|job shadow|visita[s]? a (colegios|centros)|visitas a escuelas|observation programmes? (in|at) (local )?schools)[^<]*<\/li>/iu',
    ];
    foreach ($patrones_li as $p) {
        $html = preg_replace($p, '', $html);
    }

    // Reemplazos de frases en oraciones más largas (EN + ES)
    $reemplazos = [
        // EN
        '/,\s*school visits?/i'                                            => '',
        '/school visits?\s*,?/i'                                           => '',
        '/,\s*job shadowing/i'                                             => '',
        '/job shadowing\s*,?/i'                                            => '',
        '/Visits? to schools? with[^.<]*\./iu'                             => '',
        '/visits to schools with[^.<]*\./iu'                               => '',
        // ES
        '/,\s*visitas a (colegios|centros)[^.,<]*/iu'                      => '',
        '/visitas a (colegios|centros)[^.,<]*[,\.]/iu'                     => '',
        '/Visitas a centros con programas[^.<]*\./iu'                      => '',
    ];
    foreach ($reemplazos as $patron => $r) {
        $html = preg_replace($patron, $r, $html);
    }

    // Limpieza de comas/espacios huérfanos resultantes
    $html = preg_replace('/\s{2,}/u', ' ', $html);
    $html = preg_replace('/,\s*([,.])/u', '$1', $html);

    return trim($html);
}

function limpiar_extracto(string $txt): string {
    $reemplazos = [
        '/,\s*school visits?/i'                            => '',
        '/school visits?\s*,?/i'                           => '',
        '/,\s*job shadowing/i'                             => '',
        '/job shadowing\s*,?/i'                            => '',
        '/Visits? to schools? with[^.,]*[,\.]/iu'          => '',
        '/,\s*visitas a (colegios|centros)[^.,]*/iu'       => '',
        '/visitas a (colegios|centros)[^.,]*[,\.]/iu'      => '',
    ];
    foreach ($reemplazos as $patron => $r) {
        $txt = preg_replace($patron, $r, $txt);
    }
    $txt = preg_replace('/\s{2,}/u', ' ', $txt);
    $txt = preg_replace('/,\s*([,.])/u', '$1', $txt);
    return trim($txt);
}

// ───────────────────────────────────────────────────────────────────────────
//  PROGRAMA del nuevo curso Spanish Language & Flamenco (10 días, 4 bloques)
// ───────────────────────────────────────────────────────────────────────────
function programa_flamenco(): array {
    $filas = [];
    $orden = 0;

    $coffee = bloque_coffee('en');

    // ─── DAY 1 — Welcome
    $welcome = dia_1_welcome('en');
    // Day 1 has 4 blocks (special): Welcome 09-11, Coffee, Local Welcome + Spanish intro 11:30-12:30, Flamenco intro 12:30-13:30
    $filas[] = ['dia' => 1, 'titulo' => $welcome[0][0], 'descripcion' => $welcome[0][1], 'horario' => HORARIO_M1, 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = ['dia' => 1, 'titulo' => $coffee[0], 'descripcion' => $coffee[1], 'horario' => HORARIO_BR, 'tipo' => 'actividad', 'orden' => ++$orden];
    $filas[] = ['dia' => 1, 'titulo' => 'Local Welcome Guide + Spanish placement', 'descripcion' => 'Cultural welcome from the EuryGo team and a relaxed placement check: where each participant stands in Spanish (A2–B1), what they expect from the course and a first taste of the language we will live in for two weeks.', 'horario' => '11:30 – 12:30', 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = ['dia' => 1, 'titulo' => 'Flamenco — Introductory session', 'descripcion' => 'What is flamenco? First contact with its roots in Jerez, the main palos and the body-rhythm-voice triangle that will be our toolbox for the next two weeks.', 'horario' => '12:30 – 13:30', 'tipo' => 'sesion', 'orden' => ++$orden];

    // ─── DAYS 2-4 (Tuesday-Thursday Week 1) — Foundations
    $temas_w1 = [
        2 => ['Vocabulario profesional docente: el aula en español', 'Práctica oral, role-playing, conversación guiada', 'Palmas y compás — primera incursión rítmica'],
        3 => ['Rutinas y horarios escolares en español', 'Práctica comunicativa: situaciones de aula', 'Los palos del flamenco — bulerías, soleá, alegrías'],
        4 => ['Expresiones cotidianas y vida en Jerez', 'Conversación libre + corrección guiada', 'Compás de 12 tiempos: el corazón del flamenco'],
    ];
    foreach ([2,3,4] as $d) {
        $filas[] = ['dia' => $d, 'titulo' => 'Spanish Language — Morning session', 'descripcion' => $temas_w1[$d][0] . '. Vocabulario y comunicación oral orientada al contexto educativo y profesional, con material que vas a usar en tu día a día docente.', 'horario' => HORARIO_M1, 'tipo' => 'sesion', 'orden' => ++$orden];
        $filas[] = ['dia' => $d, 'titulo' => $coffee[0], 'descripcion' => $coffee[1], 'horario' => HORARIO_BR, 'tipo' => 'actividad', 'orden' => ++$orden];
        $filas[] = ['dia' => $d, 'titulo' => 'Spanish Language — Consolidation', 'descripcion' => $temas_w1[$d][1] . '. Activación inmediata de los contenidos de la mañana mediante actividades comunicativas y dinámicas en parejas.', 'horario' => '11:30 – 12:30', 'tipo' => 'sesion', 'orden' => ++$orden];
        $filas[] = ['dia' => $d, 'titulo' => 'Flamenco — Practical session', 'descripcion' => $temas_w1[$d][2] . '. Taller práctico diario: rhythm, palmas and ensemble work. Tools you can transfer to the music and movement of your own classroom.', 'horario' => '12:30 – 13:30', 'tipo' => 'sesion', 'orden' => ++$orden];
    }

    // ─── DAY 5 (Friday Week 1) — Consolidation + Sevilla cultural moment kept as session
    $filas[] = ['dia' => 5, 'titulo' => 'Spanish Language — Week 1 consolidation', 'descripcion' => 'Repaso integral de la semana: lo aprendido sobre el aula española, vocabulario docente y rutinas. Mini-evaluación formativa amistosa y prepara la base para la profundización en la semana 2.', 'horario' => HORARIO_M1, 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = ['dia' => 5, 'titulo' => $coffee[0], 'descripcion' => $coffee[1], 'horario' => HORARIO_BR, 'tipo' => 'actividad', 'orden' => ++$orden];
    $filas[] = ['dia' => 5, 'titulo' => 'Spanish Language — Storytelling workshop', 'descripcion' => 'Taller comunicativo: cada participante cuenta una historia de su aula o de su semana en Jerez en español. Fluidez, expresividad y vocabulario en acción.', 'horario' => '11:30 – 12:30', 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = ['dia' => 5, 'titulo' => 'Flamenco — First choreographed sequence', 'descripcion' => 'Cerramos la semana 1 hilando palmas, compás y los primeros pasos de movimiento en una secuencia coreográfica corta. La base sobre la que construiremos la actuación final.', 'horario' => '12:30 – 13:30', 'tipo' => 'sesion', 'orden' => ++$orden];

    // ─── DAYS 6-8 (Monday-Wednesday Week 2) — Deepening
    $temas_w2 = [
        6 => ['Comunicación con familias en español', 'Role-playing: tutorías difíciles', 'El cante — escuchar y entender la voz del flamenco'],
        7 => ['Describir tu metodología docente en español', 'Presentaciones cortas en español + feedback', 'La guitarra flamenca — su papel y su lenguaje'],
        8 => ['El sistema educativo español explicado en español', 'Debate guiado: comparativa con tu sistema', 'El baile — expresión corporal e improvisación'],
    ];
    foreach ([6,7,8] as $d) {
        $filas[] = ['dia' => $d, 'titulo' => 'Spanish Language — Morning session', 'descripcion' => $temas_w2[$d][0] . '. Lengua avanzada aplicada a situaciones reales de un centro educativo español.', 'horario' => HORARIO_M1, 'tipo' => 'sesion', 'orden' => ++$orden];
        $filas[] = ['dia' => $d, 'titulo' => $coffee[0], 'descripcion' => $coffee[1], 'horario' => HORARIO_BR, 'tipo' => 'actividad', 'orden' => ++$orden];
        $filas[] = ['dia' => $d, 'titulo' => 'Spanish Language — Consolidation', 'descripcion' => $temas_w2[$d][1] . '. Práctica intensiva para asentar la confianza al hablar en contextos profesionales reales.', 'horario' => '11:30 – 12:30', 'tipo' => 'sesion', 'orden' => ++$orden];
        $filas[] = ['dia' => $d, 'titulo' => 'Flamenco — Practical session', 'descripcion' => $temas_w2[$d][2] . '. Cómo el ritmo, la expresión corporal y la improvisación se convierten en competencias transferibles a tu aula.', 'horario' => '12:30 – 13:30', 'tipo' => 'sesion', 'orden' => ++$orden];
    }

    // ─── DAY 9 (Thursday Week 2) — Dress rehearsal
    $filas[] = ['dia' => 9, 'titulo' => 'Spanish Language — Final consolidation', 'descripcion' => 'Conversación avanzada, vocabulario profesional consolidado y reflexión final sobre el progreso. Cada participante prepara una intervención corta para el día 10.', 'horario' => HORARIO_M1, 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = ['dia' => 9, 'titulo' => $coffee[0], 'descripcion' => $coffee[1], 'horario' => HORARIO_BR, 'tipo' => 'actividad', 'orden' => ++$orden];
    $filas[] = ['dia' => 9, 'titulo' => 'Spanish Language — Speaking practice', 'descripcion' => 'Práctica oral abierta: discusión en grupo sobre lo aprendido, sobre Jerez, sobre lo que cada uno se lleva. Pure speaking, pure confidence.', 'horario' => '11:30 – 12:30', 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = ['dia' => 9, 'titulo' => 'Flamenco — Dress rehearsal', 'descripcion' => 'Ensayo general de la actuación grupal del día siguiente. Ajuste de compás, coreografía y dinámicas de grupo. Última oportunidad para pulir antes de la mini-actuación final.', 'horario' => '12:30 – 13:30', 'tipo' => 'sesion', 'orden' => ++$orden];

    // ─── DAY 10 (Friday Week 2) — Closing
    $filas[] = ['dia' => 10, 'titulo' => 'Final Spanish review & free conversation', 'descripcion' => 'Repaso lingüístico final, conversación libre y reflexión sobre el viaje de dos semanas. Cada participante comparte su intervención preparada — en español, claro.', 'horario' => HORARIO_M1, 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = ['dia' => 10, 'titulo' => $coffee[0], 'descripcion' => $coffee[1], 'horario' => HORARIO_BR, 'tipo' => 'actividad', 'orden' => ++$orden];
    $filas[] = ['dia' => 10, 'titulo' => 'Group flamenco mini-performance', 'descripcion' => 'El momento que llevamos dos semanas construyendo. Los participantes muestran en grupo lo aprendido: compás, palmas, movimiento y expresión. Cámaras encendidas — esto se va a casa con vosotros.', 'horario' => '11:30 – 12:30', 'tipo' => 'sesion', 'orden' => ++$orden];
    $filas[] = ['dia' => 10, 'titulo' => 'Final reflection & Europass certification', 'descripcion' => 'Reflexión final guiada, evaluación del curso y entrega de los certificados Europass. El sello oficial que convierte estas dos semanas en CPD reconocido para tu carrera y tu centro.', 'horario' => '12:30 – 13:30', 'tipo' => 'sesion', 'orden' => ++$orden];

    return $filas;
}

// ───────────────────────────────────────────────────────────────────────────
//  Descripción HTML del curso de Flamenco (sin bloque OPTIONAL — se añade aparte)
// ───────────────────────────────────────────────────────────────────────────
function descripcion_flamenco(): string {
    return <<<'HTML'
<h2>Course Description</h2>
<p>A two-week course that combines learning Spanish as a foreign language with full immersion in flamenco — Andalusia's living cultural expression. Two disciplines that complete each other: the language, and the art that expresses it best.</p>

<p>For two weeks in Jerez de la Frontera — the very birthplace of flamenco — you will work every day on the Spanish you would actually use in a Spanish school: classroom vocabulary, staff room conversations, communication with families, and the everyday life of a working educator. And every day will close with a hands-on flamenco workshop: rhythm, body and stage, taught by professional flamenco artists from Jerez.</p>

<h3>Why pair Spanish with flamenco?</h3>
<p>Because flamenco is not a tourist extra here — it is a real pedagogical tool. Rhythm, body expression and improvisation are competences that transfer directly to your classroom. Anything you can move with palmas, you can teach. Anything you can improvise on stage, you can do in front of 28 teenagers on a Monday morning.</p>

<h3>Learning objectives</h3>
<ul>
<li>Improve Spanish communicative competence (A2–B1) in real educational contexts</li>
<li>Master the vocabulary of the Spanish school environment: classroom, staff room, families</li>
<li>Discover the roots, palos and rhythmic structure of flamenco in its birthplace</li>
<li>Acquire practical tools of rhythm, body expression and improvisation transferable to any classroom</li>
<li>End the course with a group flamenco mini-performance — a unique experience to take home</li>
</ul>

<h3>What is included?</h3>
<ul>
<li>40 hours of training over two weeks (Spanish + flamenco, Monday to Friday)</li>
<li>Teaching materials, digital resources and a dedicated flamenco toolkit</li>
<li>Europass certificate of attendance</li>
<li>Civil liability insurance</li>
<li>Welcome cultural guide of Jerez and Andalusia</li>
</ul>

<h3>What is NOT included?</h3>
<ul>
<li>Accommodation and meals (optional, quoted separately — see below)</li>
<li>International transport</li>
<li>Travel health insurance</li>
</ul>

<p><strong>Note:</strong> This course is 100% fundable with Erasmus+ KA1 grants. EuryGo can assist you with the application if needed. No prior Spanish required beyond A2 — and absolutely no flamenco experience required.</p>
HTML;
}

// ═══════════════════════════════════════════════════════════════════════════
//   SELECT: cursos afectados
// ═══════════════════════════════════════════════════════════════════════════
$cursos = $db->query("SELECT id, titulo, slug, idioma, estado, duracion_dias, fecha_inicio, fecha_fin, extracto, descripcion FROM cursos ORDER BY idioma, titulo")->fetchAll();
$flamenco_existe = (int)$db->query("SELECT COUNT(*) FROM cursos WHERE slug = '" . SLUG_FLAMENCO . "'")->fetchColumn() > 0;

// Para preview: pre-construir resumen de cambios
$resumen = [];
foreach ($cursos as $c) {
    if ($c['slug'] === SLUG_FLAMENCO) continue;
    $contenido = contenido_curado($c['slug']) ?? contenido_generico($c['titulo'], $c['idioma']);
    $nuevos    = generar_programa_5_dias($contenido, $c['idioma']);

    $actuales = $db->prepare("SELECT dia, titulo, horario, tipo FROM cursos_programa WHERE curso_id = :id ORDER BY dia, orden");
    $actuales->execute([':id' => $c['id']]);
    $actuales = $actuales->fetchAll();

    $tiene_visita_colegio = preg_match('/(school visit|job shadow|visita.*centro|visita.*colegio)/iu', $c['descripcion'] . ' ' . $c['extracto']);

    $resumen[$c['id']] = [
        'curso'         => $c,
        'curado'        => contenido_curado($c['slug']) !== null,
        'actuales'      => $actuales,
        'nuevos'        => $nuevos,
        'tiene_visita'  => (bool)$tiene_visita_colegio,
    ];
}

// ═══════════════════════════════════════════════════════════════════════════
//   APPLY: ejecuta cambios dentro de transacción
// ═══════════════════════════════════════════════════════════════════════════
$log = [];
$errores_apply = [];

if ($mode === 'apply') {
    try {
        $db->beginTransaction();

        // ── PARTE 1: cursos existentes (excepto flamenco)
        foreach ($cursos as $c) {
            if ($c['slug'] === SLUG_FLAMENCO) continue;

            $contenido = contenido_curado($c['slug']) ?? contenido_generico($c['titulo'], $c['idioma']);
            $nuevos    = generar_programa_5_dias($contenido, $c['idioma']);

            // 1.1 Reemplazar programa
            $db->prepare("DELETE FROM cursos_programa WHERE curso_id = :id")->execute([':id' => $c['id']]);
            $ins = $db->prepare("INSERT INTO cursos_programa (curso_id, dia, titulo, descripcion, horario, tipo, orden) VALUES (:cid, :dia, :titulo, :desc, :horario, :tipo, :orden)");
            foreach ($nuevos as $r) {
                $ins->execute([
                    ':cid' => $c['id'], ':dia' => $r['dia'], ':titulo' => $r['titulo'],
                    ':desc' => $r['descripcion'], ':horario' => $r['horario'],
                    ':tipo' => $r['tipo'], ':orden' => $r['orden'],
                ]);
            }

            // 1.2 Limpiar y enriquecer descripción + extracto
            $nuevo_extracto    = limpiar_extracto((string)$c['extracto']);
            $nueva_descripcion = limpiar_descripcion((string)$c['descripcion']);
            $nueva_descripcion = rtrim($nueva_descripcion) . "\n\n" . bloque_opcional_html($c['idioma']);

            $db->prepare("UPDATE cursos SET extracto = :e, descripcion = :d, duracion_dias = 5 WHERE id = :id")
               ->execute([':e' => $nuevo_extracto, ':d' => $nueva_descripcion, ':id' => $c['id']]);

            $log[] = "✓ Curso [{$c['id']}] {$c['idioma']} {$c['titulo']} — programa reemplazado ({" . count($nuevos) . "} filas), descripción limpia + bloque OPTIONAL añadido.";
        }

        // ── PARTE 2: crear curso de flamenco si no existe
        if (!$flamenco_existe) {
            $ins = $db->prepare("INSERT INTO cursos (titulo, slug, idioma, extracto, descripcion, precio, duracion_dias, plazas, fecha_inicio, fecha_fin, ubicacion, estado, destacado, meta_title, meta_description) VALUES (:t,:s,:i,:e,:d,:p,:du,:pl,:fi,:ff,:ub,:est,:des,:mt,:md)");
            $desc = descripcion_flamenco() . "\n\n" . bloque_opcional_html('en');
            $ins->execute([
                ':t'   => 'Spanish Language & Flamenco',
                ':s'   => SLUG_FLAMENCO,
                ':i'   => 'en',
                ':e'   => 'A two-week immersive course in Jerez combining Spanish for teachers (A2–B1) with hands-on flamenco workshops. The language plus the art that expresses it best — born in the very streets where you will study.',
                ':d'   => $desc,
                ':p'   => 480.00,
                ':du'  => 10,
                ':pl'  => 15,
                ':fi'  => '2026-10-05',
                ':ff'  => '2026-10-16',
                ':ub'  => 'Jerez de la Frontera, Cádiz, Spain',
                ':est' => 'publicado',
                ':des' => 0,
                ':mt'  => 'Spanish & Flamenco Course for Teachers in Jerez — Erasmus+ KA1 | EuryGo',
                ':md'  => 'Two-week KA1 immersive course in Jerez for European teachers: Spanish for the school context plus daily flamenco workshops in the birthplace of the art. 480 € · Europass certificate.',
            ]);
            $flamenco_id = (int)$db->lastInsertId();

            // Programa
            $insp = $db->prepare("INSERT INTO cursos_programa (curso_id, dia, titulo, descripcion, horario, tipo, orden) VALUES (:cid,:dia,:t,:d,:h,:tipo,:o)");
            foreach (programa_flamenco() as $r) {
                $insp->execute([':cid' => $flamenco_id, ':dia' => $r['dia'], ':t' => $r['titulo'], ':d' => $r['descripcion'], ':h' => $r['horario'], ':tipo' => $r['tipo'], ':o' => $r['orden']]);
            }

            // Ediciones
            $ed = $db->prepare("INSERT INTO cursos_ediciones (curso_id, fecha_inicio, fecha_fin, plazas_totales, plazas_disponibles, estado, destacada) VALUES (:cid,:fi,:ff,15,15,'abierta',:dest)");
            $ed->execute([':cid' => $flamenco_id, ':fi' => '2026-10-05', ':ff' => '2026-10-16', ':dest' => 1]);
            $ed->execute([':cid' => $flamenco_id, ':fi' => '2027-02-01', ':ff' => '2027-02-12', ':dest' => 0]);

            $log[] = "✓ Curso NUEVO creado: Spanish Language & Flamenco (id={$flamenco_id}) + 40 filas de programa + 2 ediciones.";
        } else {
            $log[] = "ℹ Curso Spanish Language & Flamenco ya existía — no se reinserta (idempotencia).";
        }

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
  <title>Update v8 — EuryGo</title>
  <meta name="robots" content="noindex, nofollow">
  <style>
    body { font-family: system-ui, -apple-system, sans-serif; max-width: 1100px; margin: 32px auto; padding: 0 20px; color:#1f2937; }
    h1 { color:#0c4a6e; border-bottom:3px solid #0284c7; padding-bottom:8px; }
    .box { padding:14px 18px; border-radius:10px; margin:12px 0; }
    .ok    { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
    .warn  { background:#fffbeb; border:1px solid #fde68a; color:#92400e; }
    .err   { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
    .info  { background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; }
    table  { width:100%; border-collapse:collapse; margin:8px 0 16px; font-size:0.88rem; }
    th,td  { padding:6px 10px; border:1px solid #e5e7eb; text-align:left; vertical-align:top; }
    th     { background:#f1f5f9; font-weight:600; }
    .pill  { display:inline-block; padding:2px 8px; border-radius:99px; font-size:0.72rem; font-weight:700; text-transform:uppercase; }
    .pill--es { background:#fde68a; color:#92400e; }
    .pill--en { background:#bfdbfe; color:#1e40af; }
    .pill--ok { background:#bbf7d0; color:#166534; }
    .pill--warn { background:#fde68a; color:#92400e; }
    .btn-apply { display:inline-block; padding:14px 28px; background:#dc2626; color:#fff; font-weight:700; border-radius:8px; text-decoration:none; font-size:1.05rem; margin-top:12px; }
    .btn-apply:hover { background:#991b1b; }
    pre { background:#f8fafc; padding:10px 12px; border-radius:6px; overflow-x:auto; font-size:0.82rem; }
    code { background:#f1f5f9; padding:1px 6px; border-radius:4px; font-size:0.85rem; }
    details { margin: 6px 0; }
    summary { cursor: pointer; font-weight: 600; }
  </style>
</head>
<body>
  <h1>Update v8 — Estandarización KA1</h1>

  <?php if ($mode === 'apply'): ?>
    <?php if (empty($errores_apply)): ?>
      <div class="box ok"><strong>✓ APPLY completado.</strong> Todos los cambios commiteados en transacción.</div>
      <h2>Log de cambios</h2>
      <ol>
        <?php foreach ($log as $linea): ?>
          <li><?= htmlspecialchars($linea) ?></li>
        <?php endforeach; ?>
      </ol>
      <div class="box warn"><strong>IMPORTANTE:</strong> borra este archivo del servidor:<br><code>/admin/setup/update_v8.php</code></div>
    <?php else: ?>
      <div class="box err"><strong>✗ APPLY abortado y revertido (ROLLBACK).</strong> Detalles:<br><pre><?= htmlspecialchars(implode("\n\n", $errores_apply)) ?></pre></div>
    <?php endif; ?>
  <?php else: ?>
    <div class="box info">
      <strong>Modo PREVIEW.</strong> No se ha modificado nada en la BD todavía.<br>
      Revisa los cursos afectados abajo. Para ejecutar:
      <br><br><a class="btn-apply" href="?mode=apply&amp;confirm=YES" onclick="return confirm('¿Confirmas que quieres EJECUTAR todos los cambios en producción? Esto modificará la base de datos.');">▶ Aplicar cambios ahora</a>
    </div>

    <h2>Cursos detectados (<?= count($cursos) ?>)</h2>
    <table>
      <thead><tr><th>ID</th><th>Idioma</th><th>Título</th><th>Slug</th><th>Estado</th><th>Días BD</th><th>Contenido</th><th>Visitas a colegios?</th></tr></thead>
      <tbody>
      <?php foreach ($cursos as $c): ?>
        <?php $info = $resumen[$c['id']] ?? null; ?>
        <tr>
          <td><?= $c['id'] ?></td>
          <td><span class="pill pill--<?= $c['idioma'] ?>"><?= strtoupper($c['idioma']) ?></span></td>
          <td><?= htmlspecialchars($c['titulo']) ?></td>
          <td><code><?= htmlspecialchars($c['slug']) ?></code></td>
          <td><?= $c['estado'] ?></td>
          <td><?= $c['duracion_dias'] ?></td>
          <td>
            <?php if ($c['slug'] === SLUG_FLAMENCO): ?>
              <em>(no se toca — es el nuevo)</em>
            <?php elseif ($info && $info['curado']): ?>
              <span class="pill pill--ok">Curado</span>
            <?php else: ?>
              <span class="pill pill--warn">Genérico</span>
            <?php endif; ?>
          </td>
          <td><?= ($info && $info['tiene_visita']) ? '<strong style="color:#dc2626;">SÍ — se limpia</strong>' : 'No' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <h2>Curso nuevo a crear</h2>
    <?php if ($flamenco_existe): ?>
      <div class="box warn">El slug <code><?= SLUG_FLAMENCO ?></code> ya existe — el APPLY lo saltará (idempotente).</div>
    <?php else: ?>
      <div class="box info">Se creará: <strong>Spanish Language &amp; Flamenco</strong> (slug <code><?= SLUG_FLAMENCO ?></code>), idioma EN, 480 €, 10 días, 15 plazas. Ediciones: 5–16 oct 2026 + 1–12 feb 2027.</div>
    <?php endif; ?>

    <h2>Detalle del nuevo programa por curso</h2>
    <p style="color:#64748b;">Para cada curso afectado (excepto el flamenco), el programa quedará en exactamente <strong>15 filas</strong> (5 días × 3 bloques: 09:00–11:00, 11:00–11:30, 11:30–13:30). El miércoles 11:30–13:30 será la <em>visita cultural al centro histórico de Jerez</em>.</p>

    <?php foreach ($resumen as $cid => $info): ?>
      <details>
        <summary>[<?= $cid ?>] <?= strtoupper($info['curso']['idioma']) ?> — <?= htmlspecialchars($info['curso']['titulo']) ?> (<?= $info['curado'] ? 'curado' : 'genérico' ?>)</summary>
        <table>
          <thead><tr><th>Día</th><th>Horario</th><th>Tipo</th><th>Título</th></tr></thead>
          <tbody>
            <?php foreach ($info['nuevos'] as $n): ?>
              <tr><td><?= $n['dia'] ?></td><td><code><?= htmlspecialchars($n['horario']) ?></code></td><td><?= $n['tipo'] ?></td><td><?= htmlspecialchars($n['titulo']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </details>
    <?php endforeach; ?>

    <h2>Bloques HTML OPTIONAL — vista previa (EN)</h2>
    <?= bloque_opcional_html('en') ?>

    <h2>Bloques HTML OPTIONAL — vista previa (ES)</h2>
    <?= bloque_opcional_html('es') ?>
  <?php endif; ?>

  <hr style="margin:32px 0;">
  <p style="font-size:0.8rem; color:#94a3b8;">EuryGo Update v8 — 2026-05-19</p>
</body>
</html>
