# Cómo instalar el blog dinámico de EuryGo en OVH

## Paso 1 — Crear la base de datos en OVH

1. Entra en tu panel de OVH: https://www.ovh.com/manager/
2. Ve a "Web Cloud" → tu hosting → "Bases de datos"
3. Crea una nueva base de datos MySQL (si no tienes una ya creada)
4. Anota estos 4 datos (los necesitarás en el paso 2):
   - **Nombre de la base de datos** (ejemplo: `eurygo_db`)
   - **Usuario de la base de datos** (ejemplo: `eurygo_user`)
   - **Contraseña de la base de datos**
   - **Servidor (host)** (normalmente algo como `eurygo.mysql.db` o `localhost`)

## Paso 2 — Configurar la conexión

1. Abre el archivo `config.php` con un editor de texto (por ejemplo Notepad++ o el bloc de notas)
2. Rellena los 4 datos que anotaste en el Paso 1:
   - `DB_HOST` → el servidor (host) de la base de datos
   - `DB_NAME` → el nombre de la base de datos
   - `DB_USER` → el usuario de la base de datos
   - `DB_PASS` → la contraseña de la base de datos
3. Guarda el archivo

## Paso 3 — Subir los archivos a OVH

1. Descarga e instala FileZilla (programa gratuito de FTP): https://filezilla-project.org/
2. Abre FileZilla y conéctate con los datos FTP de tu hosting OVH:
   - Servidor: lo encontrarás en tu panel de OVH → Hosting → "Información general" → "Acceso FTP"
   - Usuario y contraseña: los mismos del panel OVH
3. Sube **TODOS** los archivos del proyecto a la carpeta `www/` (o `public_html/`, según tu hosting)
4. Asegúrate de que la carpeta `assets/images/blog/` tiene permisos de escritura (chmod 755)

## Paso 4 — Instalar la base de datos

1. Abre tu navegador y ve a: `https://www.eurygo.com/admin/setup/install.php`
   (sustituye `www.eurygo.com` por tu dominio real si es diferente)
2. Si ves **"Instalación completada con éxito"** → todo correcto
3. Si hay errores → revisa los datos del `config.php` (Paso 2) y asegúrate de que la base de datos está creada

## Paso 5 — Borrar el instalador (IMPORTANTE por seguridad)

1. Con FileZilla, borra la carpeta `/admin/setup/` del servidor
2. Este paso es obligatorio: el instalador ya no se necesita y dejarlo en el servidor es un riesgo de seguridad

## Paso 6 — Acceder al panel de administración

1. Ve a: `https://www.eurygo.com/admin/`
2. Introduce las credenciales iniciales:
   - **Usuario:** `admin`
   - **Contraseña:** `EuryGo2025!`
3. **⚠️ CAMBIA LA CONTRASEÑA INMEDIATAMENTE** desde el menú "Cambiar contraseña" de la barra lateral

## Paso 7 — Activar TinyMCE (editor de texto enriquecido)

1. Regístrate gratis en: https://www.tiny.cloud
2. Tras el registro, obtendrás una **API key gratuita**
3. Abre el archivo `/admin/editor.php` con un editor de texto
4. Busca la línea que dice `no-api-key` y sustitúyela por tu API key real
5. Guarda el archivo y vuelve a subirlo al servidor

---

## Uso del panel de administración

### Crear un artículo nuevo
1. Haz clic en "+ Nuevo artículo" en la barra lateral
2. Rellena el título, extracto y contenido
3. Sube una imagen de portada si quieres
4. Elige el idioma y la categoría
5. Pulsa "Guardar borrador" para guardarlo sin publicar, o "Publicar" para que sea visible en la web

### Editar un artículo existente
1. Ve a "Artículos" en la barra lateral
2. Haz clic en "Editar" junto al artículo que quieras modificar
3. Haz los cambios y pulsa "Guardar borrador" o "Publicar"

### Borrar un artículo
1. Ve a "Artículos" en la barra lateral
2. Haz clic en "Borrar" junto al artículo (te pedirá confirmación)
3. **Esta acción no se puede deshacer**

### Duplicar un artículo (útil para crear versiones en otro idioma)
1. Ve a "Artículos" en la barra lateral
2. Haz clic en "Duplicar" junto al artículo
3. Se creará una copia como borrador que puedes editar

---

## Paso 8 — Instalar las tablas de newsletter y estadísticas

1. Abre tu navegador y ve a: `https://www.eurygo.com/admin/setup/update_v2.php`
2. Si ves **"Actualización v2 completada"** → todo correcto
3. Borra la carpeta `/admin/setup/` del servidor

## Paso 9 — Configurar Brevo (newsletter)

1. Regístrate gratis en: https://www.brevo.com
2. Ve a **Configuración → Claves API** y genera una clave API v3
3. En Brevo, ve a **Contactos → Listas** y crea dos listas:
   - `EuryGo Web ES` → apunta el ID numérico
   - `EuryGo Web EN` → apunta el ID numérico
4. Abre el archivo `config.php` y rellena:
   - `BREVO_API_KEY` → tu clave API v3
   - `BREVO_LIST_ID_ES` → el ID de la lista ES
   - `BREVO_LIST_ID_EN` → el ID de la lista EN
5. En Brevo, ve a **Configuración → Dominios** y verifica el dominio `eurygo.com`:
   - Añade los registros SPF y DKIM en el panel DNS de OVH
   - OVH: Web Cloud → Zona DNS → Añadir registros TXT según las instrucciones de Brevo
6. Guarda `config.php` y vuelve a subirlo al servidor

## Paso 10 — Configurar envíos de newsletter programados (opcional)

Si quieres programar newsletters para envío futuro:

1. Entra en tu panel OVH → **Hosting → Tareas cron**
2. Crea una nueva tarea con esta configuración:
   - **Frecuencia:** cada hora
   - **Comando:** `php /home/[tuusuario]/public_html/admin/cron/enviar-programados.php`
3. Guarda la tarea

Si no configuras esto, aún puedes enviar newsletters manualmente con el botón "Enviar ahora" del panel.

---

## Uso del módulo de newsletter

### Gestionar suscriptores
1. Ve a **Newsletter** en la barra lateral del panel de administración
2. Verás la lista de suscriptores con filtros por idioma, estado y confirmación
3. Puedes dar de baja o eliminar suscriptores individualmente
4. Usa **Exportar CSV** para descargar la lista de suscriptores activos

### Enviar un newsletter
1. Haz clic en **+ Nueva campaña** desde la sección Newsletter
2. Rellena el asunto, preheader (opcional), elige el idioma y escribe el contenido
3. Opciones de envío:
   - **Enviar prueba**: envía solo a tu email para revisar antes
   - **Enviar ahora**: envía a todos los suscriptores del idioma seleccionado
   - **Programar envío**: elige fecha y hora (requiere cron job configurado)

### Ver estadísticas
1. Ve a **Estadísticas** en la barra lateral
2. Encontrarás datos de visitas al blog, suscriptores y formularios de contacto
3. Usa los selectores de período e idioma para filtrar los datos

---

## Paso 11 — Instalar las tablas de cursos de formación

1. Abre tu navegador y ve a: `https://www.eurygo.com/admin/setup/update_v3.php`
2. Si ves **"Actualización v3 completada con éxito"** → todo correcto
3. Borra la carpeta `/admin/setup/` del servidor

## Uso del módulo de cursos

### Gestionar cursos
1. Ve a **Cursos** en la barra lateral del panel de administración
2. Crea un nuevo curso con **+ Nuevo curso** o edita uno existente
3. El editor tiene 5 pestañas: Datos generales, Descripción, Programa (día a día), Imagen y SEO
4. Publica el curso cuando esté listo para que sea visible en la web

### Gestionar inscripciones
1. Ve a **Inscripciones** en la barra lateral
2. Filtra por curso, estado o busca por nombre/email
3. Confirma o cancela inscripciones individualmente
4. Exporta la lista a CSV con el botón "Exportar CSV"

---

## Soporte técnico

Si tienes problemas con la instalación o el funcionamiento del panel, contacta con tu administrador técnico.
