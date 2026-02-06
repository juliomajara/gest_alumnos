-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-02-2026 a las 13:38:07
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
  `telefono` varchar(20) DEFAULT NULL,
  `email_educamadrid` varchar(150) DEFAULT NULL,
  `email_personal` varchar(150) DEFAULT NULL,
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
-- Estructura de tabla para la tabla `localidades`
--

CREATE TABLE `localidades` (
  `id_localidad` int(10) UNSIGNED NOT NULL,
  `id_provincia` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(5, 1, 1, 1, 'CMO-313', 'FP', 'Optativas', 'Módulo profesional optativo I', 'Fundamentos de Programación', 2, 50),
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
(24, 2, 2, 1, 'CMO-1', 'OPT1', 'Optativas', 'Módulo profesional optativo I', '', 2, 50),
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
(44, 2, 3, 1, 'CMO-1', 'OPT1', 'Optativas', 'Módulo profesional optativo I', '', 2, 50),
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
(64, 2, 4, 1, 'CMO-311', 'ROB', 'Optativas', 'Módulo profesional optativo I', 'Informática aplicada a sistemas electrónicos (Robótica)', 2, 50),
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paises`
--

CREATE TABLE `paises` (
  `id_pais` int(10) UNSIGNED NOT NULL,
  `pais` varchar(60) NOT NULL,
  `codigo_iso` char(2) DEFAULT NULL,
  `rango_cp` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `paises`
--

INSERT INTO `paises` (`id_pais`, `pais`, `codigo_iso`, `rango_cp`) VALUES
(1, 'España', 'ES', '01001 - 52080'),
(2, 'Andorra', 'AD', 'AD100 : AD700'),
(3, 'Argentina', 'AR', '1601 : 9431'),
(4, 'Samoa Americana', 'AS', '96799 : 96799'),
(5, 'Austria', 'AT', '1010 : 9992'),
(6, 'Australia', 'AU', '0200 : 9726'),
(7, 'Bangladesh', 'BD', '1000 : 9461'),
(8, 'Bélgica', 'BE', '1000 : 9992'),
(9, 'Bulgaria', 'BG', '1000 : 9974'),
(10, 'Brasil', 'BR', '01000-000 : 99990-000'),
(11, 'Canadá', 'CA', 'A0A : Y1A'),
(12, 'Suiza', 'CH', '1000 : 9658'),
(13, 'República Checa', 'CZ', '100 00 : 798 62'),
(14, 'Alemania', 'DE', '01067 : 99998'),
(15, 'Dinamarca', 'DK', '0800 : 9990'),
(16, 'República Dominicana', 'DO', '10101 : 11906'),
(17, 'Finlandia', 'FI', '00002 : 99999'),
(18, 'Islas Feroe', 'FO', '100 : 970'),
(19, 'Francia', 'FR', '01000 : 98799'),
(20, 'Gran Bretaña', 'GB', 'AB1 : ZE3'),
(21, 'Guayana Francesa', 'GF', '97300 : 97390'),
(22, 'Guernsey', 'GG', 'GY1 : GY9'),
(23, 'Groenlandia', 'GL', '2412 : 3992'),
(24, 'Guadalupe', 'GP', '97100 : 97190'),
(25, 'Guatemala', 'GT', '01001 : 22027'),
(26, 'Guam', 'GU', '96910 : 96932'),
(27, 'Guyana', 'GY', '97312 : 97360'),
(28, 'Croacia', 'HR', '10000 : 53296'),
(29, 'Hungría', 'HU', '1011 : 9985'),
(30, 'Isla de Man', 'IM', 'IM1 : IM9'),
(31, 'India', 'IN', '110001 : 855126'),
(32, 'Islandia', 'IS', '101 : 902'),
(33, 'Italia', 'IT', '00010 : 98168'),
(34, 'Jersey', 'JE', 'JE1 : JE3'),
(35, 'Japón', 'JP', '100-0001 : 999-8531'),
(36, 'Liechtenstein', 'LI', '9485 : 9498'),
(37, 'Sri Lanka', 'LK', '10100 : 96167'),
(38, 'Lituania', 'LT', '00001 : 99069'),
(39, 'Luxemburgo', 'LU', 'L-1009 : L-9999'),
(40, 'Mónaco', 'MC', '98000 : 98000'),
(41, 'Moldavia', 'MD', 'MD-2000 : MD-7731'),
(42, 'Islas Marshall', 'MH', '96960 : 96970'),
(43, 'Macedonia', 'MK', '1000 : 7550'),
(44, 'Islas Marianas del Norte', 'MP', '96950 : 96952'),
(45, 'Martinica', 'MQ', '97200 : 97290'),
(46, 'México', 'MX', '01000 : 99998'),
(47, 'Malasia', 'MY', '01000 : 98859'),
(48, 'Países Bajos', 'NL', '1000 : 9999'),
(49, 'Noruega', 'NO', '0001 : 9991'),
(50, 'Nueva Zelanda', 'NZ', '0110 : 9893'),
(51, 'Filipinas', 'PH', '0400 : 9811'),
(52, 'Pakistán', 'PK', '10010 : 97320'),
(53, 'Polonia', 'PL', '00-001 : 99-440'),
(54, 'San Pedro y Miquelón', 'PM', '97500 : 97500'),
(55, 'Puerto Rico', 'PR', '00601 : 00988'),
(56, 'Portugal', 'PT', '1000-001 : 9980-999'),
(57, 'Reunión', 'RE', '97400 : 97490'),
(58, 'Rusia', 'RU', '101000 : 901993'),
(59, 'Suecia', 'SE', '10005 : 98499'),
(60, 'Eslovenia', 'SI', '1000 : 9600'),
(61, 'Svalbard y Jan Mayen', 'SJ', '8099 : 9178'),
(62, 'Eslovaquia', 'SK', '010 01 : 992 01'),
(63, 'San Marino', 'SM', '47890 : 47899'),
(64, 'Tailandia', 'TH', '10100 : 96220'),
(65, 'Turquía', 'TR', '01000 : 81950'),
(66, 'Estados Unidos', 'US', '00210 : 99950'),
(67, 'Vaticano', 'VA', '00120 : 00120'),
(68, 'Islas Vírgenes', 'VI', '00801 : 00851'),
(69, 'Mayotte', 'YT', '97600 : 97680'),
(70, 'Sudáfrica', 'ZA', '0002 : 9992'),
(71, 'Afganistán', 'AF', NULL),
(72, 'Albania', 'AL', NULL),
(73, 'Argelia', 'DZ', NULL),
(74, 'Angola', 'AO', NULL),
(75, 'Antigua y Barbuda', 'AG', NULL),
(76, 'Arabia Saudita', 'SA', NULL),
(77, 'Armenia', 'AM', NULL),
(78, 'Azerbaiyán', 'AZ', NULL),
(79, 'Bahamas', 'BS', NULL),
(80, 'Baréin', 'BH', NULL),
(81, 'Barbados', 'BB', NULL),
(82, 'Belice', 'BZ', NULL),
(83, 'Benín', 'BJ', NULL),
(84, 'Bielorrusia', 'BY', NULL),
(85, 'Birmania', 'MM', NULL),
(86, 'Bolivia', 'BO', NULL),
(87, 'Bosnia y Herzegovina', 'BA', NULL),
(88, 'Botsuana', 'BW', NULL),
(89, 'Brunéi', 'BN', NULL),
(90, 'Burkina Faso', 'BF', NULL),
(91, 'Burundi', 'BI', NULL),
(92, 'Bután', 'BT', NULL),
(93, 'Cabo Verde', 'CV', NULL),
(94, 'Camboya', 'KH', NULL),
(95, 'Camerún', 'CM', NULL),
(96, 'Chad', 'TD', NULL),
(97, 'Chile', 'CL', NULL),
(98, 'China', 'CN', NULL),
(99, 'Chipre', 'CY', NULL),
(100, 'Colombia', 'CO', NULL),
(101, 'Comoras', 'KM', NULL),
(102, 'Congo', 'CG', NULL),
(103, 'Corea del Norte', 'KP', NULL),
(104, 'Corea del Sur', 'KR', NULL),
(105, 'Costa de Marfil', 'CI', NULL),
(106, 'Costa Rica', 'CR', NULL),
(107, 'Cuba', 'CU', NULL),
(108, 'Dominica', 'DM', NULL),
(109, 'Ecuador', 'EC', NULL),
(110, 'Egipto', 'EG', NULL),
(111, 'El Salvador', 'SV', NULL),
(112, 'Emiratos Árabes Unidos', 'AE', NULL),
(113, 'Eritrea', 'ER', NULL),
(114, 'Estonia', 'EE', NULL),
(115, 'Etiopía', 'ET', NULL),
(116, 'Fiyi', 'FJ', NULL),
(117, 'Gabón', 'GA', NULL),
(118, 'Gambia', 'GM', NULL),
(119, 'Georgia', 'GE', NULL),
(120, 'Ghana', 'GH', NULL),
(121, 'Granada', 'GD', NULL),
(122, 'Grecia', 'GR', NULL),
(123, 'Guinea', 'GN', NULL),
(124, 'Guinea-Bisáu', 'GW', NULL),
(125, 'Guinea Ecuatorial', 'GQ', NULL),
(126, 'Haití', 'HT', NULL),
(127, 'Honduras', 'HN', NULL),
(128, 'Indonesia', 'ID', NULL),
(129, 'Irán', 'IR', NULL),
(130, 'Irak', 'IQ', NULL),
(131, 'Irlanda', 'IE', NULL),
(132, 'Israel', 'IL', NULL),
(133, 'Jamaica', 'JM', NULL),
(134, 'Jordania', 'JO', NULL),
(135, 'Kazajistán', 'KZ', NULL),
(136, 'Kenia', 'KE', NULL),
(137, 'Kirguistán', 'KG', NULL),
(138, 'Kiribati', 'KI', NULL),
(139, 'Kuwait', 'KW', NULL),
(140, 'Laos', 'LA', NULL),
(141, 'Lesoto', 'LS', NULL),
(142, 'Letonia', 'LV', NULL),
(143, 'Líbano', 'LB', NULL),
(144, 'Liberia', 'LR', NULL),
(145, 'Libia', 'LY', NULL),
(146, 'Madagascar', 'MG', NULL),
(147, 'Malaui', 'MW', NULL),
(148, 'Maldivas', 'MV', NULL),
(149, 'Malí', 'ML', NULL),
(150, 'Malta', 'MT', NULL),
(151, 'Marruecos', 'MA', NULL),
(152, 'Mauricio', 'MU', NULL),
(153, 'Mauritania', 'MR', NULL),
(154, 'Micronesia', 'FM', NULL),
(155, 'Mongolia', 'MN', NULL),
(156, 'Montenegro', 'ME', NULL),
(157, 'Mozambique', 'MZ', NULL),
(158, 'Namibia', 'NA', NULL),
(159, 'Nauru', 'NR', NULL),
(160, 'Nepal', 'NP', NULL),
(161, 'Nicaragua', 'NI', NULL),
(162, 'Níger', 'NE', NULL),
(163, 'Nigeria', 'NG', NULL),
(164, 'Omán', 'OM', NULL),
(165, 'Palaos', 'PW', NULL),
(166, 'Panamá', 'PA', NULL),
(167, 'Papúa Nueva Guinea', 'PG', NULL),
(168, 'Paraguay', 'PY', NULL),
(169, 'Perú', 'PE', NULL),
(170, 'Catar', 'QA', NULL),
(171, 'Ruanda', 'RW', NULL),
(172, 'Rumania', 'RO', NULL),
(173, 'Islas Salomón', 'SB', NULL),
(174, 'Samoa', 'WS', NULL),
(175, 'San Cristóbal y Nieves', 'KN', NULL),
(176, 'San Vicente y las Granadinas', 'VC', NULL),
(177, 'Santa Lucía', 'LC', NULL),
(178, 'Santo Tomé y Príncipe', 'ST', NULL),
(179, 'Senegal', 'SN', NULL),
(180, 'Serbia', 'RS', NULL),
(181, 'Seychelles', 'SC', NULL),
(182, 'Sierra Leona', 'SL', NULL),
(183, 'Singapur', 'SG', NULL),
(184, 'Siria', 'SY', NULL),
(185, 'Somalia', 'SO', NULL),
(186, 'Suazilandia', 'SZ', NULL),
(187, 'Sudán', 'SD', NULL),
(188, 'Sudán del Sur', 'SS', NULL),
(189, 'Surinam', 'SR', NULL),
(190, 'Tanzania', 'TZ', NULL),
(191, 'Tayikistán', 'TJ', NULL),
(192, 'Timor Oriental', 'TL', NULL),
(193, 'Togo', 'TG', NULL),
(194, 'Tonga', 'TO', NULL),
(195, 'Trinidad y Tobago', 'TT', NULL),
(196, 'Túnez', 'TN', NULL),
(197, 'Turkmenistán', 'TM', NULL),
(198, 'Tuvalu', 'TV', NULL),
(199, 'Ucrania', 'UA', NULL),
(200, 'Uganda', 'UG', NULL),
(201, 'Uruguay', 'UY', NULL),
(202, 'Uzbekistán', 'UZ', NULL),
(203, 'Vanuatu', 'VU', NULL),
(204, 'Venezuela', 'VE', NULL),
(205, 'Vietnam', 'VN', NULL),
(206, 'Yemen', 'YE', NULL),
(207, 'Yibuti', 'DJ', NULL),
(208, 'Zambia', 'ZM', NULL),
(209, 'Zimbabue', 'ZW', NULL);

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
  `horas` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `requiere_anexo_5` tinyint(1) NOT NULL DEFAULT 0,
  `requiere_anexo_6` tinyint(1) NOT NULL DEFAULT 0,
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
(1, 'Anexo 1.1', 'Convenio entre centro docente y entidad colaboradora', 'Convenio'),
(2, 'Anexo 1.1', 'Acuerdo entre centro docente y centro público colaborador', 'Acuerdo'),
(3, 'Anexo 2.1', 'Relación de alumnos', 'Relación de alumnos'),
(4, 'Anexo 2.1', 'Cambio de condiciones', 'Cambio de condiciones'),
(5, 'Anexo 2.2', 'Comunicación a la DAT de la relación de alumnos', 'Comunicación a la DAT'),
(6, 'Anexo 3', 'Plan de formación en empresa', 'Plan de formación'),
(7, 'Anexo 4', 'Solicitud de autorización para la realización de la FFE bajo circunstancias de carácter excepcional', 'Autorización excepcional'),
(8, 'Anexo 6', 'Comunicación en caso de no realizarse la FFE en el primer curso o periodo por falta de puestos formativos', 'Comunicación de no FFE'),
(9, 'Anexo ?', 'Ficha de seguimiento periódico', 'Ficha de seguimiento'),
(10, 'Anexo ?', 'Informe de valoración final del tutor de empresa', 'Informe final');

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
(1, 'Firmado por el representante de la empresa'),
(2, 'Firmado por el director del centro'),
(3, 'Firmado por el tutor en la empresa'),
(4, 'Firmado por el tutor en el centro'),
(5, 'Firmado por el alumno');

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

--
-- Volcado de datos para la tabla `practicas_pasos`
--

INSERT INTO `practicas_pasos` (`id_practicas_paso`, `paso`) VALUES
(1, 'Datos pedidos a la empresa'),
(2, 'Enviado a firmar por el representante de la empresa'),
(3, 'Enviado a firmar por el director del centro'),
(4, 'Enviado a firmar por el tutor en la empresa'),
(5, 'Enviado a firmar por el tutor en el centro'),
(6, 'Enviado a firmar por el alumno'),
(7, 'Enviado al alumno'),
(8, 'Enviado a la empresa');

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
-- Indices de la tabla `provincias`
--
ALTER TABLE `provincias`
  ADD PRIMARY KEY (`id_provincia`),
  ADD KEY `fk_prov_pais` (`id_pais`);

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
-- AUTO_INCREMENT de la tabla `localidades`
--
ALTER TABLE `localidades`
  MODIFY `id_localidad` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT de la tabla `niveles`
--
ALTER TABLE `niveles`
  MODIFY `id_nivel` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `no_lectivos`
--
ALTER TABLE `no_lectivos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `id_practicas_anexo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `practicas_estados`
--
ALTER TABLE `practicas_estados`
  MODIFY `id_practicas_estado` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `practicas_horario`
--
ALTER TABLE `practicas_horario`
  MODIFY `id_practicas_horario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `practicas_pasos`
--
ALTER TABLE `practicas_pasos`
  MODIFY `id_practicas_paso` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `provincias`
--
ALTER TABLE `provincias`
  MODIFY `id_provincia` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

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
  ADD CONSTRAINT `fk_ac_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`),
  ADD CONSTRAINT `fk_ac_ciclo` FOREIGN KEY (`id_ciclo`) REFERENCES `ciclos` (`id_ciclo`),
  ADD CONSTRAINT `fk_ac_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`),
  ADD CONSTRAINT `fk_ac_escolar` FOREIGN KEY (`id_curso_escolar`) REFERENCES `cursos_escolares` (`id_curso_escolar`),
  ADD CONSTRAINT `fk_ac_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`),
  ADD CONSTRAINT `fk_ac_nivel` FOREIGN KEY (`id_nivel`) REFERENCES `niveles` (`id_nivel`);

--
-- Filtros para la tabla `alumno_modulo`
--
ALTER TABLE `alumno_modulo`
  ADD CONSTRAINT `fk_am_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`),
  ADD CONSTRAINT `fk_am_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulo`);

--
-- Filtros para la tabla `asistencia_mensual`
--
ALTER TABLE `asistencia_mensual`
  ADD CONSTRAINT `fk_as_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`),
  ADD CONSTRAINT `fk_as_escolar` FOREIGN KEY (`id_curso_escolar`) REFERENCES `cursos_escolares` (`id_curso_escolar`),
  ADD CONSTRAINT `fk_as_mes` FOREIGN KEY (`id_mes`) REFERENCES `meses` (`id_mes`);

--
-- Filtros para la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD CONSTRAINT `fk_dir_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`),
  ADD CONSTRAINT `fk_dir_localidad` FOREIGN KEY (`id_localidad`) REFERENCES `localidades` (`id_localidad`),
  ADD CONSTRAINT `fk_dir_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`),
  ADD CONSTRAINT `fk_dir_provincia` FOREIGN KEY (`id_provincia`) REFERENCES `provincias` (`id_provincia`),
  ADD CONSTRAINT `fk_dir_via` FOREIGN KEY (`id_via`) REFERENCES `vias` (`id_via`);

--
-- Filtros para la tabla `empresas_contactos`
--
ALTER TABLE `empresas_contactos`
  ADD CONSTRAINT `fk_con_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`);

--
-- Filtros para la tabla `empresas_tutores`
--
ALTER TABLE `empresas_tutores`
  ADD CONSTRAINT `fk_tut_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`);

--
-- Filtros para la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD CONSTRAINT `fk_grp_ciclo` FOREIGN KEY (`id_ciclo`) REFERENCES `ciclos` (`id_ciclo`),
  ADD CONSTRAINT `fk_grp_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`),
  ADD CONSTRAINT `fk_grp_nivel` FOREIGN KEY (`id_nivel`) REFERENCES `niveles` (`id_nivel`);

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
-- Filtros para la tabla `practicas`
--
ALTER TABLE `practicas`
  ADD CONSTRAINT `fk_pra_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`),
  ADD CONSTRAINT `fk_pra_direccion` FOREIGN KEY (`id_direccion`) REFERENCES `direcciones` (`id_direccion`),
  ADD CONSTRAINT `fk_pra_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`),
  ADD CONSTRAINT `fk_pra_estado` FOREIGN KEY (`id_practicas_estado`) REFERENCES `practicas_estados` (`id_practicas_estado`),
  ADD CONSTRAINT `fk_pra_tutor` FOREIGN KEY (`id_empresa_tutor`) REFERENCES `empresas_tutores` (`id_empresas_tutor`);

--
-- Filtros para la tabla `practicas_horario`
--
ALTER TABLE `practicas_horario`
  ADD CONSTRAINT `fk_hor_practica` FOREIGN KEY (`id_practica`) REFERENCES `practicas` (`id_practica`);

--
-- Filtros para la tabla `provincias`
--
ALTER TABLE `provincias`
  ADD CONSTRAINT `fk_prov_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
