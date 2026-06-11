# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Entorno

- **Servidor local**: Laragon (Apache + MariaDB 10.4 + PHP 8.0)
- **URL local**: `http://localhost/gest_alumnos/`
- **Base de datos**: `gest_alumnos` (MariaDB, charset `utf8mb4`)
- **Dependencia única**: `mpdf/mpdf ^8.2` (vía Composer, ya instalado en `vendor/`)

## Arrancar el proyecto

No requiere build. Basta con que Laragon esté activo. El punto de entrada es `index.php` (que incluye `dashboard.php`).

Para restaurar la base de datos, importar desde `database/gest_alumnos (estructura).sql` y luego `database/gest_alumnos (valores iniciales).sql` en phpMyAdmin.

## Arquitectura

### Conexión a base de datos

`db.php` expone una única función `db(): PDO` que devuelve un singleton estático. Todas las páginas hacen `require_once __DIR__ . '/db.php'` y llaman `$pdo = db()`. La configuración de conexión está en `config.php` (no versionado).

### Páginas principales

Cada página PHP es autónoma: contiene su lógica (POST/GET), validaciones y el HTML de salida. No hay MVC ni enrutador. Las páginas relevantes son:

| Página | Propósito |
|---|---|
| `dashboard.php` / `index.php` | Panel principal con estadísticas |
| `alumno_editar.php` | Crear/editar alumno |
| `practica_nueva.php` / `practica_editar.php` | Crear/editar práctica FCT |
| `practica_eliminar.php` | Eliminar práctica |
| `practicas_listado.php` | Listado de prácticas |
| `practicas_anexos.php` | Gestión de anexos de prácticas |
| `practicas_dias.php` | Días de prácticas por alumno |
| `practicas_ras.php` | Resultados de aprendizaje |
| `practicas_ficha_seguimiento.php` | Ficha de seguimiento |
| `practicas_contacto.php` | Contacto de prácticas |
| `practicas_documentacion.php` | Documentación de prácticas |
| `empresa_nueva.php` / `empresa_editar.php` | Gestión de empresas |
| `profesor_editar.php` | Edición de profesores |
| `horarios_editar.php` | Edición de horarios de grupo |
| `datos_centro.php` | Configuración del centro |
| `alumnos_exportar.php` | Exportación de alumnos |
| `generar_plan_formacion.php` | Generación de plan de formación PDF |

### Includes reutilizables

- `includes/validaciones.php` — validaciones de teléfono, correo, DNI/NIE españoles
- `includes/practicas_pdfs.php` — helpers de redirección y nombre de fichero para PDFs de prácticas
- `includes/practicas_pdf_helpers.php` — helpers de formato para contenido de PDFs
- `includes/practicas_dias_calculo.php` — lógica de cálculo de días/horas de prácticas (incluido dentro de páginas que lo necesitan)
- `includes/practicas_exportar.php` — lógica de exportación de prácticas
- `includes/generar_anexo_3a.php` / `includes/generar_anexo_4a.php` — generación de PDFs de anexos vía mPDF

### Generación de PDFs

Se usa mPDF. Los ficheros temporales de fuentes se generan en `docs/.mpdf_tmp/` e `includes/docs/.mpdf_tmp/`. El directorio de salida de PDFs es `docs/`.

### CSS y estilos

Toda la hoja de estilos compartida está en `assets/styles.css`. Las variables CSS principales están en `:root` (colores `--primary`, `--bg`, `--card`, `--text`, `--border`, etc.). El layout usa CSS Grid (`190px sidebar + 1fr contenido`). Los botones principales usan la clase `primary-button`.

### Helpers de PHP comunes

- `h($value): string` — `htmlspecialchars` para salida segura en HTML (definido localmente en cada página que lo necesita)
- `normalize_text($value): ?string` — trim y null si vacío (definido localmente en páginas que lo usan)
- `valid_date()`, `valid_time()`, `time_to_minutes()`, `analyze_schedule()` — helpers de fecha/hora definidos en `practica_editar.php` y `practica_nueva.php`

### Base de datos — tablas principales

| Tabla | Propósito |
|---|---|
| `alumnos` | Datos personales del alumno |
| `alumno_curso` | Relación alumno ↔ curso escolar, ciclo, grupo |
| `practicas` | Prácticas FCT (empresa, fechas, horas, estado) |
| `practicas_anexos` | Anexos asociados a prácticas |
| `empresas` | Empresas colaboradoras |
| `empresas_tutores` / `empresas_contactos` | Tutores y contactos de empresa |
| `profesores` | Profesores del centro |
| `grupos` / `grupos_tutores` | Grupos y sus tutores |
| `modulos` / `modulos_profesores` | Módulos formativos y sus docentes |
| `calificaciones` | Notas por alumno, módulo y evaluación |
| `asistencia_mensual` | Faltas mensuales por alumno |
| `horarios_grupos` / `horarios_tramos` | Horario semanal por grupo |
| `cursos_escolares` | Años académicos (uno puede estar `activo=1`) |
| `config` | Pares clave-valor de configuración del centro |
| `no_lectivos` | Fechas no lectivas |
