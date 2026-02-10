-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-02-2026 a las 23:29:39
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
(1, '8858757', '47318106E', '281580268564', 'Blasco', 'Gata', 'Andrea', '2007-03-22', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'ablascoguijo@gmail.com', NULL, NULL, 'yolygata4@gmail.com', NULL, 0, NULL, 0, NULL),
(2, '10271520', '49589569M', '281576458080', 'Blázquez', 'Vargas', 'Gonzalo', '2006-04-05', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'jesus.blazquez@siemens-healthineers.com', NULL, NULL, 'raquel@maderasvargas.es', NULL, 0, NULL, 0, NULL),
(3, '13242046', '49157732Q', '281489274682', 'Bonet', 'Borrás', 'Aarón', '2008-11-16', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'susanaborras@hotmail.es', NULL, NULL, 'davidbherraez@gmail.com', NULL, 0, NULL, 0, NULL),
(4, '48336796', 'Y6795560V', '281595440980', 'Borda', 'Sanchez', 'Joselyn Yalimet', '2001-08-16', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(5, '12152995', '47319652G', '471040858625', 'Cañada', 'García', 'Iván', '2007-10-19', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'kikikarol@hotmail.com', NULL, NULL, 'kikikarol@hotmail.com', NULL, 0, NULL, 0, NULL),
(6, '13219390', '49592444M', '281469268434', 'Casas', 'Álvarez', 'Alejandro', '2008-06-20', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'maralvarezcalderon@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(7, '9785908', '53906787P', '281651412913', 'Castañeda', 'Botero', 'Carlos Andrés', '2006-10-01', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'cc099679@gmail.com', NULL, NULL, 'dmb1805@hotmail.com', NULL, 0, NULL, 0, NULL),
(8, '21663413', 'Y6082370B', '281617949529', 'Cazún', 'Bonilla', 'Samuel Oswaldo', '2006-03-16', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'fredyos711@gmail.com', NULL, NULL, 'bliza806@gmail.com', NULL, 0, NULL, 0, NULL),
(9, '9963382', '02592144K', '281504434368', 'Cortés', 'Gallego', 'Diego', '2008-05-11', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'jorgecortesnevado@gmail.com', NULL, NULL, 'gallegoevamaria@gmail.com', NULL, 0, NULL, 0, NULL),
(10, '12240647', '49593929H', '281025571337', 'Díaz', 'García', 'Álvaro', '2007-07-14', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'phiolina@gmail.com', NULL, NULL, 'buyator@gmail.com', NULL, 0, NULL, 0, NULL),
(11, '50762439', '04262785B', '281573953460', 'Escalada', 'Galán', 'Gabriel', '2005-11-13', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'alesba@yahoo.es', NULL, NULL, 'pigaruiz@gmail.com', NULL, 0, NULL, 0, NULL),
(12, '13498849', '54732340E', '281651658241', 'Gallardo', 'Pérez', 'Roberto', '2008-11-02', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'maygema@gmail.com', NULL, NULL, 'gemita.perez20@gmail.com', NULL, 0, NULL, 0, NULL),
(13, '13482091', '03486572W', '281453285056', 'García', 'Bueno', 'Alejandro', '2008-04-26', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'juanfcogarcianavarro@gmail.com', NULL, NULL, 'gemabuenoverde162@gmail.com', NULL, 0, NULL, 0, NULL),
(14, '10166048', '54287520E', NULL, 'García', 'Deacal', 'Mario', '2007-10-30', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'azudeacalpena@gmail.com', NULL, NULL, 'cgarciab@fernandezmolina.com', NULL, 0, NULL, 0, NULL),
(15, '9991187', '54994483B', '281547505095', 'Garnacho', 'Linares', 'Roberto', '2006-11-15', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'rubengarnacho35@hotmail.es', NULL, NULL, 'b.linares.sanchez@gmail.com', NULL, 0, NULL, 0, NULL),
(16, '10440964', '55127182T', '281614418628', 'Giraldo', 'Arias', 'Juan Pablo', '2006-01-14', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'fgiraldo45@gmail.com', NULL, NULL, 'yamile314@gmail.com', NULL, 0, NULL, 0, NULL),
(17, '10615935', '48159046J', '281577759395', 'González', 'Alcocer', 'Rubén', '2006-07-18', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'josegarrillas@hotmail.com', NULL, NULL, 'kamialco@hotmail.com', NULL, 0, NULL, 0, NULL),
(18, '10227398', '55581259B', '281617877686', 'González', 'Parrondo', 'Itzan', '2008-07-08', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'kik_mikr@hotmail.com', NULL, NULL, 'itzan878@gmail.com', NULL, 0, NULL, 0, NULL),
(19, '9786011', '47318754A', '281578239042', 'Gutiérrez', 'Lucio', 'Jorge', '2006-08-24', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'guti10agv@hotmail.com', NULL, NULL, 'alsosanjo@hotmail.com', NULL, 0, NULL, 0, NULL),
(20, '9854796', '49746404A', '281579749616', 'Hurtado', 'Paredes', 'Darío', '2006-12-09', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'thejahc@gmail.com', NULL, NULL, 'rparedessierra@gmail.com', NULL, 0, NULL, 0, NULL),
(21, '13694494', '49704977E', '281356573228', 'Ikubor', 'Aimienoho', 'Wisdom Osamwenuyi', '2008-11-06', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'linda.aimienoho@yahoo.com', NULL, NULL, 'kevinvincent10@yahoo.com', NULL, 0, NULL, 0, NULL),
(22, '7626425', '55138304J', '281547259868', 'Jiménez', 'Ramiro', 'Roberto', '2006-10-23', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'narcisarg@gmail.com', NULL, 0, NULL, 0, NULL),
(23, '12748235', '06625477M', '281631745252', 'López', 'López', 'José Ignacio', '2007-10-01', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'belencalzada8@gmail.com', NULL, NULL, 'ignaciolopez-999@outlook.es', NULL, 0, NULL, 0, NULL),
(24, '9859524', '54299675X', '281543120901', 'Mangas', 'Martínez', 'Daniel', '2006-07-08', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'rakelmartinezcuesta@gmail.com', NULL, NULL, 'rakelmartinezcuesta@gmail.com', NULL, 0, NULL, 0, NULL),
(25, '12329830', '49589590A', '281079016519', 'Martín', 'León', 'Mario', '2008-03-13', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'gmartin_sanchez@hotmail.com', NULL, NULL, 'mudita-rl@hotmail.com', NULL, 0, NULL, 0, NULL),
(26, '13796525', 'Y1652928R', NULL, 'Mielnik', NULL, 'Bartosz', '2008-08-17', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'krisloro@interia.pl', NULL, NULL, 'dorciam9@interia.pl', NULL, 0, NULL, 0, NULL),
(27, '11831358', '02745808E', '281501532957', 'Moncada', 'Bueno', 'Sebastián', '2007-11-09', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'moncadaosoriodidier@gmail.com', NULL, NULL, 'osoriojuly@hotmail.com', NULL, 0, NULL, 0, NULL),
(28, '14675492', '51818089D', '281375971006', 'Nieto', 'Gómez', 'Daniel', '2006-05-07', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(29, '9854833', '55139532E', '281576636219', 'Orovio', 'Fernández', 'Adrián', '2006-04-21', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'oroviosan@hotmail.com', NULL, NULL, 'anabascuana@gmail.com', NULL, 0, NULL, 0, NULL),
(30, '4545521', '49702188Q', '281444582641', 'Pascual', 'González', 'Alejandro', '1998-02-08', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(31, '9631236', '03489468T', '281462321618', 'Pérez', 'Alonso', 'Rubén', '2005-12-14', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'hectorperezaparicio333@gmail.com', NULL, NULL, 'pilaralonso555@gmail.com', NULL, 0, NULL, 0, NULL),
(32, '12277735', '01867649A', '201055952475', 'Pérez', 'Marín', 'Rubén', '2008-07-12', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'luisamartincamara@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(33, '48532358', 'Z1978609Q', '281655816814', 'Pomarino', 'Mejía', 'Eduardo Gianfranco', '2005-03-25', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'carlascargglioni@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(34, '9019508', '49588427J', '281572721661', 'Puras', 'García', 'César', '2005-08-03', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'tonigarmar@hotmail.es', NULL, NULL, 'cespurn@hotmail.com', NULL, 0, NULL, 0, NULL),
(35, '11550457', '54993476Q', '281550184925', 'Rodríguez', 'Mateos', 'Adrián', '2007-07-26', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'daromanad@gmail.com', NULL, NULL, 'natadriblan@gmail.com', NULL, 0, NULL, 0, NULL),
(36, '10859827', '01867899T', '281323189363', 'Rubio', 'Alonso', 'Antonio', '2007-12-18', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'antoniorubio963@gmail.com', NULL, NULL, 'mareamorada.6@gmail.com', NULL, 0, NULL, 0, NULL),
(37, '12197446', '54033379P', '281537763063', 'Ruiz', 'Blanca', 'Marcos', '2007-08-30', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'dimasruiz242@yahoo.es', NULL, NULL, 'oblancaplaza@yahoo.es', NULL, 0, NULL, 0, NULL),
(38, '13762278', '48161087F', '281560350424', 'Sánchez', 'Martín', 'Diego', '2008-01-04', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'albertorepor@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(39, '9758179', '49150194E', '281576429889', 'Sánchez', 'Palomar', 'Míriam', '2006-03-31', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(40, '9854901', '49591353H', '281632191048', 'Sánchez', 'Sánchez', 'Adrián', '2006-12-20', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'danisanguti@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(41, '10256695', '55004421J', '281677982021', 'Santamera', 'Gallego', 'Marcos', '2008-06-09', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'joseluissantamera@gmail.com', NULL, NULL, 'piluchigallego@gmail.com', NULL, 0, NULL, 0, NULL),
(42, '10602065', '02783538D', '281632217017', 'Serrano', 'Domínguez', 'Alejandro', '2006-05-25', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'lolidomna@gmail.com', NULL, NULL, 'serrano.ema@gmail.com', NULL, 0, NULL, 0, NULL),
(43, '5136117', '55006498C', '281581105996', 'Tello', 'Horcajo', 'Sergio', '2007-05-20', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'dtellosanchez@yahoo.com', NULL, NULL, 'rosahorcajo@yahoo.es', NULL, 0, NULL, 0, NULL),
(44, '12003501', '07137486B', '281524199433', 'Torre', 'Sánchez', 'Adrián de la', '2008-09-26', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'm.carmensanchez.s@hotmail.es', NULL, NULL, 'franciscotorrecasado@hotmail.es', NULL, 0, NULL, 0, NULL),
(45, '5630950', '53720005D', '281439037372', 'Tul', 'Tabango', 'Anderson Joel', '2004-10-21', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 'germaniatabango@gmail.com', NULL, 0, NULL, 0, NULL),
(46, '10408261', '02560357C', '281618790395', 'Vega', 'Sánchez', 'Adrián', '2006-01-24', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'rvhrvhrvh@gmail.com', NULL, NULL, 'monicascla@gmail.com', NULL, 0, NULL, 0, NULL),
(47, '9495357', '55139932P', '281572846650', 'Velázquez', 'Orgaz', 'Francisco', '2005-08-04', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'velazquezdavid11@gmail.com', NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(48, '49939217', 'Y4274170W', '291122260517', 'Zhou', NULL, 'Chengwei', '2005-08-17', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'angelzhang1595@gmail.com', NULL, NULL, 'angelzhang1595@gmail.com', NULL, 0, NULL, 0, NULL),
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
(32, 'alumno', 33, 'gianfrancopome@gmail.com', 'Personal'),
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
(135, 'profesor', 1, 'julio.sanchezfernandez@educa.madrid.org', 'Personal'),
(141, 'empresa', 7, 'mreal@gapd.es', NULL),
(142, 'empresa', 8, 'mariajose.camacho@bisiona.com', NULL),
(143, 'empresa', 9, 'cesar@redphoneservices.com', NULL),
(144, 'empresa', 10, 'juan.fernandez@cw-consulting.es', NULL),
(145, 'empresa', 11, 'kate.laborio@waima.es', NULL),
(146, 'empresa', 12, 'clubvoleibolaranjuez@gmail.com', NULL),
(147, 'empresa', 13, 'fernando@datarecover.es', NULL),
(148, 'empresa', 14, 'jorteso@elecnor.es', NULL),
(149, 'empresa', 15, 'rleonsa@gmail.com', NULL),
(150, 'empresa', 16, 'rrhh@fractalia.es', NULL),
(151, 'empresa', 17, 'administracion@gtpsistemas.es', NULL),
(152, 'empresa', 18, 'fcu.secretariadireccion@clinica.urjc.es', NULL),
(153, 'empresa', 19, 'agomez@globaltis.com', NULL),
(154, 'empresa', 20, 'carmen.munoz@graddo.es', NULL),
(155, 'empresa', 21, 'd.ruiz@grupoitsl.com', NULL),
(156, 'empresa', 22, 'lmartinezp@hiberus.com', NULL),
(157, 'empresa', 23, 'ana.ramiro@info-computer.com', NULL),
(158, 'empresa', 24, 'jorge.arevalo@iberpixel.com', NULL),
(159, 'empresa', 25, 'sprieto@iconestudio.eu', NULL),
(160, 'empresa', 26, 'josemanuelperez@madrid.org', NULL),
(161, 'empresa', 27, 'japrabadan@minsait.com', NULL),
(162, 'empresa', 28, 'victor.hita@inetum.world', NULL),
(163, 'empresa', 30, 'javier.riera@jas.com', NULL),
(164, 'empresa', 31, 'jfrutos@jetcomputer.es', NULL),
(165, 'empresa', 32, 'villaverdesantiagos@johndeere.com', NULL),
(166, 'empresa', 33, 'info@dnainformatica.es', NULL),
(167, 'empresa', 34, 'juliangrande@infoneri.com', NULL),
(168, 'empresa', 35, 'anaborges@landatel.com', NULL),
(169, 'empresa', 36, 'administracion@m2sistemas.com', NULL),
(170, 'empresa', 37, 'jesus.delavieja@parquewarner.com', NULL),
(171, 'empresa', 38, 'raul@memorysistemas.com', NULL),
(172, 'empresa', 39, 'a.poveda@motiva2.net', NULL),
(173, 'empresa', 41, 'e.brites@rbeuropa.com', NULL),
(174, 'empresa', 42, 'paula.castillo@seringe.com', NULL),
(175, 'empresa', 43, 'rosa.robles@tainnde.com', NULL),
(176, 'empresa', 44, 'mbelinchon@viewnext.com', NULL),
(177, 'empresa', 45, 'mcarretero@w3networking.es', NULL),
(178, 'empresa', 46, 'shernandez@zelenza.com', NULL);

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
(6, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Virgilio 21 | 28223 · Madrid · Madrid', NULL, NULL, 1),
(7, 8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Valle del Roncal 12 | 28232 · Las Rozas · Madrid', NULL, NULL, 1),
(8, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Bajada del Salvador 5 | 45223 · Seseña · Toledo', NULL, NULL, 1),
(9, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle López de Hoyos 35 | Planta 1 | 28002 · Madrid · Madrid', NULL, NULL, 1),
(10, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Capellanes 8 | 28902 · Getafe · Madrid', NULL, NULL, 1),
(11, 13, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Las Norias 92 | 28221 · Majadahonda · Madrid', NULL, NULL, 1),
(12, 14, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Marqués de Mondéjar 33 | 28028 · Madrid · Madrid', NULL, NULL, 1),
(13, 15, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Magdalena 36 | Planta Bajo, Puerta 6 | Getafe · Madrid', NULL, NULL, 1),
(14, 16, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Ronda Poniente 2-16 | Bloque Edificio 8, Planta 2 | 28760 · Tres Cantos · Madrid', NULL, NULL, 1),
(15, 17, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Lima 25 | 28945 · Fuenlabrada · Madrid', NULL, NULL, 1),
(16, 18, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Avenida Atenas s/n | 28922 · Alcorcón · Madrid | Campus Alcorcón - Universidad Rey Juan Carlos  Avda. Europa esquina con Calle Estambul (es la entrada más cercana a la clínica.)  Edificio Clínica Universitaria – 2ª planta - Despacho 2041 Secretaría de dirección (Saliendo del ascensor a mano izquierda de', NULL, NULL, 1),
(17, 20, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Avenida Rosales 42 | Bloque 4, Planta 1ª, Puerta Derecha | Getafe · Madrid', NULL, NULL, 1),
(18, 21, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Progreso 2 | 28906 · Getafe · Madrid | Oficina 214', NULL, NULL, 1),
(19, 22, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Paseo Isabel la Católica 6 | 50009 · Zaragoza · Zaragoza', NULL, NULL, 1),
(20, 23, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Avenida Rosales 42 | 28021 · Madrid · Madrid | Bloque 3, nave 210, 211, 212', NULL, NULL, 1),
(21, 24, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Patrimonio Mundial 7 | Puerta Oficina 57 | 28300 · Aranjuez · Madrid', NULL, NULL, 1),
(22, 26, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Fuenlabrada 92 | 28981 · Parla · Madrid', NULL, NULL, 1),
(23, 27, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Roc Boronat 133 | 08018 · Barcelona · Barcelona', NULL, NULL, 1),
(24, 28, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Travesía Costa Brava 4 | 28034 · Madrid · Madrid', NULL, NULL, 1),
(25, 29, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Paseo de la Castellana 79 | Planta 7 | 28046 · Madrid · Madrid', NULL, NULL, 1),
(26, 30, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Avenida de Suiza 18-20 | Planta 2 | 28821 · Coslada · Madrid', NULL, NULL, 1),
(27, 31, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Fernando Alonso 32 | 28914 · Leganés · Madrid', NULL, NULL, 1),
(28, 32, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Carretera A-42 12,200 | 28905 · Getafe · Madrid', NULL, NULL, 1),
(29, 33, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Andalucía 24 | Planta 3, Puerta B | 28903 · Getafe · Madrid', NULL, NULL, 1),
(30, 34, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Avenida Reyes Católicos 5 | Planta 4, Puerta B | 28802 · Alcalá de Henares · Madrid', NULL, NULL, 1),
(31, 36, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Avenida Real de Pinto 91 | 28021 · Madrid · Madrid | Posterior. Nave A14', NULL, NULL, 1),
(32, 37, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Carretera M-301 15,500 | 28330 · San Martín de la Vega · Madrid | Parque Warner Madrid', NULL, NULL, 1),
(33, 38, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Valle de Tobalina 42 | 28021 · Madrid · Madrid', NULL, NULL, 1),
(34, 39, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Santa Leonor 75 | Bloque Edificio E, Planta 4, Puerta Izquierda | 28037 · Madrid · Madrid', NULL, NULL, 1),
(35, 40, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle General Arfrando 7 | 28010 · Madrid · Madrid', NULL, NULL, 1),
(36, 41, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Horno Nave 51 | 45230 · Numancia de la Sagra · Toledo | Polígono Industrial Villa', NULL, NULL, 1),
(37, 42, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Magdalena 14 | 28901 · Getafe · Madrid', NULL, NULL, 1),
(38, 43, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Plaza Villafranca de los Barros 4 | 28034 · Madrid · Madrid | Posterior', NULL, NULL, 1),
(39, 44, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Avenida Burgos 8A | 28036 · Madrid · Madrid | 6ª planta', NULL, NULL, 1),
(40, 45, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calle Desarrollo 5 | 28906 · Getafe · Madrid', NULL, NULL, 1),
(41, 46, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Avenida San Diego 1 | 28053 · Madrid · Madrid', NULL, NULL, 1);

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

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id_empresa`, `cif`, `nombre`, `apellido1`, `apellido2`, `convenio`, `notas`) VALUES
(7, 'A28634046', 'Algoritmos, Procesos y Diseños, S.A.', '', '', 374, NULL),
(8, NULL, 'Bisiona Business Solutions, S.L.', '', '', 577, NULL),
(9, '02767448L', 'César Martin', 'Lara', 'Bastidas', 917, NULL),
(10, 'B87159117', 'Cloud Workspace Consulting, S.L.', '', '', 581, NULL),
(11, 'B87216024', 'CloudAPPi, S.L.', '', '', 498, NULL),
(12, NULL, 'Club Voleibol Aranjuez', '', '', NULL, NULL),
(13, 'B84837947', 'Data Recover, S.L.', '', '', NULL, NULL),
(14, 'A79486833', 'Elecnor Servicios y Proyecto, S.A.U.', '', '', 424, NULL),
(15, 'B85669778', 'ELEOOS Solutions, S.L.', '', '', 583, NULL),
(16, 'B84933894', 'Fractalia IT Systems España', '', '', 593, NULL),
(17, 'B84523786', 'Fuenlabrada GPT Sistemas, S.L.', '', '', 1350, NULL),
(18, 'G87063285', 'Fundación de la Clínica Universitaria de la Universidad Rey ', '', '', 900, NULL),
(19, 'B83542357', 'Globaltis', '', '', 588, NULL),
(20, NULL, 'Graddo II', '', '', 586, NULL),
(21, 'B87999504', 'Gruservit, Grupo De Servicios Informaticos y Tecnologia, S.L', '', '', 960, NULL),
(22, NULL, 'Hiberus Sistemas Informaticos', '', '', 587, NULL),
(23, 'B84425420', 'Ibérica Infocomputer, S.L.', '', '', 1325, NULL),
(24, 'B83835645', 'Iberpixel, S.L.', '', '', 663, NULL),
(25, NULL, 'Iconestudios', '', '', 590, NULL),
(26, NULL, 'IES La Laguna', '', '', NULL, NULL),
(27, 'B82627019', 'Indra Business Consulting S.L.', '', '', NULL, NULL),
(28, 'A28855260', 'Inetum España, S.A.U.', '', '', 582, NULL),
(29, 'B88129218', 'Innovaciones Tecnológicas de Informática y Comunicaciones, S', '', '', NULL, NULL),
(30, 'B64989213', 'Jas Worlwide, S.L.', '', '', 571, NULL),
(31, 'B82049602', 'Jet Computer, S.L.', '', '', 805, NULL),
(32, 'A28061075', 'Jhon Deere Ibérica, S.A.', '', '', NULL, NULL),
(33, '47314697V', 'José Luis', 'Julián', 'Tebar', 715, NULL),
(34, '52100377H', 'Julián', 'Grande', 'Santamaría', 1320, NULL),
(35, 'B83170944', 'Landatel Comunicaciones, S.L.', '', '', 589, NULL),
(36, 'B86870904', 'M2 Sistemas Informáticos, S.L.', '', '', 573, NULL),
(37, 'B83331041', 'Madrid Theme Park Management S.L.U.', '', '', 408, NULL),
(38, 'B81848749', 'Memory Sistemas Informáticos, S.L.', '', '', 433, NULL),
(39, 'B83184598', 'Motiva Consulting, S.L.', '', '', 579, NULL),
(40, 'B87625109', 'My City Stay, S.L.', '', '', 592, NULL),
(41, 'B87208070', 'RB Europa', '', '', 576, NULL),
(42, 'A28843159', 'Seringe, S.A.', '', '', 1322, NULL),
(43, 'B87250478', 'Talento, Innovación y Desarrollo, S.L.', '', '', NULL, NULL),
(44, 'A80157746', 'ViewNext, S.A.', '', '', 558, NULL),
(45, 'B86664661', 'W3 Networking, S.L.', '', '', 578, NULL),
(46, 'B86218609', 'Zelenza, S.L.', '', '', NULL, NULL);

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
(6, 7, '', NULL, 'Mercedes Real', NULL, NULL),
(7, 8, '', NULL, 'María José Camacho', NULL, NULL),
(8, 9, '', NULL, 'César Martin Lara', NULL, NULL),
(9, 10, '', NULL, 'Fátima', NULL, NULL),
(10, 11, '', NULL, 'Mary Kate Laborio', NULL, NULL),
(11, 12, '', NULL, 'Alejandro Morillo', NULL, NULL),
(12, 13, '', NULL, 'Fernando Hípola', NULL, NULL),
(13, 14, '', NULL, 'Javier Orteso', NULL, NULL),
(14, 15, '', NULL, 'Rafael León', NULL, NULL),
(15, 16, '', NULL, 'Sonia', NULL, NULL),
(16, 17, '', NULL, 'Elena Cimarra', NULL, NULL),
(17, 18, '', NULL, 'Nuria Meitín', NULL, NULL),
(18, 19, '', NULL, 'Alejandro Gómez', NULL, NULL),
(19, 20, '', NULL, 'Mª Carmen Muñoz', NULL, NULL),
(20, 21, '', NULL, 'Daniel Ruiz', NULL, NULL),
(21, 22, '', NULL, 'Leticia Martínez', NULL, NULL),
(22, 23, '', NULL, 'Ana Ramiro', NULL, NULL),
(23, 24, '', NULL, 'Jorge Arévalo', NULL, NULL),
(24, 25, '', NULL, 'Sandra Prieto', NULL, NULL),
(25, 26, '', NULL, 'José Manuel Pérez', NULL, NULL),
(26, 27, '', NULL, 'José Antonio Pérez', NULL, NULL),
(27, 28, '', NULL, 'Victor Manuel Hita', NULL, NULL),
(28, 30, '', NULL, 'Javier Riera', NULL, NULL),
(29, 31, '', NULL, 'Jorge López', NULL, NULL),
(30, 32, '', NULL, 'Santiago Villaverde', NULL, NULL),
(31, 33, '', NULL, 'José Luis Julián', NULL, NULL),
(32, 34, '', NULL, 'Julián Grande', NULL, NULL),
(33, 35, '', NULL, 'Ana María Borges', NULL, NULL),
(34, 36, '', NULL, 'Sofía León', NULL, NULL),
(35, 37, '', NULL, 'Jesús de la Vieja', NULL, NULL),
(36, 38, '', NULL, 'Raúl Navarro', NULL, NULL),
(37, 39, '', NULL, 'Anabel Poveda', NULL, NULL),
(38, 41, '', NULL, 'Eliana Brites', NULL, NULL),
(39, 42, '', NULL, 'Paula del Castillo', NULL, NULL),
(40, 43, '', NULL, 'Rosa Robles', NULL, NULL),
(41, 44, '', NULL, 'María Belinchón', NULL, NULL),
(42, 45, '', NULL, 'Marta Carretero', NULL, NULL),
(43, 46, '', NULL, 'Soraya Hernández', NULL, NULL);

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

--
-- Volcado de datos para la tabla `grupos_tutores`
--

INSERT INTO `grupos_tutores` (`id_grupo_tutor`, `id_grupo`, `id_profesor`, `id_curso_escolar`) VALUES
(1, 4, 1, 1),
(2, 3, 6, 1),
(3, 5, 10, 1),
(4, 7, 11, 1),
(5, 9, 12, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `localidades`
--

CREATE TABLE `localidades` (
  `id_localidad` int(10) UNSIGNED NOT NULL,
  `id_provincia` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `cp` varchar(10) DEFAULT NULL
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
(1, 14, 2, 1),
(2, 11, 3, 1),
(3, 15, 4, 1),
(4, 16, 5, 1),
(5, 9, 1, 1),
(6, 8, 6, 1),
(7, 12, 7, 1),
(8, 13, 8, 1),
(9, 10, 9, 1),
(10, 22, 1, 1),
(11, 61, 1, 1);

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
(1, 'Firmado por el representante de la empresa'),
(2, 'Firmado por el director del centro'),
(3, 'Firmado por el tutor en la empresa'),
(4, 'Firmado por el tutor en el centro'),
(5, 'Firmado por el alumno');

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
(1, 'Sin asignar'),
(2, 'Asignada'),
(3, 'En curso'),
(4, 'Anulada'),
(5, 'Terminada');

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
(1, 'Sanchez', 'Fernandez', 'Julio', '52509464G'),
(2, 'Almodovar', 'Vialas', 'Sonia', NULL),
(3, 'Perez', 'Romero', 'Esteban', NULL),
(4, 'Villoria', 'Valiente', 'Cristina', NULL),
(5, 'Perez', 'Pinillos', 'Daniel', NULL),
(6, 'Ruescas', 'Cruz', 'Yolanda', '49095015C'),
(7, 'Perez', 'Revuelta', 'Inmaculada', NULL),
(8, 'Saldaña', 'Plaza', 'Marta', NULL),
(9, 'González', 'Palacios', 'Isidro', NULL),
(10, 'Garcia', 'Fernandez', 'David', '50961088B'),
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
(135, 'profesor', 1, '649496323', 'Personal'),
(140, 'empresa', 7, '678527255', NULL),
(141, 'empresa', 8, '916266050', NULL),
(142, 'empresa', 9, '622711605', NULL),
(143, 'empresa', 10, '630318527', NULL),
(144, 'empresa', 13, '606563024', NULL),
(145, 'empresa', 14, '608100381', NULL),
(146, 'empresa', 15, '629238899', NULL),
(147, 'empresa', 16, '917994070', NULL),
(148, 'empresa', 17, '91 492 09 90', NULL),
(149, 'empresa', 18, '914888664', NULL),
(150, 'empresa', 19, '916651232', NULL),
(151, 'empresa', 20, '603414847', NULL),
(152, 'empresa', 21, '620541219', NULL),
(153, 'empresa', 22, '976106620', NULL),
(154, 'empresa', 23, '910771230', NULL),
(155, 'empresa', 24, '675950134', NULL),
(156, 'empresa', 26, '607762221', NULL),
(157, 'empresa', 27, '961001789', NULL),
(158, 'empresa', 28, '679740541', NULL),
(159, 'empresa', 31, '639131055', NULL),
(160, 'empresa', 32, '914958465', NULL),
(161, 'empresa', 33, '610711636', NULL),
(162, 'empresa', 34, '607419044', NULL),
(163, 'empresa', 35, '911461700', NULL),
(164, 'empresa', 36, '911610392', NULL),
(165, 'empresa', 37, '635122115', NULL),
(166, 'empresa', 38, '669703997', NULL),
(167, 'empresa', 39, '637569787', NULL),
(168, 'empresa', 41, '606901136', NULL),
(169, 'empresa', 42, '600710980', NULL),
(170, 'empresa', 43, '640071938', NULL),
(171, 'empresa', 45, '682436145', NULL),
(172, 'empresa', 46, '639277346', NULL);

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
  MODIFY `id_correo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

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
  MODIFY `id_direccion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id_empresa` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `empresas_contactos`
--
ALTER TABLE `empresas_contactos`
  MODIFY `id_empresa_contacto` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

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
  MODIFY `id_grupo_tutor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT de la tabla `modulos_profesores`
--
ALTER TABLE `modulos_profesores`
  MODIFY `id_modulo_profesor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `niveles`
--
ALTER TABLE `niveles`
  MODIFY `id_nivel` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `no_lectivos`
--
ALTER TABLE `no_lectivos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- AUTO_INCREMENT de la tabla `practicas_anexos_estados`
--
ALTER TABLE `practicas_anexos_estados`
  MODIFY `id_practicas_anexo_estado` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT de la tabla `telefonos`
--
ALTER TABLE `telefonos`
  MODIFY `id_telefono` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

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
-- Filtros para la tabla `provincias`
--
ALTER TABLE `provincias`
  ADD CONSTRAINT `fk_prov_pais` FOREIGN KEY (`id_pais`) REFERENCES `paises` (`id_pais`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
