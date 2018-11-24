-- MySQL dump 10.13  Distrib 5.6.36, for FreeBSD11.1 (amd64)
--
-- Host: localhost    Database: stg
-- ------------------------------------------------------
-- Server version	5.6.36

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `adcomments`
--

DROP TABLE IF EXISTS `adcomments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adcomments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scope` varchar(255) NOT NULL,
  `item` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(40) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scope` (`scope`),
  KEY `item` (`item`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `adcomments`
--

LOCK TABLES `adcomments` WRITE;
/*!40000 ALTER TABLE `adcomments` DISABLE KEYS */;
/*!40000 ALTER TABLE `adcomments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `address`
--

DROP TABLE IF EXISTS `address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `aptid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `aptid` (`aptid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `address`
--

LOCK TABLES `address` WRITE;
/*!40000 ALTER TABLE `address` DISABLE KEYS */;
/*!40000 ALTER TABLE `address` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admacquainted`
--

DROP TABLE IF EXISTS `admacquainted`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admacquainted` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `admin` varchar(40) NOT NULL,
  `annid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`admin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admacquainted`
--

LOCK TABLES `admacquainted` WRITE;
/*!40000 ALTER TABLE `admacquainted` DISABLE KEYS */;
/*!40000 ALTER TABLE `admacquainted` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admannouncements`
--

DROP TABLE IF EXISTS `admannouncements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admannouncements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admannouncements`
--

LOCK TABLES `admannouncements` WRITE;
/*!40000 ALTER TABLE `admannouncements` DISABLE KEYS */;
/*!40000 ALTER TABLE `admannouncements` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Table structure for table `ahenassign`
--

DROP TABLE IF EXISTS `ahenassign`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ahenassign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ahenid` int(11) NOT NULL,
  `streetname` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ahenassign`
--

LOCK TABLES `ahenassign` WRITE;
/*!40000 ALTER TABLE `ahenassign` DISABLE KEYS */;
/*!40000 ALTER TABLE `ahenassign` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ahenassignstrict`
--

DROP TABLE IF EXISTS `ahenassignstrict`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ahenassignstrict` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agentid` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ahenassignstrict`
--

LOCK TABLES `ahenassignstrict` WRITE;
/*!40000 ALTER TABLE `ahenassignstrict` DISABLE KEYS */;
/*!40000 ALTER TABLE `ahenassignstrict` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `apt`
--

DROP TABLE IF EXISTS `apt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buildid` int(11) NOT NULL,
  `entrance` varchar(15) DEFAULT NULL,
  `floor` varchar(15) DEFAULT NULL,
  `apt` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `apt` (`apt`),
  KEY `buildid` (`buildid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `apt`
--

LOCK TABLES `apt` WRITE;
/*!40000 ALTER TABLE `apt` DISABLE KEYS */;
/*!40000 ALTER TABLE `apt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bankstaparsed`
--

DROP TABLE IF EXISTS `bankstaparsed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bankstaparsed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `row` int(11) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `summ` float NOT NULL,
  `state` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bankstaparsed`
--

LOCK TABLES `bankstaparsed` WRITE;
/*!40000 ALTER TABLE `bankstaparsed` DISABLE KEYS */;
/*!40000 ALTER TABLE `bankstaparsed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bankstaraw`
--

DROP TABLE IF EXISTS `bankstaraw`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bankstaraw` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `rawdata` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bankstaraw`
--

LOCK TABLES `bankstaraw` WRITE;
/*!40000 ALTER TABLE `bankstaraw` DISABLE KEYS */;
/*!40000 ALTER TABLE `bankstaraw` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branchesadmins`
--

DROP TABLE IF EXISTS `branchesadmins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branchesadmins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `admin` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branchesadmins`
--

LOCK TABLES `branchesadmins` WRITE;
/*!40000 ALTER TABLE `branchesadmins` DISABLE KEYS */;
/*!40000 ALTER TABLE `branchesadmins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branchescities`
--

DROP TABLE IF EXISTS `branchescities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branchescities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `cityid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branchescities`
--

LOCK TABLES `branchescities` WRITE;
/*!40000 ALTER TABLE `branchescities` DISABLE KEYS */;
/*!40000 ALTER TABLE `branchescities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branchesservices`
--

DROP TABLE IF EXISTS `branchesservices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branchesservices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `serviceid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branchesservices`
--

LOCK TABLES `branchesservices` WRITE;
/*!40000 ALTER TABLE `branchesservices` DISABLE KEYS */;
/*!40000 ALTER TABLE `branchesservices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branchestariffs`
--

DROP TABLE IF EXISTS `branchestariffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branchestariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `tariff` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branchestariffs`
--

LOCK TABLES `branchestariffs` WRITE;
/*!40000 ALTER TABLE `branchestariffs` DISABLE KEYS */;
/*!40000 ALTER TABLE `branchestariffs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branchesusers`
--

DROP TABLE IF EXISTS `branchesusers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branchesusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `login` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branchesusers`
--

LOCK TABLES `branchesusers` WRITE;
/*!40000 ALTER TABLE `branchesusers` DISABLE KEYS */;
/*!40000 ALTER TABLE `branchesusers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `build`
--

DROP TABLE IF EXISTS `build`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `build` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `streetid` int(11) NOT NULL,
  `buildnum` varchar(10) NOT NULL,
  `geo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `buildnum` (`buildnum`),
  KEY `streetid` (`streetid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `build`
--

LOCK TABLES `build` WRITE;
/*!40000 ALTER TABLE `build` DISABLE KEYS */;
/*!40000 ALTER TABLE `build` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buildpassport`
--

DROP TABLE IF EXISTS `buildpassport`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `buildpassport` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buildid` int(11) NOT NULL,
  `owner` varchar(255) DEFAULT NULL,
  `ownername` varchar(255) DEFAULT NULL,
  `ownerphone` varchar(255) DEFAULT NULL,
  `ownercontact` varchar(255) DEFAULT NULL,
  `keys` tinyint(4) DEFAULT NULL,
  `accessnotices` varchar(255) DEFAULT NULL,
  `floors` int(11) DEFAULT NULL,
  `apts` int(11) DEFAULT NULL,
  `entrances` int(11) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `buildid` (`buildid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buildpassport`
--

LOCK TABLES `buildpassport` WRITE;
/*!40000 ALTER TABLE `buildpassport` DISABLE KEYS */;
/*!40000 ALTER TABLE `buildpassport` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `capab`
--

DROP TABLE IF EXISTS `capab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `capab` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `stateid` int(11) NOT NULL DEFAULT '0',
  `notes` text,
  `price` varchar(255) DEFAULT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `donedate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `state` (`stateid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `capab`
--

LOCK TABLES `capab` WRITE;
/*!40000 ALTER TABLE `capab` DISABLE KEYS */;
/*!40000 ALTER TABLE `capab` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `capabstates`
--

DROP TABLE IF EXISTS `capabstates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `capabstates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state` varchar(255) NOT NULL,
  `color` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `capabstates`
--

LOCK TABLES `capabstates` WRITE;
/*!40000 ALTER TABLE `capabstates` DISABLE KEYS */;
/*!40000 ALTER TABLE `capabstates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `capdata`
--

DROP TABLE IF EXISTS `capdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `capdata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `date` datetime DEFAULT NULL,
  `days` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `capdata`
--

LOCK TABLES `capdata` WRITE;
/*!40000 ALTER TABLE `capdata` DISABLE KEYS */;
/*!40000 ALTER TABLE `capdata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cardbank`
--

DROP TABLE IF EXISTS `cardbank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cardbank` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serial` varchar(255) NOT NULL,
  `cash` varchar(45) NOT NULL,
  `admin` varchar(45) NOT NULL,
  `date` datetime NOT NULL,
  `active` tinyint(1) NOT NULL,
  `used` tinyint(1) NOT NULL,
  `usedate` datetime DEFAULT NULL,
  `usedlogin` varchar(45) NOT NULL,
  `usedip` varchar(45) DEFAULT NULL,
  `part` varchar(255) DEFAULT NULL,
  `receipt_date` datetime DEFAULT NULL,
  `selling_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cardbank`
--

LOCK TABLES `cardbank` WRITE;
/*!40000 ALTER TABLE `cardbank` DISABLE KEYS */;
/*!40000 ALTER TABLE `cardbank` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cardbrute`
--

DROP TABLE IF EXISTS `cardbrute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cardbrute` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serial` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `login` varchar(45) NOT NULL,
  `ip` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cardbrute`
--

LOCK TABLES `cardbrute` WRITE;
/*!40000 ALTER TABLE `cardbrute` DISABLE KEYS */;
/*!40000 ALTER TABLE `cardbrute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashtype`
--

DROP TABLE IF EXISTS `cashtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cashtype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cashtype` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cashtype` (`cashtype`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashtype`
--

LOCK TABLES `cashtype` WRITE;
/*!40000 ALTER TABLE `cashtype` DISABLE KEYS */;
INSERT INTO `cashtype` VALUES (1,'Cash money');
/*!40000 ALTER TABLE `cashtype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catv_activity`
--

DROP TABLE IF EXISTS `catv_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catv_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `state` tinyint(4) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catv_activity`
--

LOCK TABLES `catv_activity` WRITE;
/*!40000 ALTER TABLE `catv_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `catv_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catv_bankstaparsed`
--

DROP TABLE IF EXISTS `catv_bankstaparsed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catv_bankstaparsed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `row` int(11) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `summ` float NOT NULL,
  `state` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catv_bankstaparsed`
--

LOCK TABLES `catv_bankstaparsed` WRITE;
/*!40000 ALTER TABLE `catv_bankstaparsed` DISABLE KEYS */;
/*!40000 ALTER TABLE `catv_bankstaparsed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catv_bankstaraw`
--

DROP TABLE IF EXISTS `catv_bankstaraw`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catv_bankstaraw` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `rawdata` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catv_bankstaraw`
--

LOCK TABLES `catv_bankstaraw` WRITE;
/*!40000 ALTER TABLE `catv_bankstaraw` DISABLE KEYS */;
/*!40000 ALTER TABLE `catv_bankstaraw` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catv_decoders`
--

DROP TABLE IF EXISTS `catv_decoders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catv_decoders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `userid` int(11) NOT NULL,
  `decoder` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catv_decoders`
--

LOCK TABLES `catv_decoders` WRITE;
/*!40000 ALTER TABLE `catv_decoders` DISABLE KEYS */;
/*!40000 ALTER TABLE `catv_decoders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catv_fees`
--

DROP TABLE IF EXISTS `catv_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catv_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `userid` int(11) NOT NULL,
  `summ` float NOT NULL,
  `balance` float DEFAULT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catv_fees`
--

LOCK TABLES `catv_fees` WRITE;
/*!40000 ALTER TABLE `catv_fees` DISABLE KEYS */;
/*!40000 ALTER TABLE `catv_fees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catv_payments`
--

DROP TABLE IF EXISTS `catv_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catv_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `userid` int(11) NOT NULL,
  `summ` float NOT NULL,
  `from_month` int(11) NOT NULL,
  `from_year` int(11) NOT NULL,
  `to_month` int(11) NOT NULL,
  `to_year` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `admin` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catv_payments`
--

LOCK TABLES `catv_payments` WRITE;
/*!40000 ALTER TABLE `catv_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `catv_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catv_paymentscorr`
--

DROP TABLE IF EXISTS `catv_paymentscorr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catv_paymentscorr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `userid` int(11) NOT NULL,
  `summ` float NOT NULL,
  `from_month` int(11) NOT NULL,
  `from_year` int(11) NOT NULL,
  `to_month` int(11) NOT NULL,
  `to_year` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `admin` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catv_paymentscorr`
--

LOCK TABLES `catv_paymentscorr` WRITE;
/*!40000 ALTER TABLE `catv_paymentscorr` DISABLE KEYS */;
/*!40000 ALTER TABLE `catv_paymentscorr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catv_signups`
--

DROP TABLE IF EXISTS `catv_signups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catv_signups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `userid` int(11) NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catv_signups`
--

LOCK TABLES `catv_signups` WRITE;
/*!40000 ALTER TABLE `catv_signups` DISABLE KEYS */;
/*!40000 ALTER TABLE `catv_signups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catv_tariffs`
--

DROP TABLE IF EXISTS `catv_tariffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catv_tariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` float NOT NULL,
  `chans` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catv_tariffs`
--

LOCK TABLES `catv_tariffs` WRITE;
/*!40000 ALTER TABLE `catv_tariffs` DISABLE KEYS */;
/*!40000 ALTER TABLE `catv_tariffs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catv_users`
--

DROP TABLE IF EXISTS `catv_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catv_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contract` varchar(255) DEFAULT NULL,
  `realname` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `build` varchar(15) DEFAULT NULL,
  `apt` varchar(15) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `tariff` int(11) DEFAULT NULL,
  `tariff_nm` int(11) DEFAULT NULL,
  `cash` float NOT NULL,
  `discount` float DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `decoder` int(11) DEFAULT NULL,
  `inetlink` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catv_users`
--

LOCK TABLES `catv_users` WRITE;
/*!40000 ALTER TABLE `catv_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `catv_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cemetery`
--

DROP TABLE IF EXISTS `cemetery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cemetery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cemetery`
--

LOCK TABLES `cemetery` WRITE;
/*!40000 ALTER TABLE `cemetery` DISABLE KEYS */;
/*!40000 ALTER TABLE `cemetery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cfitems`
--

DROP TABLE IF EXISTS `cfitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cfitems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `typeid` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cfitems`
--

LOCK TABLES `cfitems` WRITE;
/*!40000 ALTER TABLE `cfitems` DISABLE KEYS */;
/*!40000 ALTER TABLE `cfitems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cftypes`
--

DROP TABLE IF EXISTS `cftypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cftypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(15) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cftypes`
--

LOCK TABLES `cftypes` WRITE;
/*!40000 ALTER TABLE `cftypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `cftypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `city`
--

DROP TABLE IF EXISTS `city`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `city` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cityname` varchar(255) NOT NULL,
  `cityalias` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cityname` (`cityname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `city`
--

LOCK TABLES `city` WRITE;
/*!40000 ALTER TABLE `city` DISABLE KEYS */;
/*!40000 ALTER TABLE `city` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `condet`
--

DROP TABLE IF EXISTS `condet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `condet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) DEFAULT NULL,
  `seal` varchar(40) DEFAULT NULL,
  `length` varchar(40) DEFAULT NULL,
  `price` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `condet`
--

LOCK TABLES `condet` WRITE;
/*!40000 ALTER TABLE `condet` DISABLE KEYS */;
/*!40000 ALTER TABLE `condet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractdates`
--

DROP TABLE IF EXISTS `contractdates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractdates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contract` varchar(255) NOT NULL,
  `date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractdates`
--

LOCK TABLES `contractdates` WRITE;
/*!40000 ALTER TABLE `contractdates` DISABLE KEYS */;
/*!40000 ALTER TABLE `contractdates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts`
--

DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `contract` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `login_2` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts`
--

LOCK TABLES `contracts` WRITE;
/*!40000 ALTER TABLE `contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contrahens`
--

DROP TABLE IF EXISTS `contrahens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contrahens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bankacc` varchar(255) DEFAULT NULL,
  `bankname` varchar(255) DEFAULT NULL,
  `bankcode` varchar(255) DEFAULT NULL,
  `edrpo` varchar(255) DEFAULT NULL,
  `ipn` varchar(255) DEFAULT NULL,
  `licensenum` varchar(255) DEFAULT NULL,
  `juraddr` varchar(255) DEFAULT NULL,
  `phisaddr` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `contrname` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contrahens`
--

LOCK TABLES `contrahens` WRITE;
/*!40000 ALTER TABLE `contrahens` DISABLE KEYS */;
/*!40000 ALTER TABLE `contrahens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `corp_data`
--

DROP TABLE IF EXISTS `corp_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `corp_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `corpname` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `doctype` int(11) DEFAULT NULL,
  `docnum` varchar(255) DEFAULT NULL,
  `docdate` date DEFAULT NULL,
  `bankacc` varchar(255) DEFAULT NULL,
  `bankname` varchar(255) DEFAULT NULL,
  `bankmfo` varchar(255) DEFAULT NULL,
  `edrpou` varchar(255) DEFAULT NULL,
  `ndstaxnum` varchar(255) DEFAULT NULL,
  `inncode` varchar(255) DEFAULT NULL,
  `taxtype` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `corp_data`
--

LOCK TABLES `corp_data` WRITE;
/*!40000 ALTER TABLE `corp_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `corp_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `corp_persons`
--

DROP TABLE IF EXISTS `corp_persons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `corp_persons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `corpid` int(11) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `im` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `appointment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `corp_persons`
--

LOCK TABLES `corp_persons` WRITE;
/*!40000 ALTER TABLE `corp_persons` DISABLE KEYS */;
/*!40000 ALTER TABLE `corp_persons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `corp_taxtypes`
--

DROP TABLE IF EXISTS `corp_taxtypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `corp_taxtypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `corp_taxtypes`
--

LOCK TABLES `corp_taxtypes` WRITE;
/*!40000 ALTER TABLE `corp_taxtypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `corp_taxtypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `corp_users`
--

DROP TABLE IF EXISTS `corp_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `corp_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `corpid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `corp_users`
--

LOCK TABLES `corp_users` WRITE;
/*!40000 ALTER TABLE `corp_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `corp_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cpe`
--

DROP TABLE IF EXISTS `cpe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cpe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cpemodelid` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `desc` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `snmp` varchar(45) DEFAULT NULL,
  `netid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cpe`
--

LOCK TABLES `cpe` WRITE;
/*!40000 ALTER TABLE `cpe` DISABLE KEYS */;
/*!40000 ALTER TABLE `cpe` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cpetypes`
--

DROP TABLE IF EXISTS `cpetypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cpetypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cpemodel` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cpetypes`
--

LOCK TABLES `cpetypes` WRITE;
/*!40000 ALTER TABLE `cpetypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `cpetypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cudiscounts`
--

DROP TABLE IF EXISTS `cudiscounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cudiscounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `discount` double DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `days` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cudiscounts`
--

LOCK TABLES `cudiscounts` WRITE;
/*!40000 ALTER TABLE `cudiscounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `cudiscounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custmaps`
--

DROP TABLE IF EXISTS `custmaps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custmaps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custmaps`
--

LOCK TABLES `custmaps` WRITE;
/*!40000 ALTER TABLE `custmaps` DISABLE KEYS */;
/*!40000 ALTER TABLE `custmaps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custmapsitems`
--

DROP TABLE IF EXISTS `custmapsitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custmapsitems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mapid` int(11) DEFAULT NULL,
  `type` varchar(40) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mapid` (`mapid`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custmapsitems`
--

LOCK TABLES `custmapsitems` WRITE;
/*!40000 ALTER TABLE `custmapsitems` DISABLE KEYS */;
/*!40000 ALTER TABLE `custmapsitems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dealwithit`
--

DROP TABLE IF EXISTS `dealwithit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dealwithit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `login` varchar(45) NOT NULL,
  `action` varchar(45) NOT NULL,
  `param` varchar(45) DEFAULT NULL,
  `note` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dealwithit`
--

LOCK TABLES `dealwithit` WRITE;
/*!40000 ALTER TABLE `dealwithit` DISABLE KEYS */;
/*!40000 ALTER TABLE `dealwithit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dealwithithist`
--

DROP TABLE IF EXISTS `dealwithithist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dealwithithist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `originalid` int(11) NOT NULL,
  `mtime` datetime NOT NULL,
  `date` date NOT NULL,
  `datetimedone` datetime DEFAULT NULL,
  `login` varchar(45) NOT NULL,
  `action` varchar(45) NOT NULL,
  `param` varchar(45) DEFAULT NULL,
  `note` varchar(45) DEFAULT NULL,
  `admin` varchar(50) DEFAULT NULL,
  `done` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dealwithithist`
--

LOCK TABLES `dealwithithist` WRITE;
/*!40000 ALTER TABLE `dealwithithist` DISABLE KEYS */;
/*!40000 ALTER TABLE `dealwithithist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deathtime`
--

DROP TABLE IF EXISTS `deathtime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deathtime` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deathtime`
--

LOCK TABLES `deathtime` WRITE;
/*!40000 ALTER TABLE `deathtime` DISABLE KEYS */;
/*!40000 ALTER TABLE `deathtime` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dhcp`
--

DROP TABLE IF EXISTS `dhcp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dhcp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `netid` int(11) NOT NULL,
  `dhcpconfig` text,
  `confname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dhcp`
--

LOCK TABLES `dhcp` WRITE;
/*!40000 ALTER TABLE `dhcp` DISABLE KEYS */;
/*!40000 ALTER TABLE `dhcp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `directions`
--

DROP TABLE IF EXISTS `directions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `directions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rulenumber` int(11) NOT NULL,
  `rulename` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `rulenumber` (`rulenumber`),
  KEY `rulename` (`rulename`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `directions`
--

LOCK TABLES `directions` WRITE;
/*!40000 ALTER TABLE `directions` DISABLE KEYS */;
INSERT INTO `directions` VALUES (1,0,'Internet');
/*!40000 ALTER TABLE `directions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `districtdata`
--

DROP TABLE IF EXISTS `districtdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `districtdata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `districtid` int(11) NOT NULL,
  `cityid` int(11) DEFAULT NULL,
  `streetid` int(11) DEFAULT NULL,
  `buildid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `districtdata`
--

LOCK TABLES `districtdata` WRITE;
/*!40000 ALTER TABLE `districtdata` DISABLE KEYS */;
/*!40000 ALTER TABLE `districtdata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `districtnames`
--

DROP TABLE IF EXISTS `districtnames`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `districtnames` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `districtnames`
--

LOCK TABLES `districtnames` WRITE;
/*!40000 ALTER TABLE `districtnames` DISABLE KEYS */;
/*!40000 ALTER TABLE `districtnames` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docxdocuments`
--

DROP TABLE IF EXISTS `docxdocuments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docxdocuments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  `public` tinyint(4) DEFAULT NULL,
  `templateid` int(11) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `public` (`public`),
  KEY `path` (`path`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docxdocuments`
--

LOCK TABLES `docxdocuments` WRITE;
/*!40000 ALTER TABLE `docxdocuments` DISABLE KEYS */;
/*!40000 ALTER TABLE `docxdocuments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `docxtemplates`
--

DROP TABLE IF EXISTS `docxtemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docxtemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `public` tinyint(4) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `path` (`path`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `docxtemplates`
--

LOCK TABLES `docxtemplates` WRITE;
/*!40000 ALTER TABLE `docxtemplates` DISABLE KEYS */;
/*!40000 ALTER TABLE `docxtemplates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dshape_time`
--

DROP TABLE IF EXISTS `dshape_time`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dshape_time` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariff` varchar(255) NOT NULL,
  `threshold1` time NOT NULL,
  `threshold2` time NOT NULL,
  `speed` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dshape_time`
--

LOCK TABLES `dshape_time` WRITE;
/*!40000 ALTER TABLE `dshape_time` DISABLE KEYS */;
/*!40000 ALTER TABLE `dshape_time` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emails`
--

DROP TABLE IF EXISTS `emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emails`
--

LOCK TABLES `emails` WRITE;
/*!40000 ALTER TABLE `emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee`
--

DROP TABLE IF EXISTS `employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `appointment` varchar(255) NOT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `telegram` varchar(40) DEFAULT NULL,
  `admlogin` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  `tagid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee`
--

LOCK TABLES `employee` WRITE;
/*!40000 ALTER TABLE `employee` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exhorse`
--

DROP TABLE IF EXISTS `exhorse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exhorse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `u_totalusers` int(11) DEFAULT NULL,
  `u_activeusers` int(11) DEFAULT NULL,
  `u_inactiveusers` int(11) DEFAULT NULL,
  `u_frozenusers` int(11) DEFAULT NULL,
  `u_complextotal` int(11) DEFAULT NULL,
  `u_complexactive` int(11) DEFAULT NULL,
  `u_complexinactive` int(11) DEFAULT NULL,
  `u_signups` int(11) DEFAULT NULL,
  `u_citysignups` text,
  `f_totalmoney` double DEFAULT NULL,
  `f_paymentscount` int(11) DEFAULT NULL,
  `f_cashmoney` double DEFAULT NULL,
  `f_cashcount` int(11) DEFAULT NULL,
  `f_arpu` double DEFAULT NULL,
  `f_arpau` double DEFAULT NULL,
  `c_totalusers` int(11) DEFAULT NULL,
  `c_activeusers` int(11) DEFAULT NULL,
  `c_inactiveusers` int(11) DEFAULT NULL,
  `c_illegal` int(11) DEFAULT NULL,
  `c_complex` int(11) DEFAULT NULL,
  `c_social` int(11) DEFAULT NULL,
  `c_totalmoney` double DEFAULT NULL,
  `c_paymentscount` int(11) DEFAULT NULL,
  `c_arpu` double DEFAULT NULL,
  `c_arpau` double DEFAULT NULL,
  `c_totaldebt` double DEFAULT NULL,
  `c_signups` int(11) DEFAULT NULL,
  `a_totalcalls` int(11) DEFAULT NULL,
  `a_totalanswered` int(11) DEFAULT NULL,
  `a_totalcallsduration` int(11) DEFAULT NULL,
  `a_averagecallduration` int(11) DEFAULT NULL,
  `e_switches` int(11) DEFAULT NULL,
  `e_pononu` int(11) DEFAULT NULL,
  `e_docsis` int(11) DEFAULT NULL,
  `a_recallunsuccess` double DEFAULT NULL,
  `a_recalltrytime` int(11) DEFAULT NULL,
  `e_deadswintervals` int(11) DEFAULT NULL,
  `t_sigreq` int(11) DEFAULT NULL,
  `t_tickets` int(11) DEFAULT NULL,
  `t_tasks` int(11) DEFAULT NULL,
  `t_capabtotal` int(11) DEFAULT NULL,
  `t_capabundone` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exhorse`
--

LOCK TABLES `exhorse` WRITE;
/*!40000 ALTER TABLE `exhorse` DISABLE KEYS */;
/*!40000 ALTER TABLE `exhorse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `friendship`
--

DROP TABLE IF EXISTS `friendship`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `friendship` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friend` varchar(255) NOT NULL,
  `parent` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `friend` (`friend`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `friendship`
--

LOCK TABLES `friendship` WRITE;
/*!40000 ALTER TABLE `friendship` DISABLE KEYS */;
/*!40000 ALTER TABLE `friendship` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frozen_charge_days`
--

DROP TABLE IF EXISTS `frozen_charge_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frozen_charge_days` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `freeze_days_amount` smallint(3) NOT NULL DEFAULT '0',
  `freeze_days_used` smallint(3) NOT NULL DEFAULT '0',
  `work_days_restore` smallint(3) NOT NULL DEFAULT '0',
  `days_worked` smallint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frozen_charge_days`
--

LOCK TABLES `frozen_charge_days` WRITE;
/*!40000 ALTER TABLE `frozen_charge_days` DISABLE KEYS */;
/*!40000 ALTER TABLE `frozen_charge_days` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `genocide`
--

DROP TABLE IF EXISTS `genocide`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `genocide` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariff` varchar(255) NOT NULL,
  `speed` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genocide`
--

LOCK TABLES `genocide` WRITE;
/*!40000 ALTER TABLE `genocide` DISABLE KEYS */;
/*!40000 ALTER TABLE `genocide` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `info`
--

DROP TABLE IF EXISTS `info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `info` (
  `version` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `info`
--

LOCK TABLES `info` WRITE;
/*!40000 ALTER TABLE `info` DISABLE KEYS */;
INSERT INTO `info` VALUES (1);
/*!40000 ALTER TABLE `info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `jobid` int(11) NOT NULL,
  `workerid` int(11) NOT NULL,
  `login` varchar(45) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobtypes`
--

DROP TABLE IF EXISTS `jobtypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobtypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jobname` varchar(255) NOT NULL,
  `jobcolor` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jobcolor` (`jobcolor`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobtypes`
--

LOCK TABLES `jobtypes` WRITE;
/*!40000 ALTER TABLE `jobtypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobtypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ldap_groups`
--

DROP TABLE IF EXISTS `ldap_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ldap_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ldap_groups`
--

LOCK TABLES `ldap_groups` WRITE;
/*!40000 ALTER TABLE `ldap_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `ldap_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ldap_queue`
--

DROP TABLE IF EXISTS `ldap_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ldap_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task` varchar(255) NOT NULL,
  `param` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ldap_queue`
--

LOCK TABLES `ldap_queue` WRITE;
/*!40000 ALTER TABLE `ldap_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `ldap_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ldap_users`
--

DROP TABLE IF EXISTS `ldap_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ldap_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `groups` text,
  `changed` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ldap_users`
--

LOCK TABLES `ldap_users` WRITE;
/*!40000 ALTER TABLE `ldap_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `ldap_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lousytariffs`
--

DROP TABLE IF EXISTS `lousytariffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lousytariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariff` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lousytariffs`
--

LOCK TABLES `lousytariffs` WRITE;
/*!40000 ALTER TABLE `lousytariffs` DISABLE KEYS */;
/*!40000 ALTER TABLE `lousytariffs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `login` varchar(40) DEFAULT '',
  `id` bigint(20) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `lastSendTime` int(11) DEFAULT NULL,
  `creationTime` int(11) DEFAULT NULL,
  `showTime` int(11) DEFAULT NULL,
  `stgRepeat` int(11) DEFAULT NULL,
  `repeatPeriod` int(11) DEFAULT NULL,
  `text` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mg_history`
--

DROP TABLE IF EXISTS `mg_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mg_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `tariffid` int(11) NOT NULL,
  `actdate` datetime NOT NULL,
  `freeperiod` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mg_history`
--

LOCK TABLES `mg_history` WRITE;
/*!40000 ALTER TABLE `mg_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `mg_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mg_queue`
--

DROP TABLE IF EXISTS `mg_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mg_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `action` varchar(45) NOT NULL,
  `tariffid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mg_queue`
--

LOCK TABLES `mg_queue` WRITE;
/*!40000 ALTER TABLE `mg_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `mg_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mg_subscribers`
--

DROP TABLE IF EXISTS `mg_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mg_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `tariffid` int(11) NOT NULL,
  `actdate` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `primary` tinyint(1) NOT NULL DEFAULT '0',
  `freeperiod` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mg_subscribers`
--

LOCK TABLES `mg_subscribers` WRITE;
/*!40000 ALTER TABLE `mg_subscribers` DISABLE KEYS */;
/*!40000 ALTER TABLE `mg_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mg_tariffs`
--

DROP TABLE IF EXISTS `mg_tariffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mg_tariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `fee` double DEFAULT NULL,
  `serviceid` varchar(45) DEFAULT NULL,
  `primary` tinyint(1) NOT NULL DEFAULT '0',
  `freeperiod` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mg_tariffs`
--

LOCK TABLES `mg_tariffs` WRITE;
/*!40000 ALTER TABLE `mg_tariffs` DISABLE KEYS */;
/*!40000 ALTER TABLE `mg_tariffs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mobileext`
--

DROP TABLE IF EXISTS `mobileext`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mobileext` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(64) NOT NULL,
  `mobile` varchar(64) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`,`mobile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mobileext`
--

LOCK TABLES `mobileext` WRITE;
/*!40000 ALTER TABLE `mobileext` DISABLE KEYS */;
/*!40000 ALTER TABLE `mobileext` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modem_templates`
--

DROP TABLE IF EXISTS `modem_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modem_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `body` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modem_templates`
--

LOCK TABLES `modem_templates` WRITE;
/*!40000 ALTER TABLE `modem_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `modem_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modems`
--

DROP TABLE IF EXISTS `modems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `maclan` varchar(255) NOT NULL,
  `macusb` varchar(255) NOT NULL,
  `date` date DEFAULT NULL,
  `ip` varchar(25) NOT NULL,
  `conftemplate` varchar(20) NOT NULL,
  `userbind` varchar(100) DEFAULT NULL,
  `nic` varchar(100) NOT NULL,
  `note` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modems`
--

LOCK TABLES `modems` WRITE;
/*!40000 ALTER TABLE `modems` DISABLE KEYS */;
/*!40000 ALTER TABLE `modems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mtnasifaces`
--

DROP TABLE IF EXISTS `mtnasifaces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mtnasifaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nasid` int(11) NOT NULL,
  `iface` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mtnasifaces`
--

LOCK TABLES `mtnasifaces` WRITE;
/*!40000 ALTER TABLE `mtnasifaces` DISABLE KEYS */;
/*!40000 ALTER TABLE `mtnasifaces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nas`
--

DROP TABLE IF EXISTS `nas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `netid` int(11) NOT NULL,
  `nasip` varchar(255) NOT NULL,
  `nasname` varchar(255) NOT NULL,
  `nastype` varchar(45) DEFAULT NULL,
  `bandw` varchar(255) DEFAULT NULL,
  `options` text,
  PRIMARY KEY (`id`),
  KEY `netid` (`netid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nas`
--

LOCK TABLES `nas` WRITE;
/*!40000 ALTER TABLE `nas` DISABLE KEYS */;
/*!40000 ALTER TABLE `nas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nastemplates`
--

DROP TABLE IF EXISTS `nastemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nastemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nasid` int(11) NOT NULL,
  `template` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nastemplates`
--

LOCK TABLES `nastemplates` WRITE;
/*!40000 ALTER TABLE `nastemplates` DISABLE KEYS */;
/*!40000 ALTER TABLE `nastemplates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `netextips`
--

DROP TABLE IF EXISTS `netextips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `netextips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poolid` int(11) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `nas` varchar(255) DEFAULT NULL,
  `iface` varchar(40) DEFAULT NULL,
  `mac` varchar(40) DEFAULT NULL,
  `switchid` int(11) DEFAULT NULL,
  `port` varchar(40) DEFAULT NULL,
  `vlan` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `netextips`
--

LOCK TABLES `netextips` WRITE;
/*!40000 ALTER TABLE `netextips` DISABLE KEYS */;
/*!40000 ALTER TABLE `netextips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `netextpools`
--

DROP TABLE IF EXISTS `netextpools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `netextpools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `netid` int(11) NOT NULL,
  `pool` varchar(255) NOT NULL,
  `netmask` varchar(255) NOT NULL,
  `gw` varchar(255) DEFAULT NULL,
  `clientip` varchar(255) DEFAULT NULL,
  `broadcast` varchar(255) DEFAULT NULL,
  `vlan` varchar(255) DEFAULT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `netextpools`
--

LOCK TABLES `netextpools` WRITE;
/*!40000 ALTER TABLE `netextpools` DISABLE KEYS */;
/*!40000 ALTER TABLE `netextpools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nethosts`
--

DROP TABLE IF EXISTS `nethosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nethosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `netid` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `mac` varchar(45) DEFAULT NULL,
  `option` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `net-ip` (`netid`,`ip`),
  KEY `netid` (`netid`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nethosts`
--

LOCK TABLES `nethosts` WRITE;
/*!40000 ALTER TABLE `nethosts` DISABLE KEYS */;
/*!40000 ALTER TABLE `nethosts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `networks`
--

DROP TABLE IF EXISTS `networks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `networks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startip` varchar(45) NOT NULL,
  `endip` varchar(45) NOT NULL,
  `desc` varchar(45) NOT NULL,
  `nettype` varchar(20) NOT NULL,
  `use_radius` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `networks`
--

LOCK TABLES `networks` WRITE;
/*!40000 ALTER TABLE `networks` DISABLE KEYS */;
/*!40000 ALTER TABLE `networks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `note` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `om_queue`
--

DROP TABLE IF EXISTS `om_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `om_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerid` bigint(20) NOT NULL,
  `tariffid` int(11) DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `om_queue`
--

LOCK TABLES `om_queue` WRITE;
/*!40000 ALTER TABLE `om_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `om_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `om_suspend`
--

DROP TABLE IF EXISTS `om_suspend`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `om_suspend` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `om_suspend`
--

LOCK TABLES `om_suspend` WRITE;
/*!40000 ALTER TABLE `om_suspend` DISABLE KEYS */;
/*!40000 ALTER TABLE `om_suspend` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `om_tariffs`
--

DROP TABLE IF EXISTS `om_tariffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `om_tariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariffid` int(11) NOT NULL,
  `tariffname` varchar(255) NOT NULL,
  `type` varchar(64) NOT NULL,
  `fee` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `om_tariffs`
--

LOCK TABLES `om_tariffs` WRITE;
/*!40000 ALTER TABLE `om_tariffs` DISABLE KEYS */;
/*!40000 ALTER TABLE `om_tariffs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `om_users`
--

DROP TABLE IF EXISTS `om_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `om_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `customerid` bigint(20) NOT NULL,
  `basetariffid` int(11) DEFAULT NULL,
  `bundletariffs` varchar(255) DEFAULT NULL,
  `active` int(11) DEFAULT NULL,
  `actdate` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `om_users`
--

LOCK TABLES `om_users` WRITE;
/*!40000 ALTER TABLE `om_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `om_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `passportdata`
--

DROP TABLE IF EXISTS `passportdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `passportdata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `passportnum` varchar(255) DEFAULT NULL,
  `passportdate` date DEFAULT NULL,
  `passportwho` varchar(255) DEFAULT NULL,
  `pcity` varchar(255) DEFAULT NULL,
  `pstreet` varchar(255) DEFAULT NULL,
  `pbuild` varchar(10) DEFAULT NULL,
  `papt` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `passportdata`
--

LOCK TABLES `passportdata` WRITE;
/*!40000 ALTER TABLE `passportdata` DISABLE KEYS */;
/*!40000 ALTER TABLE `passportdata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `balance` varchar(45) NOT NULL,
  `summ` varchar(45) NOT NULL,
  `cashtypeid` int(11) NOT NULL,
  `note` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paymentscorr`
--

DROP TABLE IF EXISTS `paymentscorr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paymentscorr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `balance` varchar(45) NOT NULL,
  `summ` varchar(45) NOT NULL,
  `cashtypeid` int(11) NOT NULL,
  `note` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paymentscorr`
--

LOCK TABLES `paymentscorr` WRITE;
/*!40000 ALTER TABLE `paymentscorr` DISABLE KEYS */;
/*!40000 ALTER TABLE `paymentscorr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phones`
--

DROP TABLE IF EXISTS `phones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(45) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `phone` (`phone`),
  KEY `mobile` (`mobile`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phones`
--

LOCK TABLES `phones` WRITE;
/*!40000 ALTER TABLE `phones` DISABLE KEYS */;
/*!40000 ALTER TABLE `phones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `photostorage`
--

DROP TABLE IF EXISTS `photostorage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `photostorage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scope` varchar(255) NOT NULL,
  `item` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(40) NOT NULL,
  `filename` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scope` (`scope`),
  KEY `item` (`item`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `photostorage`
--

LOCK TABLES `photostorage` WRITE;
/*!40000 ALTER TABLE `photostorage` DISABLE KEYS */;
/*!40000 ALTER TABLE `photostorage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `policedog`
--

DROP TABLE IF EXISTS `policedog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `policedog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `mac` varchar(40) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`mac`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `policedog`
--

LOCK TABLES `policedog` WRITE;
/*!40000 ALTER TABLE `policedog` DISABLE KEYS */;
/*!40000 ALTER TABLE `policedog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `policedogalerts`
--

DROP TABLE IF EXISTS `policedogalerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `policedogalerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `mac` varchar(40) NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`mac`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `policedogalerts`
--

LOCK TABLES `policedogalerts` WRITE;
/*!40000 ALTER TABLE `policedogalerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `policedogalerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `polls`
--

DROP TABLE IF EXISTS `polls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `polls` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` datetime DEFAULT '0000-00-00 00:00:00',
  `params` text NOT NULL,
  `admin` varchar(255) NOT NULL DEFAULT '',
  `voting` varchar(255) NOT NULL DEFAULT 'Users',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `polls`
--

LOCK TABLES `polls` WRITE;
/*!40000 ALTER TABLE `polls` DISABLE KEYS */;
/*!40000 ALTER TABLE `polls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `polls_options`
--

DROP TABLE IF EXISTS `polls_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `polls_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `poll_id` (`id`,`poll_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `polls_options`
--

LOCK TABLES `polls_options` WRITE;
/*!40000 ALTER TABLE `polls_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `polls_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `polls_votes`
--

DROP TABLE IF EXISTS `polls_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `polls_votes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `option_id` int(11) NOT NULL DEFAULT '0',
  `poll_id` int(11) NOT NULL DEFAULT '0',
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_poll` (`poll_id`,`login`) USING BTREE,
  UNIQUE KEY `login_poll_option` (`option_id`,`poll_id`,`login`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `polls_votes`
--

LOCK TABLES `polls_votes` WRITE;
/*!40000 ALTER TABLE `polls_votes` DISABLE KEYS */;
/*!40000 ALTER TABLE `polls_votes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pononu`
--

DROP TABLE IF EXISTS `pononu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pononu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `onumodelid` int(11) DEFAULT NULL,
  `oltid` int(11) DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `mac` varchar(20) DEFAULT NULL,
  `serial` varchar(255) DEFAULT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pononu`
--

LOCK TABLES `pononu` WRITE;
/*!40000 ALTER TABLE `pononu` DISABLE KEYS */;
/*!40000 ALTER TABLE `pononu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `print_card`
--

DROP TABLE IF EXISTS `print_card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `print_card` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `field` varchar(255) NOT NULL,
  `color` varchar(255) DEFAULT '',
  `font_size` int(11) DEFAULT NULL,
  `top` int(11) DEFAULT NULL,
  `left` int(11) DEFAULT NULL,
  `text` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `print_card`
--

LOCK TABLES `print_card` WRITE;
/*!40000 ALTER TABLE `print_card` DISABLE KEYS */;
INSERT INTO `print_card` VALUES (1,'Serial number','number','0.0.0',12,80,130,'ÐÐ¾Ð¼ÐµÑ€ â„– {number}'),(2,'Serial part','serial','0.0.0',12,80,110,'Ð¡ÐµÑ€Ð¸Ñ {serial}'),(3,'Price','rating','139.0.139',16,120,90,'ÐÐ¾Ð¼Ð¸Ð½Ð°Ð» {sum}Ð³Ñ€Ð½. '),(4,'Phone','phone','0.0.0',8,160,3,'+38(096)xxx-xx-xx, +38(096)xxx-xx-xx, +38(096)xxx-xx-xx'),(5,'Site','site','0.0.0',10,15,5,'Ð¡Ð°Ð¹Ñ‚: xxx.xxx.ua');
/*!40000 ALTER TABLE `print_card` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `punchscripts`
--

DROP TABLE IF EXISTS `punchscripts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `punchscripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alias` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text,
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `punchscripts`
--

LOCK TABLES `punchscripts` WRITE;
/*!40000 ALTER TABLE `punchscripts` DISABLE KEYS */;
/*!40000 ALTER TABLE `punchscripts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `radattr`
--

DROP TABLE IF EXISTS `radattr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `radattr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `attr` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `radattr`
--

LOCK TABLES `radattr` WRITE;
/*!40000 ALTER TABLE `radattr` DISABLE KEYS */;
/*!40000 ALTER TABLE `radattr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `realname`
--

DROP TABLE IF EXISTS `realname`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `realname` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(45) DEFAULT NULL,
  `realname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `realname` (`realname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `realname`
--

LOCK TABLES `realname` WRITE;
/*!40000 ALTER TABLE `realname` DISABLE KEYS */;
/*!40000 ALTER TABLE `realname` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_jobprices`
--

DROP TABLE IF EXISTS `salary_jobprices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_jobprices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jobtypeid` int(11) NOT NULL,
  `price` double NOT NULL,
  `unit` varchar(255) NOT NULL,
  `time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_jobprices`
--

LOCK TABLES `salary_jobprices` WRITE;
/*!40000 ALTER TABLE `salary_jobprices` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_jobprices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_jobs`
--

DROP TABLE IF EXISTS `salary_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `taskid` int(11) DEFAULT NULL,
  `employeeid` int(11) NOT NULL,
  `jobtypeid` int(11) NOT NULL,
  `factor` double DEFAULT NULL,
  `overprice` double DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `taskid` (`taskid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_jobs`
--

LOCK TABLES `salary_jobs` WRITE;
/*!40000 ALTER TABLE `salary_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_paid`
--

DROP TABLE IF EXISTS `salary_paid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_paid` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jobid` int(11) NOT NULL,
  `employeeid` int(11) NOT NULL,
  `paid` double DEFAULT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_paid`
--

LOCK TABLES `salary_paid` WRITE;
/*!40000 ALTER TABLE `salary_paid` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_paid` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_timesheets`
--

DROP TABLE IF EXISTS `salary_timesheets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_timesheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `employeeid` int(11) NOT NULL,
  `hours` int(11) NOT NULL DEFAULT '0',
  `holiday` tinyint(1) NOT NULL DEFAULT '0',
  `hospital` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_timesheets`
--

LOCK TABLES `salary_timesheets` WRITE;
/*!40000 ALTER TABLE `salary_timesheets` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_timesheets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_wages`
--

DROP TABLE IF EXISTS `salary_wages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_wages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employeeid` int(11) NOT NULL,
  `wage` double NOT NULL,
  `bounty` double NOT NULL,
  `worktime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_wages`
--

LOCK TABLES `salary_wages` WRITE;
/*!40000 ALTER TABLE `salary_wages` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_wages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `selling`
--

DROP TABLE IF EXISTS `selling`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `selling` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `count_cards` int(11) DEFAULT NULL,
  `comment` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `selling`
--

LOCK TABLES `selling` WRITE;
/*!40000 ALTER TABLE `selling` DISABLE KEYS */;
/*!40000 ALTER TABLE `selling` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `netid` int(11) NOT NULL,
  `desc` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `netid` (`netid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servtariff`
--

DROP TABLE IF EXISTS `servtariff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servtariff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serviceid` int(11) NOT NULL,
  `tariffs` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servtariff`
--

LOCK TABLES `servtariff` WRITE;
/*!40000 ALTER TABLE `servtariff` DISABLE KEYS */;
/*!40000 ALTER TABLE `servtariff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signup_prices_tariffs`
--

DROP TABLE IF EXISTS `signup_prices_tariffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `signup_prices_tariffs` (
  `tariff` varchar(40) NOT NULL,
  `price` double NOT NULL,
  PRIMARY KEY (`tariff`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signup_prices_tariffs`
--

LOCK TABLES `signup_prices_tariffs` WRITE;
/*!40000 ALTER TABLE `signup_prices_tariffs` DISABLE KEYS */;
/*!40000 ALTER TABLE `signup_prices_tariffs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signup_prices_users`
--

DROP TABLE IF EXISTS `signup_prices_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `signup_prices_users` (
  `login` varchar(50) NOT NULL,
  `price` double NOT NULL,
  PRIMARY KEY (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signup_prices_users`
--

LOCK TABLES `signup_prices_users` WRITE;
/*!40000 ALTER TABLE `signup_prices_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `signup_prices_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sigreq`
--

DROP TABLE IF EXISTS `sigreq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sigreq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `state` tinyint(4) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `street` varchar(255) NOT NULL,
  `build` varchar(40) NOT NULL,
  `apt` varchar(40) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `service` varchar(255) NOT NULL,
  `notes` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sigreq`
--

LOCK TABLES `sigreq` WRITE;
/*!40000 ALTER TABLE `sigreq` DISABLE KEYS */;
/*!40000 ALTER TABLE `sigreq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sigreqconf`
--

DROP TABLE IF EXISTS `sigreqconf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sigreqconf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  KEY `key` (`key`),
  FULLTEXT KEY `value` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sigreqconf`
--

LOCK TABLES `sigreqconf` WRITE;
/*!40000 ALTER TABLE `sigreqconf` DISABLE KEYS */;
/*!40000 ALTER TABLE `sigreqconf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_history`
--

DROP TABLE IF EXISTS `sms_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `srvmsgself_id` varchar(255) NOT NULL,
  `srvmsgpack_id` varchar(255) NOT NULL,
  `date_send` datetime NOT NULL,
  `date_statuschk` datetime NOT NULL,
  `delivered` tinyint(1) unsigned DEFAULT '0',
  `no_statuschk` tinyint(1) unsigned DEFAULT '0',
  `send_status` varchar(255) NOT NULL DEFAULT '',
  `msg_text` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `login` (`login`) USING BTREE,
  KEY `phone` (`phone`) USING BTREE,
  KEY `date_send` (`date_send`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_history`
--

LOCK TABLES `sms_history` WRITE;
/*!40000 ALTER TABLE `sms_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smz_excl`
--

DROP TABLE IF EXISTS `smz_excl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smz_excl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smz_excl`
--

LOCK TABLES `smz_excl` WRITE;
/*!40000 ALTER TABLE `smz_excl` DISABLE KEYS */;
/*!40000 ALTER TABLE `smz_excl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smz_filters`
--

DROP TABLE IF EXISTS `smz_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smz_filters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `filters` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smz_filters`
--

LOCK TABLES `smz_filters` WRITE;
/*!40000 ALTER TABLE `smz_filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `smz_filters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smz_lists`
--

DROP TABLE IF EXISTS `smz_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smz_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smz_lists`
--

LOCK TABLES `smz_lists` WRITE;
/*!40000 ALTER TABLE `smz_lists` DISABLE KEYS */;
/*!40000 ALTER TABLE `smz_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smz_nums`
--

DROP TABLE IF EXISTS `smz_nums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smz_nums` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numid` int(11) NOT NULL,
  `mobile` varchar(40) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smz_nums`
--

LOCK TABLES `smz_nums` WRITE;
/*!40000 ALTER TABLE `smz_nums` DISABLE KEYS */;
/*!40000 ALTER TABLE `smz_nums` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smz_templates`
--

DROP TABLE IF EXISTS `smz_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smz_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `text` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smz_templates`
--

LOCK TABLES `smz_templates` WRITE;
/*!40000 ALTER TABLE `smz_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `smz_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `speeds`
--

DROP TABLE IF EXISTS `speeds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `speeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariff` varchar(45) DEFAULT NULL,
  `speeddown` varchar(45) DEFAULT NULL,
  `speedup` varchar(45) DEFAULT NULL,
  `burstdownload` varchar(45) DEFAULT NULL,
  `burstupload` varchar(45) DEFAULT NULL,
  `bursttimedownload` varchar(45) DEFAULT NULL,
  `burstimetupload` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tariff` (`tariff`),
  KEY `speeddown` (`speeddown`),
  KEY `speedup` (`speedup`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `speeds`
--

LOCK TABLES `speeds` WRITE;
/*!40000 ALTER TABLE `speeds` DISABLE KEYS */;
/*!40000 ALTER TABLE `speeds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stat`
--

DROP TABLE IF EXISTS `stat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stat` (
  `login` varchar(50) DEFAULT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `year` smallint(6) DEFAULT NULL,
  `U0` bigint(20) DEFAULT NULL,
  `D0` bigint(20) DEFAULT NULL,
  `U1` bigint(20) DEFAULT NULL,
  `D1` bigint(20) DEFAULT NULL,
  `U2` bigint(20) DEFAULT NULL,
  `D2` bigint(20) DEFAULT NULL,
  `U3` bigint(20) DEFAULT NULL,
  `D3` bigint(20) DEFAULT NULL,
  `U4` bigint(20) DEFAULT NULL,
  `D4` bigint(20) DEFAULT NULL,
  `U5` bigint(20) DEFAULT NULL,
  `D5` bigint(20) DEFAULT NULL,
  `U6` bigint(20) DEFAULT NULL,
  `D6` bigint(20) DEFAULT NULL,
  `U7` bigint(20) DEFAULT NULL,
  `D7` bigint(20) DEFAULT NULL,
  `U8` bigint(20) DEFAULT NULL,
  `D8` bigint(20) DEFAULT NULL,
  `U9` bigint(20) DEFAULT NULL,
  `D9` bigint(20) DEFAULT NULL,
  `cash` double DEFAULT NULL,
  KEY `login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stat`
--

LOCK TABLES `stat` WRITE;
/*!40000 ALTER TABLE `stat` DISABLE KEYS */;
/*!40000 ALTER TABLE `stat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stickynotes`
--

DROP TABLE IF EXISTS `stickynotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stickynotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` varchar(255) NOT NULL,
  `createdate` datetime NOT NULL,
  `reminddate` date DEFAULT NULL,
  `remindtime` time DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `text` text,
  PRIMARY KEY (`id`),
  KEY `owner` (`owner`),
  KEY `reminddate` (`reminddate`),
  KEY `active` (`active`),
  KEY `remindtime` (`remindtime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stickynotes`
--

LOCK TABLES `stickynotes` WRITE;
/*!40000 ALTER TABLE `stickynotes` DISABLE KEYS */;
/*!40000 ALTER TABLE `stickynotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `street`
--

DROP TABLE IF EXISTS `street`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `street` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cityid` int(11) NOT NULL,
  `streetname` varchar(255) NOT NULL,
  `streetalias` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cityid` (`cityid`),
  KEY `streetname` (`streetname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `street`
--

LOCK TABLES `street` WRITE;
/*!40000 ALTER TABLE `street` DISABLE KEYS */;
/*!40000 ALTER TABLE `street` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `switch_login`
--

DROP TABLE IF EXISTS `switch_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `switch_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `swid` int(5) DEFAULT NULL,
  `swlogin` varchar(50) DEFAULT NULL,
  `swpass` varchar(50) DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `community` varchar(50) DEFAULT NULL,
  `enable` varchar(3) DEFAULT NULL,
  `snmptemplate` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `switch_login`
--

LOCK TABLES `switch_login` WRITE;
/*!40000 ALTER TABLE `switch_login` DISABLE KEYS */;
/*!40000 ALTER TABLE `switch_login` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `switchdeadlog`
--

DROP TABLE IF EXISTS `switchdeadlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `switchdeadlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `timestamp` int(11) NOT NULL,
  `swdead` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `switchdeadlog`
--

LOCK TABLES `switchdeadlog` WRITE;
/*!40000 ALTER TABLE `switchdeadlog` DISABLE KEYS */;
/*!40000 ALTER TABLE `switchdeadlog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `switches`
--

DROP TABLE IF EXISTS `switches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `switches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modelid` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `desc` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `snmp` varchar(45) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  `parentid` int(11) DEFAULT NULL,
  `swid` varchar(32) DEFAULT NULL,
  `snmpwrite` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parentid` (`parentid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `switches`
--

LOCK TABLES `switches` WRITE;
/*!40000 ALTER TABLE `switches` DISABLE KEYS */;
/*!40000 ALTER TABLE `switches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `switchmodels`
--

DROP TABLE IF EXISTS `switchmodels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `switchmodels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modelname` varchar(255) NOT NULL,
  `ports` int(11) DEFAULT NULL,
  `snmptemplate` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `switchmodels`
--

LOCK TABLES `switchmodels` WRITE;
/*!40000 ALTER TABLE `switchmodels` DISABLE KEYS */;
/*!40000 ALTER TABLE `switchmodels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `switchportassign`
--

DROP TABLE IF EXISTS `switchportassign`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `switchportassign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `switchid` int(11) NOT NULL,
  `port` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `switchportassign`
--

LOCK TABLES `switchportassign` WRITE;
/*!40000 ALTER TABLE `switchportassign` DISABLE KEYS */;
/*!40000 ALTER TABLE `switchportassign` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tagid` int(11) NOT NULL,
  `login` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tagtypes`
--

DROP TABLE IF EXISTS `tagtypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tagtypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tagname` varchar(255) NOT NULL,
  `tagcolor` varchar(15) NOT NULL,
  `tagsize` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tagtypes`
--

LOCK TABLES `tagtypes` WRITE;
/*!40000 ALTER TABLE `tagtypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `tagtypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tariffs`
--

DROP TABLE IF EXISTS `tariffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tariffs` (
  `name` varchar(40) NOT NULL DEFAULT '',
  `PriceDayA0` double DEFAULT '0',
  `PriceDayB0` double DEFAULT '0',
  `PriceNightA0` double DEFAULT '0',
  `PriceNightB0` double DEFAULT '0',
  `Threshold0` int(11) DEFAULT '0',
  `Time0` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount0` int(11) DEFAULT '0',
  `SinglePrice0` int(11) DEFAULT '0',
  `PriceDayA1` double DEFAULT '0',
  `PriceDayB1` double DEFAULT '0',
  `PriceNightA1` double DEFAULT '0',
  `PriceNightB1` double DEFAULT '0',
  `Threshold1` int(11) DEFAULT '0',
  `Time1` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount1` int(11) DEFAULT '0',
  `SinglePrice1` int(11) DEFAULT '0',
  `PriceDayA2` double DEFAULT '0',
  `PriceDayB2` double DEFAULT '0',
  `PriceNightA2` double DEFAULT '0',
  `PriceNightB2` double DEFAULT '0',
  `Threshold2` int(11) DEFAULT '0',
  `Time2` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount2` int(11) DEFAULT '0',
  `SinglePrice2` int(11) DEFAULT '0',
  `PriceDayA3` double DEFAULT '0',
  `PriceDayB3` double DEFAULT '0',
  `PriceNightA3` double DEFAULT '0',
  `PriceNightB3` double DEFAULT '0',
  `Threshold3` int(11) DEFAULT '0',
  `Time3` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount3` int(11) DEFAULT '0',
  `SinglePrice3` int(11) DEFAULT '0',
  `PriceDayA4` double DEFAULT '0',
  `PriceDayB4` double DEFAULT '0',
  `PriceNightA4` double DEFAULT '0',
  `PriceNightB4` double DEFAULT '0',
  `Threshold4` int(11) DEFAULT '0',
  `Time4` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount4` int(11) DEFAULT '0',
  `SinglePrice4` int(11) DEFAULT '0',
  `PriceDayA5` double DEFAULT '0',
  `PriceDayB5` double DEFAULT '0',
  `PriceNightA5` double DEFAULT '0',
  `PriceNightB5` double DEFAULT '0',
  `Threshold5` int(11) DEFAULT '0',
  `Time5` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount5` int(11) DEFAULT '0',
  `SinglePrice5` int(11) DEFAULT '0',
  `PriceDayA6` double DEFAULT '0',
  `PriceDayB6` double DEFAULT '0',
  `PriceNightA6` double DEFAULT '0',
  `PriceNightB6` double DEFAULT '0',
  `Threshold6` int(11) DEFAULT '0',
  `Time6` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount6` int(11) DEFAULT '0',
  `SinglePrice6` int(11) DEFAULT '0',
  `PriceDayA7` double DEFAULT '0',
  `PriceDayB7` double DEFAULT '0',
  `PriceNightA7` double DEFAULT '0',
  `PriceNightB7` double DEFAULT '0',
  `Threshold7` int(11) DEFAULT '0',
  `Time7` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount7` int(11) DEFAULT '0',
  `SinglePrice7` int(11) DEFAULT '0',
  `PriceDayA8` double DEFAULT '0',
  `PriceDayB8` double DEFAULT '0',
  `PriceNightA8` double DEFAULT '0',
  `PriceNightB8` double DEFAULT '0',
  `Threshold8` int(11) DEFAULT '0',
  `Time8` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount8` int(11) DEFAULT '0',
  `SinglePrice8` int(11) DEFAULT '0',
  `PriceDayA9` double DEFAULT '0',
  `PriceDayB9` double DEFAULT '0',
  `PriceNightA9` double DEFAULT '0',
  `PriceNightB9` double DEFAULT '0',
  `Threshold9` int(11) DEFAULT '0',
  `Time9` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount9` int(11) DEFAULT '0',
  `SinglePrice9` int(11) DEFAULT '0',
  `PassiveCost` double DEFAULT '0',
  `Fee` double DEFAULT '0',
  `Free` double DEFAULT '0',
  `TraffType` varchar(10) DEFAULT '',
  `period` varchar(32) NOT NULL DEFAULT 'month',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tariffs`
--

LOCK TABLES `tariffs` WRITE;
/*!40000 ALTER TABLE `tariffs` DISABLE KEYS */;
/*!40000 ALTER TABLE `tariffs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `taskman`
--

DROP TABLE IF EXISTS `taskman`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taskman` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `address` varchar(255) NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  `jobtype` int(11) NOT NULL,
  `jobnote` text,
  `phone` varchar(255) DEFAULT NULL,
  `employee` int(11) NOT NULL,
  `employeedone` int(11) DEFAULT NULL,
  `donenote` text,
  `startdate` date NOT NULL,
  `starttime` time DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `smsdata` text,
  `change_admin` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `status` (`status`),
  KEY `login` (`login`),
  KEY `starttime` (`starttime`),
  KEY `address` (`address`),
  KEY `startdate` (`startdate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taskman`
--

LOCK TABLES `taskman` WRITE;
/*!40000 ALTER TABLE `taskman` DISABLE KEYS */;
/*!40000 ALTER TABLE `taskman` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `taskmandone`
--

DROP TABLE IF EXISTS `taskmandone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taskmandone` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskid` int(11) DEFAULT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taskmandone`
--

LOCK TABLES `taskmandone` WRITE;
/*!40000 ALTER TABLE `taskmandone` DISABLE KEYS */;
/*!40000 ALTER TABLE `taskmandone` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `taskmanlogs`
--

DROP TABLE IF EXISTS `taskmanlogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taskmanlogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskid` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `admin` varchar(45) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `logs` text,
  PRIMARY KEY (`id`),
  KEY `taskid` (`taskid`) USING BTREE,
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taskmanlogs`
--

LOCK TABLES `taskmanlogs` WRITE;
/*!40000 ALTER TABLE `taskmanlogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `taskmanlogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `taskmantrack`
--

DROP TABLE IF EXISTS `taskmantrack`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taskmantrack` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskid` int(11) NOT NULL,
  `admin` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `taskid` (`taskid`,`admin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taskmantrack`
--

LOCK TABLES `taskmantrack` WRITE;
/*!40000 ALTER TABLE `taskmantrack` DISABLE KEYS */;
/*!40000 ALTER TABLE `taskmantrack` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticketing`
--

DROP TABLE IF EXISTS `ticketing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticketing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `replyid` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `from` varchar(255) DEFAULT NULL,
  `to` varchar(255) DEFAULT NULL,
  `text` text,
  `admin` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticketing`
--

LOCK TABLES `ticketing` WRITE;
/*!40000 ALTER TABLE `ticketing` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticketing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ub_im`
--

DROP TABLE IF EXISTS `ub_im`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ub_im` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `read` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ub_im`
--

LOCK TABLES `ub_im` WRITE;
/*!40000 ALTER TABLE `ub_im` DISABLE KEYS */;
/*!40000 ALTER TABLE `ub_im` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Table structure for table `ubstorage`
--

DROP TABLE IF EXISTS `ubstorage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ubstorage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) DEFAULT NULL,
  `value` longtext,
  PRIMARY KEY (`id`),
  KEY `key` (`key`),
  FULLTEXT KEY `value` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ubstorage`
--

LOCK TABLES `ubstorage` WRITE;
/*!40000 ALTER TABLE `ubstorage` DISABLE KEYS */;
/*!40000 ALTER TABLE `ubstorage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uhw_brute`
--

DROP TABLE IF EXISTS `uhw_brute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uhw_brute` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `password` varchar(255) NOT NULL,
  `login` varchar(255) NOT NULL,
  `mac` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uhw_brute`
--

LOCK TABLES `uhw_brute` WRITE;
/*!40000 ALTER TABLE `uhw_brute` DISABLE KEYS */;
/*!40000 ALTER TABLE `uhw_brute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uhw_log`
--

DROP TABLE IF EXISTS `uhw_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uhw_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `password` varchar(255) NOT NULL,
  `login` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `nhid` int(11) NOT NULL,
  `oldmac` varchar(255) DEFAULT NULL,
  `newmac` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uhw_log`
--

LOCK TABLES `uhw_log` WRITE;
/*!40000 ALTER TABLE `uhw_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `uhw_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ukv_banksta`
--

DROP TABLE IF EXISTS `ukv_banksta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ukv_banksta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `hash` varchar(255) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `admin` varchar(255) NOT NULL,
  `contract` varchar(255) DEFAULT NULL,
  `summ` varchar(42) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `realname` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `pdate` varchar(42) DEFAULT NULL,
  `ptime` varchar(42) DEFAULT NULL,
  `processed` tinyint(4) NOT NULL,
  `payid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ukv_banksta`
--

LOCK TABLES `ukv_banksta` WRITE;
/*!40000 ALTER TABLE `ukv_banksta` DISABLE KEYS */;
/*!40000 ALTER TABLE `ukv_banksta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ukv_fees`
--

DROP TABLE IF EXISTS `ukv_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ukv_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `yearmonth` varchar(42) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `yearmonth` (`yearmonth`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ukv_fees`
--

LOCK TABLES `ukv_fees` WRITE;
/*!40000 ALTER TABLE `ukv_fees` DISABLE KEYS */;
/*!40000 ALTER TABLE `ukv_fees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ukv_payments`
--

DROP TABLE IF EXISTS `ukv_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ukv_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `balance` varchar(45) NOT NULL,
  `summ` varchar(45) NOT NULL,
  `visible` tinyint(4) NOT NULL,
  `cashtypeid` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`,`date`,`visible`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ukv_payments`
--

LOCK TABLES `ukv_payments` WRITE;
/*!40000 ALTER TABLE `ukv_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `ukv_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ukv_tags`
--

DROP TABLE IF EXISTS `ukv_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ukv_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tagtypeid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ukv_tags`
--

LOCK TABLES `ukv_tags` WRITE;
/*!40000 ALTER TABLE `ukv_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `ukv_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ukv_tariffs`
--

DROP TABLE IF EXISTS `ukv_tariffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ukv_tariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariffname` varchar(255) NOT NULL,
  `price` double NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ukv_tariffs`
--

LOCK TABLES `ukv_tariffs` WRITE;
/*!40000 ALTER TABLE `ukv_tariffs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ukv_tariffs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ukv_users`
--

DROP TABLE IF EXISTS `ukv_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ukv_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contract` varchar(40) DEFAULT NULL,
  `tariffid` int(11) DEFAULT NULL,
  `cash` double NOT NULL,
  `active` tinyint(4) NOT NULL,
  `realname` varchar(255) DEFAULT NULL,
  `passnum` varchar(40) DEFAULT NULL,
  `passwho` varchar(255) DEFAULT NULL,
  `passdate` date DEFAULT NULL,
  `paddr` varchar(255) DEFAULT NULL,
  `ssn` varchar(40) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `mobile` varchar(40) DEFAULT NULL,
  `regdate` datetime NOT NULL,
  `city` varchar(40) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `build` varchar(40) DEFAULT NULL,
  `apt` varchar(20) DEFAULT NULL,
  `inetlogin` varchar(40) DEFAULT NULL,
  `cableseal` varchar(40) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract` (`contract`),
  KEY `tariffid` (`tariffid`),
  KEY `cash` (`cash`),
  KEY `active` (`active`),
  KEY `regdate` (`regdate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ukv_users`
--

LOCK TABLES `ukv_users` WRITE;
/*!40000 ALTER TABLE `ukv_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `ukv_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `userreg`
--

DROP TABLE IF EXISTS `userreg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userreg` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `admin` varchar(45) NOT NULL,
  `login` varchar(45) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `userreg`
--

LOCK TABLES `userreg` WRITE;
/*!40000 ALTER TABLE `userreg` DISABLE KEYS */;
/*!40000 ALTER TABLE `userreg` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `login` varchar(50) NOT NULL DEFAULT '',
  `Password` varchar(150) NOT NULL DEFAULT '*',
  `Passive` int(3) DEFAULT '0',
  `Down` int(3) DEFAULT '0',
  `DisabledDetailStat` int(3) DEFAULT '0',
  `AlwaysOnline` int(3) DEFAULT '0',
  `Tariff` varchar(40) NOT NULL DEFAULT '',
  `Address` varchar(254) NOT NULL DEFAULT '',
  `Phone` varchar(128) NOT NULL DEFAULT '',
  `Email` varchar(50) NOT NULL DEFAULT '',
  `Note` text NOT NULL,
  `RealName` varchar(254) NOT NULL DEFAULT '',
  `StgGroup` varchar(40) NOT NULL DEFAULT '',
  `Credit` double DEFAULT '0',
  `TariffChange` varchar(40) NOT NULL DEFAULT '',
  `Userdata0` varchar(254) NOT NULL,
  `Userdata1` varchar(254) NOT NULL,
  `Userdata2` varchar(254) NOT NULL,
  `Userdata3` varchar(254) NOT NULL,
  `Userdata4` varchar(254) NOT NULL,
  `Userdata5` varchar(254) NOT NULL,
  `Userdata6` varchar(254) NOT NULL,
  `Userdata7` varchar(254) NOT NULL,
  `Userdata8` varchar(254) NOT NULL,
  `Userdata9` varchar(254) NOT NULL,
  `CreditExpire` int(11) DEFAULT '0',
  `IP` varchar(254) DEFAULT '*',
  `D0` bigint(30) DEFAULT '0',
  `U0` bigint(30) DEFAULT '0',
  `D1` bigint(30) DEFAULT '0',
  `U1` bigint(30) DEFAULT '0',
  `D2` bigint(30) DEFAULT '0',
  `U2` bigint(30) DEFAULT '0',
  `D3` bigint(30) DEFAULT '0',
  `U3` bigint(30) DEFAULT '0',
  `D4` bigint(30) DEFAULT '0',
  `U4` bigint(30) DEFAULT '0',
  `D5` bigint(30) DEFAULT '0',
  `U5` bigint(30) DEFAULT '0',
  `D6` bigint(30) DEFAULT '0',
  `U6` bigint(30) DEFAULT '0',
  `D7` bigint(30) DEFAULT '0',
  `U7` bigint(30) DEFAULT '0',
  `D8` bigint(30) DEFAULT '0',
  `U8` bigint(30) DEFAULT '0',
  `D9` bigint(30) DEFAULT '0',
  `U9` bigint(30) DEFAULT '0',
  `Cash` double DEFAULT '0',
  `FreeMb` double DEFAULT '0',
  `LastCashAdd` double DEFAULT '0',
  `LastCashAddTime` int(11) DEFAULT '0',
  `PassiveTime` int(11) DEFAULT '0',
  `LastActivityTime` int(11) DEFAULT '0',
  `NAS` varchar(17) NOT NULL,
  PRIMARY KEY (`login`),
  KEY `AlwaysOnline` (`AlwaysOnline`),
  KEY `IP` (`IP`),
  KEY `Address` (`Address`),
  KEY `Tariff` (`Tariff`),
  KEY `Phone` (`Phone`),
  KEY `Email` (`Email`),
  KEY `RealName` (`RealName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `userspeeds`
--

DROP TABLE IF EXISTS `userspeeds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userspeeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `speed` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `speed` (`speed`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `userspeeds`
--

LOCK TABLES `userspeeds` WRITE;
/*!40000 ALTER TABLE `userspeeds` DISABLE KEYS */;
/*!40000 ALTER TABLE `userspeeds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vcash`
--

DROP TABLE IF EXISTS `vcash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vcash` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `cash` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vcash`
--

LOCK TABLES `vcash` WRITE;
/*!40000 ALTER TABLE `vcash` DISABLE KEYS */;
/*!40000 ALTER TABLE `vcash` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vcashlog`
--

DROP TABLE IF EXISTS `vcashlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vcashlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `date` datetime NOT NULL,
  `balance` varchar(45) NOT NULL,
  `summ` varchar(45) NOT NULL,
  `cashtypeid` int(11) NOT NULL,
  `note` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `date` (`date`),
  KEY `login_2` (`login`),
  KEY `date_2` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vcashlog`
--

LOCK TABLES `vcashlog` WRITE;
/*!40000 ALTER TABLE `vcashlog` DISABLE KEYS */;
/*!40000 ALTER TABLE `vcashlog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vlan_mac_history`
--

DROP TABLE IF EXISTS `vlan_mac_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vlan_mac_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(45) DEFAULT NULL,
  `vlan` int(4) DEFAULT NULL,
  `mac` varchar(45) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vlan_mac_history`
--

LOCK TABLES `vlan_mac_history` WRITE;
/*!40000 ALTER TABLE `vlan_mac_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `vlan_mac_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vlan_pools`
--

DROP TABLE IF EXISTS `vlan_pools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vlan_pools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `desc` varchar(32) DEFAULT '*',
  `firstvlan` int(4) DEFAULT NULL,
  `endvlan` int(4) DEFAULT NULL,
  `qinq` int(1) DEFAULT NULL,
  `svlan` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vlan_pools`
--

LOCK TABLES `vlan_pools` WRITE;
/*!40000 ALTER TABLE `vlan_pools` DISABLE KEYS */;
/*!40000 ALTER TABLE `vlan_pools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vlan_terminators`
--

DROP TABLE IF EXISTS `vlan_terminators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vlan_terminators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `netid` int(4) DEFAULT NULL,
  `vlanpoolid` int(4) DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `remote-id` varchar(50) DEFAULT NULL,
  `interface` varchar(50) DEFAULT NULL,
  `relay` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vlan_terminators`
--

LOCK TABLES `vlan_terminators` WRITE;
/*!40000 ALTER TABLE `vlan_terminators` DISABLE KEYS */;
/*!40000 ALTER TABLE `vlan_terminators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vlanhosts`
--

DROP TABLE IF EXISTS `vlanhosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vlanhosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vlanpoolid` int(11) NOT NULL,
  `login` varchar(32) DEFAULT '*',
  `vlan` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vlanhosts`
--

LOCK TABLES `vlanhosts` WRITE;
/*!40000 ALTER TABLE `vlanhosts` DISABLE KEYS */;
/*!40000 ALTER TABLE `vlanhosts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vlanhosts_qinq`
--

DROP TABLE IF EXISTS `vlanhosts_qinq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vlanhosts_qinq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vlanpoolid` int(11) NOT NULL,
  `login` varchar(32) DEFAULT '*',
  `svlan` int(4) DEFAULT NULL,
  `cvlan` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vlanhosts_qinq`
--

LOCK TABLES `vlanhosts_qinq` WRITE;
/*!40000 ALTER TABLE `vlanhosts_qinq` DISABLE KEYS */;
/*!40000 ALTER TABLE `vlanhosts_qinq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vols_docs`
--

DROP TABLE IF EXISTS `vols_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vols_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) DEFAULT NULL,
  `date` datetime NOT NULL,
  `line_id` int(11) DEFAULT NULL,
  `mark_id` int(11) DEFAULT NULL,
  `path` varchar(128) NOT NULL DEFAULT '/',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vols_docs`
--

LOCK TABLES `vols_docs` WRITE;
/*!40000 ALTER TABLE `vols_docs` DISABLE KEYS */;
/*!40000 ALTER TABLE `vols_docs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vols_lines`
--

DROP TABLE IF EXISTS `vols_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vols_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `point_start` varchar(255) NOT NULL,
  `point_end` varchar(255) NOT NULL,
  `fibers_amount` int(11) NOT NULL DEFAULT '0',
  `length` double NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `param_color` varchar(32) NOT NULL,
  `param_width` int(11) NOT NULL,
  `geo` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vols_lines`
--

LOCK TABLES `vols_lines` WRITE;
/*!40000 ALTER TABLE `vols_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `vols_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vols_marks`
--

DROP TABLE IF EXISTS `vols_marks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vols_marks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_id` int(11) NOT NULL,
  `number` int(11) DEFAULT NULL,
  `placement` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vols_marks`
--

LOCK TABLES `vols_marks` WRITE;
/*!40000 ALTER TABLE `vols_marks` DISABLE KEYS */;
/*!40000 ALTER TABLE `vols_marks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vols_marks_types`
--

DROP TABLE IF EXISTS `vols_marks_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vols_marks_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon_color` varchar(255) NOT NULL DEFAULT 'blue',
  `icon_style` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vols_marks_types`
--

LOCK TABLES `vols_marks_types` WRITE;
/*!40000 ALTER TABLE `vols_marks_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `vols_marks_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vservices`
--

DROP TABLE IF EXISTS `vservices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vservices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tagid` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `cashtype` varchar(40) NOT NULL,
  `priority` int(11) NOT NULL,
  `fee_charge_always` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vservices`
--

LOCK TABLES `vservices` WRITE;
/*!40000 ALTER TABLE `vservices` DISABLE KEYS */;
/*!40000 ALTER TABLE `vservices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `watchdog`
--

DROP TABLE IF EXISTS `watchdog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `watchdog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `checktype` varchar(255) NOT NULL,
  `param` varchar(255) NOT NULL,
  `operator` varchar(255) NOT NULL,
  `condition` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `oldresult` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `name` (`name`),
  KEY `oldresult` (`oldresult`),
  KEY `param` (`param`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `watchdog`
--

LOCK TABLES `watchdog` WRITE;
/*!40000 ALTER TABLE `watchdog` DISABLE KEYS */;
/*!40000 ALTER TABLE `watchdog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wcpedevices`
--

DROP TABLE IF EXISTS `wcpedevices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wcpedevices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modelid` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `mac` varchar(45) DEFAULT NULL,
  `snmp` varchar(45) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `bridge` tinyint(4) NOT NULL DEFAULT '0',
  `uplinkapid` int(11) DEFAULT NULL,
  `uplinkcpeid` int(11) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wcpedevices`
--

LOCK TABLES `wcpedevices` WRITE;
/*!40000 ALTER TABLE `wcpedevices` DISABLE KEYS */;
/*!40000 ALTER TABLE `wcpedevices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wcpeusers`
--

DROP TABLE IF EXISTS `wcpeusers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wcpeusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cpeid` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wcpeusers`
--

LOCK TABLES `wcpeusers` WRITE;
/*!40000 ALTER TABLE `wcpeusers` DISABLE KEYS */;
/*!40000 ALTER TABLE `wcpeusers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wdycinfo`
--

DROP TABLE IF EXISTS `wdycinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wdycinfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `missedcount` int(11) DEFAULT NULL,
  `recallscount` int(11) DEFAULT NULL,
  `unsucccount` int(11) DEFAULT NULL,
  `missednumbers` text,
  `totaltrytime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wdycinfo`
--

LOCK TABLES `wdycinfo` WRITE;
/*!40000 ALTER TABLE `wdycinfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `wdycinfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `weblogs`
--

DROP TABLE IF EXISTS `weblogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weblogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `admin` varchar(45) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `date_2` (`date`),
  KEY `date_3` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weblogs`
--

LOCK TABLES `weblogs` WRITE;
/*!40000 ALTER TABLE `weblogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `weblogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wh_categories`
--

DROP TABLE IF EXISTS `wh_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wh_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wh_categories`
--

LOCK TABLES `wh_categories` WRITE;
/*!40000 ALTER TABLE `wh_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `wh_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wh_contractors`
--

DROP TABLE IF EXISTS `wh_contractors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wh_contractors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wh_contractors`
--

LOCK TABLES `wh_contractors` WRITE;
/*!40000 ALTER TABLE `wh_contractors` DISABLE KEYS */;
/*!40000 ALTER TABLE `wh_contractors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wh_in`
--

DROP TABLE IF EXISTS `wh_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wh_in` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `itemtypeid` int(11) NOT NULL,
  `contractorid` int(11) NOT NULL,
  `count` double NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `storageid` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `admin` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`itemtypeid`,`contractorid`,`storageid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wh_in`
--

LOCK TABLES `wh_in` WRITE;
/*!40000 ALTER TABLE `wh_in` DISABLE KEYS */;
/*!40000 ALTER TABLE `wh_in` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wh_itemtypes`
--

DROP TABLE IF EXISTS `wh_itemtypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wh_itemtypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoryid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `unit` varchar(40) NOT NULL,
  `reserve` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categoryid` (`categoryid`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wh_itemtypes`
--

LOCK TABLES `wh_itemtypes` WRITE;
/*!40000 ALTER TABLE `wh_itemtypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `wh_itemtypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wh_out`
--

DROP TABLE IF EXISTS `wh_out`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wh_out` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `desttype` varchar(40) NOT NULL,
  `destparam` varchar(255) NOT NULL,
  `storageid` int(11) NOT NULL,
  `itemtypeid` int(11) NOT NULL,
  `count` double NOT NULL,
  `price` double DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `admin` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`storageid`,`itemtypeid`),
  KEY `desttype` (`desttype`),
  KEY `destparam` (`destparam`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wh_out`
--

LOCK TABLES `wh_out` WRITE;
/*!40000 ALTER TABLE `wh_out` DISABLE KEYS */;
/*!40000 ALTER TABLE `wh_out` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wh_reserve`
--

DROP TABLE IF EXISTS `wh_reserve`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wh_reserve` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `storageid` int(11) NOT NULL,
  `itemtypeid` int(11) NOT NULL,
  `count` double NOT NULL,
  `employeeid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `storageid` (`storageid`),
  KEY `itemtypeid` (`itemtypeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wh_reserve`
--

LOCK TABLES `wh_reserve` WRITE;
/*!40000 ALTER TABLE `wh_reserve` DISABLE KEYS */;
/*!40000 ALTER TABLE `wh_reserve` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wh_reshist`
--

DROP TABLE IF EXISTS `wh_reshist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wh_reshist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `type` varchar(40) NOT NULL,
  `storageid` int(11) DEFAULT NULL,
  `itemtypeid` int(11) DEFAULT NULL,
  `count` double DEFAULT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `admin` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`storageid`,`itemtypeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wh_reshist`
--

LOCK TABLES `wh_reshist` WRITE;
/*!40000 ALTER TABLE `wh_reshist` DISABLE KEYS */;
/*!40000 ALTER TABLE `wh_reshist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wh_storages`
--

DROP TABLE IF EXISTS `wh_storages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wh_storages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wh_storages`
--

LOCK TABLES `wh_storages` WRITE;
/*!40000 ALTER TABLE `wh_storages` DISABLE KEYS */;
/*!40000 ALTER TABLE `wh_storages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whiteboard`
--

DROP TABLE IF EXISTS `whiteboard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `whiteboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoryid` int(11) NOT NULL,
  `admin` varchar(255) NOT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `createdate` datetime NOT NULL,
  `donedate` datetime DEFAULT NULL,
  `priority` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `text` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whiteboard`
--

LOCK TABLES `whiteboard` WRITE;
/*!40000 ALTER TABLE `whiteboard` DISABLE KEYS */;
/*!40000 ALTER TABLE `whiteboard` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zbsannhist`
--

DROP TABLE IF EXISTS `zbsannhist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zbsannhist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `annid` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `annid` (`annid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zbsannhist`
--

LOCK TABLES `zbsannhist` WRITE;
/*!40000 ALTER TABLE `zbsannhist` DISABLE KEYS */;
/*!40000 ALTER TABLE `zbsannhist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zbsannouncements`
--

DROP TABLE IF EXISTS `zbsannouncements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zbsannouncements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `public` tinyint(4) DEFAULT '0',
  `type` varchar(20) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `public` (`public`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zbsannouncements`
--

LOCK TABLES `zbsannouncements` WRITE;
/*!40000 ALTER TABLE `zbsannouncements` DISABLE KEYS */;
/*!40000 ALTER TABLE `zbsannouncements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zbssclog`
--

DROP TABLE IF EXISTS `zbssclog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zbssclog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zbssclog`
--

LOCK TABLES `zbssclog` WRITE;
/*!40000 ALTER TABLE `zbssclog` DISABLE KEYS */;
/*!40000 ALTER TABLE `zbssclog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zte_cards`
--

DROP TABLE IF EXISTS `zte_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zte_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `swid` int(11) NOT NULL,
  `slot_number` int(11) NOT NULL,
  `card_name` varchar(5) NOT NULL,
  `chasis_number` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `swid` (`swid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zte_cards`
--

LOCK TABLES `zte_cards` WRITE;
/*!40000 ALTER TABLE `zte_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `zte_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zte_vlan_bind`
--

DROP TABLE IF EXISTS `zte_vlan_bind`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zte_vlan_bind` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `swid` int(11) NOT NULL,
  `slot_number` int(11) NOT NULL,
  `port_number` int(2) NOT NULL,
  `vlan` int(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `swid` (`swid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zte_vlan_bind`
--

LOCK TABLES `zte_vlan_bind` WRITE;
/*!40000 ALTER TABLE `zte_vlan_bind` DISABLE KEYS */;
/*!40000 ALTER TABLE `zte_vlan_bind` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-10-23 18:08:42

-- 0.9.3 update 
ALTER TABLE `ukv_users` ADD `tariffnmid` INT NULL AFTER `tariffid`;
ALTER TABLE `sms_history` ADD `smssrvid` INT(11) NOT NULL DEFAULT 0 AFTER `id`;
ALTER TABLE `sms_history` ADD INDEX(`smssrvid`);

CREATE TABLE IF NOT EXISTS `sms_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `login` varchar(255) NOT NULL,
  `passwd` varchar(255) NOT NULL,
  `url_addr` varchar(255) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `alpha_name` varchar(40) NOT NULL,
  `default_service` tinyint(1) UNSIGNED DEFAULT 0,
  `api_file_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `sms_services_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sms_srv_id` int(11) NOT NULL,
  `user_login` varchar(255) DEFAULT NULL,
  `employee_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`user_login`),
  UNIQUE KEY (`employee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `switches_qinq` (
  `switchid` int(11) NOT NULL,
  `svlan` int(11) NOT NULL,
  `cvlan` int(11) NOT NULL,
  PRIMARY KEY (`switchid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `bankstamd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `hash` varchar(255) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `admin` varchar(255) NOT NULL,
  `contract` varchar(255) DEFAULT NULL,
  `summ` varchar(42) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `realname` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `pdate` varchar(42) DEFAULT NULL,
  `ptime` varchar(42) DEFAULT NULL,
  `processed` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- mlg issues fix
DROP TABLE IF EXISTS `mlg_acct`;
DROP TABLE IF EXISTS `mlg_postauth`;
DROP TABLE IF EXISTS `mlg_check`;
DROP TABLE IF EXISTS `mlg_reply`;
DROP TABLE IF EXISTS `mlg_groupcheck`;
DROP TABLE IF EXISTS `mlg_groupreply`;
DROP TABLE IF EXISTS `mlg_usergroup`;
DROP TABLE IF EXISTS `mlg_nasattributes`;
DROP TABLE IF EXISTS `mlg_nasoptions`;
DROP TABLE IF EXISTS `mlg_services`;
DROP TABLE IF EXISTS `mlg_userstates`;
DROP TABLE IF EXISTS `mlg_traffic`;

