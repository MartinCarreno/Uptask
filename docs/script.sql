-- tabla usuarios
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) DEFAULT NULL,
  `email` varchar(30) DEFAULT NULL,
  `password` varchar(60) DEFAULT NULL,
  `token` varchar(15) DEFAULT NULL,
  `confirmado` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--tabla proyectos
CREATE TABLE `proyectos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proyecto` varchar(60) DEFAULT NULL,
  `url` varchar(32) DEFAULT NULL,
  `propietarioId` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proyectos_usuarios_FK` (`propietarioId`),
  CONSTRAINT `proyectos_usuarios_FK` FOREIGN KEY (`propietarioId`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--tabla tareas
CREATE TABLE `tareas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT NULL,
  `proyectoId` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tareas_proyectos_FK` (`proyectoId`),
  CONSTRAINT `tareas_proyectos_FK` FOREIGN KEY (`proyectoId`) REFERENCES `proyectos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


--NUEVOS CAMBIOS

-- 1. Tabla de roles (Normalización 3FN para Rol 1: Administrador y Rol 2: Miembro)
CREATE TABLE `roles` (
    `id` tinyint NOT NULL AUTO_INCREMENT,
    `nombre` varchar(30) NOT NULL,
    `descripcion` varchar(150) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `roles_nombre_UQ` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
    (1, 'administrador', 'Crea, edita, elimina proyectos y gestiona miembros y tareas'),
    (2, 'miembro', 'Visualiza tablero y actualiza el estado de las tareas asignadas');

-- 2. Tabla usuarios (Email UNIQUE, password amplio para BCrypt/Argon2 y token expirable)
CREATE TABLE `usuarios` (
    `id` int NOT NULL AUTO_INCREMENT,
    `nombre` varchar(60) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `token` varchar(64) DEFAULT NULL,
    `token_expira` datetime DEFAULT NULL,
    `confirmado` tinyint(1) NOT NULL DEFAULT 0,
    `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `usuarios_email_UQ` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 3. Tabla proyectos (Incluye cliente y descripcion de HU-05/HU-06 y URL única)
CREATE TABLE `proyectos` (
    `id` int NOT NULL AUTO_INCREMENT,
    `proyecto` varchar(80) NOT NULL,
    `cliente` varchar(80) NOT NULL,
    `descripcion` text DEFAULT NULL,
    `url` varchar(32) NOT NULL,
    `propietarioId` int NOT NULL,
    `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `proyectos_url_UQ` (`url`),
    KEY `proyectos_usuarios_FK` (`propietarioId`),
    CONSTRAINT `proyectos_usuarios_FK`
        FOREIGN KEY (`propietarioId`) REFERENCES `usuarios` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 4. Tabla pivote proyecto_usuarios (Relación N:M para HU-07 y roles por proyecto)
CREATE TABLE `proyecto_usuarios` (
    `id` int NOT NULL AUTO_INCREMENT,
    `proyectoId` int NOT NULL,
    `usuarioId` int NOT NULL,
    `rolId` tinyint NOT NULL DEFAULT 2,
    `agregado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `proyecto_usuario_UQ` (`proyectoId`, `usuarioId`),
    KEY `pu_usuario_FK` (`usuarioId`),
    KEY `pu_rol_FK` (`rolId`),
    CONSTRAINT `pu_proyecto_FK`
        FOREIGN KEY (`proyectoId`) REFERENCES `proyectos` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `pu_usuario_FK`
        FOREIGN KEY (`usuarioId`) REFERENCES `usuarios` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `pu_rol_FK`
        FOREIGN KEY (`rolId`) REFERENCES `roles` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 5. Tabla tareas (Borrado en cascada HU-08 y asignación a usuario HU-09)
CREATE TABLE `tareas` (
    `id` int NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `estado` tinyint(1) NOT NULL DEFAULT 0,
    `proyectoId` int NOT NULL,
    `asignadoId` int DEFAULT NULL,
    `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `tareas_proyectos_FK` (`proyectoId`),
    KEY `tareas_asignado_FK` (`asignadoId`),
    CONSTRAINT `tareas_proyectos_FK`
        FOREIGN KEY (`proyectoId`) REFERENCES `proyectos` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `tareas_asignado_FK`
        FOREIGN KEY (`asignadoId`) REFERENCES `usuarios` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
