/**
 * @file
 * @author Michael Sypolt <msypolt@gmail.com>
 * 
 * Creates the database users
 */

-- Create users

CREATE USER 'lwt'@'localhost' IDENTIFIED BY 'LibreW38t00ls';
GRANT ALL PRIVILEGES ON libreweb.* TO 'lwt'@'localhost';
FLUSH PRIVILEGES;

