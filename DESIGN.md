---
name: EuryGo
description: Atlas Erasmus — cartografía visual europea desde Jerez, institucional sin gris ministerial.
colors:
  atlas-midnight: "#0C4A6E"
  atlas-azure: "#0284C7"
  atlas-sky: "#38BDF8"
  atlas-mist: "#7DD3FC"
  oro-erasmus-deep: "#D97706"
  oro-erasmus: "#F59E0B"
  oro-erasmus-light: "#FBBF24"
  oro-erasmus-veil: "#FDE68A"
  eu-institutional-blue: "#003399"
  paper-cream: "#FAFAF8"
  stone-cream: "#F1F0EC"
  atlas-ink: "#1B2A3D"
  slate-mist: "#64748B"
  mist-border: "#E2E8F0"
  pure-white: "#FFFFFF"
  signal-red: "#DC2626"
  signal-green: "#16A34A"
typography:
  display:
    fontFamily: "Playfair Display, Georgia, serif"
    fontSize: "clamp(2.2rem, 5vw, 3.5rem)"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "normal"
  headline:
    fontFamily: "Playfair Display, Georgia, serif"
    fontSize: "clamp(1.8rem, 4vw, 2.8rem)"
    fontWeight: 700
    lineHeight: 1.2
  title:
    fontFamily: "Playfair Display, Georgia, serif"
    fontSize: "clamp(1.3rem, 3vw, 1.8rem)"
    fontWeight: 700
    lineHeight: 1.3
  body:
    fontFamily: "DM Sans, system-ui, sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: 1.7
  label:
    fontFamily: "DM Sans, system-ui, sans-serif"
    fontSize: "0.8rem"
    fontWeight: 700
    letterSpacing: "0.1em"
rounded:
  pill: "30px"
  lg: "16px"
  md: "12px"
  sm: "8px"
  xs: "6px"
spacing:
  xs: "0.5rem"
  sm: "1rem"
  md: "1.5rem"
  lg: "2.5rem"
  xl: "4rem"
  "2xl": "6rem"
  "3xl": "8rem"
components:
  button-primary:
    backgroundColor: "{colors.atlas-azure}"
    textColor: "{colors.pure-white}"
    rounded: "{rounded.sm}"
    padding: "0.85rem 2rem"
    typography: "{typography.label}"
  button-primary-hover:
    backgroundColor: "{colors.atlas-midnight}"
    textColor: "{colors.pure-white}"
  button-gold:
    backgroundColor: "{colors.oro-erasmus}"
    textColor: "{colors.pure-white}"
    rounded: "{rounded.sm}"
    padding: "0.85rem 2rem"
  button-gold-hover:
    backgroundColor: "{colors.oro-erasmus-deep}"
    textColor: "{colors.pure-white}"
  button-eu:
    backgroundColor: "{colors.eu-institutional-blue}"
    textColor: "{colors.pure-white}"
    rounded: "{rounded.sm}"
    padding: "0.85rem 2rem"
  button-outline-white:
    backgroundColor: "transparent"
    textColor: "{colors.pure-white}"
    rounded: "{rounded.sm}"
    padding: "0.85rem 2rem"
  card:
    backgroundColor: "{colors.pure-white}"
    textColor: "{colors.atlas-ink}"
    rounded: "{rounded.lg}"
    padding: "2.5rem"
  course-card:
    backgroundColor: "{colors.pure-white}"
    textColor: "{colors.atlas-ink}"
    rounded: "{rounded.md}"
    padding: "1.5rem"
  input-text:
    backgroundColor: "{colors.paper-cream}"
    textColor: "{colors.atlas-ink}"
    rounded: "{rounded.sm}"
    padding: "0.75rem 1rem"
  hero-badge:
    backgroundColor: "rgba(255, 255, 255, 0.1)"
    textColor: "{colors.oro-erasmus-light}"
    rounded: "{rounded.pill}"
    padding: "0.4rem 1rem"
  section-tag:
    textColor: "{colors.oro-erasmus}"
    typography: "{typography.label}"
  nav-link:
    textColor: "{colors.atlas-ink}"
    typography: "{typography.label}"
---

# Design System: EuryGo

## 1. Overview

**Creative North Star: "El Atlas Erasmus"**

EuryGo se compone como un atlas europeo moderno: cartografía de la movilidad, puntos conectados por líneas finas, geografía del aprendizaje continental. La pantalla es un mapa habitable, no una página de marketing. El hero arranca con un cielo nocturno azul (`#0C4A6E → #0A2940`) atravesado por puntos luminosos y trazos de partículas; el dorado **Oro Erasmus** aparece como destello — sello, subrayado, número grande — nunca como masa. El sistema no es "página de Erasmus": es la representación cartográfica de su ecosistema desde Jerez de la Frontera.

El sistema acepta dos vocabularios de superficie que conviven sin pelearse. Por un lado, **glass institucional** en superficies persistentes: la barra de navegación es cristal esmerilado (`rgba(250,250,248,0.95) + backdrop-filter: blur(12px)`) y el badge del hero también; transmiten "presencia que no estorba". Por otro, **lift editorial** en superficies interactivas: las tarjetas reposan planas sobre crema y levitan en hover con sombra ambiente amplia. Entre ambas, la composición es respirada — espaciado clamp generoso, tipografía Playfair con peso real, ritmo variable — pensada más como revista internacional con corresponsalía en Andalucía que como portal ministerial.

Rechaza explícitamente lo que PRODUCT.md nombra: la **página institucional gris-burocrática** (banderas pegadas, azul corporativo opaco, tablas planas), el **SaaS startup genérico** (gradiente púrpura, screenshot tilt, "Trusted by 1000+ teams"), la **agencia de viajes turística** (carruseles de playas, badges "#1 en Europa"), y la **academia online** de tarjetas idénticas. La densidad es media: aire pero no vacío, datos concretos delante de adjetivos.

**Key Characteristics:**
- Hero drenched azul-medianoche con partículas y mapa de Europa; resto del sitio respirando crema.
- Dorado escaso y deliberado: sellos de sección en mayúsculas con `letter-spacing 0.1em`, subrayado de nav (2px), números de estadísticas del hero.
- Playfair Display 700 para títulos (clamp), DM Sans 400 para cuerpo a `line-height: 1.7`, justificado con guionado automático.
- Cards de borde sutil (`1px solid #E2E8F0`) y radius 16px que despegan en hover con sombra ancha de baja opacidad.
- Botones-CTA con halo tintado del propio color (`box-shadow: 0 4px 14px rgba(color, 0.3)`), no neutros.
- Cursos como sección VIP: tarjeta propia con tratamiento diferenciado (sombra más sutil, imagen 200px, badge dorado).

## 2. Colors

Paleta de tres voces: azul-atlas que ancla la institución, oro Erasmus que sella el detalle, y crema cálida que respira. La regla maestra: el dorado nunca cubre; subraya.

### Primary
- **Atlas Midnight** (`#0C4A6E`): el azul de los títulos, el fondo del hero, la sede gravitacional del sistema. Es el color que dice "esto es serio". Aparece en cada `h1–h4`, en el footer, en el gradiente de los CTAs primarios y como dominante del 100% del hero.
- **Atlas Azure** (`#0284C7`): el azul de los enlaces y los botones primarios. Vive en la acción: link hover, focus glow (`box-shadow: 0 0 0 3px rgba(2,132,199,0.1)`), gradientes de CTA primario.
- **Atlas Sky** (`#38BDF8`): azul medio, puntos de mapa, conectores, dataviz futura.
- **Atlas Mist** (`#7DD3FC`): tintes muy claros sobre fondos oscuros (selector de edición de cursos: `background #f0f9ff`, `border #bae6fd` derivan de esta familia).

### Secondary
- **Oro Erasmus** (`#F59E0B`): el acento de marca. Aparece en `.section__tag` (mayúsculas, `letter-spacing 0.1em`), en el underline animado del nav (2px de alto), en el botón-CTA `.btn--gold` con halo dorado, y en `badge` de tarjeta de curso. Es el sello, no la pintura.
- **Oro Erasmus Deep** (`#D97706`): hover/end del gradiente dorado, texto dorado sobre crema cuando se requiere contraste AA.
- **Oro Erasmus Light** (`#FBBF24`): números del hero (`.hero__stat-number`), texto del badge del hero, brillo decorativo.
- **Oro Erasmus Veil** (`#FDE68A`): tonos de soporte (fondo de badge "soon" `#fef3c7` deriva de esta familia).

### Tertiary
- **EU Institutional Blue** (`#003399`): el azul institucional UE. **Una sola asignación**: botones `.btn--eu` que llevan a los cursos KA1 (financiación europea). Cita simbólica, no decoración. Si aparece fuera de ese contexto, está mal usado.

### Neutral
- **Paper Cream** (`#FAFAF8`): fondo de página por defecto. Crema con un susurro de calidez, no blanco quirúrgico.
- **Stone Cream** (`#F1F0EC`): fondo de secciones alternas (`.section--alt`). El segundo respiro, más pisado.
- **Atlas Ink** (`#1B2A3D`): el tono de cuerpo. No es negro: es azul muy oscuro que tinta hacia la familia atlas.
- **Slate Mist** (`#64748B`): texto secundario, metadatos de tarjetas de blog/curso, copy de soporte, subtítulos de sección.
- **Mist Border** (`#E2E8F0`): borde por defecto de tarjetas, inputs y separadores.
- **Pure White** (`#FFFFFF`): fondo de tarjetas, blanco verdadero solo para superficies elevadas.

### Signal
- **Signal Red** (`#DC2626`): solo errores, plazas limitadas (`spots--low`), formularios inválidos.
- **Signal Green** (`#16A34A`): solo success en formularios y feedback de newsletter.

### Named Rules
**La Regla del Sello Dorado.** Oro Erasmus subraya, sella o destaca un dato; nunca cubre un bloque amplio. Si quieres rellenar una superficie con dorado, vuelve a Atlas Midnight o a una crema. El máximo permitido: un botón CTA, una columna de stats con números dorados, un tag de sección. No tres a la vez en la misma pantalla.

**La Regla del Sur con Criterio.** Cremas (Paper / Stone) sostienen el sitio. Si dudas entre crema y blanco, elige crema; el blanco puro queda reservado para superficies elevadas (cards, inputs). El sitio nunca debe parecer un panel de admin blanco.

**La Regla del Azul UE.** EU Institutional Blue (`#003399`) es cita simbólica de Erasmus+, no parte libre de la paleta. Solo se usa en CTAs que llevan a cursos KA1 o a contenido directamente financiado por el programa.

## 3. Typography

**Display Font:** Playfair Display (fallback Georgia, serif)
**Body Font:** DM Sans (fallback system-ui, sans-serif)

**Character:** un emparejamiento editorial clásico — serifa transitional con contraste para los títulos, sans humanista neutra para el cuerpo. Playfair aporta gravedad institucional con un toque renacentista que rescata el sitio del "tono ministerial"; DM Sans aporta legibilidad moderna sin convertirlo en otra landing genérica. La diferencia entre familias debe ser obvia a primera vista.

### Hierarchy
- **Display** (Playfair 700, `clamp(2.2rem, 5vw, 3.5rem)`, line-height 1.2, color `Atlas Midnight`): h1 — un solo uso por página, en el hero o en cabecera de artículo. El hero lo sube a `clamp(2.4rem, 5.5vw, 3.8rem)` cuando ocupa pantalla completa.
- **Headline** (Playfair 700, `clamp(1.8rem, 4vw, 2.8rem)`, line-height 1.2): h2 — apertura de sección.
- **Title** (Playfair 700, `clamp(1.3rem, 3vw, 1.8rem)`, line-height 1.3): h3 — títulos de tarjeta. Los títulos de tarjeta de curso bajan a `1.2rem` para no competir con el precio.
- **Body** (DM Sans 400, `16px`, line-height `1.7`, color `Atlas Ink`): párrafos. Justificado con `hyphens: auto` activado — opción editorial deliberada que ata el aire del sitio a una revista, no a un blog. Anchura cómoda dentro de container 1200px o 800px estrecho según contexto.
- **Body Lift** (DM Sans 400, `1.05rem`): párrafos destacados dentro de secciones segmentadas (intro de segmento, descripción de artículo).
- **Label** (DM Sans 700, `0.8rem`, `letter-spacing: 0.1em`, UPPERCASE, color `Oro Erasmus`): los `.section__tag` y micro-textos institucionales. Es el componente tipográfico más reconocible del sitio.
- **Meta** (DM Sans 400/500, `0.8–0.85rem`, color `Slate Mist`): metadatos de tarjeta (fechas, plazas, autor, tiempo de lectura).

### Named Rules
**La Regla del Sello en Mayúsculas.** Los tags de sección (`.section__tag`) y micro-headers institucionales van en Label: DM Sans 700, 0.8rem, mayúsculas, `letter-spacing 0.1em`, color Oro Erasmus. No mezclar Playfair con mayúsculas espaciadas; no usar Label en cuerpo. Esta pareja (Tag dorado en mayúsculas + Headline Playfair debajo) abre cada sección y es la firma visual del sitio.

**La Regla del Justificado Editorial.** El cuerpo va justificado con guionado (`text-align: justify; hyphens: auto`). Es deliberado y debe mantenerse. Excepción: dentro de `.section__header` los párrafos se centran (el contenedor manda). Los párrafos sueltos de UI breve (formularios, modals) no se justifican.

## 4. Elevation

Filosofía **Glass + Lift dual**: dos vocabularios coexisten con dominios claros. Las superficies persistentes (nav fija, badge del hero) usan glassmorphism muy contenido — `backdrop-filter: blur(8–12px)` sobre cremas/blancos translúcidos — para "estar sin tapar". Las superficies interactivas (cards, course-cards, botones) usan lift editorial: planas en reposo sobre crema, elevadas en hover con sombras anchas y de baja opacidad. La sensación es de página tranquila con respuesta táctil al recorrerla; el hero es la excepción plana absoluta (gradiente drenched, sin sombras internas).

### Shadow Vocabulary
- **Ambient Card Rest** (`box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08)`): sombra de tarjeta `.card` en hover — ancha, suave, baja opacidad. También usada en `.segment__illustration` y `.diff-card`. Es la sombra "reina" del sistema.
- **Ambient Card Light** (`box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06)`): sombra de `.course-card` en reposo. Más sutil que la "reina" porque la tarjeta de curso vive en una grid densa y no debe vibrar.
- **Ambient Card Lift** (`box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1)`): hover de `.course-card`. Lift moderado, no exagerado.
- **Tinted Glow Azure** (`box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3)` → `0 6px 20px rgba(2, 132, 199, 0.4)` en hover): halo tintado de `.btn--primary`. El botón flota con su propia luz.
- **Tinted Glow Gold** (`box-shadow: 0 4px 14px rgba(245, 158, 11, 0.3)` → `0 6px 20px rgba(245, 158, 11, 0.4)` en hover): halo de `.btn--gold`.
- **Tinted Glow EU** (`box-shadow: 0 4px 14px rgba(0, 51, 153, 0.3)`): halo del CTA institucional UE.
- **Nav Scroll Shadow** (`box-shadow: 0 2px 20px rgba(0, 0, 0, 0.06)`): aparece solo cuando `.nav--scrolled`. Anuncia que la página se ha desplazado bajo la barra.
- **Focus Glow** (`box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1)`): foco de input. Anillo respirado de Atlas Azure al 10%.

### Named Rules
**La Regla Glass-or-Lift, Never Both.** Una superficie es glass (persistente, blur) o lift (interactiva, sombra). Nunca aplicar `backdrop-filter` y `box-shadow` decorativos a la vez al mismo elemento. La nav es glass; las cards son lift; punto.

**La Regla del Halo Tintado.** Cuando un botón tiene sombra, la sombra es del color del propio botón al 30% (rest) y 40% (hover). Nunca usar sombra neutra `rgba(0,0,0,…)` en un botón con color — apagaría su presencia. Para botones outline o ghost: cero sombra.

## 5. Components

### Buttons
- **Shape:** semi-pill controlado — esquinas suavemente curvadas (`border-radius: 8px`), no píldora completa. Padding generoso `0.85rem 2rem`, gap interno `0.5rem` para iconos.
- **Tipografía:** DM Sans 600, `0.95rem`, sin mayúsculas.
- **Primary** (`.btn--primary`): gradiente `linear-gradient(135deg, Atlas Azure → Atlas Midnight)` + halo tintado azul. El botón "soy centro escolar" del hero usa este registro.
- **Gold** (`.btn--gold`): gradiente `linear-gradient(135deg, Oro Erasmus → Oro Erasmus Deep)` + halo tintado dorado. Reservado al CTA de cursos cuando se quiere máximo destaque.
- **EU** (`.btn--eu`): fondo sólido `EU Institutional Blue` + halo tintado UE-blue. **Solo para CTAs hacia cursos KA1.** Lleva icono de gorro académico inline (SVG 18px) por delante.
- **Outline** (`.btn--outline`): borde 2px Atlas Midnight, fondo transparente, sin sombra.
- **Outline White** (`.btn--outline-white`): variante para hero — borde 2px blanco translúcido sobre fondo oscuro.
- **Tamaños:** `.btn--sm` (0.6rem 1.4rem, 0.85rem font) y `.btn--lg` (1rem 2.5rem, 1.05rem font).
- **Hover:** `transform: translateY(-2px)` + halo intensificado en 200–300ms.
- **Focus:** anillo Atlas Azure al 10% por defecto del navegador (mejorable; ver Don'ts).

### Cards
- **Generic Card** (`.card`):
  - **Corner Style:** 16px (`rounded.lg`) — generoso, editorial.
  - **Background:** `Pure White`.
  - **Border:** `1px solid Mist Border`. La tarjeta nunca está suelta; siempre tiene un borde sutil que la fija.
  - **Internal Padding:** `2.5rem` — respiración alta.
  - **Shadow Strategy:** plana en reposo; en hover, `translateY(-4px)` + sombra Ambient Card Rest.
  - **Icon slot:** `.card__icon` 48×48px, radius 12px, fondo tintado (`--blue`, `--gold`, `--green`).
- **Course Card** (`.course-card`):
  - **Corner Style:** 12px (`rounded.md`) — más compacto que la card genérica para vivir en grids densas.
  - **Background:** `Pure White`, sin borde (solo sombra).
  - **Structure:** imagen 200px (gradiente Atlas Midnight → Atlas Azure como placeholder), badge dorado absoluto top-right, body con meta, título, excerpt, footer separado por borde superior.
  - **Shadow Strategy:** Ambient Card Light en reposo, Ambient Card Lift en hover, transición 250ms.
  - **Price:** Atlas Midnight, 1.3rem, peso 700 — el dato que cierra la decisión.
  - **Edition badges:** pill `border-radius: 6px`, fondo `#f0f9ff` (familia Atlas Mist) con texto Atlas Azure y borde claro; variante `--soon` cambia a familia Oro Erasmus Veil.

### Inputs / Fields
- **Style:** fondo `Paper Cream` (no blanco), borde `1.5px solid Mist Border`, radius 8px, padding `0.75rem 1rem`. Font DM Sans `0.95rem` color Atlas Ink.
- **Focus:** borde se vuelve Atlas Azure + `Focus Glow` (anillo Atlas Azure al 10%, 3px). Sin movimiento, sin elevación. La sensación es de "campo iluminado".
- **Textarea:** `min-height: 120px`, `resize: vertical`.
- **Checkbox:** 18×18px, `accent-color: Atlas Azure`. Label asociado en `Slate Mist` `0.82rem`.
- **Error:** mensaje `0.8rem` `Signal Red`, sin cambio de fondo (anuncia, no agrede). Banner global de feedback (`.form-feedback`) sí usa fondo tintado verde o rojo claro.

### Navigation
- **Style:** fija arriba, altura 72px, fondo cristal `rgba(250, 250, 248, 0.95)` + `backdrop-filter: blur(12px)`, borde inferior `1px Mist Border`.
- **Logo:** SVG inline con plano de aristas doradas + pin de localización en gradiente Atlas; ocupa 160×44px en desktop.
- **Links:** DM Sans 500, `0.85rem`, Atlas Ink. Underline animado dorado en hover/active: pseudo `::after` que crece de 0 a 100% (2px de alto, color Oro Erasmus, transición 300ms).
- **Estado scrolled:** añade `Nav Scroll Shadow`. La barra anuncia que la página se mueve bajo ella.
- **Switch idioma:** dos botones `.lang-switch__btn` en cápsula de 16px radius, fondo cristal blanco translúcido. El activo se rellena de Atlas Midnight con texto blanco.

### Section Tag + Header
- **Section Tag** (`.section__tag`): DM Sans 700 `0.8rem`, mayúsculas, `letter-spacing 0.1em`, color Oro Erasmus, margen inferior `space.sm`. Aparece encima de cada `h2` de apertura. Es el "qué somos en esta sección".
- **Section Header**: tag → headline Playfair → subtítulo Slate Mist `1.05rem` centrado con `max-width: 720px`. La fórmula es invariable; cambiar el contenido, no la composición.

### Hero
- **Background:** gradiente diagonal Atlas Midnight → `#0e3c5e` → `#0a2940`, con canvas de partículas (network) y mapa SVG de Europa a opacidad 0.08 como capas de fondo.
- **Badge:** glass pill `rgba(255,255,255,0.1)` + borde blanco translúcido + `backdrop-filter: blur(8px)`, texto Oro Erasmus Light con icono estrella. Marca el ancla del scope.
- **Stats:** tres columnas separadas por gap `space.xl`, número Playfair 2.2rem Oro Erasmus Light, label `0.85rem` blanco al 60%.
- **Canvas:** ocupa el 55% derecho, no interactivo, decora sin dominar.

### Course Page Editions Selector
- **Style:** lista de `.edition-option` con borde 2px Mist Border, radius 8px, padding `0.75rem 1rem`. Hover y selected → borde Atlas Azure + fondo `#f0f9ff`.
- **Radio:** `accent-color: Atlas Azure`, 18×18px.

## 6. Do's and Don'ts

### Do:
- **Do** abrir cada sección con la fórmula `Section Tag (Oro Erasmus mayúsculas, letter-spacing 0.1em) → Headline (Playfair 700) → Subtítulo (Slate Mist 1.05rem)`. Es la firma de EuryGo.
- **Do** mantener cremas (`Paper Cream #FAFAF8`, `Stone Cream #F1F0EC`) como fondo del sitio. El blanco puro vive solo dentro de cards e inputs.
- **Do** usar `EU Institutional Blue (#003399)` exclusivamente para CTAs a cursos KA1 o contenido financiado por Erasmus+. En cualquier otro sitio está mal aplicado.
- **Do** dar a la sección "Cursos de Formación KA1" tratamiento de protagonista visual y conversión: badge dorado en tarjeta, precio claro, CTA `.btn--eu` o `.btn--gold` con halo. El modelo de negocio manda.
- **Do** apoyar la confianza en datos concretos (`6+ años`, `€26.2B`, `100% asesoría integral`) tipografiados en Playfair grande con Oro Erasmus Light. Datos antes que adjetivos.
- **Do** respetar la jerarquía dual de personas (centro escolar ES + docente europeo) con secciones segmentadas que tengan CTA propio y narrativa diferenciada.
- **Do** atender `prefers-reduced-motion` desactivando el canvas de partículas, los reveals, las transiciones de elevación y el underline animado del nav.
- **Do** verificar contraste antes de poner Oro Erasmus (`#F59E0B`) como texto sobre Paper Cream: el dorado pasa AA a partir de `Oro Erasmus Deep (#D97706)`. Para texto pequeño, deeper.

### Don't:
- **Don't** caer en la **página institucional gris-burocrática** (PRODUCT.md). Sin tablas planas con banderas pegadas, sin azul corporativo opaco, sin PDF disfrazado de web. Si parece SEPIE, está mal.
- **Don't** caer en el **SaaS startup genérico** (PRODUCT.md). Sin gradientes púrpura, sin screenshot-tilt del hero, sin badges "Trusted by 1000+ teams", sin iconos lottie de manitas.
- **Don't** caer en la **agencia de viajes turística**. Sin carruseles de playas, sin badges "#1 en Europa", sin sonrisas stock con mochilas. Andalucía aparece como propuesta de valor (bodegas, Real Escuela del Arte Ecuestre), no como folklore decorativo.
- **Don't** usar texto con `background-clip: text` y gradiente para títulos. El sistema actual lo hace en `.hero h1 .text-gold` (líneas 515–520 de `main.css`); es la excepción heredada que debe retirarse en próxima pasada de polish y sustituirse por color sólido `Oro Erasmus Light` con peso 700.
- **Don't** rellenar superficies amplias con Oro Erasmus. El dorado subraya, sella, destaca un dato. No cubre. Máximo un CTA dorado + un sello + una columna de stats por pantalla.
- **Don't** mezclar `backdrop-filter` con `box-shadow` decorativo en el mismo elemento. Glass o lift, nunca los dos a la vez.
- **Don't** usar `border-left` o `border-right` >1px como acento de color en tarjetas, callouts o list items. El sistema no usa side-stripes y no debe empezar.
- **Don't** repetir grids de tarjetas idénticas (icono + título + texto) más de una vez por página. Si hace falta, rompe la tarjeta: cambia la jerarquía interna, la asimetría, el peso.
- **Don't** introducir nuevas familias tipográficas. Playfair Display + DM Sans cubren todo. Si una sección "necesita" una tercera fuente, la sección está mal pensada, no el sistema.
- **Don't** usar `#000` ni `#fff` literales en CSS nuevo. Tinta los neutros: negro → `Atlas Ink #1B2A3D`; blanco → `Pure White #FFFFFF` o crema según contexto.
- **Don't** sustituir las cremas por blanco "para que parezca más limpio". El sitio nunca debe verse como un panel de admin.
- **Don't** mover `.btn--eu` (azul UE) a secciones que no sean curso/financiación europea. Pierde su carga simbólica si se generaliza.
