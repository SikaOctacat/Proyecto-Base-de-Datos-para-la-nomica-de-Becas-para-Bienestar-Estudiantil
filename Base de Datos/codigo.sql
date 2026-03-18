DROP DATABASE IF EXISTS becas_datase_v2;
CREATE DATABASE becas_datase_v2;
USE becas_datase_v2;

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE SCHEMA IF NOT EXISTS `Becas_Datase_v2` ;
USE `Becas_Datase_v2` ;


CREATE TABLE Estudiante
(
  CI                 INT                                        NOT NULL,
  primer_nombre      VARCHAR(50)                                NOT NULL,
  segundo_nombre     VARCHAR(50)                                NULL    ,
  primer_apellido    VARCHAR(50)                                NOT NULL,
  segundo_apellido   VARCHAR(50)                                NULL    ,
  fecha_nacimiento   DATE                                       NOT NULL,
  estado_civil       ENUM(Soltero,Casado,Divorciado,Viudo,Otro) NULL    ,
  numero_telefono    VARCHAR(20)                                NULL    ,
  correo_electronico VARCHAR(100)                               NULL    ,
  carnet_patria      VARCHAR(20)                                NULL    ,
  trabaja            BOOLEAN                                    NULL     DEFAULT FALSE,
  viaja              BOOLEAN                                    NULL     DEFAULT FALSE,
  PRIMARY KEY (CI)
);

ALTER TABLE Estudiante
  ADD CONSTRAINT UQ_correo_electronico UNIQUE (correo_electronico);

CREATE TABLE Familiares
(
  ID_familiares     INT           NOT NULL AUTO_INCREMENT,
  nombres           VARCHAR(100)  NOT NULL,
  apellidos         VARCHAR(100)  NULL    ,
  parentesco        VARCHAR(45)   NULL    ,
  edad              INT           NULL    ,
  nivel_instruccion VARCHAR(45)   NULL    ,
  ocupacion         VARCHAR(45)   NULL    ,
  ingreso_mensual   DECIMAL(12,2) NULL    ,
  estudiante_CI     INT           NOT NULL,
  PRIMARY KEY (ID_familiares, estudiante_CI)
);

CREATE TABLE Historial_Academico
(
  ID_historial          INT          NOT NULL AUTO_INCREMENT,
  ID_PNF                INT          NOT NULL,
  estudiante_CI         INT          NOT NULL,
  materias_inscritas    INT          NULL     DEFAULT 0,
  materias_aprobadas    INT          NULL     DEFAULT 0,
  materias_aplazadas    INT          NULL     DEFAULT 0,
  materias_inasistentes INT          NULL     DEFAULT 0,
  indice_trimestre      DECIMAL(4,2) NULL    ,
  PRIMARY KEY (ID_historial, ID_PNF, estudiante_CI)
);

CREATE TABLE Materias
(
  ID_materia       INT          NOT NULL AUTO_INCREMENT,
  nombre_materia   VARCHAR(100) NOT NULL,
  unidades_credito INT          NULL    ,
  ID_historial     INT          NOT NULL,
  ID_materia       INT          NOT NULL,
  ID_PNF           INT          NOT NULL,
  estudiante_CI    INT          NOT NULL,
  ID_historial     INT          NOT NULL,
  ID_PNF           INT          NOT NULL,
  estudiante_CI    INT          NOT NULL,
  PRIMARY KEY (ID_materia)
);

CREATE TABLE PNF
(
  ID_PNF        INT         NOT NULL AUTO_INCREMENT,
  fecha_ingreso YEAR        NOT NULL,
  trayecto      VARCHAR(45) NULL    ,
  trimestre     VARCHAR(45) NULL    ,
  estudiante_CI INT         NOT NULL,
  PRIMARY KEY (ID_PNF, estudiante_CI)
);

CREATE TABLE Trabajo
(
  ID_trabajo       INT           NOT NULL AUTO_INCREMENT,
  profesion_oficio VARCHAR(100)  NULL    ,
  ingreso_mensual  DECIMAL(12,2) NULL    ,
  estudiante_CI    INT           NOT NULL,
  PRIMARY KEY (ID_trabajo, estudiante_CI)
);

CREATE TABLE Vivienda_Estudiante
(
  ID_residencia         INT           NOT NULL AUTO_INCREMENT,
  tipo_tenencia         VARCHAR(45)   NULL    ,
  tipo_vivienda         VARCHAR(45)   NULL    ,
  zona_ubicacion        VARCHAR(45)   NULL    ,
  regimen_vivienda      VARCHAR(45)   NULL    ,
  direccion_local       TEXT          NULL    ,
  direccion_procedencia TEXT          NULL    ,
  telefono_local        VARCHAR(20)   NULL    ,
  telefono_procedencia  VARCHAR(20)   NULL    ,
  monto_alquiler        DECIMAL(12,2) NULL    ,
  estudiante_CI         INT           NOT NULL,
  PRIMARY KEY (ID_residencia, estudiante_CI)
);

ALTER TABLE PNF
  ADD CONSTRAINT FK_Estudiante_TO_PNF
    FOREIGN KEY (estudiante_CI)
    REFERENCES Estudiante (CI);

ALTER TABLE Historial_Academico
  ADD CONSTRAINT FK_PNF_TO_Historial_Academico
    FOREIGN KEY (ID_PNF, estudiante_CI)
    REFERENCES PNF (ID_PNF, estudiante_CI);

ALTER TABLE Familiares
  ADD CONSTRAINT FK_Estudiante_TO_Familiares
    FOREIGN KEY (estudiante_CI)
    REFERENCES Estudiante (CI);

ALTER TABLE Vivienda_Estudiante
  ADD CONSTRAINT FK_Estudiante_TO_Vivienda_Estudiante
    FOREIGN KEY (estudiante_CI)
    REFERENCES Estudiante (CI);

ALTER TABLE Trabajo
  ADD CONSTRAINT FK_Estudiante_TO_Trabajo
    FOREIGN KEY (estudiante_CI)
    REFERENCES Estudiante (CI);

ALTER TABLE Materias
  ADD CONSTRAINT FK_Historial_Academico_TO_Materias
    FOREIGN KEY (ID_historial, ID_PNF, estudiante_CI)
    REFERENCES Historial_Academico (ID_historial, ID_PNF, estudiante_CI);
