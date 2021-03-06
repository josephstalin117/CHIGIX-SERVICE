﻿# Host: CHIGIX.com  (Version: 5.5.16-log)
# Date: 2013-03-12 12:25:42
# Generator: MySQL-Front 5.3  (Build 2.36)

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE */;
/*!40101 SET SQL_MODE='' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES */;
/*!40103 SET SQL_NOTES='ON' */;

#
# Source for table "chigi_page"
#

DROP TABLE IF EXISTS `chigi_page`;
CREATE TABLE `chigi_page` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `pagename` char(10) NOT NULL DEFAULT 'index' COMMENT '页面注册名',
  `domain` char(20) NOT NULL DEFAULT 'www.chigix.com' COMMENT '所属域名',
  `protocol` char(6) NOT NULL DEFAULT 'http' COMMENT '协议',
  `status` bit(1) NOT NULL DEFAULT b'0' COMMENT '是否可用',
  `apphost` char(32) NOT NULL DEFAULT '' COMMENT '所属应用',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `pagename` (`pagename`,`status`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='全局页面注册表';

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
