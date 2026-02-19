-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-02-2026 a las 13:06:30
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

--
-- Volcado de datos para la tabla `alumnos`
--

INSERT INTO `alumnos` (`id_alumno`, `nia`, `dni`, `seg_soc`, `apellido1`, `apellido2`, `nombre`, `fecha_nacimiento`, `horas_ffe_aprobadas`, `id_provincia`, `id_localidad`, `cp`, `repite_curso`, `nombre_tutor1`, `telefono_tutor1`, `correo_tutor1`, `nombre_tutor2`, `telefono_tutor2`, `correo_tutor2`, `faltas_10_dia`, `faltas_10_cantidad`, `faltas_15_dia`, `faltas_15_cantidad`, `comentarios`) VALUES
(1, '8858757', '47318106E', '281580268564', 'Blasco', 'Gata', 'Andrea', '2007-03-22', 130, 28, NULL, NULL, 0, NULL, NULL, 'ablascoguijo@gmail.com', NULL, NULL, 'yolygata4@gmail.com', NULL, 0, NULL, 0, NULL),
(2, '10271520', '49589569M', '281576458080', 'Blázquez', 'Vargas', 'Gonzalo', '2006-04-05', 0, 28, NULL, NULL, 0, NULL, NULL, 'jesus.blazquez@siemens-healthineers.com', NULL, NULL, 'raquel@maderasvargas.es', NULL, 0, NULL, 0, NULL),
(3, '13242046', '49157732Q', '281489274682', 'Bonet', 'Borrás', 'Aarón', '2008-11-16', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'susanaborras@hotmail.es', NULL, NULL, 'davidbherraez@gmail.com', NULL, 0, NULL, 0, NULL),
(4, '48336796', 'Y6795560V', '281595440980', 'Borda', 'Sanchez', 'Joselyn Yalimet', '2001-08-16', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(5, '12152995', '47319652G', '471040858625', 'Cañada', 'García', 'Iván', '2007-10-19', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'kikikarol@hotmail.com', NULL, NULL, 'kikikarol@hotmail.com', NULL, 0, NULL, 0, NULL),
(6, '13219390', '49592444M', '281469268434', 'Casas', 'Álvarez', 'Alejandro', '2008-06-20', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'maralvarezcalderon@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(7, '9785908', '53906787P', '281651412913', 'Castañeda', 'Botero', 'Carlos Andrés', '2006-10-01', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'cc099679@gmail.com', NULL, NULL, 'dmb1805@hotmail.com', NULL, 0, NULL, 0, NULL),
(8, '21663413', 'Y6082370B', '281617949529', 'Cazún', 'Bonilla', 'Samuel Oswaldo', '2006-03-16', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'fredyos711@gmail.com', NULL, NULL, 'bliza806@gmail.com', NULL, 0, NULL, 0, NULL),
(9, '9963382', '02592144K', '281504434368', 'Cortés', 'Gallego', 'Diego', '2008-05-11', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'jorgecortesnevado@gmail.com', NULL, NULL, 'gallegoevamaria@gmail.com', NULL, 0, NULL, 0, NULL),
(10, '12240647', '49593929H', '281025571337', 'Díaz', 'García', 'Álvaro', '2007-07-14', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'phiolina@gmail.com', NULL, NULL, 'buyator@gmail.com', NULL, 0, NULL, 0, NULL),
(11, '50762439', '04262785B', '281573953460', 'Escalada', 'Galán', 'Gabriel', '2005-11-13', 60, 28, 1, '28903', 0, NULL, NULL, 'alesba@yahoo.es', NULL, NULL, 'pigaruiz@gmail.com', NULL, 0, NULL, 0, NULL),
(12, '13498849', '54732340E', '281651658241', 'Gallardo', 'Pérez', 'Roberto', '2008-11-02', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'maygema@gmail.com', NULL, NULL, 'gemita.perez20@gmail.com', NULL, 0, NULL, 0, NULL),
(13, '13482091', '03486572W', '281453285056', 'García', 'Bueno', 'Alejandro', '2008-04-26', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'juanfcogarcianavarro@gmail.com', NULL, NULL, 'gemabuenoverde162@gmail.com', NULL, 0, NULL, 0, NULL),
(14, '10166048', '54287520E', NULL, 'García', 'Deacal', 'Mario', '2007-10-30', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'azudeacalpena@gmail.com', NULL, NULL, 'cgarciab@fernandezmolina.com', NULL, 0, NULL, 0, NULL),
(15, '9991187', '54994483B', '281547505095', 'Garnacho', 'Linares', 'Roberto', '2006-11-15', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'rubengarnacho35@hotmail.es', NULL, NULL, 'b.linares.sanchez@gmail.com', NULL, 0, NULL, 0, NULL),
(16, '10440964', '55127182T', '281614418628', 'Giraldo', 'Arias', 'Juan Pablo', '2006-01-14', 130, 28, NULL, NULL, 0, NULL, NULL, 'fgiraldo45@gmail.com', NULL, NULL, 'yamile314@gmail.com', NULL, 0, NULL, 0, NULL),
(17, '10615935', '48159046J', '281577759395', 'González', 'Alcocer', 'Rubén', '2006-07-18', 130, 28, NULL, NULL, 0, NULL, NULL, 'josegarrillas@hotmail.com', NULL, NULL, 'kamialco@hotmail.com', NULL, 0, NULL, 0, NULL),
(18, '10227398', '55581259B', '281617877686', 'González', 'Parrondo', 'Itzan', '2008-07-08', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'kik_mikr@hotmail.com', NULL, NULL, 'itzan878@gmail.com', NULL, 0, NULL, 0, NULL),
(19, '9786011', '47318754A', '281578239042', 'Gutiérrez', 'Lucio', 'Jorge', '2006-08-24', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'guti10agv@hotmail.com', NULL, NULL, 'alsosanjo@hotmail.com', NULL, 0, NULL, 0, NULL),
(20, '9854796', '49746404A', '281579749616', 'Hurtado', 'Paredes', 'Darío', '2006-12-09', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'thejahc@gmail.com', NULL, NULL, 'rparedessierra@gmail.com', NULL, 0, NULL, 0, NULL),
(21, '13694494', '49704977E', '281356573228', 'Ikubor', 'Aimienoho', 'Wisdom Osamwenuyi', '2008-11-06', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'linda.aimienoho@yahoo.com', NULL, NULL, 'kevinvincent10@yahoo.com', NULL, 0, NULL, 0, NULL),
(22, '7626425', '55138304J', '281547259868', 'Jiménez', 'Ramiro', 'Roberto', '2006-10-23', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'narcisarg@gmail.com', NULL, 0, NULL, 0, NULL),
(23, '12748235', '06625477M', '281631745252', 'López', 'López', 'José Ignacio', '2007-10-01', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'belencalzada8@gmail.com', NULL, NULL, 'ignaciolopez-999@outlook.es', NULL, 0, NULL, 0, NULL),
(24, '9859524', '54299675X', '281543120901', 'Mangas', 'Martínez', 'Daniel', '2006-07-08', 0, 28, NULL, NULL, 0, NULL, NULL, 'rakelmartinezcuesta@gmail.com', NULL, NULL, 'rakelmartinezcuesta@gmail.com', NULL, 0, NULL, 0, NULL),
(25, '12329830', '49589590A', '281079016519', 'Martín', 'León', 'Mario', '2008-03-13', 0, 28, NULL, NULL, 0, NULL, NULL, 'gmartin_sanchez@hotmail.com', NULL, NULL, 'mudita-rl@hotmail.com', NULL, 0, NULL, 0, NULL),
(26, '13796525', 'Y1652928R', NULL, 'Mielnik', NULL, 'Bartosz', '2008-08-17', 0, 28, NULL, NULL, 0, NULL, NULL, 'krisloro@interia.pl', NULL, NULL, 'dorciam9@interia.pl', NULL, 0, NULL, 0, NULL),
(27, '11831358', '02745808E', '281501532957', 'Moncada', 'Bueno', 'Sebastián', '2007-11-09', 0, 28, NULL, NULL, 0, NULL, NULL, 'moncadaosoriodidier@gmail.com', NULL, NULL, 'osoriojuly@hotmail.com', NULL, 0, NULL, 0, NULL),
(28, '14675492', '51818089D', '281375971006', 'Nieto', 'Gómez', 'Daniel', '2006-05-07', 0, 28, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(29, '9854833', '55139532E', '281576636219', 'Orovio', 'Fernández', 'Adrián', '2006-04-21', 0, 28, NULL, NULL, 0, NULL, NULL, 'oroviosan@hotmail.com', NULL, NULL, 'anabascuana@gmail.com', NULL, 0, NULL, 0, NULL),
(30, '4545521', '49702188Q', '281444582641', 'Pascual', 'González', 'Alejandro', '1998-02-08', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(31, '9631236', '03489468T', '281462321618', 'Pérez', 'Alonso', 'Rubén', '2005-12-14', 130, 28, NULL, NULL, 0, NULL, NULL, 'hectorperezaparicio333@gmail.com', NULL, NULL, 'pilaralonso555@gmail.com', NULL, 0, NULL, 0, NULL),
(32, '12277735', '01867649A', '201055952475', 'Pérez', 'Marín', 'Rubén', '2008-07-12', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'luisamartincamara@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(33, '48532358', 'Z1978609Q', '281655816814', 'Pomarino', 'Mejía', 'Eduardo Gianfranco', '2005-03-25', NULL, 28, NULL, NULL, 0, NULL, NULL, 'carlascargglioni@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(34, '9019508', '49588427J', '281572721661', 'Puras', 'García', 'César', '2005-08-03', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'tonigarmar@hotmail.es', NULL, NULL, 'cespurn@hotmail.com', NULL, 0, NULL, 0, NULL),
(35, '11550457', '54993476Q', '281550184925', 'Rodríguez', 'Mateos', 'Adrián', '2007-07-26', 0, 28, NULL, NULL, 0, NULL, NULL, 'daromanad@gmail.com', NULL, NULL, 'natadriblan@gmail.com', NULL, 0, NULL, 0, NULL),
(36, '10859827', '01867899T', '281323189363', 'Rubio', 'Alonso', 'Antonio', '2007-12-18', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'antoniorubio963@gmail.com', NULL, NULL, 'mareamorada.6@gmail.com', NULL, 0, NULL, 0, NULL),
(37, '12197446', '54033379P', '281537763063', 'Ruiz', 'Blanca', 'Marcos', '2007-08-30', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'dimasruiz242@yahoo.es', NULL, NULL, 'oblancaplaza@yahoo.es', NULL, 0, NULL, 0, NULL),
(38, '13762278', '48161087F', '281560350424', 'Sánchez', 'Martín', 'Diego', '2008-01-04', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'albertorepor@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(39, '9758179', '49150194E', '281576429889', 'Sánchez', 'Palomar', 'Míriam', '2006-03-31', 130, 28, 5, '28943', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(40, '9854901', '49591353H', '281632191048', 'Sánchez', 'Sánchez', 'Adrián', '2006-12-20', 130, 28, NULL, NULL, 0, NULL, NULL, 'danisanguti@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(41, '10256695', '55004421J', '281677982021', 'Santamera', 'Gallego', 'Marcos', '2008-06-09', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'joseluissantamera@gmail.com', NULL, NULL, 'piluchigallego@gmail.com', NULL, 0, NULL, 0, NULL),
(42, '10602065', '02783538D', '281632217017', 'Serrano', 'Domínguez', 'Alejandro', '2006-05-25', 130, 28, NULL, NULL, 0, NULL, NULL, 'lolidomna@gmail.com', NULL, NULL, 'serrano.ema@gmail.com', NULL, 0, NULL, 0, NULL),
(43, '5136117', '55006498C', '281581105996', 'Tello', 'Horcajo', 'Sergio', '2007-05-20', 130, 28, NULL, NULL, 0, NULL, NULL, 'dtellosanchez@yahoo.com', NULL, NULL, 'rosahorcajo@yahoo.es', NULL, 0, NULL, 0, NULL),
(44, '12003501', '07137486B', '281524199433', 'Torre', 'Sánchez', 'Adrián de la', '2008-09-26', 0, 28, 1, '28903', 0, NULL, NULL, 'm.carmensanchez.s@hotmail.es', NULL, NULL, 'franciscotorrecasado@hotmail.es', NULL, 0, NULL, 0, NULL),
(45, '5630950', '53720005D', '281439037372', 'Tul', 'Tabango', 'Anderson Joel', '2004-10-21', 0, 28, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'germaniatabango@gmail.com', NULL, 0, NULL, 0, NULL),
(46, '10408261', '02560357C', '281618790395', 'Vega', 'Sánchez', 'Adrián', '2006-01-24', 0, 28, NULL, NULL, 0, NULL, NULL, 'rvhrvhrvh@gmail.com', NULL, NULL, 'monicascla@gmail.com', NULL, 0, NULL, 0, NULL),
(47, '9495357', '55139932P', '281572846650', 'Velázquez', 'Orgaz', 'Francisco', '2005-08-04', 130, 28, 1, '28901', 0, NULL, NULL, 'velazquezdavid11@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(48, '49939217', 'Y4274170W', '291122260517', 'Zhou', NULL, 'Chengwei', '2005-08-17', 0, 28, 6, '28971', 0, NULL, NULL, 'angelzhang1595@gmail.com', NULL, NULL, 'angelzhang1595@gmail.com', NULL, 0, NULL, 0, NULL),
(49, '11117384', '49585417Q', '281576810718', 'Alcolea', 'Villarta', 'Ainara', '2006-04-25', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'fmlyalcolea@gmail.com', NULL, NULL, 'fmlyalcolea@gmail.com', NULL, 0, NULL, 0, NULL),
(50, '11478652', '49592516P', '281671950540', 'Alonso', 'Pascual', 'Ramón', '2007-11-13', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'ramonalonsoprieto@gmail.com', NULL, NULL, 'mariaisabelpascualmartinez@gmail.com', NULL, 0, NULL, 0, NULL),
(51, '12714629', '01867079P', '281443440768', 'Amo', 'Hidalgo', 'David del', '2007-05-15', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'manuelamo2006@yahoo.es', NULL, NULL, 'marisa_hidalgo@yahoo.es', NULL, 0, NULL, 0, NULL),
(52, '12274635', '54501462H', '181087935330', 'Anaya', 'Usero', 'Diego', '2007-11-07', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'roalbea@hotmail.com', NULL, NULL, 'beatrizusero@hotmail.com', NULL, 0, NULL, 0, NULL),
(53, '1097597', '51722693V', '281506138639', 'Cortés', 'Ducuara', 'Santiago', '1999-01-19', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'isabelducuara@hotmail.com', NULL, NULL, 'fredyalexandercortes@hotmail.com', NULL, 0, NULL, 0, NULL),
(54, '10524954', '52028515P', '281578558940', 'Ferreras', 'López', 'Iker', '2006-09-07', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'elena0709@hotmail.es', NULL, NULL, 'calvin.klinex@hotmail.com', NULL, 0, NULL, 0, NULL),
(55, '10271605', '55004766J', '281576697045', 'Gil', 'Pantoja', 'Carlos', '2006-04-12', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'mercedespantoja27@gmail.com', NULL, NULL, 'juanjgil@hotmail.com', NULL, 0, NULL, 0, NULL),
(56, '42096535', 'Z1247979G', '281646154196', 'Gómez', 'García', 'Kyara Vanessa', '2007-06-13', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'karenferrera1981@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(57, '52284229', '54897389T', '281500211131', 'Gonzales', 'Arenas', 'Salvador Matías', '2002-01-31', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(58, '2493169', '50635124A', '281201549040', 'Herrera', 'Zamora', 'Álex Fabricio', '2003-08-18', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'veronicazamoram3@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(59, '52284946', 'Z1771728C', '281647323048', 'Jimenez', 'Arias', 'Sebastian Enrique', '2005-08-10', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(60, '52901522', '52186554Z', '281025742095', 'Martínez', 'Boza', 'Andrés', '1974-01-13', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(61, '12167333', '51495460R', '281338085937', 'Medina', 'Andrade', 'Cristian', '2007-02-14', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'cristina.andrade@hotmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(62, '11202110', '50576559L', '281638931235', 'Moreno', 'Moreno', 'José Luis', '2006-12-05', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'danijosemoreno@gmail.com', NULL, NULL, 'loremoredj@gmail.com', NULL, 0, NULL, 0, NULL),
(63, '11891536', '53804552P', '281402816663', 'Muñoz', 'Espinoza', 'Víctor Ángel', '2007-12-30', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'elmermunhoz@hotmail.com', NULL, NULL, 'selenaesp2015@gmail.com', NULL, 0, NULL, 0, NULL),
(64, '11839873', '53808524R', '281669000124', 'Ortega', 'Gómez', 'Hugo', '2007-07-08', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'robertocasona@gmail.com', NULL, NULL, 'karinagomez230@gmail.com', NULL, 0, NULL, 0, NULL),
(65, '10282250', '48160044E', '281657660016', 'Parra', 'González', 'Rodrigo', '2006-08-21', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'anatorrejon69@gmail.com', NULL, 0, NULL, 0, NULL),
(66, '10177020', '54399160C', '281327939030', 'Pavón', 'Sánchez', 'Ismael', '2007-11-16', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'javipavon5@hotmail.com', NULL, NULL, 'estherst590@gmail.com', NULL, 0, NULL, 0, NULL),
(67, '11717294', '53807118K', '281639966610', 'Peinado', 'Valdivieso', 'Juan Carlos', '2007-11-17', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'mpeinado@gmail.com', NULL, NULL, 'merce.valdivieso.r@gmail.com', NULL, 0, NULL, 0, NULL),
(68, '10096185', '04675241P', '281631995735', 'Peña', 'Redondo', 'Álvaro', '2007-06-15', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'miguel66p@hotmail.com', NULL, NULL, 'belenredondo1971@gmail.com', NULL, 0, NULL, 0, NULL),
(69, '11116615', '54730438Y', '281580172776', 'Peral', 'Hurtado', 'Héctor', '2007-03-11', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'fperal@gmail.com', NULL, NULL, 'hurtadoi@gmail.com', NULL, 0, NULL, 0, NULL),
(70, '20095963', 'Y2087021S', NULL, 'Pérez', 'Figueroa', 'Rodrigo Elías', '2003-01-13', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(71, '10485354', '02755044N', '281632019579', 'Pérez', 'Torrijos', 'María', '2006-09-21', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'gema.torri@hotmail.com', NULL, NULL, 'josema.pepe@hotmail.com', NULL, 0, NULL, 0, NULL),
(72, '10245422', '50570565M', '281580539962', 'Rodríguez', 'Alda', 'Víctor', '2007-04-06', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'jrdorado1@hotmail.com', NULL, NULL, 'sonia_alda@hotmail.com', NULL, 0, NULL, 0, NULL),
(73, '9741553', '54419518T', '281632109711', 'Rodríguez', 'Jiménez', 'Tomás', '2006-04-22', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'josefrb22@hotmail.com', NULL, NULL, 'al-mofrag@gmail.com', NULL, 0, NULL, 0, NULL),
(74, '10525548', '49747330D', '281578485481', 'San Florencio', 'Ruiz', 'Jesús', '2006-08-25', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'jmsanflorencio@gmail.com', NULL, NULL, 'conchi.ruiz.ruiz@gmail.com', NULL, 0, NULL, 0, NULL),
(75, '11433286', '54033549V', '281579511358', 'Solera', 'Moreno', 'Sara', '2006-11-16', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'solerajoseluis@gmail.com', NULL, NULL, 'mg55rosa@gmail.com', NULL, 0, NULL, 0, NULL),
(76, '9991439', '48161797G', '281650303574', 'Úbeda', 'López', 'Álvaro', '2006-09-06', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'e.ubeda@ubedacal.com', NULL, NULL, 'maribel15243@gmail.com', NULL, 0, NULL, 0, NULL),
(77, '11031093', '51552896Y', '281502986543', 'Ucendo', 'Plaza', 'David', '2006-12-19', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'ucenrm@gmail.com', NULL, NULL, 'plazanur@gmail.com', NULL, 0, NULL, 0, NULL),
(78, '52286490', 'AAF692961', '181087908149', 'Vallejos', NULL, 'Sol Micaela', '2001-07-04', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(79, '14895289', '46859646K', '281171433166', 'Alemán', 'de Cruz', 'Iván', '1984-10-23', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(80, '1834666', '54992004Q', '281332894316', 'Amarouch', 'El Mattichi', 'Kamal', '2005-12-03', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'amarouch452015@gmail.com', NULL, NULL, 'amarouch452015@gmail.com', NULL, 0, NULL, 0, NULL),
(81, '3468180', '47312412D', '281644763157', 'Aspano', 'Garrido', 'Hugo', '2005-02-25', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'isidro.aspano@gmail.com', NULL, NULL, 'marisa.garridoruiz@gmail.com', NULL, 0, NULL, 0, NULL),
(82, '14043826', '55581074X', '281590125279', 'Benrahal', 'Elmotai', 'Adam', '2006-05-14', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'abderrahimbenrahal1973@gmail.com', NULL, NULL, 'naimaelmotai6@gmail.com', NULL, 0, NULL, 0, NULL),
(83, '48254830', '54936090S', '281631264191', 'Buitrago', 'Márquez', 'Carla', '2007-09-26', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'luigbufu@gmail.com', NULL, NULL, 'urizen@hotmail.es', NULL, 0, NULL, 0, NULL),
(84, '11970514', '54932151D', NULL, 'Carral', 'Díaz', 'Nerea', '2007-05-02', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'mdiazca@gmal.com', NULL, NULL, 'antomio.carral@vodafone.com', NULL, 0, NULL, 0, NULL),
(85, '7123818', '47311827E', '281368659529', 'Chaparro', 'Cancelos', 'Arianne', '1995-09-20', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(86, '140423', '51720431D', '281494784585', 'Díaz', 'Martos', 'Alejandro', '2004-05-17', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'estebandiazg@hotmail.com', NULL, NULL, 'solecillas@gmail.com', NULL, 0, NULL, 0, NULL),
(87, '10060551', '11873255B', '281666297359', 'Gómez', 'López', 'Noel', '2006-09-17', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'd.gomez.ramirez.1@gmail.com', NULL, NULL, 'belen.morillo72@gmail.com', NULL, 0, NULL, 0, NULL),
(88, '45694776', 'Y9816200K', '281624536839', 'Hernández', 'González', 'Ángel David', '2007-03-10', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'iniridagonzalez@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(89, '6573072', '50358792Q', '281669211403', 'Hernando', 'Rodríguez', 'Iván', '2007-07-05', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'lrodriguezmarques75@gmail.com', NULL, NULL, 'info@aulamobel.com', NULL, 0, NULL, 0, NULL),
(90, '10566596', '54729825Z', '281523007040', 'Lahoz', 'Rodríguez', 'Álvaro', '2006-03-22', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'sn.naran@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(91, '11820338', '50632404C', '281356890803', 'Lascano', 'Aguirre', 'Luis Fernando', '2007-03-28', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'carmen2211aguirre@gmail.com', NULL, 0, NULL, 0, NULL),
(92, '11778868', '54500068G', '281503893390', 'López', 'García', 'Álvaro', '2007-10-17', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'dlsyayn@hotmail.es', NULL, NULL, 'lauragf2678176@gmail.com', NULL, 0, NULL, 0, NULL),
(93, '6002541', '02748498K', '281625945056', 'Lorente', 'Cortés', 'Aarón', '2005-04-17', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'mario84.lc@gmail.com', NULL, NULL, 'mcortesbravo@gmail.com', NULL, 0, NULL, 0, NULL),
(94, '11560197', '03173765H', '281580452258', 'Marcos', 'de la Fuente', 'Héctor de', '2007-03-27', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'monicadfj@yahoo.es', NULL, NULL, 'sergiodme@yahoo.es', NULL, 0, NULL, 0, NULL),
(95, '9435551', '49453525Y', '281566382814', 'Mas', 'Herrera', 'Daniel', '2003-10-16', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(96, '11418948', '45186077R', '281579004130', 'Muñoz', 'Carrasco', 'Sergio', '2006-10-05', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'gemasergio06@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(97, '22578631', '53465221L', '281197521419', 'Ojeda', 'Cantero', 'Antonio', '1986-03-14', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(98, '13513627', '03959551D', '451027137201', 'Orellana', 'Torrico', 'Edson', '2007-12-08', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'luciatorrico77@gmail.com', NULL, NULL, 'jcbony_222@hotmail.com', NULL, 0, NULL, 0, NULL),
(99, '10324127', '54032358E', '281631961480', 'Ortiz', 'Martín', 'Adrián', '2007-09-30', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'familia.ortiz.martin.000@gmail.com', NULL, NULL, 'familia.ortiz.martin.000@gmail.com', NULL, 0, NULL, 0, NULL),
(100, '12180059', '35719115T', '281649064705', 'Pop', 'Avram', 'Raúl Alejandro', '2007-08-20', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'ankutzica81@yahoo.es', NULL, NULL, 'ciprianpop80@yahoo.es', NULL, 0, NULL, 0, NULL),
(101, '12240913', '01866793K', '281632122239', 'Rodríguez-Bobada', 'Albarrán', 'Marcos', '2007-02-01', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(102, '47194472', '0AAG695268', '281632162251', 'Salime', NULL, 'Thiago Samuel', '2006-01-23', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'danielavizcarra777@gmail.com', NULL, NULL, 'psalime777.ps@gmail.com', NULL, 0, NULL, 0, NULL),
(103, '48284837', 'LE58923', '281660786547', 'Sánchez', 'Cortez', 'Luis Santiago', '2004-02-01', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'sandracortez1970@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(104, '10025154', '02557319H', '281669107127', 'Sánchez', 'Nicolás', 'Alejandro', '2007-01-20', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'angel071@hotmail.es', NULL, NULL, 'niki2mai@yahoo.com', NULL, 0, NULL, 0, NULL),
(105, '10248058', '54730380V', '281665214494', 'Santamaría', 'Hernández', 'Álvaro', '2007-01-03', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'raquelalvaro77@gmail.com', NULL, 0, NULL, 0, NULL),
(106, '10378434', '55065167Q', '281613291105', 'Santos', 'Jiménez', 'Carlos', '2006-01-11', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'pirata.pareja@gmail.com', NULL, NULL, 'anaijk@gmail.com', NULL, 0, NULL, 0, NULL),
(107, '6000417', '55138942F', NULL, 'Torremocha', 'Cantera', 'Mikel', '2006-02-15', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'torremochin@hotmail.com', NULL, NULL, 'judit_cgarcia@hotmail.com', NULL, 0, NULL, 0, NULL),
(108, '10468296', '02789683J', '281476306994', 'Vélez', 'Mora', 'Víctor', '2005-12-14', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'juanmavelez1407@gmail.com', NULL, 0, NULL, 0, NULL),
(109, '2172316', '51819236Y', '281523294303', 'Aranega', 'Fernández', 'José', '2005-07-21', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'charofernandez1110@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(110, '8859242', '18560019P', '511006055866', 'Belhachmi', 'Ouaissa', 'Laila', '2006-10-21', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'najatmadrid2021@gmail.com', NULL, NULL, 'fouadbelhachmi@gmail.com', NULL, 0, NULL, 0, NULL),
(111, '11970484', '54524782Q', '281503768910', 'Benhar', 'Katrissi', 'Sami', '2007-08-25', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'abdel.afak@gmail.com', NULL, 0, NULL, 0, NULL),
(112, '11005544', '48159751M', '281631298143', 'Cárdenas', 'Cambrón', 'Aníbal', '2006-11-03', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(113, '12179282', '35682045Y', '281580127007', 'Cerrato', 'Martínez', 'Silvia', '2007-03-06', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(114, '12304141', '12815117T', '281319659068', 'El Mourabit', 'El Mourabit', 'Doha', '2007-06-21', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'fadmaelmourabit75@gmail.com', NULL, NULL, 'moralplantio@gmail.com', NULL, 0, NULL, 0, NULL),
(115, '10461495', '49594362Z', '281576586204', 'Gallego', 'Merino', 'Iván', '2006-04-16', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'francisco.gallego.perea@gmail.com', NULL, NULL, 'raquel.merino@hotmail.es', NULL, 0, NULL, 0, NULL),
(116, '10543610', '54995863B', '281631549939', 'Garrido', 'Martín', 'Cristian', '2007-08-03', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'crizan07@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(117, '8943484', '50560483C', '281414954801', 'Golu', 'Blázquez', 'Alejandro', '2004-04-28', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'silviablamo@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(118, '12132782', '54665072Y', '281348842126', 'Gomes', 'Mendes', 'Benvindo Alberto', '2007-12-21', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'mendesalberto1267@gmail.com', NULL, NULL, 'monica45gm@gmail.com', NULL, 0, NULL, 0, NULL),
(119, '10872239', '04269483Q', '281669362660', 'Gonzalo', 'Hernández', 'Natalia', '2006-11-30', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'chaminatomas@hotmail.com', NULL, NULL, 'nataliamirari@gmail.com', NULL, 0, NULL, 0, NULL),
(120, '11965466', '54730305B', NULL, 'Guardia', 'Íñigo', 'Iker', '2007-09-04', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(121, '9755475', '53906694F', NULL, 'Hernández', 'Ferrer', 'Juan', '2006-12-16', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'elenaferrerruiz@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(122, '10454435', '51496982M', '281580545016', 'Livia', 'García', 'Ángel', '2007-04-11', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'jhonlivia@yahoo.com', NULL, NULL, 'maryjgf@yahoo.com', NULL, 0, NULL, 0, NULL),
(123, '10938744', '54993144Y', '281576372400', 'Márquez', 'Tena', 'Ruth', '2006-03-21', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'marquezrobledo@hotmail.com', NULL, NULL, 'layolitena@gmail.com', NULL, 0, NULL, 0, NULL),
(124, '10674338', '03176456H', '281669382464', 'Martínez', 'García', 'Mario', '2007-03-28', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'cycchus@gmail.com', NULL, NULL, 'cyccarmen@gmail.com', NULL, 0, NULL, 0, NULL),
(125, '46800527', 'Z3092534M', NULL, 'Melo', 'Arias', 'David Alexander', '2006-08-23', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'ladyarias732@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(126, '11923374', '48157776P', '281391474333', 'Morante', 'Perales', 'Iván', '2007-12-27', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'fraitamar@gmail.com', NULL, NULL, 'fraitamar@gmail.com', NULL, 0, NULL, 0, NULL),
(127, '11600602', '48161795W', '281570994051', 'Moreno', 'Montero', 'Adrián', '2005-03-17', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(128, '7184543', '01867236G', '281636039928', 'Navarro', 'Juguera', 'Adrian', '2005-12-19', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'm.jugueragomez@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(129, '9667853', '53476044D', '281638986708', 'Olivares', 'Essaken', 'Soraya Victoria', '2003-12-12', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'leandroasaq@gmail.com', NULL, NULL, 'jamila.essa.sal@gmail.com', NULL, 0, NULL, 0, NULL),
(130, '5871544', '47296898C', '281537738714', 'Pardo', 'Aparicio', 'John Alexander', '2005-03-20', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'jairopardo1757@gmail.com', NULL, NULL, 'liliaapariciocaballero@gmail.com', NULL, 0, NULL, 0, NULL),
(131, '11946502', '06035076Z', '281580389917', 'Pescador', 'Sesmero', 'Iker', '2007-03-31', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'rodasep79@gmail.com', NULL, NULL, 'anases31@gmail.com', NULL, 0, NULL, 0, NULL),
(132, '86394', '02303816K', '281567044939', 'Segovia', 'Sierra', 'Sara', '2004-02-01', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'maxegovia@gmail.com', NULL, NULL, 'mipisisa@gmail.com', NULL, 0, NULL, 0, NULL),
(133, '7875328', '50259560Y', '281566859427', 'Utrero', 'Durán', 'Carlos', '2004-01-29', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'nicoutrero1971@gmail.com', NULL, NULL, 'beatdurman78@gmail.com', NULL, 0, NULL, 0, NULL),
(134, '10166574', '53804101V', '281632309367', 'Velázquez', 'Durán', 'Yaiza', '2007-09-21', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'vampigoyo@hotmail.com', NULL, NULL, 'vampigoyo@hotmail.com', NULL, 0, NULL, 0, NULL),
(135, '14130021', 'X8288994R', NULL, 'Zhou', NULL, 'Junjie', '2006-08-01', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'zhouyn119@gmail.com', NULL, NULL, 'zhouyn119@gmail.com', NULL, 0, NULL, 0, NULL);

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

--
-- Volcado de datos para la tabla `alumno_curso`
--

INSERT INTO `alumno_curso` (`id_alumno`, `id_curso_escolar`, `id_nivel`, `id_ciclo`, `id_curso`, `id_grupo`) VALUES
(1, 1, 1, 1, 2, 4),
(2, 1, 1, 1, 2, 4),
(3, 1, 1, 1, 2, 3),
(4, 1, 1, 1, 2, 3),
(5, 1, 1, 1, 2, 3),
(6, 1, 1, 1, 2, 3),
(7, 1, 1, 1, 2, 3),
(8, 1, 1, 1, 2, 3),
(9, 1, 1, 1, 2, 3),
(10, 1, 1, 1, 2, 3),
(11, 1, 1, 1, 2, 4),
(12, 1, 1, 1, 2, 3),
(13, 1, 1, 1, 2, 3),
(14, 1, 1, 1, 2, 3),
(15, 1, 1, 1, 2, 3),
(16, 1, 1, 1, 2, 4),
(17, 1, 1, 1, 2, 4),
(18, 1, 1, 1, 2, 3),
(19, 1, 1, 1, 2, 3),
(20, 1, 1, 1, 2, 3),
(21, 1, 1, 1, 2, 3),
(22, 1, 1, 1, 2, 3),
(23, 1, 1, 1, 2, 4),
(24, 1, 1, 1, 2, 4),
(25, 1, 1, 1, 2, 4),
(26, 1, 1, 1, 2, 4),
(27, 1, 1, 1, 2, 4),
(28, 1, 1, 1, 2, 4),
(29, 1, 1, 1, 2, 4),
(30, 1, 1, 1, 2, 4),
(31, 1, 1, 1, 2, 4),
(32, 1, 1, 1, 2, 4),
(33, 1, 1, 1, 2, 4),
(34, 1, 1, 1, 2, 4),
(35, 1, 1, 1, 2, 4),
(36, 1, 1, 1, 2, 3),
(37, 1, 1, 1, 2, 4),
(38, 1, 1, 1, 2, 3),
(39, 1, 1, 1, 2, 4),
(40, 1, 1, 1, 2, 4),
(41, 1, 1, 1, 2, 3),
(42, 1, 1, 1, 2, 4),
(43, 1, 1, 1, 2, 4),
(44, 1, 1, 1, 2, 4),
(45, 1, 1, 1, 2, 4),
(46, 1, 1, 1, 2, 4),
(47, 1, 1, 1, 2, 4),
(48, 1, 1, 1, 2, 4),
(49, 1, 2, 2, 1, 5),
(50, 1, 2, 2, 1, 5),
(51, 1, 2, 2, 1, 5),
(52, 1, 2, 2, 1, 5),
(53, 1, 2, 2, 1, 5),
(54, 1, 2, 2, 1, 5),
(55, 1, 2, 2, 1, 5),
(56, 1, 2, 2, 1, 5),
(57, 1, 2, 2, 1, 5),
(58, 1, 2, 2, 1, 5),
(59, 1, 2, 2, 1, 5),
(60, 1, 2, 2, 1, 5),
(61, 1, 2, 2, 1, 5),
(62, 1, 2, 2, 1, 5),
(63, 1, 2, 2, 1, 5),
(64, 1, 2, 2, 1, 5),
(65, 1, 2, 2, 1, 5),
(66, 1, 2, 2, 1, 5),
(67, 1, 2, 2, 1, 5),
(68, 1, 2, 2, 1, 5),
(69, 1, 2, 2, 1, 5),
(70, 1, 2, 2, 1, 5),
(71, 1, 2, 2, 1, 5),
(72, 1, 2, 2, 1, 5),
(73, 1, 2, 2, 1, 5),
(74, 1, 2, 2, 1, 5),
(75, 1, 2, 2, 1, 5),
(76, 1, 2, 2, 1, 5),
(77, 1, 2, 2, 1, 5),
(78, 1, 2, 2, 1, 5),
(79, 1, 2, 3, 1, 7),
(80, 1, 2, 3, 1, 7),
(81, 1, 2, 3, 1, 7),
(82, 1, 2, 3, 1, 7),
(83, 1, 2, 3, 1, 7),
(84, 1, 2, 3, 1, 7),
(85, 1, 2, 3, 1, 7),
(86, 1, 2, 3, 1, 7),
(87, 1, 2, 3, 1, 7),
(88, 1, 2, 3, 1, 7),
(89, 1, 2, 3, 1, 7),
(90, 1, 2, 3, 1, 7),
(91, 1, 2, 3, 1, 7),
(92, 1, 2, 3, 1, 7),
(93, 1, 2, 3, 1, 7),
(94, 1, 2, 3, 1, 7),
(95, 1, 2, 3, 1, 7),
(96, 1, 2, 3, 1, 7),
(97, 1, 2, 3, 1, 7),
(98, 1, 2, 3, 1, 7),
(99, 1, 2, 3, 1, 7),
(100, 1, 2, 3, 1, 7),
(101, 1, 2, 3, 1, 7),
(102, 1, 2, 3, 1, 7),
(103, 1, 2, 3, 1, 7),
(104, 1, 2, 3, 1, 7),
(105, 1, 2, 3, 1, 7),
(106, 1, 2, 3, 1, 7),
(107, 1, 2, 3, 1, 7),
(108, 1, 2, 3, 1, 7),
(109, 1, 2, 4, 1, 9),
(110, 1, 2, 4, 1, 9),
(111, 1, 2, 4, 1, 9),
(112, 1, 2, 4, 1, 9),
(113, 1, 2, 4, 1, 9),
(114, 1, 2, 4, 1, 9),
(115, 1, 2, 4, 1, 9),
(116, 1, 2, 4, 1, 9),
(117, 1, 2, 4, 1, 9),
(118, 1, 2, 4, 1, 9),
(119, 1, 2, 4, 1, 9),
(120, 1, 2, 4, 1, 9),
(121, 1, 2, 4, 1, 9),
(122, 1, 2, 4, 1, 9),
(123, 1, 2, 4, 1, 9),
(124, 1, 2, 4, 1, 9),
(125, 1, 2, 4, 1, 9),
(126, 1, 2, 4, 1, 9),
(127, 1, 2, 4, 1, 9),
(128, 1, 2, 4, 1, 9),
(129, 1, 2, 4, 1, 9),
(130, 1, 2, 4, 1, 9),
(131, 1, 2, 4, 1, 9),
(132, 1, 2, 4, 1, 9),
(133, 1, 2, 4, 1, 9),
(134, 1, 2, 4, 1, 9),
(135, 1, 2, 4, 1, 9);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumno_modulo`
--

CREATE TABLE `alumno_modulo` (
  `id_alumno` int(10) UNSIGNED NOT NULL,
  `id_modulo` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumno_modulo`
--

INSERT INTO `alumno_modulo` (`id_alumno`, `id_modulo`) VALUES
(2, 11),
(2, 14),
(2, 15),
(2, 16),
(2, 17),
(2, 18),
(3, 9),
(4, 9),
(5, 9),
(6, 9),
(7, 9),
(8, 9),
(9, 9),
(10, 9),
(11, 8),
(11, 9),
(11, 10),
(11, 11),
(11, 12),
(11, 13),
(11, 14),
(11, 15),
(11, 16),
(11, 17),
(11, 18),
(12, 9),
(13, 9),
(14, 9),
(17, 11),
(18, 9),
(19, 9),
(20, 9),
(21, 9),
(22, 9),
(23, 10),
(23, 11),
(23, 14),
(23, 15),
(23, 16),
(23, 17),
(23, 18),
(24, 8),
(24, 9),
(24, 10),
(24, 11),
(24, 12),
(24, 13),
(24, 14),
(24, 15),
(24, 16),
(24, 17),
(24, 18),
(25, 8),
(25, 9),
(25, 10),
(25, 11),
(25, 12),
(25, 13),
(25, 14),
(25, 15),
(25, 16),
(25, 17),
(25, 18),
(26, 8),
(26, 9),
(26, 10),
(26, 11),
(26, 12),
(26, 13),
(26, 14),
(26, 15),
(26, 16),
(26, 17),
(26, 18),
(27, 8),
(27, 9),
(27, 10),
(27, 11),
(27, 12),
(27, 13),
(27, 14),
(27, 15),
(27, 16),
(27, 17),
(27, 18),
(28, 8),
(28, 9),
(28, 10),
(28, 11),
(28, 12),
(28, 13),
(28, 14),
(28, 15),
(28, 16),
(28, 17),
(28, 18),
(29, 8),
(29, 10),
(29, 11),
(29, 14),
(29, 15),
(29, 16),
(29, 17),
(29, 18),
(30, 8),
(30, 10),
(30, 11),
(30, 14),
(30, 15),
(30, 16),
(30, 17),
(30, 18),
(32, 8),
(32, 9),
(32, 10),
(32, 11),
(32, 12),
(32, 13),
(32, 14),
(32, 15),
(32, 16),
(32, 17),
(32, 18),
(33, 8),
(33, 9),
(33, 10),
(33, 11),
(33, 12),
(33, 13),
(33, 14),
(33, 15),
(33, 16),
(33, 17),
(33, 18),
(34, 8),
(34, 9),
(34, 10),
(34, 11),
(34, 12),
(34, 13),
(34, 14),
(34, 15),
(34, 16),
(34, 17),
(34, 18),
(35, 8),
(35, 10),
(35, 11),
(35, 14),
(35, 15),
(35, 16),
(35, 17),
(35, 18),
(36, 9),
(37, 8),
(37, 9),
(37, 10),
(37, 11),
(37, 12),
(37, 13),
(37, 14),
(37, 15),
(37, 16),
(37, 17),
(37, 18),
(38, 9),
(39, 8),
(39, 9),
(39, 10),
(39, 11),
(39, 12),
(39, 13),
(39, 14),
(39, 15),
(39, 16),
(39, 17),
(39, 18),
(41, 9),
(42, 11),
(43, 11),
(44, 8),
(44, 9),
(44, 10),
(44, 11),
(44, 12),
(44, 13),
(44, 14),
(44, 15),
(44, 16),
(44, 17),
(44, 18),
(45, 8),
(45, 10),
(45, 11),
(45, 14),
(45, 15),
(45, 16),
(45, 17),
(45, 18),
(46, 11),
(46, 13),
(46, 14),
(46, 15),
(46, 16),
(46, 17),
(46, 18),
(47, 8),
(47, 9),
(47, 10),
(47, 11),
(47, 12),
(47, 13),
(47, 14),
(47, 15),
(47, 16),
(47, 17),
(47, 18),
(48, 8),
(48, 9),
(48, 10),
(48, 11),
(48, 12),
(48, 13),
(48, 14),
(48, 15),
(48, 16),
(48, 17),
(48, 18),
(49, 22),
(50, 22),
(51, 22),
(52, 22),
(54, 22),
(56, 22),
(57, 22),
(58, 22),
(59, 22),
(60, 22),
(61, 22),
(62, 22),
(63, 22),
(64, 22),
(65, 22),
(66, 22),
(67, 22),
(68, 22),
(69, 22),
(70, 22),
(71, 22),
(72, 22),
(73, 22),
(74, 22),
(75, 22),
(76, 22),
(78, 22),
(79, 64),
(80, 64),
(81, 64),
(82, 64),
(83, 64),
(84, 64),
(85, 64),
(86, 64),
(87, 64),
(88, 64),
(89, 64),
(90, 64),
(91, 64),
(92, 64),
(93, 64),
(94, 64),
(95, 64),
(96, 64),
(97, 64),
(98, 64),
(99, 64),
(100, 64),
(101, 64),
(102, 64),
(104, 64),
(105, 64),
(106, 64),
(111, 61),
(112, 61),
(113, 61),
(114, 61),
(115, 61),
(116, 61),
(117, 61),
(118, 61),
(119, 61),
(120, 61),
(121, 61),
(122, 61),
(123, 61),
(124, 61),
(125, 61),
(126, 61),
(129, 61),
(130, 61),
(131, 61),
(132, 61),
(133, 61),
(134, 61),
(135, 61);

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
  `abreviatura` varchar(60) NOT NULL,
  `codigo` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ciclos`
--

INSERT INTO `ciclos` (`id_ciclo`, `ciclo`, `abreviatura`, `codigo`) VALUES
(1, 'Sistemas Microinformáticos y Redes', 'SMR', 'IFCM01'),
(2, 'Administración de Sistemas Informáticos en Red', 'ASIR', 'IFCS01'),
(3, 'Desarrollo de Aplicaciones Multiplataforma', 'DAM', 'IFCS02'),
(4, 'Desarrollo de Aplicaciones Web', 'DAW', 'IFCS03');

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

--
-- Volcado de datos para la tabla `correos`
--

INSERT INTO `correos` (`id_correo`, `entidad_tipo`, `id_entidad`, `direccion_correo`, `etiqueta`) VALUES
(1, 'alumno', 1, 'andreablascogata@gmail.com', 'Personal'),
(2, 'alumno', 2, 'springon14@gmail.com', 'Personal'),
(3, 'alumno', 3, 'aaronbontras@gmail.com', 'Personal'),
(4, 'alumno', 4, 'jossborda16@gmail.com', 'Personal'),
(5, 'alumno', 5, 'ivancanadag@gmail.com', 'Personal'),
(6, 'alumno', 6, 'alejandrocasasalvarez@gmail.com', 'Personal'),
(7, 'alumno', 7, 'ccse360@gmail.com', 'Personal'),
(8, 'alumno', 8, 'samuelcazun734@gmail.com', 'Personal'),
(9, 'alumno', 9, 'cortesgallegodiego@gmail.com', 'Personal'),
(10, 'alumno', 10, 'xenobendy457@gmail.com', 'Personal'),
(11, 'alumno', 11, 'gabrielrscalada@gmail.com', 'Personal'),
(12, 'alumno', 12, 'robertgallardoperez11@gmail.com', 'Personal'),
(13, 'alumno', 13, 'alejandrogarciabueno1@gmail.com', 'Personal'),
(14, 'alumno', 14, 'mariodeacal2007@gmail.com', 'Personal'),
(15, 'alumno', 15, 'r.garnacho.linares@gmail.com', 'Personal'),
(16, 'alumno', 16, 'jpabloga14@gmail.com', 'Personal'),
(17, 'alumno', 17, 'rgonzalezalcocer@gmail.com', 'Personal'),
(18, 'alumno', 18, 'itzan878@gmail.com', 'Personal'),
(19, 'alumno', 19, 'jorgegutierrezlucio@gmail.com', 'Personal'),
(20, 'alumno', 20, 'dariohpmovil@gmail.com', 'Personal'),
(21, 'alumno', 21, 'wisdom.ikubor@myyahoo.com', 'Personal'),
(22, 'alumno', 23, 'violentt22@gmail.com', 'Personal'),
(23, 'alumno', 24, 'danielmangas0678@gmail.com', 'Personal'),
(24, 'alumno', 25, 'marionartinleon95@gmail.com', 'Personal'),
(25, 'alumno', 26, 'xsaomigo@gmail.com', 'Personal'),
(26, 'alumno', 27, 'moncadabuenos@gmail.com', 'Personal'),
(27, 'alumno', 28, 'daniel.nieto.gomez13@gmail.com', 'Personal'),
(28, 'alumno', 29, 'adrianorovios@gmail.com', 'Personal'),
(29, 'alumno', 30, 'pasculini98@gmail.com', 'Personal'),
(30, 'alumno', 31, 'rubenproby@gmail.com', 'Personal'),
(31, 'alumno', 32, 'rubenpm120708@gmail.com', 'Personal'),
(32, 'alumno', 33, 'julipi.sf@gmail.com', 'Personal'),
(33, 'alumno', 34, 'cesarpurasgar@gmail.com', 'Personal'),
(34, 'alumno', 35, 'adriroma2007@gmail.com', 'Personal'),
(35, 'alumno', 36, 'antoniorubioalonso8@gmail.com', 'Personal'),
(36, 'alumno', 37, 'marcosruizblanca1@gmail.com', 'Personal'),
(37, 'alumno', 38, 'diego.sm412008@gmail.com', 'Personal'),
(38, 'alumno', 39, 'miriamsp2006@gmail.com', 'Personal'),
(39, 'alumno', 40, 'adrisanchez.ps4@gmail.com', 'Personal'),
(40, 'alumno', 41, 'marcossantamera@gmail.com', 'Personal'),
(41, 'alumno', 42, 'alexsara0609@gmail.com', 'Personal'),
(42, 'alumno', 43, 'stellohorcajo@gmail.com', 'Personal'),
(43, 'alumno', 44, 'adricelta12@gmail.com', 'Personal'),
(44, 'alumno', 45, 'andersontul456@gmail.com', 'Personal'),
(45, 'alumno', 46, 'adrian.vega.smr@gmail.com', 'Personal'),
(46, 'alumno', 47, 'fvelazquezorgaz13@gmail.com', 'Personal'),
(47, 'alumno', 48, 'chengweizhou1@gmail.com', 'Personal'),
(48, 'alumno', 49, 'ainara.alc.vil@gmail.com', 'Personal'),
(49, 'alumno', 50, 'ramonalonsopascual@gmail.com', 'Personal'),
(50, 'alumno', 51, 'david.delamohidalgo@gmail.com', 'Personal'),
(51, 'alumno', 52, 'd.anayausero@gmail.com', 'Personal'),
(52, 'alumno', 53, 'cortesducuarasantiago@gmail.com', 'Personal'),
(53, 'alumno', 54, 'ferreraslopeziker@gmail.com', 'Personal'),
(54, 'alumno', 55, 'caarloosgil12@gmail.com', 'Personal'),
(55, 'alumno', 56, 'kyaravanessa2017@gmail.com', 'Personal'),
(56, 'alumno', 57, 'salvadormga@hotmail.com', 'Personal'),
(57, 'alumno', 58, 'alex.fabricio.h@gmail.com', 'Personal'),
(58, 'alumno', 59, 'jasebastian_73@gmail.com', 'Personal'),
(59, 'alumno', 60, 'andresmboza@yahoo.es', 'Personal'),
(60, 'alumno', 61, 'cristianmedinaandrade@outlook.com', 'Personal'),
(61, 'alumno', 62, 'joselumoreno24@gmail.com', 'Personal'),
(62, 'alumno', 63, 'victor.munoz22@educa.madrid.org', 'Personal'),
(63, 'alumno', 64, 'hugo.ortg87@gmail.com', 'Personal'),
(64, 'alumno', 65, 'parra2006rpg21@gmail.com', 'Personal'),
(65, 'alumno', 66, 'ismaelst8516@gmail.com', 'Personal'),
(66, 'alumno', 67, 'jcarlospv07@gmail.com', 'Personal'),
(67, 'alumno', 68, 'alvaropenaredondo219@gmail.com', 'Personal'),
(68, 'alumno', 69, 'hperal07@gmail.com', 'Personal'),
(69, 'alumno', 70, 'perez.figueroa.rodrigo@gmail.com', 'Personal'),
(70, 'alumno', 71, 'mariapereztorrijos1@gmail.com', 'Personal'),
(71, 'alumno', 72, 'vrodriguezalda@gmail.com', 'Personal'),
(72, 'alumno', 73, 'tomasrj22@gmail.com', 'Personal'),
(73, 'alumno', 74, 'jsfr2006@gmail.com', 'Personal'),
(74, 'alumno', 75, 'sarasolera16@gmail.com', 'Personal'),
(75, 'alumno', 76, 'alvaroubedaa@icloud.com', 'Personal'),
(76, 'alumno', 77, 'daviducendop@gmail.com', 'Personal'),
(77, 'alumno', 78, 'sol.vallejos65@gmail.com', 'Personal'),
(78, 'alumno', 79, 'adc.ivan22@gmail.com', 'Personal'),
(79, 'alumno', 80, 'kamalamarouch3@gmail.com', 'Personal'),
(80, 'alumno', 81, 'hugo.aspanogarrido@gmail.com', 'Personal'),
(81, 'alumno', 82, 'adambenrahal250@gmail.com', 'Personal'),
(82, 'alumno', 83, 'carlabtrg@gmail.com', 'Personal'),
(83, 'alumno', 84, 'ncarraldiaz@gmail.com', 'Personal'),
(84, 'alumno', 85, 'arianne20091995@gmail.com', 'Personal'),
(85, 'alumno', 86, 'alexdiazmartos@gmail.com', 'Personal'),
(86, 'alumno', 87, 'lopez.noel2006@gmail.com', 'Personal'),
(87, 'alumno', 88, 'angeldavidhernandezgonzalez3@gmail.com', 'Personal'),
(88, 'alumno', 89, 'ivan2007hr@gmail.com', 'Personal'),
(89, 'alumno', 90, 'alvaarolahoz@gmail.com', 'Personal'),
(90, 'alumno', 91, 'lascanoluis79@gmail.com', 'Personal'),
(91, 'alumno', 92, 'alg17109876@gmail.com', 'Personal'),
(92, 'alumno', 93, 'aaron.lorentecortes05@gmail.com', 'Personal'),
(93, 'alumno', 94, 'hectordemarcosdelafuente@gmail.com', 'Personal'),
(94, 'alumno', 95, 'masherreradaniel@gmail.com', 'Personal'),
(95, 'alumno', 96, 'sergimunoz0506@gmail.com', 'Personal'),
(96, 'alumno', 97, 'aoc530@educa.madrid.org', 'Personal'),
(97, 'alumno', 98, 'orellanatorricoedson@gmail.com', 'Personal'),
(98, 'alumno', 99, 'adri.ortiz.martin@gmail.com', 'Personal'),
(99, 'alumno', 100, 'rauul.p.7@gmail.com', 'Personal'),
(100, 'alumno', 101, 'tirillaso7@gmail.com', 'Personal'),
(101, 'alumno', 102, 'thiagosalime7@gmail,com', 'Personal'),
(102, 'alumno', 103, 'sansanchez890@gmail.com', 'Personal'),
(103, 'alumno', 104, 'alex20012007s@gmail.com', 'Personal'),
(104, 'alumno', 105, 'santamariaalvaro013@gmail.com', 'Personal'),
(105, 'alumno', 106, 'santosjimenezcarlos4@gmail.com', 'Personal'),
(106, 'alumno', 107, 'mikeltorre06@gmail.com', 'Personal'),
(107, 'alumno', 108, 'velez.moravv@gmail.com', 'Personal'),
(108, 'alumno', 109, 'josearanega07@gmail.com', 'Personal'),
(109, 'alumno', 110, 'lailaouaissabelhachmi@gmail.com', 'Personal'),
(110, 'alumno', 111, 'samibr8.afak@gmail.com', 'Personal'),
(111, 'alumno', 112, 'anibal.cc2006@icloud.com', 'Personal'),
(112, 'alumno', 113, 'silviiacm07@gmail.com', 'Personal'),
(113, 'alumno', 114, 'dohaelmourabit07@gmail.com', 'Personal'),
(114, 'alumno', 115, 'ivan.gallego.merino@gmail.com', 'Personal'),
(115, 'alumno', 116, 'cristiangarridomartin@gmail.com', 'Personal'),
(116, 'alumno', 117, 'alejandrogolu2004@gmail.com', 'Personal'),
(117, 'alumno', 118, 'benvindo2007@gmail.com', 'Personal'),
(118, 'alumno', 119, 'nataliagonzalohernadez@gmail.com', 'Personal'),
(119, 'alumno', 120, 'ikerguardiainigo@gmail.com', 'Personal'),
(120, 'alumno', 121, 'juanhernandezferrer0@gmail.com', 'Personal'),
(121, 'alumno', 122, 'angelliviag@gmail.com', 'Personal'),
(122, 'alumno', 123, 'larutamarquez@gmail.com', 'Personal'),
(123, 'alumno', 124, 'cycmario@gmail.com', 'Personal'),
(124, 'alumno', 125, 'davidma6002@gmail.com', 'Personal'),
(125, 'alumno', 126, 'fraitamar@gmail.com', 'Personal'),
(126, 'alumno', 127, 'zadri1703@gmail.com', 'Personal'),
(127, 'alumno', 128, 'anavarrojuguera@gmail.com', 'Personal'),
(128, 'alumno', 129, 'sorayavictoriaolivares@gmail.com', 'Personal'),
(129, 'alumno', 130, 'alexpardo263@gmail.com', 'Personal'),
(130, 'alumno', 131, 'ips310307@gmail.com', 'Personal'),
(131, 'alumno', 132, 'sarasegovia2003@gmail.com', 'Personal'),
(132, 'alumno', 133, 'carlosutreroduran@gmail.com', 'Personal'),
(133, 'alumno', 134, 'yaizav210907@gmail.com', 'Personal'),
(134, 'alumno', 135, 'zhoujj.081@gmail.com', 'Personal'),
(135, 'empresa', 1, 'info@gtpsistemas.com', 'Trabajo'),
(136, 'empresa_contacto', 1, 'administracion@gtpsistemas.es', 'Trabajo'),
(137, 'empresa_tutor', 1, 'epulido@gtpsistemas.es', 'Trabajo'),
(138, 'profesor', 1, 'julio.sanchezfernandez@educa.madrid.org', NULL),
(139, 'empresa', 2, 'info@whitewatergroup.ie', 'Trabajo'),
(140, 'empresa_contacto', 2, 'pablosastre@whitewatergroup.es', 'Trabajo'),
(141, 'empresa_tutor', 2, 'pablosastre@whitewatergroup.es', 'Trabajo'),
(142, 'alumno', 25, 'mario.martin95@educa.madrid.org', 'EducaMadrid'),
(146, 'alumno', 28, 'daniel.nieto28@educa.madrid.org', 'EducaMadrid'),
(147, 'empresa', 3, 'contacto@zelenza.com', 'Trabajo'),
(148, 'empresa_contacto', 4, 'shernandez@zelenza.com', 'Trabajo'),
(149, 'empresa_tutor', 4, 'shernandez@zelenza.com', 'Trabajo'),
(150, 'empresa_contacto', 5, 'jesus.delavieja@parquewarner.com', 'Trabajo'),
(151, 'empresa_tutor', 5, 'jose.allende@parquewarner.com', 'Trabajo'),
(152, 'empresa', 5, 'anaesbell.1996@gmail.com', 'Trabajo'),
(153, 'empresa_contacto', 6, 'anaesbell.1996@gmail.com', 'Trabajo'),
(154, 'empresa_tutor', 6, 'anaesbell.1996@gmail.com', 'Trabajo'),
(155, 'alumno', 31, 'rperezalonso@educa.madrid.org', 'EducaMadrid'),
(156, 'empresa', 6, 'tienda@jetcomputer.net', 'Trabajo'),
(157, 'empresa_contacto', 7, 'jfrutos@jetcomputer.es', 'Trabajo'),
(158, 'empresa_tutor', 7, 'jfrutos@jetcomputer.es', 'Trabajo'),
(159, 'alumno', 39, 'miriam.sanchez34@educa.madrid.org', 'EducaMadrid'),
(160, 'empresa_contacto', 8, 'irene.soto@elecnor.com', 'Trabajo'),
(161, 'empresa_contacto', 9, 'aycalero@elecnor.es', 'Trabajo'),
(162, 'empresa_tutor', 8, 'gzubia@elecnor.com', 'Trabajo'),
(163, 'alumno', 44, 'adrian.de20@educa.madrid.org', 'EducaMadrid'),
(164, 'empresa', 8, 'cesar@redphoneservices.es', 'Trabajo'),
(165, 'empresa_contacto', 10, 'cesar@redphoneservices.es', 'Trabajo'),
(166, 'empresa_tutor', 9, 'cesar@redphoneservices.es', 'Trabajo'),
(167, 'empresa_contacto', 11, 'raul@memorysistemas.com', 'Trabajo'),
(168, 'alumno', 47, 'francisco.velazquezorgaz@educa.madrid.org', 'EducaMadrid'),
(169, 'alumno', 48, 'cz4601@educa.madrid.org', 'EducaMadrid'),
(170, 'empresa', 10, 'lti@ltisistemas.net', 'Trabajo'),
(171, 'empresa_contacto', 12, 'bdiaz@ltisistemas.net', 'Trabajo'),
(172, 'empresa_tutor', 11, 'pdiaz@ltisistemas.net', 'Trabajo'),
(173, 'empresa_contacto', 13, 'andres.aranguren@idbmp.es', 'Trabajo'),
(174, 'empresa_contacto', 14, 'franciscojavier.lopez@idbmp.es', 'Trabajo'),
(175, 'empresa_tutor', 12, 'ricardo.blecua@idbmp.es', 'Trabajo'),
(176, 'alumno', 11, 'geg055@educa.madrid.org', 'EducaMadrid'),
(177, 'empresa', 12, 'info@institutoidem.es', 'Trabajo'),
(178, 'empresa_contacto', 15, 'info@institutoidem.es', 'Trabajo'),
(179, 'empresa_tutor', 13, 'info@institutoidem.es', 'Trabajo'),
(180, 'empresa_contacto', 17, 'laura@magaversa.com', 'Trabajo'),
(181, 'empresa_contacto', 18, 'hola@creativersion.com', 'Trabajo'),
(182, 'empresa_tutor', 14, 'laura@magaversa.com', 'Trabajo'),
(183, 'empresa', 14, 'ana@calicantoarq.com', 'Trabajo'),
(184, 'empresa_contacto', 19, 'ana@calicantoarq.com', 'Trabajo'),
(185, 'empresa_tutor', 15, 'ana@calicantoarq.com', 'Trabajo'),
(186, 'empresa', 15, 'teresahh.psicologia@gmail.com', 'Trabajo'),
(187, 'empresa_contacto', 20, 'teresahh.psicologia@gmail.com', 'Trabajo'),
(188, 'empresa_tutor', 16, 'teresahh.psicologia@gmail.com', 'Trabajo'),
(189, 'empresa', 16, 'info-com@info-computer.com', 'Trabajo'),
(190, 'empresa_contacto', 21, 'ana.ramiro@info-computer.com', 'Trabajo'),
(191, 'empresa_tutor', 17, 'ana.ramiro@info-computer.com', 'Trabajo'),
(192, 'empresa_contacto', 22, 'nuria.meitin@clinica.urjc.es', 'Trabajo'),
(193, 'empresa', 18, 'd.ruiz@grupoitsl.com', 'Trabajo'),
(194, 'empresa_contacto', 23, 'd.ruiz@grupoitsl.com', 'Trabajo'),
(195, 'empresa_tutor', 19, 'd.ruiz@grupoitsl.com', 'Trabajo'),
(196, 'empresa', 19, 'juliangrande@infoneri.com', 'Trabajo'),
(197, 'empresa_contacto', 24, 'juliangrande@infoneri.com', 'Trabajo'),
(198, 'empresa_tutor', 20, 'juliangrande@infoneri.com', 'Trabajo'),
(199, 'alumno', 27, 'sebastian.moncada1@educa.madrid.org', 'EducaMadrid'),
(200, 'alumno', 33, 'julio.sanchezfernandez@educa.madrid.org', NULL);

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

--
-- Volcado de datos para la tabla `direcciones`
--

INSERT INTO `direcciones` (`id_direccion`, `id_empresa`, `id_pais`, `id_provincia`, `id_localidad`, `id_via`, `nombre_via`, `numero`, `bloque`, `escalera`, `planta`, `puerta`, `otros`, `etiqueta`, `cp`, `principal`) VALUES
(1, 1, 1, 28, NULL, 10, 'Lima', '25', NULL, NULL, NULL, NULL, 'Local', 'Principal', '28945', 1),
(2, 2, 1, 28, 1, 10, 'Miguel Faraday', '20', NULL, NULL, NULL, NULL, 'Edificio Symbco, A101', 'Principal', '28906', 1),
(4, 3, 1, 28, 4, 5, 'San Diego', '1', NULL, NULL, NULL, NULL, NULL, 'Principal', '28053', 1),
(5, 3, 1, 28, 1, 10, 'Bell', '24', NULL, NULL, NULL, NULL, NULL, 'Centro de Trabajo', '28906', 0),
(6, 4, 1, 28, 4, 10, 'Federico Mompou', '5', NULL, NULL, NULL, NULL, NULL, 'Principal', '28050', 1),
(7, 4, 1, 28, NULL, 13, 'M301', '15,500', NULL, NULL, NULL, NULL, NULL, 'Centro de Trabajo', '28330', 0),
(8, 5, 1, 28, 1, 10, 'Cataluña', '36', NULL, NULL, NULL, NULL, NULL, 'Principal', '28903', 1),
(9, 6, 1, 28, NULL, 10, 'Fernando Alonso', '32', NULL, NULL, NULL, NULL, NULL, 'Principal', '28914', 1),
(10, 7, 1, 28, 4, 10, 'Marqués de Modéjar', '33', NULL, NULL, NULL, NULL, NULL, 'Principal', '28028', 1),
(11, 7, 1, 28, 4, 10, 'Maestro Alonso', '21-23', NULL, NULL, NULL, NULL, NULL, 'Centro de Trabajo', '28028', 0),
(12, 8, 1, 45, NULL, 10, 'Bajada del Salvador', '5', NULL, NULL, NULL, NULL, NULL, 'Principal', '45223', 1),
(13, 9, 1, 28, 4, 10, 'Valle de Tobalina', '42', NULL, NULL, NULL, NULL, 'Nave 11', 'Principal', '28021', 1),
(14, 10, 1, 28, 4, 10, 'Resina', '13-15', NULL, NULL, NULL, NULL, 'Nave 2-14', 'Principal', '28021', 1),
(15, 11, 1, 28, 4, 5, 'Daroca', '294', NULL, NULL, NULL, NULL, NULL, 'Principal', '28032', 1),
(16, 12, 1, 28, 4, 10, 'Luna', '19', NULL, NULL, NULL, NULL, 'Local 2', 'Principal', '28004', 1),
(17, 13, 1, 28, 4, 10, 'Amaltea', '1', NULL, 'F', '1', 'A', NULL, 'Principal', '28045', 1),
(18, 14, 1, 28, 1, 10, 'Garcilaso', '14', NULL, NULL, '2', NULL, NULL, 'Principal', '28904', 1),
(19, 15, 1, 28, NULL, 10, 'Capitan Angosto Gómez Castrillón', '18', NULL, NULL, 'Bajo', 'Derecha', NULL, 'Principal', '28300', 1),
(20, 16, 1, 28, 4, 5, 'de los Rosales', '42', '3', NULL, NULL, NULL, 'Nave 210, 211, 212', 'Principal', '28021', 1),
(21, 17, 1, 28, NULL, 5, 'Atenas', 's/n', NULL, NULL, NULL, NULL, 'Campus URJC Alcorcón - Edificio Clínica Universitaria - 2ª planta', 'Principal', '28922', 1),
(22, 18, 1, 28, 1, 10, 'Progreso', '2', NULL, NULL, NULL, NULL, 'Oficina 214', 'Principal', '28906', 1),
(23, 19, 1, 28, NULL, 10, 'Reyes Católicos', '5', NULL, NULL, '4', 'B', NULL, 'Principal', '28802', 1);

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

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id_empresa`, `cif`, `nombre`, `apellido1`, `apellido2`, `nombre_comercial`, `convenio`, `notas`) VALUES
(1, 'B84523786', 'Fuenlabrada GTP Sistemas, S.L.', NULL, NULL, NULL, 1350, NULL),
(2, 'B56352123', 'Whitewater Treatment Spain, S.L.', NULL, NULL, NULL, 1306, NULL),
(3, 'B86218609', 'Zelenza, S.L.', NULL, NULL, NULL, 1557, NULL),
(4, 'B83331041', 'Madrid Theme Park Management, S.L.', NULL, NULL, NULL, 408, NULL),
(5, '47039080D', 'Ana María', 'García-Page', 'Díaz', NULL, 1562, NULL),
(6, 'B82049602', 'Jet Computer, S.L.', NULL, NULL, NULL, 805, NULL),
(7, 'A79486833', 'Elecnor Servicios y Proyectos, S.A.U.', NULL, NULL, NULL, 916, NULL),
(8, '02767448L', 'César Martín', 'Lara', 'Bastidas', NULL, 917, NULL),
(9, 'B81848749', 'Memory Sistemas Informáticos, S.L.', NULL, NULL, NULL, 433, NULL),
(10, 'B80691694', 'Laboratorio Técnico Informático, S.L.', NULL, NULL, NULL, 1352, NULL),
(11, 'A87299848', 'Imprenta de Billetes, S. A.', NULL, NULL, NULL, 1351, NULL),
(12, 'B85224376', 'Instituto de Diseño, Estilismo y Moda S.L.', NULL, NULL, NULL, 1560, NULL),
(13, 'B13904214', 'Magaversa, S.L.', NULL, NULL, NULL, 1568, NULL),
(14, '51069694B', 'Ana', 'Alfageme', 'Langdon', NULL, 1563, NULL),
(15, '50474566P', 'Teresa', 'Hernando', 'Herrero', NULL, 1556, NULL),
(16, 'B84425420', 'Ibérica Infocomputer, S.L.', NULL, NULL, NULL, 1325, NULL),
(17, 'G87063285', 'Fundación Clínica Universitaria de la Universidad Rey Juan C', NULL, NULL, NULL, 900, NULL),
(18, 'B87999504', 'Gruservit, Grupo de Servicios Informáticos y Tecnología S.L.', NULL, NULL, NULL, 960, NULL),
(19, '52100377H', 'Julián', 'Grande', 'Santamaría', NULL, 1320, NULL);

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

--
-- Volcado de datos para la tabla `empresas_contactos`
--

INSERT INTO `empresas_contactos` (`id_empresa_contacto`, `id_empresa`, `apellido1`, `apellido2`, `nombre`, `cargo`, `comentarios`) VALUES
(1, 1, 'Cimarra', NULL, 'Elena', 'Dpto. Administración', NULL),
(2, 2, 'Sastre', 'Pacheco', 'Pablo Francisco', NULL, NULL),
(4, 3, 'Hernández', 'Martín', 'Soraya', 'Responsable Área de Selección', NULL),
(5, 4, 'de la Vieja', 'Ruiz', 'Jesús', NULL, NULL),
(6, 5, 'García-Page', 'Díaz', 'Ana María', NULL, NULL),
(7, 6, 'López', 'de Frutos', 'Jorge', 'Director Técnico', NULL),
(8, 7, 'Soto', NULL, 'Irene', NULL, NULL),
(9, 7, 'Calero', 'Romero', 'Alejandra Yamilex', NULL, NULL),
(10, 8, 'Lara', 'Bastidas', 'César Martín', NULL, NULL),
(11, 9, 'Navarro', 'Flor de Lis', 'Raúl', NULL, NULL),
(12, 10, 'Díaz', 'Tena', 'Beatriz', NULL, NULL),
(13, 11, 'Aranguren', 'Jiménez', 'Andrés', NULL, NULL),
(14, 11, 'López', 'Muñoz', 'Francisco Javier', NULL, NULL),
(15, 12, 'Pérez', 'Lara', 'Erik', NULL, NULL),
(16, 12, 'Díez', 'Merino', 'Ester', NULL, NULL),
(17, 13, 'Gómez', 'Alique', 'Laura', NULL, NULL),
(18, 13, 'Torrijos', 'Azores', 'Javier', NULL, NULL),
(19, 14, 'Alfageme', 'Langdon', 'Ana', NULL, NULL),
(20, 15, 'Hernando', 'Herrero', 'Teresa', NULL, NULL),
(21, 16, 'Ramiro', NULL, 'Ana', NULL, NULL),
(22, 17, 'Meitín', 'Quelle', 'Nuria', NULL, NULL),
(23, 18, 'Ruiz', 'Mora', 'Daniel', NULL, NULL),
(24, 19, 'Grande', 'Santamaría', 'Julián', NULL, NULL);

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

--
-- Volcado de datos para la tabla `empresas_tutores`
--

INSERT INTO `empresas_tutores` (`id_empresas_tutor`, `id_empresa`, `apellido1`, `apellido2`, `nombre`, `dni`, `comentarios`) VALUES
(1, 1, 'Pulido', 'González', 'Eusebio', '53040312N', NULL),
(2, 2, 'Sastre', 'Pacheco', 'Pablo Francisco', NULL, NULL),
(4, 3, 'Hernández', 'Martín', 'Soraya', '47305944G', NULL),
(5, 4, 'Allende', 'Santa María', 'José Luis', NULL, NULL),
(6, 5, 'García-Page', 'Díaz', 'Ana María', '47039080D', NULL),
(7, 6, 'López', 'de Frutos', 'Jorge', NULL, NULL),
(8, 7, 'Zubia', 'Saez', 'Gonzalo Miguel', '16076514C', NULL),
(9, 8, 'Lara', 'Bastidas', 'César Martín', '02767448L', NULL),
(10, 9, 'Navarro', 'Flor de Lis', 'Raúl', NULL, NULL),
(11, 10, 'Díaz', 'Tena', 'Hipólito', '50108437Q', NULL),
(12, 11, 'Blecua', 'Morales', 'Ricardo', '17729230W', NULL),
(13, 12, 'Pérez', 'Lara', 'Erik', '72097911H', NULL),
(14, 13, 'Gómez', 'Alique', 'Laura', '50740497J', NULL),
(15, 14, 'Alfageme', 'Langdon', 'Ana', '51069694B', NULL),
(16, 15, 'Hernando', 'Herrero', 'Teresa', '50474566P', NULL),
(17, 16, 'Ramiro', NULL, 'Ana', '06255766L', NULL),
(18, 17, 'Aguilar-Tablada', 'Masso', 'María Victoria', '43624572N', NULL),
(19, 18, 'Ruiz', 'Mora', 'Daniel', '49007655Z', NULL),
(20, 19, 'Grande', 'Santamaría', 'Julián', '52100377H', NULL);

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

--
-- Volcado de datos para la tabla `grupos_tutores`
--

INSERT INTO `grupos_tutores` (`id_grupo_tutor`, `id_grupo`, `id_profesor`, `id_curso_escolar`) VALUES
(2, 3, 2, 1),
(4, 7, 11, 1),
(7, 4, 1, 1),
(9, 9, 12, 1),
(10, 5, 10, 1);

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
(5, 28, 'Fuenlabrada'),
(1, 28, 'Getafe'),
(6, 28, 'Griñon'),
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

--
-- Volcado de datos para la tabla `modulos_profesores`
--

INSERT INTO `modulos_profesores` (`id_modulo_profesor`, `id_modulo`, `id_profesor`, `id_curso_escolar`) VALUES
(3, 15, 8, 1),
(6, 8, 2, 1),
(8, 13, 5, 1),
(9, 10, 6, 1),
(17, 22, 1, 1),
(18, 61, 1, 1),
(19, 64, 1, 1),
(20, 9, 1, 1),
(21, 14, 3, 1),
(22, 16, 9, 1),
(23, 12, 4, 1),
(24, 11, 7, 1);

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

CREATE TABLE `paises` (
  `id_pais` int(10) UNSIGNED NOT NULL,
  `codigo_iso` char(2) NOT NULL,
  `pais` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `paises`
--

INSERT INTO `paises` (`id_pais`, `codigo_iso`, `pais`) VALUES
(1, 'ES', 'España'),
(2, 'AD', 'Andorra'),
(3, 'AR', 'Argentina'),
(4, 'AS', 'Samoa Americana'),
(5, 'AT', 'Austria'),
(6, 'AU', 'Australia'),
(7, 'BD', 'Bangladés'),
(8, 'BE', 'Bélgica'),
(9, 'BG', 'Bulgaria'),
(10, 'BR', 'Brasil'),
(11, 'CA', 'Canadá'),
(12, 'CH', 'Suiza'),
(13, 'CZ', 'República Checa'),
(14, 'DE', 'Alemania'),
(15, 'DK', 'Dinamarca'),
(16, 'DO', 'República Dominicana'),
(17, 'FI', 'Finlandia'),
(18, 'FO', 'Islas Feroe'),
(19, 'FR', 'Francia'),
(20, 'GB', 'Reino Unido'),
(21, 'GF', 'Guayana Francesa'),
(22, 'GG', 'Guernsey'),
(23, 'GL', 'Groenlandia'),
(24, 'GP', 'Guadalupe'),
(25, 'GT', 'Guatemala'),
(26, 'GU', 'Guam'),
(27, 'GY', 'Guyana'),
(28, 'HR', 'Croacia'),
(29, 'HU', 'Hungría'),
(30, 'IM', 'Isla de Man'),
(31, 'IN', 'India'),
(32, 'IS', 'Islandia'),
(33, 'IT', 'Italia'),
(34, 'JE', 'Jersey'),
(35, 'JP', 'Japón'),
(36, 'LI', 'Liechtenstein'),
(37, 'LK', 'Sri Lanka'),
(38, 'LT', 'Lituania'),
(39, 'LU', 'Luxemburgo'),
(40, 'MC', 'Mónaco'),
(41, 'MD', 'Moldavia'),
(42, 'MH', 'Islas Marshall'),
(43, 'MK', 'Macedonia del Norte'),
(44, 'MP', 'Islas Marianas del Norte'),
(45, 'MQ', 'Martinica'),
(46, 'MX', 'México'),
(47, 'MY', 'Malasia'),
(48, 'NL', 'Países Bajos'),
(49, 'NO', 'Noruega'),
(50, 'NZ', 'Nueva Zelanda'),
(51, 'PH', 'Filipinas'),
(52, 'PK', 'Pakistán'),
(53, 'PL', 'Polonia'),
(54, 'PM', 'San Pedro y Miquelón'),
(55, 'PR', 'Puerto Rico'),
(56, 'PT', 'Portugal'),
(57, 'RE', 'Reunión'),
(58, 'RU', 'Rusia'),
(59, 'SE', 'Suecia'),
(60, 'SI', 'Eslovenia'),
(61, 'SJ', 'Svalbard y Jan Mayen'),
(62, 'SK', 'Eslovaquia'),
(63, 'SM', 'San Marino'),
(64, 'TH', 'Tailandia'),
(65, 'TR', 'Turquía'),
(66, 'US', 'Estados Unidos'),
(67, 'VA', 'Vaticano'),
(68, 'VI', 'Islas Vírgenes de EE.UU.'),
(69, 'YT', 'Mayotte'),
(70, 'ZA', 'Sudáfrica');

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
  `dias_extra` tinyint(2) UNSIGNED NOT NULL DEFAULT 0,
  `fecha_fin_real` date DEFAULT NULL,
  `horas` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `circ_excep` tinyint(1) NOT NULL DEFAULT 0,
  `cancelada` tinyint(1) NOT NULL DEFAULT 0,
  `mayor_edad` tinyint(1) NOT NULL DEFAULT 0,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `practicas`
--

INSERT INTO `practicas` (`id_practica`, `id_alumno`, `id_empresa`, `id_direccion`, `id_empresa_tutor`, `anexo`, `fecha_inicio`, `fecha_fin`, `dias_extra`, `fecha_fin_real`, `horas`, `circ_excep`, `cancelada`, `mayor_edad`, `observaciones`) VALUES
(1, 23, 1, 1, 1, 59, '2026-02-23', '2026-06-10', 0, '2026-06-10', 500, 0, 0, 0, NULL),
(2, 25, 2, 2, 2, 95, '2026-03-02', '2026-06-10', 0, '2026-06-10', 500, 0, 0, 0, NULL),
(3, 28, 3, 5, 4, 64, '2026-02-23', '2026-06-03', 0, '2026-06-03', 500, 0, 0, 0, NULL),
(4, 30, 4, 7, 5, 60, '2026-02-23', '2026-06-03', 1, '2026-06-04', 500, 0, 0, 0, NULL),
(5, 31, 5, 8, 6, 154, '2026-02-23', '2026-05-08', 0, '2026-05-08', 370, 0, 0, 0, NULL),
(6, 39, 6, 9, 7, 61, '2026-02-23', '2026-05-08', 0, '2026-05-08', 370, 0, 0, 0, NULL),
(7, 44, 7, 11, 8, 143, '2026-02-23', '2026-06-03', 1, '2026-06-04', 500, 0, 0, 0, NULL),
(8, 46, 8, 12, 9, 153, '2026-02-23', '2026-06-03', 1, '2026-06-04', 500, 1, 0, 0, NULL),
(10, 48, 8, 12, 9, 63, '2026-02-23', '2026-06-03', 1, '2026-06-04', 500, 1, 0, 0, NULL),
(11, 2, 10, 14, 11, 93, '2026-02-23', '2026-06-03', 1, '2026-06-04', 500, 0, 0, 0, NULL),
(12, 11, 11, 15, 12, 94, '2026-02-23', '2026-05-28', 1, '2026-05-29', 440, 0, 0, 0, NULL),
(13, 42, 17, 21, 18, 21, '2025-10-01', '2025-12-17', 0, '2025-12-17', 370, 0, 0, 0, NULL),
(14, 16, 8, 12, 9, 18, '2025-10-14', '2025-12-19', 0, '2025-12-19', 370, 1, 0, 0, NULL),
(15, 17, 9, 13, 10, 26, '2025-10-22', '2026-01-15', 0, '2026-01-15', 370, 0, 0, 0, NULL),
(16, 40, 16, 20, 17, 27, '2025-10-22', '2026-01-15', 0, '2026-01-15', 370, 0, 0, 0, NULL),
(17, 43, 18, 22, 19, 17, '2025-10-14', '2025-12-19', 0, '2025-12-19', 370, 0, 0, 0, NULL),
(18, 1, 19, 23, 20, 16, '2025-10-01', '2025-12-17', 0, '2025-12-17', 370, 0, 0, 0, NULL),
(19, 26, 13, 17, 14, 166, '2026-02-23', '2026-06-03', 1, '2026-06-04', 500, 0, 0, 0, NULL),
(20, 27, 13, 17, 14, 166, '2026-02-23', '2026-06-03', 1, '2026-06-04', 500, 0, 0, 0, NULL),
(21, 29, 12, 16, 13, 165, '2026-02-23', '2026-06-03', 1, '2026-06-04', 500, 0, 0, 0, NULL),
(22, 24, 12, 16, 13, 165, '2026-02-23', '2026-06-03', 1, '2026-06-04', 500, 0, 0, 0, NULL),
(23, 35, 15, 19, 16, 167, '2026-03-02', '2026-06-10', 0, '2026-06-10', 500, 0, 0, 0, NULL),
(24, 45, 15, 19, 16, 167, '2026-03-02', '2026-06-10', 0, '2026-06-10', 500, 0, 0, 0, NULL),
(25, 37, 6, 9, 7, 174, '2026-02-23', '2026-06-03', 0, '2026-06-03', 500, 0, 0, 0, NULL),
(26, 47, 9, 13, 10, 175, '2026-02-26', '2026-05-13', 0, '2026-05-13', 370, 0, 0, 0, NULL),
(27, 32, 9, 13, 10, 177, '2026-02-26', '2026-06-08', 0, '2026-06-08', 500, 0, 0, 0, NULL);

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
-- Estructura de tabla para la tabla `practicas_horario`
--

CREATE TABLE `practicas_horario` (
  `id_practicas_horario` int(10) UNSIGNED NOT NULL,
  `id_practica` int(10) UNSIGNED NOT NULL,
  `dia_semana` tinyint(1) NOT NULL DEFAULT 0,
  `hora_entrada` time NOT NULL,
  `hora_salida` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `practicas_horario`
--

INSERT INTO `practicas_horario` (`id_practicas_horario`, `id_practica`, `dia_semana`, `hora_entrada`, `hora_salida`) VALUES
(1, 1, 1, '09:00:00', '14:00:00'),
(2, 1, 1, '15:00:00', '18:00:00'),
(3, 1, 2, '09:00:00', '16:00:00'),
(4, 1, 3, '09:00:00', '14:00:00'),
(5, 1, 3, '15:00:00', '18:00:00'),
(6, 1, 4, '09:00:00', '16:00:00'),
(7, 1, 5, '08:00:00', '15:00:00'),
(8, 2, 1, '08:15:00', '13:30:00'),
(9, 2, 1, '15:00:00', '17:45:00'),
(10, 2, 2, '08:15:00', '13:30:00'),
(11, 2, 2, '15:00:00', '17:45:00'),
(12, 2, 3, '08:15:00', '13:30:00'),
(13, 2, 3, '15:00:00', '17:45:00'),
(14, 2, 4, '08:15:00', '13:30:00'),
(15, 2, 4, '15:00:00', '17:45:00'),
(16, 2, 5, '08:15:00', '13:30:00'),
(17, 2, 5, '15:00:00', '17:45:00'),
(18, 3, 1, '08:00:00', '14:00:00'),
(19, 3, 1, '15:00:00', '17:00:00'),
(20, 3, 2, '08:00:00', '14:00:00'),
(21, 3, 2, '15:00:00', '17:00:00'),
(22, 3, 3, '08:00:00', '14:00:00'),
(23, 3, 3, '15:00:00', '17:00:00'),
(24, 3, 4, '08:00:00', '14:00:00'),
(25, 3, 4, '15:00:00', '17:00:00'),
(26, 3, 5, '08:00:00', '14:00:00'),
(27, 3, 5, '15:00:00', '17:00:00'),
(28, 4, 1, '09:00:00', '17:00:00'),
(29, 4, 2, '09:00:00', '17:00:00'),
(30, 4, 3, '09:00:00', '17:00:00'),
(31, 4, 4, '09:00:00', '17:00:00'),
(32, 4, 5, '09:00:00', '17:00:00'),
(33, 5, 1, '08:00:00', '16:00:00'),
(34, 5, 2, '08:00:00', '16:00:00'),
(35, 5, 3, '08:00:00', '16:00:00'),
(36, 5, 4, '08:00:00', '16:00:00'),
(37, 5, 5, '08:00:00', '16:00:00'),
(38, 6, 1, '09:00:00', '17:00:00'),
(39, 6, 2, '09:00:00', '17:00:00'),
(40, 6, 3, '09:00:00', '17:00:00'),
(41, 6, 4, '09:00:00', '17:00:00'),
(42, 6, 5, '09:00:00', '17:00:00'),
(43, 7, 1, '08:30:00', '14:00:00'),
(44, 7, 1, '15:00:00', '17:30:00'),
(45, 7, 2, '08:30:00', '14:00:00'),
(46, 7, 2, '15:00:00', '17:30:00'),
(47, 7, 3, '08:30:00', '14:00:00'),
(48, 7, 3, '15:00:00', '17:30:00'),
(49, 7, 4, '08:30:00', '14:00:00'),
(50, 7, 4, '15:00:00', '17:30:00'),
(51, 7, 5, '08:30:00', '14:00:00'),
(52, 7, 5, '15:00:00', '17:30:00'),
(53, 8, 1, '09:30:00', '14:30:00'),
(54, 8, 1, '16:30:00', '19:30:00'),
(55, 8, 2, '09:30:00', '14:30:00'),
(56, 8, 2, '16:30:00', '19:30:00'),
(57, 8, 3, '09:30:00', '14:30:00'),
(58, 8, 3, '16:30:00', '19:30:00'),
(59, 8, 4, '09:30:00', '14:30:00'),
(60, 8, 4, '16:30:00', '19:30:00'),
(61, 8, 5, '09:30:00', '14:30:00'),
(62, 8, 5, '16:30:00', '19:30:00'),
(68, 10, 1, '09:30:00', '14:30:00'),
(69, 10, 1, '16:30:00', '19:30:00'),
(70, 10, 2, '09:30:00', '14:30:00'),
(71, 10, 2, '16:30:00', '19:30:00'),
(72, 10, 3, '09:30:00', '14:30:00'),
(73, 10, 3, '16:30:00', '19:30:00'),
(74, 10, 4, '09:30:00', '14:30:00'),
(75, 10, 4, '16:30:00', '19:30:00'),
(76, 10, 5, '09:30:00', '14:30:00'),
(77, 10, 5, '16:30:00', '19:30:00'),
(78, 11, 1, '09:00:00', '14:00:00'),
(79, 11, 1, '15:00:00', '18:00:00'),
(80, 11, 2, '09:00:00', '14:00:00'),
(81, 11, 2, '15:00:00', '18:00:00'),
(82, 11, 3, '09:00:00', '14:00:00'),
(83, 11, 3, '15:00:00', '18:00:00'),
(84, 11, 4, '09:00:00', '14:00:00'),
(85, 11, 4, '15:00:00', '18:00:00'),
(86, 11, 5, '09:00:00', '14:00:00'),
(87, 11, 5, '15:00:00', '18:00:00'),
(88, 12, 1, '08:00:00', '15:30:00'),
(89, 12, 2, '08:00:00', '15:30:00'),
(90, 12, 3, '08:00:00', '15:30:00'),
(91, 12, 4, '08:00:00', '15:30:00'),
(92, 12, 5, '08:00:00', '15:30:00'),
(93, 13, 1, '08:30:00', '15:30:00'),
(94, 13, 2, '08:30:00', '15:30:00'),
(95, 13, 3, '08:30:00', '15:30:00'),
(96, 13, 4, '08:30:00', '15:30:00'),
(97, 13, 5, '08:30:00', '15:30:00'),
(98, 14, 1, '09:30:00', '14:30:00'),
(99, 14, 1, '16:30:00', '19:30:00'),
(100, 14, 2, '09:30:00', '14:30:00'),
(101, 14, 2, '16:30:00', '19:30:00'),
(102, 14, 3, '09:30:00', '14:30:00'),
(103, 14, 3, '16:30:00', '19:30:00'),
(104, 14, 4, '09:30:00', '14:30:00'),
(105, 14, 4, '16:30:00', '19:30:00'),
(106, 14, 5, '09:30:00', '14:30:00'),
(107, 14, 5, '16:30:00', '19:30:00'),
(108, 15, 1, '08:00:00', '16:00:00'),
(109, 15, 2, '08:00:00', '16:00:00'),
(110, 15, 3, '08:00:00', '16:00:00'),
(111, 15, 4, '08:00:00', '16:00:00'),
(112, 15, 5, '08:00:00', '16:00:00'),
(113, 16, 1, '08:00:00', '16:00:00'),
(114, 16, 2, '08:00:00', '16:00:00'),
(115, 16, 3, '08:00:00', '16:00:00'),
(116, 16, 4, '08:00:00', '16:00:00'),
(117, 16, 5, '08:00:00', '16:00:00'),
(118, 17, 1, '07:00:00', '15:00:00'),
(119, 17, 2, '07:00:00', '15:00:00'),
(120, 17, 3, '07:00:00', '15:00:00'),
(121, 17, 4, '07:00:00', '15:00:00'),
(122, 17, 5, '07:00:00', '15:00:00'),
(123, 18, 1, '08:00:00', '15:00:00'),
(124, 18, 2, '08:00:00', '15:00:00'),
(125, 18, 3, '08:00:00', '15:00:00'),
(126, 18, 4, '08:00:00', '15:00:00'),
(127, 18, 5, '08:00:00', '15:00:00'),
(128, 19, 1, '08:00:00', '16:00:00'),
(129, 19, 2, '08:00:00', '16:00:00'),
(130, 19, 3, '08:00:00', '16:00:00'),
(131, 19, 4, '08:00:00', '16:00:00'),
(132, 19, 5, '08:00:00', '16:00:00'),
(133, 20, 1, '08:00:00', '16:00:00'),
(134, 20, 2, '08:00:00', '16:00:00'),
(135, 20, 3, '08:00:00', '16:00:00'),
(136, 20, 4, '08:00:00', '16:00:00'),
(137, 20, 5, '08:00:00', '16:00:00'),
(138, 21, 1, '08:00:00', '16:00:00'),
(139, 21, 2, '08:00:00', '16:00:00'),
(140, 21, 3, '08:00:00', '16:00:00'),
(141, 21, 4, '08:00:00', '16:00:00'),
(142, 21, 5, '08:00:00', '16:00:00'),
(143, 22, 1, '08:00:00', '16:00:00'),
(144, 22, 2, '08:00:00', '16:00:00'),
(145, 22, 3, '08:00:00', '16:00:00'),
(146, 22, 4, '08:00:00', '16:00:00'),
(147, 22, 5, '08:00:00', '16:00:00'),
(148, 23, 1, '08:00:00', '16:00:00'),
(149, 23, 2, '08:00:00', '16:00:00'),
(150, 23, 3, '08:00:00', '16:00:00'),
(151, 23, 4, '08:00:00', '16:00:00'),
(152, 23, 5, '08:00:00', '16:00:00'),
(153, 24, 1, '08:00:00', '16:00:00'),
(154, 24, 2, '08:00:00', '16:00:00'),
(155, 24, 3, '08:00:00', '16:00:00'),
(156, 24, 4, '08:00:00', '16:00:00'),
(157, 24, 5, '08:00:00', '16:00:00'),
(158, 25, 1, '09:00:00', '17:00:00'),
(159, 25, 2, '09:00:00', '17:00:00'),
(160, 25, 3, '09:00:00', '17:00:00'),
(161, 25, 4, '09:00:00', '17:00:00'),
(162, 25, 5, '09:00:00', '17:00:00'),
(163, 26, 1, '08:00:00', '16:00:00'),
(164, 26, 2, '08:00:00', '16:00:00'),
(165, 26, 3, '08:00:00', '16:00:00'),
(166, 26, 4, '08:00:00', '16:00:00'),
(167, 26, 5, '08:00:00', '16:00:00'),
(168, 27, 1, '08:00:00', '16:00:00'),
(169, 27, 2, '08:00:00', '16:00:00'),
(170, 27, 3, '08:00:00', '16:00:00'),
(171, 27, 4, '08:00:00', '16:00:00'),
(172, 27, 5, '08:00:00', '16:00:00');

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
(14, '1', 'SMR', 1, 1, 8, 137, 10.00),
(15, '1', 'SMR', 1, 1, 14, 139, 10.00),
(16, '1', 'SMR', 1, 1, 15, 149, 10.00),
(17, '1', 'SMR', 1, 1, 9, 121, 10.00),
(18, '1', 'SMR', 1, 1, 9, 122, 10.00),
(19, '1', 'SMR', 1, 1, 9, 123, 10.00),
(20, '1', 'SMR', 1, 1, 9, 124, 10.00),
(21, '1', 'SMR', 1, 1, 11, 154, 10.00),
(22, '1', 'SMR', 1, 1, 11, 155, 10.00),
(23, '1', 'SMR', 1, 1, 11, 156, 10.00),
(24, '1', 'SMR', 1, 1, 11, 157, 10.00),
(25, '1', 'SMR', 1, 1, 11, 159, 10.00),
(26, '1', 'SMR', 1, 1, 10, 131, 10.00);

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

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`id_profesor`, `apellido1`, `apellido2`, `nombre`, `dni`) VALUES
(1, 'Sánchez', 'Fernández', 'Julio', '52509464G'),
(2, 'Ruescas', 'Cruz', 'Yolanda', '49095015C'),
(3, 'Almodóvar', 'Vialas', 'Sonia', NULL),
(4, 'Pérez', 'Revuelta', 'Inmaculada', NULL),
(5, 'Saldaña', 'Plaza', 'Marta', NULL),
(6, 'González', 'Palacios', 'Isidro', NULL),
(7, 'Pérez', 'Romero', 'Esteban', NULL),
(8, 'Villoria', 'Valiente', 'Cristina', NULL),
(9, 'Pérez', 'Pinillos', 'Daniel', NULL),
(10, 'García', 'Fernández', 'David', '50961088B'),
(11, 'Fernández', 'Fernández', 'Alain', '72490362C'),
(12, 'Abderrahman', 'Cañabate', 'Ricardo', '03899222D');

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

--
-- Volcado de datos para la tabla `telefonos`
--

INSERT INTO `telefonos` (`id_telefono`, `entidad_tipo`, `id_entidad`, `telefono`, `etiqueta`) VALUES
(1, 'alumno', 1, '640086238', 'Personal'),
(2, 'alumno', 2, '648250716', 'Personal'),
(3, 'alumno', 3, '624497996', 'Personal'),
(4, 'alumno', 4, '622898964', 'Personal'),
(5, 'alumno', 5, '676252240', 'Personal'),
(6, 'alumno', 6, '633780491', 'Personal'),
(7, 'alumno', 7, '643752812', 'Personal'),
(8, 'alumno', 8, '622946564', 'Personal'),
(9, 'alumno', 9, '654764485', 'Personal'),
(10, 'alumno', 10, '634366741', 'Personal'),
(11, 'alumno', 11, '693579817', 'Personal'),
(12, 'alumno', 12, '722660727', 'Personal'),
(13, 'alumno', 13, '699334801', 'Personal'),
(14, 'alumno', 14, '685578779', 'Personal'),
(15, 'alumno', 15, '610232325', 'Personal'),
(16, 'alumno', 16, '676266268', 'Personal'),
(17, 'alumno', 17, '653090110', 'Personal'),
(18, 'alumno', 18, '635145372', 'Personal'),
(19, 'alumno', 19, '632928865', 'Personal'),
(20, 'alumno', 20, '649504330', 'Personal'),
(21, 'alumno', 21, '722442149', 'Personal'),
(22, 'alumno', 22, '671434045', 'Personal'),
(23, 'alumno', 23, '623267344', 'Personal'),
(24, 'alumno', 24, '663368301', 'Personal'),
(25, 'alumno', 25, '674223734', 'Personal'),
(26, 'alumno', 26, '642891177', 'Personal'),
(27, 'alumno', 27, '631264744', 'Personal'),
(28, 'alumno', 28, '665487901', 'Personal'),
(29, 'alumno', 29, '623116695', 'Personal'),
(30, 'alumno', 30, '618026027', 'Personal'),
(31, 'alumno', 31, '662262375', 'Personal'),
(32, 'alumno', 32, '699413831', 'Personal'),
(33, 'alumno', 33, '643492891', 'Personal'),
(34, 'alumno', 34, '681180579', 'Personal'),
(35, 'alumno', 35, '625559732', 'Personal'),
(36, 'alumno', 36, '625911036', 'Personal'),
(37, 'alumno', 37, '687445050', 'Personal'),
(38, 'alumno', 39, '666600537', 'Personal'),
(39, 'alumno', 40, '644217213', 'Personal'),
(40, 'alumno', 41, '641156386', 'Personal'),
(41, 'alumno', 42, '632671971', 'Personal'),
(42, 'alumno', 43, '680299300', 'Personal'),
(43, 'alumno', 44, '640589711', 'Personal'),
(44, 'alumno', 45, '604256327', 'Personal'),
(45, 'alumno', 46, '644220793', 'Personal'),
(46, 'alumno', 47, '616827816', 'Personal'),
(47, 'alumno', 48, '630634385', 'Personal'),
(48, 'alumno', 49, '644719085', 'Personal'),
(49, 'alumno', 50, '693036588', 'Personal'),
(50, 'alumno', 51, '610076628', 'Personal'),
(51, 'alumno', 52, '623198775', 'Personal'),
(52, 'alumno', 53, '617551935', 'Personal'),
(53, 'alumno', 54, '677077869', 'Personal'),
(54, 'alumno', 55, '667313882', 'Personal'),
(55, 'alumno', 56, '667214707', 'Personal'),
(56, 'alumno', 57, '610863704', 'Personal'),
(57, 'alumno', 58, '640766396', 'Personal'),
(58, 'alumno', 59, '627397097', 'Personal'),
(59, 'alumno', 60, '649215072', 'Personal'),
(60, 'alumno', 61, '642710686', 'Personal'),
(61, 'alumno', 62, '687242799', 'Personal'),
(62, 'alumno', 63, '614158911', 'Personal'),
(63, 'alumno', 64, '656362189', 'Personal'),
(64, 'alumno', 65, '645520581', 'Personal'),
(65, 'alumno', 66, '635200874', 'Personal'),
(66, 'alumno', 67, '668620174', 'Personal'),
(67, 'alumno', 68, '623440583', 'Personal'),
(68, 'alumno', 69, '696819444', 'Personal'),
(69, 'alumno', 70, '622740103', 'Personal'),
(70, 'alumno', 71, '601626135', 'Personal'),
(71, 'alumno', 72, '622959202', 'Personal'),
(72, 'alumno', 73, '673254435', 'Personal'),
(73, 'alumno', 74, '644764702', 'Personal'),
(74, 'alumno', 75, '644766673', 'Personal'),
(75, 'alumno', 76, '637630978', 'Personal'),
(76, 'alumno', 77, '642352640', 'Personal'),
(77, 'alumno', 78, '684794215', 'Personal'),
(78, 'alumno', 79, '660447102', 'Personal'),
(79, 'alumno', 80, '624951728', 'Personal'),
(80, 'alumno', 81, '644887574', 'Personal'),
(81, 'alumno', 82, '641319693', 'Personal'),
(82, 'alumno', 83, '644550294', 'Personal'),
(83, 'alumno', 84, '674226563', 'Personal'),
(84, 'alumno', 85, '677030271', 'Personal'),
(85, 'alumno', 86, '640509099', 'Personal'),
(86, 'alumno', 87, '684123718', 'Personal'),
(87, 'alumno', 88, '640797343', 'Personal'),
(88, 'alumno', 89, '655168821', 'Personal'),
(89, 'alumno', 90, '603569970', 'Personal'),
(90, 'alumno', 91, '675604810', 'Personal'),
(91, 'alumno', 92, '660190045', 'Personal'),
(92, 'alumno', 93, '687233842', 'Personal'),
(93, 'alumno', 94, '637122038', 'Personal'),
(94, 'alumno', 95, '722269799', 'Personal'),
(95, 'alumno', 96, '629271726', 'Personal'),
(96, 'alumno', 97, '638016796', 'Personal'),
(97, 'alumno', 98, '655664990', 'Personal'),
(98, 'alumno', 99, '644741455', 'Personal'),
(99, 'alumno', 100, '650092345', 'Personal'),
(100, 'alumno', 101, '640317146', 'Personal'),
(101, 'alumno', 102, '692349163', 'Personal'),
(102, 'alumno', 103, '622274016', 'Personal'),
(103, 'alumno', 104, '653046854', 'Personal'),
(104, 'alumno', 105, '608407195', 'Personal'),
(105, 'alumno', 106, '644061650', 'Personal'),
(106, 'alumno', 107, '658520151', 'Personal'),
(107, 'alumno', 108, '678956743', 'Personal'),
(108, 'alumno', 109, '644323427', 'Personal'),
(109, 'alumno', 110, '631207220', 'Personal'),
(110, 'alumno', 111, '644125673', 'Personal'),
(111, 'alumno', 112, '674132157', 'Personal'),
(112, 'alumno', 113, '674597073', 'Personal'),
(113, 'alumno', 114, '697827592', 'Personal'),
(114, 'alumno', 115, '618338931', 'Personal'),
(115, 'alumno', 116, '675193511', 'Personal'),
(116, 'alumno', 117, '633050858', 'Personal'),
(117, 'alumno', 118, '687192836', 'Personal'),
(118, 'alumno', 119, '656605981', 'Personal'),
(119, 'alumno', 120, '648807320', 'Personal'),
(120, 'alumno', 121, '622434169', 'Personal'),
(121, 'alumno', 122, '626173542', 'Personal'),
(122, 'alumno', 123, '640913935', 'Personal'),
(123, 'alumno', 124, '653346000', 'Personal'),
(124, 'alumno', 125, '603853218', 'Personal'),
(125, 'alumno', 126, '633735533', 'Personal'),
(126, 'alumno', 127, '684048595', 'Personal'),
(127, 'alumno', 128, '622664290', 'Personal'),
(128, 'alumno', 129, '680339325', 'Personal'),
(129, 'alumno', 130, '695047298', 'Personal'),
(130, 'alumno', 131, '643639144', 'Personal'),
(131, 'alumno', 132, '655437783', 'Personal'),
(132, 'alumno', 133, '630416206', 'Personal'),
(133, 'alumno', 134, '647727693', 'Personal'),
(134, 'alumno', 135, '624890110', 'Personal'),
(135, 'empresa', 1, '914920990', 'Trabajo'),
(136, 'empresa_contacto', 1, '914920991', 'Trabajo'),
(137, 'empresa_tutor', 1, '620294909', 'Trabajo'),
(138, 'profesor', 1, '916832026', NULL),
(139, 'empresa', 2, '674325023', 'Trabajo'),
(140, 'empresa_contacto', 2, '664171886', 'Trabajo'),
(141, 'empresa_tutor', 2, '664171886', 'Trabajo'),
(145, 'empresa', 3, '915062660', 'Trabajo'),
(146, 'empresa_contacto', 4, '639277346', 'Trabajo'),
(147, 'empresa_tutor', 4, '639277346', 'Trabajo'),
(148, 'empresa', 4, '918211228', 'Trabajo'),
(149, 'empresa_contacto', 5, '635122115', 'Trabajo'),
(150, 'empresa', 5, '656832366', 'Trabajo'),
(151, 'empresa_contacto', 6, '656832366', 'Trabajo'),
(152, 'empresa_tutor', 6, '656832366', 'Trabajo'),
(153, 'empresa', 6, '916879554', 'Trabajo'),
(154, 'empresa_contacto', 7, '639131055', 'Trabajo'),
(155, 'empresa_tutor', 7, '639131055', 'Trabajo'),
(156, 'empresa_contacto', 8, '660780366', 'Trabajo'),
(157, 'empresa_tutor', 8, '917251004', 'Trabajo'),
(158, 'empresa', 8, '622711605', 'Trabajo'),
(159, 'empresa', 8, '611392966', 'Trabajo'),
(160, 'empresa_contacto', 10, '622711605', 'Trabajo'),
(161, 'empresa_tutor', 9, '622711605', 'Trabajo'),
(162, 'empresa', 9, '917230177', 'Trabajo'),
(163, 'empresa', 9, '917230567', 'Trabajo'),
(164, 'empresa_contacto', 11, '669703997', 'Trabajo'),
(165, 'empresa', 10, '917230258', 'Trabajo'),
(166, 'empresa', 10, '917230328', 'Trabajo'),
(167, 'empresa_tutor', 11, '917230258', 'Trabajo'),
(168, 'empresa', 11, '915742200', 'Trabajo'),
(169, 'empresa_contacto', 13, '918726421', 'Trabajo'),
(170, 'empresa_tutor', 12, '919077000', 'Trabajo'),
(171, 'empresa', 12, '646917725', 'Trabajo'),
(172, 'empresa_contacto', 15, '646917725', 'Trabajo'),
(173, 'empresa_contacto', 16, '655828542', 'Trabajo'),
(174, 'empresa_tutor', 13, '646917725', 'Trabajo'),
(175, 'empresa_contacto', 17, '687467235', 'Trabajo'),
(176, 'empresa_contacto', 18, '609601322', 'Trabajo'),
(177, 'empresa_tutor', 14, '687467235', 'Trabajo'),
(178, 'empresa', 14, '669113805', 'Trabajo'),
(179, 'empresa_contacto', 19, '669113805', 'Trabajo'),
(180, 'empresa_tutor', 15, '669113805', 'Trabajo'),
(181, 'empresa', 15, '653210444', 'Trabajo'),
(182, 'empresa_contacto', 20, '653210444', 'Trabajo'),
(183, 'empresa_tutor', 16, '653210444', 'Trabajo'),
(184, 'empresa', 16, '910771230', 'Trabajo'),
(185, 'empresa_contacto', 21, '910771230', 'Trabajo'),
(186, 'empresa_tutor', 17, '910771230', 'Trabajo'),
(187, 'empresa', 17, '914888664', 'Trabajo'),
(188, 'empresa_contacto', 22, '914888664', 'Trabajo'),
(189, 'empresa', 18, '917170012', 'Trabajo'),
(190, 'empresa_contacto', 23, '620541219', 'Trabajo'),
(191, 'empresa_tutor', 19, '620541219', 'Trabajo'),
(192, 'empresa', 19, '607419044', 'Trabajo'),
(193, 'empresa_contacto', 24, '607419044', 'Trabajo'),
(194, 'empresa_tutor', 20, '607419044', 'Trabajo');

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
  MODIFY `id_alumno` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

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
  MODIFY `id_correo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

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
  MODIFY `id_direccion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id_empresa` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `empresas_contactos`
--
ALTER TABLE `empresas_contactos`
  MODIFY `id_empresa_contacto` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `empresas_tutores`
--
ALTER TABLE `empresas_tutores`
  MODIFY `id_empresas_tutor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id_grupo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `grupos_tutores`
--
ALTER TABLE `grupos_tutores`
  MODIFY `id_grupo_tutor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `localidades`
--
ALTER TABLE `localidades`
  MODIFY `id_localidad` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `id_modulo_profesor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
  MODIFY `id_practica` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

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
-- AUTO_INCREMENT de la tabla `practicas_horario`
--
ALTER TABLE `practicas_horario`
  MODIFY `id_practicas_horario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT de la tabla `practicas_pasos`
--
ALTER TABLE `practicas_pasos`
  MODIFY `id_practicas_paso` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `practicas_ras`
--
ALTER TABLE `practicas_ras`
  MODIFY `id_practica_ra` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `id_profesor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  MODIFY `id_telefono` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=195;

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
