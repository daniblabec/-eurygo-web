<?php
/**
 * ACTUALIZACIÓN v11 — EuryGo: Insertar artículos del blog
 *                     "Jerez de la Frontera como destino Erasmus+" (ES + EN)
 *
 * Crea el par bilingüe en la tabla `articulos` y los vincula con
 * `traduccion_id` para que el switch ES/EN del header navegue entre ellos.
 *
 *  - Slug ES: jerez-destino-erasmus-plus
 *  - Slug EN: jerez-erasmus-plus-destination
 *  - Categoría: erasmus
 *  - Estado: publicado
 *  - tiempo_lectura: 6 minutos
 *  - Sin imagen de portada (se puede añadir después desde /admin/articulos.php)
 *
 * USO:
 *   /admin/setup/update_v11.php                       → PREVIEW (no escribe)
 *   /admin/setup/update_v11.php?mode=apply&confirm=YES  → APPLY (transacción)
 *
 * Requiere sesión admin. BORRAR del servidor tras ejecutar.
 *
 * ⚠ Antes de APPLY, hacer backup de la BD (solo la tabla afectada):
 *     /admin/setup/backup-db.php?tablas=articulos
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

$db   = get_db();
$mode = ($_GET['mode'] ?? 'preview') === 'apply' && ($_GET['confirm'] ?? '') === 'YES' ? 'apply' : 'preview';

const SLUG_ES = 'jerez-destino-erasmus-plus';
const SLUG_EN = 'jerez-erasmus-plus-destination';

// ───────────────────────────────────────────────────────────────────────────
//  CONTENIDO HTML — versión ES
// ───────────────────────────────────────────────────────────────────────────
function contenido_es(): string {
    return <<<'HTML'
<p>España es, año tras año, uno de los destinos preferidos por los centros educativos europeos que diseñan proyectos de movilidad KA1. La razón es sencilla: combina infraestructura formativa madura, una red consolidada de entidades acreditadas y un patrimonio cultural que convierte cada estancia profesional en una experiencia memorable. La <strong>formación Erasmus+ en España</strong> no se limita a aulas y certificados — es una experiencia que los participantes recuerdan durante años.</p>

<p>Dentro de ese mapa, <strong>Jerez de la Frontera</strong> ocupa un lugar singular. No es una capital saturada de visitantes ni un destino que vendan los grandes operadores: es una ciudad andaluza viva, con identidad propia, donde la formación se respira al mismo ritmo que el flamenco, el caballo y el vino de Jerez. Para un coordinador KA1 que busca calidad pedagógica y autenticidad cultural a partes iguales, Jerez es una respuesta seria.</p>

<p>En EuryGo acompañamos a centros y agencias europeas que eligen Jerez como destino para su movilidad. Nuestro papel es el de <strong>asesores</strong>: orientamos en el diseño del proyecto, en la elección de cursos y en la coordinación cultural local. No gestionamos los fondos directamente — eso es competencia de tu centro y del SEPIE — pero sí te acompañamos en cada decisión para que la inversión KA1 dé el máximo retorno formativo y humano.</p>

<h2>Tres visitas culturales imprescindibles</h2>

<p>Una semana de formación en Jerez se enriquece radicalmente cuando los participantes salen del aula y entran en contacto con la cultura local. Estas tres experiencias son, en nuestra opinión de quienes vivimos aquí, las que más impacto dejan.</p>

<h3>La Real Escuela Andaluza del Arte Ecuestre</h3>

<p>No es un espectáculo turístico al uso. <em>"Cómo bailan los caballos andaluces"</em> es una coreografía ecuestre única en el mundo, en la que jinetes y caballos cartujanos ejecutan pasos de doma clásica, doma vaquera y enganches al ritmo de música española en directo. La Real Escuela es <strong>patrimonio vivo</strong> de Jerez: además del espectáculo, se puede visitar la cuadra, el museo del enganche y los entrenamientos abiertos. Para un docente europeo es una ventana directa a la identidad cultural andaluza — y, sin proponérselo, una lección magistral sobre disciplina, tradición y arte que justifica por sí sola la visita.</p>

<h3>Cata en las Bodegas de Jerez</h3>

<p>Jerez da nombre a una de las <strong>Denominaciones de Origen más antiguas y prestigiosas de España</strong>, reconocida internacionalmente como Sherry. Bodegas centenarias como González Byass, Tío Pepe o Lustau abren sus puertas a visitas guiadas que combinan historia, arquitectura andaluza, oficio bodeguero y una cata final en el corazón de las criaderas. Más allá del vino, lo que se transmite son siglos de comercio internacional con el norte de Europa: los lazos entre Jerez y los puertos británicos, neerlandeses y daneses son una historia europea apasionante. Una buena cata en Jerez no es ocio — es patrimonio en estado puro.</p>

<h3>El barrio de Santiago y el flamenco</h3>

<p>El <strong>flamenco está reconocido por la UNESCO</strong> como Patrimonio Cultural Inmaterial de la Humanidad, y Jerez es una de sus tres cunas. El barrio de Santiago, con sus calles estrechas, sus tabancos y sus peñas flamencas, es donde se vive el flamenco más auténtico — el de los nombres de la familia Soto, los Carpio o los Agujetas. Una visita guiada por Santiago, una velada en una peña con cante en vivo, o un taller introductorio al compás de palmas y la voz, son experiencias que cambian la forma en que un participante europeo mira el sur de España. Para los grupos KA1 es además una herramienta pedagógica real: ritmo, expresión corporal e improvisación son competencias transferibles a cualquier aula.</p>

<h2>Localización estratégica en el sur de España</h2>

<p>Una de las ventajas operativas de elegir Jerez como base es su <strong>localización estratégica</strong>. Estás a 30 minutos en tren de <strong>Cádiz</strong> — la ciudad continuamente habitada más antigua de Europa occidental — y a poco más de una hora de <strong>Sevilla</strong>, con su Alcázar y Catedral declarados Patrimonio de la Humanidad. Eso significa que puedes diseñar excursiones de medio día o jornada completa sin mover la base formativa: los participantes duermen, desayunan y reciben sus sesiones en Jerez, y por la tarde o un sábado conocen ciudades que están en la lista de cualquier viajero culto.</p>

<p>La conectividad aérea también acompaña. Jerez tiene aeropuerto internacional (XRY) con vuelos a varias capitales europeas; <strong>Sevilla (SVQ)</strong> está a una hora por autopista y conecta con prácticamente toda Europa; y <strong>Málaga (AGP)</strong>, a algo más de dos horas, es uno de los hubs aéreos más completos del sur. Esta triangulación facilita enormemente la logística de movilidades KA1 con grupos llegando desde distintos países y aeropuertos.</p>

<h2>Gastronomía y costa: el valor añadido de Jerez</h2>

<p>La formación es el corazón de toda movilidad KA1, pero la <strong>calidad de vida durante la estancia</strong> es lo que convierte un proyecto correcto en uno inolvidable — y, en términos prácticos, lo que hace que los participantes vuelvan a su centro hablando del proyecto y motivando a colegas para el siguiente.</p>

<p>La <strong>gastronomía jerezana y gaditana</strong> es una experiencia cultural en sí misma. El recorrido por los bares del centro a la hora del aperitivo — tortillitas de camarones, pescaíto frito, atún de almadraba, mojama, queso payoyo — es el aula informal donde tus participantes practican vocabulario en español, descubren productos locales y construyen vínculos con sus compañeros europeos. No subestimes el poder de una tarde de tapeo bien diseñada como complemento a una sesión intensiva de formación por la mañana.</p>

<p>Y a un cuarto de hora en coche, la <strong>Costa de la Luz</strong>: Sanlúcar de Barrameda, donde el Guadalquivir entra en el Atlántico; Chipiona, Rota y El Puerto de Santa María. Playas abiertas, atardeceres infinitos y un litoral mucho menos masificado que la costa mediterránea. Para una movilidad de primavera o principios de verano, es un activo enorme: el viernes por la tarde, todo el grupo puede estar tomando el aire del Atlántico antes de cerrar la semana.</p>

<h2>Tu próxima movilidad KA1 en Jerez, con EuryGo a tu lado</h2>

<p>Elegir destino para una movilidad <strong>Erasmus+ destino Jerez</strong> no es solo una decisión logística. Es una decisión pedagógica y cultural que va a marcar a tus participantes y a tu centro. Jerez combina, en una misma ciudad y a precios razonables, lo que muchos destinos europeos solo ofrecen por fragmentos: rigor formativo, identidad cultural fuerte, gastronomía excelente, calidad de vida y conectividad real.</p>

<p>En EuryGo no gestionamos tus fondos KA1 — eso lo hacen tu centro y tu Agencia Nacional — pero sí te acompañamos en todo lo demás: asesoría sobre cursos disponibles, recomendaciones de alojamiento, diseño del programa cultural y conexión con la red local. Si estás diseñando tu próximo proyecto de <strong>movilidad educativa Andalucía</strong> o de <strong>KA1 España</strong>, hablemos.</p>

<div class="article__cta">
  <h3>¿Listo para diseñar tu próxima movilidad KA1 en Jerez?</h3>
  <p>Te asesoramos sin compromiso sobre cómo aprovechar al máximo tu próxima formación.</p>
  <a href="/#contact" class="btn btn--gold">Contacta con nosotros</a>
</div>
HTML;
}

// ───────────────────────────────────────────────────────────────────────────
//  CONTENIDO HTML — versión EN
// ───────────────────────────────────────────────────────────────────────────
function contenido_en(): string {
    return <<<'HTML'
<p>Year after year, Spain ranks among the top destinations chosen by European schools planning KA1 mobility projects. The reasons are clear: a mature training infrastructure, a well-established network of accredited partners, and a cultural depth that turns every professional stay into something memorable. <strong>Erasmus+ training in Spain</strong> is rarely just about classrooms and certificates — it's an experience your colleagues will talk about for years.</p>

<p>Within that map, <strong>Jerez de la Frontera</strong> holds a special place. It's not a tourist-saturated capital, nor a destination pushed by mass operators: it's a real Andalusian city, with its own strong identity, where professional development breathes alongside flamenco, horses and sherry. For a KA1 coordinator looking for a destination that combines pedagogical quality with cultural authenticity in equal measure, Jerez is a serious answer.</p>

<p>At EuryGo, we accompany European schools and agencies that choose Jerez as their destination for educational mobility. Our role is that of <strong>advisors</strong>: we guide you in designing the project, choosing the right courses and coordinating the cultural side on the ground. We do <strong>not</strong> manage your National Agency funds directly — that remains the responsibility of your school and your National Agency, SEPIE in Spain or its equivalent in your country — but we walk with you through every decision so that your KA1 investment delivers the highest training and human returns.</p>

<h2>Three cultural visits you cannot miss</h2>

<p>A week of training in Jerez gains enormous depth when participants step out of the classroom and into the city itself. These three experiences are, in our local view, the ones that leave the deepest impression.</p>

<h3>The Royal Andalusian School of Equestrian Art</h3>

<p>This is not the usual tourist show. <em>"How the Andalusian Horses Dance"</em> is a one-of-a-kind equestrian ballet in which riders and Carthusian horses perform classical dressage, Spanish-style dressage and traditional carriage driving to live Spanish music. The Royal School is <strong>living heritage</strong> in Jerez: beyond the performance itself, visitors can explore the stables, the carriage museum and open training sessions. For a European teacher, it's a direct window into Andalusian cultural identity — and, almost unintentionally, a masterclass in discipline, tradition and art that's worth the visit on its own.</p>

<h3>Sherry tasting in Jerez's historic bodegas</h3>

<p>Jerez gives its name to one of the <strong>oldest and most prestigious Designations of Origin in Spain</strong>, internationally recognised as Sherry. Centenarian wineries such as González Byass, Tío Pepe and Lustau open their doors to guided tours that combine history, Andalusian architecture, the craft of the winemaker and a final tasting deep in the cellars. Beyond the wine itself, what really comes across is centuries of international trade with northern Europe — the bonds between Jerez and British, Dutch and Danish ports are a fascinating European story. A good tasting in Jerez is not a leisure activity; it's heritage in its purest form.</p>

<h3>The Santiago neighbourhood and flamenco</h3>

<p><strong>Flamenco is recognised by UNESCO</strong> as Intangible Cultural Heritage of Humanity, and Jerez is one of its three main birthplaces. The Santiago neighbourhood — with its narrow streets, its <em>tabancos</em> (traditional sherry taverns) and its <em>peñas flamencas</em> (flamenco clubs) — is where the most authentic flamenco lives, the kind kept alive by names like the Soto family, the Carpio and the Agujetas. A guided walk through Santiago, an evening in a <em>peña</em> with live <em>cante</em>, or an introductory workshop on rhythm, palmas and voice, are experiences that change how a participant looks at southern Europe. For KA1 groups, it's also a practical pedagogical tool: rhythm, body expression and improvisation are competences directly transferable to any classroom back home.</p>

<h2>Strategically located in southern Spain</h2>

<p>One of the operational advantages of choosing Jerez as your base is its <strong>strategic location</strong>. You're 30 minutes by train from <strong>Cádiz</strong> — the oldest continuously inhabited city in Western Europe — and just over an hour from <strong>Seville</strong>, whose Alcázar and Cathedral are UNESCO World Heritage sites. That means you can plan half-day or full-day excursions without ever moving your training base: your participants sleep, have breakfast and attend their sessions in Jerez, and in the afternoon or on a Saturday they explore cities that belong on any cultured traveller's bucket list.</p>

<p>Air connectivity matches. Jerez has an international airport (XRY) with flights to several European capitals; <strong>Seville (SVQ)</strong> is an hour away by motorway and connects to virtually all of Europe; and <strong>Málaga (AGP)</strong>, just over two hours away, is one of the most complete air hubs in southern Europe. This triangulation makes the logistics of KA1 mobility — with groups arriving from different countries and airports — remarkably manageable.</p>

<h2>Gastronomy and coast: the added value of Jerez</h2>

<p>Training is at the heart of any KA1 mobility, but the <strong>quality of life during the stay</strong> is what turns a competent project into an unforgettable one — and, in practical terms, what makes participants return to their schools talking about the experience and convincing colleagues to join the next mobility.</p>

<p>Andalusian and Cádiz <strong>gastronomy is itself a cultural experience</strong>. The informal tour of the bars in the historic centre at aperitif time — <em>tortillitas de camarones</em> (crispy shrimp fritters), <em>pescaíto frito</em> (lightly fried fish), red tuna from the local <em>almadrabas</em>, <em>mojama</em> (cured tuna), <em>payoyo</em> cheese — is the informal classroom where your participants practise Spanish vocabulary, discover local products and build bonds with European colleagues. Don't underestimate the value of a well-designed tapas afternoon as the perfect complement to an intensive morning training session.</p>

<p>And fifteen minutes away by car lies the <strong>Costa de la Luz</strong>: Sanlúcar de Barrameda, where the Guadalquivir meets the Atlantic; Chipiona, Rota, El Puerto de Santa María. Wide-open beaches, endless sunsets and a coastline far less crowded than the Mediterranean side. For a spring or early-summer mobility, this is a major asset: by Friday afternoon the whole group can be breathing Atlantic air before closing the week.</p>

<h2>Your next KA1 mobility in Jerez, with EuryGo by your side</h2>

<p>Choosing a destination for an <strong>Erasmus+ destination Jerez</strong> project is not just a logistical decision. It's a pedagogical and cultural choice that will shape your participants and your school. Jerez combines, in a single city and at reasonable prices, what many European destinations only offer in fragments: training rigour, strong cultural identity, excellent food, quality of life and real connectivity.</p>

<p>At EuryGo we don't manage your KA1 funds — your school and your National Agency do that — but we accompany you in everything else: advice on available courses, accommodation recommendations, cultural programme design and connection with the local network. If you're planning your next <strong>KA1 mobility Andalusia</strong> project or your next <strong>teacher training Spain Erasmus</strong> experience, let's talk.</p>

<div class="article__cta">
  <h3>Ready to plan your next KA1 mobility in Jerez?</h3>
  <p>We'll advise you, with no commitment, on how to get the most out of your next training.</p>
  <a href="/en/#contact" class="btn btn--gold">Get in touch with us</a>
</div>
HTML;
}

// ───────────────────────────────────────────────────────────────────────────
//  METADATOS de cada artículo
// ───────────────────────────────────────────────────────────────────────────
$datos_es = [
    'titulo'           => 'Jerez de la Frontera: el destino perfecto para tus formaciones Erasmus+',
    'subtitulo'        => 'Cultura viva, conexiones estratégicas y calidad de vida — por qué Jerez se está convirtiendo en el destino KA1 favorito para coordinadores europeos exigentes.',
    'extracto'         => 'España es uno de los destinos preferidos para movilidades Erasmus+ KA1, y Jerez de la Frontera ocupa un lugar singular: ciudad andaluza viva con identidad propia, donde formación, cultura y conectividad se combinan a precios razonables. EuryGo te acompaña como asesor en el diseño de tu próxima movilidad.',
    'contenido'        => contenido_es(),
    'idioma'           => 'es',
    'categoria'        => 'erasmus',
    'autor'            => 'Equipo EuryGo',
    'slug'             => SLUG_ES,
    'meta_title'       => 'Jerez de la Frontera: el mejor destino KA1 para formación docente Erasmus+ en España',
    'meta_description' => 'Descubre por qué Jerez de la Frontera es el destino ideal para tu formación Erasmus+ en España: cultura, conexiones y asesoría KA1 personalizada con EuryGo.',
    'tiempo_lectura'   => 6,
    'publicado'        => 1,
    'fecha_publicacion'=> date('Y-m-d H:i:s'),
    'imagen_portada'   => null,
    'alt_imagen'       => 'Vista del centro histórico de Jerez de la Frontera, destino para formación Erasmus+ KA1',
];

$datos_en = [
    'titulo'           => 'Jerez de la Frontera: The Perfect Destination for Your Erasmus+ Training',
    'subtitulo'        => 'Living culture, strategic connections and quality of life — why Jerez is becoming the KA1 destination of choice for discerning European coordinators.',
    'extracto'         => 'Spain ranks among the top destinations for Erasmus+ KA1 mobility, and Jerez de la Frontera holds a special place: a real Andalusian city with strong identity, combining training rigour, cultural authenticity and connectivity at reasonable prices. EuryGo accompanies you as an advisor when planning your next mobility.',
    'contenido'        => contenido_en(),
    'idioma'           => 'en',
    'categoria'        => 'erasmus',
    'autor'            => 'EuryGo Team',
    'slug'             => SLUG_EN,
    'meta_title'       => 'Jerez de la Frontera: The Top KA1 Destination for Erasmus+ Teacher Training in Spain',
    'meta_description' => 'Discover why Jerez de la Frontera is the ideal destination for Erasmus+ training in Spain: culture, connections and personalised KA1 advice from EuryGo.',
    'tiempo_lectura'   => 6,
    'publicado'        => 1,
    'fecha_publicacion'=> date('Y-m-d H:i:s'),
    'imagen_portada'   => null,
    'alt_imagen'       => 'View of the historic centre of Jerez de la Frontera, destination for Erasmus+ KA1 training',
];

// ───────────────────────────────────────────────────────────────────────────
//  Cargar estado actual de la BD (preview)
// ───────────────────────────────────────────────────────────────────────────
function cargar_articulo_por_slug(PDO $db, string $slug, string $idioma): ?array {
    $st = $db->prepare("SELECT id, titulo, traduccion_id FROM articulos WHERE slug = :s AND idioma = :i LIMIT 1");
    $st->execute([':s' => $slug, ':i' => $idioma]);
    $r = $st->fetch();
    return $r ?: null;
}

$art_es = cargar_articulo_por_slug($db, SLUG_ES, 'es');
$art_en = cargar_articulo_por_slug($db, SLUG_EN, 'en');

// ───────────────────────────────────────────────────────────────────────────
//  Función upsert: INSERT si no existe, UPDATE si existe (idempotente)
// ───────────────────────────────────────────────────────────────────────────
function upsert_articulo(PDO $db, array $d, ?int $id_existente): int {
    if ($id_existente) {
        $sql = "UPDATE articulos SET titulo=:titulo, subtitulo=:subtitulo, extracto=:extracto,
                contenido=:contenido, idioma=:idioma, categoria=:categoria, autor=:autor,
                slug=:slug, meta_title=:meta_title, meta_description=:meta_description,
                tiempo_lectura=:tiempo_lectura, publicado=:publicado, imagen_portada=:imagen_portada,
                alt_imagen=:alt_imagen WHERE id=:id";
        $params = $d;
        unset($params['fecha_publicacion']); // no se actualiza al re-ejecutar
        $params['id'] = $id_existente;
        $st = $db->prepare($sql);
        $st->execute(array_combine(
            array_map(fn($k) => ':' . $k, array_keys($params)),
            array_values($params)
        ));
        return $id_existente;
    }

    $sql = "INSERT INTO articulos
        (titulo, subtitulo, extracto, contenido, idioma, categoria, autor, slug,
         meta_title, meta_description, tiempo_lectura, publicado, fecha_publicacion,
         imagen_portada, alt_imagen)
        VALUES
        (:titulo, :subtitulo, :extracto, :contenido, :idioma, :categoria, :autor, :slug,
         :meta_title, :meta_description, :tiempo_lectura, :publicado, :fecha_publicacion,
         :imagen_portada, :alt_imagen)";
    $st = $db->prepare($sql);
    $st->execute(array_combine(
        array_map(fn($k) => ':' . $k, array_keys($d)),
        array_values($d)
    ));
    return (int)$db->lastInsertId();
}

// ───────────────────────────────────────────────────────────────────────────
//  APPLY
// ───────────────────────────────────────────────────────────────────────────
$log = [];
$errores_apply = [];

if ($mode === 'apply') {
    try {
        $db->beginTransaction();

        $id_es = upsert_articulo($db, $datos_es, $art_es['id'] ?? null);
        $log[] = ($art_es ? "✓ ES actualizado (id={$id_es})" : "✓ ES insertado (id={$id_es})");

        $id_en = upsert_articulo($db, $datos_en, $art_en['id'] ?? null);
        $log[] = ($art_en ? "✓ EN actualizado (id={$id_en})" : "✓ EN insertado (id={$id_en})");

        // Vincular ambos artículos entre sí
        $db->prepare("UPDATE articulos SET traduccion_id = :tid WHERE id = :id")
           ->execute([':tid' => $id_en, ':id' => $id_es]);
        $db->prepare("UPDATE articulos SET traduccion_id = :tid WHERE id = :id")
           ->execute([':tid' => $id_es, ':id' => $id_en]);
        $log[] = "✓ Vinculadas traducciones: ES(id={$id_es}) ↔ EN(id={$id_en})";

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        $errores_apply[] = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Update v11 — Artículos Jerez (blog)</title>
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
    details { margin: 12px 0; }
    summary { cursor: pointer; font-weight: 600; color:#0c4a6e; }
    .preview-html { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px 20px; margin-top:8px; max-height: 400px; overflow:auto; }
  </style>
</head>
<body>
  <h1>Update v11 — Artículos de blog "Jerez como destino Erasmus+"</h1>

  <?php if ($mode === 'apply'): ?>
    <?php if (empty($errores_apply)): ?>
      <div class="box ok"><strong>✓ APPLY completado.</strong> Cambios commiteados en transacción.</div>
      <h2>Log</h2>
      <ol>
        <?php foreach ($log as $l): ?>
          <li><?= htmlspecialchars($l) ?></li>
        <?php endforeach; ?>
      </ol>
      <div class="box info">URLs públicas a verificar:<br>
        <strong>ES:</strong> <code>https://www.eurygo.com/blog/<?= SLUG_ES ?>/</code><br>
        <strong>EN:</strong> <code>https://www.eurygo.com/en/blog/<?= SLUG_EN ?>/</code><br>
        El switch ES/EN del header debería navegar entre ambos (vía <code>traduccion_id</code>).
      </div>
      <div class="box warn"><strong>IMPORTANTE:</strong> borra este archivo del servidor:<br><code>/admin/setup/update_v11.php</code></div>
    <?php else: ?>
      <div class="box err"><strong>✗ APPLY abortado (ROLLBACK).</strong><br><pre><?= htmlspecialchars(implode("\n\n", $errores_apply)) ?></pre></div>
    <?php endif; ?>
  <?php else: ?>
    <div class="box info">
      <strong>Modo PREVIEW.</strong> No se ha modificado nada. Revisa abajo el plan.
      <br><br><a class="btn-apply" href="?mode=apply&amp;confirm=YES" onclick="return confirm('¿Aplicar los cambios? Inserta/actualiza los 2 artículos del blog y los vincula.');">▶ Aplicar cambios ahora</a>
    </div>

    <h2>Estado actual</h2>
    <table>
      <tr><th>Artículo</th><th>Slug</th><th>ID</th><th>Título actual</th><th>traduccion_id</th></tr>
      <tr><td>ES</td><td><code><?= SLUG_ES ?></code>
          </td><td><?= $art_es ? (int)$art_es['id'] : '<em>no existe — se creará</em>' ?></td>
          <td><?= $art_es ? htmlspecialchars($art_es['titulo']) : '—' ?></td>
          <td><?= $art_es ? ($art_es['traduccion_id'] ?? '<em>NULL</em>') : '—' ?></td></tr>
      <tr><td>EN</td><td><code><?= SLUG_EN ?></code>
          </td><td><?= $art_en ? (int)$art_en['id'] : '<em>no existe — se creará</em>' ?></td>
          <td><?= $art_en ? htmlspecialchars($art_en['titulo']) : '—' ?></td>
          <td><?= $art_en ? ($art_en['traduccion_id'] ?? '<em>NULL</em>') : '—' ?></td></tr>
    </table>

    <h2>Lo que hará APPLY</h2>
    <ol>
      <li>Si el artículo ES no existe → INSERT en <code>articulos</code>; si existe → UPDATE (idempotente).</li>
      <li>Lo mismo con la versión EN.</li>
      <li>Vincular ambos con <code>articulos.traduccion_id</code> en las dos direcciones.</li>
      <li>Ambos quedan <code>publicado = 1</code> y visibles inmediatamente en <code>/blog/</code> y <code>/en/blog/</code>.</li>
      <li><strong>No</strong> se asigna imagen de portada — se puede subir después desde <code>/admin/articulos.php</code>.</li>
    </ol>

    <h2>Metadatos a insertar</h2>
    <table>
      <tr><th>Campo</th><th>ES</th><th>EN</th></tr>
      <tr><td>titulo</td><td><?= htmlspecialchars($datos_es['titulo']) ?></td><td><?= htmlspecialchars($datos_en['titulo']) ?></td></tr>
      <tr><td>subtitulo</td><td><?= htmlspecialchars($datos_es['subtitulo']) ?></td><td><?= htmlspecialchars($datos_en['subtitulo']) ?></td></tr>
      <tr><td>extracto</td><td><?= htmlspecialchars($datos_es['extracto']) ?></td><td><?= htmlspecialchars($datos_en['extracto']) ?></td></tr>
      <tr><td>categoria</td><td><code><?= $datos_es['categoria'] ?></code></td><td><code><?= $datos_en['categoria'] ?></code></td></tr>
      <tr><td>autor</td><td><?= htmlspecialchars($datos_es['autor']) ?></td><td><?= htmlspecialchars($datos_en['autor']) ?></td></tr>
      <tr><td>tiempo_lectura</td><td><?= $datos_es['tiempo_lectura'] ?> min</td><td><?= $datos_en['tiempo_lectura'] ?> min</td></tr>
      <tr><td>meta_title</td><td><?= htmlspecialchars($datos_es['meta_title']) ?></td><td><?= htmlspecialchars($datos_en['meta_title']) ?></td></tr>
      <tr><td>meta_description</td><td><?= htmlspecialchars($datos_es['meta_description']) ?></td><td><?= htmlspecialchars($datos_en['meta_description']) ?></td></tr>
    </table>

    <details>
      <summary>Vista previa del contenido HTML — ES</summary>
      <div class="preview-html"><?= contenido_es() ?></div>
    </details>

    <details>
      <summary>Vista previa del contenido HTML — EN</summary>
      <div class="preview-html"><?= contenido_en() ?></div>
    </details>
  <?php endif; ?>

  <hr style="margin:32px 0;">
  <p style="font-size:0.8rem; color:#94a3b8;">EuryGo Update v11 — 2026-05-26</p>
</body>
</html>
