GeoEspaña — Provincias y Comunidades Autónomas
================================================

Cómo subirlo a InfinityFree:

1. Entra en tu panel de InfinityFree (cPanel / Administrador de archivos)
   y abre la carpeta "htdocs" de tu dominio.
2. Sube este ZIP y descomprímelo directamente DENTRO de "htdocs" (el
   contenido debe quedar en htdocs/index.php, htdocs/juego.php, etc.,
   no dentro de una subcarpeta adicional).
3. Abre tu dominio en el móvil. La primera vez que alguien escriba un
   nombre de usuario y un PIN, se le crea la cuenta automáticamente.
   Listo: no hace falta crear ninguna base de datos ni tocar ningún
   fichero de configuración.

Requisitos: solo PHP 7.4+ (InfinityFree ya lo incluye). No usa MySQL:
los usuarios y las puntuaciones se guardan en dos ficheros JSON dentro
de data/almacen/, protegidos con un .htaccess para que no se puedan
descargar directamente. Asegúrate de que esa carpeta viaja tal cual
está en el ZIP (con su .htaccess) al subirla.

Cómo funciona:
- Cada jugador entra con un nombre de usuario y un PIN de 4 dígitos
  (si el nombre no existe, se crea la cuenta con ese PIN; si ya
  existe, el PIN debe coincidir).
- Antes de jugar, se elige "modo aprendizaje" (sin cronómetro, no se
  guarda) o "modo cronometrado" (se mide el tiempo y el resultado
  entra en el ranking de ese modo).
- El ranking de cada modo ordena primero por porcentaje de aciertos y,
  en caso de empate, por menor tiempo.

Instalar como app en el móvil:
La web incluye manifest.json y un service worker, así que en Chrome,
Brave, Edge o Samsung Internet (Android) el navegador ofrece "Instalar
app" (no solo "Añadir a pantalla de inicio"): queda con icono propio y
se abre a pantalla completa, sin barra de navegador. Para eso hace
falta que se sirva por HTTPS: el subdominio gratuito de InfinityFree
(algo.infinityfreeapp.com) ya lo trae activado; si usas un dominio
propio, activa el SSL gratuito desde el panel de InfinityFree antes de
comprobar la instalación.

Estructura:
- index.php                 Menú principal con los 4 modos de juego
- juego.php                  Motor del juego (?tipo=&modo=&variante=)
- login.php / logout.php     Entrada con usuario + PIN, cierre de sesión
- ranking.php                Clasificación de un modo concreto
- guardar_puntuacion.php     Endpoint que guarda las partidas cronometradas
- includes/                  Cabecera, pie, helpers, sesión y almacén de datos
- includes/almacen.php       Lectura/escritura de JSON con bloqueo de fichero
- includes/usuarios.php      Alta y consulta de cuentas
- includes/puntuaciones.php  Ranking y guardado de partidas cronometradas
- data/almacen/              usuarios.json y puntuaciones.json (con .htaccess)
- manifest.json               Metadatos de instalación (nombre, iconos, colores)
- sw.js                       Service worker (solo cachea CSS/JS/SVG/PNG)
- assets/css/                Estilos
- assets/js/                 Lógica del juego, zoom/pan del mapa, sonido y cronómetro
- assets/svg/                Mapas de España (provincias y comunidades autónomas)
- data/                      Listados de provincias/comunidades y CCAA vecinas en JSON
