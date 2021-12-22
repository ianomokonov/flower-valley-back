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
-- Foreign Keys 
-- ---

ALTER TABLE `RefreshTokens` ADD FOREIGN KEY (userId) REFERENCES `User` (`id`) ON DELETE CASCADE;


--
-- Дамп данных таблицы `User`
--

-- INSERT INTO `User` (`id`, `password`, `isAdmin`) VALUES
-- (1, '$2y$10$2KGKgW0BISA4QzlaY6ljNe61sVXHmRpQV8quzjLFr9ZJv3gRWI.la', b'1'),
-- (2, '$2y$10$JAIkLQvfbwEJ7KTwgcbDC.U20YdyJQ59Sb71FiVY8zIAyRCQCmgqC', b'1'),
