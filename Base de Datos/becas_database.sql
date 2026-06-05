-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-04-2026 a las 03:09:42
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
-- Base de datos: `becas_database`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante`
--

CREATE TABLE `estudiante` (
  `ci` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `nombre1` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nombre2` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `apellido_paterno` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `apellido_materno` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `f_nac` date DEFAULT NULL,
  `edad` int DEFAULT NULL,
  `tel_estudiante` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correo` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `edo_civil` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_beneficio` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `C_Patria` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `viaja` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estatus_estudio` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `carrera` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cod_est` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `f_ingreso` date DEFAULT NULL,
  `trayecto` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `trimestre` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ira_anterior` decimal(4,2) DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`ci`) /*T![clustered_index] NONCLUSTERED */,
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estudiante`
--

INSERT INTO `estudiante` (`ci`, `usuario_id`, `nombre1`, `nombre2`, `apellido_paterno`, `apellido_materno`, `f_nac`, `edad`, `tel_estudiante`, `correo`, `edo_civil`, `tipo_beneficio`, `C_Patria`, `viaja`, `estatus_estudio`, `carrera`, `cod_est`, `f_ingreso`, `trayecto`, `trimestre`, `ira_anterior`, `observaciones`) VALUES
(32546208, 1, 'Hely', 'Josue', 'Oberto', 'Monasterio', '2006-03-04', 20, '04121402787', 'helyobertom@gmail.com', 'soltero', 'Beca_Estudiantil/Universitaria', '', 'no', 'activo', 'informatica', 'INT1234567', '2023-10-08', '2', '2', 18.00, 'Es el mejor pije');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `familiar`
--

CREATE TABLE `familiar` (
  `id` int NOT NULL,
  `ci_estudiante` int DEFAULT NULL,
  `f_nom` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `f_ape` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `f_par` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `f_eda` int DEFAULT NULL,
  `f_ins` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `f_ocu` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `f_ing` decimal(12,2) DEFAULT NULL,
  `f_clasificacion` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`) /*T![clustered_index] NONCLUSTERED */,
  KEY `ci_estudiante` (`ci_estudiante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `familiar`
--

INSERT INTO `familiar` (`id`, `ci_estudiante`, `f_nom`, `f_ape`, `f_par`, `f_eda`, `f_ins`, `f_ocu`, `f_ing`) VALUES
(1, 32546208, 'Gloria', 'Monasterios', 'Madre', 60, 'Profesional', 'Contadora', 2000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `record_academico`
--

CREATE TABLE `record_academico` (
  `id` int NOT NULL,
  `ci_estudiante` int DEFAULT NULL,
  `indice_trimestre` decimal(4,2) DEFAULT NULL,
  `ira_anterior` decimal(4,2) DEFAULT NULL,
  PRIMARY KEY (`id`) /*T![clustered_index] NONCLUSTERED */,
  KEY `ci_estudiante` (`ci_estudiante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `record_academico`
--

INSERT INTO `record_academico` (`id`, `ci_estudiante`, `indice_trimestre`, `ira_anterior`) VALUES
(1, 32546208, NULL, 18.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `residencia`
--

CREATE TABLE `residencia` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `ci_estudiante` int DEFAULT NULL,
  `t_res` varchar(50) DEFAULT NULL,
  `t_viv` varchar(50) DEFAULT NULL,
  `t_loc` varchar(50) DEFAULT NULL,
  `r_prop` varchar(50) DEFAULT NULL,
  `estado_res` varchar(50) DEFAULT NULL,
  `municipio_res` varchar(50) DEFAULT NULL,
  `dir_local` text DEFAULT NULL,
  `dir_procedencia` text NOT NULL,
  `tel_local` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin AUTO_INCREMENT=660001;

--
-- Volcado de datos para la tabla `residencia`
--

INSERT INTO `residencia` (`id`, `ci_estudiante`, `t_res`, `t_viv`, `t_loc`, `r_prop`, `estado_res`, `municipio_res`, `dir_local`, `tel_local`) VALUES
(1, 32546208, 'familiar', 'casa', 'urbano', 'comodato', 'Falcón', 'Miranda', 'Los perozos', '04121402787');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `pregunta_seguridad` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `respuesta_seguridad` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rol` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'estudiante',
  PRIMARY KEY (`id`) /*T![clustered_index] NONCLUSTERED */,
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `password`, `pregunta_seguridad`, `respuesta_seguridad`, `rol`) VALUES
(1, '32546208', '$2y$10$z.xixekm1AVnscpufBAVj.hREeJ7ivfGqWZnRBHnZdnBHFgcN0tVu', NULL, NULL, 'estudiante');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora`
--

CREATE TABLE `bitacora` (
  `id` bigint NOT NULL /*T![auto_rand] AUTO_RANDOM(5) */,
  `usuario_id` int DEFAULT NULL,
  `accion` varchar(255) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `detalles` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin /*T![auto_rand_base] AUTO_RANDOM_BASE=1710001 */;


--
-- Índices para tablas volcadas y AUTO_INCREMENT se manejan en la declaración de las tablas para cumplir el estándar de TiDB.
--

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `estudiante`
--
ALTER TABLE `estudiante`
  ADD CONSTRAINT `estudiante_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `familiar`
--
ALTER TABLE `familiar`
  ADD CONSTRAINT `familiar_ibfk_1` FOREIGN KEY (`ci_estudiante`) REFERENCES `estudiante` (`ci`) ON DELETE CASCADE;

--
-- Filtros para la tabla `record_academico`
--
ALTER TABLE `record_academico`
  ADD CONSTRAINT `record_academico_ibfk_1` FOREIGN KEY (`ci_estudiante`) REFERENCES `estudiante` (`ci`) ON DELETE CASCADE;

--
-- Filtros para la tabla `residencia`
--
ALTER TABLE `residencia`
  ADD CONSTRAINT `residencia_ibfk_1` FOREIGN KEY (`ci_estudiante`) REFERENCES `estudiante` (`ci`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;