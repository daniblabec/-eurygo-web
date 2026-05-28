# Product

## Register

brand

## Users

Dos personas conviven en la home y deben sentirse llamadas desde el primer scroll, sin diluirse mutuamente:

1. **Centro escolar español** — director/a o coordinador/a Erasmus+ de un IES o colegio público/concertado. Llega buscando ayuda concreta para acreditación KA1/KA2, gestión SEPIE, Beneficiary Module, Ulises o un partner que cuadre logística de movilidades. Contexto: navega entre tareas docentes, lee con prisa, decide con cabeza. Llega cauteloso ("¿estos quiénes son, son de fiar?") y necesita pasar a confiado en menos de un minuto.

2. **Docente europeo (cursos KA1)** — profesor/a de Alemania, Italia, Polonia, etc., buscando un curso estructurado con fondos Erasmus+ en un destino premium. Aterriza desde búsqueda en inglés, compara cinco webs, decide por mezcla de credibilidad + experiencia destino. Contexto: planifica con meses de antelación, quiere ver programa día-a-día, fechas y precios sin tener que escribir un email.

Una persona secundaria existe (**agencia de movilidad europea** B2B) pero no manda la jerarquía visual.

## Product Purpose

EuryGo es la pieza intermedia que faltaba en el ecosistema Erasmus+ escolar: une centros educativos con agencias y fondos europeos, y vende cursos KA1 para docentes europeos en Jerez de la Frontera. El sitio público no es un folleto: es la palanca de adquisición. Éxito se mide en: (a) solicitudes de asesoría desde centros ES, (b) inscripciones a cursos KA1 desde docentes europeos. La sección **Cursos de Formación** es el motor de conversión más directo y debe tratarse como el escaparate principal del modelo de negocio, no como nota al pie del menú.

## Brand Personality

**Tres palabras: institucional, solvente, ambiciosa.**

Mezcla deliberada de dos vectores que normalmente se ignoran:
- **Institucional/solvente** — cerca de SEPIE, fundaciones, organismos europeos. Cuerpo tipográfico con peso, aire respirado, datos concretos por delante de adjetivos.
- **Joven/ambiciosa** — sin ser startup genérica: energía europea, sensación de movimiento, jerarquía editorial moderna que no parece sacada de un PDF ministerial.

**Tono de voz**: experto pero claro, directo sin ser frío. *"Sin burocracia, con garantías"* es el latido. Datos antes que adjetivos (6+ años, €26.2B, plataformas por nombre). Nada de "transforma tu carrera" ni "la solución definitiva". Andalucía aparece como propuesta de valor real (jobshadowing premium con bodegas, Real Escuela del Arte Ecuestre, flamenco) — nunca como decoración folklórica.

**Emoción objetivo en los primeros 5 segundos**: confianza institucional. El visitante debe pensar *"esta gente sabe lo que hace, son serios"* antes de leer una sola frase de copy.

## Anti-references

Lo que EuryGo **no es**, y debe evitarse activamente:

- **Página institucional gris-burocrática** (tipo SEPIE, portales de ministerio). PDF disfrazado de web, tablas planas, banderas pegadas como sellos, azul corporativo opaco, cero jerarquía. La solvencia no se demuestra pareciendo aburrido.
- **SaaS startup genérico**. Hero con screenshot tilt + gradiente púrpura, badges "Trusted by 1000+ teams", iconos planos repetidos, lottie con manitas. Nada de eso aplica a una intermediaria Erasmus+ seria.
- **Agencia de viajes turística**. Carruseles de playas, jóvenes stock con mochilas, badges "#1 en Europa", gradientes saturados. Los cursos son formación profesional, no escapada de fin de semana.
- **Academia online genérica**. Tarjetas idénticas con icono+título+párrafo repetidas en grid 3xN.

## Design Principles

1. **Confianza por evidencia, no por banderas.** La solvencia se demuestra con números concretos (6+ años de experiencia real, €26.2B presupuesto Erasmus+, plataformas mencionadas por nombre: SEPIE, Ulises, Beneficiary Module) y con la voz de quien lo ha vivido desde dentro. Nada de sellos vacíos, badges decorativos, ni "trusted by".

2. **Cursos al frente, no al pie.** La sección "Cursos de Formación KA1" es la palanca económica más directa. Debe tener peso visual de protagonista en home, descubrimiento prioritario en navegación, y tratamiento editorial (programa día-a-día, fechas, precio, foto de Jerez) que no la confunda con "una sección más".

3. **Dos personas, una voz.** Centros escolares españoles y docentes europeos comparten la misma home y la misma identidad. La jerarquía debe servir a ambos sin diluir ninguno: secciones segmentadas con CTA propio, no una papilla que no le habla a nadie.

4. **Editorial, no ministerial.** Aire, tipografía con jerarquía clara (Playfair para títulos, DM Sans para cuerpo ya están en el sistema), ritmo de spacing variado. Pensar más en revista buena que en boletín oficial. El registro institucional se gana con composición, no con seriedad triste.

5. **Andalucía como propuesta de valor, no como decoración.** Jerez, bodegas, flamenco, caballos aparecen porque hacen único el jobshadowing y los cursos KA1 — no como elementos folklóricos sueltos. Si aparece una imagen del territorio, justifica una decisión de negocio del visitante.

## Accessibility & Inclusion

**Objetivo base: WCAG 2.1 AA.**

- Contrastes ≥ 4.5:1 en texto cuerpo y ≥ 3:1 en texto grande. Ojo especial con el dorado sobre crema (#F59E0B sobre #FAFAF8 — verificar antes de usar como texto).
- Foco visible y consistente, navegable por teclado en formularios de contacto, inscripción a cursos y suscripción a newsletter.
- `alt` significativo en imágenes; las ilustraciones SVG decorativas con `aria-hidden`.
- Atender `prefers-reduced-motion` para el canvas animado del hero y cualquier reveal-on-scroll.
- Estructura semántica correcta (`h1` único, jerarquía de headings real, landmarks).
- Bilingüismo ES/EN: `lang` correcto en `<html>` por idioma y `hreflang` ya presente — mantener al añadir páginas.
