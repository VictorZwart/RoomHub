-- MySQL dump 10.13  Distrib 5.7.28, for Linux (armv7l)
--
-- Host: localhost    Database: ddwt19_fp
-- ------------------------------------------------------
-- Server version	5.7.28-0ubuntu0.18.04.4

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
-- Current Database: `ddwt19_fp`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `ddwt19_fp` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `ddwt19_fp`;

--
-- Table structure for table `listing`
--

DROP TABLE IF EXISTS `listing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `listing` (
  `listing_id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(10) NOT NULL,
  `available_from` date NOT NULL,
  `available_to` date DEFAULT NULL,
  `room_id` int(11) NOT NULL,
  PRIMARY KEY (`listing_id`),
  KEY `room_fk` (`room_id`),
  CONSTRAINT `room_fk` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `listing`
--

LOCK TABLES `listing` WRITE;
/*!40000 ALTER TABLE `listing` DISABLE KEYS */;
INSERT INTO `listing` VALUES (1,'open','2020-01-18',NULL,1),(2,'open','2020-02-01','2021-02-01',2),(3,'open','2020-01-18',NULL,3),(4,'closed','2020-03-01',NULL,4),(5,'open','2020-02-01',NULL,5);
/*!40000 ALTER TABLE `listing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migration`
--

DROP TABLE IF EXISTS `migration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migration` (
  `migration_id` int(11) NOT NULL AUTO_INCREMENT,
  `migration_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `migration_file` varchar(255) NOT NULL,
  PRIMARY KEY (`migration_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migration`
--

LOCK TABLES `migration` WRITE;
/*!40000 ALTER TABLE `migration` DISABLE KEYS */;
INSERT INTO `migration` VALUES (1,'2020-01-18 15:14:47','0.sql');
/*!40000 ALTER TABLE `migration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `opt_in`
--

DROP TABLE IF EXISTS `opt_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `opt_in` (
  `opt_in_id` int(11) NOT NULL AUTO_INCREMENT,
  `listing_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(10) NOT NULL DEFAULT 'open',
  PRIMARY KEY (`opt_in_id`),
  KEY `listing_fk_optin` (`listing_id`),
  KEY `user_fk_optin` (`user_id`),
  CONSTRAINT `listing_fk_optin` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`listing_id`) ON DELETE CASCADE,
  CONSTRAINT `user_fk_optin` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `opt_in`
--

LOCK TABLES `opt_in` WRITE;
/*!40000 ALTER TABLE `opt_in` DISABLE KEYS */;
INSERT INTO `opt_in` VALUES (1,1,1,'Beste Victor, wat een leuke kamer! Graat zou ik langs willen komen voor een bezichtiging.\r\nMet vriendelijke groet, Robin van der Noord','2020-01-18 03:22:12','open'),(2,3,1,'Deze kamer wil ik helemaal niet!','2020-01-18 03:47:34','open'),(3,4,1,'Wat ontzettend leuk! Doe mij er maar drie!','2020-01-18 03:47:46','accepted'),(4,3,5,'Mogen hier dikke toeters geklapt worden? Vraag het voor een vriend.','2020-01-19 11:23:56','open'),(5,5,5,'Is the price including or excluding gwl?','2020-01-19 11:24:25','open'),(6,1,6,'Please geef kamer, woon in doos\r\n','2020-01-19 01:35:12','open');
/*!40000 ALTER TABLE `opt_in` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room`
--

DROP TABLE IF EXISTS `room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `price` float NOT NULL,
  `size` varchar(20) NOT NULL,
  `type` varchar(100) NOT NULL,
  `city` varchar(255) NOT NULL,
  `zipcode` char(6) NOT NULL,
  `street_name` varchar(255) NOT NULL,
  `number` varchar(10) NOT NULL,
  `picture` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`room_id`),
  KEY `user_fk` (`owner_id`),
  CONSTRAINT `user_fk` FOREIGN KEY (`owner_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room`
--

LOCK TABLES `room` WRITE;
/*!40000 ALTER TABLE `room` DISABLE KEYS */;
INSERT INTO `room` VALUES (1,2,'Mooie kamer voor een nog mooiere prijs! Deze vrijstaande woning is voor iedereen die van ruimte en moderne architectuur houdt! Met glazen muren en ramen ziet men altijd het prachtige platteland van Drenthe! ',750,'55','Vrijstaande woning','Assen','9405AE','Witterstraat','97','room1.jpg'),(2,3,'Leuke kamer in Groningen, voor een knalprijs (excl.)!',964,'6','Studio','Noordlaren','9479PA','Lageweg','37','room2.jpg'),(3,2,'Prachtige soort van studio met gedeelde keuken, wc en badkamer! Deze prachtige kamer staat geen huisdieren toe. wel mag er gerookt worden binnen en buitenshuis!',348,'18','Studio','Groningen','9716EP','Asingastraat','8','room3.png'),(4,3,'Veilig pand in de binnenstad',499,'25','Studio met gedeelde badkamer','Groningen','9712GR','Hardewikerstraat','15','room4.jpg'),(5,4,'Nice appartment, 5 min bike ride from city centre. Comes with free neighbour who apparently has no study/job and sings very loud all day! ',750,'40','Appartment','Groningen','9715CP','Padangstraat','20B','room5.jpg');
/*!40000 ALTER TABLE `room` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(10) NOT NULL,
  `email` varchar(255) NOT NULL,
  `language` varchar(255) NOT NULL,
  `birthdate` date NOT NULL,
  `biography` text,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `occupation` varchar(255) NOT NULL,
  `role` varchar(10) NOT NULL,
  `picture` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'robinvandernoord','$2y$10$viLvMGtq.cdhDQpF3hqT3.J4X1ctO8.9KNQY3aGOeteFH72ZaZS6S','0634486182','r.van.der.noord@student.rug.nl','Dutch','1999-01-23','- student\r\n- programmer','Robin','van der Noord','Programmer','tenant','user1.jpg'),(2,'Victor','$2y$10$3XzaNMRLwcDvQ8yxEvPbKO04erVxY6arXsrcmltr.kltWcYQ8Qq8K','0631582587','victor.zwart@ziggo.nl','Dutch','1998-11-05','Im a guy from Assen in the Netherlands, and like to sit behind my computer. Joejoe!','Victor','Zwart','Student, cinemaworker','owner','user2.png'),(3,'WimBulten','$2y$10$Dj30M8FE/TCFkFDSwt0Q/OSoq0o88ux8g07kC1fT5WUXvbaACoIQe','0505698742','wim@bulten.com','Dutch and Italian','1975-09-05','Leukste huisjesmelker uit de Stad!','Wim','Bulten','Pandeigenaar en (hobbiematig) geitenmelker','owner','user3.jpg'),(4,'Lars_DR','$2y$10$ghmvXWXS/lYUaXgbJCjVF.sxMtbZAZp0c8DzxCB131ORgbHT.n8F2','0655058315','lars.dr5@gmail.com','Dutch/English','1999-09-05','Nuchter.','Lars','de Roo','Student','owner',NULL),(5,'larsdr','$2y$10$nI8uXf8YD0iaN.x5NBZNqu/Ee5YcTx6Ynj.ZNNa6BpsNJaomVDU2O','0653148546','l@l.com','Dutch','1999-02-11','Works in Amsterdam','Jesse','Aukema','Hotelnogwat','tenant',NULL),(6,'Zhenjaah','$2y$10$Ki/SRbw.BaTvjd6sFr5ufeTlV/47PBNN6sVKq9T4Wp2BkGus4D0Pq','0612345678','zhenjag.zg@gmail.com','Deutsch','1999-11-07','Fakkakakakak','JeBoii','Gnezdilov','Stripper','tenant',NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-01-19 13:57:41
