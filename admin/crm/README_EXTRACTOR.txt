EXTRACTOR PDF SEPIE — INSTRUCCIONES
====================================

El archivo extract-sepie.js es un script Node.js que extrae los centros
admitidos del PDF oficial del SEPIE y genera un JSON listo para importar.

REQUISITOS:
  - Node.js instalado
  - Paquete pdfjs-dist (npm install pdfjs-dist@3.11.174)

EJECUCIÓN (en local, no en producción):
  cd ruta/al/proyecto
  node admin/crm/extract-sepie.js "ruta/al/Centros provisión de fondos.pdf"

SALIDA:
  Genera admin/crm/centros_sepie.json con todos los centros KA121-SCH y KA122-SCH.

IMPORTACIÓN A LA BD:
  Una vez generado el JSON, sube el archivo centros_sepie.json al servidor
  y abre /admin/crm/importar.php en el navegador. Pulsa "Importar".

ÚLTIMA EXTRACCIÓN:
  - 3.036 centros extraídos del PDF de la convocatoria 2026
  - 1.538 KA121-SCH (acreditación) + 1.498 KA122-SCH (corta duración)
  - 609 en Andalucía (prioridad alta), 333 en zona media, 2.094 baja
  - 186 presenciales (<150 km de Jerez), 2.850 telemáticas
