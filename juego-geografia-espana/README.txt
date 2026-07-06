GeoEspaña — Provincias y Comunidades Autónomas
================================================

Ahora la app tiene usuarios (nombre + PIN de 4 dígitos) y ranking, así
que necesita una base de datos MySQL. En InfinityFree es gratis y se
crea en un par de minutos.

Cómo subirlo a InfinityFree:

1. En el panel de InfinityFree, ve a "MySQL Databases" y crea una base
   de datos nueva. Apunta los 4 datos que te da: host, nombre de la
   base de datos, usuario y contraseña (el nombre y el usuario suelen
   llevar un prefijo tipo "if0_12345678_").
2. Abre phpMyAdmin desde ese mismo panel, entra en tu base de datos y
   ejecuta el fichero database/schema.sql (pestaña "Importar" o pegar
   su contenido en "SQL"). Esto crea las tablas "usuarios" y
   "puntuaciones".
3. Edita el fichero config.php de este ZIP (o renombra config.example.php
   a config.php si no existe) y rellena host / name / user / pass con
   los datos del paso 1.
4. Entra en tu panel de archivos (Administrador de archivos) y abre la
   carpeta "htdocs" de tu dominio.
5. Sube este ZIP y descomprímelo directamente DENTRO de "htdocs" (el
   contenido debe quedar en htdocs/index.php, htdocs/juego.php, etc.,
   no dentro de una subcarpeta adicional).
6. Abre tu dominio en el móvil. La primera vez que alguien escriba un
   nombre de usuario y un PIN, se le crea la cuenta automáticamente.

Requisitos: PHP 7.4+ y una base de datos MySQL (InfinityFree incluye
ambos gratis).

Cómo funciona:
- Cada jugador entra con un nombre de usuario y un PIN de 4 dígitos
  (si el nombre no existe, se crea la cuenta con ese PIN; si ya
  existe, el PIN debe coincidir).
- Antes de jugar, se elige "modo aprendizaje" (sin cronómetro, no se
  guarda) o "modo cronometrado" (se mide el tiempo y el resultado
  entra en el ranking de ese modo).
- El ranking de cada modo ordena primero por porcentaje de aciertos y,
  en caso de empate, por menor tiempo.

Estructura:
- index.php                 Menú principal con los 4 modos de juego
- juego.php                  Motor del juego (?tipo=&modo=&variante=)
- login.php / logout.php     Entrada con usuario + PIN, cierre de sesión
- ranking.php                Clasificación de un modo concreto
- guardar_puntuacion.php     Endpoint que guarda las partidas cronometradas
- config.example.php         Plantilla de configuración de la base de datos
- db.php                     Conexión PDO a MySQL
- database/schema.sql        Tablas a importar en phpMyAdmin
- includes/                  Cabecera, pie, helpers, sesión y consultas de ranking
- assets/css/                Estilos
- assets/js/                 Lógica del juego, zoom/pan del mapa, sonido y cronómetro
- assets/svg/                Mapas de España (provincias y comunidades autónomas)
- data/                      Listados de provincias/comunidades y CCAA vecinas en JSON
