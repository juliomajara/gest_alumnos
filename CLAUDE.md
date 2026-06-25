# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Entorno

- **Servidor local**: Laragon (Apache + MariaDB 10.4 + PHP 8.0)
- **URL local**: `http://localhost/gest_alumnos/`
- **Base de datos**: `gest_alumnos` (MariaDB, charset `utf8mb4`)
- **Dependencias**: `mpdf/mpdf ^8.2` (vía Composer, ya instalado en `vendor/`); `ZipArchive` (extensión PHP nativa, requerida por `generar_fichas_zip.php`)

## Arrancar el proyecto

No requiere build. Basta con que Laragon esté activo. El punto de entrada es `index.php` (que incluye `dashboard.php`).

Para restaurar la base de datos, importar desde `database/gest_alumnos (estructura).sql` y luego `database/gest_alumnos (valores iniciales).sql` en phpMyAdmin.

## Arquitectura

### Conexión a base de datos

`db.php` expone una única función `db(): PDO` que devuelve un singleton estático. Todas las páginas hacen `require_once __DIR__ . '/db.php'` y llaman `$pdo = db()`. La configuración de conexión está en `config.php` (no versionado).

### Estructura de páginas

Cada página PHP es autónoma: contiene su lógica (POST/GET), validaciones y el HTML de salida. No hay MVC ni enrutador. El sidebar se incluye mediante `require_once 'includes/sidebar.php'` y detecta automáticamente la página activa mediante `$_SERVER['PHP_SELF']`; para secciones que agrupan varias páginas, cada página puede declarar `$active_page` antes de incluir el sidebar.

### Páginas principales

| Sección | Páginas |
|---|---|
| Dashboard | `index.php` → `dashboard.php` |
| Alumnos | `alumnos.php`, `alumno_detalle.php`, `alumno_editar.php`, `alumnos_contacto.php`, `alumnos_exportar.php`, `alumnos_importar.php` |
| Módulos | `modulos.php`, `modulo_detalle.php`, `calificaciones.php`, `calificaciones_analisis.php`, `importar_calificaciones.php` |
| Horarios | `horarios.php`, `horarios_editar.php`, `horarios_importar.php` |
| Empresas | `empresas.php`, `empresa_detalle.php`, `empresa_editar.php`, `empresa_nueva.php` |
| Prácticas | `practicas.php`, `practicas_listado.php`, `practica_detalle.php`, `practica_nueva.php`, `practica_editar.php`, `practica_eliminar.php`, `practicas_anexos.php`, `practicas_dias.php`, `practicas_ras.php`, `practicas_ficha_seguimiento.php`, `practicas_contacto.php`, `practicas_documentacion.php` |
| Profesores | `profesores.php`, `profesor_editar.php` |
| Utilidades | `utilidades.php`, `asistencia.php`, `asistencia_importar.php`, `calendario.php`, `emails.php`, `enviar_correos.php`, `dump_db.php`, `grupo_por_defecto.php` |
| Configuración | `configuracion.php`, `datos_centro.php` |
| PDFs | `generar_plan_formacion.php` |

### Includes reutilizables

- `includes/sidebar.php` — sidebar compartido; incluido en todas las páginas
- `includes/validaciones.php` — validaciones de teléfono, correo, DNI/NIE españoles
- `includes/practicas_pdfs.php` — helpers de redirección y nombre de fichero para PDFs de prácticas
- `includes/practicas_pdf_helpers.php` — helpers de formato para contenido de PDFs
- `includes/practicas_dias_calculo.php` — lógica de cálculo de días/horas de prácticas
- `includes/practicas_exportar.php` — lógica de exportación de prácticas
- `includes/analisis_exportar.php` — lógica de exportación de análisis
- `includes/generar_claves_json.php` — cálculo de clave de validación a partir de DNI + NIA
- `includes/generar_anexo_3a.php` / `includes/generar_anexo_4a.php` — generación de PDFs de anexos vía mPDF
- `includes/generar_fichas_zip.php` — genera un ZIP con fichas PDF de prácticas (mPDF + ZipArchive, solo POST)
- `includes/generar_informe_valoracion_tutor_empresa.php` — informe de valoración del tutor de empresa en PDF
- `includes/generar_informe_valoracion_tutor_empresa_rellenable.php` — versión rellenable del informe anterior
- `includes/generar_ficha_seguimiento_periodico.php` — ficha de seguimiento periódico en PDF
- `includes/generar_calendario_practica.php` — calendario de práctica en PDF

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
