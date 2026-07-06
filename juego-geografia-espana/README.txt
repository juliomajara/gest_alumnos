GeoEspaña — Provincias y Comunidades Autónomas
================================================

Cómo subirlo a InfinityFree:

1. Entra en tu panel de InfinityFree (cPanel / Ficheros / Administrador de archivos)
   y abre la carpeta "htdocs" de tu dominio.
2. Sube este ZIP y descomprímelo directamente DENTRO de "htdocs"
   (el contenido debe quedar en htdocs/index.php, htdocs/juego.php, etc.,
   no dentro de una subcarpeta adicional).
3. Abre tu dominio en el móvil. Listo, no requiere base de datos ni configuración.

Requisitos: PHP 7.4 o superior (InfinityFree ya lo incluye). No usa MySQL:
las puntuaciones se guardan en el propio navegador (localStorage), por
dispositivo.

Estructura:
- index.php        Menú principal con los 4 modos de juego
- juego.php         Motor del juego (recibe ?tipo=provincias|ccaa&modo=reconocer|tocar)
- includes/         Cabecera, pie y helpers PHP
- assets/css/       Estilos
- assets/js/        Lógica del juego, zoom/pan del mapa, sonido y estadísticas
- assets/svg/       Mapas de España (provincias y comunidades autónomas)
- data/             Listados de provincias/comunidades en JSON
