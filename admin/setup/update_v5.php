<?php
/**
 * ACTUALIZACIÓN v5 — EuryGo: Sistema de Ediciones + Nuevos Cursos
 *
 * Cambios:
 * 1. Crea tabla cursos_ediciones (múltiples fechas por curso)
 * 2. Migra fechas existentes de cursos a ediciones
 * 3. Añade columna edicion_id a cursos_inscripciones
 * 4. Actualiza todos los precios a 480€
 * 5. Inserta 2 nuevos cursos con sus programas y ediciones
 *
 * Ejecutar UNA SOLA VEZ desde el navegador y BORRAR después.
 */

require_once __DIR__ . '/../../includes/db.php';

$db = get_db();
$errores = [];
$pasos = 0;

// ─── PASO 1: Crear tabla cursos_ediciones ───
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS cursos_ediciones (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            curso_id          INT NOT NULL,
            fecha_inicio      DATE NOT NULL,
            fecha_fin         DATE NOT NULL,
            plazas_totales    SMALLINT NOT NULL DEFAULT 25,
            plazas_disponibles SMALLINT NOT NULL DEFAULT 25,
            estado            ENUM('abierta','cerrada','cancelada','finalizada') DEFAULT 'abierta',
            destacada         TINYINT(1) DEFAULT 0,
            notas             TEXT,
            created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
            INDEX idx_curso_estado (curso_id, estado),
            INDEX idx_fecha (fecha_inicio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pasos++;
} catch (PDOException $e) {
    $errores[] = 'Crear cursos_ediciones: ' . $e->getMessage();
}

// ─── PASO 2: Migrar fechas existentes de cursos a ediciones ───
try {
    $existentes = $db->query("SELECT COUNT(*) FROM cursos_ediciones")->fetchColumn();
    if ($existentes == 0) {
        $cursos = $db->query("SELECT id, fecha_inicio, fecha_fin, plazas, inscritos FROM cursos WHERE fecha_inicio IS NOT NULL")->fetchAll();
        foreach ($cursos as $c) {
            $disponibles = max(0, $c['plazas'] - $c['inscritos']);
            $db->prepare("INSERT INTO cursos_ediciones (curso_id, fecha_inicio, fecha_fin, plazas_totales, plazas_disponibles, estado, destacada) VALUES (?, ?, ?, ?, ?, 'abierta', 1)")
               ->execute([$c['id'], $c['fecha_inicio'], $c['fecha_fin'], $c['plazas'], $disponibles]);
        }
        $pasos++;
    }
} catch (PDOException $e) {
    $errores[] = 'Migrar fechas: ' . $e->getMessage();
}

// ─── PASO 3: Añadir edicion_id a cursos_inscripciones ───
try {
    $cols = $db->query("SHOW COLUMNS FROM cursos_inscripciones LIKE 'edicion_id'")->fetchAll();
    if (empty($cols)) {
        $db->exec("ALTER TABLE cursos_inscripciones ADD COLUMN edicion_id INT NULL AFTER curso_id");
        $db->exec("ALTER TABLE cursos_inscripciones ADD INDEX idx_edicion (edicion_id)");
        // Vincular inscripciones existentes a la edición migrada
        $db->exec("
            UPDATE cursos_inscripciones ci
            JOIN cursos_ediciones ce ON ce.curso_id = ci.curso_id
            SET ci.edicion_id = ce.id
            WHERE ci.edicion_id IS NULL
        ");
        $pasos++;
    }
} catch (PDOException $e) {
    $errores[] = 'Añadir edicion_id: ' . $e->getMessage();
}

// ─── PASO 4: Actualizar todos los precios a 480€ ───
try {
    $db->exec("UPDATE cursos SET precio = 480.00");
    $pasos++;
} catch (PDOException $e) {
    $errores[] = 'Actualizar precios: ' . $e->getMessage();
}

// ─── PASO 5: Insertar nuevos cursos ───

// Verificar si ya existen
$existe_spanish = $db->query("SELECT COUNT(*) FROM cursos WHERE slug = 'spanish-language-for-teachers'")->fetchColumn();
$existe_inclusive = $db->query("SELECT COUNT(*) FROM cursos WHERE slug = 'inclusive-education-spain'")->fetchColumn();
$existe_spanish_es = $db->query("SELECT COUNT(*) FROM cursos WHERE slug = 'espanol-para-docentes'")->fetchColumn();
$existe_inclusive_es = $db->query("SELECT COUNT(*) FROM cursos WHERE slug = 'educacion-inclusiva-espana'")->fetchColumn();

// === CURSO 5a — ES: Español para Docentes ===
if (!$existe_spanish_es) {
    try {
        $db->exec("
            INSERT INTO cursos (titulo, slug, idioma, extracto, descripcion, precio, duracion_dias, plazas, fecha_inicio, fecha_fin, ubicacion, estado, destacado, meta_title, meta_description) VALUES
            ('Español para Docentes: Lengua y Cultura en Inmersión',
             'espanol-para-docentes',
             'es',
             'Curso de 5 días para docentes europeos que desean mejorar su español en un contexto profesional y cultural. Clases de lengua adaptadas al ámbito educativo, talleres de conversación e inmersión cultural en Jerez de la Frontera.',
             '<h2>Descripción del curso</h2>
<p>Este curso está diseñado para docentes europeos que desean mejorar su competencia en lengua española dentro de un contexto profesional y cultural auténtico. Combinamos clases de español adaptadas al vocabulario educativo con talleres de conversación, visitas culturales y una inmersión total en la vida de Jerez de la Frontera.</p>

<h3>Objetivos de aprendizaje</h3>
<ul>
<li>Mejorar la competencia comunicativa en español (niveles A2-B2)</li>
<li>Adquirir vocabulario específico del ámbito educativo en español</li>
<li>Desarrollar habilidades de conversación en contextos profesionales</li>
<li>Conocer la cultura española y andaluza a través de la lengua</li>
<li>Practicar el español en situaciones reales de inmersión</li>
</ul>

<h3>¿Qué incluye?</h3>
<ul>
<li>20 horas de formación (clases + talleres + actividades culturales)</li>
<li>Material didáctico y recursos digitales</li>
<li>Certificado Europass de asistencia</li>
<li>Visitas culturales guiadas en español</li>
<li>Seguro de responsabilidad civil</li>
</ul>

<h3>¿Qué NO incluye?</h3>
<ul>
<li>Alojamiento y manutención</li>
<li>Transporte internacional</li>
<li>Seguro médico de viaje</li>
</ul>

<p><strong>Nota:</strong> Este curso es financiable al 100% con fondos Erasmus+ KA1. EuryGo te asesora en la solicitud si lo necesitas.</p>',
             480.00, 5, 12,
             '2026-05-25', '2026-05-29',
             'Jerez de la Frontera, Cádiz, España',
             'publicado', 0,
             'Curso Erasmus+ KA1: Español para Docentes | EuryGo',
             'Curso de español para docentes europeos en Jerez. 5 días de inmersión lingüística y cultural. Clases adaptadas al ámbito educativo. Certificado Erasmus+.')
        ");
        $c5es_id = (int)$db->lastInsertId();

        // Programa
        $db->exec("
            INSERT INTO cursos_programa (curso_id, dia, titulo, descripcion, horario, tipo, orden) VALUES
            ($c5es_id, 1, 'Evaluación inicial y español en el aula', 'Test de nivel y formación de grupos. Sesión de español centrada en vocabulario educativo: el aula, los alumnos, las asignaturas, el sistema escolar. Dinámicas de presentación en español.', '09:30 – 14:00', 'sesion', 1),
            ($c5es_id, 1, 'Paseo lingüístico por Jerez', 'Recorrido por el centro histórico practicando español: pedir en un bar, preguntar direcciones, leer carteles. Inmersión real desde el primer día.', '17:00 – 19:00', 'actividad', 2),
            ($c5es_id, 2, 'Gramática práctica y conversación profesional', 'Taller de gramática aplicada al contexto docente. Práctica de situaciones reales: reunión de profesores, entrevista con familias, coordinación con compañeros. Role-playing en español.', '09:00 – 14:00', 'sesion', 3),
            ($c5es_id, 2, 'Taller de cocina andaluza en español', 'Clase de cocina con vocabulario gastronómico: ingredientes, instrucciones, sabores. Preparación de platos típicos andaluces mientras se practica español. Degustación conjunta.', '16:30 – 19:00', 'actividad', 4),
            ($c5es_id, 3, 'Español a través de la cultura: flamenco y tradición', 'Sesión de lengua a través del flamenco: letras, expresiones, emociones. Taller de ritmo y palmas. Vocabulario cultural: fiestas, tradiciones, gastronomía andaluza.', '09:00 – 14:00', 'sesion', 5),
            ($c5es_id, 3, 'Excursión cultural: Bodegas de Jerez', 'Visita guiada en español a una bodega tradicional. Vocabulario del vino, historia local y degustación. Práctica de comprensión oral en contexto real.', '16:30 – 19:00', 'excursion', 6),
            ($c5es_id, 4, 'Producción escrita y oral avanzada', 'Taller de escritura: redactar emails profesionales, informes escolares y mensajes a familias en español. Práctica de presentaciones orales sobre tu sistema educativo.', '09:00 – 14:00', 'sesion', 7),
            ($c5es_id, 4, 'Excursión: Cádiz en español', 'Visita guiada a Cádiz con actividades lingüísticas: yincana cultural, entrevistas a locales, lectura de patrimonio. Práctica intensiva de comprensión y expresión oral.', '15:00 – 20:00', 'excursion', 8)
        ");
        $db->exec("
            INSERT INTO cursos_programa (curso_id, dia, titulo, descripcion, horario, tipo, orden) VALUES
            ($c5es_id, 5, 'Presentaciones finales y certificación', 'Cada participante presenta en español un aspecto de su sistema educativo o una experiencia del curso. Evaluación de progreso, retroalimentación y entrega de certificados Europass.', '09:30 – 13:30', 'sesion', 9)
        ");

        // Ediciones: 3 fechas
        $db->exec("
            INSERT INTO cursos_ediciones (curso_id, fecha_inicio, fecha_fin, plazas_totales, plazas_disponibles, estado, destacada) VALUES
            ($c5es_id, '2026-05-25', '2026-05-29', 12, 12, 'abierta', 1),
            ($c5es_id, '2026-06-22', '2026-06-26', 12, 12, 'abierta', 0),
            ($c5es_id, '2026-07-13', '2026-07-17', 12, 12, 'abierta', 0)
        ");
        $pasos++;
    } catch (PDOException $e) {
        $errores[] = 'Curso Español Docentes (ES): ' . $e->getMessage();
    }
}

// === CURSO 5b — EN: Spanish Language for Teachers ===
if (!$existe_spanish) {
    try {
        $db->exec("
            INSERT INTO cursos (titulo, slug, idioma, extracto, descripcion, precio, duracion_dias, plazas, fecha_inicio, fecha_fin, ubicacion, estado, destacado, meta_title, meta_description) VALUES
            ('Spanish Language for Teachers: Language and Culture Immersion',
             'spanish-language-for-teachers',
             'en',
             'A 5-day course for European teachers who wish to improve their Spanish in a professional and cultural context. Language classes adapted to the educational field, conversation workshops and cultural immersion in Jerez de la Frontera.',
             '<h2>Course Description</h2>
<p>This course is designed for European teachers who wish to improve their Spanish language skills within an authentic professional and cultural context. We combine Spanish classes adapted to educational vocabulary with conversation workshops, cultural visits and a full immersion in the life of Jerez de la Frontera.</p>

<h3>Learning Objectives</h3>
<ul>
<li>Improve communicative competence in Spanish (levels A2-B2)</li>
<li>Acquire specific vocabulary for the educational field in Spanish</li>
<li>Develop conversation skills in professional contexts</li>
<li>Discover Spanish and Andalusian culture through the language</li>
<li>Practise Spanish in real immersion situations</li>
</ul>

<h3>What is included?</h3>
<ul>
<li>20 hours of training (classes + workshops + cultural activities)</li>
<li>Teaching materials and digital resources</li>
<li>Europass certificate of attendance</li>
<li>Guided cultural visits in Spanish</li>
<li>Civil liability insurance</li>
</ul>

<h3>What is NOT included?</h3>
<ul>
<li>Accommodation and meals</li>
<li>International transport</li>
<li>Travel health insurance</li>
</ul>

<p><strong>Note:</strong> This course is 100% fundable with Erasmus+ KA1 grants. EuryGo can assist you with the application if needed.</p>',
             480.00, 5, 12,
             '2026-05-25', '2026-05-29',
             'Jerez de la Frontera, Cádiz, Spain',
             'publicado', 0,
             'Erasmus+ KA1 Course: Spanish for Teachers | EuryGo',
             'Spanish language course for European teachers in Jerez. 5 days of linguistic and cultural immersion. Classes adapted to educational contexts. Erasmus+ certificate.')
        ");
        $c5en_id = (int)$db->lastInsertId();

        // Programa
        $db->exec("
            INSERT INTO cursos_programa (curso_id, dia, titulo, descripcion, horario, tipo, orden) VALUES
            ($c5en_id, 1, 'Initial assessment and classroom Spanish', 'Level test and group formation. Spanish session focused on educational vocabulary: the classroom, students, subjects, the school system. Introductory dynamics in Spanish.', '09:30 – 14:00', 'sesion', 1),
            ($c5en_id, 1, 'Linguistic walk through Jerez', 'Walking tour of the historic centre practising Spanish: ordering at a bar, asking for directions, reading signs. Real immersion from day one.', '17:00 – 19:00', 'actividad', 2),
            ($c5en_id, 2, 'Practical grammar and professional conversation', 'Applied grammar workshop in the teaching context. Practice of real situations: staff meetings, parent interviews, coordination with colleagues. Role-playing in Spanish.', '09:00 – 14:00', 'sesion', 3),
            ($c5en_id, 2, 'Andalusian cooking workshop in Spanish', 'Cooking class with gastronomic vocabulary: ingredients, instructions, flavours. Preparing typical Andalusian dishes while practising Spanish. Shared tasting.', '16:30 – 19:00', 'actividad', 4),
            ($c5en_id, 3, 'Spanish through culture: flamenco and tradition', 'Language session through flamenco: lyrics, expressions, emotions. Rhythm and clapping workshop. Cultural vocabulary: festivals, traditions, Andalusian gastronomy.', '09:00 – 14:00', 'sesion', 5),
            ($c5en_id, 3, 'Cultural excursion: Sherry Bodegas of Jerez', 'Guided visit in Spanish to a traditional sherry bodega. Wine vocabulary, local history and tasting. Listening comprehension practice in a real context.', '16:30 – 19:00', 'excursion', 6),
            ($c5en_id, 4, 'Advanced written and oral production', 'Writing workshop: drafting professional emails, school reports and messages to families in Spanish. Oral presentation practice about your education system.', '09:00 – 14:00', 'sesion', 7),
            ($c5en_id, 4, 'Excursion: Cádiz in Spanish', 'Guided visit to Cádiz with linguistic activities: cultural scavenger hunt, interviews with locals, heritage reading. Intensive listening and speaking practice.', '15:00 – 20:00', 'excursion', 8)
        ");
        $db->exec("
            INSERT INTO cursos_programa (curso_id, dia, titulo, descripcion, horario, tipo, orden) VALUES
            ($c5en_id, 5, 'Final presentations and certification', 'Each participant presents in Spanish an aspect of their education system or a course experience. Progress assessment, feedback and Europass certificate ceremony.', '09:30 – 13:30', 'sesion', 9)
        ");

        // Ediciones: 3 fechas
        $db->exec("
            INSERT INTO cursos_ediciones (curso_id, fecha_inicio, fecha_fin, plazas_totales, plazas_disponibles, estado, destacada) VALUES
            ($c5en_id, '2026-05-25', '2026-05-29', 12, 12, 'abierta', 1),
            ($c5en_id, '2026-06-22', '2026-06-26', 12, 12, 'abierta', 0),
            ($c5en_id, '2026-07-13', '2026-07-17', 12, 12, 'abierta', 0)
        ");
        $pasos++;
    } catch (PDOException $e) {
        $errores[] = 'Curso Spanish Teachers (EN): ' . $e->getMessage();
    }
}

// === CURSO 6a — ES: Educación Inclusiva en España ===
if (!$existe_inclusive_es) {
    try {
        $db->exec("
            INSERT INTO cursos (titulo, slug, idioma, extracto, descripcion, precio, duracion_dias, plazas, fecha_inicio, fecha_fin, ubicacion, estado, destacado, meta_title, meta_description) VALUES
            ('Educación Inclusiva en España: Estrategias y Buenas Prácticas',
             'educacion-inclusiva-espana',
             'es',
             'Curso de 5 días para docentes europeos sobre educación inclusiva en el sistema español. Protocolos de atención a la diversidad, necesidades educativas especiales, visitas a centros inclusivos y talleres prácticos en Jerez de la Frontera.',
             '<h2>Descripción del curso</h2>
<p>España ha desarrollado un modelo de educación inclusiva reconocido internacionalmente. Este curso ofrece a docentes europeos una inmersión profunda en las estrategias, protocolos y buenas prácticas de inclusión del sistema educativo español, con especial atención a la legislación vigente (LOMLOE) y su aplicación real en los centros de la provincia de Cádiz.</p>

<h3>Objetivos de aprendizaje</h3>
<ul>
<li>Comprender el marco legislativo español de atención a la diversidad</li>
<li>Conocer los protocolos de detección e intervención para NEE</li>
<li>Observar prácticas inclusivas reales en centros educativos</li>
<li>Diseñar adaptaciones curriculares y planes de apoyo</li>
<li>Intercambiar experiencias y estrategias con colegas europeos</li>
</ul>

<h3>¿Qué incluye?</h3>
<ul>
<li>20 horas de formación (sesiones + visitas + talleres)</li>
<li>Material didáctico y banco de recursos inclusivos</li>
<li>Certificado Europass de asistencia</li>
<li>Visitas a centros con programas de inclusión referentes</li>
<li>Actividades culturales en Jerez y Cádiz</li>
</ul>

<h3>¿Qué NO incluye?</h3>
<ul>
<li>Alojamiento y manutención</li>
<li>Transporte internacional</li>
<li>Seguro médico de viaje</li>
</ul>

<p><strong>Nota:</strong> Este curso es financiable al 100% con fondos Erasmus+ KA1. EuryGo te asesora en la solicitud si lo necesitas.</p>',
             480.00, 5, 15,
             '2026-06-01', '2026-06-05',
             'Jerez de la Frontera, Cádiz, España',
             'publicado', 0,
             'Curso Erasmus+ KA1: Educación Inclusiva en España | EuryGo',
             'Curso de educación inclusiva para docentes europeos en Jerez. Atención a la diversidad, NEE, visitas a centros inclusivos. 5 días, certificado Erasmus+.')
        ");
        $c6es_id = (int)$db->lastInsertId();

        // Programa
        $db->exec("
            INSERT INTO cursos_programa (curso_id, dia, titulo, descripcion, horario, tipo, orden) VALUES
            ($c6es_id, 1, 'Marco legislativo y modelo español de inclusión', 'Panorama del modelo inclusivo español: LOMLOE, decretos de atención a la diversidad, protocolos de detección y equipos de orientación. Comparativa con otros sistemas europeos. Debate participativo.', '09:30 – 14:00', 'sesion', 1),
            ($c6es_id, 1, 'Paseo de bienvenida por Jerez', 'Recorrido por el centro histórico, dinámica de presentación intercultural y establecimiento de objetivos personales para el curso.', '17:00 – 19:00', 'actividad', 2),
            ($c6es_id, 2, 'Visita a centro con aula de apoyo a la integración', 'Job shadowing en un centro público con programa de integración referente. Observación de aulas específicas, entrevista con el equipo de orientación y análisis de adaptaciones curriculares reales.', '09:00 – 14:00', 'sesion', 3),
            ($c6es_id, 2, 'Taller: Diseño Universal para el Aprendizaje (DUA)', 'Sesión práctica sobre los principios del DUA. Los participantes diseñan una actividad accesible para todos los alumnos, incluyendo aquellos con NEE, aplicable a su contexto.', '16:00 – 18:30', 'sesion', 4),
            ($c6es_id, 3, 'Necesidades educativas especiales: protocolos y recursos', 'Sesión monográfica sobre TEA, TDAH, dislexia, altas capacidades y discapacidad sensorial/motora en el contexto escolar español. Herramientas de evaluación y recursos de intervención.', '09:00 – 14:00', 'sesion', 5),
            ($c6es_id, 3, 'Excursión cultural: Bodegas de Jerez', 'Visita a una bodega tradicional de Jerez. Historia del vino, proceso de elaboración y degustación. Reflexión sobre accesibilidad en espacios culturales.', '16:30 – 19:00', 'excursion', 6),
            ($c6es_id, 4, 'Inclusión social y convivencia escolar', 'Programas de convivencia, mediación entre iguales, protocolos anti-bullying y educación emocional en el sistema español. Taller: diseñar un plan de convivencia inclusivo para tu centro.', '09:00 – 14:00', 'sesion', 7),
            ($c6es_id, 4, 'Excursión: Cádiz accesible', 'Visita guiada a Cádiz con enfoque en accesibilidad urbana y patrimonial. Análisis crítico de barreras y buenas prácticas en espacios públicos.', '15:00 – 20:00', 'excursion', 8)
        ");
        $db->exec("
            INSERT INTO cursos_programa (curso_id, dia, titulo, descripcion, horario, tipo, orden) VALUES
            ($c6es_id, 5, 'Planes de acción y certificación', 'Cada participante diseña un plan de mejora de la inclusión para su centro educativo. Presentación, evaluación entre pares, retroalimentación y entrega de certificados Europass.', '09:30 – 13:30', 'sesion', 9)
        ");

        // Ediciones: 2 fechas
        $db->exec("
            INSERT INTO cursos_ediciones (curso_id, fecha_inicio, fecha_fin, plazas_totales, plazas_disponibles, estado, destacada) VALUES
            ($c6es_id, '2026-06-01', '2026-06-05', 15, 15, 'abierta', 1),
            ($c6es_id, '2026-06-29', '2026-07-03', 15, 15, 'abierta', 0)
        ");
        $pasos++;
    } catch (PDOException $e) {
        $errores[] = 'Curso Educación Inclusiva (ES): ' . $e->getMessage();
    }
}

// === CURSO 6b — EN: Inclusive Education in Spain ===
if (!$existe_inclusive) {
    try {
        $db->exec("
            INSERT INTO cursos (titulo, slug, idioma, extracto, descripcion, precio, duracion_dias, plazas, fecha_inicio, fecha_fin, ubicacion, estado, destacado, meta_title, meta_description) VALUES
            ('Inclusive Education in Spain: Strategies and Best Practices',
             'inclusive-education-spain',
             'en',
             'A 5-day course for European teachers on inclusive education in the Spanish system. Diversity protocols, special educational needs, visits to inclusive schools and practical workshops in Jerez de la Frontera.',
             '<h2>Course Description</h2>
<p>Spain has developed an internationally recognised model of inclusive education. This course offers European teachers a deep immersion into the strategies, protocols and best practices of inclusion in the Spanish education system, with special attention to current legislation (LOMLOE) and its real-world application in schools across the province of Cádiz.</p>

<h3>Learning Objectives</h3>
<ul>
<li>Understand the Spanish legislative framework for attention to diversity</li>
<li>Learn about detection and intervention protocols for SEN</li>
<li>Observe real inclusive practices in educational centres</li>
<li>Design curricular adaptations and support plans</li>
<li>Exchange experiences and strategies with European colleagues</li>
</ul>

<h3>What is included?</h3>
<ul>
<li>20 hours of training (sessions + visits + workshops)</li>
<li>Teaching materials and inclusive resource bank</li>
<li>Europass certificate of attendance</li>
<li>Visits to schools with leading inclusion programmes</li>
<li>Cultural activities in Jerez and Cádiz</li>
</ul>

<h3>What is NOT included?</h3>
<ul>
<li>Accommodation and meals</li>
<li>International transport</li>
<li>Travel health insurance</li>
</ul>

<p><strong>Note:</strong> This course is 100% fundable with Erasmus+ KA1 grants. EuryGo can assist you with the application if needed.</p>',
             480.00, 5, 15,
             '2026-06-01', '2026-06-05',
             'Jerez de la Frontera, Cádiz, Spain',
             'publicado', 0,
             'Erasmus+ KA1 Course: Inclusive Education in Spain | EuryGo',
             'Inclusive education course for European teachers in Jerez. Attention to diversity, SEN, visits to inclusive schools. 5 days, Erasmus+ certificate.')
        ");
        $c6en_id = (int)$db->lastInsertId();

        // Programa
        $db->exec("
            INSERT INTO cursos_programa (curso_id, dia, titulo, descripcion, horario, tipo, orden) VALUES
            ($c6en_id, 1, 'Legislative framework and the Spanish inclusion model', 'Overview of the Spanish inclusive model: LOMLOE, diversity decrees, detection protocols and guidance teams. Comparison with other European systems. Participatory debate.', '09:30 – 14:00', 'sesion', 1),
            ($c6en_id, 1, 'Welcome walk through Jerez', 'Walking tour of the historic centre, intercultural icebreaker activity and setting personal goals for the course.', '17:00 – 19:00', 'actividad', 2),
            ($c6en_id, 2, 'Visit to a school with integration support unit', 'Job shadowing at a public school with a leading integration programme. Observation of specialist classrooms, interview with the guidance team and analysis of real curricular adaptations.', '09:00 – 14:00', 'sesion', 3),
            ($c6en_id, 2, 'Workshop: Universal Design for Learning (UDL)', 'Practical session on UDL principles. Participants design an accessible activity for all students, including those with SEN, applicable to their own context.', '16:00 – 18:30', 'sesion', 4),
            ($c6en_id, 3, 'Special educational needs: protocols and resources', 'Monographic session on ASD, ADHD, dyslexia, gifted students and sensory/motor disabilities in the Spanish school context. Assessment tools and intervention resources.', '09:00 – 14:00', 'sesion', 5),
            ($c6en_id, 3, 'Cultural excursion: Sherry Bodegas of Jerez', 'Visit to a traditional Jerez sherry bodega. Wine history, production process and tasting. Reflection on accessibility in cultural spaces.', '16:30 – 19:00', 'excursion', 6),
            ($c6en_id, 4, 'Social inclusion and school coexistence', 'Coexistence programmes, peer mediation, anti-bullying protocols and emotional education in the Spanish system. Workshop: designing an inclusive coexistence plan for your school.', '09:00 – 14:00', 'sesion', 7),
            ($c6en_id, 4, 'Excursion: Accessible Cádiz', 'Guided visit to Cádiz with a focus on urban and heritage accessibility. Critical analysis of barriers and best practices in public spaces.', '15:00 – 20:00', 'excursion', 8)
        ");
        $db->exec("
            INSERT INTO cursos_programa (curso_id, dia, titulo, descripcion, horario, tipo, orden) VALUES
            ($c6en_id, 5, 'Action plans and certification', 'Each participant designs an inclusion improvement plan for their school. Presentation, peer evaluation, feedback and Europass certificate ceremony.', '09:30 – 13:30', 'sesion', 9)
        ");

        // Ediciones: 2 fechas
        $db->exec("
            INSERT INTO cursos_ediciones (curso_id, fecha_inicio, fecha_fin, plazas_totales, plazas_disponibles, estado, destacada) VALUES
            ($c6en_id, '2026-06-01', '2026-06-05', 15, 15, 'abierta', 1),
            ($c6en_id, '2026-06-29', '2026-07-03', 15, 15, 'abierta', 0)
        ");
        $pasos++;
    } catch (PDOException $e) {
        $errores[] = 'Curso Inclusive Education (EN): ' . $e->getMessage();
    }
}

// ─── Resultado ───
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualización v5 — EuryGo</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 600px; margin: 60px auto; padding: 20px; }
        .ok  { color: #16a34a; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px; border-radius: 8px; margin: 10px 0; }
        .err { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; padding: 16px; border-radius: 8px; margin: 10px 0; }
        .warn { color: #d97706; background: #fffbeb; border: 1px solid #fde68a; padding: 16px; border-radius: 8px; margin: 10px 0; }
        h1 { color: #1e3a5f; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>Actualización v5 — Ediciones + Nuevos Cursos</h1>

    <?php if (empty($errores)): ?>
        <div class="ok">
            <strong>Actualización v5 completada con éxito.</strong><br>
            Pasos completados: <?= $pasos ?>
        </div>
    <?php else: ?>
        <?php foreach ($errores as $err): ?>
            <div class="err"><strong>Error:</strong> <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
        <?php if ($pasos > 0): ?>
            <div class="warn">Pasos completados antes del error: <?= $pasos ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <h3>Resumen de cambios:</h3>
    <ul>
        <li>Tabla <code>cursos_ediciones</code> creada</li>
        <li>Fechas existentes migradas a ediciones</li>
        <li>Columna <code>edicion_id</code> añadida a inscripciones</li>
        <li>Todos los precios actualizados a <strong>480€</strong></li>
        <li>Nuevos cursos: Español para Docentes (ES/EN) + Educación Inclusiva (ES/EN)</li>
    </ul>

    <div class="warn">
        <strong>IMPORTANTE:</strong> Borra este archivo del servidor después de ejecutarlo.
    </div>
</body>
</html>
