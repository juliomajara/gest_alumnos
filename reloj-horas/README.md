# Aprende las horas

Aplicación web para aprender a leer y poner la hora en un reloj analógico.
Es HTML/CSS/JS estático puro (sin PHP, sin build, sin dependencias), pensada
para usarse en el móvil.

## Modos

- **👀 Leer la hora**: el reloj marca una hora al azar y hay que indicarla
  eligiendo la hora y los minutos.
- **✋ Poner la hora**: se propone una hora en texto y hay que arrastrar las
  agujas (la corta para las horas, la larga para los minutos) hasta marcarla.

Hay 4 niveles de dificultad (en punto / cuartos y medias / cada 5 minutos /
cualquier minuto), que afectan tanto a las horas que se proponen como a la
precisión con la que se pueden mover las agujas.

## Subir a InfinityFree

1. Entra en el panel de control de InfinityFree y abre el **Administrador de
   archivos** (o conéctate por FTP con las credenciales de tu cuenta).
2. Ve a la carpeta `htdocs` de tu dominio (o a una subcarpeta si quieres que
   la app viva en `tudominio.com/reloj/`, por ejemplo).
3. Sube los 5 archivos de esta carpeta (`index.html`, `style.css`,
   `script.js`, `icon.svg`, `manifest.json`) manteniéndolos juntos en el
   mismo directorio.
4. Abre `https://tudominio.com/` (o `https://tudominio.com/reloj/` si la
   subiste en una subcarpeta) desde el móvil.

No hace falta base de datos, PHP ni ninguna configuración adicional: son
solo ficheros estáticos.
