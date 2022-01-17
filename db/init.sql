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
  `categoryOrder` INTEGER(10) DEFAULT 0,
  `isSpecial` BIT(1) DEFAULT 0,
  `isTulip` BIT(1) DEFAULT 0,
  PRIMARY KEY (`id`)
);

-- ---
-- Table 'CategoryStep'
-- 
-- ---

DROP TABLE IF EXISTS `CategoryStep`;
		
CREATE TABLE `CategoryStep` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `categoryId` INTEGER(10) NOT NULL,
  `countFrom` INTEGER(10) NOT NULL,
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
  `nds` INTEGER(20) NULL,
  `ndsMode` INTEGER(20) NULL,
  `volume` VARCHAR(255) NULL,
  `note1` VARCHAR(255) NULL,
  `note2` VARCHAR(255) NULL,
  `coefficient` INTEGER(20) NULL,
  `pack` VARCHAR(255) NULL,
  `description` TEXT NOT NULL,
  `isPopular` BIT(1) DEFAULT 0,


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
-- Table 'ProductPrice'
-- 
-- ---

DROP TABLE IF EXISTS `ProductPrice`;
		
CREATE TABLE `ProductPrice` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `productId` VARCHAR(20) NOT NULL,
  `countFrom` INTEGER(10) NOT NULL,
  `price` FLOAT NOT NULL,
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
  `title` VARCHAR(255) NULL,
  `routerLink` VARCHAR(255) NULL,
  `label` VARCHAR(255) NULL,
  `autoPlay` INTEGER(10) NOT NULL,
  `isUserCanLeaf` BIT(1) NOT NULL,
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
-- Table 'ContactPhoto'
-- 
-- ---

DROP TABLE IF EXISTS `ContactPhoto`;
		
CREATE TABLE `ContactPhoto` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `img` VARCHAR(255) NOT NULL,
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
-- Table 'Sale'
-- 
-- ---

DROP TABLE IF EXISTS `Sale`;
		
CREATE TABLE `Sale` (
  `id` INTEGER(10) AUTO_INCREMENT,
  `img` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `productId` VARCHAR(20) NULL,
  `discount` FLOAT NOT NULL,
  `categoryId` INTEGER(10) NULL,
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
ALTER TABLE `Sale` ADD FOREIGN KEY (productId) REFERENCES `Product` (`id`) ON DELETE CASCADE;
ALTER TABLE `Sale` ADD FOREIGN KEY (categoryId) REFERENCES `Category` (`id`) ON DELETE CASCADE;
ALTER TABLE `ProductImage` ADD FOREIGN KEY (productId) REFERENCES `Product` (`id`) ON DELETE CASCADE;
ALTER TABLE `StaticPhoto` ADD FOREIGN KEY (staticId) REFERENCES `Static` (`id`) ON DELETE CASCADE;
ALTER TABLE `ProductPrice` ADD FOREIGN KEY (productId) REFERENCES `Product` (`id`) ON DELETE CASCADE;
ALTER TABLE `CategoryStep` ADD FOREIGN KEY (categoryId) REFERENCES `Category` (`id`) ON DELETE CASCADE;


--
-- Дамп данных таблицы `User`
--

INSERT INTO `User` (`id`, `password`, `isAdmin`) VALUES
(1, '$2y$10$XoQ9FN8HgxwIFjjDdcuCaOsMtXZagMQoNdWXIB1VMDzbigWh5oacW', b'1');

INSERT INTO `Box` (`id`, `name`, `volume`, `price`) VALUES
(1, 'Транспортировочная коробка 600х400х400', 500, 150),
(2, 'Транспортировочная коробка для кассеты 54 ячейки', 54, 60);

INSERT INTO `Category` (`id`, `parentId`, `name`, `img`, `categoryOrder`, `isSpecial`, `isTulip`) VALUES
(1, NULL, 'Тюльпаны на 8 марта', 'http://stand3.progoff.ru/back/CategoryImages/61d36bf2b5eca_1.png', 1, b'1', b'1'),
(2, NULL, 'Рассада однолетних цветов', 'http://stand3.progoff.ru/back/CategoryImages/61d36bf97b905_2.png', NULL, b'1', NULL),
(3, NULL, 'Многолетние растения', 'http://stand3.progoff.ru/back/CategoryImages/61d36c2150941_4.png', 3, NULL, NULL),
(4, NULL, 'Рассада овощей', 'http://stand3.progoff.ru/back/CategoryImages/61d36c2c1ea64_6.png', 4, NULL, NULL),
(5, 2, 'Бархатцы', 'http://stand3.progoff.ru/back/CategoryImages/61e2a5443c001_strong-gold.png', 4, b'0', NULL),
(6, NULL, 'Ампельные растения а кашпо', 'http://stand3.progoff.ru/back/CategoryImages/61e2a94d4599b_IMG_3543.jpg', 1, NULL, NULL),
(8, 2, 'Агератум', 'http://stand3.progoff.ru/back/CategoryImages/61e4787f3ab5e_strong-gold.png', 6, b'0', NULL),
(9, 2, 'Иберис', 'http://stand3.progoff.ru/back/CategoryImages/61e478a901e3c_strong-gold.png', 7, b'0', NULL);

INSERT INTO `Product` (`id`, `boxId`, `name`, `price`, `nds`, `ndsMode`, `volume`, `note1`, `note2`, `coefficient`, `pack`, `description`, `isPopular`) VALUES
('00~Pvjh01M96', 1, 'Тестовый объект 2', 20, NULL, NULL, '00~Pvjh0000F', 'Горшок 25см', 'null', 1, '00~Pvjh0000F', '«Petra» – уникальный сорт кротона, сегодня считающийся одним из наиболее известных и часто продаваемых. У этого растения крупные яйцевидные листья до 30 см в длину формируют компактную, удивительно орнаментальную крону. Отличительная черта сорта – доминирование только зеленого и желтого окрасов и очень толстые прожилки, расположенные по центру листовой пластины и отходящие от нее «ребрами» с выемчатым краем. Только на очень старых листьях кротона края листовой пластины и центральная жилка приобретают легкий красноватый тон.', b'1'),
('00~Pvjh01M97', 1, 'Тестовый объект 3', 26, 0, 0, '00~Pvjh0000F', 'Коробка 54шт', '', 54, '00~Pvjh0000F', '«Petra» – уникальный сорт кротона, сегодня считающийся одним из наиболее известных и часто продаваемых. У этого растения крупные яйцевидные листья до 30 см в длину формируют компактную, удивительно орнаментальную крону. Отличительная черта сорта – доминирование только зеленого и желтого окрасов и очень толстые прожилки, расположенные по центру листовой пластины и отходящие от нее «ребрами» с выемчатым краем. Только на очень старых листьях кротона края листовой пластины и центральная жилка приобретают легкий красноватый тон.', b'0'),
('00~Pvjh01MFD', 1, 'Стронг голд', 50, NULL, NULL, '00~Pvjh0000F', 'null', 'null', 20, '00~Pvjh0000F', 'Тюльпан Стронг Голд – истинный самородок из «золотого фонда» голландской селекции. Высота стебля до 55-60см, бокал достигает 9 см в длину и 6 см – в ширину. Сила растения настолько велика, что свежесрезанный, поставленный в воду цветок этого сорта способен подрасти еще на несколько сантиметров, а при условии регулярной смены воды он сохранится как минимум 15 дней.Окраска бутона равномерная, ярко-желтая, что придает цветку нарядный, праздничный вид. Даже в пасмурную зимнюю погоду букет тюльпанов Стронг Голд способен поднять настроение и напомнить о лете, которое ждет впереди!', b'1'),
('00~Pvjh01MFE', 2, 'Бархатцы Еллоу', 18, 0, 0, '00~Pvjh0000F', '', '', 54, '00~Pvjh0000F', 'Тагетес отклоненный желтый', b'1'),
('00~Pvjh01MFF', 1, 'Антарктика', 40, NULL, NULL, '00~Pvjh0000F', NULL, NULL, 20, '00~Pvjh0000F', 'Название сорта тюльпана Антарктика говорит само за себя. В полуроспуске - белый со светло-жёлтой спинкой, в роспуске - чисто-белый. Листья светло-зелёные. Форма цветка бокаловидная. Высота бокала до 7 см.Снежно-белый цвет бутона придает цветку ледяную строгость и царственное благородство. Бокал классический, немного вытянутый, плотно собранный. До начала распускания допускается едва заметный желтоватый оттенок спинки, который в последующем исчезает, уступая место самому чистому белому оттенку.Высота растения может составлять от 40 до 70 см, и это один из самых высоких сортов тюльпанов группы Триумф. Стебель средней силы, листья светло-зеленые, эффектно оттеняют бутон. Тюльпан Антарктика за счет контрастного сочетания насыщенной зелени и белоснежных лепестков бокала смотрится очень гармонично и привлекательно.', b'1'),
('00~Pvjh01MFG', 1, 'Апдейт', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Тюльпаны высотой 55-60 см., бокал до 8 см. Тюльпаны Белого цвета. Лепестки тюльпанов Апдейт имеют белоснежный окрас, который символизирует чистоту и женственность. Высота растения составляет 55-60 см, бутон имеет бокаловидную форму и достигает 6-8 см в высоту.', b'1'),
('00~Pvjh01MFH', 1, 'Барри Альта', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Тюльпаны высотой 55-60 см., бокал до 8 см.', b'1'),
('00~Pvjh01MFI', 1, 'Барселона', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Тюльпан Барселона относится к классу Триумф и представляет собой элегантный цветок с классической бокаловидной формой бутона. Стебель крепкий, идеально ровный, высотой от 50 до 70 см. Бутон плотный, до 8 см высотой и до 6 см в диаметре, длительное время сохраняет форму, источает приятный, легкий аромат.', b'1'),
('00~Pvjh01MFJ', 1, 'Буллет', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Тюльпаны высотой 55-60 см., бокал до 8 см.', b'1'),
('00~Pvjh01MFK', 1, 'Вайт Дримс', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Тюльпан Триумф White Dream порадует Вас изящными слегка изогнутыми линиями лепестков, плотно прилегающих друг к другу. Нежно-белый цветок в высоту около полуметра подходит для весеннего украшения сада и для создания букетов.', b'1'),
('00~Pvjh01MFL', 1, 'Денмарк', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Денмарк – тюльпан с классическим бокаловидным полным бутоном ярко-красного цвета с желтой каймой. Высота цветоноса 40-50 см, бутона – до 8 см. Сорт этих «пламенных» цветов выведен в Голландии и относится к классу Триумф. В плотно собранном состоянии в окраске бутоне преобладает красный цвет, кайма едва заметна только в самой верхней части. По мере роспуска кайма становится шире и заметнее, граница оттенков - более четкой, красная часть лепестка напоминает широкий мазок с заостренной вершиной. Дно бокала повторяет оттенок каймы.', b'1'),
('00~Pvjh01MFM', 1, 'Доу Джонс', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Высота тюльпана до 50 см, группа триумф Тюльпан Доу Джонс - счастливый собственник очень прочных стеблей, которые не может согнуть ветер или сильный дождь.', b'1'),
('00~Pvjh01MFN', 1, 'Колумбус (махровый)', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Тюльпаны&amp;nbsp;КОЛУМБУС высотой 45-50 см., Бокал крупный. Цвет красный с белыми кончиками.', b'1'),
('00~Pvjh01MFO', 1, 'Кунг Фу', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Кремово-белая широкая кайма очерчивает бархатистые красно-лиловые лепестки, и эти два оттенка великолепно сочетаются с сизовато-зелеными матовыми листьями широколанцетовидной формы. Цветоносный стебель отличается прочностью и достигает 55 см в высоту.', b'1'),
('00~Pvjh01MFP', 1, 'Лалибела', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Тюльпаны гибридного сорта «Лалибела» пользуются особым спросом за свой оригинальный внешний вид. Сорт «Лалибела» относится к селекционным, впервые был выведен в Голландии. Тюльпаны этой разновидности имеют крупный бокаловидный бутон, как правило, размером около 8 см. Окрас ровный, насыщенно-красный.&amp;nbsp;', b'1'),
('00~Pvjh01MFQ', 1, 'Лаптоп', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Тюльпан «Laptop» — изящное решение для украшения вашего приусадебного участка. Весною, когда мир просыпается от зимней спячки, невозможно не отметить всё буйство красок, даруемых природой изголодавшемуся по весенним красотам человеку. Но, как ни крути, именно тюльпаны стали неизменным атрибутом любого сада. Оно и не мудрено, ведь столь красивые цветы тотчас привлекают на себя всё внимание! Если вы хотите порадовать близкого вам человека потрясающим фиолетовым цветком — то именно тюльпан Laptop как нельзя лучше вам подходит! Ежели вы — опытный флорист, то данный сорт тюльпана станет потрясающим средством выражения даже самой смелой вашей идеи! Цветущие с конца апреля тюльпаны, достигают в высоту до пятидесяти сантиметров и обладают бокаловидным бутоном фиолетового цвета.', b'1'),
('00~Pvjh01MFR', 1, 'Лаура Фуджи', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Высота растения 50-60 см. Красный бокал цветка, плавно переходящий в желтую кайму, притягивает взгляд зрителя, заставляя в полной мере оценить игру оттенков.', b'1'),
('00~Pvjh01MFS', 1, 'Никон (махровый)', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Тюльпаны НИКОН высотой 45-50 см., бокал крупный. Тюльпаны ярко желтого цвета.', b'1'),
('00~Pvjh01MFT', 1, 'Престо (махровый)', 40, 0, 0, '00~Pvjh0000F', '', '', 20, '00~Pvjh0000F', 'Тюльпаны сорта ПРЕСТО достигают 45-50 см в высоту. Класс – Махровые. Цвет бутонов – насышенно красный.', b'1');

INSERT INTO `ProductCategory` (`id`, `productId`, `categoryId`) VALUES
(6, '00~Pvjh01M97', 4),
(7, '00~Pvjh01M97', 3),
(69, '00~Pvjh01MFE', 5),
(71, '00~Pvjh01MFF', 1),
(73, '00~Pvjh01MFD', 1),
(74, '00~Pvjh01MFG', 1),
(75, '00~Pvjh01MFH', 1),
(76, '00~Pvjh01MFI', 1),
(77, '00~Pvjh01MFJ', 1),
(78, '00~Pvjh01MFK', 1),
(79, '00~Pvjh01MFL', 1),
(80, '00~Pvjh01MFM', 1),
(81, '00~Pvjh01MFN', 1),
(82, '00~Pvjh01MFO', 1),
(83, '00~Pvjh01MFP', 1),
(84, '00~Pvjh01MFQ', 1),
(85, '00~Pvjh01MFR', 1),
(86, '00~Pvjh01MFS', 1),
(87, '00~Pvjh01MFT', 1),
(90, '00~Pvjh01M96', 2),
(91, '00~Pvjh01M96', 3);

INSERT INTO `ProductImage` (`id`, `src`, `productId`) VALUES
(5, 'http://stand3.progoff.ru/back/Images/61d36be0bdf17_2.png', '00~Pvjh01M96'),
(6, 'http://stand3.progoff.ru/back/Images/61d36be0bdf17_3.png', '00~Pvjh01M96'),
(7, 'http://stand3.progoff.ru/back/Images/61d36c569e948_3.png', '00~Pvjh01M97'),
(8, 'http://stand3.progoff.ru/back/Images/61d36c569e948_4.png', '00~Pvjh01M97'),
(30, 'http://stand3.progoff.ru/back/Images/61e2b3e196432_Лалиьелла 19.jpg', '00~Pvjh01MFE'),
(32, 'http://stand3.progoff.ru/back/Images/61e4131008f4b_ant.jpg', '00~Pvjh01MFF'),
(34, 'http://stand3.progoff.ru/back/Images/61e413e87b4fa_589b602ca2cd1_strong_gold_zheltyy.jpg', '00~Pvjh01MFD'),
(35, 'http://stand3.progoff.ru/back/Images/61e414810d9e2_11528-p1180172.png', '00~Pvjh01MFG'),
(36, 'http://stand3.progoff.ru/back/Images/61e4159693ce5_maxresdefault.jpg', '00~Pvjh01MFH'),
(37, 'http://stand3.progoff.ru/back/Images/61e4163fbd917_barcelona.jpg', '00~Pvjh01MFI'),
(38, 'http://stand3.progoff.ru/back/Images/61e41780a8ec5_43531802.jfif', '00~Pvjh01MFJ'),
(39, 'http://stand3.progoff.ru/back/Images/61e417de3a06c_738183.jpg', '00~Pvjh01MFK'),
(40, 'http://stand3.progoff.ru/back/Images/61e41872f1f9d_225ef5c5d55679d.jpg', '00~Pvjh01MFL'),
(41, 'http://stand3.progoff.ru/back/Images/61e418becb857_41840.970.jpg', '00~Pvjh01MFM'),
(42, 'http://stand3.progoff.ru/back/Images/61e419f8f404b_17-6.jpg', '00~Pvjh01MFN'),
(43, 'http://stand3.progoff.ru/back/Images/61e41b39d93bc_unnamed.jpg', '00~Pvjh01MFO'),
(44, 'http://stand3.progoff.ru/back/Images/61e41c9c35d65_2433_lalibela_0.jpg', '00~Pvjh01MFP'),
(45, 'http://stand3.progoff.ru/back/Images/61e41d1c48cfa_4-laptop-foto-2.jpg', '00~Pvjh01MFQ'),
(46, 'http://stand3.progoff.ru/back/Images/61e41e29f2dbb_11-429.jpg', '00~Pvjh01MFR'),
(47, 'http://stand3.progoff.ru/back/Images/61e41eef86e99_flowers-1585233_1280.jpg', '00~Pvjh01MFS'),
(48, 'http://stand3.progoff.ru/back/Images/61e41f7deb270_27260bf09cc8b8df.jpg', '00~Pvjh01MFT');


INSERT INTO `ProductPrice` (`id`, `productId`, `countFrom`, `price`) VALUES
(1, '00~Pvjh01MFD', 500, 45),
(2, '00~Pvjh01MFD', 1000, 40);

INSERT INTO `CategoryStep` (`id`, `categoryId`, `countFrom`) VALUES
(1, 1, 500),
(2, 1, 1000);

INSERT INTO `Static` (`id`, `title`, `routerLink`, `label`, `autoPlay`, `isUserCanLeaf`, `description`) VALUES
(1, 'Агрофирма Цветочная Долина', 'tyulpany-na-8-marta', 'Каталог тюльпанов', 5, b'1', 'Тепличное хозяйство Агрофирма «Цветочная Долина» является признанным производителем цветочной продукции широкого ассортимента. Наш принцип - индивидуальный подход к клиенту и постоянный контроль качества продукции на каждом этапе выполнения работ. Наша Агрофирма старается удовлетворить запросы самых взыскательных клиентов, как профессионалов цветочного бизнеса, так и садоводов-любителей.'),
(2, NULL, NULL, NULL, 3, b'1', ''),
(3, NULL, NULL, NULL, 1000, b'1', ''),
(4, NULL, NULL, NULL, 3, b'0', '');

INSERT INTO `StaticPhoto` (`id`, `src`, `staticId`) VALUES
(10, 'http://stand3.progoff.ru/back/MainImages/61e1eb2ebb41f_0e1f806d4ba503ead96b647cb6f8763e.jpg', 2),
(13, 'http://stand3.progoff.ru/back/MainImages/61e1ebd5400b5_2.png', 3),
(15, 'http://stand3.progoff.ru/back/MainImages/61e2a4851b52a_strong-gold.png', 1),
(16, 'http://stand3.progoff.ru/back/MainImages/61e2a8cd91923_Санбелт кр пл 21 .JPG', 1);

INSERT INTO `Sale` (`id`, `img`, `title`, `description`, `productId`, `discount`, `categoryId`) VALUES
(3, 'http://stand3.progoff.ru/back/SaleImages/61e4a6b4a7823_sale_1.png', 'Скидка на рассады до 20%', 'Скидка распространяется на рассады однолетних цветов и рассады овощей', NULL, 0, 2);


INSERT INTO `Video` (`id`, `src`, `title`, `description`) VALUES
(1, 'https://youtube.com/embed/j_BDRnG9DR4', 'ПРОИЗВОДСТВО ТЮЛЬПАНОВ | МОЯ ИСТОРИЯ | АГРОФИРМА ЦВЕТОЧНАЯ ДОЛИНА', 'Цветочная Долина работает на рынке более 20 лет. Выращиваем более 1,5 млн цветов в год. Ежегодно мы выращиваем 240 тысяч тюльпанов, которые выгоняются из лучших голландских луковиц размера 12+.\nНаша традиция – выращивание отборного, качественного тюльпана. Наш тюльпан – длиной до 50-60 см, бокал до 8 см. Ручное производство, длительная выгонка цветов, опытные специалисты, профессиональный подход к выращиванию цветов. '),
(2, 'https://youtube.com/embed/j_BDRnG9DR4', 'ПРОИЗВОДСТВО ТЮЛЬПАНОВ | МОЯ ИСТОРИЯ | АГРОФИРМА ЦВЕТОЧНАЯ ДОЛИНА', 'Цветочная Долина работает на рынке более 20 лет. Выращиваем более 1,5 млн цветов в год. Ежегодно мы выращиваем 240 тысяч тюльпанов, которые выгоняются из лучших голландских луковиц размера 12+.'),
(3, 'https://youtube.com/embed/c_thL5aWHko', 'ЧТО ТАКОЕ КАЧЕСТВЕННАЯ РАССАДА? | АГРОФИРМА ЦВЕТОЧНАЯ ДОЛИНА', '\nАгрофирма Цветочная Долина работает на рынке более 20 лет.\nВыращиваем более 1,5 млн цветов в год.'),
(4, 'https://youtube.com/embed/mueAlhkQaDs', 'НТВ ОБ АГРОФИРМЕ ЦВЕТОЧНАЯ ДОЛИНА | РАССАДА ЦВЕТОВ 2017Г', 'Ежегодно мы выращиваем 240 тысяч тюльпанов, которые выгоняются из лучших голландских луковиц размера 12+ . Наша традиция – выращивание отборного, качественного тюльпана. ');


