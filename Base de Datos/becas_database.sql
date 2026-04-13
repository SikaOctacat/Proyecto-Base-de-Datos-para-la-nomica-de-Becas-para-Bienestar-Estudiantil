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
  `ci` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nombre1` varchar(50) DEFAULT NULL,
  `nombre2` varchar(50) DEFAULT NULL,
  `apellido_paterno` varchar(50) DEFAULT NULL,
  `apellido_materno` varchar(50) DEFAULT NULL,
  `f_nac` date DEFAULT NULL,
  `edad` int(3) DEFAULT NULL,
  `tel_estudiante` varchar(20) DEFAULT NULL,
  `correo` varchar(60) DEFAULT NULL,
  `edo_civil` varchar(30) DEFAULT NULL,
  `tipo_beneficio` varchar(50) DEFAULT NULL,
  `C_Patria` varchar(50) DEFAULT NULL,
  `viaja` varchar(10) DEFAULT NULL,
  `estatus_estudio` varchar(20) DEFAULT NULL,
  `carrera` varchar(100) DEFAULT NULL,
  `cod_est` varchar(50) DEFAULT NULL,
  `f_ingreso` date DEFAULT NULL,
  `trayecto` varchar(10) DEFAULT NULL,
  `trimestre` varchar(10) DEFAULT NULL,
  `ira_anterior` decimal(4,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
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
  `id` int(11) NOT NULL,
  `ci_estudiante` int(11) DEFAULT NULL,
  `f_nom` varchar(100) DEFAULT NULL,
  `f_ape` varchar(100) DEFAULT NULL,
  `f_par` varchar(50) DEFAULT NULL,
  `f_eda` int(3) DEFAULT NULL,
  `f_ins` varchar(50) DEFAULT NULL,
  `f_ocu` varchar(100) DEFAULT NULL,
  `f_ing` decimal(12,2) DEFAULT NULL
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
  `id` int(11) NOT NULL,
  `ci_estudiante` int(11) DEFAULT NULL,
  `indice_trimestre` decimal(4,2) DEFAULT NULL,
  `ira_anterior` decimal(4,2) DEFAULT NULL
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
  `id` int(11) NOT NULL,
  `ci_estudiante` int(11) DEFAULT NULL,
  `t_res` varchar(50) DEFAULT NULL,
  `t_viv` varchar(50) DEFAULT NULL,
  `t_loc` varchar(50) DEFAULT NULL,
  `r_prop` varchar(50) DEFAULT NULL,
  `estado_res` varchar(50) DEFAULT NULL,
  `municipio_res` varchar(50) DEFAULT NULL,
  `dir_local` text DEFAULT NULL,
  `tel_local` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(20) DEFAULT 'estudiante'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `password`, `rol`) VALUES
(1, '32546208', '$2y$10$z.xixekm1AVnscpufBAVj.hREeJ7ivfGqWZnRBHnZdnBHFgcN0tVu', 'estudiante');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `estudiante`
--
ALTER TABLE `estudiante`
  ADD PRIMARY KEY (`ci`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `familiar`
--
ALTER TABLE `familiar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ci_estudiante` (`ci_estudiante`);

--
-- Indices de la tabla `record_academico`
--
ALTER TABLE `record_academico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ci_estudiante` (`ci_estudiante`);

--
-- Indices de la tabla `residencia`
--
ALTER TABLE `residencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ci_estudiante` (`ci_estudiante`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `familiar`
--
ALTER TABLE `familiar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `record_academico`
--
ALTER TABLE `record_academico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `residencia`
--
ALTER TABLE `residencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
