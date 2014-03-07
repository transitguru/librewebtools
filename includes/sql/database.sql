/**
 * @file
 * @author Michael Sypolt <msypolt@gmail.com>
 * 
 * Creates the database users for LWT
 */

-- Create database users and database

-- Name your database between the `tick_marks` (no spaces, use underscores place of spaces)
DROP SCHEMA IF EXISTS `librewebtools`;

-- This name should be the same as the one on line 11
CREATE SCHEMA IF NOT EXISTS `librewebtools` DEFAULT CHARACTER SET utf8;

-- Rename lwt to the database user you want
-- Please use a different password than LibreW38t00ls
CREATE USER 'lwt'@'localhost' IDENTIFIED BY 'LibreW38t00ls';

-- Replace librewebtools with the name you used on line 11 and 14
-- Replace lwt with the name you used on line 18 (before @'localhost')
GRANT ALL PRIVILEGES ON `librewebtools`.* TO 'lwt'@'localhost';
FLUSH PRIVILEGES;

