<?php
/**
 * ACTUALIZACIÓN v7 — EuryGo: Blog traducción + artículos KA1 (ES + EN)
 *
 * Cambios:
 * 1. Añade columna traduccion_id a la tabla articulos
 * 2. Inserta artículo EN sobre KA1 teacher training in Jerez
 * 3. Inserta artículo ES sobre cursos KA1 en Jerez
 * 4. Vincula ambos artículos entre sí (traduccion_id)
 *
 * Ejecutar UNA SOLA VEZ desde el navegador y BORRAR después.
 */

require_once __DIR__ . '/../../includes/db.php';

$db = get_db();
$errores = [];
$pasos = 0;

// ─── PASO 1: Añadir columna traduccion_id a articulos ───
try {
    $col = $db->query("SHOW COLUMNS FROM articulos LIKE 'traduccion_id'")->fetch();
    if (!$col) {
        $db->exec("ALTER TABLE articulos ADD COLUMN traduccion_id INT NULL DEFAULT NULL AFTER idioma");
        echo "<p>✅ Columna traduccion_id añadida a articulos.</p>";
    } else {
        echo "<p>ℹ️ Columna traduccion_id ya existe en articulos.</p>";
    }
    $pasos++;
} catch (Throwable $e) {
    $errores[] = "PASO 1: " . $e->getMessage();
}

// ─── PASO 2: Insertar artículo KA1 en inglés ───
$id_en = null;
try {
    $check = $db->prepare("SELECT id FROM articulos WHERE slug = :slug AND idioma = 'en' LIMIT 1");
    $check->execute([':slug' => 'erasmus-ka1-teacher-training-jerez-andalusia']);
    $existente = $check->fetch();
    if ($existente) {
        $id_en = (int)$existente['id'];
        echo "<p>ℹ️ El artículo KA1 EN ya existe (ID: {$id_en}).</p>";
    } else {
        $contenido_en = <<<'HTML'
<p>If you're a European educator looking for a meaningful professional development experience abroad, <strong>Erasmus+ KA1 structured courses in Jerez de la Frontera</strong> offer exactly that — and much more. Nestled in the heart of Andalusia, Jerez combines rich cultural heritage with a welcoming, authentic Spanish atmosphere that makes every learning moment unforgettable.</p>

<h2>Why Choose Jerez de la Frontera for Your KA1 Mobility?</h2>
<p>Jerez is not your typical tourist destination — and that's precisely what makes it ideal for teacher training. Unlike overcrowded cities, Jerez allows educators to experience <strong>real Spanish life</strong>, interact with locals, and immerse themselves in a vibrant cultural setting. From flamenco and sherry bodegas to historic plazas and Andalusian gastronomy, Jerez offers a rich cultural backdrop that naturally complements structured professional training.</p>
<p>Located just 12 km from the Atlantic coast and well connected to Seville and Cádiz, Jerez is easy to reach via its own international airport (XRY) or through Seville (SVQ).</p>

<h2>What Does a KA1 Structured Course in Jerez Look Like?</h2>
<p>Our 5-day programmes are designed to combine intensive professional learning with cultural and school-based experiences. A typical week includes:</p>
<ul>
  <li><strong>Expert-led sessions</strong> on topics like the Spanish education system, AI in education, inclusive practices, or active methodologies.</li>
  <li><strong>School visits</strong> to local primary and secondary schools, where participants observe classes and speak with Spanish teachers.</li>
  <li><strong>Cultural activities</strong> such as guided heritage walks, flamenco workshops, and Andalusian cooking experiences.</li>
  <li><strong>Peer learning</strong> through group reflection, exchange of good practices, and collaborative planning.</li>
</ul>
<p>Each course is structured to meet Erasmus+ quality standards, and participants receive a <strong>certificate of attendance and Europass Mobility Document</strong> upon completion.</p>

<h2>Who Are These Courses For?</h2>
<p>Our training programmes are open to all <strong>teachers, headmasters, school counsellors, and non-teaching educational staff</strong> from EU and associated countries. Whether you work in early childhood, primary, secondary, or vocational education, there's a programme tailored to your professional profile.</p>
<p>Courses are delivered in <strong>English</strong>, with optional beginner-level Spanish language modules available for those interested.</p>

<h2>Popular Course Topics</h2>
<p>Among our most requested structured courses, you'll find:</p>
<ul>
  <li><strong>The Spanish Education System</strong> — Discover how Spain organises public education, from policy to classroom practice.</li>
  <li><strong>AI and Digital Tools in Education</strong> — Explore how artificial intelligence and edtech are transforming European classrooms.</li>
  <li><strong>Inclusive Education</strong> — Learn strategies for supporting diverse learners, including students with special educational needs.</li>
  <li><strong>Spanish Language and Culture for Educators</strong> — Combine basic Spanish lessons with deep cultural immersion.</li>
</ul>

<h2>How to Enrol</h2>
<p>Enrolling is simple:</p>
<ol>
  <li>Browse our <a href="/en/cursos/">course catalogue</a> and choose your preferred dates.</li>
  <li>Submit a pre-registration form or contact us directly.</li>
  <li>Include the course in your school's Erasmus+ KA1 mobility project (budget line: "Course fees").</li>
</ol>
<p>We also provide support letters, course descriptions, and PIC numbers to help your school with the Erasmus+ application.</p>

<h2>Why EuryGo?</h2>
<p>EuryGo is a specialised education services provider based in Jerez de la Frontera. We work exclusively with European schools and Erasmus+ agencies to deliver high-quality structured courses, job shadowing, and school observation programmes. Our team combines local expertise with deep knowledge of the Erasmus+ framework.</p>

<div class="article__cta">
  <h3>Ready to come to Jerez?</h3>
  <p>Check our upcoming dates and reserve your spot.</p>
  <a href="/en/cursos/" class="btn btn--gold">View KA1 Courses</a>
</div>

<div class="article__cta" style="background: linear-gradient(135deg, #1a5276, #0e3c5e); margin-top: var(--space-md);">
  <h3>Your school doesn't have Erasmus+ accreditation yet?</h3>
  <p>We can help with that too.</p>
  <a href="/en/#contact" class="btn btn--gold">Talk to EuryGo</a>
</div>
HTML;

        $stmt = $db->prepare("INSERT INTO articulos
            (titulo, subtitulo, extracto, contenido, idioma, categoria, autor, slug,
             meta_title, meta_description, tiempo_lectura, publicado, fecha_publicacion, imagen_portada, alt_imagen)
            VALUES
            (:titulo, :subtitulo, :extracto, :contenido, :idioma, :categoria, :autor, :slug,
             :meta_title, :meta_description, :tiempo_lectura, :publicado, :fecha_publicacion, :imagen_portada, :alt_imagen)");

        $stmt->execute([
            ':titulo'       => 'Erasmus+ KA1 Teacher Training in Jerez de la Frontera, Andalusia',
            ':subtitulo'    => 'Professional development, school visits and cultural immersion in the heart of southern Spain',
            ':extracto'     => 'Discover why Jerez de la Frontera is the ideal destination for Erasmus+ KA1 structured courses. Five-day programmes combining expert-led training, school visits, and Andalusian cultural immersion for European educators.',
            ':contenido'    => $contenido_en,
            ':idioma'       => 'en',
            ':categoria'    => 'erasmus',
            ':autor'        => 'Equipo EuryGo',
            ':slug'         => 'erasmus-ka1-teacher-training-jerez-andalusia',
            ':meta_title'   => 'KA1 Teacher Training in Jerez, Andalusia | EuryGo',
            ':meta_description' => 'Structured Erasmus+ KA1 courses for European teachers in Jerez de la Frontera. Spanish education, AI, inclusion, school visits and cultural immersion.',
            ':tiempo_lectura' => 7,
            ':publicado'    => 1,
            ':fecha_publicacion' => date('Y-m-d H:i:s'),
            ':imagen_portada' => null,
            ':alt_imagen'   => 'Teachers participating in an Erasmus+ KA1 training course in Jerez de la Frontera',
        ]);

        $id_en = (int)$db->lastInsertId();
        echo "<p>✅ Artículo KA1 EN insertado (ID: {$id_en})</p>";
    }
    $pasos++;
} catch (Throwable $e) {
    $errores[] = "PASO 2: " . $e->getMessage();
}

// ─── PASO 3: Insertar artículo KA1 en español ───
$id_es = null;
try {
    $check = $db->prepare("SELECT id FROM articulos WHERE slug = :slug AND idioma = 'es' LIMIT 1");
    $check->execute([':slug' => 'cursos-ka1-formacion-docente-jerez-andalucia']);
    $existente = $check->fetch();
    if ($existente) {
        $id_es = (int)$existente['id'];
        echo "<p>ℹ️ El artículo KA1 ES ya existe (ID: {$id_es}).</p>";
    } else {
        $contenido_es = <<<'HTML'
<h2>¿Qué es una movilidad Erasmus+ KA1 para docentes?</h2>

<p>Si eres docente en Europa, probablemente hayas oído hablar del programa Erasmus+ KA1 — la línea de financiación que permite al personal de centros escolares realizar estancias de formación profesional en otros países de la Unión Europea. Pero si nunca has participado, o si tu centro todavía está explorando el proceso de acreditación, es posible que te preguntes: ¿por dónde empiezo y adónde debería ir?</p>

<p>KA1 (Acción Clave 1) financia cursos de formación de corta duración, estancias de observación profesional (job shadowing) y actividades de enseñanza en el extranjero para docentes, equipos directivos y otro personal escolar. La financiación cubre las tasas del curso, el viaje, el alojamiento y la manutención, lo que significa que tu desarrollo profesional en el extranjero puede no costarle nada a tu centro de su propio presupuesto.</p>

<p>La clave está en elegir el destino adecuado y el curso adecuado. Y ahí es exactamente donde entra EuryGo.</p>

<h2>¿Por qué Jerez de la Frontera?</h2>

<p>Jerez de la Frontera no es la primera ciudad que viene a la mente cuando un docente europeo piensa en España. La mayoría se dirige a Madrid, Barcelona o Valencia. Y precisamente por eso, Jerez es una mejor elección.</p>

<p>Lo que Jerez ofrece y las grandes ciudades no pueden igualar:</p>

<ul>
  <li><strong>Andalucía auténtica.</strong> Jerez es la cuna del flamenco, del vino de Jerez y de la tradición ecuestre andaluza. Tu semana aquí será una inmersión cultural genuina, no una experiencia turística de postal.</li>
  <li><strong>Una red de centros comprometidos.</strong> EuryGo trabaja con una red de centros escolares locales con años de experiencia en proyectos europeos. Tu visita o job shadowing será un intercambio profesional real, no una visita protocolaria.</li>
  <li><strong>Clima excepcional.</strong> La primavera y el inicio del verano en Jerez significan días cálidos, cielos despejados y el aroma del azahar. Tus mañanas en el aula serán productivas; tus tardes, inolvidables.</li>
  <li><strong>Cádiz y Sevilla a un paso.</strong> Dos de las ciudades más hermosas de España están a menos de una hora. Las excursiones opcionales están incluidas en nuestro programa.</li>
  <li><strong>Coste de vida razonable.</strong> Jerez es notablemente más asequible que las grandes ciudades españolas, lo que significa que tu presupuesto KA1 cunde mucho más.</li>
</ul>

<h2>Los cursos KA1 de EuryGo en Jerez</h2>

<p>EuryGo ofrece cinco cursos de formación certificados para docentes europeos, todos de lunes a viernes con 20 horas de formación presencial. Cada curso incluye una visita guiada al centro histórico de Jerez, una actividad cultural por la tarde (espectáculo ecuestre o visita a bodega con cata) y una excursión opcional a Cádiz o Sevilla.</p>

<p>Todos los cursos se imparten en formato bilingüe español e inglés. No se requieren conocimientos previos de español.</p>

<h3>1. El Sistema Educativo Español</h3>
<p>Una visión en profundidad de cómo funcionan los centros escolares en España: estructura, legislación, la profesión docente, innovación y la realidad cotidiana del aula. Imprescindible para cualquier docente que quiera entender el contexto educativo de su país de acogida.</p>

<h3>2. IA Aplicada a la Enseñanza: Herramientas para el Aula del Futuro</h3>
<p>Formación práctica con herramientas de inteligencia artificial para la docencia: planificación de clases, generación de contenidos, evaluación personalizada y uso ético de la IA en el aula. No se requieren conocimientos técnicos previos.</p>

<h3>3. Metodologías Activas: ABP, Flipped Classroom y Aprendizaje Cooperativo</h3>
<p>Vivirás estas tres metodologías transformadoras primero como aprendiz. Te irás con un proyecto completo diseñado para tu propia asignatura y con la confianza para implementarlo desde el primer día en tu centro.</p>

<h3>4. Español para Docentes</h3>
<p>Un curso de español comunicativo diseñado específicamente para educadores: el vocabulario de la sala de profesores, el aula y la vida cotidiana en Andalucía. Jerez es tu aula: el mercado, las bodegas y las calles son parte de la lección.</p>

<h3>5. Educación Inclusiva en España</h3>
<p>El enfoque español de la diversidad, la equidad y la inclusión en el aula ordinaria. Marco legal, Diseño Universal para el Aprendizaje, estrategias prácticas y visita a un centro escolar con buenas prácticas reconocidas en inclusión.</p>

<p>Todos los cursos tienen un precio de <strong>480 € por participante</strong> y son totalmente financiables a través de tu proyecto Erasmus+ KA1.</p>

<h2>Cómo financiar tu curso con Erasmus+ KA1</h2>

<p>Si tu centro ya tiene una acreditación Erasmus+ (KA121 o KA122), puedes incluir nuestros cursos en tu próximo proyecto de movilidad y solicitar la financiación directamente a tu Agencia Nacional.</p>

<p>Si tu centro todavía no tiene acreditación — o si no estás seguro de si la tiene — EuryGo puede ayudarte. Asesoramos a centros durante todo el proceso de acreditación y trabajamos con coordinadores de toda España para que sus proyectos KA1 sean sólidos, conformes a la normativa y financiados.</p>

<p>La partida de Apoyo Organizativo (OS) en los proyectos KA1 de Erasmus+ está diseñada específicamente para cubrir los costes de coordinación y gestión, lo que significa que la carga administrativa de llevar a cabo un proyecto de movilidad no tiene por qué recaer sobre los hombros de los docentes.</p>

<h2>Cómo es una semana en Jerez con EuryGo</h2>

<p>Tu semana típica con EuryGo tiene esta estructura:</p>

<ul>
  <li><strong>De lunes a viernes por la mañana (9:00–13:00):</strong> 20 horas de formación certificada en nuestro espacio en Jerez.</li>
  <li><strong>Mañana del miércoles:</strong> Visita cultural guiada al centro histórico de Jerez: la Alcazaba, la Colegiata de San Salvador y el barrio de Santiago, cuna del flamenco.</li>
  <li><strong>Tarde del martes o jueves:</strong> Actividad cultural incluida en el programa: el espectáculo ecuestre de la Real Escuela Andaluza del Arte Ecuestre o una visita guiada a una bodega histórica de Jerez con cata de vinos.</li>
  <li><strong>Excursión opcional:</strong> Media jornada a Cádiz (la ciudad más antigua de Europa) o Sevilla (Alcázar, Plaza de España) por aproximadamente 25 € por persona.</li>
  <li><strong>Certificado:</strong> Al final de la semana recibes el certificado de participación de EuryGo — 20 horas de formación — válido como justificante de actividad formativa para tu proyecto Erasmus+ KA1 y reconocido por el SEPIE.</li>
</ul>

<h2>¿Quién viene a nuestros cursos?</h2>

<p>Nuestros participantes vienen de toda Europa: Alemania, Francia, Polonia, Italia, Países Bajos, Portugal, República Checa y muchos más. Docentes de primaria, de secundaria, equipos directivos, coordinadores de educación especial y coordinadores Erasmus+ que quieren conocer de primera mano el sistema educativo español.</p>

<p>Lo que todos tienen en común es que se van de Jerez con algo más que un certificado. Se van con nuevos colegas de toda Europa, nuevas ideas para sus aulas y, con bastante frecuencia, con ganas de volver.</p>

<h2>¿Listo para dar el paso?</h2>

<p>Tanto si tu centro ya tiene su acreditación Erasmus+ como si estás dando los primeros pasos hacia ella, EuryGo está aquí para ayudarte a sacar el máximo partido a tu movilidad KA1.</p>

<p>Consulta nuestras próximas fechas y reserva tu plaza: las plazas son limitadas para garantizar una experiencia de calidad.</p>

<div class="article__cta">
  <h3>¿Listo para venir a Jerez?</h3>
  <p>Consulta nuestras próximas convocatorias y reserva tu plaza.</p>
  <a href="/cursos/" class="btn btn--gold">Ver cursos KA1 →</a>
</div>

<div class="article__cta" style="background: linear-gradient(135deg, #1a5276, #0e3c5e); margin-top: var(--space-md);">
  <h3>¿Tu centro todavía no tiene acreditación Erasmus+?</h3>
  <p>Te ayudamos con eso también.</p>
  <a href="/#contacto" class="btn btn--gold">Habla con EuryGo →</a>
</div>
HTML;

        $stmt = $db->prepare("INSERT INTO articulos
            (titulo, subtitulo, extracto, contenido, idioma, categoria, autor, slug,
             meta_title, meta_description, tiempo_lectura, publicado, fecha_publicacion, imagen_portada, alt_imagen)
            VALUES
            (:titulo, :subtitulo, :extracto, :contenido, :idioma, :categoria, :autor, :slug,
             :meta_title, :meta_description, :tiempo_lectura, :publicado, :fecha_publicacion, :imagen_portada, :alt_imagen)");

        $stmt->execute([
            ':titulo'       => 'Tu Curso KA1 en Jerez: Todo lo que Necesitas Saber antes de Venir',
            ':subtitulo'    => 'Cómo financiar tu formación en Andalucía, qué esperar de nuestros cursos y por qué Jerez va a sorprenderte.',
            ':extracto'     => '¿Estás pensando en hacer una movilidad Erasmus+ KA1? Jerez de la Frontera ofrece formación docente certificada, un programa cultural único y uno de los destinos más especiales del sur de Europa. Aquí tienes todo lo que necesitas saber antes de solicitar tu plaza.',
            ':contenido'    => $contenido_es,
            ':idioma'       => 'es',
            ':categoria'    => 'erasmus',
            ':autor'        => 'Equipo EuryGo',
            ':slug'         => 'cursos-ka1-formacion-docente-jerez-andalucia',
            ':meta_title'   => 'Cursos de Formación KA1 en Jerez para Docentes Europeos | EuryGo',
            ':meta_description' => 'Descubre los cursos de formación Erasmus+ KA1 de EuryGo en Jerez de la Frontera: sistema educativo español, IA en el aula, metodologías activas, español para docentes e inclusión educativa. Fináncialo con tu proyecto KA1.',
            ':tiempo_lectura' => 6,
            ':publicado'    => 1,
            ':fecha_publicacion' => date('Y-m-d H:i:s'),
            ':imagen_portada' => null,
            ':alt_imagen'   => 'Docentes europeos en un curso de formación KA1 en Jerez de la Frontera',
        ]);

        $id_es = (int)$db->lastInsertId();
        echo "<p>✅ Artículo KA1 ES insertado (ID: {$id_es})</p>";
    }
    $pasos++;
} catch (Throwable $e) {
    $errores[] = "PASO 3: " . $e->getMessage();
}

// ─── PASO 4: Vincular ambos artículos (traduccion_id bidireccional) ───
try {
    if ($id_en && $id_es) {
        $db->prepare("UPDATE articulos SET traduccion_id = :tid WHERE id = :id")
           ->execute([':tid' => $id_es, ':id' => $id_en]);
        $db->prepare("UPDATE articulos SET traduccion_id = :tid WHERE id = :id")
           ->execute([':tid' => $id_en, ':id' => $id_es]);
        echo "<p>✅ Artículos vinculados: ES (ID:{$id_es}) ↔ EN (ID:{$id_en})</p>";
    } else {
        echo "<p>⚠️ No se pudieron vincular — falta algún ID (ES:{$id_es}, EN:{$id_en})</p>";
    }
    $pasos++;
} catch (Throwable $e) {
    $errores[] = "PASO 4: " . $e->getMessage();
}

// ─── RESULTADO ───
echo "<hr>";
if (empty($errores)) {
    echo "<h2 style='color:green;'>✅ Migración v7 completada — {$pasos} pasos</h2>";
    echo "<p><strong>IMPORTANTE:</strong> Borra este archivo del servidor después de ejecutarlo.</p>";
} else {
    echo "<h2 style='color:red;'>⚠️ Errores:</h2><ul>";
    foreach ($errores as $err) echo "<li>" . htmlspecialchars($err) . "</li>";
    echo "</ul>";
}
