/**
 * @file
 * @author Michael Sypolt <msypolt@gmail.com>
 * 
 * Creates the site users
 */
 
-- Username is admin and password will be Admin. Make sure to reset it in the website.

INSERT INTO `companies` (`name`, `license_key`, `license_users`, `license_date`) VALUES ('Site Admin', '', '', '2100-01-01');
INSERT INTO `users` (`company_id`, `login`, `firstname`, `lastname`) VALUES ((SELECT `id` FROM `companies` WHERE `name` = 'Site Admin'), 'admin', 'Site', 'Adminstrator');
INSERT INTO `passwords` (`user_id`, `valid_date`, `hash`, `key`) VALUES ((SELECT `id` FROM `users` WHERE `login`='admin'),'2014-01-01', '$2a$07$FrwVLqsQApBQ6bWD3NgE9u37SeiH9QwANXKt0EopNTHkq3Ly9l1.C', 'FrwVLqsQApBQ6bWD3NgE94');
