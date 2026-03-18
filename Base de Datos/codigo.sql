-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-03-2026 a las 04:43:09
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
-- Base de datos: `proyecto_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas`
--

CREATE TABLE `alertas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT 'info',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `becas`
--

CREATE TABLE `becas` (
  `id` int(11) NOT NULL,
  `tipo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `becas`
--

INSERT INTO `becas` (`id`, `tipo`, `descripcion`, `created_at`, `updated_at`) VALUES
(1, 'Beca General', 'Bequita asignada por defecto', '2026-03-13 03:27:06', '2026-03-13 03:27:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `id` int(11) NOT NULL,
  `ci` varchar(30) DEFAULT NULL,
  `carnet_patria` varchar(50) DEFAULT NULL,
  `nombres` varchar(150) NOT NULL,
  `apellidos` varchar(150) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `edad` tinyint(4) DEFAULT NULL,
  `estado_civil` varchar(50) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `estatus_estudio` varchar(100) DEFAULT NULL,
  `pnf_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_becas`
--

CREATE TABLE `estudiante_becas` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `beca_id` int(11) NOT NULL,
  `fecha_solicitud` date DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_materias`
--

CREATE TABLE `estudiante_materias` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `trimestre` varchar(50) DEFAULT NULL,
  `nota` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `familiares`
--

CREATE TABLE `familiares` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `nombres` varchar(150) NOT NULL,
  `apellidos` varchar(150) NOT NULL,
  `parentesco` varchar(100) DEFAULT NULL,
  `edad` tinyint(4) DEFAULT NULL,
  `instruccion` varchar(150) DEFAULT NULL,
  `ocupacion` varchar(150) DEFAULT NULL,
  `ingreso` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`id`, `nombre`, `created_at`, `updated_at`) VALUES
(1, 'Agroalimentacion', '2026-03-02 02:24:45', '2026-03-02 02:24:45'),
(2, 'Algoritmica', '2026-03-02 02:24:45', '2026-03-02 02:24:45'),
(3, 'Arquitectura', '2026-03-02 02:24:45', '2026-03-02 02:24:45'),
(4, 'Auditoria', '2026-03-02 02:24:45', '2026-03-02 02:24:45'),
(5, 'Bases Datos', '2026-03-02 02:24:45', '2026-03-02 02:24:45'),
(6, 'Formacion', '2026-03-02 02:24:45', '2026-03-02 02:24:45'),
(7, 'Ingenieria Sw', '2026-03-02 02:24:45', '2026-03-02 02:24:45'),
(8, 'Instalaciones', '2026-03-13 03:27:06', '2026-03-13 03:27:06'),
(9, 'Administracion', '2026-03-15 03:19:01', '2026-03-15 03:19:01'),
(10, 'Instrumentacion', '2026-03-15 03:19:01', '2026-03-15 03:19:01'),
(11, 'Biologia', '2026-03-17 02:35:17', '2026-03-17 02:35:17'),
(12, 'Circuitos', '2026-03-17 02:35:17', '2026-03-17 02:35:17'),
(13, 'Contabilidad', '2026-03-17 02:35:17', '2026-03-17 02:35:17'),
(14, 'Control', '2026-03-17 03:16:19', '2026-03-17 03:16:19'),
(15, 'Deporte', '2026-03-17 03:16:19', '2026-03-17 03:16:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pnfs`
--

CREATE TABLE `pnfs` (
  `id` int(11) NOT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `trayecto` varchar(100) DEFAULT NULL,
  `trimestre_actual` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `carrera` varchar(255) DEFAULT NULL,
  `codigo_estudiante` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pnfs`
--

INSERT INTO `pnfs` (`id`, `fecha_ingreso`, `trayecto`, `trimestre_actual`, `created_at`, `updated_at`, `carrera`, `codigo_estudiante`) VALUES
(11, '2025-06-17', '2', '1', '2026-03-15 03:34:53', '2026-03-15 03:34:53', 'informatica', '123123'),
(12, '2026-03-01', '2', '1', '2026-03-17 02:35:17', '2026-03-17 02:35:17', 'informatica', '123123'),
(13, '2026-03-03', '1', '1', '2026-03-17 03:11:01', '2026-03-17 03:11:01', 'informatica', '21312312312'),
(14, '2026-03-03', '1', '2', '2026-03-17 03:16:19', '2026-03-17 03:22:33', 'construccion_civil', '123123');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `records_academicos`
--

CREATE TABLE `records_academicos` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) DEFAULT NULL,
  `codigo_estudiante` varchar(100) DEFAULT NULL,
  `n_materias_inscritas` tinyint(4) DEFAULT NULL,
  `n_materias_aprobadas` tinyint(4) DEFAULT NULL,
  `n_materias_aplazadas` tinyint(4) DEFAULT NULL,
  `n_materias_inasistentes` tinyint(4) DEFAULT NULL,
  `indice_trimestre` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `residencias`
--

CREATE TABLE `residencias` (
  `id` int(11) NOT NULL,
  `numero` varchar(50) DEFAULT NULL,
  `tipo_vivienda` varchar(150) DEFAULT NULL,
  `tipo_estructura` varchar(150) DEFAULT NULL,
  `tipo_localidad` varchar(150) DEFAULT NULL,
  `regimen_propiedad` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `monto_bs` decimal(12,2) DEFAULT NULL,
  `estudiante_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `residencias`
--

INSERT INTO `residencias` (`id`, `numero`, `tipo_vivienda`, `tipo_estructura`, `tipo_localidad`, `regimen_propiedad`, `direccion`, `telefono`, `monto_bs`, `estudiante_id`, `created_at`, `updated_at`) VALUES
(9, 'familiar', 'casa', 'rancho', 'rancho', 'propia', 'asdsad', '2131231', NULL, NULL, '2026-03-15 03:34:53', '2026-03-15 03:34:53'),
(10, 'familiar', 'casa', 'rural', 'rural', 'propia', 'dasdasd', '121231', NULL, NULL, '2026-03-17 02:35:17', '2026-03-17 02:35:17'),
(11, 'familiar', 'casa', 'rural', 'rural', 'propia', 'asdasd', '23123123', NULL, NULL, '2026-03-17 03:11:01', '2026-03-17 03:11:01'),
(13, 'familiar', 'casa', 'rural', 'rural', 'propia', '2wqweqwe', '3123123', NULL, NULL, '2026-03-17 03:22:33', '2026-03-17 03:22:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trabajos`
--

CREATE TABLE `trabajos` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) DEFAULT NULL,
  `lugar` varchar(255) DEFAULT NULL,
  `ingreso` varchar(100) DEFAULT NULL,
  `monto_bs` decimal(12,2) DEFAULT NULL,
  `aportador` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(20) DEFAULT 'estudiante'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `password`, `rol`) VALUES
(1, 'admin', '5994471abb01112afcc18159f6cc74b4f511b99806da59b3caf5a9c173cacfc5', 'admin');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alertas`
--
ALTER TABLE `alertas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `becas`
--
ALTER TABLE `becas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ci` (`ci`),
  ADD KEY `pnf_id` (`pnf_id`),
  ADD KEY `fk_estudiantes_usuarios` (`usuario_id`);

--
-- Indices de la tabla `estudiante_becas`
--
ALTER TABLE `estudiante_becas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estudiante_id` (`estudiante_id`),
  ADD KEY `beca_id` (`beca_id`);

--
-- Indices de la tabla `estudiante_materias`
--
ALTER TABLE `estudiante_materias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estudiante_id` (`estudiante_id`),
  ADD KEY `materia_id` (`materia_id`);

--
-- Indices de la tabla `familiares`
--
ALTER TABLE `familiares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estudiante_id` (`estudiante_id`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pnfs`
--
ALTER TABLE `pnfs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `records_academicos`
--
ALTER TABLE `records_academicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `estudiante_id` (`estudiante_id`),
  ADD KEY `estudiante_id_2` (`estudiante_id`);

--
-- Indices de la tabla `residencias`
--
ALTER TABLE `residencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `estudiante_id` (`estudiante_id`),
  ADD KEY `estudiante_id_2` (`estudiante_id`);

--
-- Indices de la tabla `trabajos`
--
ALTER TABLE `trabajos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `estudiante_id` (`estudiante_id`),
  ADD KEY `estudiante_id_2` (`estudiante_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alertas`
--
ALTER TABLE `alertas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `becas`
--
ALTER TABLE `becas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `estudiante_becas`
--
ALTER TABLE `estudiante_becas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `estudiante_materias`
--
ALTER TABLE `estudiante_materias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de la tabla `familiares`
--
ALTER TABLE `familiares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `pnfs`
--
ALTER TABLE `pnfs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `records_academicos`
--
ALTER TABLE `records_academicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `residencias`
--
ALTER TABLE `residencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `trabajos`
--
ALTER TABLE `trabajos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD CONSTRAINT `fk_estudiantes_pnf` FOREIGN KEY (`pnf_id`) REFERENCES `pnfs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_estudiantes_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estudiante_becas`
--
ALTER TABLE `estudiante_becas`
  ADD CONSTRAINT `fk_estudiante_becas_beca` FOREIGN KEY (`beca_id`) REFERENCES `becas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_estudiante_becas_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiante_materias`
--
ALTER TABLE `estudiante_materias`
  ADD CONSTRAINT `fk_estudentematerias_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_estudentematerias_materia` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `familiares`
--
ALTER TABLE `familiares`
  ADD CONSTRAINT `fk_familiares_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `records_academicos`
--
ALTER TABLE `records_academicos`
  ADD CONSTRAINT `fk_records_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `residencias`
--
ALTER TABLE `residencias`
  ADD CONSTRAINT `fk_residencias_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `trabajos`
--
ALTER TABLE `trabajos`
  ADD CONSTRAINT `fk_trabajos_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
