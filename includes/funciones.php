<?php
/**
 * Funciones helper reutilizables: slugs, categorías, etc.
 */

/**
 * Genera un slug URL-friendly desde un texto.
 */
function generar_slug(string $texto): string {
    $slug = mb_strtolower($texto, 'UTF-8');
    // Eliminar acentos
    $slug = strtr($slug, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
        'ñ'=>'n','ü'=>'u','à'=>'a','è'=>'e','ì'=>'i',
        'ò'=>'o','ù'=>'u','ä'=>'a','ö'=>'o','â'=>'a',
        'ê'=>'e','î'=>'i','ô'=>'o','û'=>'u','ç'=>'c',
    ]);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Devuelve el nombre legible de una categoría según el idioma.
 */
function nombre_categoria(string $categoria, string $idioma = 'es'): string {
    $categorias = [
        'es' => [
            'centros'      => 'Para Centros',
            'agencias'     => 'Para Agencias',
            'erasmus'      => 'Erasmus+',
            'novedades'    => 'Novedades',
            'casos-exito'  => 'Casos de Éxito',
        ],
        'en' => [
            'centros'      => 'For Schools',
            'agencias'     => 'For Agencies',
            'erasmus'      => 'Erasmus+',
            'novedades'    => 'News',
            'casos-exito'  => 'Success Stories',
        ],
    ];
    return $categorias[$idioma][$categoria] ?? $categoria;
}

/**
 * Devuelve la clase CSS para el badge de categoría.
 */
function clase_categoria(string $categoria): string {
    $clases = [
        'centros'     => '',
        'agencias'    => 'blog-card__category--green',
        'erasmus'     => 'blog-card__category--gold',
        'novedades'   => 'blog-card__category--blue',
        'casos-exito' => 'blog-card__category--green',
    ];
    return $clases[$categoria] ?? '';
}

/**
 * Estima el tiempo de lectura en minutos basándose en el recuento de palabras.
 */
function estimar_lectura(string $contenido): int {
    $palabras = str_word_count(strip_tags($contenido));
    $minutos = max(1, (int) ceil($palabras / 200));
    return $minutos;
}

/**
 * Formatea una fecha para mostrarla en el frontend.
 */
function formato_fecha(string $fecha, string $idioma = 'es'): string {
    $ts = strtotime($fecha);
    if ($idioma === 'en') {
        return date('d M Y', $ts);
    }
    $meses = ['enero','febrero','marzo','abril','mayo','junio',
              'julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return date('d', $ts) . ' ' . $meses[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

/**
 * Trunca un texto a un número máximo de caracteres sin cortar palabras.
 */
function truncar(string $texto, int $max = 300): string {
    if (mb_strlen($texto) <= $max) return $texto;
    $cortado = mb_substr($texto, 0, $max);
    $ultimo_espacio = mb_strrpos($cortado, ' ');
    return mb_substr($cortado, 0, $ultimo_espacio) . '...';
}

/**
 * Devuelve el CTA contextual según categoría e idioma.
 */
function cta_por_categoria(string $categoria, string $idioma = 'es'): array {
    $ctas = [
        'es' => [
            'centros'  => ['titulo' => '¿Listo para tu proyecto Erasmus+?', 'texto' => 'EuryGo te acompaña de principio a fin. Hablemos.'],
            'agencias' => ['titulo' => '¿Quieres explorar una partnership?', 'texto' => 'EuryGo te acompaña desde el primer paso. Hablemos.'],
            'default'  => ['titulo' => '¿Tienes un proyecto Erasmus+?', 'texto' => 'Cuéntanos tu situación.'],
        ],
        'en' => [
            'centros'  => ['titulo' => 'Ready for your Erasmus+ project?', 'texto' => 'EuryGo guides you from start to finish. Let\'s talk.'],
            'agencias' => ['titulo' => 'Want to explore a partnership?', 'texto' => 'EuryGo supports you from the first step. Let\'s talk.'],
            'default'  => ['titulo' => 'Got an Erasmus+ project?', 'texto' => 'Tell us about your situation.'],
        ],
    ];
    $lang = $ctas[$idioma] ?? $ctas['es'];
    return $lang[$categoria] ?? $lang['default'];
}
