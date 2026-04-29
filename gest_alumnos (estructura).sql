-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-04-2026 a las 19:24:05
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

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
  `retrasos` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia_modulo_acumulada`
--

CREATE TABLE `asistencia_modulo_acumulada` (
  `id_asistencia_modulo` int(10) UNSIGNED NOT NULL,
  `id_alumno` int(10) UNSIGNED NOT NULL,
  `id_modulo` int(10) UNSIGNED NOT NULL,
  `id_curso_escolar` int(10) UNSIGNED NOT NULL,
  `faltas_totales` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `retrasos_totales` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones`
--

CREATE TABLE `calificaciones` (
  `id_calificacion` int(10) UNSIGNED NOT NULL,
  `id_alumno` int(10) UNSIGNED NOT NULL,
  `id_curso_escolar` int(10) UNSIGNED NOT NULL,
  `id_ciclo` int(10) UNSIGNED NOT NULL,
  `id_curso` int(10) UNSIGNED NOT NULL,
  `id_grupo` int(10) UNSIGNED NOT NULL,
  `id_modulo` int(10) UNSIGNED NOT NULL,
  `id_evaluacion` int(10) UNSIGNED NOT NULL,
  `calificacion_original` varchar(20) DEFAULT NULL,
  `prefijo` varchar(10) DEFAULT NULL,
  `nota` decimal(4,2) DEFAULT NULL,
  `fecha_importacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciclos`
--

CREATE TABLE `ciclos` (
  `id_ciclo` int(10) UNSIGNED NOT NULL,
  `ciclo` varchar(60) NOT NULL,
  `abreviatura` varchar(60) NOT NULL,
  `codigo` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `codigos_postales`
--

CREATE TABLE `codigos_postales` (
  `id` int(11) NOT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `poblacion` varchar(150) NOT NULL,
  `cod_postal` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `config`
--

CREATE TABLE `config` (
  `clave` varchar(64) NOT NULL,
  `valor` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id_curso` int(10) UNSIGNED NOT NULL,
  `curso` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos_escolares`
--

CREATE TABLE `cursos_escolares` (
  `id_curso_escolar` int(10) UNSIGNED NOT NULL,
  `curso_escolar` varchar(60) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `nombre_comercial` varchar(150) DEFAULT NULL,
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
-- Estructura de tabla para la tabla `evaluaciones`
--

CREATE TABLE `evaluaciones` (
  `id_evaluacion` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL
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
-- Estructura de tabla para la tabla `horarios_grupos`
--

CREATE TABLE `horarios_grupos` (
  `id_horario_grupo` int(10) UNSIGNED NOT NULL,
  `id_curso_escolar` int(10) UNSIGNED NOT NULL,
  `id_grupo` int(10) UNSIGNED NOT NULL,
  `dia_semana` tinyint(1) UNSIGNED NOT NULL,
  `id_horario_tramo` int(10) UNSIGNED NOT NULL,
  `id_modulo` int(10) UNSIGNED NOT NULL,
  `id_profesor` int(10) UNSIGNED DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios_tramos`
--

CREATE TABLE `horarios_tramos` (
  `id_horario_tramo` int(10) UNSIGNED NOT NULL,
  `numero_tramo` tinyint(1) UNSIGNED NOT NULL,
  `nombre` varchar(20) NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `localidades`
--

CREATE TABLE `localidades` (
  `id_localidad` int(10) UNSIGNED NOT NULL,
  `id_provincia` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `km_desde_getafe` smallint(5) UNSIGNED DEFAULT NULL,
  `abono_desde_getafe` varchar(20) DEFAULT NULL
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
  `codigo_iso` char(2) NOT NULL,
  `pais` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `fecha_fin_real` date DEFAULT NULL,
  `motivo_exclusion` varchar(60) DEFAULT NULL,
  `id_practica_anterior` int(10) UNSIGNED DEFAULT NULL,
  `dias_extra` tinyint(2) UNSIGNED NOT NULL DEFAULT 0,
  `fecha_fin_extra` date DEFAULT NULL,
  `horas` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `circ_excep` tinyint(1) NOT NULL DEFAULT 0,
  `cancelada` tinyint(1) NOT NULL DEFAULT 0,
  `horas_hechas` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `mayor_edad` tinyint(1) NOT NULL DEFAULT 0,
  `instrucciones` text DEFAULT NULL,
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practicas_anexos_estados`
--

CREATE TABLE `practicas_anexos_estados` (
  `id_practicas_anexo_estado` int(10) UNSIGNED NOT NULL,
  `estado` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practicas_anexos_seguimiento`
--

CREATE TABLE `practicas_anexos_seguimiento` (
  `id_practica_anexo_seguimiento` int(10) UNSIGNED NOT NULL,
  `id_practica` int(10) UNSIGNED NOT NULL,
  `id_practicas_anexo` int(10) UNSIGNED NOT NULL,
  `id_practicas_anexo_estado` int(10) UNSIGNED NOT NULL,
  `numero_seguimiento` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `cp_prefijo` tinyint(2) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Estructura de tabla para la tabla `tutorias`
--

CREATE TABLE `tutorias` (
  `id_tutoria` int(10) UNSIGNED NOT NULL,
  `fecha` date NOT NULL
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
-- Indices de la tabla `asistencia_modulo_acumulada`
--
ALTER TABLE `asistencia_modulo_acumulada`
  ADD PRIMARY KEY (`id_asistencia_modulo`),
  ADD UNIQUE KEY `uq_asistencia_modulo` (`id_alumno`,`id_modulo`,`id_curso_escolar`),
  ADD KEY `fk_ama_alumno` (`id_alumno`),
  ADD KEY `fk_ama_modulo` (`id_modulo`),
  ADD KEY `fk_ama_curso_escolar` (`id_curso_escolar`);

--
-- Indices de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD PRIMARY KEY (`id_calificacion`),
  ADD UNIQUE KEY `uq_calificacion_contexto` (`id_alumno`,`id_curso_escolar`,`id_ciclo`,`id_curso`,`id_grupo`,`id_modulo`,`id_evaluacion`),
  ADD KEY `idx_cal_alumno` (`id_alumno`),
  ADD KEY `idx_cal_curso_escolar` (`id_curso_escolar`),
  ADD KEY `idx_cal_ciclo` (`id_ciclo`),
  ADD KEY `idx_cal_curso` (`id_curso`),
  ADD KEY `idx_cal_grupo` (`id_grupo`),
  ADD KEY `idx_cal_modulo` (`id_modulo`),
  ADD KEY `idx_cal_evaluacion` (`id_evaluacion`);

--
-- Indices de la tabla `ciclos`
--
ALTER TABLE `ciclos`
  ADD PRIMARY KEY (`id_ciclo`);

--
-- Indices de la tabla `codigos_postales`
--
ALTER TABLE `codigos_postales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cp` (`cod_postal`),
  ADD KEY `idx_poblacion` (`poblacion`),
  ADD KEY `idx_provincia` (`provincia`);

--
-- Indices de la tabla `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`clave`);

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
-- Indices de la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  ADD PRIMARY KEY (`id_evaluacion`),
  ADD UNIQUE KEY `uq_evaluacion_codigo` (`codigo`);

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
-- Indices de la tabla `horarios_grupos`
--
ALTER TABLE `horarios_grupos`
  ADD PRIMARY KEY (`id_horario_grupo`),
  ADD UNIQUE KEY `uq_horario_grupo_slot` (`id_curso_escolar`,`id_grupo`,`dia_semana`,`id_horario_tramo`),
  ADD KEY `idx_horarios_grupos_curso_grupo` (`id_curso_escolar`,`id_grupo`),
  ADD KEY `idx_horarios_grupos_modulo` (`id_modulo`),
  ADD KEY `idx_horarios_grupos_profesor` (`id_profesor`),
  ADD KEY `fk_hg_grupo` (`id_grupo`),
  ADD KEY `fk_hg_tramo` (`id_horario_tramo`);

--
-- Indices de la tabla `horarios_tramos`
--
ALTER TABLE `horarios_tramos`
  ADD PRIMARY KEY (`id_horario_tramo`),
  ADD UNIQUE KEY `uq_horarios_tramos_numero` (`numero_tramo`);

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
  ADD PRIMARY KEY (`id_pais`),
  ADD UNIQUE KEY `uk_paises_codigo_iso` (`codigo_iso`),
  ADD KEY `idx_paises_nombre` (`pais`);

--
-- Indices de la tabla `practicas`
--
ALTER TABLE `practicas`
  ADD PRIMARY KEY (`id_practica`),
  ADD KEY `fk_pra_alumno` (`id_alumno`),
  ADD KEY `fk_pra_empresa` (`id_empresa`),
  ADD KEY `fk_pra_direccion` (`id_direccion`),
  ADD KEY `fk_pra_tutor` (`id_empresa_tutor`);

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
-- Indices de la tabla `practicas_anexos_seguimiento`
--
ALTER TABLE `practicas_anexos_seguimiento`
  ADD PRIMARY KEY (`id_practica_anexo_seguimiento`),
  ADD UNIQUE KEY `uq_practica_anexo_seguimiento` (`id_practica`,`id_practicas_anexo`,`id_practicas_anexo_estado`,`numero_seguimiento`),
  ADD KEY `idx_pas_practica` (`id_practica`),
  ADD KEY `idx_pas_anexo` (`id_practicas_anexo`),
  ADD KEY `idx_pas_estado` (`id_practicas_anexo_estado`),
  ADD KEY `idx_pas_practica_anexo_numero` (`id_practica`,`id_practicas_anexo`,`numero_seguimiento`);

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
-- Indices de la tabla `tutorias`
--
ALTER TABLE `tutorias`
  ADD PRIMARY KEY (`id_tutoria`);

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
-- AUTO_INCREMENT de la tabla `asistencia_modulo_acumulada`
--
ALTER TABLE `asistencia_modulo_acumulada`
  MODIFY `id_asistencia_modulo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  MODIFY `id_calificacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ciclos`
--
ALTER TABLE `ciclos`
  MODIFY `id_ciclo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `codigos_postales`
--
ALTER TABLE `codigos_postales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `correos`
--
ALTER TABLE `correos`
  MODIFY `id_correo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `criterios_evaluacion`
--
ALTER TABLE `criterios_evaluacion`
  MODIFY `id_ce` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id_curso` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cursos_escolares`
--
ALTER TABLE `cursos_escolares`
  MODIFY `id_curso_escolar` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT de la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  MODIFY `id_evaluacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id_grupo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grupos_tutores`
--
ALTER TABLE `grupos_tutores`
  MODIFY `id_grupo_tutor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `horarios_grupos`
--
ALTER TABLE `horarios_grupos`
  MODIFY `id_horario_grupo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `horarios_tramos`
--
ALTER TABLE `horarios_tramos`
  MODIFY `id_horario_tramo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `localidades`
--
ALTER TABLE `localidades`
  MODIFY `id_localidad` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `meses`
--
ALTER TABLE `meses`
  MODIFY `id_mes` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id_modulo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulos_profesores`
--
ALTER TABLE `modulos_profesores`
  MODIFY `id_modulo_profesor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `niveles`
--
ALTER TABLE `niveles`
  MODIFY `id_nivel` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `no_lectivos`
--
ALTER TABLE `no_lectivos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paises`
--
ALTER TABLE `paises`
  MODIFY `id_pais` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `practicas`
--
ALTER TABLE `practicas`
  MODIFY `id_practica` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `practicas_anexos`
--
ALTER TABLE `practicas_anexos`
  MODIFY `id_practicas_anexo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `practicas_anexos_estados`
--
ALTER TABLE `practicas_anexos_estados`
  MODIFY `id_practicas_anexo_estado` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `practicas_anexos_seguimiento`
--
ALTER TABLE `practicas_anexos_seguimiento`
  MODIFY `id_practica_anexo_seguimiento` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `id_practica_ra` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `id_profesor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `provincias`
--
ALTER TABLE `provincias`
  MODIFY `id_provincia` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `resultados_aprendizaje`
--
ALTER TABLE `resultados_aprendizaje`
  MODIFY `id_ra` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `telefonos`
--
ALTER TABLE `telefonos`
  MODIFY `id_telefono` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tutorias`
--
ALTER TABLE `tutorias`
  MODIFY `id_tutoria` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vias`
--
ALTER TABLE `vias`
  MODIFY `id_via` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
-- Filtros para la tabla `asistencia_modulo_acumulada`
--
ALTER TABLE `asistencia_modulo_acumulada`
  ADD CONSTRAINT `fk_ama_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ama_curso_escolar` FOREIGN KEY (`id_curso_escolar`) REFERENCES `cursos_escolares` (`id_curso_escolar`),
  ADD CONSTRAINT `fk_ama_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD CONSTRAINT `fk_cal_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cal_ciclo` FOREIGN KEY (`id_ciclo`) REFERENCES `ciclos` (`id_ciclo`),
  ADD CONSTRAINT `fk_cal_curso` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`),
  ADD CONSTRAINT `fk_cal_curso_escolar` FOREIGN KEY (`id_curso_escolar`) REFERENCES `cursos_escolares` (`id_curso_escolar`),
  ADD CONSTRAINT `fk_cal_evaluacion` FOREIGN KEY (`id_evaluacion`) REFERENCES `evaluaciones` (`id_evaluacion`),
  ADD CONSTRAINT `fk_cal_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`),
  ADD CONSTRAINT `fk_cal_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulo`);

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
-- Filtros para la tabla `horarios_grupos`
--
ALTER TABLE `horarios_grupos`
  ADD CONSTRAINT `fk_hg_curso_escolar` FOREIGN KEY (`id_curso_escolar`) REFERENCES `cursos_escolares` (`id_curso_escolar`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hg_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hg_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hg_profesor` FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id_profesor`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hg_tramo` FOREIGN KEY (`id_horario_tramo`) REFERENCES `horarios_tramos` (`id_horario_tramo`) ON UPDATE CASCADE;

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
  ADD CONSTRAINT `fk_pra_tutor` FOREIGN KEY (`id_empresa_tutor`) REFERENCES `empresas_tutores` (`id_empresas_tutor`);

--
-- Filtros para la tabla `practicas_anexos_seguimiento`
--
ALTER TABLE `practicas_anexos_seguimiento`
  ADD CONSTRAINT `fk_pas_anexo` FOREIGN KEY (`id_practicas_anexo`) REFERENCES `practicas_anexos` (`id_practicas_anexo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pas_estado` FOREIGN KEY (`id_practicas_anexo_estado`) REFERENCES `practicas_anexos_estados` (`id_practicas_anexo_estado`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pas_practica` FOREIGN KEY (`id_practica`) REFERENCES `practicas` (`id_practica`) ON DELETE CASCADE ON UPDATE CASCADE;

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
