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
(`admin/setup/update_v*.php`) que SÍ tocan la BD, antes de ejecutar en producción:

1. Entra a phpMyAdmin de OVH.
2. Exporta a SQL las tablas afectadas (en update_v8 fueron `cursos`,
   `cursos_programa`, `cursos_ediciones`).
3. Guarda el dump en una carpeta local fuera del repo (también está en
   `.gitignore` la carpeta `backups/`).
4. Ejecuta el script de migración.
5. Si algo sale mal, importas el dump y vuelves al estado anterior.

Esto lo podemos automatizar con un script bash que use `mysqldump` y descargue
por SSH/FTP. Pídelo cuando quieras.
