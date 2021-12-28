-- ---
-- Globals
-- ---

-- SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS=0;

-- ---
-- Table 'User'
-- 
-- ---

DROP TABLE IF EXISTS `User`;
		
CREATE TABLE `User` (
  `id` INTEGER NOT NULL AUTO_INCREMENT,
  `password` VARCHAR(200) NOT NULL DEFAULT 'NULL',
  -- `email` VARCHAR(200) NOT NULL DEFAULT 'NULL',
  -- `phone` VARCHAR(20) NOT NULL DEFAULT 'NULL',
  -- `name` VARCHAR(200) NOT NULL DEFAULT 'NULL',
  -- `surname` VARCHAR(200) NULL DEFAULT NULL,
  -- `lastname` VARCHAR(200) NULL DEFAULT NULL,
  `isAdmin` bit NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
);


-- ---
-- Table 'RefreshTokens'
-- 
-- ---

DROP TABLE IF EXISTS `RefreshTokens`;
		
CREATE TABLE `RefreshTokens` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `userId` INTEGER(10) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
);

-- ---
-- Table 'Category'
-- 
-- ---

DROP TABLE IF EXISTS `Category`;
		
CREATE TABLE `Category` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `parentId` INTEGER(10) NULL,
  `name` VARCHAR(255) NOT NULL,
  `img` VARCHAR(255) NULL,
  `categoryOrder` INTEGER(10) NOT NULL,
  PRIMARY KEY (`id`)
);

-- ---
-- Table 'Product'
-- 
-- ---

DROP TABLE IF EXISTS `Product`;
		
CREATE TABLE `Product` (
  `id` VARCHAR(20) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `price` FLOAT NOT NULL,
  `nds` INTEGER(255) NULL,
  `ndsMode` INTEGER(255) NULL,
  `volume` VARCHAR(255) NULL,
  `note1` VARCHAR(255) NULL,
  `note2` VARCHAR(255) NULL,
  `coefficient` VARCHAR(255) NULL,
  `pack` VARCHAR(255) NULL,
  `description` TEXT NOT NULL,

  PRIMARY KEY (`id`)
);

-- ---
-- Table 'ProductCategory'
-- 
-- ---

DROP TABLE IF EXISTS `ProductCategory`;
		
CREATE TABLE `ProductCategory` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `productId` INTEGER(10) NOT NULL,
  `categoryId` INTEGER(10) NOT NULL,
  PRIMARY KEY (`id`)
);

-- ---
-- Table 'ProductImage'
-- 
-- ---

DROP TABLE IF EXISTS `ProductImage`;
		
CREATE TABLE `ProductImage` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `src` VARCHAR(255) NOT NULL,
  `productId` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`id`)
);

-- ---
-- Foreign Keys 
-- ---

ALTER TABLE `RefreshTokens` ADD FOREIGN KEY (userId) REFERENCES `User` (`id`) ON DELETE CASCADE;


--
-- Дамп данных таблицы `User`
--

INSERT INTO `User` (`id`, `password`, `isAdmin`) VALUES
(1, '$2y$10$XoQ9FN8HgxwIFjjDdcuCaOsMtXZagMQoNdWXIB1VMDzbigWh5oacW', b'1')
