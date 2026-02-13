-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-02-2026 a las 23:08:27
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gest_alumnos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

CREATE TABLE `alumnos` (
  `id_alumno` int(10) UNSIGNED NOT NULL,
  `nia` varchar(20) DEFAULT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `seg_soc` varchar(20) DEFAULT NULL,
  `apellido1` varchar(60) NOT NULL,
  `apellido2` varchar(60) DEFAULT NULL,
  `nombre` varchar(60) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `horas_ffe_aprobadas` smallint(5) UNSIGNED DEFAULT NULL,
  `id_provincia` int(10) UNSIGNED DEFAULT NULL,
  `id_localidad` int(10) UNSIGNED DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `repite_curso` tinyint(1) DEFAULT 0,
  `nombre_tutor1` varchar(60) DEFAULT NULL,
  `telefono_tutor1` varchar(20) DEFAULT NULL,
  `correo_tutor1` varchar(150) DEFAULT NULL,
  `nombre_tutor2` varchar(60) DEFAULT NULL,
  `telefono_tutor2` varchar(20) DEFAULT NULL,
  `correo_tutor2` varchar(150) DEFAULT NULL,
  `faltas_10_dia` date DEFAULT NULL,
  `faltas_10_cantidad` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `faltas_15_dia` date DEFAULT NULL,
  `faltas_15_cantidad` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `comentarios` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumno_curso`
--

CREATE TABLE `alumno_curso` (
  `id_alumno` int(10) UNSIGNED NOT NULL,
  `id_curso_escolar` int(10) UNSIGNED NOT NULL,
  `id_nivel` int(10) UNSIGNED DEFAULT NULL,
  `id_ciclo` int(10) UNSIGNED DEFAULT NULL,
  `id_curso` int(10) UNSIGNED DEFAULT NULL,
  `id_grupo` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumno_modulo`
--

CREATE TABLE `alumno_modulo` (
  `id_alumno` int(10) UNSIGNED NOT NULL,
  `id_modulo` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia_mensual`
--

CREATE TABLE `asistencia_mensual` (
  `id_asistencia` int(10) UNSIGNED NOT NULL,
  `id_alumno` int(10) UNSIGNED NOT NULL,
  `id_curso_escolar` int(10) UNSIGNED NOT NULL,
  `id_mes` int(10) UNSIGNED NOT NULL,
  `faltas_justificadas` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `faltas_injustificadas` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `retrasos` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciclos`
--

CREATE TABLE `ciclos` (
  `id_ciclo` int(10) UNSIGNED NOT NULL,
  `ciclo` varchar(60) NOT NULL,
  `abreviatura` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ciclos`
--

INSERT INTO `ciclos` (`id_ciclo`, `ciclo`, `abreviatura`) VALUES
(1, 'Sistemas Microinformáticos y Redes', 'SMR'),
(2, 'Administración de Sistemas Informáticos en Red', 'ASIR'),
(3, 'Desarrollo de Aplicaciones Multiplataforma', 'DAM'),
(4, 'Desarrollo de Aplicaciones Web', 'DAW');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `correos`
--

CREATE TABLE `correos` (
  `id_correo` int(10) UNSIGNED NOT NULL,
  `entidad_tipo` varchar(60) NOT NULL,
  `id_entidad` int(10) UNSIGNED NOT NULL,
  `direccion_correo` varchar(150) NOT NULL,
  `etiqueta` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `criterios_evaluacion`
--

CREATE TABLE `criterios_evaluacion` (
  `id_ce` int(10) UNSIGNED NOT NULL,
  `id_ra` int(10) UNSIGNED NOT NULL,
  `codigo` char(1) NOT NULL,
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `criterios_evaluacion`
--

INSERT INTO `criterios_evaluacion` (`id_ce`, `id_ra`, `codigo`, `descripcion`) VALUES
(1, 1, 'a', 'Se han descrito los bloques que componen un equipo microinformático y sus funciones.'),
(2, 1, 'b', 'Se ha reconocido la arquitectura de buses.'),
(3, 1, 'c', 'Se han descrito las características de los tipos de microprocesadores (frecuencia, tensiones, potencia, zócalos, entre otros).'),
(4, 1, 'd', 'Se ha descrito la función de los disipadores y ventiladores.'),
(5, 1, 'e', 'Se han descrito las características y utilidades más importantes de la configuración de la placa base.'),
(6, 1, 'f', 'Se han evaluado tipos de chasis para la placa base y el resto de componentes.'),
(7, 1, 'g', 'Se han identificado y manipulado los componentes básicos (módulos de memoria, discos fijos y sus controladoras, soportes de memorias auxiliares, entre otros).'),
(8, 1, 'h', 'Se ha analizado la función del adaptador gráfico y el monitor.'),
(9, 1, 'i', 'Se han identificado y manipulado distintos adaptadores (gráficos, LAN, modems, entre otros).'),
(10, 1, 'j', 'Se han identificado los elementos que acompañan a un componente de integración (documentación, controladores, cables y utilidades, entre otros).'),
(11, 2, 'a', 'Se han seleccionado las herramientas y útiles necesarios para el ensamblado de equipos microinformáticos.'),
(12, 2, 'b', 'Se ha interpretado la documentación técnica de todos los componentes a ensamblar.'),
(13, 2, 'c', 'Se ha determinado el sistema de apertura / cierre del chasis y los distintos sistemas de fijación para ensamblar-desensamblar los elementos del equipo.'),
(14, 2, 'd', 'Se han ensamblado diferentes conjuntos de placa base, microprocesador y elementos de refrigeración en diferentes modelos de chasis, según las especificaciones dadas.'),
(15, 2, 'e', 'Se han ensamblado los módulos de memoria RAM, los discos fijos, las unidades de lectura / grabación en soportes de memoria auxiliar y otros componentes.'),
(16, 2, 'f', 'Se han configurado parámetros básicos del conjunto accediendo a la configuración de la placa base.'),
(17, 2, 'g', 'Se han ejecutado utilidades de chequeo y diagnóstico para verificar las prestaciones del conjunto ensamblado.'),
(18, 2, 'h', 'Se ha realizado un informe de montaje.'),
(19, 3, 'a', 'Se ha identificado el tipo de señal a medir con el aparato correspondiente.'),
(20, 3, 'b', 'Se ha seleccionado la magnitud, el rango de medida y se ha conectado el aparato según la magnitud a medir.'),
(21, 3, 'c', 'Se ha relacionado la medida obtenida con los valores típicos.'),
(22, 3, 'd', 'Se han identificado los bloques de una fuente de alimentación (F.A.) para un ordenador personal.'),
(23, 3, 'e', 'Se han enumerado las tensiones proporcionadas por una F.A. típica.'),
(24, 3, 'f', 'Se han medido las tensiones en F.A. típicas de ordenadores personales.'),
(25, 3, 'g', 'Se han identificado los bloques de un sistema de alimentación ininterrumpida.'),
(26, 3, 'h', 'Se han medido las señales en los puntos significativos de un SAI.'),
(27, 4, 'a', 'Se han reconocido las señales acústicas y/o visuales que avisan de problemas en el hardware de un equipo.'),
(28, 4, 'b', 'Se han identificado y solventado las averías producidas por sobrecalentamiento del microprocesador.'),
(29, 4, 'c', 'Se han identificado y solventado averías típicas de un equipo microinformático (mala conexión de componentes, incompatibilidades, problemas en discos fijos, suciedad, entre otras).'),
(30, 4, 'd', 'Se han sustituido componentes deteriorados.'),
(31, 4, 'e', 'Se ha verificado la compatibilidad de los componentes sustituidos.'),
(32, 4, 'f', 'Se han realizado actualizaciones y ampliaciones de componentes.'),
(33, 4, 'g', 'Se han elaborado informes de avería (reparación o ampliación).'),
(34, 5, 'a', 'Se ha reconocido la diferencia entre una instalación estándar y una preinstalación de software.'),
(35, 5, 'b', 'Se han identificado y probado las distintas secuencias de arranque configurables en la placa base.'),
(36, 5, 'c', 'Se han inicializado equipos desde distintos soportes de memoria auxiliar.'),
(37, 5, 'd', 'Se han realizado imágenes de una preinstalación de software.'),
(38, 5, 'e', 'Se han restaurado imágenes sobre el disco fijo desde distintos soportes.'),
(39, 5, 'f', 'Se han descrito las utilidades para la creación de imágenes de partición/disco.'),
(40, 6, 'a', 'Se han reconocido las nuevas posibilidades para dar forma al conjunto chasis-placa base.'),
(41, 6, 'b', 'Se han descrito las prestaciones y características de algunas de las plataformas semiensambladas («barebones») más representativas del momento.'),
(42, 6, 'c', 'Se han descrito las características de los ordenadores de entretenimiento multimedia (HTPC), los chasis y componentes específicos empleados en su ensamblado.'),
(43, 6, 'd', 'Se han descrito las características diferenciales que demandan los equipos informáticos empleados en otros campos de aplicación específicos.'),
(44, 6, 'e', 'Se ha evaluado la presencia de la informática móvil como mercado emergente, con una alta demanda en equipos y dispositivos con características específicas: móviles, PDA, navegadores, entre otros.'),
(45, 6, 'f', 'Se ha evaluado la presencia del «modding» como corriente alternativa al ensamblado de equipos microinformáticos.'),
(46, 7, 'a', 'Se han identificado y solucionado problemas mecánicos en periféricos de impresión estándar.'),
(47, 7, 'b', 'Se han sustituido consumibles en periféricos de impresión estándar.'),
(48, 7, 'c', 'Se han identificado y solucionado problemas mecánicos en periféricos de entrada.'),
(49, 7, 'd', 'Se han asociado las características y prestaciones de los periféricos de captura de imágenes digitales, fijas y en movimiento con sus posibles aplicaciones.'),
(50, 7, 'e', 'Se han asociado las características y prestaciones de otros periféricos multimedia con sus posibles aplicaciones.'),
(51, 7, 'f', 'Se han reconocido los usos y ámbitos de aplicación de equipos de fotocopiado, impresión digital profesional y filmado.'),
(52, 7, 'g', 'Se han aplicado técnicas de mantenimiento preventivo a los periféricos.'),
(53, 8, 'a', 'Se han identificado los riesgos y el nivel de peligrosidad que suponen la manipulación de los materiales, herramientas, útiles, máquinas y medios de transporte.'),
(54, 8, 'b', 'Se han operado las máquinas respetando las normas de seguridad.'),
(55, 8, 'c', 'Se han identificado las causas más frecuentes de accidentes en la manipulación de materiales, herramientas, máquinas de corte y conformado, entre otras.'),
(56, 8, 'd', 'Se han descrito los elementos de seguridad (protecciones, alarmas, pasos de emergencia, entre otros) de las máquinas y los equipos de protección individual (calzado, protección ocular, indumentaria, entre otros) que se deben emplear en las distintas operaciones de montaje y mantenimiento.'),
(57, 8, 'e', 'Se ha relacionado la manipulación de materiales, herramientas y máquinas con las medidas de seguridad y protección personal requeridos.'),
(58, 8, 'f', 'Se han identificado las posibles fuentes de contaminación del entorno ambiental.'),
(59, 8, 'g', 'Se han clasificado los residuos generados para su retirada selectiva.'),
(60, 8, 'h', 'Se ha valorado el orden y la limpieza de instalaciones y equipos como primer factor de prevención de riesgos.'),
(61, 9, 'a', 'Se han identificado y descrito los elementos funcionales de un sistema informático.'),
(62, 9, 'b', 'Se ha codificado y relacionado la información en los diferentes sistemas de representación.'),
(63, 9, 'c', 'Se han identificado los procesos y sus estados.'),
(64, 9, 'd', 'Se ha descrito la estructura y organización del sistema de archivos.'),
(65, 9, 'e', 'Se han distinguido los atributos de un archivo y un directorio.'),
(66, 9, 'f', 'Se han reconocido los permisos de archivos y directorios.'),
(67, 9, 'g', 'Se ha constatado la utilidad de los sistemas transaccionales y sus repercusiones al seleccionar un sistema de archivos.'),
(68, 10, 'a', 'Se han analizando las funciones del sistema operativo.'),
(69, 10, 'b', 'Se ha descrito la arquitectura del sistema operativo.'),
(70, 10, 'c', 'Se ha verificado la idoneidad del hardware.'),
(71, 10, 'd', 'Se ha seleccionado el sistema operativo.'),
(72, 10, 'e', 'Se ha elaborado un plan de instalación.'),
(73, 10, 'f', 'Se han configurado parámetros básicos de la instalación.'),
(74, 10, 'g', 'Se ha configurado un gestor de arranque.'),
(75, 10, 'h', 'Se han descrito las incidencias de la instalación.'),
(76, 10, 'i', 'Se han respetado las normas de utilización del software (licencias).'),
(77, 10, 'j', 'Se ha actualizado el sistema operativo.'),
(78, 11, 'a', 'Se han diferenciado los interfaces de usuario según sus propiedades.'),
(79, 11, 'b', 'Se han aplicado preferencias en la configuración del entorno personal.'),
(80, 11, 'c', 'Se han gestionado los sistemas de archivos específicos.'),
(81, 11, 'd', 'Se han aplicado métodos para la recuperación del sistema operativo.'),
(82, 11, 'e', 'Se ha realizado la configuración para la actualización del sistema operativo.'),
(83, 11, 'f', 'Se han realizado operaciones de instalación/desinstalación de utilidades.'),
(84, 11, 'g', 'Se han utilizado los asistentes de configuración del sistema (acceso a redes, dispositivos, entre otros).'),
(85, 11, 'h', 'Se han ejecutado operaciones para la automatización de tareas del sistema.'),
(86, 12, 'a', 'Se han configurado perfiles de usuario y grupo.'),
(87, 12, 'b', 'Se han utilizado herramientas gráficas para describir la organización de los archivos del sistema.'),
(88, 12, 'c', 'Se ha actuado sobre los procesos del usuario en función de las necesidades puntuales.'),
(89, 12, 'd', 'Se ha actuado sobre los servicios del sistema en función de las necesidades puntuales.'),
(90, 12, 'e', 'Se han aplicado criterios para la optimización de la memoria disponible.'),
(91, 12, 'f', 'Se ha analizado la actividad del sistema a partir de las trazas generadas por el propio sistema.'),
(92, 12, 'g', 'Se ha optimizado el funcionamiento de los dispositivos de almacenamiento.'),
(93, 12, 'h', 'Se han reconocido y configurado los recursos compartibles del sistema.'),
(94, 12, 'i', 'Se ha interpretado la información de configuración del sistema operativo.'),
(95, 13, 'a', 'Se ha diferenciado entre máquina real y máquina virtual.'),
(96, 13, 'b', 'Se han establecido las ventajas e inconvenientes de la utilización de máquinas virtuales.'),
(97, 13, 'c', 'Se ha instalado el software libre y propietario para la creación de máquinas virtuales.'),
(98, 13, 'd', 'Se han creado máquinas virtuales a partir de sistemas operativos libres y propietarios.'),
(99, 13, 'e', 'Se han configurado máquinas virtuales.'),
(100, 13, 'f', 'Se ha relacionado la máquina virtual con el sistema operativo anfitrión.'),
(101, 13, 'g', 'Se han realizado pruebas de rendimiento del sistema.'),
(102, 101, 'a', 'Se han identificado y establecido las fases del proceso de instalación.'),
(103, 101, 'b', 'Se han respetado las especificaciones técnicas del proceso de instalación.'),
(104, 101, 'c', 'Se han configurado las aplicaciones según los criterios establecidos.'),
(105, 101, 'd', 'Se han documentado las incidencias.'),
(106, 101, 'e', 'Se han solucionado problemas en la instalación o integración con el sistema informático.'),
(107, 101, 'f', 'Se han eliminado y/o añadido componentes de la instalación en el equipo.'),
(108, 101, 'g', 'Se han actualizado las aplicaciones.'),
(109, 101, 'h', 'Se han respetado las licencias software.'),
(110, 101, 'i', 'Se han propuesto soluciones software para entornos de aplicación.'),
(111, 102, 'a', 'Se ha personalizado las opciones de software y barra de herramientas.'),
(112, 102, 'b', 'Se han diseñado plantillas.'),
(113, 102, 'c', 'Se han utilizado aplicaciones y periféricos para introducir textos e imágenes.'),
(114, 102, 'd', 'Se han importado y exportado documentos creados con otras aplicaciones y en otros formatos.'),
(115, 102, 'e', 'Se han creado y utilizado macros en la realización de documentos.'),
(116, 102, 'f', 'Se han elaborado manuales específicos.'),
(117, 103, 'a', 'Se ha personalizado las opciones de software y barra de herramientas.'),
(118, 103, 'b', 'Se han utilizado los diversos tipos de datos y referencia para celdas, rangos, hojas y libros.'),
(119, 103, 'c', 'Se han aplicado fórmulas y funciones.'),
(120, 103, 'd', 'Se han generado y modificado gráficos de diferentes tipos.'),
(121, 103, 'e', 'Se han empleado macros para la realización de documentos y plantillas.'),
(122, 103, 'f', 'Se han importado y exportado hojas de cálculo creadas con otras aplicaciones y en otros formatos.'),
(123, 103, 'g', 'Se ha utilizado la hoja de cálculo como base de datos: formularios, creación de listas, filtrado, protección y ordenación de datos.'),
(124, 103, 'h', 'Se han utilizado aplicaciones y periféricos para introducir textos, números, códigos e imágenes.'),
(125, 104, 'a', 'Se han identificado los elementos de las bases de datos relacionales.'),
(126, 104, 'b', 'Se han creado bases de datos ofimáticas.'),
(127, 104, 'c', 'Se han utilizado las tablas de la base de datos (insertar, modificar y eliminar registros).'),
(128, 104, 'd', 'Se han utilizado asistentes en la creación de consultas.'),
(129, 104, 'e', 'Se han utilizado asistentes en la creación de formularios.'),
(130, 104, 'f', 'Se han utilizado asistentes en la creación de informes.'),
(131, 104, 'g', 'Se ha realizado búsqueda y filtrado sobre la información almacenada.'),
(132, 104, 'h', 'Se han creado y utilizado macros.'),
(133, 105, 'a', 'Se han analizado los distintos formatos de imágenes.'),
(134, 105, 'b', 'Se ha realizado la adquisición de imágenes con periféricos.'),
(135, 105, 'c', 'Se ha trabajado con imágenes a diferentes resoluciones, según su finalidad.'),
(136, 105, 'd', 'Se han empleado herramientas para la edición de imagen digital.'),
(137, 105, 'e', 'Se han importado y exportado imágenes en diversos formatos.'),
(138, 106, 'a', 'Se han reconocido los elementos que componen una secuencia de vídeo.'),
(139, 106, 'b', 'Se han estudiado los tipos de formatos y codecs más empleados.'),
(140, 106, 'c', 'Se han importado y exportado secuencias de vídeo.'),
(141, 106, 'd', 'Se han capturado secuencias de vídeo con recursos adecuados.'),
(142, 106, 'e', 'Se han elaborado vídeo tutoriales.'),
(143, 107, 'a', 'Se han identificado las opciones básicas de las aplicaciones de presentaciones.'),
(144, 107, 'b', 'Se han reconocido los distintos tipos de vista asociados a una presentación.'),
(145, 107, 'c', 'Se han aplicado y reconocido las distintas tipografías y normas básicas de composición, diseño y utilización del color.'),
(146, 107, 'd', 'Se han diseñado plantillas de presentaciones.'),
(147, 107, 'e', 'Se han creado presentaciones.'),
(148, 107, 'f', 'Se han utilizado periféricos para ejecutar presentaciones.'),
(149, 108, 'a', 'Se han descrito los elementos que componen un correo electrónico.'),
(150, 108, 'b', 'Se han analizado las necesidades básicas de gestión de correo y agenda electrónica.'),
(151, 108, 'c', 'Se han configurado distintos tipos de cuentas de correo electrónico.'),
(152, 108, 'd', 'Se han conectado y sincronizado agendas del equipo informático con dispositivos móviles.'),
(153, 108, 'e', 'Se ha operado con la libreta de direcciones.'),
(154, 108, 'f', 'Se ha trabajado con todas las opciones de gestión de correo electrónico (etiquetas, filtros, carpetas, entre otros).'),
(155, 108, 'g', 'Se han utilizado opciones de agenda electrónica.'),
(156, 109, 'a', 'Se han elaborado guías visuales con los conceptos básicos de uso de una aplicación.'),
(157, 109, 'b', 'Se han identificado problemas relacionados con el uso de aplicaciones ofimáticas.'),
(158, 109, 'c', 'Se han utilizado manuales de usuario para instruir en el uso de aplicaciones.'),
(159, 109, 'd', 'Se han aplicado técnicas de asesoramiento en el uso de aplicaciones.'),
(160, 109, 'e', 'Se han realizado informes de incidencias.'),
(161, 109, 'f', 'Se han aplicado los procedimientos necesarios para salvaguardar la información y su recuperación.'),
(162, 109, 'g', 'Se han utilizado los recursos disponibles (documentación técnica, ayudas en línea, soporte técnico, entre otros) para solventar incidencias.'),
(163, 109, 'h', 'Se han solventando las incidencias en el tiempo adecuado y con el nivel de calidad esperado.'),
(164, 110, 'a', 'Se han identificado las empresas tipo más representativas del sector.'),
(165, 110, 'b', 'Se ha descrito la estructura organizativa de las empresas.'),
(166, 110, 'c', 'Se han caracterizado los principales departamentos.'),
(167, 110, 'd', 'Se han determinado las funciones de cada departamento.'),
(168, 110, 'e', 'Se ha evaluado el volumen de negocio de acuerdo a las necesidades de los clientes.'),
(169, 110, 'f', 'Se ha definido la estrategia para dar respuesta a las demandas.'),
(170, 110, 'g', 'Se han valorado los recursos humanos y materiales necesarios.'),
(171, 110, 'h', 'Se ha realizado el seguimiento de los resultados de acuerdo a la estrategia aplicada.'),
(172, 110, 'i', 'Se han relacionado los productos o servicios con su posible contribución a los ODS (Objetivos de Desarrollo Sostenible).'),
(173, 111, 'a', 'Se han identificado las necesidades.'),
(174, 111, 'b', 'Se han planteado en grupo posibles soluciones.'),
(175, 111, 'c', 'Se ha obtenido la información relativa a las soluciones planteadas.'),
(176, 111, 'd', 'Se han identificado aspectos innovadores que puedan ser de aplicación.'),
(177, 111, 'e', 'Se ha realizado el estudio de viabilidad técnica.'),
(178, 111, 'f', 'Se han identificado las partes que componen el proyecto.'),
(179, 111, 'g', 'Se han previsto los recursos materiales y humanos para realizarlo.'),
(180, 111, 'h', 'Se ha realizado el presupuesto económico correspondiente.'),
(181, 111, 'i', 'Se ha definido y elaborado la documentación para su diseño.'),
(182, 111, 'j', 'Se han identificado los aspectos relacionados con la calidad del proyecto.'),
(183, 111, 'k', 'Se han presentado en público las ideas más relevantes de los proyectos propuestos.'),
(184, 112, 'a', 'Se han temporizado las secuencias de las actividades.'),
(185, 112, 'b', 'Se han determinado los recursos y la logística de cada actividad.'),
(186, 112, 'c', 'Se han identificado permisos y autorizaciones en caso de ser necesarios.'),
(187, 112, 'd', 'Se han identificado las actividades que implican riesgos en su ejecución.'),
(188, 112, 'e', 'Se ha tenido en cuenta el plan de prevención de riesgos y los medios y equipos necesarios.'),
(189, 112, 'f', 'Se han asignado recursos materiales y humanos a cada actividad.'),
(190, 112, 'g', 'Se han tenido en cuenta posibles imprevistos.'),
(191, 112, 'h', 'Se han propuesto soluciones a los posibles imprevistos.'),
(192, 112, 'i', 'Se ha elaborado la documentación necesaria.'),
(193, 113, 'a', 'Se ha definido el procedimiento de seguimiento de las actividades.'),
(194, 113, 'b', 'Se ha verificado la calidad de los resultados de las actividades.'),
(195, 113, 'c', 'Se han identificado posibles desviaciones de la planificación y/o los resultados esperados.'),
(196, 113, 'd', 'Se ha informado de las desviaciones en caso de ser necesario.'),
(197, 113, 'e', 'Se han solucionado las desviaciones y se han documentado las intervenciones.'),
(198, 113, 'f', 'Se ha definido y elaborado la documentación necesaria para la evaluación de las actividades y del proyecto en su conjunto.'),
(199, 114, 'a', 'Se ha mantenido una actitud ordenada y metódica en la transmisión de la información.'),
(200, 114, 'b', 'Se ha transmitido información verbal tanto horizontal como verticalmente.'),
(201, 114, 'c', 'Se ha transmitido información entre los miembros del grupo utilizando medios informáticos.'),
(202, 114, 'd', 'Se han conocido los términos técnicos en otras lenguas que sean estándares del sector.'),
(203, 115, 'a', 'Se han descrito los principios de funcionamiento de las redes locales.'),
(204, 115, 'b', 'Se han identificado los distintos tipos de redes.'),
(205, 115, 'c', 'Se han descrito los elementos de la red local y su función.'),
(206, 115, 'd', 'Se han identificado y clasificado los medios de transmisión.'),
(207, 115, 'e', 'Se ha reconocido el mapa físico de la red local.'),
(208, 115, 'f', 'Se han utilizado aplicaciones para representar el mapa físico de la red local.'),
(209, 115, 'g', 'Se han reconocido las distintas topologías de red.'),
(210, 115, 'h', 'Se han identificado estructuras alternativas.'),
(211, 116, 'a', 'Se han reconocido los principios funcionales de las redes locales.'),
(212, 116, 'b', 'Se han identificado los distintos tipos de redes.'),
(213, 116, 'c', 'Se han diferenciado los medios de transmisión.'),
(214, 116, 'd', 'Se han reconocido los detalles del cableado de la instalación y su despliegue (categoría del cableado, espacios por los que discurre, soporte para las canalizaciones, entre otros).'),
(215, 116, 'e', 'Se han seleccionado y montado las canalizaciones y tubos.'),
(216, 116, 'f', 'Se han montado los armarios de comunicaciones y sus accesorios.'),
(217, 116, 'g', 'Se han montado y conexionado las tomas de usuario y paneles de parcheo.'),
(218, 116, 'h', 'Se han probado las líneas de comunicación entre las tomas de usuario y paneles de parcheo.'),
(219, 116, 'i', 'Se han etiquetado los cables y tomas de usuario.'),
(220, 116, 'j', 'Se ha trabajado con la calidad y seguridad requeridas.'),
(221, 117, 'a', 'Se ha interpretado el plan de montaje lógico de la red.'),
(222, 117, 'b', 'Se han montado los adaptadores de red en los equipos.'),
(223, 117, 'c', 'Se han montado conectores sobre cables (cobre y fibra) de red.'),
(224, 117, 'd', 'Se han montado los equipos de conmutación en los armarios de comunicaciones.'),
(225, 117, 'e', 'Se han conectado los equipos de conmutación a los paneles de parcheo.'),
(226, 117, 'f', 'Se ha verificado la conectividad de la instalación.'),
(227, 117, 'g', 'Se ha trabajado con la calidad requerida.'),
(228, 118, 'a', 'Se han identificado las características funcionales de las redes inalámbricas.'),
(229, 118, 'b', 'Se han identificado los modos de funcionamiento de las redes inalámbricas.'),
(230, 118, 'c', 'Se han instalado adaptadores y puntos de acceso inalámbrico.'),
(231, 118, 'd', 'Se han configurado los modos de funcionamiento y los parámetros básicos.'),
(232, 118, 'e', 'Se ha comprobado la conctividad entre diversos dispositivos y adaptadores inalámbricos.'),
(233, 118, 'f', 'Se ha instalado el software correspondiente.'),
(234, 118, 'g', 'Se han identificado los protocolos.'),
(235, 118, 'h', 'Se han configurado los parámetros básicos.'),
(236, 118, 'i', 'Se han aplicados mecanismos básicos de seguridad.'),
(237, 118, 'j', 'Se han creado y configurado VLANS.'),
(238, 119, 'a', 'Se han identificado incidencias y comportamientos anómalos.'),
(239, 119, 'b', 'Se ha identificado si la disfunción es debida al hardware o al software.'),
(240, 119, 'c', 'Se han monitorizado las señales visuales de los dispositivos de interconexión.'),
(241, 119, 'd', 'Se han verificado los protocolos de comunicaciones.'),
(242, 119, 'e', 'Se ha localizado la causa de la disfunción.'),
(243, 119, 'f', 'Se ha restituido el funcionamiento sustituyendo equipos o elementos.'),
(244, 119, 'g', 'Se han solucionado las disfunciones software (configurando o reinstalando).'),
(245, 119, 'h', 'Se ha elaborado un informe de incidencias.'),
(246, 120, 'a', 'Se han identificado los riesgos y el nivel de peligrosidad que suponen la manipulación de los materiales, herramientas, útiles, máquinas y medios de transporte.'),
(247, 120, 'b', 'Se han operado las máquinas respetando las normas de seguridad.'),
(248, 120, 'c', 'Se han identificado las causas más frecuentes de accidentes en la manipulación de materiales, herramientas, máquinas de corte y conformado, entre otras.'),
(249, 120, 'd', 'Se han descrito los elementos de seguridad (protecciones, alarmas, pasos de emergencia, entre otros) de las máquinas y los equipos de protección individual que se deben emplear.'),
(250, 120, 'e', 'Se ha relacionado la manipulación de materiales, herramientas y máquinas con las medidas de seguridad y protección personal requeridos.'),
(251, 120, 'f', 'Se han identificado las posibles fuentes de contaminación del entorno ambiental.'),
(252, 120, 'g', 'Se han clasificado los residuos generados para su retirada selectiva.'),
(253, 120, 'h', 'Se ha valorado el orden y la limpieza de instalaciones y equipos como primer factor de prevención de riesgos.'),
(254, 121, 'a', 'Se ha valorado la importancia de mantener la información segura.'),
(255, 121, 'b', 'Se han descrito las diferencias entre seguridad física y lógica.'),
(256, 121, 'c', 'Se han definido las características de la ubicación física y condiciones ambientales de los equipos y servidores.'),
(257, 121, 'd', 'Se ha identificado la necesidad de proteger físicamente los sistemas informáticos.'),
(258, 121, 'e', 'Se ha verificado el funcionamiento de los sistemas de alimentación ininterrumpida.'),
(259, 121, 'f', 'Se han seleccionado los puntos de aplicación de los sistemas de alimentación ininterrumpida.'),
(260, 121, 'g', 'Se han esquematizado las características de una política de seguridad basada en listas de control de acceso.'),
(261, 121, 'h', 'Se ha valorado la importancia de establecer una política de contraseñas.'),
(262, 121, 'i', 'Se han valorado las ventajas que supone la utilización de sistemas biométricos.'),
(263, 122, 'a', 'Se ha interpretado la documentación técnica relativa a la política de almacenamiento.'),
(264, 122, 'b', 'Se han tenido en cuenta factores inherentes al almacenamiento de la información (rendimiento, disponibilidad, accesibilidad, entre otros).'),
(265, 122, 'c', 'Se han clasificado y enumerado los principales métodos de almacenamiento incluidos los sistemas de almacenamiento en red.'),
(266, 122, 'd', 'Se han descrito las tecnologías de almacenamiento redundante y distribuido.'),
(267, 122, 'e', 'Se han seleccionado estrategias para la realización de copias de seguridad.'),
(268, 122, 'f', 'Se ha tenido en cuenta la frecuencia y el esquema de rotación.'),
(269, 122, 'g', 'Se han realizado copias de seguridad con distintas estrategias.'),
(270, 122, 'h', 'Se han identificado las características de los medios de almacenamiento remotos y extraíbles.'),
(271, 122, 'i', 'Se han utilizado medios de almacenamiento remotos y extraíbles.'),
(272, 122, 'j', 'Se han creado y restaurado imágenes de respaldo de sistemas en funcionamiento.'),
(273, 123, 'a', 'Se han seguido planes de contingencia para actuar ante fallos de seguridad.'),
(274, 123, 'b', 'Se han clasificado los principales tipos de software malicioso.'),
(275, 123, 'c', 'Se han realizado actualizaciones periódicas de los sistemas para corregir posibles vulnerabilidades.'),
(276, 123, 'd', 'Se ha verificado el origen y la autenticidad de las aplicaciones que se instalan en los sistemas.'),
(277, 123, 'e', 'Se han instalado, probado y actualizado aplicaciones específicas para la detección y eliminación de software malicioso.'),
(278, 123, 'f', 'Se han aplicado técnicas de recuperación de datos.'),
(279, 124, 'a', 'Se ha identificado la necesidad de inventariar y controlar los servicios de red.'),
(280, 124, 'b', 'Se ha contrastado la incidencia de las técnicas de ingeniería social en los fraudes informáticos y robos de información.'),
(281, 124, 'c', 'Se ha deducido la importancia de minimizar el volumen de tráfico generado por la publicidad y el correo no deseado.'),
(282, 124, 'd', 'Se han aplicado medidas para evitar la monitorización de redes cableadas.'),
(283, 124, 'e', 'Se han clasificado y valorado las propiedades de seguridad de los protocolos usados en redes inalámbricas.'),
(284, 124, 'f', 'Se han descrito sistemas de identificación como la firma electrónica, certificado digital, entre otros.'),
(285, 124, 'g', 'Se han utilizado sistemas de identificación como la firma electrónica, certificado digital, entre otros.'),
(286, 124, 'h', 'Se ha instalado y configurado un cortafuegos en un equipo o servidor.'),
(287, 125, 'a', 'Se ha descrito la legislación sobre protección de datos de carácter personal.'),
(288, 125, 'b', 'Se ha determinado la necesidad de controlar el acceso a la información personal almacenada.'),
(289, 125, 'c', 'Se han identificado las figuras legales que intervienen en el tratamiento y mantenimiento de los ficheros de datos.'),
(290, 125, 'd', 'Se ha contrastado la obligación de poner a disposición de las personas los datos personales que les conciernen.'),
(291, 125, 'e', 'Se ha descrito la legislación actual sobre los servicios de la sociedad de la información y comercio electrónico.'),
(292, 125, 'f', 'Se han contrastado las normas sobre gestión de seguridad de la información.'),
(293, 126, 'a', 'Se ha reconocido el funcionamiento de los mecanismos automatizados de configuración de los parámetros de red.'),
(294, 126, 'b', 'Se han identificado las ventajas que proporcionan.'),
(295, 126, 'c', 'Se han ilustrado los procedimientos y pautas que intervienen en una solicitud de configuración de los parámetros de red.'),
(296, 126, 'd', 'Se ha instalado un servicio de configuración dinámica de los parámetros de red.'),
(297, 126, 'e', 'Se ha preparado el servicio para asignar la configuración básica a los sistemas de una red local.'),
(298, 126, 'f', 'Se han realizado asignaciones dinámicas y estáticas.'),
(299, 126, 'g', 'Se han integrado en el servicio opciones adicionales de configuración.'),
(300, 126, 'h', 'Se ha verificando la correcta asignación de los parámetros.'),
(301, 127, 'a', 'Se han identificado y descrito escenarios en los que surge la necesidad de un servicio de resolución de nombres.'),
(302, 127, 'b', 'Se han clasificado los principales mecanismos de resolución de nombres.'),
(303, 127, 'c', 'Se ha descrito la estructura, nomenclatura y funcionalidad de los sistemas de nombres jerárquicos.'),
(304, 127, 'd', 'Se ha instalado un servicio jerárquico de resolución de nombres.'),
(305, 127, 'e', 'Se ha preparado el servicio para almacenar las respuestas procedentes de servidores de redes públicas y servirlas a los equipos de la red local.'),
(306, 127, 'f', 'Se han añadido registros de nombres correspondientes a una zona nueva, con opciones relativas a servidores de correo y alias.'),
(307, 127, 'g', 'Se ha trabajado en grupo para realizar transferencias de zona entre dos o más servidores.'),
(308, 127, 'h', 'Se ha comprobado el funcionamiento correcto del servidor.'),
(309, 128, 'a', 'Se ha establecido la utilidad y modo de operación del servicio de transferencia de ficheros.'),
(310, 128, 'b', 'Se ha instalado un servicio de transferencia de ficheros.'),
(311, 128, 'c', 'Se han creado usuarios y grupos para acceso remoto al servidor.'),
(312, 128, 'd', 'Se ha configurado el acceso anónimo.'),
(313, 128, 'e', 'Se han establecido límites en los distintos modos de acceso.'),
(314, 128, 'f', 'Se ha comprobado el acceso al servidor, tanto en modo activo como en modo pasivo.'),
(315, 128, 'g', 'Se han realizado pruebas con clientes en línea de comandos y en modo gráfico.'),
(316, 129, 'a', 'Se han descrito los diferentes protocolos que intervienen en el envío y recogida del correo electrónico.'),
(317, 129, 'b', 'Se ha instalado un servidor de correo electrónico.'),
(318, 129, 'c', 'Se han creado cuentas de usuario y verificado el acceso de las mismas.'),
(319, 129, 'd', 'Se han definido alias para las cuentas de correo.'),
(320, 129, 'e', 'Se han aplicados métodos para impedir usos indebidos del servidor de correo electrónico.'),
(321, 129, 'f', 'Se han instalado servicios para permitir la recogida remota del correo existente en los buzones de usuario.'),
(322, 129, 'g', 'Se han usado clientes de correo electrónico para enviar y recibir correo.'),
(323, 130, 'a', 'Se han descrito los fundamentos y protocolos en los que se basa el funcionamiento de un servidor web.'),
(324, 130, 'b', 'Se ha instalado un servidor web.'),
(325, 130, 'c', 'Se han creado sitios virtuales.'),
(326, 130, 'd', 'Se han verificado las posibilidades existentes para discriminar el sitio destino del tráfico entrante al servidor.'),
(327, 130, 'e', 'Se ha configurado la seguridad del servidor.'),
(328, 130, 'f', 'Se ha comprobando el acceso de los usuarios al servidor.'),
(329, 130, 'g', 'Se ha diferenciado y probado la ejecución de código en el servidor y en el cliente.'),
(330, 130, 'h', 'Se han instalado módulos sobre el servidor.'),
(331, 130, 'i', 'Se han establecido mecanismos para asegurar las comunicaciones entre el cliente y el servidor.'),
(332, 131, 'a', 'Se han descrito métodos de acceso y administración remota de sistemas.'),
(333, 131, 'b', 'Se ha instalado un servicio de acceso remoto en línea de comandos.'),
(334, 131, 'c', 'Se ha instalado un servicio de acceso remoto en modo gráfico.'),
(335, 131, 'd', 'Se ha comprobado el funcionamiento de ambos métodos.'),
(336, 131, 'e', 'Se han identificado las principales ventajas y deficiencias de cada uno.'),
(337, 131, 'f', 'Se han realizado pruebas de acceso remoto entre sistemas de distinta naturaleza.'),
(338, 131, 'g', 'Se han realizado pruebas de administración remota entre sistemas de distinta naturaleza.'),
(339, 132, 'a', 'Se ha instalado un punto de acceso inalámbrico dentro de una red local.'),
(340, 132, 'b', 'Se han reconocido los protocolos, modos de funcionamiento y principales parámetros de configuración del punto de acceso.'),
(341, 132, 'c', 'Se ha seleccionado la configuración más idónea sobre distintos escenarios de prueba.'),
(342, 132, 'd', 'Se ha establecido un mecanismo adecuado de seguridad para las comunicaciones inalámbricas.'),
(343, 132, 'e', 'Se han usado diversos tipos de dispositivos y adaptadores inalámbricos para comprobar la cobertura.'),
(344, 132, 'f', 'Se ha instalado un encaminador inalámbrico con conexión a red pública y servicios inalámbricos de red local.'),
(345, 132, 'g', 'Se ha configurado y probado el encaminador desde los ordenadores de la red local.'),
(346, 133, 'a', 'Se ha instalado y configurado el hardware de un sistema con acceso a una red privada local y a una red pública.'),
(347, 133, 'b', 'Se ha instalado una aplicación que actúe de pasarela entre la red privada local y la red pública.'),
(348, 133, 'c', 'Se han reconocido y diferenciado las principales características y posibilidades de la aplicación seleccionada.'),
(349, 133, 'd', 'Se han configurado los sistemas de la red privada local para acceder a la red pública a través de la pasarela.'),
(350, 133, 'e', 'Se han establecidos los procedimientos de control de acceso para asegurar el tráfico que se transmite a través de la pasarela.'),
(351, 133, 'f', 'Se han implementado mecanismos para acelerar las comunicaciones entre la red privada local y la pública.'),
(352, 133, 'g', 'Se han identificado los posibles escenarios de aplicación de este tipo de mecanismos.'),
(353, 133, 'h', 'Se ha establecido un mecanismo que permita reenviar tráfico de red entre dos o más interfaces de un mismo sistema.'),
(354, 133, 'i', 'Se ha comprobado el acceso a una red determinada desde los sistemas conectados a otra red distinta.'),
(355, 133, 'j', 'Se ha implantado y verificado la configuración para acceder desde una red pública a un servicio localizado en una máquina de una red privada local'),
(356, 134, 'a', 'Se han identificado los requerimientos necesarios para instalar gestores de contenidos.'),
(357, 134, 'b', 'Se han gestionado usuarios con roles diferentes.'),
(358, 134, 'c', 'Se ha personalizado la interfaz del gestor de contenidos.'),
(359, 134, 'd', 'Se han realizado pruebas de funcionamiento.'),
(360, 134, 'e', 'Se han realizado tareas de actualización del gestor de contenidos, especialmente las de seguridad.'),
(361, 134, 'f', 'Se han instalado y configurado los módulos y menús necesarios.'),
(362, 134, 'g', 'Se han activado y configurado los mecanismos de seguridad proporcionados por el propio gestor de contenidos.'),
(363, 134, 'h', 'Se han habilitado foros y establecido reglas de acceso.'),
(364, 134, 'i', 'Se han realizado pruebas de funcionamiento.'),
(365, 134, 'j', 'Se han realizado copias de seguridad de los contenidos del gestor.'),
(366, 135, 'a', 'Se ha reconocido la estructura del sitio y la jerarquía de directorios generada.'),
(367, 135, 'b', 'Se han realizado modificaciones en la estética o aspecto del sitio.'),
(368, 135, 'c', 'Se han manipulado y generado perfiles personalizados.'),
(369, 135, 'd', 'Se ha comprobado la funcionalidad de las comunicaciones mediante foros, consultas, entre otros.'),
(370, 135, 'e', 'Se han importado y exportado contenidos en distintos formatos.'),
(371, 135, 'f', 'Se han realizado copias de seguridad y restauraciones.'),
(372, 135, 'g', 'Se han realizado informes de acceso y utilización del sitio.'),
(373, 135, 'h', 'Se ha comprobado la seguridad del sitio.'),
(374, 136, 'a', 'Se ha establecido la utilidad de un servicio de gestión de archivos web.'),
(375, 136, 'b', 'Se han descrito diferentes aplicaciones de gestión de archivos web.'),
(376, 136, 'c', 'Se ha instalado y adaptado una herramienta de gestión de archivos web.'),
(377, 136, 'd', 'Se han creado y clasificado cuentas de usuario en función de sus permisos.'),
(378, 136, 'e', 'Se han gestionado archivos y directorios.'),
(379, 136, 'f', 'Se han utilizado archivos de información adicional.'),
(380, 136, 'g', 'Se han aplicado criterios de indexación sobre los archivos y directorios.'),
(381, 136, 'h', 'Se ha comprobado la seguridad del gestor de archivos.'),
(382, 137, 'a', 'Se ha establecido la utilidad de las aplicaciones de ofimática web.'),
(383, 137, 'b', 'Se han descrito diferentes aplicaciones de ofimática web (procesador de textos, hoja de cálculo, entre otras).'),
(384, 137, 'c', 'Se han instalado aplicaciones de ofimática web.'),
(385, 137, 'd', 'Se han gestionado las cuentas de usuario.'),
(386, 137, 'e', 'Se han aplicado criterios de seguridad en el acceso de los usuarios.'),
(387, 137, 'f', 'Se han reconocido las prestaciones específicas de cada una de las aplicaciones instaladas.'),
(388, 137, 'g', 'Se han utilizado las aplicaciones de forma colaborativa.'),
(389, 138, 'a', 'Se han descrito diferentes aplicaciones web de escritorio.'),
(390, 138, 'b', 'Se han instalado aplicaciones para proveer de acceso web al servicio de correo electrónico.'),
(391, 138, 'c', 'Se han configurado las aplicaciones para integrarlas con un servidor de correo.'),
(392, 138, 'd', 'Se han gestionado las cuentas de usuario.'),
(393, 138, 'e', 'Se ha verificado el acceso al correo electrónico.'),
(394, 138, 'f', 'Se han instalado aplicaciones de calendario web.'),
(395, 138, 'g', 'Se han reconocido las prestaciones específicas de las aplicaciones instaladas (citas, tareas, entre otras).'),
(396, 139, 'a', 'Se han identificado las etapas «típicas» de los modelos basados en EL y modelos basados en EC.'),
(397, 139, 'b', 'Se ha analizado cada etapa de los modelos EL y EC y su repercusión en el medioambiente.'),
(398, 139, 'c', 'Se ha valorado la importancia del reciclaje en los modelos económicos.'),
(399, 139, 'd', 'Se han identificado procesos reales basados en EL.'),
(400, 139, 'e', 'Se han identificado procesos reales basados en EC.'),
(401, 139, 'f', 'Se han comparado los modelos anteriores en relación con su impacto medioambiental y los ODS (Objetivos de Desarrollo Sostenible).'),
(402, 140, 'a', 'Se han relacionado los sistemas ciber físicos con la evolución industrial.'),
(403, 140, 'b', 'Se ha analizado el cambio producido en los sistemas automatizados.'),
(404, 140, 'c', 'Se ha descrito la combinación de la parte física de las industrias con el software, IoT (Internet de las cosas), comunicaciones, entre otros.'),
(405, 140, 'd', 'Se ha descrito la interrelación entre el mundo físico y el virtual.'),
(406, 140, 'e', 'Se ha relacionado la migración a entornos 4.0 con la mejora de los resultados de las empresas.'),
(407, 140, 'f', 'Se han identificado las ventajas para clientes y empresas.'),
(408, 141, 'a', 'Se han identificado los diferentes niveles de la cloud o nube.'),
(409, 141, 'b', 'Se han identificado las principales funciones de la cloud o nube (procesamiento de datos, intercambio de información, ejecución de aplicaciones, entre otros).'),
(410, 141, 'c', 'Se ha descrito el concepto de edge computing y su relación con la cloud o nube.'),
(411, 141, 'd', 'Se han definido los conceptos de fog y mist y sus zonas de aplicación en el conjunto.'),
(412, 141, 'e', 'Se han identificado las ventajas que proporciona la utilización de la cloud o nube en los sistemas conectados.'),
(413, 142, 'a', 'Se han identificado las tecnologías habilitadoras (THD) actuales que definen un sistema digitalizado.'),
(414, 142, 'b', 'Se han descrito las características y aplicaciones del loT, IA (Inteligencia Artificial), Big Data, tecnología 5G, la robótica colaborativa, Blockchain, Ciberseguridad, fabricación aditiva, realidad virtual, gemelos digitales, entre otras.'),
(415, 142, 'c', 'Se ha descrito la contribución de las THD a la mejora de la productividad y la eficiencia de los sistemas productivos o de prestación de servicios.'),
(416, 142, 'd', 'Se ha relacionado la alineación entre las unidades funcionales de las empresas que conforman el sistema y el objetivo del mismo.'),
(417, 142, 'e', 'Se ha relacionado la implantación de las tecnologías habilitadoras (sensórica, tratamiento de datos, automatización y comunicaciones, entre otras) con la reducción de costes y la mejora de la competitividad.'),
(418, 142, 'f', 'Se han relacionado las tecnologías disruptivas con aplicaciones concretas en los sectores productivos.'),
(419, 142, 'g', 'Se han definido los sistemas de almacenamiento de datos no convencionales y el acceso a los mismos desde cada unidad.'),
(420, 142, 'h', 'Se han descrito las mejoras producidas en el sistema y en cada una de sus etapas.'),
(421, 143, 'a', 'Se ha definido a nivel de bloques el diagrama de funcionamiento de la empresa clásica.'),
(422, 143, 'b', 'Se han identificado las etapas susceptibles de ser digitalizadas.'),
(423, 143, 'c', 'Se han definido las tecnologías implicadas en cada una de las etapas.'),
(424, 143, 'd', 'Se ha establecido la conexión de las etapas digitalizadas con el resto del sistema.'),
(425, 143, 'e', 'Se ha elaborado un diagrama de bloques del sistema digitalizado.'),
(426, 143, 'f', 'Se ha elaborado un informe de viabilidad y de las mejoras introducidas.'),
(427, 143, 'g', 'Se ha analizado la mejora en la producción y gestión de residuos, entre otras.'),
(428, 143, 'h', 'Se ha elaborado un documento con la secuencia del plan de transformación y los recursos empleados.'),
(429, 144, 'a', 'Se ha descrito el concepto de sostenibilidad, estableciendo los marcos internacionales asociados al desarrollo sostenible.'),
(430, 144, 'b', 'Se han identificado los asuntos ambientales, sociales y de gobernanza que influyen en el desarrollo sostenible de las organizaciones empresariales.'),
(431, 144, 'c', 'Se han relacionado los Objetivos de Desarrollo Sostenible (ODS) con su importancia para la consecución de la Agenda 2030.'),
(432, 144, 'd', 'Se ha analizado la importancia de identificar los aspectos ASG más relevantes para los grupos de interés de las organizaciones.'),
(433, 144, 'e', 'Se han identificado los principales estándares de métricas para la evaluación del desempeño en sostenibilidad.'),
(434, 144, 'f', 'Se ha descrito la inversión socialmente responsable y el papel de los analistas, inversores e índices de sostenibilidad.'),
(435, 145, 'a', 'Se han identificado los principales retos ambientales y sociales.'),
(436, 145, 'b', 'Se han relacionado los retos ambientales y sociales con el desarrollo de la actividad económica.'),
(437, 145, 'c', 'Se ha analizado el efecto de los impactos ambientales y sociales sobre las personas y los sectores productivos.'),
(438, 145, 'd', 'Se han identificado las medidas y acciones encaminadas a minimizar los impactos ambientales y sociales.'),
(439, 145, 'e', 'Se ha analizado la importancia de establecer alianzas y trabajar de manera transversal para abordar los retos.'),
(440, 146, 'a', 'Se han identificado los ODS más relevantes para la actividad profesional que realiza.'),
(441, 146, 'b', 'Se han analizado los riesgos y oportunidades que representan los ODS.'),
(442, 146, 'c', 'Se han identificado las acciones necesarias para atender los retos ambientales y sociales desde el entorno profesional.'),
(443, 147, 'a', 'Se ha caracterizado el modelo de producción y consumo actual.'),
(444, 147, 'b', 'Se han identificado los principios de la economía verde y circular.'),
(445, 147, 'c', 'Se han contrastado los beneficios de la economía circular frente al modelo clásico de producción.'),
(446, 147, 'd', 'Se han aplicado principios de ecodiseño.'),
(447, 147, 'e', 'Se ha analizado el ciclo de vida del producto.'),
(448, 147, 'f', 'Se han identificado los procesos de producción y los criterios de sostenibilidad aplicados.'),
(449, 148, 'a', 'Se ha caracterizado el modelo de producción y consumo actual.'),
(450, 148, 'b', 'Se han identificado los principios de la economía verde y circular.'),
(451, 148, 'c', 'Se han contrastado los beneficios de la economía verde y circular frente al modelo clásico.'),
(452, 148, 'd', 'Se ha evaluado el impacto de las actividades personales y profesionales.'),
(453, 148, 'e', 'Se han aplicado principios de ecodiseño.'),
(454, 148, 'f', 'Se han aplicado estrategias sostenibles.'),
(455, 148, 'g', 'Se ha analizado el ciclo de vida del producto.'),
(456, 148, 'h', 'Se han identificado los criterios de sostenibilidad aplicados.'),
(457, 148, 'i', 'Se ha aplicado la normativa ambiental.'),
(458, 149, 'a', 'Se han identificado los principales grupos de interés de la empresa.'),
(459, 149, 'b', 'Se han analizado los aspectos ASG materiales y las expectativas de los grupos de interés.'),
(460, 149, 'c', 'Se han definido acciones encaminadas a minimizar los impactos negativos y aprovechar oportunidades.'),
(461, 149, 'd', 'Se han determinado las métricas de evaluación del desempeño de acuerdo con estándares internacionales.'),
(462, 149, 'e', 'Se ha elaborado un informe de sostenibilidad con el plan y los indicadores propuestos.'),
(463, 150, 'a', 'Se ha situado el videojuego en el contexto de la creación audiovisual, reconociendo su importancia y potencial.'),
(464, 150, 'b', 'Se han conocido las características del mercado de videojuegos.'),
(465, 150, 'c', 'Se han reconocido los principales elementos de desarrollo de videojuegos y el state of the art de las tecnologías implicadas.'),
(466, 150, 'd', 'Se han valorado las principales tendencias en el mundo de los videojuegos en relación con las tecnologías emergentes.'),
(467, 151, 'a', 'Se ha instalado y configurado el motor de desarrollo de videojuegos.'),
(468, 151, 'b', 'Se han identificado y conectado todos los tipos de recursos disponibles y necesarios para la elaboración del videojuego.'),
(469, 151, 'c', 'Se ha definido la estructura de un proyecto de videojuego.'),
(470, 151, 'd', 'Se han creado diferentes objetos del videojuego y componentes.'),
(471, 151, 'e', 'Se han configurado las interacciones entre los diferentes elementos y los conceptos básicos de iluminación.'),
(472, 151, 'f', 'Se han analizado y creado las diferentes interacciones del usuario con el videojuego.'),
(473, 152, 'a', 'Se han manejado conceptos esenciales del lenguaje de programación, utilizado en el motor de desarrollo de videojuegos.'),
(474, 152, 'b', 'Se han analizado los diferentes elementos que intervienen en la mecánica del videojuego.'),
(475, 152, 'c', 'Se han creado y usado scripts para la programación de los objetos del videojuego.'),
(476, 152, 'd', 'Se han creado funciones de eventos que ocurren durante el juego.'),
(477, 152, 'e', 'Se han administrado el tiempo de los eventos y acciones y el orden de ejecución.'),
(478, 152, 'f', 'Se han verificado las herramientas de ayuda a la programación de scripts que permiten la depuración y testeo.'),
(479, 152, 'g', 'Se ha supervisado el sistema de eventos para comunicación entre los objetos de la aplicación.'),
(480, 153, 'a', 'Se ha determinado el orden de visualización de todos los objetos que contiene el juego.'),
(481, 153, 'b', 'Se han ajustado los modos de renderizado de los objetos en la pantalla o contenedor del Juego.'),
(482, 153, 'c', 'Se han posicionado y establecido los tamaños y rotaciones de los elementos de la interfaz.'),
(483, 153, 'd', 'Se ha proporcionado a los elementos del interfaz la interacción asociada a las acciones del videojuego.'),
(484, 153, 'e', 'Se han configurado las animaciones del interfaz de usuario.'),
(485, 154, 'a', 'Se ha realizado el estudio de compatibilidad del sistema informático.'),
(486, 154, 'b', 'Se han diferenciado los modos de instalación.'),
(487, 154, 'c', 'Se ha planificado y realizado el particionado del disco del servidor.'),
(488, 154, 'd', 'Se han seleccionado y aplicado los sistemas de archivos.'),
(489, 154, 'e', 'Se han seleccionado los componentes a instalar.'),
(490, 154, 'f', 'Se han aplicado procedimientos para la automatización de instalaciones.'),
(491, 154, 'g', 'Se han aplicado preferencias en la configuración del entorno personal.'),
(492, 154, 'h', 'Se ha actualizado el sistema operativo en red.'),
(493, 154, 'i', 'Se ha comprobado la conectividad del servidor con los equipos cliente.'),
(494, 155, 'a', 'Se han configurado y gestionado cuentas de usuario.'),
(495, 155, 'b', 'Se han configurado y gestionado perfiles de usuario.'),
(496, 155, 'c', 'Se han configurado y gestionado cuentas de equipo.'),
(497, 155, 'd', 'Se ha distinguido el propósito de los grupos, sus tipos y ámbitos.'),
(498, 155, 'e', 'Se han configurado y gestionado grupos.'),
(499, 155, 'f', 'Se ha gestionado la pertenencia de usuarios a grupos.'),
(500, 155, 'g', 'Se han identificado las características de usuarios y grupos predeterminados y especiales.'),
(501, 155, 'h', 'Se han planificado perfiles móviles de usuarios.'),
(502, 155, 'i', 'Se han utilizado herramientas para la administración de usuarios y grupos, incluidas en el sistema operativo en red.'),
(503, 156, 'a', 'Se ha identificado la función del servicio de directorio, sus elementos y nomenclatura.'),
(504, 156, 'b', 'Se ha reconocido el concepto de dominio y sus funciones.'),
(505, 156, 'c', 'Se han establecido relaciones de confianza entre dominios.'),
(506, 156, 'd', 'Se ha realizado la instalación del servicio de directorio.'),
(507, 156, 'e', 'Se ha realizado la configuración básica del servicio de directorio.'),
(508, 156, 'f', 'Se han utilizado agrupaciones de elementos para la creación de modelos administrativos.'),
(509, 156, 'g', 'Se ha analizado la estructura del servicio de directorio.'),
(510, 156, 'h', 'Se han utilizado herramientas de administración de dominios.');
INSERT INTO `criterios_evaluacion` (`id_ce`, `id_ra`, `codigo`, `descripcion`) VALUES
(511, 157, 'a', 'Se ha reconocido la diferencia entre permiso y derecho.'),
(512, 157, 'b', 'Se han identificado los recursos del sistema que se van a compartir y en qué condiciones.'),
(513, 157, 'c', 'Se han asignado permisos a los recursos del sistema que se van a compartir.'),
(514, 157, 'd', 'Se han compartido impresoras en red.'),
(515, 157, 'e', 'Se ha utilizado el entorno gráfico para compartir recursos.'),
(516, 157, 'f', 'Se han establecido niveles de seguridad para controlar el acceso del cliente a los recursos compartidos en red.'),
(517, 157, 'g', 'Se ha trabajado en grupo para comprobar el acceso a los recursos compartidos del sistema.'),
(518, 158, 'a', 'Se han descrito las características de los programas de monitorización.'),
(519, 158, 'b', 'Se han identificado problemas de rendimiento en los dispositivos de almacenamiento.'),
(520, 158, 'c', 'Se ha observado la actividad del sistema operativo en red a partir de las trazas generadas por el propio sistema.'),
(521, 158, 'd', 'Se han realizado tareas de mantenimiento del software instalado en el sistema.'),
(522, 158, 'e', 'Se han ejecutado operaciones para la automatización de tareas del sistema.'),
(523, 158, 'f', 'Se ha interpretado la información de configuración del sistema operativo en red.'),
(524, 159, 'a', 'Se ha identificado la necesidad de compartir recursos en red entre diferentes sistemas operativos.'),
(525, 159, 'b', 'Se ha comprobado la conectividad de la red en un escenario heterogéneo.'),
(526, 159, 'c', 'Se ha descrito la funcionalidad de los servicios que permiten compartir recursos en red.'),
(527, 159, 'd', 'Se han instalado y configurado servicios para compartir recursos en red.'),
(528, 159, 'e', 'Se ha accedido a sistemas de archivos en red desde equipos con diferentes sistemas operativos.'),
(529, 159, 'f', 'Se ha accedido a impresoras desde equipos con diferentes sistemas operativos.'),
(530, 159, 'g', 'Se ha trabajado en grupo.'),
(531, 159, 'h', 'Se han establecido niveles de seguridad para controlar el acceso del usuario a los recursos compartidos en red.'),
(532, 159, 'i', 'Se ha comprobado el funcionamiento de los servicios instalados.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id_curso` int(10) UNSIGNED NOT NULL,
  `curso` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id_curso`, `curso`) VALUES
(1, '1º'),
(2, '2º');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos_escolares`
--

CREATE TABLE `cursos_escolares` (
  `id_curso_escolar` int(10) UNSIGNED NOT NULL,
  `curso_escolar` varchar(60) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cursos_escolares`
--

INSERT INTO `cursos_escolares` (`id_curso_escolar`, `curso_escolar`, `activo`) VALUES
(1, '2025/2026', 1),
(2, '2026/2027', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direcciones`
--

CREATE TABLE `direcciones` (
  `id_direccion` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED DEFAULT NULL,
  `id_pais` int(10) UNSIGNED DEFAULT NULL,
  `id_provincia` int(10) UNSIGNED DEFAULT NULL,
  `id_localidad` int(10) UNSIGNED DEFAULT NULL,
  `id_via` int(10) UNSIGNED DEFAULT NULL,
  `nombre_via` varchar(60) DEFAULT NULL,
  `numero` varchar(60) DEFAULT NULL,
  `bloque` varchar(60) DEFAULT NULL,
  `escalera` varchar(60) DEFAULT NULL,
  `planta` varchar(60) DEFAULT NULL,
  `puerta` varchar(60) DEFAULT NULL,
  `otros` text DEFAULT NULL,
  `etiqueta` varchar(60) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `cif` varchar(20) DEFAULT NULL,
  `nombre` varchar(60) NOT NULL,
  `apellido1` varchar(60) DEFAULT NULL,
  `apellido2` varchar(60) DEFAULT NULL,
  `convenio` smallint(5) UNSIGNED DEFAULT NULL,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas_contactos`
--

CREATE TABLE `empresas_contactos` (
  `id_empresa_contacto` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `apellido1` varchar(60) NOT NULL,
  `apellido2` varchar(60) DEFAULT NULL,
  `nombre` varchar(60) NOT NULL,
  `cargo` varchar(60) DEFAULT NULL,
  `comentarios` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas_tutores`
--

CREATE TABLE `empresas_tutores` (
  `id_empresas_tutor` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `apellido1` varchar(60) NOT NULL,
  `apellido2` varchar(60) DEFAULT NULL,
  `nombre` varchar(60) NOT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `comentarios` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id_grupo` int(10) UNSIGNED NOT NULL,
  `id_nivel` int(10) UNSIGNED DEFAULT NULL,
  `id_ciclo` int(10) UNSIGNED DEFAULT NULL,
  `id_curso` int(10) UNSIGNED DEFAULT NULL,
  `grupo` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id_grupo`, `id_nivel`, `id_ciclo`, `id_curso`, `grupo`) VALUES
(1, 1, 1, 1, 'SMR1A'),
(2, 1, 1, 1, 'SMR1B'),
(3, 1, 1, 2, 'SMR2A'),
(4, 1, 1, 2, 'SMR2B'),
(5, 2, 2, 1, 'ASIR1'),
(6, 2, 2, 2, 'ASIR2'),
(7, 2, 3, 1, 'DAM1'),
(8, 2, 3, 2, 'DAM2'),
(9, 2, 4, 1, 'DAW1'),
(10, 2, 4, 2, 'DAW2');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos_tutores`
--

CREATE TABLE `grupos_tutores` (
  `id_grupo_tutor` int(10) UNSIGNED NOT NULL,
  `id_grupo` int(10) UNSIGNED NOT NULL,
  `id_profesor` int(10) UNSIGNED NOT NULL,
  `id_curso_escolar` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `localidades`
--

CREATE TABLE `localidades` (
  `id_localidad` int(10) UNSIGNED NOT NULL,
  `id_provincia` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `localidades`
--

INSERT INTO `localidades` (`id_localidad`, `id_provincia`, `nombre`) VALUES
(2, 8, 'Barcelona'),
(1, 28, 'Getafe'),
(4, 28, 'Madrid'),
(3, 45, 'Talavera De La Reina');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `meses`
--

CREATE TABLE `meses` (
  `id_mes` int(10) UNSIGNED NOT NULL,
  `mes` varchar(60) NOT NULL,
  `orden` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `meses`
--

INSERT INTO `meses` (`id_mes`, `mes`, `orden`) VALUES
(1, 'Enero', 5),
(2, 'Febrero', 6),
(3, 'Marzo', 7),
(4, 'Abril', 8),
(5, 'Mayo', 9),
(6, 'Junio', 10),
(7, 'Julio', 11),
(8, 'Agosto', 12),
(9, 'Septiembre', 1),
(10, 'Octubre', 2),
(11, 'Noviembre', 3),
(12, 'Diciembre', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos`
--

CREATE TABLE `modulos` (
  `id_modulo` int(10) UNSIGNED NOT NULL,
  `id_nivel` int(10) UNSIGNED DEFAULT NULL,
  `id_ciclo` int(10) UNSIGNED DEFAULT NULL,
  `id_curso` int(10) UNSIGNED DEFAULT NULL,
  `codigo` varchar(8) NOT NULL,
  `abreviatura` varchar(8) NOT NULL,
  `tipo` varchar(60) NOT NULL,
  `materia_general` varchar(60) NOT NULL,
  `materia_propia` varchar(60) NOT NULL,
  `horas_semanales` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `horas_totales` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modulos`
--

INSERT INTO `modulos` (`id_modulo`, `id_nivel`, `id_ciclo`, `id_curso`, `codigo`, `abreviatura`, `tipo`, `materia_general`, `materia_propia`, `horas_semanales`, `horas_totales`) VALUES
(1, 1, 1, 1, '0223', 'AO', 'Comunes', 'Aplicaciones ofimáticas', '', 7, 235),
(2, 1, 1, 1, '0221', 'MME', 'Comunes', 'Montaje y mantenimiento de equipos', '', 6, 200),
(3, 1, 1, 1, '0225', 'RL', 'Comunes', 'Redes locales', '', 7, 240),
(4, 1, 1, 1, '0222', 'SOM', 'Comunes', 'Sistemas operativos monopuesto', '', 5, 175),
(5, 1, 1, 1, 'CMO-313', 'FP', 'Optativas', 'Módulo profesional optativo', 'Fundamentos de Programación', 2, 50),
(6, 1, 1, 1, '1709', 'IPE1', 'Comunes', 'Itinerario personal para la empleabilidad I', '', 3, 100),
(7, 1, 1, 1, '---', 'FFE1', 'FFE', 'FFE1', '', 0, 130),
(8, 1, 1, 2, '0228', 'AW', 'Comunes', 'Aplicaciones web', '', 4, 135),
(9, 1, 1, 2, '0226', 'SI', 'Comunes', 'Seguridad informática', '', 3, 85),
(10, 1, 1, 2, '0227', 'SR', 'Comunes', 'Servicios en red', '', 7, 230),
(11, 1, 1, 2, '0224', 'SOR', 'Comunes', 'Sistemas operativos en red', '', 7, 230),
(12, 1, 1, 2, '0156', 'ING', 'Comunes', 'Inglés profesional (GM)', '', 2, 50),
(13, 1, 1, 2, '1710', 'IPE2', 'Comunes', 'Itinerario personal para la empleabilidad II', '', 2, 70),
(14, 1, 1, 2, '1664', 'DASP', 'Comunes', 'Digitalización aplicada a los sectores productivos (GM)', '', 1, 30),
(15, 1, 1, 2, '1708', 'SASP', 'Comunes', 'Sostenibilidad aplicada al sistema productivo', '', 1, 30),
(16, 1, 1, 2, 'CMO-318', 'PV', 'Optativas', 'Módulo profesional optativo II', 'Programación de videojuegos', 3, 90),
(17, 1, 1, 2, '1713', 'PRY', 'Proyectos', 'Proyecto intermodular', '', 0, 50),
(18, 1, 1, 2, '---', 'FFE2', 'FFE', 'FFE2', '', 0, 370),
(19, 2, 2, 1, '0371', 'FH', 'Comunes', 'Fundamentos de hardware', '', 3, 105),
(20, 2, 2, 1, '0372', 'GBD', 'Comunes', 'Gestión de bases de datos', '', 6, 200),
(21, 2, 2, 1, '0369', 'ISO', 'Comunes', 'Implantación de sistemas operativos', '', 7, 245),
(22, 2, 2, 1, '0373', 'LMSGI', 'Comunes', 'Lenguajes de marcas y sistemas de gestión de información', '', 3, 110),
(23, 2, 2, 1, '0370', 'PAR', 'Comunes', 'Planificación y administración de redes', '', 6, 190),
(24, 2, 2, 1, 'CMO-1', 'OPT1', 'Optativas', 'Módulo profesional optativo', '', 2, 50),
(25, 2, 2, 1, '1709', 'IPE1', 'Comunes', 'Itinerario personal para la empleabilidad I', '', 3, 100),
(26, 2, 2, 1, '---', 'FFE1', 'FFE', 'FFE1', '', 0, 130),
(27, 2, 2, 2, '0377', 'ASGBD', 'Comunes', 'Administración de sistemas gestores de bases de datos', '', 3, 110),
(28, 2, 2, 2, '0374', 'ASO', 'Comunes', 'Administración de sistemas operativos', '', 5, 150),
(29, 2, 2, 2, '0376', 'IAW', 'Comunes', 'Implantación de aplicaciones web', '', 3, 100),
(30, 2, 2, 2, '0378', 'SAD', 'Comunes', 'Seguridad y alta disponibilidad', '', 5, 160),
(31, 2, 2, 2, '0375', 'SRI', 'Comunes', 'Servicios de red e internet', '', 5, 160),
(32, 2, 2, 2, '0179', 'ING', 'Comunes', 'Inglés profesional (GS)', '', 2, 50),
(33, 2, 2, 2, '1710', 'IPE2', 'Comunes', 'Itinerario personal para la empleabilidad II', '', 2, 70),
(34, 2, 2, 2, '1665', 'DASP', 'Comunes', 'Digitalización aplicada a los sectores productivos (GS)', '', 1, 30),
(35, 2, 2, 2, '1708', 'SASP', 'Comunes', 'Sostenibilidad aplicada al sistema productivo', '', 1, 30),
(36, 2, 2, 2, 'CMO-2', 'OPT2', 'Optativas', 'Módulo profesional optativo II', '', 3, 90),
(37, 2, 2, 2, '0379', 'PRY', 'Proyectos', 'Proyecto intermodular de administración de sistemas informát', '', 0, 50),
(38, 2, 2, 2, '---', 'FFE2', 'FFE', 'FFE2', '', 0, 370),
(39, 2, 3, 1, '0484', 'BD', 'Comunes', 'Bases de datos', '', 6, 205),
(40, 2, 3, 1, '0487', 'ED', 'Comunes', 'Entornos de desarrollo', '', 2, 60),
(41, 2, 3, 1, '0373', 'LMSGI', 'Comunes', 'Lenguajes de marcas y sistemas de gestión de información', '', 3, 110),
(42, 2, 3, 1, '0485', 'PROG', 'Comunes', 'Programación', '', 8, 270),
(43, 2, 3, 1, '0483', 'SI', 'Comunes', 'Sistemas informáticos', '', 6, 205),
(44, 2, 3, 1, 'CMO-1', 'OPT1', 'Optativas', 'Módulo profesional optativo', '', 2, 50),
(45, 2, 3, 1, '1709', 'IPE1', 'Comunes', 'Itinerario personal para la empleabilidad I', '', 3, 100),
(46, 2, 3, 1, '---', 'FFE1', 'FFE', 'FFE1', '', 0, 130),
(47, 2, 3, 2, '0486', 'AD', 'Comunes', 'Acceso a datos', '', 5, 165),
(48, 2, 3, 2, '0488', 'DI', 'Comunes', 'Desarrollo de interfaces', '', 5, 150),
(49, 2, 3, 2, '0490', 'PSP', 'Comunes', 'Programación de servicios y procesos', '', 4, 135),
(50, 2, 3, 2, '0489', 'PMDM', 'Comunes', 'Programación multimedia y dispositivos móviles', '', 4, 135),
(51, 2, 3, 2, '0491', 'SGE', 'Comunes', 'Sistemas de gestión empresarial', '', 3, 95),
(52, 2, 3, 2, '0179', 'ING', 'Comunes', 'Inglés profesional (GS)', '', 2, 50),
(53, 2, 3, 2, '1710', 'IPE2', 'Comunes', 'Itinerario personal para la empleabilidad II', '', 2, 70),
(54, 2, 3, 2, '1665', 'DASP', 'Comunes', 'Digitalización aplicada a los sectores productivos (GS)', '', 1, 30),
(55, 2, 3, 2, '1708', 'SASP', 'Comunes', 'Sostenibilidad aplicada al sistema productivo', '', 1, 30),
(56, 2, 3, 2, 'CMO-2', 'OPT2', 'Optativas', 'Módulo profesional optativo II', '', 3, 90),
(57, 2, 3, 2, '0492', 'PRY', 'Proyectos', 'Proyecto intermodular de desarrollo de aplicaciones multipla', '', 0, 50),
(58, 2, 3, 2, '---', 'FFE2', 'FFE', 'FFE2', '', 0, 370),
(59, 2, 4, 1, '0484', 'BD', 'Comunes', 'Bases de datos', '', 6, 205),
(60, 2, 4, 1, '0487', 'ED', 'Comunes', 'Entornos de desarrollo', '', 2, 60),
(61, 2, 4, 1, '0373', 'LMSGI', 'Comunes', 'Lenguajes de marcas y sistemas de gestión de información', '', 3, 110),
(62, 2, 4, 1, '0485', 'PROG', 'Comunes', 'Programación', '', 8, 270),
(63, 2, 4, 1, '0483', 'SI', 'Comunes', 'Sistemas informáticos', '', 6, 205),
(64, 2, 3, 1, 'CMO-311', 'ROB', 'Optativas', 'Módulo profesional optativo', 'Informática aplicada a sistemas electrónicos (Robótica)', 2, 50),
(65, 2, 4, 1, '1709', 'IPE1', 'Comunes', 'Itinerario personal para la empleabilidad I', '', 3, 100),
(66, 2, 4, 1, '---', 'FFE1', 'FFE', 'FFE1', '', 0, 130),
(67, 2, 4, 2, '0612', 'DWEC', 'Comunes', 'Desarrollo web en entorno cliente', '', 6, 205),
(68, 2, 4, 2, '0613', 'DWES', 'Comunes', 'Desarrollo web en entorno servidor', '', 8, 265),
(69, 2, 4, 2, '0614', 'DAW', 'Comunes', 'Despliegue de aplicaciones web', '', 3, 90),
(70, 2, 4, 2, '0615', 'DIW', 'Comunes', 'Diseño de interfaces web', '', 4, 120),
(71, 2, 4, 2, '0179', 'ING', 'Comunes', 'Inglés profesional (GS)', '', 2, 50),
(72, 2, 4, 2, '1710', 'IPE2', 'Comunes', 'Itinerario personal para la empleabilidad II', '', 2, 70),
(73, 2, 4, 2, '1665', 'DASP', 'Comunes', 'Digitalización aplicada a los sectores productivos (GS)', '', 1, 30),
(74, 2, 4, 2, '1708', 'SASP', 'Comunes', 'Sostenibilidad aplicada al sistema productivo', '', 1, 30),
(75, 2, 4, 2, 'CMO-2', 'OPT2', 'Optativas', 'Módulo profesional optativo II', '', 3, 90),
(76, 2, 4, 2, '0616', 'PRY', 'Proyectos', 'Proyecto intermodular de desarrollo de aplicaciones web', '', 0, 50),
(77, 2, 4, 2, '---', 'FFE2', 'FFE', 'FFE2', '', 0, 370);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos_profesores`
--

CREATE TABLE `modulos_profesores` (
  `id_modulo_profesor` int(10) UNSIGNED NOT NULL,
  `id_modulo` int(10) UNSIGNED NOT NULL,
  `id_profesor` int(10) UNSIGNED NOT NULL,
  `id_curso_escolar` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `niveles`
--

CREATE TABLE `niveles` (
  `id_nivel` int(10) UNSIGNED NOT NULL,
  `nivel` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `niveles`
--

INSERT INTO `niveles` (`id_nivel`, `nivel`) VALUES
(1, 'F.P. Grado Medio'),
(2, 'F.P. Grado Superior');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `no_lectivos`
--

CREATE TABLE `no_lectivos` (
  `id` int(10) UNSIGNED NOT NULL,
  `fecha` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `no_lectivos`
--

INSERT INTO `no_lectivos` (`id`, `fecha`) VALUES
(1, '2025-10-13'),
(2, '2025-11-03'),
(3, '2025-12-08'),
(4, '2025-12-22'),
(5, '2025-12-23'),
(6, '2025-12-24'),
(7, '2025-12-25'),
(8, '2025-12-26'),
(9, '2025-12-29'),
(10, '2025-12-30'),
(11, '2025-12-31'),
(12, '2026-01-01'),
(13, '2026-01-02'),
(14, '2026-01-07'),
(15, '2026-01-06'),
(16, '2026-01-05'),
(17, '2026-03-27'),
(18, '2026-03-30'),
(19, '2026-03-31'),
(20, '2026-04-01'),
(21, '2026-04-02'),
(22, '2026-04-03'),
(23, '2026-04-06'),
(24, '2026-05-01'),
(25, '2026-05-14'),
(26, '2026-05-25'),
(27, '2026-06-19'),
(28, '2026-06-22'),
(29, '2026-06-29'),
(30, '2026-06-30'),
(31, '2026-06-23'),
(32, '2026-06-24'),
(33, '2026-06-25'),
(34, '2026-06-26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paises`
--

CREATE TABLE paises (
  id_pais INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo_iso CHAR(2) NOT NULL,
  nombre VARCHAR(60) NOT NULL,
  PRIMARY KEY (id_pais),
  UNIQUE KEY uk_paises_codigo_iso (codigo_iso),
  KEY idx_paises_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Volcado de datos para la tabla `paises`
--

INSERT INTO paises (codigo_iso, nombre) VALUES
('ES','España'),
('AD','Andorra'),
('AR','Argentina'),
('AS','Samoa Americana'),
('AT','Austria'),
('AU','Australia'),
('BD','Bangladés'),
('BE','Bélgica'),
('BG','Bulgaria'),
('BR','Brasil'),
('CA','Canadá'),
('CH','Suiza'),
('CZ','República Checa'),
('DE','Alemania'),
('DK','Dinamarca'),
('DO','República Dominicana'),
('FI','Finlandia'),
('FO','Islas Feroe'),
('FR','Francia'),
('GB','Reino Unido'),
('GF','Guayana Francesa'),
('GG','Guernsey'),
('GL','Groenlandia'),
('GP','Guadalupe'),
('GT','Guatemala'),
('GU','Guam'),
('GY','Guyana'),
('HR','Croacia'),
('HU','Hungría'),
('IM','Isla de Man'),
('IN','India'),
('IS','Islandia'),
('IT','Italia'),
('JE','Jersey'),
('JP','Japón'),
('LI','Liechtenstein'),
('LK','Sri Lanka'),
('LT','Lituania'),
('LU','Luxemburgo'),
('MC','Mónaco'),
('MD','Moldavia'),
('MH','Islas Marshall'),
('MK','Macedonia del Norte'),
('MP','Islas Marianas del Norte'),
('MQ','Martinica'),
('MX','México'),
('MY','Malasia'),
('NL','Países Bajos'),
('NO','Noruega'),
('NZ','Nueva Zelanda'),
('PH','Filipinas'),
('PK','Pakistán'),
('PL','Polonia'),
('PM','San Pedro y Miquelón'),
('PR','Puerto Rico'),
('PT','Portugal'),
('RE','Reunión'),
('RU','Rusia'),
('SE','Suecia'),
('SI','Eslovenia'),
('SJ','Svalbard y Jan Mayen'),
('SK','Eslovaquia'),
('SM','San Marino'),
('TH','Tailandia'),
('TR','Turquía'),
('US','Estados Unidos'),
('VA','Vaticano'),
('VI','Islas Vírgenes de EE.UU.'),
('YT','Mayotte'),
('ZA','Sudáfrica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practicas`
--

CREATE TABLE `practicas` (
  `id_practica` int(10) UNSIGNED NOT NULL,
  `id_alumno` int(10) UNSIGNED NOT NULL,
  `id_empresa` int(10) UNSIGNED NOT NULL,
  `id_direccion` int(10) UNSIGNED DEFAULT NULL,
  `id_empresa_tutor` int(10) UNSIGNED DEFAULT NULL,
  `anexo` smallint(5) UNSIGNED DEFAULT NULL,
  `id_practicas_estado` int(10) UNSIGNED DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `dias_extra` tinyint(2) UNSIGNED NOT NULL DEFAULT 0,
  `fecha_fin_real` date DEFAULT NULL,
  `horas` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `circ_excep` tinyint(1) NOT NULL DEFAULT 0,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practicas_anexos`
--

CREATE TABLE `practicas_anexos` (
  `id_practicas_anexo` int(10) UNSIGNED NOT NULL,
  `anexo` varchar(60) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `resumen` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `practicas_anexos`
--

INSERT INTO `practicas_anexos` (`id_practicas_anexo`, `anexo`, `descripcion`, `resumen`) VALUES
(1, 'anexo 2.1', 'Relación de alumnos', NULL),
(2, 'anexo 2.2', 'Comunicación a la DAT de la relación de alumnos', NULL),
(3, 'anexo 3', 'Plan de formación', NULL),
(4, 'anexo 4', 'Autorización por circunstancias excepcionales', NULL),
(5, 'anexo 7', 'Ficha de seguimiento periódico', NULL),
(6, 'anexo 8', 'Informe de valoración final del tutor', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practicas_anexos_estados`
--

CREATE TABLE `practicas_anexos_estados` (
  `id_practicas_anexo_estado` int(10) UNSIGNED NOT NULL,
  `estado` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `practicas_anexos_estados`
--

INSERT INTO `practicas_anexos_estados` (`id_practicas_anexo_estado`, `estado`) VALUES
(1, 'Sin iniciar'),
(2, 'Datos solicitados'),
(3, 'Enviado a firmar por la empresa'),
(4, 'Firmado por la empresa'),
(5, 'Enviado a firmar por el director'),
(6, 'Firmado por el director'),
(7, 'Completado'),
(8, 'Enviado al alumno'),
(9, 'Devuelto a la empresa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practicas_estados`
--

CREATE TABLE `practicas_estados` (
  `id_practicas_estado` int(10) UNSIGNED NOT NULL,
  `estado` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `practicas_estados`
--

INSERT INTO `practicas_estados` (`id_practicas_estado`, `estado`) VALUES
(1, 'En espera'),
(2, 'En curso'),
(3, 'Finalizada'),
(4, 'Cancelada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practicas_horario`
--

CREATE TABLE `practicas_horario` (
  `id_practicas_horario` int(10) UNSIGNED NOT NULL,
  `id_practica` int(10) UNSIGNED NOT NULL,
  `dia_semana` tinyint(1) NOT NULL DEFAULT 0,
  `hora_entrada` time NOT NULL,
  `hora_salida` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practicas_pasos`
--

CREATE TABLE `practicas_pasos` (
  `id_practicas_paso` int(10) UNSIGNED NOT NULL,
  `paso` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practicas_ras`
--

CREATE TABLE `practicas_ras` (
  `id_practica_ra` int(10) UNSIGNED NOT NULL,
  `curso_escolar` varchar(20) DEFAULT NULL,
  `ciclo` varchar(20) DEFAULT NULL,
  `id_curso_escolar` int(10) UNSIGNED NOT NULL,
  `id_ciclo` int(10) UNSIGNED NOT NULL,
  `id_modulo` int(10) UNSIGNED DEFAULT NULL,
  `id_ra` int(10) UNSIGNED NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `practicas_ras`
--

INSERT INTO `practicas_ras` (`id_practica_ra`, `curso_escolar`, `ciclo`, `id_curso_escolar`, `id_ciclo`, `id_modulo`, `id_ra`, `porcentaje`) VALUES
(1, '1', 'SMR', 1, 1, 8, 137, 10.00),
(2, '1', 'SMR', 1, 1, 14, 139, 10.00),
(3, '1', 'SMR', 1, 1, 15, 149, 10.00),
(4, '1', 'SMR', 1, 1, 9, 121, 10.00),
(5, '1', 'SMR', 1, 1, 9, 122, 10.00),
(6, '1', 'SMR', 1, 1, 9, 123, 10.00),
(7, '1', 'SMR', 1, 1, 9, 124, 10.00),
(8, '1', 'SMR', 1, 1, 11, 154, 10.00),
(9, '1', 'SMR', 1, 1, 11, 155, 10.00),
(10, '1', 'SMR', 1, 1, 11, 156, 10.00),
(11, '1', 'SMR', 1, 1, 11, 157, 10.00),
(12, '1', 'SMR', 1, 1, 11, 159, 10.00),
(13, '1', 'SMR', 1, 1, 10, 131, 10.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `id_profesor` int(10) UNSIGNED NOT NULL,
  `apellido1` varchar(60) NOT NULL,
  `apellido2` varchar(60) DEFAULT NULL,
  `nombre` varchar(60) NOT NULL,
  `dni` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `provincias`
--

CREATE TABLE `provincias` (
  `id_provincia` int(10) UNSIGNED NOT NULL,
  `id_pais` int(10) UNSIGNED DEFAULT NULL,
  `nombre` varchar(60) NOT NULL,
  `cp_prefijo` tinyint(2) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `provincias`
--

INSERT INTO `provincias` (`id_provincia`, `id_pais`, `nombre`, `cp_prefijo`) VALUES
(1, 1, 'Álava', 1),
(2, 1, 'Albacete', 2),
(3, 1, 'Alicante', 3),
(4, 1, 'Almería', 4),
(5, 1, 'Ávila', 5),
(6, 1, 'Badajoz', 6),
(7, 1, 'Illes Balears', 7),
(8, 1, 'Barcelona', 8),
(9, 1, 'Burgos', 9),
(10, 1, 'Cáceres', 10),
(11, 1, 'Cádiz', 11),
(12, 1, 'Castellón', 12),
(13, 1, 'Ciudad Real', 13),
(14, 1, 'Córdoba', 14),
(15, 1, 'A Coruña', 15),
(16, 1, 'Cuenca', 16),
(17, 1, 'Girona', 17),
(18, 1, 'Granada', 18),
(19, 1, 'Guadalajara', 19),
(20, 1, 'Gipuzkoa', 20),
(21, 1, 'Huelva', 21),
(22, 1, 'Huesca', 22),
(23, 1, 'Jaén', 23),
(24, 1, 'León', 24),
(25, 1, 'Lleida', 25),
(26, 1, 'La Rioja', 26),
(27, 1, 'Lugo', 27),
(28, 1, 'Madrid', 28),
(29, 1, 'Málaga', 29),
(30, 1, 'Murcia', 30),
(31, 1, 'Navarra', 31),
(32, 1, 'Ourense', 32),
(33, 1, 'Asturias', 33),
(34, 1, 'Palencia', 34),
(35, 1, 'Las Palmas', 35),
(36, 1, 'Pontevedra', 36),
(37, 1, 'Salamanca', 37),
(38, 1, 'Santa Cruz de Tenerife', 38),
(39, 1, 'Cantabria', 39),
(40, 1, 'Segovia', 40),
(41, 1, 'Sevilla', 41),
(42, 1, 'Soria', 42),
(43, 1, 'Tarragona', 43),
(44, 1, 'Teruel', 44),
(45, 1, 'Toledo', 45),
(46, 1, 'Valencia', 46),
(47, 1, 'Valladolid', 47),
(48, 1, 'Bizkaia', 48),
(49, 1, 'Zamora', 49),
(50, 1, 'Zaragoza', 50),
(51, 1, 'Ceuta', 51),
(52, 1, 'Melilla', 52);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resultados_aprendizaje`
--

CREATE TABLE `resultados_aprendizaje` (
  `id_ra` int(10) UNSIGNED NOT NULL,
  `id_modulo` int(10) UNSIGNED NOT NULL,
  `numero` int(10) UNSIGNED NOT NULL,
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `resultados_aprendizaje`
--

INSERT INTO `resultados_aprendizaje` (`id_ra`, `id_modulo`, `numero`, `descripcion`) VALUES
(1, 2, 1, 'Selecciona los componentes de integración de un equipo microinformático estándar, describiendo sus funciones y comparando prestaciones de distintos fabricantes.'),
(2, 2, 2, 'Ensambla un equipo microinformático, interpretando planos e instrucciones del fabricante aplicando técnicas de montaje.'),
(3, 2, 3, 'Mide parámetros eléctricos, identificando el tipo de señal y relacionándola con sus unidades características.'),
(4, 2, 4, 'Mantiene equipos informáticos interpretando las recomendaciones de los fabricantes y relacionando las disfunciones con sus causas.'),
(5, 2, 5, 'Instala software en un equipo informático utilizando una imagen almacenada en un soporte de memoria y justificando el procedimiento a seguir.'),
(6, 2, 6, 'Reconoce nuevas tendencias en el ensamblaje de equipos microinformáticos describiendo sus ventajas y adaptándolas a las características de uso de los equipos.'),
(7, 2, 7, 'Mantiene periféricos, interpretando las recomendaciones de los fabricantes de equipos y relacionando disfunciones con sus causas.'),
(8, 2, 8, 'Cumple las normas de prevención de riesgos laborales y de protección ambiental, identificando los riesgos asociados, las medidas y equipos para prevenirlos.'),
(9, 4, 1, 'Reconoce las características de los sistemas de archivo, describiendo sus tipos y aplicaciones.'),
(10, 4, 2, 'Instala sistemas operativos, relacionando sus características con el hardware del equipo y el software de aplicación.'),
(11, 4, 3, 'Realiza tareas básicas de configuración de sistemas operativos, interpretando requerimientos y describiendo los procedimientos seguidos.'),
(12, 4, 4, 'Realiza operaciones básicas de administración de sistemas operativos, interpretando requerimientos y optimizando el sistema para su uso.'),
(13, 4, 5, 'Crea máquinas virtuales identificando su campo de aplicación e instalando software específico.'),
(101, 1, 1, 'Instala y actualiza aplicaciones ofimáticas, interpretando especificaciones y describiendo los pasos a seguir en el proceso.'),
(102, 1, 2, 'Elabora documentos y plantillas, describiendo y aplicando las opciones avanzadas de procesadores de textos.'),
(103, 1, 3, 'Elabora documentos y plantillas de cálculo, describiendo y aplicando opciones avanzadas de hojas de cálculo.'),
(104, 1, 4, 'Elabora documentos con bases de datos ofimáticas describiendo y aplicando operaciones de manipulación de datos.'),
(105, 1, 5, 'Manipula imágenes digitales analizando las posibilidades de distintos programas y aplicando técnicas de captura y edición básicas.'),
(106, 1, 6, 'Manipula secuencias de vídeo analizando las posibilidades de distintos programas y aplicando técnicas de captura y edición básicas.'),
(107, 1, 7, 'Elabora presentaciones multimedia describiendo y aplicando normas básicas de composición y diseño.'),
(108, 1, 8, 'Realiza operaciones de gestión del correo y la agenda electrónica, relacionando necesidades de uso con su configuración.'),
(109, 1, 9, 'Aplica técnicas de soporte en el uso de aplicaciones, identificando y resolviendo incidencias.'),
(110, 17, 1, 'Caracteriza las empresas del sector atendiendo a su organización y al tipo de producto o servicio que ofrecen.'),
(111, 17, 2, 'Plantea soluciones a las necesidades del sector teniendo en cuenta la viabilidad de las mismas, los costes asociados y elaborando un pequeño proyecto.'),
(112, 17, 3, 'Planifica la ejecución de las actividades propuestas a la solución planteada, determinando el plan de intervención y elaborando la documentación correspondiente.'),
(113, 17, 4, 'Realiza el seguimiento de la ejecución de las actividades planteadas, verificando que se cumple con la planificación.'),
(114, 17, 5, 'Transmite información con claridad, de manera ordenada y estructurada.'),
(115, 3, 1, 'Reconoce la estructura de redes locales cableadas analizando las características de entornos de aplicación y describiendo la funcionalidad de sus componentes.'),
(116, 3, 2, 'Despliega el cableado de una red local interpretando especificaciones y aplicando técnicas de montaje.'),
(117, 3, 3, 'Interconecta equipos en redes locales cableadas describiendo estándares de cableado y aplicando técnicas de montaje de conectores.'),
(118, 3, 4, 'Instala equipos en red, describiendo sus prestaciones y aplicando técnicas de montaje.'),
(119, 3, 5, 'Mantiene una red local interpretando recomendaciones de los fabricantes de hardware o software y estableciendo la relación entre disfunciones y sus causas.'),
(120, 3, 6, 'Cumple las normas de prevención de riesgos laborales y de protección ambiental, identificando los riesgos asociados, las medidas y equipos para prevenirlos.'),
(121, 9, 1, 'Aplica medidas de seguridad pasiva en sistemas informáticos describiendo características de entornos y relacionándolas con sus necesidades.'),
(122, 9, 2, 'Gestiona dispositivos de almacenamiento describiendo los procedimientos efectuados y aplicando técnicas para asegurar la integridad de la información.'),
(123, 9, 3, 'Aplica mecanismos de seguridad activa describiendo sus características y relacionándolas con las necesidades de uso del sistema informático.'),
(124, 9, 4, 'Asegura la privacidad de la información transmitida en redes informáticas describiendo vulnerabilidades e instalando software especifico.'),
(125, 9, 5, 'Reconoce la legislación y normativa sobre seguridad y protección de datos analizando las repercusiones de su incumplimiento.'),
(126, 10, 1, 'Instala servicios de configuración dinámica, describiendo sus características y aplicaciones.'),
(127, 10, 2, 'Instala servicios de resolución de nombres, describiendo sus características y aplicaciones.'),
(128, 10, 3, 'Instala servicios de transferencia de ficheros, describiendo sus características y aplicaciones.'),
(129, 10, 4, 'Gestiona servidores de correo electrónico identificando requerimientos de utilización y aplicando criterios de configuración.'),
(130, 10, 5, 'Gestiona servidores web identificando requerimientos de utilización y aplicando criterios de configuración.'),
(131, 10, 6, 'Gestiona métodos de acceso remoto describiendo sus características e instalando los servicios correspondientes.'),
(132, 10, 7, 'Despliega redes inalámbricas seguras justificando la configuración elegida y describiendo los procedimientos de implantación.'),
(133, 10, 8, 'Establece el acceso desde redes locales a redes públicas identificando posibles escenarios y aplicando software específico.'),
(134, 8, 1, 'Instala gestores de contenidos, identificando sus aplicaciones y configurándolos según requerimientos.'),
(135, 8, 2, 'Instala sistemas de gestión de aprendizaje a distancia, describiendo la estructura del sitio y la jerarquía de directorios generada.'),
(136, 8, 3, 'Instala servicios de gestión de archivos web, identificando sus aplicaciones y verificando su integridad.'),
(137, 8, 4, 'Instala aplicaciones de ofimática web, describiendo sus características y entornos de uso.'),
(138, 8, 5, 'Instala aplicaciones web de escritorio, describiendo sus características y entornos de uso.'),
(139, 14, 1, 'Establece las diferencias entre la Economía Lineal (EL) y la Economía Circular (EC), identificando las ventajas de la EC en relación con el medioambiente y el desarrollo sostenible.'),
(140, 14, 2, 'Caracteriza los principales aspectos de la 4.ª Revolución Industrial indicando los cambios y las ventajas que se producen tanto desde el punto de vista de los clientes como de las empresas.'),
(141, 14, 3, 'Identifica la estructura de los sistemas basados en cloud o nube describiendo su tipología y campo de aplicación.'),
(142, 14, 4, 'Compara los sistemas de producción y prestación de servicios digitalizados con los sistemas clásicos identificando las mejoras introducidas.'),
(143, 14, 5, 'Elabora un plan de transformación de una empresa clásica del sector en el que se enmarca el título, basada en una EL, al concepto 4.0, determinando los cambios a introducir en las principales fases del sistema e indicando como afectaría a los recursos humanos.'),
(144, 15, 1, 'Identifica los aspectos ambientales, sociales y de gobernanza (ASG) relativos a la sostenibilidad teniendo en cuenta el concepto de desarrollo sostenible y los marcos internacionales que contribuyen a su consecución.'),
(145, 15, 2, 'Caracteriza los retos ambientales y sociales a los que se enfrenta la sociedad, describiendo los impactos sobre las personas y los sectores productivos.'),
(146, 15, 3, 'Establece la aplicación de criterios de sostenibilidad en el desempeño profesional y personal, identificando los elementos necesarios.'),
(147, 15, 4, 'Propone productos y servicios responsables teniendo en cuenta los principios de la economía circular.'),
(148, 15, 5, 'Realiza actividades sostenibles minimizando el impacto de las mismas en el medio ambiente.'),
(149, 15, 6, 'Analiza un plan de sostenibilidad de una empresa del sector, identificando sus grupos de interés y los aspectos ASG materiales.'),
(150, 16, 1, 'Identifica los principales referentes de la historia y la cultura del videojuego valorando su incidencia en la sociedad actual y las tendencias de desarrollo.'),
(151, 16, 2, 'Configura entornos de desarrollo, herramientas y motores de desarrollo de videojuegos.'),
(152, 16, 3, 'Desarrolla programas que integran contenidos multimedia analizando y empleando las tecnologías y librerías específicas.'),
(153, 16, 4, 'Define el interfaz de usuario del videojuego teniendo en cuenta su rapidez y la facilidad de utilización.'),
(154, 11, 1, 'Instala sistemas operativos en red describiendo sus características e interpretando la documentación técnica.'),
(155, 11, 2, 'Gestiona usuarios y grupos de sistemas operativos en red, interpretando especificaciones y aplicando herramientas del sistema.'),
(156, 11, 3, 'Realiza tareas de gestión sobre dominios identificando necesidades y aplicando herramientas de administración de dominios.'),
(157, 11, 4, 'Gestiona los recursos compartidos del sistema, interpretando especificaciones y determinando niveles de seguridad.'),
(158, 11, 5, 'Realiza tareas de monitorización y uso del sistema operativo en red, describiendo las herramientas utilizadas e identificando las principales incidencias.'),
(159, 11, 6, 'Realiza tareas de integración de sistemas operativos libres y propietarios, describiendo las ventajas de compartir recursos e instalando software específico.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefonos`
--

CREATE TABLE `telefonos` (
  `id_telefono` int(10) UNSIGNED NOT NULL,
  `entidad_tipo` varchar(60) NOT NULL,
  `id_entidad` int(10) UNSIGNED NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `etiqueta` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vias`
--

CREATE TABLE `vias` (
  `id_via` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(5) NOT NULL,
  `via` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vias`
--

INSERT INTO `vias` (`id_via`, `codigo`, `via`) VALUES
(1, 'AL', 'Alameda'),
(2, 'AD', 'Aldea'),
(3, 'AP', 'Apartamentos'),
(4, 'AY', 'Arroyo'),
(5, 'AV', 'Avenida'),
(6, 'BJ', 'Bajada'),
(7, 'BR', 'Barranco'),
(8, 'BO', 'Barrio'),
(9, 'BL', 'Bloque'),
(10, 'CL', 'Calle'),
(11, 'CJ', 'Calleja'),
(12, 'CM', 'Camino'),
(13, 'CR', 'Carretera'),
(14, 'CS', 'Caserío'),
(15, 'CH', 'Chalet'),
(16, 'CG', 'Colegio'),
(17, 'CO', 'Colonia'),
(18, 'CN', 'Conjunto'),
(19, 'CT', 'Cuesta'),
(20, 'ED', 'Edificio'),
(21, 'EN', 'Entrada'),
(22, 'ES', 'Escalinata'),
(23, 'EX', 'Explanada'),
(24, 'EM', 'Extramuros'),
(25, 'ER', 'Extrarradio'),
(26, 'FC', 'Ferrocarril'),
(27, 'GL', 'Glorieta'),
(28, 'GV', 'Gran vía'),
(29, 'GR', 'Grupo'),
(30, 'HT', 'Huerta'),
(31, 'JR', 'Jardines'),
(32, 'LD', 'Lado'),
(33, 'LG', 'Lugar'),
(34, 'MZ', 'Manzana'),
(35, 'MS', 'Masía'),
(36, 'MC', 'Mercado'),
(37, 'MT', 'Monte'),
(38, 'ML', 'Muelle'),
(39, 'MN', 'Municipio'),
(40, 'PA', 'Parcela'),
(41, 'PQ', 'Parque'),
(42, 'PI', 'Parroquia'),
(43, 'PD', 'Partida'),
(44, 'PJ', 'Pasaje'),
(45, 'PS', 'Paseo'),
(46, 'PZ', 'Plaza'),
(47, 'PB', 'Poblado'),
(48, 'PG', 'Polígono'),
(49, 'PR', 'Prolongación'),
(50, 'PT', 'Puente'),
(51, 'PU', 'Puerta'),
(52, 'QT', 'Quinta'),
(53, 'RM', 'Ramal'),
(54, 'RB', 'Rambla'),
(55, 'RP', 'Rampa'),
(56, 'RI', 'Riera'),
(57, 'RC', 'Rincón'),
(58, 'RD', 'Ronda'),
(59, 'RU', 'Rúa'),
(60, 'SA', 'Salida'),
(61, 'SC', 'Sector, sección'),
(62, 'SD', 'Senda'),
(63, 'SL', 'Solar, salón'),
(64, 'SB', 'Subida'),
(65, 'TN', 'Terrenos'),
(66, 'TO', 'Torrente'),
(67, 'TR', 'Travesía'),
(68, 'UR', 'Urbanización'),
(69, 'VI', 'Vía'),
(70, 'VP', 'Vía pública'),
(71, 'AR', 'Área, arrabal');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD PRIMARY KEY (`id_alumno`),
  ADD UNIQUE KEY `nia` (`nia`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD KEY `fk_alu_provincia` (`id_provincia`),
  ADD KEY `fk_alu_localidad` (`id_localidad`);

--
-- Indices de la tabla `alumno_curso`
--
ALTER TABLE `alumno_curso`
  ADD PRIMARY KEY (`id_alumno`,`id_curso_escolar`),
  ADD KEY `fk_ac_escolar` (`id_curso_escolar`),
  ADD KEY `fk_ac_nivel` (`id_nivel`),
  ADD KEY `fk_ac_ciclo` (`id_ciclo`),
  ADD KEY `fk_ac_curso` (`id_curso`),
  ADD KEY `fk_ac_grupo` (`id_grupo`);

--
-- Indices de la tabla `alumno_modulo`
--
ALTER TABLE `alumno_modulo`
  ADD PRIMARY KEY (`id_alumno`,`id_modulo`),
  ADD KEY `fk_am_modulo` (`id_modulo`);

--
-- Indices de la tabla `asistencia_mensual`
--
ALTER TABLE `asistencia_mensual`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD KEY `fk_as_alumno` (`id_alumno`),
  ADD KEY `fk_as_escolar` (`id_curso_escolar`),
  ADD KEY `fk_as_mes` (`id_mes`);

--
-- Indices de la tabla `ciclos`
--
ALTER TABLE `ciclos`
  ADD PRIMARY KEY (`id_ciclo`);

--
-- Indices de la tabla `correos`
--
ALTER TABLE `correos`
  ADD PRIMARY KEY (`id_correo`);

--
-- Indices de la tabla `criterios_evaluacion`
--
ALTER TABLE `criterios_evaluacion`
  ADD PRIMARY KEY (`id_ce`),
  ADD KEY `fk_ce_ra` (`id_ra`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id_curso`);

--
-- Indices de la tabla `cursos_escolares`
--
ALTER TABLE `cursos_escolares`
  ADD PRIMARY KEY (`id_curso_escolar`);

--
-- Indices de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD PRIMARY KEY (`id_direccion`),
  ADD KEY `fk_dir_empresa` (`id_empresa`),
  ADD KEY `fk_dir_provincia` (`id_provincia`),
  ADD KEY `fk_dir_localidad` (`id_localidad`),
  ADD KEY `fk_dir_via` (`id_via`),
  ADD KEY `fk_dir_pais` (`id_pais`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id_empresa`),
  ADD UNIQUE KEY `cif` (`cif`);

--
-- Indices de la tabla `empresas_contactos`
--
ALTER TABLE `empresas_contactos`
  ADD PRIMARY KEY (`id_empresa_contacto`),
  ADD KEY `fk_con_empresa` (`id_empresa`);

--
-- Indices de la tabla `empresas_tutores`
--
ALTER TABLE `empresas_tutores`
  ADD PRIMARY KEY (`id_empresas_tutor`),
  ADD KEY `fk_tut_empresa` (`id_empresa`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id_grupo`),
  ADD KEY `fk_grp_nivel` (`id_nivel`),
  ADD KEY `fk_grp_ciclo` (`id_ciclo`),
  ADD KEY `fk_grp_curso` (`id_curso`);

--
-- Indices de la tabla `grupos_tutores`
--
ALTER TABLE `grupos_tutores`
  ADD PRIMARY KEY (`id_grupo_tutor`),
  ADD UNIQUE KEY `uk_grupo_curso` (`id_grupo`,`id_curso_escolar`),
  ADD KEY `fk_gt_profesor` (`id_profesor`),
  ADD KEY `fk_gt_curso_escolar` (`id_curso_escolar`);

--
-- Indices de la tabla `localidades`
--
ALTER TABLE `localidades`
  ADD PRIMARY KEY (`id_localidad`),
  ADD UNIQUE KEY `uk_localidad_provincia` (`id_provincia`,`nombre`);

--
-- Indices de la tabla `meses`
--
ALTER TABLE `meses`
  ADD PRIMARY KEY (`id_mes`);

--
-- Indices de la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD PRIMARY KEY (`id_modulo`),
  ADD KEY `fk_mod_nivel` (`id_nivel`),
  ADD KEY `fk_mod_ciclo` (`id_ciclo`),
  ADD KEY `fk_mod_curso` (`id_curso`);

--
-- Indices de la tabla `modulos_profesores`
--
ALTER TABLE `modulos_profesores`
  ADD PRIMARY KEY (`id_modulo_profesor`),
  ADD KEY `fk_mp_modulo` (`id_modulo`),
  ADD KEY `fk_mp_profesor` (`id_profesor`),
  ADD KEY `fk_mp_curso_escolar` (`id_curso_escolar`);

--
-- Indices de la tabla `niveles`
--
ALTER TABLE `niveles`
  ADD PRIMARY KEY (`id_nivel`);

--
-- Indices de la tabla `no_lectivos`
--
ALTER TABLE `no_lectivos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `paises`
--
ALTER TABLE `paises`
  ADD PRIMARY KEY (`id_pais`);

--
-- Indices de la tabla `practicas`
--
ALTER TABLE `practicas`
  ADD PRIMARY KEY (`id_practica`),
  ADD KEY `fk_pra_alumno` (`id_alumno`),
  ADD KEY `fk_pra_empresa` (`id_empresa`),
  ADD KEY `fk_pra_direccion` (`id_direccion`),
  ADD KEY `fk_pra_tutor` (`id_empresa_tutor`),
  ADD KEY `fk_pra_estado` (`id_practicas_estado`);

--
-- Indices de la tabla `practicas_anexos`
--
ALTER TABLE `practicas_anexos`
  ADD PRIMARY KEY (`id_practicas_anexo`);

--
-- Indices de la tabla `practicas_anexos_estados`
--
ALTER TABLE `practicas_anexos_estados`
  ADD PRIMARY KEY (`id_practicas_anexo_estado`);

--
-- Indices de la tabla `practicas_estados`
--
ALTER TABLE `practicas_estados`
  ADD PRIMARY KEY (`id_practicas_estado`);

--
-- Indices de la tabla `practicas_horario`
--
ALTER TABLE `practicas_horario`
  ADD PRIMARY KEY (`id_practicas_horario`),
  ADD KEY `fk_hor_practica` (`id_practica`);

--
-- Indices de la tabla `practicas_pasos`
--
ALTER TABLE `practicas_pasos`
  ADD PRIMARY KEY (`id_practicas_paso`);

--
-- Indices de la tabla `practicas_ras`
--
ALTER TABLE `practicas_ras`
  ADD PRIMARY KEY (`id_practica_ra`),
  ADD UNIQUE KEY `uq_practicas_ras_context` (`curso_escolar`,`ciclo`,`id_modulo`,`id_ra`),
  ADD KEY `fk_pras_curso_escolar` (`id_curso_escolar`),
  ADD KEY `fk_pras_ra` (`id_ra`),
  ADD KEY `fk_pras_ciclo` (`id_ciclo`),
  ADD KEY `idx_practicas_ras_curso_ciclo` (`curso_escolar`,`ciclo`),
  ADD KEY `idx_practicas_ras_id_ra` (`id_ra`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`id_profesor`),
  ADD UNIQUE KEY `dni` (`dni`);

--
-- Indices de la tabla `provincias`
--
ALTER TABLE `provincias`
  ADD PRIMARY KEY (`id_provincia`),
  ADD KEY `fk_prov_pais` (`id_pais`);

--
-- Indices de la tabla `resultados_aprendizaje`
--
ALTER TABLE `resultados_aprendizaje`
  ADD PRIMARY KEY (`id_ra`),
  ADD KEY `fk_ra_modulo` (`id_modulo`);

--
-- Indices de la tabla `telefonos`
--
ALTER TABLE `telefonos`
  ADD PRIMARY KEY (`id_telefono`);

--
-- Indices de la tabla `vias`
--
ALTER TABLE `vias`
  ADD PRIMARY KEY (`id_via`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  MODIFY `id_alumno` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asistencia_mensual`
--
ALTER TABLE `asistencia_mensual`
  MODIFY `id_asistencia` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ciclos`
--
ALTER TABLE `ciclos`
  MODIFY `id_ciclo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `correos`
--
ALTER TABLE `correos`
  MODIFY `id_correo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `criterios_evaluacion`
--
ALTER TABLE `criterios_evaluacion`
  MODIFY `id_ce` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=533;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id_curso` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cursos_escolares`
--
ALTER TABLE `cursos_escolares`
  MODIFY `id_curso_escolar` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  MODIFY `id_direccion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id_empresa` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresas_contactos`
--
ALTER TABLE `empresas_contactos`
  MODIFY `id_empresa_contacto` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresas_tutores`
--
ALTER TABLE `empresas_tutores`
  MODIFY `id_empresas_tutor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id_grupo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `grupos_tutores`
--
ALTER TABLE `grupos_tutores`
  MODIFY `id_grupo_tutor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `localidades`
--
ALTER TABLE `localidades`
  MODIFY `id_localidad` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `meses`
--
ALTER TABLE `meses`
  MODIFY `id_mes` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id_modulo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de la tabla `modulos_profesores`
--
ALTER TABLE `modulos_profesores`
  MODIFY `id_modulo_profesor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `niveles`
--
ALTER TABLE `niveles`
  MODIFY `id_nivel` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `no_lectivos`
--
ALTER TABLE `no_lectivos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `paises`
--
ALTER TABLE `paises`
  MODIFY `id_pais` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=210;

--
-- AUTO_INCREMENT de la tabla `practicas`
--
ALTER TABLE `practicas`
  MODIFY `id_practica` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `practicas_anexos`
--
ALTER TABLE `practicas_anexos`
  MODIFY `id_practicas_anexo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `practicas_anexos_estados`
--
ALTER TABLE `practicas_anexos_estados`
  MODIFY `id_practicas_anexo_estado` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `practicas_estados`
--
ALTER TABLE `practicas_estados`
  MODIFY `id_practicas_estado` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `practicas_horario`
--
ALTER TABLE `practicas_horario`
  MODIFY `id_practicas_horario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `practicas_pasos`
--
ALTER TABLE `practicas_pasos`
  MODIFY `id_practicas_paso` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `practicas_ras`
--
ALTER TABLE `practicas_ras`
  MODIFY `id_practica_ra` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `id_profesor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `provincias`
--
ALTER TABLE `provincias`
  MODIFY `id_provincia` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de la tabla `resultados_aprendizaje`
--
ALTER TABLE `resultados_aprendizaje`
  MODIFY `id_ra` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT de la tabla `telefonos`
--
ALTER TABLE `telefonos`
  MODIFY `id_telefono` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vias`
--
ALTER TABLE `vias`
  MODIFY `id_via` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD CONSTRAINT `fk_alu_localidad` FOREIGN KEY (`id_localidad`) REFERENCES `localidades` (`id_localidad`),
  ADD CONSTRAINT `fk_alu_provincia` FOREIGN KEY (`id_provincia`) REFERENCES `provincias` (`id_provincia`);

--
-- Filtros para la tabla `alumno_curso`
--
ALTER TABLE `alumno_curso`
  ADD CONSTRAINT `fk_ac_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ac_ciclo` FOREIGN KEY (`id_ciclo`) REFERENCES `ciclos` (`id_ciclo`),
  ADD CONSTRAINT `fk_ac_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`),
  ADD CONSTRAINT `fk_ac_escolar` FOREIGN KEY (`id_curso_escolar`) REFERENCES `cursos_escolares` (`id_curso_escolar`),
  ADD CONSTRAINT `fk_ac_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`),
  ADD CONSTRAINT `fk_ac_nivel` FOREIGN KEY (`id_nivel`) REFERENCES `niveles` (`id_nivel`);

--
-- Filtros para la tabla `alumno_modulo`
--
ALTER TABLE `alumno_modulo`
  ADD CONSTRAINT `fk_am_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_am_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulo`);

--
-- Filtros para la tabla `asistencia_mensual`
--
ALTER TABLE `asistencia_mensual`
  ADD CONSTRAINT `fk_as_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_as_escolar` FOREIGN KEY (`id_curso_escolar`) REFERENCES `cursos_escolares` (`id_curso_escolar`),
  ADD CONSTRAINT `fk_as_mes` FOREIGN KEY (`id_mes`) REFERENCES `meses` (`id_mes`);

--
-- Filtros para la tabla `criterios_evaluacion`
--
ALTER TABLE `criterios_evaluacion`
  ADD CONSTRAINT `fk_ce_ra` FOREIGN KEY (`id_ra`) REFERENCES `resultados_aprendizaje` (`id_ra`) ON DELETE CASCADE;

--
-- Filtros para la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD CONSTRAINT `fk_dir_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dir_localidad` FOREIGN KEY (`id_localidad`) REFERENCES `localidades` (`id_localidad`),
  ADD CONSTRAINT `fk_dir_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`),
  ADD CONSTRAINT `fk_dir_provincia` FOREIGN KEY (`id_provincia`) REFERENCES `provincias` (`id_provincia`),
  ADD CONSTRAINT `fk_dir_via` FOREIGN KEY (`id_via`) REFERENCES `vias` (`id_via`);

--
-- Filtros para la tabla `empresas_contactos`
--
ALTER TABLE `empresas_contactos`
  ADD CONSTRAINT `fk_con_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON DELETE CASCADE;

--
-- Filtros para la tabla `empresas_tutores`
--
ALTER TABLE `empresas_tutores`
  ADD CONSTRAINT `fk_tut_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON DELETE CASCADE;

--
-- Filtros para la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD CONSTRAINT `fk_grp_ciclo` FOREIGN KEY (`id_ciclo`) REFERENCES `ciclos` (`id_ciclo`),
  ADD CONSTRAINT `fk_grp_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`),
  ADD CONSTRAINT `fk_grp_nivel` FOREIGN KEY (`id_nivel`) REFERENCES `niveles` (`id_nivel`);

--
-- Filtros para la tabla `grupos_tutores`
--
ALTER TABLE `grupos_tutores`
  ADD CONSTRAINT `fk_gt_curso_escolar` FOREIGN KEY (`id_curso_escolar`) REFERENCES `cursos_escolares` (`id_curso_escolar`),
  ADD CONSTRAINT `fk_gt_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gt_profesor` FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id_profesor`);

--
-- Filtros para la tabla `localidades`
--
ALTER TABLE `localidades`
  ADD CONSTRAINT `fk_loc_provincia` FOREIGN KEY (`id_provincia`) REFERENCES `provincias` (`id_provincia`);

--
-- Filtros para la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD CONSTRAINT `fk_mod_ciclo` FOREIGN KEY (`id_ciclo`) REFERENCES `ciclos` (`id_ciclo`),
  ADD CONSTRAINT `fk_mod_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`),
  ADD CONSTRAINT `fk_mod_nivel` FOREIGN KEY (`id_nivel`) REFERENCES `niveles` (`id_nivel`);

--
-- Filtros para la tabla `modulos_profesores`
--
ALTER TABLE `modulos_profesores`
  ADD CONSTRAINT `fk_mp_curso_escolar_ref` FOREIGN KEY (`id_curso_escolar`) REFERENCES `cursos_escolares` (`id_curso_escolar`),
  ADD CONSTRAINT `fk_mp_modulo_ref` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulo`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mp_profesor_ref` FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id_profesor`);

--
-- Filtros para la tabla `practicas`
--
ALTER TABLE `practicas`
  ADD CONSTRAINT `fk_pra_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pra_direccion` FOREIGN KEY (`id_direccion`) REFERENCES `direcciones` (`id_direccion`),
  ADD CONSTRAINT `fk_pra_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pra_estado` FOREIGN KEY (`id_practicas_estado`) REFERENCES `practicas_estados` (`id_practicas_estado`),
  ADD CONSTRAINT `fk_pra_tutor` FOREIGN KEY (`id_empresa_tutor`) REFERENCES `empresas_tutores` (`id_empresas_tutor`);

--
-- Filtros para la tabla `practicas_horario`
--
ALTER TABLE `practicas_horario`
  ADD CONSTRAINT `fk_hor_practica` FOREIGN KEY (`id_practica`) REFERENCES `practicas` (`id_practica`) ON DELETE CASCADE;

--
-- Filtros para la tabla `practicas_ras`
--
ALTER TABLE `practicas_ras`
  ADD CONSTRAINT `fk_pras_ciclo` FOREIGN KEY (`id_ciclo`) REFERENCES `ciclos` (`id_ciclo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pras_curso_escolar` FOREIGN KEY (`id_curso_escolar`) REFERENCES `cursos_escolares` (`id_curso_escolar`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pras_ra` FOREIGN KEY (`id_ra`) REFERENCES `resultados_aprendizaje` (`id_ra`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `provincias`
--
ALTER TABLE `provincias`
  ADD CONSTRAINT `fk_prov_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`);

--
-- Filtros para la tabla `resultados_aprendizaje`
--
ALTER TABLE `resultados_aprendizaje`
  ADD CONSTRAINT `fk_ra_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulo`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
