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
  `boxId` INTEGER(10) NOT NULL,
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
-- Table 'Box'
-- 
-- ---

DROP TABLE IF EXISTS `Box`;
		
CREATE TABLE `Box` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `volume` INTEGER(20) NOT NULL,
  `price` FLOAT NOT NULL,
  PRIMARY KEY (`id`)
);

-- ---
-- Table 'ProductCategory'
-- 
-- ---

DROP TABLE IF EXISTS `ProductCategory`;
		
CREATE TABLE `ProductCategory` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `productId` VARCHAR(20) NOT NULL,
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
-- Table 'Static'
-- 
-- ---

DROP TABLE IF EXISTS `Static`;
		
CREATE TABLE `Static` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `routerLink` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  PRIMARY KEY (`id`)
);

-- ---
-- Table 'StaticPhoto'
-- 
-- ---

DROP TABLE IF EXISTS `StaticPhoto`;
		
CREATE TABLE `StaticPhoto` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `src` VARCHAR(255) NOT NULL,
  `staticId` INTEGER(10) NOT NULL,
  PRIMARY KEY (`id`)
);

-- ---
-- Table 'Comment'
-- 
-- ---

DROP TABLE IF EXISTS `Comment`;
		
CREATE TABLE `Comment` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `img` VARCHAR(255) NOT NULL,
  `link` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
);

-- ---
-- Table 'Client'
-- 
-- ---

DROP TABLE IF EXISTS `Client`;
		
CREATE TABLE `Client` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `img` VARCHAR(255) NOT NULL,
  `link` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
);

-- ---
-- Table 'Video'
-- 
-- ---

DROP TABLE IF EXISTS `Video`;
		
CREATE TABLE `Video` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `src` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  PRIMARY KEY (`id`)
);



-- ---
-- Foreign Keys 
-- ---

ALTER TABLE `RefreshTokens` ADD FOREIGN KEY (userId) REFERENCES `User` (`id`) ON DELETE CASCADE;
ALTER TABLE `Category` ADD FOREIGN KEY (parentId) REFERENCES `Category` (`id`) ON DELETE SET NULL;
ALTER TABLE `Product` ADD FOREIGN KEY (boxId) REFERENCES `Box` (`id`);
ALTER TABLE `ProductCategory` ADD FOREIGN KEY (productId) REFERENCES `Product` (`id`) ON DELETE CASCADE;
ALTER TABLE `ProductCategory` ADD FOREIGN KEY (categoryId) REFERENCES `Category` (`id`) ON DELETE CASCADE;
ALTER TABLE `ProductImage` ADD FOREIGN KEY (productId) REFERENCES `Product` (`id`) ON DELETE CASCADE;
ALTER TABLE `StaticPhoto` ADD FOREIGN KEY (staticId) REFERENCES `Static` (`id`) ON DELETE CASCADE;


--
-- Дамп данных таблицы `User`
--

INSERT INTO `User` (`id`, `password`, `isAdmin`) VALUES
(1, '$2y$10$XoQ9FN8HgxwIFjjDdcuCaOsMtXZagMQoNdWXIB1VMDzbigWh5oacW', b'1');

INSERT INTO `Box` (`id`, `volume`, `price`, `name`) VALUES
(1, 10, 24, 'Тестовая каробка');

INSERT INTO `Category` (`id`, `parentId`, `name`, `img`, `categoryOrder`) VALUES
(1, 0, 'Тюльпаны на 8 марта', 'http://stand3.progoff.ru/back/CategoryImages/61d36bf2b5eca_1.png', 0),
(2, 0, 'Рассада однолетних цветов', 'http://stand3.progoff.ru/back/CategoryImages/61d36bf97b905_2.png', 0),
(3, 0, 'Многолетние растения', 'http://stand3.progoff.ru/back/CategoryImages/61d36c2150941_4.png', 0),
(4, 0, 'Рассада овощей', 'http://stand3.progoff.ru/back/CategoryImages/61d36c2c1ea64_6.png', 0);

INSERT INTO `Product` (`id`, `boxId`, `name`, `price`, `nds`, `ndsMode`, `volume`, `note1`, `note2`, `coefficient`, `pack`, `description`) VALUES
('00~Pvjh01M94', 1, 'Тестовый объект 1', 1000, NULL, NULL, '00~Pvjh0000F', NULL, NULL, '10', '00~Pvjh0000F', '123'),
('00~Pvjh01M96', 1, 'Тестовый объект 2', 500, 0, 0, '00~Pvjh0000F', 'Горшок 25см', 'null', '1', '00~Pvjh0000F', '«Petra» – уникальный сорт кротона, сегодня считающийся одним из наиболее известных и часто продаваемых. У этого растения крупные яйцевидные листья до 30 см в длину формируют компактную, удивительно орнаментальную крону. Отличительная черта сорта – доминирование только зеленого и желтого окрасов и очень толстые прожилки, расположенные по центру листовой пластины и отходящие от нее «ребрами» с выемчатым краем. Только на очень старых листьях кротона края листовой пластины и центральная жилка приобретают легкий красноватый тон.'),
('00~Pvjh01M97', 1, 'Тестовый объект 3', 26, 0, 0, '00~Pvjh0000F', 'Коробка 54шт', '', '54', '00~Pvjh0000F', '«Petra» – уникальный сорт кротона, сегодня считающийся одним из наиболее известных и часто продаваемых. У этого растения крупные яйцевидные листья до 30 см в длину формируют компактную, удивительно орнаментальную крону. Отличительная черта сорта – доминирование только зеленого и желтого окрасов и очень толстые прожилки, расположенные по центру листовой пластины и отходящие от нее «ребрами» с выемчатым краем. Только на очень старых листьях кротона края листовой пластины и центральная жилка приобретают легкий красноватый тон.'),
('00~Pvjh01M99', 1, 'Тестовый объект 4', 2000, 0, 0, '00~Pvjh0000F', 'Горшок в ассортименте', '', '1', '00~Pvjh0000F', '«Petra» – уникальный сорт кротона, сегодня считающийся одним из наиболее известных и часто продаваемых. У этого растения крупные яйцевидные листья до 30 см в длину формируют компактную, удивительно орнаментальную крону. Отличительная черта сорта – доминирование только зеленого и желтого окрасов и очень толстые прожилки, расположенные по центру листовой пластины и отходящие от нее «ребрами» с выемчатым краем. Только на очень старых листьях кротона края листовой пластины и центральная жилка приобретают легкий красноватый тон.');

INSERT INTO `ProductCategory` (`id`, `productId`, `categoryId`) VALUES
(2, '00~Pvjh01M94', 1),
(3, '00~Pvjh01M94', 2),
(4, '00~Pvjh01M96', 2),
(5, '00~Pvjh01M96', 3),
(6, '00~Pvjh01M97', 4),
(7, '00~Pvjh01M97', 3),
(8, '00~Pvjh01M99', 1),
(9, '00~Pvjh01M99', 2),
(10, '00~Pvjh01M99', 3),
(11, '00~Pvjh01M99', 4);

INSERT INTO `ProductImage` (`id`, `src`, `productId`) VALUES
(1, 'http://stand3.progoff.ru/back/Images/61d35e969fdf5_1.png', '00~Pvjh01M94'),
(2, 'http://stand3.progoff.ru/back/Images/61d35e969fdf5_2.png', '00~Pvjh01M94'),
(3, 'http://stand3.progoff.ru/back/Images/61d35e969fdf5_3.png', '00~Pvjh01M94'),
(4, 'http://stand3.progoff.ru/back/Images/61d35e969fdf5_4.png', '00~Pvjh01M94'),
(5, 'http://stand3.progoff.ru/back/Images/61d36be0bdf17_2.png', '00~Pvjh01M96'),
(6, 'http://stand3.progoff.ru/back/Images/61d36be0bdf17_3.png', '00~Pvjh01M96'),
(7, 'http://stand3.progoff.ru/back/Images/61d36c569e948_3.png', '00~Pvjh01M97'),
(8, 'http://stand3.progoff.ru/back/Images/61d36c569e948_4.png', '00~Pvjh01M97'),
(9, 'http://stand3.progoff.ru/back/Images/61d36c78adf12_1.png', '00~Pvjh01M99');

INSERT INTO `Static` (`id`, `title`, `routerLink`, `description`) VALUES (NULL, 'Тюльпаны оптом', 'tyulpany-na-8-marta', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Semper duis libero, arcu sed. Aliquet ut sit vestibulum auctor id. Maecenas vel mollis et viverra aenean cursus. Consequat felis nec ultricies vel, massa, est nunc. Purus at a nisl.');


