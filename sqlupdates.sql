ALTER TABLE `files`
    ADD COLUMN `title` VARCHAR(255) NULL DEFAULT NULL AFTER `masterId`;


ALTER TABLE `files`
    ADD COLUMN `masterId` INT NULL DEFAULT NULL AFTER `folderId`,
    ADD COLUMN `description` TEXT NULL DEFAULT NULL AFTER `name`,
    ADD COLUMN `copyright` VARCHAR(255) NULL DEFAULT NULL AFTER `uid`;


CREATE TABLE `locations` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `date` datetime NOT NULL,
     `updated` datetime NOT NULL,
     `pageId` int(11) DEFAULT NULL,
     `pluginId` int(11) DEFAULT NULL,
     `orderId` int(11) NOT NULL DEFAULT '0',
     `className` varchar(255) NOT NULL,
     `title` varchar(255) NOT NULL,
     `config` text,
     `street` varchar(255) DEFAULT NULL,
     `streetNumber` varchar(255) DEFAULT NULL,
     `zipcode` varchar(255) DEFAULT NULL,
     `city` varchar(255) DEFAULT NULL,
     `country` varchar(255) DEFAULT NULL,
     PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `persons` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `date` datetime NOT NULL,
   `updated` datetime NOT NULL,
   `pluginId` int(11) DEFAULT NULL,
   `pageId` int(11) DEFAULT NULL,
   `orderId` int(11) DEFAULT '0',
   `className` varchar(255) NOT NULL,
   `gender` varchar(45) DEFAULT NULL,
   `title` varchar(255) DEFAULT NULL,
   `firstName` varchar(255) DEFAULT NULL,
   `lastName` varchar(255) DEFAULT NULL,
   `phone` varchar(255) DEFAULT NULL,
   `email` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `categories_2_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `categoryId` int(11) NOT NULL,
  `categoryClass` varchar(255) NOT NULL,
  `itemId` int(11) NOT NULL,
  `itemClass` varchar(255) NOT NULL,
  `config` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


ALTER TABLE `categories`
    CHANGE COLUMN `model` `model` VARCHAR(255) NULL ;


ALTER TABLE `categories`
    ADD COLUMN `className` VARCHAR(255) NOT NULL AFTER `model`;


ALTER TABLE `categories`
    ADD COLUMN `uid` VARCHAR(255) NULL AFTER `config`;


ALTER TABLE `assets`
    ADD COLUMN `dateStart` DATETIME NULL AFTER `alias`,
    ADD COLUMN `dateEnd` DATETIME NULL AFTER `dateStart`;


ALTER TABLE `admin_apps`
    ADD COLUMN `menuId` VARCHAR(255) NULL DEFAULT 'Global' AFTER `icon`;


ALTER TABLE `content_elements`
    ADD COLUMN `inheritance` VARCHAR(45) NULL DEFAULT 'None' AFTER `type`;


ALTER TABLE `aliases`
    CHANGE COLUMN `data` `payload` TEXT NULL DEFAULT NULL ;


CREATE TABLE `content_widgets` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `date` datetime NOT NULL,
   `updated` datetime NOT NULL,
   `userId` int(11) NOT NULL,
   `className` varchar(255) NOT NULL,
   `config` text,
   `textUid` varchar(255) NOT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;



ALTER TABLE `content_texts`
    ADD COLUMN `config` TEXT NULL AFTER `text`;

ALTER TABLE `assets`
    CHANGE COLUMN `pluginId` `pluginId` INT(11) NULL DEFAULT 0 ,
    CHANGE COLUMN `orderId` `orderId` INT(11) NULL DEFAULT 0 ;

ALTER TABLE `assets`
    ADD COLUMN `orderId` INT NULL AFTER `parentId`;

ALTER TABLE `assets`
    ADD COLUMN `parentId` INT NULL AFTER `pluginId`;

ALTER TABLE `files`
    ADD COLUMN `uid` VARCHAR(255) NULL AFTER `path`;

ALTER TABLE `assets`
    CHANGE COLUMN `config` `config` TEXT NULL DEFAULT NULL ;

CREATE TABLE `content_texts` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `date` datetime NOT NULL,
     `updated` datetime NOT NULL,
     `userId` int(11) NOT NULL,
     `uid` varchar(255) CHARACTER SET utf8 NOT NULL,
     `text` text CHARACTER SET utf8mb4,
     PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `content_elements`
    CHANGE COLUMN `parentId` `parentId` INT(11) NULL DEFAULT 0 ;

ALTER TABLE `content_elements`
    ADD COLUMN `type` VARCHAR(255) NOT NULL AFTER `config`;

ALTER TABLE `content_plugins`
    CHANGE COLUMN `className` `className` VARCHAR(255) NOT NULL AFTER `socket`,
    ADD COLUMN `parentId` INT NULL AFTER `updated`, RENAME TO  `content_elements` ;

DROP TABLE `content_elements`;

ALTER TABLE `content_plugins`
ADD COLUMN `title` VARCHAR(255) NULL AFTER `socket`;

ALTER TABLE `admin_apps` 
CHANGE COLUMN `className` `controllerName` VARCHAR(255) NOT NULL ;

ALTER TABLE `admin_apps` 
ADD COLUMN `icon` VARCHAR(16) NULL AFTER `title`;
