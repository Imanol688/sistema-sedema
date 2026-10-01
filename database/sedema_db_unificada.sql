-- SEDEMA v2.0 - Base unificada Clientes/Usuarios + Despacho/Logística
-- Instalación limpia: este archivo crea y selecciona sedema_db.

CREATE DATABASE IF NOT EXISTS sedema_db
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE sedema_db;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-09-2026 a las 02:34:01
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
-- Base de datos: `sedema_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ajuste`
--

CREATE TABLE `ajuste` (
  `idAjuste` bigint(20) NOT NULL,
  `idPedido` bigint(20) NOT NULL,
  `tipoAjuste` varchar(40) NOT NULL DEFAULT 'DESCUENTO',
  `tipoTarjeta` varchar(50) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00,
  `montoCalculado` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ajuste`
--

INSERT INTO `ajuste` (`idAjuste`, `idPedido`, `tipoAjuste`, `tipoTarjeta`, `descripcion`, `porcentaje`, `montoCalculado`) VALUES
(1, 2, 'DESCUENTO', NULL, 'Descuento comercial', 10.00, 8000.00),
(2, 2, 'DESCUENTO_VOLUMEN', NULL, 'Descuento por volumen', 50.00, 40000.00),
(3, 2, 'RECARGO_TARJETA', 'visa', 'Recargo por financiación con tarjeta', 20.00, 16000.00),
(4, 2, 'COBRANZA_DESCUENTO', NULL, 'Descuento aplicado en cobranza', 10.00, 4800.00),
(5, 3, 'DESCUENTO', NULL, 'Descuento comercial', 20.00, 6400.00),
(6, 3, 'COBRANZA_RECARGO', NULL, 'Recargo aplicado en cobranza', 10.00, 2560.00),
(7, 4, 'DESCUENTO', NULL, 'Descuento comercial', 20.00, 11200.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `almacen`
--

CREATE TABLE `almacen` (
  `idAlmacen` bigint(20) NOT NULL,
  `nombreAlmacen` varchar(100) NOT NULL,
  `direccionAlmacen` varchar(255) NOT NULL,
  `estadoAlmacen` enum('LIBRE','OCUPADO','EN_REFORMA') NOT NULL DEFAULT 'LIBRE',
  `descripcionAlmacen` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auth_audit`
--

CREATE TABLE `auth_audit` (
  `idAudit` bigint(20) NOT NULL,
  `idUsuario` bigint(20) DEFAULT NULL,
  `eventType` enum('LOGIN_OK','LOGIN_FAIL','PASSWORD_RESET','LOGOUT') NOT NULL,
  `ipHash` binary(32) DEFAULT NULL,
  `userAgent` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `auth_audit`
--

INSERT INTO `auth_audit` (`idAudit`, `idUsuario`, `eventType`, `ipHash`, `userAgent`, `createdAt`) VALUES
(1, 1, 'LOGIN_FAIL', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 12:54:52'),
(2, 1, 'LOGIN_FAIL', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 12:55:14'),
(3, 1, 'LOGIN_FAIL', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 12:58:51'),
(4, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 12:59:08'),
(5, 1, 'LOGIN_FAIL', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 13:09:28'),
(6, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 13:09:41'),
(7, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 13:22:37'),
(8, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 16:22:24'),
(9, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 16:43:55'),
(10, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 16:50:24'),
(11, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 16:53:25'),
(12, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 16:55:31'),
(13, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 18:08:52'),
(14, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 18:10:23'),
(15, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 18:11:07'),
(16, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 18:19:59'),
(17, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 20:21:21'),
(18, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 21:06:09'),
(19, 1, 'LOGIN_OK', 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:155.0) Gecko/20100101 Firefox/155.0', '2026-09-03 21:12:55'),
(20, 1, 'LOGIN_OK', 0xe206a34f44ee5c95115072a05d1cd7d72992be5bff7871b8e584fd0b9f9ce0af, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-03 23:41:49'),
(21, 1, 'LOGIN_OK', 0xe206a34f44ee5c95115072a05d1cd7d72992be5bff7871b8e584fd0b9f9ce0af, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-04 00:11:50'),
(22, 1, 'LOGIN_OK', 0xe206a34f44ee5c95115072a05d1cd7d72992be5bff7871b8e584fd0b9f9ce0af, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-04 00:36:19'),
(23, 1, 'LOGIN_FAIL', 0xe206a34f44ee5c95115072a05d1cd7d72992be5bff7871b8e584fd0b9f9ce0af, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-09 21:16:05'),
(24, 1, 'LOGIN_OK', 0xe206a34f44ee5c95115072a05d1cd7d72992be5bff7871b8e584fd0b9f9ce0af, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-09 21:16:13'),
(25, 1, 'LOGIN_OK', 0xe206a34f44ee5c95115072a05d1cd7d72992be5bff7871b8e584fd0b9f9ce0af, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-09 21:58:01'),
(26, 1, 'LOGIN_OK', 0xe206a34f44ee5c95115072a05d1cd7d72992be5bff7871b8e584fd0b9f9ce0af, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 19:03:29'),
(27, 1, 'LOGIN_OK', 0xe206a34f44ee5c95115072a05d1cd7d72992be5bff7871b8e584fd0b9f9ce0af, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 19:13:20'),
(28, 6, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 20:15:55'),
(29, 6, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 20:18:49'),
(30, 1, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 20:22:41'),
(31, 6, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 20:34:42'),
(32, 1, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 20:35:40'),
(33, 1, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 20:48:28'),
(34, NULL, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 20:50:55'),
(35, NULL, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 20:52:34'),
(36, NULL, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 20:53:29'),
(37, 1, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 20:53:57'),
(38, NULL, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 20:56:08'),
(39, 1, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 20:56:55'),
(40, 1, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 21:31:32'),
(41, NULL, 'LOGIN_FAIL', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 21:32:54'),
(42, NULL, 'LOGIN_FAIL', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 21:34:07'),
(43, NULL, 'LOGIN_FAIL', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 21:34:15'),
(44, NULL, 'LOGIN_FAIL', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 21:34:49'),
(45, 1, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 21:45:24'),
(46, 6, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 21:47:27'),
(47, 1, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 21:48:25'),
(48, 6, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 21:49:59'),
(49, 1, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 22:01:01'),
(50, 1, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-11 22:06:16'),
(51, 6, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 01:28:50'),
(52, 6, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 21:14:57'),
(53, 1, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 21:15:40'),
(54, 1, 'LOGIN_OK', 0xb041f3a1960bb907a855cadeb38a21eaba03c144f80ab3e434851d1cc0c80d06, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-14 21:32:11'),
(55, 1, 'LOGIN_FAIL', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 20:59:50'),
(56, 1, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 21:00:16'),
(57, 7, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 21:07:50'),
(58, 7, 'LOGIN_OK', 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-16 21:09:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `idCliente` bigint(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cuitDNI` varchar(20) NOT NULL,
  `tipoCliente` enum('PARTICULAR','ALBAÑIL','EMPRESA','MAYORISTA','CONTRATISTA') NOT NULL,
  `razonSocial` varchar(150) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `direccion` varchar(255) NOT NULL,
  `localidad` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`idCliente`, `nombre`, `apellido`, `cuitDNI`, `tipoCliente`, `razonSocial`, `telefono`, `direccion`, `localidad`, `activo`, `createdAt`, `updatedAt`) VALUES
(1, 'Antonella', 'Siacia', '46470993', 'PARTICULAR', NULL, '3704517022', 'jm uriburu 1486', 'formosa', 1, '2026-09-11 22:09:41', '2026-09-11 22:09:41'),
(2, 'franco', 'dominguez', '45970367', 'MAYORISTA', NULL, '3704355561', 'diaz roig 620', 'formosa', 1, '2026-09-14 21:23:22', '2026-09-14 21:23:22'),
(3, 'renzo', 'delturco', '44924096', 'ALBAÑIL', NULL, '3705098802', 'Pedro bonancio 636', 'Formosa', 1, '2026-09-14 21:53:07', '2026-09-14 21:53:07'),
(4, 'lautaro', 'torres', '44908776', 'PARTICULAR', NULL, '3704889044', 'villa del carmen 3889', 'Formosa', 1, '2026-09-16 21:10:37', '2026-09-16 21:10:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `current_account_movement`
--

CREATE TABLE `current_account_movement` (
  `idMovement` bigint(20) UNSIGNED NOT NULL,
  `idCliente` bigint(20) NOT NULL,
  `idPedido` bigint(20) DEFAULT NULL,
  `idPago` bigint(20) DEFAULT NULL,
  `movementType` enum('DEBITO','CREDITO') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `despacho`
--

CREATE TABLE `despacho` (
  `idDespacho` bigint(20) NOT NULL,
  `idPedido` bigint(20) NOT NULL,
  `idVehiculo` bigint(20) DEFAULT NULL,
  `transportista` varchar(150) DEFAULT NULL,
  `fechaHoraProgramada` datetime NOT NULL,
  `destino` varchar(255) NOT NULL,
  `modalidad` enum('EN_CORRALON','ENTREGA_DOMICILIO') NOT NULL,
  `estado` enum('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO') NOT NULL DEFAULT 'EN_ESPERA',
  `observacionesEntrega` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `despacho`
--

INSERT INTO `despacho` (`idDespacho`, `idPedido`, `idVehiculo`, `transportista`, `fechaHoraProgramada`, `destino`, `modalidad`, `estado`, `observacionesEntrega`) VALUES
(1, 1, NULL, 'pedro', '2026-09-12 01:12:00', 'jm uriburu 1486', 'EN_CORRALON', 'EN_ESPERA', NULL),
(2, 3, NULL, NULL, '2026-09-14 22:00:00', 'pedro bonancio 636', 'ENTREGA_DOMICILIO', 'EN_ESPERA', NULL),
(3, 4, NULL, 'pedro', '2026-09-18 21:13:00', 'villa del carmen 3889', 'ENTREGA_DOMICILIO', 'EN_ESPERA', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallepedido`
--

CREATE TABLE `detallepedido` (
  `idDetalle` bigint(20) NOT NULL,
  `idPedido` bigint(20) NOT NULL,
  `idProducto` bigint(20) DEFAULT NULL,
  `idInventoryProduct` bigint(20) UNSIGNED DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `cantidadSolicitada` decimal(12,2) NOT NULL,
  `cantidadPendiente` decimal(12,2) NOT NULL,
  `precioUnitario` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detallepedido`
--

INSERT INTO `detallepedido` (`idDetalle`, `idPedido`, `idProducto`, `idInventoryProduct`, `descripcion`, `observaciones`, `cantidadSolicitada`, `cantidadPendiente`, `precioUnitario`, `subtotal`) VALUES
(1, 1, NULL, 1, 'Cemento Loma Negra', NULL, 30.00, 30.00, 8000.00, 240000.00),
(2, 2, NULL, 1, 'Cemento Loma Negra', NULL, 10.00, 10.00, 8000.00, 80000.00),
(3, 3, NULL, 1, 'Cemento Loma Negra', NULL, 4.00, 4.00, 8000.00, 32000.00),
(4, 4, NULL, 1, 'Cemento Loma Negra', NULL, 7.00, 7.00, 8000.00, 56000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallerecepcion`
--

CREATE TABLE `detallerecepcion` (
  `idDetalleRecepcion` bigint(20) NOT NULL,
  `idRecepcion` bigint(20) NOT NULL,
  `idProducto` bigint(20) NOT NULL,
  `cantidadRecibida` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalleremito`
--

CREATE TABLE `detalleremito` (
  `idDetalleRemito` bigint(20) NOT NULL,
  `idRemitoProveedor` bigint(20) NOT NULL,
  `idProducto` bigint(20) NOT NULL,
  `cantidadSolicitada` decimal(12,2) NOT NULL,
  `precioUnitario` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `idEmpleado` bigint(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `sueldoBase` decimal(12,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`idEmpleado`, `nombre`, `apellido`, `dni`, `telefono`, `sueldoBase`, `activo`) VALUES
(1, 'Admin', 'SEDEMA', 'PENDIENTE-06de46aa', NULL, 0.00, 1),
(2, 'Renzo', 'Delturco', '44.924.096', '3704517093', 1000000.00, 0),
(3, 'Antonella', 'Siacia', '46.924.096', '3704517022', 3000000.00, 1),
(4, 'Renzo', 'Delturco', '44.000.900', '3704558900', 200000.00, 1),
(5, 'franco', 'dominguez', '34566789', '3704355561', 2999999.00, 1),
(6, 'Antonella', 'Siacia', '46470993', NULL, 0.00, 1),
(7, 'Anto', 'Siacia', '46.900.998', NULL, 0.00, 1),
(8, 'franco', 'dominguez', '45970367', NULL, 0.00, 1),
(9, 'renzo', 'delturco', '44998890', NULL, 0.00, 1),
(10, 'paula', 'palacio', '46006036', NULL, 0.00, 1),
(11, 'imanol', 'silvera', '44924097', NULL, 0.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

CREATE TABLE `factura` (
  `idFactura` bigint(20) NOT NULL,
  `idPedido` bigint(20) NOT NULL,
  `nroComprobante` bigint(20) NOT NULL,
  `puntoVenta` int(11) NOT NULL,
  `fechaEmision` datetime NOT NULL DEFAULT current_timestamp(),
  `tipoFactura` enum('A','B','C') NOT NULL,
  `totalFacturado` decimal(12,2) NOT NULL,
  `descuentoAplicado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `recargoAplicado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `firmayAclaracion` varchar(150) DEFAULT NULL,
  `nroEnvio` varchar(50) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `factura`
--

INSERT INTO `factura` (`idFactura`, `idPedido`, `nroComprobante`, `puntoVenta`, `fechaEmision`, `tipoFactura`, `totalFacturado`, `descuentoAplicado`, `recargoAplicado`, `firmayAclaracion`, `nroEnvio`, `observaciones`) VALUES
(1, 2, 1, 1, '2026-09-14 21:54:27', 'B', 43200.00, 48000.00, 16000.00, NULL, NULL, NULL),
(2, 3, 2, 1, '2026-09-14 21:59:56', 'B', 28160.00, 6400.00, 0.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_category`
--

CREATE TABLE `inventory_category` (
  `idCategory` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inventory_category`
--

INSERT INTO `inventory_category` (`idCategory`, `name`, `description`, `active`, `createdAt`, `updatedAt`) VALUES
(1, 'Sin categoría', 'Categoría inicial para productos pendientes de clasificación', 1, '2026-09-03 21:11:38', '2026-09-03 21:11:38'),
(2, 'Cemento', NULL, 1, '2026-09-03 21:18:12', '2026-09-03 21:18:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_movement`
--

CREATE TABLE `inventory_movement` (
  `idMovement` bigint(20) UNSIGNED NOT NULL,
  `idProduct` bigint(20) UNSIGNED NOT NULL,
  `idWarehouse` bigint(20) UNSIGNED NOT NULL,
  `movementType` enum('INGRESO','EGRESO','AJUSTE_POSITIVO','AJUSTE_NEGATIVO','TRANSFERENCIA_ENTRADA','TRANSFERENCIA_SALIDA') NOT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `previousQuantity` decimal(14,3) NOT NULL,
  `resultingQuantity` decimal(14,3) NOT NULL,
  `observations` varchar(500) NOT NULL,
  `actorUserId` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Referencia lógica a usuario; sin FK para mantener módulos desacoplados',
  `sourceModule` varchar(40) NOT NULL DEFAULT 'INVENTARIO',
  `sourceReference` varchar(100) DEFAULT NULL,
  `correlationId` char(36) DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inventory_movement`
--

INSERT INTO `inventory_movement` (`idMovement`, `idProduct`, `idWarehouse`, `movementType`, `quantity`, `previousQuantity`, `resultingQuantity`, `observations`, `actorUserId`, `sourceModule`, `sourceReference`, `correlationId`, `createdAt`) VALUES
(1, 1, 1, 'INGRESO', 30.000, 0.000, 30.000, 'Ingreso. 30 bolsas 25 kg.', 1, 'INVENTARIO', NULL, NULL, '2026-09-03 21:17:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_product`
--

CREATE TABLE `inventory_product` (
  `idProduct` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `idCategory` bigint(20) UNSIGNED NOT NULL,
  `idUnit` bigint(20) UNSIGNED NOT NULL,
  `salePrice` decimal(12,2) NOT NULL DEFAULT 0.00,
  `customAttributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`customAttributes`)),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inventory_product`
--

INSERT INTO `inventory_product` (`idProduct`, `code`, `name`, `description`, `idCategory`, `idUnit`, `salePrice`, `customAttributes`, `active`, `createdAt`, `updatedAt`) VALUES
(1, 'CEM-001', 'Cemento Loma Negra', 'Cemento de la marca Loma Negra. Bolsa de 25 KG.', 2, 1, 8000.00, NULL, 1, '2026-09-03 21:15:10', '2026-09-03 21:18:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_stock`
--

CREATE TABLE `inventory_stock` (
  `idProduct` bigint(20) UNSIGNED NOT NULL,
  `idWarehouse` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `minimumStock` decimal(14,3) NOT NULL DEFAULT 0.000,
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inventory_stock`
--

INSERT INTO `inventory_stock` (`idProduct`, `idWarehouse`, `quantity`, `minimumStock`, `updatedAt`) VALUES
(1, 1, 30.000, 50.000, '2026-09-03 21:17:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_unit`
--

CREATE TABLE `inventory_unit` (
  `idUnit` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(80) NOT NULL,
  `symbol` varchar(15) NOT NULL,
  `allowsDecimals` tinyint(1) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inventory_unit`
--

INSERT INTO `inventory_unit` (`idUnit`, `code`, `name`, `symbol`, `allowsDecimals`, `active`, `createdAt`, `updatedAt`) VALUES
(1, 'UN', 'Unidad', 'un', 0, 1, '2026-09-03 21:11:38', '2026-09-03 21:11:38'),
(2, 'KG', 'Kilogramo', 'kg', 1, 1, '2026-09-03 21:11:38', '2026-09-03 21:11:38'),
(3, 'TN', 'Tonelada', 't', 1, 1, '2026-09-03 21:11:38', '2026-09-03 21:11:38'),
(4, 'M', 'Metro', 'm', 1, 1, '2026-09-03 21:11:38', '2026-09-03 21:11:38'),
(5, 'M2', 'Metro cuadrado', 'm²', 1, 1, '2026-09-03 21:11:38', '2026-09-03 21:11:38'),
(6, 'M3', 'Metro cúbico', 'm³', 1, 1, '2026-09-03 21:11:38', '2026-09-03 21:11:38'),
(7, 'BATEA', 'Batea', 'batea', 1, 1, '2026-09-03 21:11:38', '2026-09-03 21:11:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_warehouse`
--

CREATE TABLE `inventory_warehouse` (
  `idWarehouse` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inventory_warehouse`
--

INSERT INTO `inventory_warehouse` (`idWarehouse`, `name`, `address`, `description`, `active`, `createdAt`, `updatedAt`) VALUES
(1, 'Depósito principal', NULL, 'Ubicación inicial del inventario', 1, '2026-09-03 21:11:38', '2026-09-03 21:11:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `itemdespacho`
--

CREATE TABLE `itemdespacho` (
  `idItemDespacho` bigint(20) NOT NULL,
  `idDespacho` bigint(20) NOT NULL,
  `idDetallePedido` bigint(20) NOT NULL,
  `cantidadADespachar` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `liquidacionsueldo`
--

CREATE TABLE `liquidacionsueldo` (
  `idLiquidacion` bigint(20) NOT NULL,
  `idEmpleado` bigint(20) NOT NULL,
  `idAdminLiquidador` bigint(20) NOT NULL,
  `periodo` varchar(20) NOT NULL,
  `sueldoBase` decimal(12,2) NOT NULL DEFAULT 0.00,
  `totalHaberes` decimal(12,2) NOT NULL DEFAULT 0.00,
  `totalDescuentos` decimal(12,2) NOT NULL DEFAULT 0.00,
  `montoNeto` decimal(12,2) NOT NULL,
  `numeroRecibo` varchar(50) DEFAULT NULL,
  `fechaLiquidacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `liquidacionsueldo`
--

INSERT INTO `liquidacionsueldo` (`idLiquidacion`, `idEmpleado`, `idAdminLiquidador`, `periodo`, `sueldoBase`, `totalHaberes`, `totalDescuentos`, `montoNeto`, `numeroRecibo`, `fechaLiquidacion`) VALUES
(1, 2, 1, '2026-09', 1000000.00, 1055000.00, 30000.00, 1025000.00, 'REC-202609-000001', '2026-09-04 00:15:39'),
(2, 3, 1, '2026-09', 3000000.00, 4030000.00, 2000000.00, 2030000.00, 'REC-202609-000002', '2026-09-04 00:27:59'),
(3, 4, 1, '2026-09', 250000.00, 480000.00, 300000.00, 180000.00, 'REC-202609-000003', '2026-09-04 00:43:28'),
(4, 5, 1, '2026-09', 2999999.00, 3199999.00, 90000.00, 3109999.00, 'REC-202609-000004', '2026-09-09 21:41:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_attempt`
--

CREATE TABLE `login_attempt` (
  `idAttempt` bigint(20) NOT NULL,
  `identityHash` binary(32) NOT NULL,
  `ipHash` binary(32) NOT NULL,
  `attemptedAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `login_attempt`
--

INSERT INTO `login_attempt` (`idAttempt`, `identityHash`, `ipHash`, `attemptedAt`) VALUES
(3, 0x8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918, 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, '2026-09-03 12:29:39'),
(4, 0x8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918, 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, '2026-09-03 12:41:47'),
(5, 0x8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918, 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, '2026-09-03 12:54:52'),
(9, 0xd75b73757b42e6f2674d5976d03c2dc5c00a6490caea571413d63ac9f9a63335, 0xc8542cd1e78be528d9a9a6884aafa600fcfbe43a029cf479d0c746b9f06d41cc, '2026-09-03 16:21:59'),
(11, 0xeef543709eedbe4abf8a49b5450895de06b078677e9b6d80d414106d1cd46daf, 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, '2026-09-10 20:15:30'),
(12, 0xf9bd39ff988f04a1d083cae4860853370148d7bb4953334716a7f28faa51bf82, 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, '2026-09-11 21:28:24'),
(13, 0x667003d5d026c55887fc498c38dd869773862eca45abfea1d52ce4f0f3cfda84, 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, '2026-09-11 21:32:54'),
(14, 0x667003d5d026c55887fc498c38dd869773862eca45abfea1d52ce4f0f3cfda84, 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, '2026-09-11 21:34:07'),
(15, 0x667003d5d026c55887fc498c38dd869773862eca45abfea1d52ce4f0f3cfda84, 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, '2026-09-11 21:34:15'),
(16, 0x667003d5d026c55887fc498c38dd869773862eca45abfea1d52ce4f0f3cfda84, 0xb6847dc169949c1b6b9e78474b962792c865b5ddae1cf05691c083f6eaeea8db, '2026-09-11 21:34:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientostock`
--

CREATE TABLE `movimientostock` (
  `idMovimiento` bigint(20) NOT NULL,
  `idProducto` bigint(20) NOT NULL,
  `idUsuario` bigint(20) NOT NULL,
  `fechaHora` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo` enum('INGRESO_COMPRA','EGRESO_DESPACHO','AJUSTE_POSITIVO','AJUSTE_NEGATIVO') NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

CREATE TABLE `pago` (
  `idPago` bigint(20) NOT NULL,
  `idPedido` bigint(20) NOT NULL,
  `idUsuario` bigint(20) DEFAULT NULL,
  `tipoOperacion` enum('PAGO','CUENTA_CORRIENTE') NOT NULL DEFAULT 'PAGO',
  `fechaPago` datetime NOT NULL DEFAULT current_timestamp(),
  `importe` decimal(12,2) NOT NULL,
  `importeBase` decimal(12,2) DEFAULT NULL,
  `medioPago` enum('EFECTIVO','TARJETA','CHEQUE','TRANSFERENCIA','CONTROLADOR','CUENTA_CORRIENTE') DEFAULT NULL,
  `estadoPago` enum('PENDIENTE','APROBADO','RECHAZADO') NOT NULL DEFAULT 'APROBADO',
  `apiKeySol` varchar(255) DEFAULT NULL,
  `puntoVentaID` varchar(100) DEFAULT NULL,
  `transaccionExternaID` varchar(100) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pago`
--

INSERT INTO `pago` (`idPago`, `idPedido`, `idUsuario`, `tipoOperacion`, `fechaPago`, `importe`, `importeBase`, `medioPago`, `estadoPago`, `apiKeySol`, `puntoVentaID`, `transaccionExternaID`, `observaciones`) VALUES
(1, 2, 1, 'PAGO', '2026-09-14 21:32:48', 43200.00, NULL, 'TARJETA', 'APROBADO', NULL, NULL, 'SIM-20260914213248-e0bb2e', NULL),
(2, 1, 1, 'PAGO', '2026-09-14 21:34:23', 240000.00, NULL, 'EFECTIVO', 'APROBADO', NULL, NULL, NULL, NULL),
(3, 3, 1, 'PAGO', '2026-09-14 21:59:09', 28160.00, 25600.00, 'TARJETA', 'APROBADO', NULL, NULL, 'SIM-20260914215909-64a245', NULL),
(4, 4, 7, 'PAGO', '2026-09-16 21:18:03', 40320.00, 44800.00, 'TARJETA', 'PENDIENTE', NULL, NULL, 'SIM-20260916211803-4eba03', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_token`
--

CREATE TABLE `password_reset_token` (
  `idToken` bigint(20) NOT NULL,
  `idUsuario` bigint(20) NOT NULL,
  `selector` char(18) NOT NULL,
  `tokenHash` binary(32) NOT NULL,
  `expiresAt` datetime NOT NULL,
  `usedAt` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payment_adjustment`
--

CREATE TABLE `payment_adjustment` (
  `idPaymentAdjustment` bigint(20) UNSIGNED NOT NULL,
  `idPago` bigint(20) NOT NULL,
  `tipoAjuste` enum('DESCUENTO','RECARGO') NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00,
  `montoCalculado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `appliedAt` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `payment_adjustment`
--

INSERT INTO `payment_adjustment` (`idPaymentAdjustment`, `idPago`, `tipoAjuste`, `porcentaje`, `montoCalculado`, `appliedAt`, `createdAt`) VALUES
(1, 1, 'DESCUENTO', 10.00, 4800.00, '2026-09-14 21:32:57', '2026-09-14 21:32:48'),
(2, 3, 'RECARGO', 10.00, 2560.00, '2026-09-14 21:59:14', '2026-09-14 21:59:09'),
(3, 4, 'DESCUENTO', 10.00, 4480.00, NULL, '2026-09-16 21:18:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payment_audit`
--

CREATE TABLE `payment_audit` (
  `idAudit` bigint(20) UNSIGNED NOT NULL,
  `idPago` bigint(20) DEFAULT NULL,
  `idPedido` bigint(20) NOT NULL,
  `idUsuario` bigint(20) DEFAULT NULL,
  `eventType` varchar(40) NOT NULL,
  `detail` varchar(500) DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `payment_audit`
--

INSERT INTO `payment_audit` (`idAudit`, `idPago`, `idPedido`, `idUsuario`, `eventType`, `detail`, `createdAt`) VALUES
(1, 1, 2, 1, 'PAYMENT_APPROVED', 'TARJETA $43200.00', '2026-09-14 21:32:57'),
(2, 2, 1, 1, 'PAYMENT_APPROVED', 'EFECTIVO $240000.00', '2026-09-14 21:34:23'),
(3, 3, 3, 1, 'GATEWAY_REQUEST_CREATED', 'TARJETA SIM-20260914215909-64a245', '2026-09-14 21:59:09'),
(4, 3, 3, 1, 'PAYMENT_APPROVED', 'TARJETA $28160.00', '2026-09-14 21:59:14'),
(5, 4, 4, 7, 'GATEWAY_REQUEST_CREATED', 'TARJETA SIM-20260916211803-4eba03', '2026-09-16 21:18:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payment_cash_detail`
--

CREATE TABLE `payment_cash_detail` (
  `idPago` bigint(20) NOT NULL,
  `importeRecibido` decimal(12,2) NOT NULL,
  `vuelto` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payment_check_detail`
--

CREATE TABLE `payment_check_detail` (
  `idPago` bigint(20) NOT NULL,
  `banco` varchar(120) NOT NULL,
  `numeroCheque` varchar(80) NOT NULL,
  `titular` varchar(150) NOT NULL,
  `fechaEmision` date NOT NULL,
  `fechaVencimiento` date NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payment_gateway_request`
--

CREATE TABLE `payment_gateway_request` (
  `idGatewayRequest` bigint(20) UNSIGNED NOT NULL,
  `idPago` bigint(20) NOT NULL,
  `provider` varchar(60) NOT NULL,
  `paymentMethod` enum('TARJETA','TRANSFERENCIA') DEFAULT NULL,
  `externalId` varchar(150) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `qrPayload` text DEFAULT NULL,
  `qrImageUrl` varchar(500) DEFAULT NULL,
  `responseJson` longtext DEFAULT NULL,
  `lastCheckedAt` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `payment_gateway_request`
--

INSERT INTO `payment_gateway_request` (`idGatewayRequest`, `idPago`, `provider`, `paymentMethod`, `externalId`, `status`, `qrPayload`, `qrImageUrl`, `responseJson`, `lastCheckedAt`, `createdAt`, `updatedAt`) VALUES
(1, 1, 'SIMULADOR', NULL, 'SIM-20260914213248-e0bb2e', 'APPROVED', 'SEDEMA|SIM-20260914213248-e0bb2e|43200.00', NULL, '{\"mode\":\"mock\",\"status\":\"APPROVED\"}', '2026-09-14 21:32:57', '2026-09-14 21:32:48', '2026-09-14 21:32:57'),
(2, 3, 'SIMULADOR', 'TARJETA', 'SIM-20260914215909-64a245', 'APPROVED', 'SEDEMA|TARJETA|SIM-20260914215909-64a245|28160.00', NULL, '{\"mode\":\"mock\",\"status\":\"APPROVED\"}', '2026-09-14 21:59:14', '2026-09-14 21:59:09', '2026-09-14 21:59:14'),
(3, 4, 'SIMULADOR', 'TARJETA', 'SIM-20260916211803-4eba03', 'PENDING', 'SEDEMA|TARJETA|SIM-20260916211803-4eba03|40320.00', NULL, '{\"mode\":\"mock\",\"id\":\"SIM-20260916211803-4eba03\",\"amount\":40320,\"method\":\"TARJETA\",\"description\":\"Pedido SEDEMA #4\",\"payerEmail\":\"\"}', '2026-09-16 21:18:03', '2026-09-16 21:18:03', '2026-09-16 21:18:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `idPedido` bigint(20) NOT NULL,
  `idCliente` bigint(20) NOT NULL,
  `idUsuario` bigint(20) NOT NULL,
  `idWarehouse` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(50) NOT NULL DEFAULT 'PENDIENTE',
  `porcentajeDescuento` decimal(5,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `totalAjustes` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `modalidadOperativa` enum('EN_CORRALON','ENTREGA_DOMICILIO','ACOPIO') NOT NULL,
  `direccionEntrega` varchar(255) DEFAULT NULL,
  `fechaEntrega` datetime DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedido`
--

INSERT INTO `pedido` (`idPedido`, `idCliente`, `idUsuario`, `idWarehouse`, `fecha`, `estado`, `porcentajeDescuento`, `subtotal`, `totalAjustes`, `total`, `modalidadOperativa`, `direccionEntrega`, `fechaEntrega`, `observaciones`, `updatedAt`) VALUES
(1, 1, 1, 1, '2026-09-12 01:11:48', 'DESPACHO_PROGRAMADO', 0.00, 240000.00, 0.00, 240000.00, 'EN_CORRALON', NULL, '2026-09-12 01:11:00', NULL, '2026-09-12 01:12:43'),
(2, 2, 1, 1, '2026-09-14 21:26:25', 'FACTURADO', 60.00, 80000.00, -36800.00, 43200.00, 'ENTREGA_DOMICILIO', 'diaz roig 620 B° san agustin', '2004-11-10 10:00:00', 'casa con porton verde', '2026-09-14 21:54:27'),
(3, 3, 1, 1, '2026-09-14 21:56:45', 'DESPACHO_PROGRAMADO', 20.00, 32000.00, -3840.00, 28160.00, 'ENTREGA_DOMICILIO', 'pedro bonancio 636', '2026-09-14 21:55:00', 'CASA CON BASURERO DE PERSONA', '2026-09-14 22:00:59'),
(4, 4, 7, 1, '2026-09-16 21:13:01', 'DESPACHO_PROGRAMADO', 20.00, 56000.00, -11200.00, 44800.00, 'ENTREGA_DOMICILIO', 'villa del carmen 3889', '2026-09-18 21:12:00', 'porton azul', '2026-09-16 21:14:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personnel_payroll_item`
--

CREATE TABLE `personnel_payroll_item` (
  `idConcepto` bigint(20) UNSIGNED NOT NULL,
  `idLiquidacion` bigint(20) NOT NULL,
  `tipo` enum('HABER','DESCUENTO') NOT NULL,
  `concepto` varchar(150) NOT NULL,
  `importe` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `personnel_payroll_item`
--

INSERT INTO `personnel_payroll_item` (`idConcepto`, `idLiquidacion`, `tipo`, `concepto`, `importe`) VALUES
(1, 1, 'HABER', 'Sueldo base', 1000000.00),
(2, 1, 'HABER', 'Presentismo', 40000.00),
(3, 1, 'HABER', 'Horas extra', 15000.00),
(4, 1, 'DESCUENTO', 'Adelanto', 30000.00),
(5, 2, 'HABER', 'Sueldo base', 3000000.00),
(6, 2, 'HABER', 'Presentismo', 30000.00),
(7, 2, 'HABER', 'Horasextra', 1000000.00),
(8, 2, 'DESCUENTO', 'Adelanto', 2000000.00),
(9, 3, 'HABER', 'Sueldo base', 250000.00),
(10, 3, 'HABER', 'Presentismo', 30000.00),
(11, 3, 'HABER', 'Horasextra', 200000.00),
(12, 3, 'DESCUENTO', 'Adelanto', 300000.00),
(13, 4, 'HABER', 'Sueldo base', 2999999.00),
(14, 4, 'HABER', 'P', 200000.00),
(15, 4, 'DESCUENTO', 'A', 90000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `idProducto` bigint(20) NOT NULL,
  `idAlmacen` bigint(20) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombreProducto` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `stockActual` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stockMinimo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `categoriaProducto` varchar(100) NOT NULL,
  `unidadMedida` varchar(50) NOT NULL,
  `precio` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `idProveedor` bigint(20) NOT NULL,
  `razonSocial` varchar(150) NOT NULL,
  `cuit` varchar(20) NOT NULL,
  `descripcionProveedor` text DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recepcioncompra`
--

CREATE TABLE `recepcioncompra` (
  `idRecepcion` bigint(20) NOT NULL,
  `idRemitoProveedor` bigint(20) NOT NULL,
  `fechaRecepcion` datetime NOT NULL DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `remitodespacho`
--

CREATE TABLE `remitodespacho` (
  `idRemito` bigint(20) NOT NULL,
  `idDespacho` bigint(20) NOT NULL,
  `numeroRemito` varchar(50) NOT NULL,
  `fechaEmision` datetime NOT NULL DEFAULT current_timestamp(),
  `sucursalOrigen` varchar(100) NOT NULL,
  `hojaRuta` varchar(100) DEFAULT NULL,
  `esDevolucion` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `remitodespacho`
--

INSERT INTO `remitodespacho` (`idRemito`, `idDespacho`, `numeroRemito`, `fechaEmision`, `sucursalOrigen`, `hojaRuta`, `esDevolucion`) VALUES
(1, 1, 'R-20260912-000001', '2026-09-12 01:12:43', 'SEDEMA S.R.L.', 'HR-R-20260912-000001', 0),
(2, 2, 'R-20260914-000002', '2026-09-14 22:00:59', 'SEDEMA S.R.L.', 'HR-R-20260914-000002', 0),
(3, 3, 'R-20260916-000003', '2026-09-16 21:14:02', 'SEDEMA S.R.L.', 'HR-R-20260916-000003', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `remitoproveedor`
--

CREATE TABLE `remitoproveedor` (
  `idRemitoProveedor` bigint(20) NOT NULL,
  `idProveedor` bigint(20) NOT NULL,
  `numeroRemito` varchar(50) NOT NULL,
  `fechaEmision` date NOT NULL,
  `estado` enum('BORRADOR','PENDIENTE','EMITIDA','RECEPCION_PARCIAL','RECEPCION_TOTAL','CANCELADA') NOT NULL DEFAULT 'PENDIENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sales_order_audit`
--

CREATE TABLE `sales_order_audit` (
  `idAudit` bigint(20) UNSIGNED NOT NULL,
  `idPedido` bigint(20) NOT NULL,
  `idUsuario` bigint(20) DEFAULT NULL,
  `eventType` varchar(40) NOT NULL,
  `detail` varchar(500) DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sales_order_audit`
--

INSERT INTO `sales_order_audit` (`idAudit`, `idPedido`, `idUsuario`, `eventType`, `detail`, `createdAt`) VALUES
(1, 1, 1, 'ORDER_CREATED', 'Pedido creado por $240000.00', '2026-09-12 01:11:48'),
(2, 1, 1, 'REMIT_ISSUED', 'R-20260912-000001', '2026-09-12 01:12:43'),
(3, 2, 1, 'ORDER_CREATED', 'Pedido creado por $48000.00', '2026-09-14 21:26:25'),
(4, 2, 1, 'INVOICE_ISSUED', 'Factura B 1-1', '2026-09-14 21:54:27'),
(5, 3, 1, 'ORDER_CREATED', 'Pedido creado por $25600.00', '2026-09-14 21:56:45'),
(6, 3, 1, 'INVOICE_ISSUED', 'Factura B 1-2', '2026-09-14 21:59:56'),
(7, 3, 1, 'REMIT_ISSUED', 'R-20260914-000002', '2026-09-14 22:00:59'),
(8, 4, 7, 'ORDER_CREATED', 'Pedido creado por $44800.00', '2026-09-16 21:13:01'),
(9, 4, 7, 'REMIT_ISSUED', 'R-20260916-000003', '2026-09-16 21:14:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_access_audit`
--

CREATE TABLE `user_access_audit` (
  `idAccessAudit` bigint(20) UNSIGNED NOT NULL,
  `idUsuario` bigint(20) DEFAULT NULL,
  `actorUserId` bigint(20) DEFAULT NULL,
  `eventType` enum('ACCOUNT_CREATED','TEMP_CREDENTIAL_SENT','FIRST_ACCESS_COMPLETED','ACCOUNT_ENABLED','ACCOUNT_DISABLED','PERMISSIONS_CHANGED','EMAIL_CHANGED','ACCOUNT_DELETED') NOT NULL,
  `detail` varchar(500) DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `user_access_audit`
--

INSERT INTO `user_access_audit` (`idAccessAudit`, `idUsuario`, `actorUserId`, `eventType`, `detail`, `createdAt`) VALUES
(1, NULL, 1, 'ACCOUNT_CREATED', 'Cuenta creada para antonellasiacia1@gmail.com en sector Sistemas.', '2026-09-10 19:08:50'),
(2, NULL, 1, 'TEMP_CREDENTIAL_SENT', 'Credencial temporal enviada por correo.', '2026-09-10 19:08:50'),
(3, 3, 1, 'ACCOUNT_CREATED', 'Cuenta creada para antonellasiacia@gmail.com con rol VENDEDOR.', '2026-09-10 19:14:04'),
(4, 3, 1, 'TEMP_CREDENTIAL_SENT', 'Credencial temporal enviada por correo.', '2026-09-10 19:14:04'),
(5, 4, 1, 'ACCOUNT_CREATED', 'Cuenta creada para francodomingz@gmail.com con rol CAJA.', '2026-09-10 19:15:56'),
(6, 4, 1, 'TEMP_CREDENTIAL_SENT', 'Credencial temporal enviada por correo.', '2026-09-10 19:15:56'),
(7, 4, 1, 'PERMISSIONS_CHANGED', 'Rol o permisos actualizados.', '2026-09-10 19:17:35'),
(8, 4, 1, 'ACCOUNT_DISABLED', 'Cuenta deshabilitada.', '2026-09-10 19:17:35'),
(9, 5, 1, 'ACCOUNT_CREATED', 'Cuenta creada para delturcoren@gmail.com con rol CAJA.', '2026-09-10 20:10:55'),
(10, 5, 1, 'TEMP_CREDENTIAL_SENT', 'Credencial temporal enviada por correo.', '2026-09-10 20:11:00'),
(11, 6, 1, 'ACCOUNT_CREATED', 'Cuenta creada para francodomingz2004@gmail.com con rol CAJA.', '2026-09-10 20:13:32'),
(12, 6, 1, 'TEMP_CREDENTIAL_SENT', 'Credencial temporal enviada por correo.', '2026-09-10 20:13:37'),
(13, 6, 6, 'FIRST_ACCESS_COMPLETED', 'Configuración inicial completada.', '2026-09-10 20:18:24'),
(14, 5, 1, 'PERMISSIONS_CHANGED', 'Rol o permisos actualizados.', '2026-09-10 20:36:17'),
(15, 5, 1, 'ACCOUNT_ENABLED', 'Cuenta habilitada.', '2026-09-10 20:36:17'),
(16, 5, 1, 'ACCOUNT_DELETED', 'Cuenta eliminada por el administrador. El legajo se conserva.', '2026-09-10 20:49:16'),
(17, 3, 1, 'ACCOUNT_DELETED', 'Cuenta eliminada por el administrador. El legajo se conserva.', '2026-09-10 20:49:16'),
(18, NULL, 1, 'ACCOUNT_DELETED', 'Cuenta eliminada por el administrador. El legajo se conserva.', '2026-09-10 20:49:16'),
(19, 4, 1, 'ACCOUNT_DELETED', 'Cuenta eliminada por el administrador. El legajo se conserva.', '2026-09-10 20:49:16'),
(20, NULL, 1, 'ACCOUNT_CREATED', 'Cuenta creada para antonellasiacia1@gmail.com con rol SISTEMAS.', '2026-09-10 20:49:51'),
(21, NULL, 1, 'TEMP_CREDENTIAL_SENT', 'Credencial temporal enviada por correo.', '2026-09-10 20:49:56'),
(22, NULL, NULL, 'FIRST_ACCESS_COMPLETED', 'Configuración inicial completada.', '2026-09-10 20:52:08'),
(23, NULL, 1, 'EMAIL_CHANGED', 'Correo actualizado de antonellasiacia1@gmail.com a delturcoren@gmail.com.', '2026-09-10 20:54:54'),
(24, NULL, 1, 'PERMISSIONS_CHANGED', 'Rol o permisos actualizados.', '2026-09-10 20:54:54'),
(25, NULL, 1, 'ACCOUNT_ENABLED', 'Cuenta habilitada.', '2026-09-10 20:54:54'),
(26, 6, 1, 'PERMISSIONS_CHANGED', 'Rol o permisos actualizados.', '2026-09-11 21:49:17'),
(27, 6, 1, 'ACCOUNT_ENABLED', 'Cuenta habilitada.', '2026-09-11 21:49:17'),
(28, 7, 1, 'ACCOUNT_CREATED', 'Cuenta creada para silveraimanol39@gmail.com con rol SISTEMAS.', '2026-09-16 21:05:59'),
(29, 7, 1, 'TEMP_CREDENTIAL_SENT', 'Credencial temporal enviada por correo.', '2026-09-16 21:06:09'),
(30, 7, 7, 'FIRST_ACCESS_COMPLETED', 'Configuración inicial completada.', '2026-09-16 21:09:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `idUsuario` bigint(20) NOT NULL,
  `idEmpleado` bigint(20) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `passwordHash` varchar(255) NOT NULL,
  `mustChangePassword` tinyint(1) NOT NULL DEFAULT 0,
  `initialSetupCompleted` tinyint(1) NOT NULL DEFAULT 1,
  `profilePhoto` varchar(255) DEFAULT NULL,
  `credentialSentAt` datetime DEFAULT NULL,
  `emailValidated` tinyint(1) NOT NULL DEFAULT 1,
  `deletedAt` datetime DEFAULT NULL,
  `deletedBy` bigint(20) DEFAULT NULL,
  `roles` enum('ADMINISTRADOR','VENDEDOR','PROVEEDOR','DEPOSITO','LOGISTICA','CAJA','ALMACEN','FINANZAS','SISTEMAS') NOT NULL,
  `sector` varchar(80) DEFAULT NULL,
  `permisos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permisos`)),
  `habilitado` tinyint(1) NOT NULL DEFAULT 1,
  `failedAttempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `lockedUntil` datetime DEFAULT NULL,
  `ultimoAcceso` datetime DEFAULT NULL,
  `authVersion` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`idUsuario`, `idEmpleado`, `username`, `email`, `passwordHash`, `mustChangePassword`, `initialSetupCompleted`, `profilePhoto`, `credentialSentAt`, `emailValidated`, `deletedAt`, `deletedBy`, `roles`, `sector`, `permisos`, `habilitado`, `failedAttempts`, `lockedUntil`, `ultimoAcceso`, `authVersion`, `createdAt`, `updatedAt`) VALUES
(1, 1, 'admin', 'gruposedema4@gmail.com', '$2y$10$IoXV73zEcDd4tvu0mu2ruOfVuFlWCB6HfwbumyMl0GPfbPoWF782K', 0, 1, 'uploads/profiles/user_1_e2d5c309f2d7.png', NULL, 1, NULL, NULL, 'ADMINISTRADOR', NULL, '[\"*\"]', 1, 0, NULL, '2026-09-16 21:00:16', 2, '2026-09-03 11:51:39', '2026-09-16 21:00:16'),
(3, 7, 'deleted.3.20260910204916', 'deleted+3.20260910204916@invalid.local', '$2y$10$/7sdioNj7t0mtPxN.W36.u9VlomQXKIENjqNPK9jvxdMA8h2hQh.e', 0, 1, NULL, NULL, 1, '2026-09-10 20:49:16', 1, 'VENDEDOR', NULL, '[]', 0, 0, NULL, NULL, 2, '2026-09-10 19:14:04', '2026-09-10 20:49:16'),
(4, 8, 'deleted.4.20260910204916', 'deleted+4.20260910204916@invalid.local', '$2y$10$p27o6PJaka8hcNIUkgN9aOYC3nAnBUJT98bkKyBwkGMAd1kKvqN6y', 0, 1, NULL, NULL, 1, '2026-09-10 20:49:17', 1, 'CAJA', NULL, '[]', 0, 0, NULL, NULL, 3, '2026-09-10 19:15:56', '2026-09-10 20:49:17'),
(5, 9, 'deleted.5.20260910204916', 'deleted+5.20260910204916@invalid.local', '$2y$10$nJu5cwMEXdrQt4M9jrRkaO67aMA9WR0YUjDAMutET2omY/FzPvqkW', 0, 1, NULL, NULL, 1, '2026-09-10 20:49:16', 1, 'CAJA', NULL, '[]', 0, 0, NULL, NULL, 3, '2026-09-10 20:10:55', '2026-09-10 20:49:16'),
(6, 10, 'paula.palacio', 'francodomingz2004@gmail.com', '$2y$10$cIjd8FhtsqsNN8gzwk9T4.qVXGgCcC77V11WE3SM9BH5M.pc0UnIq', 0, 1, 'uploads/profiles/db583abd3ae86cbfc449cdc4d69b0f03.jpg', '2026-09-10 20:13:37', 1, NULL, NULL, 'CAJA', NULL, '[\"ventas.*\",\"inventario.*\",\"inventory.*\",\"pagos.*\"]', 1, 0, NULL, '2026-09-14 21:14:57', 3, '2026-09-10 20:13:32', '2026-09-14 21:14:57'),
(7, 11, 'silveraima', 'silveraimanol39@gmail.com', '$2y$10$d1UXfFdjPjkQl2n5ESJZnuTn9cJjKsYD/aFmXtkQJBXSyD6DaDT9C', 0, 1, 'uploads/profiles/a4d6fbe7012bb2b21f80b2a1f6b9a3ca.jpg', '2026-09-16 21:06:09', 1, NULL, NULL, 'SISTEMAS', NULL, '[\"ventas.*\",\"sales.*\",\"clientes.*\",\"pagos.*\",\"payments.*\"]', 1, 0, NULL, '2026-09-16 21:09:23', 2, '2026-09-16 21:05:59', '2026-09-16 21:09:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculo`
--

CREATE TABLE `vehiculo` (
  `idVehiculo` bigint(20) NOT NULL,
  `matricula` varchar(20) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `capacidadCarga` decimal(10,2) NOT NULL,
  `estado` enum('DISPONIBLE','OCUPADO','EN_MANTENIMIENTO') NOT NULL DEFAULT 'DISPONIBLE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `ajuste`
--
ALTER TABLE `ajuste`
  ADD PRIMARY KEY (`idAjuste`),
  ADD KEY `fk_ajuste_pedido` (`idPedido`);

--
-- Indices de la tabla `almacen`
--
ALTER TABLE `almacen`
  ADD PRIMARY KEY (`idAlmacen`);

--
-- Indices de la tabla `auth_audit`
--
ALTER TABLE `auth_audit`
  ADD PRIMARY KEY (`idAudit`),
  ADD KEY `idx_audit_user_time` (`idUsuario`,`createdAt`),
  ADD KEY `idx_audit_event_time` (`eventType`,`createdAt`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`idCliente`),
  ADD UNIQUE KEY `cuitDNI` (`cuitDNI`);

--
-- Indices de la tabla `current_account_movement`
--
ALTER TABLE `current_account_movement`
  ADD PRIMARY KEY (`idMovement`),
  ADD KEY `idx_current_account_client_time` (`idCliente`,`createdAt`),
  ADD KEY `fk_current_account_order` (`idPedido`),
  ADD KEY `fk_current_account_payment` (`idPago`);

--
-- Indices de la tabla `despacho`
--
ALTER TABLE `despacho`
  ADD PRIMARY KEY (`idDespacho`),
  ADD KEY `fk_despacho_pedido` (`idPedido`),
  ADD KEY `fk_despacho_vehiculo` (`idVehiculo`);

--
-- Indices de la tabla `detallepedido`
--
ALTER TABLE `detallepedido`
  ADD PRIMARY KEY (`idDetalle`),
  ADD KEY `fk_detallepedido_pedido` (`idPedido`),
  ADD KEY `fk_detallepedido_producto` (`idProducto`);

--
-- Indices de la tabla `detallerecepcion`
--
ALTER TABLE `detallerecepcion`
  ADD PRIMARY KEY (`idDetalleRecepcion`),
  ADD KEY `fk_detallerecep_recepcion` (`idRecepcion`),
  ADD KEY `fk_detallerecep_producto` (`idProducto`);

--
-- Indices de la tabla `detalleremito`
--
ALTER TABLE `detalleremito`
  ADD PRIMARY KEY (`idDetalleRemito`),
  ADD KEY `fk_detalleremito_remito` (`idRemitoProveedor`),
  ADD KEY `fk_detalleremito_producto` (`idProducto`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`idEmpleado`),
  ADD UNIQUE KEY `dni` (`dni`);

--
-- Indices de la tabla `factura`
--
ALTER TABLE `factura`
  ADD PRIMARY KEY (`idFactura`),
  ADD UNIQUE KEY `idPedido` (`idPedido`),
  ADD UNIQUE KEY `nroComprobante` (`nroComprobante`);

--
-- Indices de la tabla `inventory_category`
--
ALTER TABLE `inventory_category`
  ADD PRIMARY KEY (`idCategory`),
  ADD UNIQUE KEY `uq_inventory_category_name` (`name`);

--
-- Indices de la tabla `inventory_movement`
--
ALTER TABLE `inventory_movement`
  ADD PRIMARY KEY (`idMovement`),
  ADD UNIQUE KEY `uq_inventory_movement_source` (`sourceModule`,`sourceReference`,`movementType`,`idProduct`,`idWarehouse`),
  ADD KEY `idx_inventory_movement_product_time` (`idProduct`,`createdAt`),
  ADD KEY `idx_inventory_movement_warehouse_time` (`idWarehouse`,`createdAt`),
  ADD KEY `idx_inventory_movement_correlation` (`correlationId`);

--
-- Indices de la tabla `inventory_product`
--
ALTER TABLE `inventory_product`
  ADD PRIMARY KEY (`idProduct`),
  ADD UNIQUE KEY `uq_inventory_product_code` (`code`),
  ADD KEY `fk_inventory_product_unit` (`idUnit`),
  ADD KEY `idx_inventory_product_search` (`active`,`name`),
  ADD KEY `idx_inventory_product_category` (`idCategory`,`active`);

--
-- Indices de la tabla `inventory_stock`
--
ALTER TABLE `inventory_stock`
  ADD PRIMARY KEY (`idProduct`,`idWarehouse`),
  ADD KEY `idx_inventory_stock_alert` (`idWarehouse`,`minimumStock`,`quantity`);

--
-- Indices de la tabla `inventory_unit`
--
ALTER TABLE `inventory_unit`
  ADD PRIMARY KEY (`idUnit`),
  ADD UNIQUE KEY `uq_inventory_unit_code` (`code`),
  ADD UNIQUE KEY `uq_inventory_unit_name` (`name`);

--
-- Indices de la tabla `inventory_warehouse`
--
ALTER TABLE `inventory_warehouse`
  ADD PRIMARY KEY (`idWarehouse`),
  ADD UNIQUE KEY `uq_inventory_warehouse_name` (`name`);

--
-- Indices de la tabla `itemdespacho`
--
ALTER TABLE `itemdespacho`
  ADD PRIMARY KEY (`idItemDespacho`),
  ADD KEY `fk_itemdespacho_despacho` (`idDespacho`),
  ADD KEY `fk_itemdespacho_detallepedido` (`idDetallePedido`);

--
-- Indices de la tabla `liquidacionsueldo`
--
ALTER TABLE `liquidacionsueldo`
  ADD PRIMARY KEY (`idLiquidacion`),
  ADD UNIQUE KEY `uq_liquidacion_empleado_periodo` (`idEmpleado`,`periodo`),
  ADD UNIQUE KEY `uq_liquidacion_numero_recibo` (`numeroRecibo`),
  ADD KEY `fk_liquidacion_empleado` (`idEmpleado`),
  ADD KEY `fk_liquidacion_admin` (`idAdminLiquidador`);

--
-- Indices de la tabla `login_attempt`
--
ALTER TABLE `login_attempt`
  ADD PRIMARY KEY (`idAttempt`),
  ADD KEY `idx_attempt_identity_time` (`identityHash`,`attemptedAt`),
  ADD KEY `idx_attempt_ip_time` (`ipHash`,`attemptedAt`);

--
-- Indices de la tabla `movimientostock`
--
ALTER TABLE `movimientostock`
  ADD PRIMARY KEY (`idMovimiento`),
  ADD KEY `fk_movimiento_producto` (`idProducto`),
  ADD KEY `fk_movimiento_usuario` (`idUsuario`);

--
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`idPago`),
  ADD KEY `fk_pago_pedido` (`idPedido`);

--
-- Indices de la tabla `password_reset_token`
--
ALTER TABLE `password_reset_token`
  ADD PRIMARY KEY (`idToken`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `idx_reset_user_status` (`idUsuario`,`usedAt`,`expiresAt`);

--
-- Indices de la tabla `payment_adjustment`
--
ALTER TABLE `payment_adjustment`
  ADD PRIMARY KEY (`idPaymentAdjustment`),
  ADD UNIQUE KEY `uq_payment_adjustment_payment` (`idPago`);

--
-- Indices de la tabla `payment_audit`
--
ALTER TABLE `payment_audit`
  ADD PRIMARY KEY (`idAudit`),
  ADD KEY `idx_payment_audit_order_time` (`idPedido`,`createdAt`);

--
-- Indices de la tabla `payment_cash_detail`
--
ALTER TABLE `payment_cash_detail`
  ADD PRIMARY KEY (`idPago`);

--
-- Indices de la tabla `payment_check_detail`
--
ALTER TABLE `payment_check_detail`
  ADD PRIMARY KEY (`idPago`),
  ADD KEY `idx_payment_check_number` (`numeroCheque`);

--
-- Indices de la tabla `payment_gateway_request`
--
ALTER TABLE `payment_gateway_request`
  ADD PRIMARY KEY (`idGatewayRequest`),
  ADD UNIQUE KEY `uq_gateway_payment` (`idPago`),
  ADD UNIQUE KEY `uq_gateway_external` (`provider`,`externalId`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`idPedido`),
  ADD KEY `fk_pedido_cliente` (`idCliente`),
  ADD KEY `fk_pedido_usuario` (`idUsuario`);

--
-- Indices de la tabla `personnel_payroll_item`
--
ALTER TABLE `personnel_payroll_item`
  ADD PRIMARY KEY (`idConcepto`),
  ADD KEY `idx_personnel_payroll_item_liquidacion` (`idLiquidacion`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`idProducto`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `fk_producto_almacen` (`idAlmacen`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`idProveedor`),
  ADD UNIQUE KEY `cuit` (`cuit`);

--
-- Indices de la tabla `recepcioncompra`
--
ALTER TABLE `recepcioncompra`
  ADD PRIMARY KEY (`idRecepcion`),
  ADD KEY `fk_recepcion_remito` (`idRemitoProveedor`);

--
-- Indices de la tabla `remitodespacho`
--
ALTER TABLE `remitodespacho`
  ADD PRIMARY KEY (`idRemito`),
  ADD UNIQUE KEY `idDespacho` (`idDespacho`),
  ADD UNIQUE KEY `numeroRemito` (`numeroRemito`);

--
-- Indices de la tabla `remitoproveedor`
--
ALTER TABLE `remitoproveedor`
  ADD PRIMARY KEY (`idRemitoProveedor`),
  ADD KEY `fk_remitoprov_proveedor` (`idProveedor`);

--
-- Indices de la tabla `sales_order_audit`
--
ALTER TABLE `sales_order_audit`
  ADD PRIMARY KEY (`idAudit`),
  ADD KEY `idx_sales_audit_order_time` (`idPedido`,`createdAt`);

--
-- Indices de la tabla `user_access_audit`
--
ALTER TABLE `user_access_audit`
  ADD PRIMARY KEY (`idAccessAudit`),
  ADD KEY `idx_access_audit_user_time` (`idUsuario`,`createdAt`),
  ADD KEY `idx_access_audit_actor_time` (`actorUserId`,`createdAt`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`idUsuario`),
  ADD UNIQUE KEY `idEmpleado` (`idEmpleado`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uq_usuario_email` (`email`);

--
-- Indices de la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  ADD PRIMARY KEY (`idVehiculo`),
  ADD UNIQUE KEY `matricula` (`matricula`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ajuste`
--
ALTER TABLE `ajuste`
  MODIFY `idAjuste` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `almacen`
--
ALTER TABLE `almacen`
  MODIFY `idAlmacen` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auth_audit`
--
ALTER TABLE `auth_audit`
  MODIFY `idAudit` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `idCliente` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `current_account_movement`
--
ALTER TABLE `current_account_movement`
  MODIFY `idMovement` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `despacho`
--
ALTER TABLE `despacho`
  MODIFY `idDespacho` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `detallepedido`
--
ALTER TABLE `detallepedido`
  MODIFY `idDetalle` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `detallerecepcion`
--
ALTER TABLE `detallerecepcion`
  MODIFY `idDetalleRecepcion` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalleremito`
--
ALTER TABLE `detalleremito`
  MODIFY `idDetalleRemito` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `idEmpleado` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `factura`
--
ALTER TABLE `factura`
  MODIFY `idFactura` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `inventory_category`
--
ALTER TABLE `inventory_category`
  MODIFY `idCategory` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `inventory_movement`
--
ALTER TABLE `inventory_movement`
  MODIFY `idMovement` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `inventory_product`
--
ALTER TABLE `inventory_product`
  MODIFY `idProduct` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `inventory_unit`
--
ALTER TABLE `inventory_unit`
  MODIFY `idUnit` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `inventory_warehouse`
--
ALTER TABLE `inventory_warehouse`
  MODIFY `idWarehouse` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `itemdespacho`
--
ALTER TABLE `itemdespacho`
  MODIFY `idItemDespacho` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `liquidacionsueldo`
--
ALTER TABLE `liquidacionsueldo`
  MODIFY `idLiquidacion` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `login_attempt`
--
ALTER TABLE `login_attempt`
  MODIFY `idAttempt` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `movimientostock`
--
ALTER TABLE `movimientostock`
  MODIFY `idMovimiento` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `idPago` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `password_reset_token`
--
ALTER TABLE `password_reset_token`
  MODIFY `idToken` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `payment_adjustment`
--
ALTER TABLE `payment_adjustment`
  MODIFY `idPaymentAdjustment` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `payment_audit`
--
ALTER TABLE `payment_audit`
  MODIFY `idAudit` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `payment_gateway_request`
--
ALTER TABLE `payment_gateway_request`
  MODIFY `idGatewayRequest` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `idPedido` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `personnel_payroll_item`
--
ALTER TABLE `personnel_payroll_item`
  MODIFY `idConcepto` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `idProducto` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `idProveedor` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recepcioncompra`
--
ALTER TABLE `recepcioncompra`
  MODIFY `idRecepcion` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `remitodespacho`
--
ALTER TABLE `remitodespacho`
  MODIFY `idRemito` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `remitoproveedor`
--
ALTER TABLE `remitoproveedor`
  MODIFY `idRemitoProveedor` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sales_order_audit`
--
ALTER TABLE `sales_order_audit`
  MODIFY `idAudit` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `user_access_audit`
--
ALTER TABLE `user_access_audit`
  MODIFY `idAccessAudit` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `idUsuario` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  MODIFY `idVehiculo` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `ajuste`
--
ALTER TABLE `ajuste`
  ADD CONSTRAINT `fk_ajuste_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `auth_audit`
--
ALTER TABLE `auth_audit`
  ADD CONSTRAINT `fk_audit_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `current_account_movement`
--
ALTER TABLE `current_account_movement`
  ADD CONSTRAINT `fk_current_account_client` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`),
  ADD CONSTRAINT `fk_current_account_order` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_current_account_payment` FOREIGN KEY (`idPago`) REFERENCES `pago` (`idPago`) ON DELETE SET NULL;

--
-- Filtros para la tabla `despacho`
--
ALTER TABLE `despacho`
  ADD CONSTRAINT `fk_despacho_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_despacho_vehiculo` FOREIGN KEY (`idVehiculo`) REFERENCES `vehiculo` (`idVehiculo`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `detallepedido`
--
ALTER TABLE `detallepedido`
  ADD CONSTRAINT `fk_detallepedido_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detallepedido_producto` FOREIGN KEY (`idProducto`) REFERENCES `producto` (`idProducto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detallerecepcion`
--
ALTER TABLE `detallerecepcion`
  ADD CONSTRAINT `fk_detallerecep_producto` FOREIGN KEY (`idProducto`) REFERENCES `producto` (`idProducto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detallerecep_recepcion` FOREIGN KEY (`idRecepcion`) REFERENCES `recepcioncompra` (`idRecepcion`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalleremito`
--
ALTER TABLE `detalleremito`
  ADD CONSTRAINT `fk_detalleremito_producto` FOREIGN KEY (`idProducto`) REFERENCES `producto` (`idProducto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalleremito_remito` FOREIGN KEY (`idRemitoProveedor`) REFERENCES `remitoproveedor` (`idRemitoProveedor`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `factura`
--
ALTER TABLE `factura`
  ADD CONSTRAINT `fk_factura_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `inventory_movement`
--
ALTER TABLE `inventory_movement`
  ADD CONSTRAINT `fk_inventory_movement_product` FOREIGN KEY (`idProduct`) REFERENCES `inventory_product` (`idProduct`),
  ADD CONSTRAINT `fk_inventory_movement_warehouse` FOREIGN KEY (`idWarehouse`) REFERENCES `inventory_warehouse` (`idWarehouse`);

--
-- Filtros para la tabla `inventory_product`
--
ALTER TABLE `inventory_product`
  ADD CONSTRAINT `fk_inventory_product_category` FOREIGN KEY (`idCategory`) REFERENCES `inventory_category` (`idCategory`),
  ADD CONSTRAINT `fk_inventory_product_unit` FOREIGN KEY (`idUnit`) REFERENCES `inventory_unit` (`idUnit`);

--
-- Filtros para la tabla `inventory_stock`
--
ALTER TABLE `inventory_stock`
  ADD CONSTRAINT `fk_inventory_stock_product` FOREIGN KEY (`idProduct`) REFERENCES `inventory_product` (`idProduct`),
  ADD CONSTRAINT `fk_inventory_stock_warehouse` FOREIGN KEY (`idWarehouse`) REFERENCES `inventory_warehouse` (`idWarehouse`);

--
-- Filtros para la tabla `itemdespacho`
--
ALTER TABLE `itemdespacho`
  ADD CONSTRAINT `fk_itemdespacho_despacho` FOREIGN KEY (`idDespacho`) REFERENCES `despacho` (`idDespacho`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_itemdespacho_detallepedido` FOREIGN KEY (`idDetallePedido`) REFERENCES `detallepedido` (`idDetalle`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `liquidacionsueldo`
--
ALTER TABLE `liquidacionsueldo`
  ADD CONSTRAINT `fk_liquidacion_admin` FOREIGN KEY (`idAdminLiquidador`) REFERENCES `usuario` (`idUsuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_liquidacion_empleado` FOREIGN KEY (`idEmpleado`) REFERENCES `empleado` (`idEmpleado`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `movimientostock`
--
ALTER TABLE `movimientostock`
  ADD CONSTRAINT `fk_movimiento_producto` FOREIGN KEY (`idProducto`) REFERENCES `producto` (`idProducto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimiento_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `fk_pago_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `password_reset_token`
--
ALTER TABLE `password_reset_token`
  ADD CONSTRAINT `fk_reset_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `payment_adjustment`
--
ALTER TABLE `payment_adjustment`
  ADD CONSTRAINT `fk_payment_adjustment_payment` FOREIGN KEY (`idPago`) REFERENCES `pago` (`idPago`) ON DELETE CASCADE;

--
-- Filtros para la tabla `payment_cash_detail`
--
ALTER TABLE `payment_cash_detail`
  ADD CONSTRAINT `fk_payment_cash_payment` FOREIGN KEY (`idPago`) REFERENCES `pago` (`idPago`) ON DELETE CASCADE;

--
-- Filtros para la tabla `payment_check_detail`
--
ALTER TABLE `payment_check_detail`
  ADD CONSTRAINT `fk_payment_check_payment` FOREIGN KEY (`idPago`) REFERENCES `pago` (`idPago`) ON DELETE CASCADE;

--
-- Filtros para la tabla `payment_gateway_request`
--
ALTER TABLE `payment_gateway_request`
  ADD CONSTRAINT `fk_gateway_payment` FOREIGN KEY (`idPago`) REFERENCES `pago` (`idPago`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `personnel_payroll_item`
--
ALTER TABLE `personnel_payroll_item`
  ADD CONSTRAINT `fk_personnel_payroll_item_liquidacion` FOREIGN KEY (`idLiquidacion`) REFERENCES `liquidacionsueldo` (`idLiquidacion`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `fk_producto_almacen` FOREIGN KEY (`idAlmacen`) REFERENCES `almacen` (`idAlmacen`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `recepcioncompra`
--
ALTER TABLE `recepcioncompra`
  ADD CONSTRAINT `fk_recepcion_remito` FOREIGN KEY (`idRemitoProveedor`) REFERENCES `remitoproveedor` (`idRemitoProveedor`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `remitodespacho`
--
ALTER TABLE `remitodespacho`
  ADD CONSTRAINT `fk_remitodespacho_despacho` FOREIGN KEY (`idDespacho`) REFERENCES `despacho` (`idDespacho`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `remitoproveedor`
--
ALTER TABLE `remitoproveedor`
  ADD CONSTRAINT `fk_remitoprov_proveedor` FOREIGN KEY (`idProveedor`) REFERENCES `proveedor` (`idProveedor`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `user_access_audit`
--
ALTER TABLE `user_access_audit`
  ADD CONSTRAINT `fk_access_audit_actor` FOREIGN KEY (`actorUserId`) REFERENCES `usuario` (`idUsuario`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_access_audit_user` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_empleado` FOREIGN KEY (`idEmpleado`) REFERENCES `empleado` (`idEmpleado`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Integración estructural de Despacho/Logística sobre la exportación de Clientes/Usuarios.
CREATE TABLE IF NOT EXISTS schema_migration (
    version VARCHAR(50) NOT NULL PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    appliedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
DROP PROCEDURE IF EXISTS migrate_010_integration_merge$$
CREATE PROCEDURE migrate_010_integration_merge()
migration: BEGIN
    IF EXISTS (SELECT 1 FROM schema_migration WHERE version = '010_integration_merge') THEN
        LEAVE migration;
    END IF;

    ALTER TABLE vehiculo
        ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1 AFTER estado;

    ALTER TABLE despacho
        ADD COLUMN IF NOT EXISTS idWarehouse BIGINT UNSIGNED NULL AFTER idPedido,
        ADD COLUMN IF NOT EXISTS transportista VARCHAR(150) NULL AFTER idVehiculo,
        ADD COLUMN IF NOT EXISTS creadoPor BIGINT NULL AFTER observacionesEntrega,
        ADD COLUMN IF NOT EXISTS creadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER creadoPor,
        ADD COLUMN IF NOT EXISTS actualizadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER creadoEn,
        ADD COLUMN IF NOT EXISTS despachadoEn DATETIME NULL AFTER actualizadoEn,
        ADD COLUMN IF NOT EXISTS entregadoEn DATETIME NULL AFTER despachadoEn,
        ADD COLUMN IF NOT EXISTS devueltoEn DATETIME NULL AFTER entregadoEn;

    ALTER TABLE detallepedido
        ADD COLUMN IF NOT EXISTS idInventoryProduct BIGINT UNSIGNED NULL AFTER idProducto,
        MODIFY COLUMN cantidadSolicitada DECIMAL(14,3) NOT NULL,
        MODIFY COLUMN cantidadPendiente DECIMAL(14,3) NOT NULL;

    ALTER TABLE itemdespacho
        MODIFY COLUMN cantidadADespachar DECIMAL(14,3) NOT NULL;

    ALTER TABLE vehiculo
        MODIFY COLUMN capacidadCarga DECIMAL(14,3) NOT NULL;

    UPDATE detallepedido dp
    INNER JOIN inventory_product ip ON ip.idProduct = dp.idProducto
    SET dp.idInventoryProduct = ip.idProduct
    WHERE dp.idInventoryProduct IS NULL;

    UPDATE detallepedido dp
    LEFT JOIN inventory_product ip ON ip.idProduct = dp.idInventoryProduct
    SET dp.idInventoryProduct = NULL
    WHERE dp.idInventoryProduct IS NOT NULL AND ip.idProduct IS NULL;

    UPDATE despacho d
    INNER JOIN pedido p ON p.idPedido = d.idPedido
    SET d.idWarehouse = p.idWarehouse
    WHERE d.idWarehouse IS NULL AND p.idWarehouse IS NOT NULL;

    UPDATE despacho d
    SET d.idWarehouse = (SELECT MIN(w.idWarehouse) FROM inventory_warehouse w WHERE w.active = 1)
    WHERE d.idWarehouse IS NULL;

    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'despacho' AND INDEX_NAME = 'idx_despacho_warehouse') THEN
        ALTER TABLE despacho ADD KEY idx_despacho_warehouse (idWarehouse);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'despacho' AND INDEX_NAME = 'idx_despacho_creado_por') THEN
        ALTER TABLE despacho ADD KEY idx_despacho_creado_por (creadoPor);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'detallepedido' AND INDEX_NAME = 'idx_detalle_inventory_product') THEN
        ALTER TABLE detallepedido ADD KEY idx_detalle_inventory_product (idInventoryProduct);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'despacho' AND CONSTRAINT_NAME = 'fk_despacho_warehouse') THEN
        ALTER TABLE despacho ADD CONSTRAINT fk_despacho_warehouse FOREIGN KEY (idWarehouse)
            REFERENCES inventory_warehouse(idWarehouse) ON DELETE RESTRICT ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'despacho' AND CONSTRAINT_NAME = 'fk_despacho_creado_por') THEN
        ALTER TABLE despacho ADD CONSTRAINT fk_despacho_creado_por FOREIGN KEY (creadoPor)
            REFERENCES usuario(idUsuario) ON DELETE SET NULL ON UPDATE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'detallepedido' AND CONSTRAINT_NAME = 'fk_detalle_inventory_product') THEN
        ALTER TABLE detallepedido ADD CONSTRAINT fk_detalle_inventory_product FOREIGN KEY (idInventoryProduct)
            REFERENCES inventory_product(idProduct) ON DELETE RESTRICT ON UPDATE CASCADE;
    END IF;

    CREATE TABLE IF NOT EXISTS despacho_estado_historial (
        idHistorial BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        idDespacho BIGINT NOT NULL,
        estadoAnterior ENUM('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO') NULL,
        estadoNuevo ENUM('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO') NOT NULL,
        observaciones VARCHAR(500) NULL,
        idUsuario BIGINT NULL,
        creadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (idHistorial),
        KEY idx_despacho_historial (idDespacho, creadoEn),
        CONSTRAINT fk_despacho_historial_despacho FOREIGN KEY (idDespacho)
            REFERENCES despacho(idDespacho) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_despacho_historial_usuario FOREIGN KEY (idUsuario)
            REFERENCES usuario(idUsuario) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    INSERT INTO itemdespacho (idDespacho, idDetallePedido, cantidadADespachar)
    SELECT d.idDespacho, dp.idDetalle, dp.cantidadPendiente
    FROM despacho d
    INNER JOIN detallepedido dp ON dp.idPedido = d.idPedido
    WHERE dp.cantidadPendiente > 0
      AND NOT EXISTS (SELECT 1 FROM itemdespacho ix WHERE ix.idDespacho = d.idDespacho);

    INSERT INTO despacho_estado_historial (idDespacho, estadoAnterior, estadoNuevo, observaciones, idUsuario, creadoEn)
    SELECT d.idDespacho, NULL, d.estado, 'Estado incorporado durante la integración.', d.creadoPor, d.creadoEn
    FROM despacho d
    WHERE NOT EXISTS (SELECT 1 FROM despacho_estado_historial h WHERE h.idDespacho = d.idDespacho);

    INSERT INTO schema_migration (version, description)
    VALUES ('010_integration_merge', 'Compatibilidad entre Clientes/Usuarios y Despacho/Logística');
END$$
CALL migrate_010_integration_merge()$$
DROP PROCEDURE migrate_010_integration_merge$$
DELIMITER ;
