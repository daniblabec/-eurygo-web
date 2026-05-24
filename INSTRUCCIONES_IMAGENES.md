# Gestionar las imágenes de la página de inicio

## Acceso

1. Entra en el panel de administración: `https://www.eurygo.com/admin/`
2. En el menú lateral, haz clic en **Imágenes inicio**.

## Posiciones disponibles

| Posición | Sección | Tamaño recomendado |
|----------|---------|---------------------|
| **Hero** | Cabecera principal (fondo azul) | 1920 x 800 px mínimo, horizontal |
| **About** | ¿Qué es EuryGo? | 1200 x 800 px |
| **Schools** | Centros Escolares | 1200 x 800 px |
| **Agencies** | Agencias | 1200 x 800 px |

## Cómo cambiar una imagen

1. Haz clic en **Cambiar imagen** (o **Añadir imagen** si aún no hay foto).
2. Arrastra una imagen al recuadro o haz clic para seleccionarla desde tu ordenador.
3. Rellena el campo **Texto alternativo (alt)** — es obligatorio para SEO. Describe brevemente lo que muestra la foto (ej: "Docentes europeos en el centro histórico de Jerez").
4. Opcionalmente, añade un **Título** para uso interno.
5. Pulsa **Guardar**.

La imagen se sube, se redimensiona automáticamente al tamaño máximo de la posición y se convierte a formato **WebP** (más ligero y rápido). La imagen anterior se borra del servidor.

## Formatos aceptados

- **JPG**, **PNG** o **WEBP**
- Tamaño máximo: **5 MB** por archivo

## Notas importantes

- Si no subes ninguna imagen, la web muestra una ilustración SVG por defecto.
- La imagen del **Hero** se usa como fondo con una capa de color azul semitransparente encima, por lo que funcionan mejor las fotos luminosas y con buen contraste.
- Las imágenes de **About**, **Schools** y **Agencies** reemplazan completamente la ilustración SVG.
- Los cambios son inmediatos: en cuanto pulses Guardar, la nueva imagen aparecerá en la web.
- Si solo quieres cambiar el texto alternativo sin subir una nueva foto, deja el campo de imagen vacío y pulsa Guardar.

## Resolución de problemas

| Problema | Solución |
|----------|----------|
| "Token CSRF inválido" | Recarga la página del admin y vuelve a intentarlo |
| "El archivo supera los 5MB" | Reduce el tamaño de la imagen antes de subirla |
| "Tipo de archivo no permitido" | Usa solo JPG, PNG o WEBP |
| La imagen se ve pixelada | Sube una imagen con mayor resolución (mínimo el tamaño recomendado) |
