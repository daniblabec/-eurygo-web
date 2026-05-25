# Versionado del proyecto eurygo.com

Este proyecto usa **git** como sistema de control de versiones desde 2026-05-24.
El "punto cero" (commit `ef2d6fd`) recoge el estado de la web en ese momento.

## Para qué sirve

- Volver a una versión anterior de cualquier fichero en cualquier momento.
- Ver exactamente qué cambió, cuándo y por qué.
- Tener una copia de seguridad fiable en GitHub (privado) por si se rompe el portátil.

---

## Workflow recomendado por turno

Antes de pedirme cambios:

```powershell
cd "C:\Users\PC AOE\Desktop\Claude Code\Empresa\eurygo-web"
git status        # ver si hay cambios sin commitear
```

Después de aplicar y validar lo que hayamos hecho (en local, antes de subir a OVH):

```powershell
git add .
git commit -m "Descripción breve del cambio"
git push          # si está conectado a GitHub
```

Si algo sale mal en producción y quieres volver atrás:

```powershell
git log --oneline                # lista de commits
git checkout <hash> -- ruta/al/fichero    # restaurar UN fichero
git reset --hard <hash>          # volver TODO el proyecto a ese punto
```

⚠ `git reset --hard` borra los cambios locales no commiteados. Pídeme ayuda antes de
usarlo si tienes dudas — es seguro pero irreversible.

---

## Conectar con GitHub (una sola vez)

`gh` CLI no está instalado en el equipo, así que el repo en GitHub se crea desde
el navegador:

1. Entra en https://github.com/new
2. Crea un repo **privado** llamado `eurygo-web` (o el nombre que quieras).
3. **NO** marques "Initialize with README" (ya tenemos uno local).
4. Copia la URL del repo (algo como `https://github.com/TuUsuario/eurygo-web.git`).
5. En PowerShell:

```powershell
cd "C:\Users\PC AOE\Desktop\Claude Code\Empresa\eurygo-web"
git remote add origin https://github.com/TuUsuario/eurygo-web.git
git push -u origin main
```

La primera vez te pedirá credenciales de GitHub (login + token, no contraseña).
Si te lía, dime y te guío con un Personal Access Token paso a paso.

---

## Qué se versiona y qué NO

**Versionado** (en `git ls-files`):
- Todo el código PHP, JS, CSS, HTML estático, htaccess
- `config.example.php` (plantilla sin credenciales)
- `sitemap.xml`, `robots.txt`, `INSTALACION.md`, este `VERSIONADO.md`

**NO versionado** (en `.gitignore`):
- `config.php` — contiene credenciales BD/SMTP/Brevo/Turnstile. SÓLO vive
  en tu copia local y en el servidor OVH.
- `uploads/` y todas las imágenes JPG/PNG/WebP de `assets/images/blog/`.
  OVH ya las respalda.
- Logs, cachés, `.DS_Store`, `node_modules`, etc.

Si en algún momento ves que **falta algo importante** después de un git clone
(p.ej. imágenes), avísame y revisamos el `.gitignore`.

---

## Y la base de datos

Git **no** captura la base de datos MySQL. Para los scripts de migración
(`admin/setup/update_v*.php`) que SÍ tocan la BD, hay un script propio que
descarga un dump comprimido directamente desde el navegador:

### Backup automático antes de cada migración

**1.** Logueado como admin, abre en el navegador:

```
https://www.eurygo.com/admin/setup/backup-db.php
```

Esto inicia la descarga inmediata de un fichero
`eurygo_backup_AAAA-MM-DD_HHMMSS.sql.gz` con TODA la base de datos.

**2.** Mueve el fichero descargado a:

```
C:\Users\PC AOE\Desktop\Claude Code\Empresa\eurygo-web\backups\
```

(La carpeta `backups/` está en `.gitignore`, no se sube al repo.)

**3.** Ahora puedes ejecutar el `update_v*.php` con red de seguridad.

### Backup solo de las tablas afectadas (más rápido)

Si la migración solo toca ciertas tablas (p.ej. update_v8 toca `cursos`,
`cursos_programa`, `cursos_ediciones`):

```
https://www.eurygo.com/admin/setup/backup-db.php?tablas=cursos,cursos_programa,cursos_ediciones
```

Descarga un fichero más pequeño con solo esas tablas. Útil para restauraciones
quirúrgicas.

### Dump en texto plano (sin comprimir)

Si quieres inspeccionar el SQL a ojo antes de guardarlo:

```
https://www.eurygo.com/admin/setup/backup-db.php?formato=sql
```

Descarga `.sql` plano en vez de `.sql.gz`.

### Restaurar desde un dump

Si una migración rompió algo:

**1.** Entra a phpMyAdmin de OVH (panel OVH → Bases de datos → phpMyAdmin).

**2.** Selecciona la base de datos `eurygoceurygodb` → pestaña **Importar**.

**3.** Sube el `.sql.gz` (phpMyAdmin lo descomprime al vuelo) o el `.sql`
si lo tienes en texto plano.

**4.** Pulsa **Continuar**. En menos de un minuto la BD vuelve al estado del dump.

⚠ La restauración **borra los registros añadidos después del dump**
(inscripciones nuevas a cursos, comentarios, etc.). Para una restauración
selectiva — solo las tablas tocadas por la migración — abre el `.sql` en un
editor, copia las líneas de las tablas que quieres restaurar y pégalas en
phpMyAdmin → SQL → Ejecutar.

### Buena práctica

Antes de subir y ejecutar cualquier `update_v*.php`:

1. `git status` y `git commit` del estado actual del código local.
2. Backup BD con el script de arriba → mover a `backups/`.
3. Subir el `update_vX.php` al servidor por FTP.
4. Abrirlo en el navegador, modo preview (sin parámetros).
5. Confirmar lo que va a hacer.
6. Lanzar con `?mode=apply&confirm=YES`.
7. Verificar en la web pública que no se rompió nada.
8. Borrar el `update_vX.php` del servidor (no del repo).
9. `git commit` final si el código local también cambió.

Si en el paso 7 algo se rompió: restaurar el dump del paso 2 + `git revert`
del código si aplica.
