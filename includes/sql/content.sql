/**
 * @file
 * @author Michael Sypolt <msypolt@gmail.com>
 * 
 * Populates LWT database with minimal information needed to get started
 */
 
-- NOTE! First user's Username is admin and password will be Admin. Make sure to reset it in the website.

-- Create the group that is "root" (typically no users get assigned this group except the admin)
INSERT INTO `groups` (`name`) VALUES ('Everyone');
UPDATE `groups` SET `id`=0;
ALTER TABLE `groups` AUTO_INCREMENT=1;

-- Starting back at 1, continue adding groups
INSERT INTO `groups` (`name`) VALUES 
  ('Unauthenticated'),
  ('Authenticated'),
  ('Internal'), 
  ('External');
  
-- Set group hierarchy
INSERT INTO `group_hierarchy` (`parent_id`,`group_id`) VALUES 
  (0,(SELECT `id` FROM `groups` WHERE `name`='Everyone')),
  ((SELECT `id` FROM `groups` WHERE `name`='Everyone'), (SELECT `id` FROM `groups` WHERE `name`='Unauthenticated')),
  ((SELECT `id` FROM `groups` WHERE `name`='Everyone'), (SELECT `id` FROM `groups` WHERE `name`='Authenticated')), 
  ((SELECT `id` FROM `groups` WHERE `name`='Authenticated'), (SELECT `id` FROM `groups` WHERE `name`='Internal')),
  ((SELECT `id` FROM `groups` WHERE `name`='Authenticated'), (SELECT `id` FROM `groups` WHERE `name`='External'));

-- Noone is given this role, but used for applying permissions to unlogged users
INSERT INTO `roles` (`name`, `desc`) VALUES ('Unauthenticated User', 'Non-logged in user');
UPDATE `roles` SET `id`=0;
ALTER TABLE `roles` AUTO_INCREMENT=1;

-- Always keep administrator as role id=1
INSERT INTO `roles` (`name`, `desc`) VALUES 
  ('Administrator','Administers website'),
  ('Authenticated User', 'Basic user');
  
-- Add the admin user
INSERT INTO `users` (`login`, `firstname`, `lastname`, `desc`) VALUES ('admin', 'Site', 'Adminstrator', 'Site administrator');
INSERT INTO `user_roles` (`role_id`, `user_id`) VALUES ((SELECT `id` FROM `roles` WHERE `name`='Administrator'), (SELECT `id` FROM `users` WHERE `login`='admin'));
INSERT INTO `user_groups` (`group_id`, `user_id`) VALUES ((SELECT `id` FROM `groups` WHERE `name`='Everyone'), (SELECT `id` FROM `users` WHERE `login`='admin'));
INSERT INTO `passwords` (`user_id`, `valid_date`, `hash`, `key`) VALUES 
  ((SELECT `id` FROM `users` WHERE `login`='admin'),'2014-01-01', '$2a$07$FrwVLqsQApBQ6bWD3NgE9u37SeiH9QwANXKt0EopNTHkq3Ly9l1.C', 'FrwVLqsQApBQ6bWD3NgE94');

-- Add the root homepage at id=0
INSERT INTO `content` (`title`,`function_call`,`content`) VALUES ('Home in Database','lwt_render_home',NULL);
UPDATE `content` SET `id`=0;
ALTER TABLE `content` AUTO_INCREMENT=1;

-- Adding required content to enable site to run
INSERT INTO `content` (`title`,`function_call`,`content`) VALUES
  ('Login', 'lwt_render_login',NULL),
  ('Logout', 'lwt_process_logout', NULL),
  ('Test Page',NULL,'<p>This is a Test Page<br />Making sure it shows up</p>');

-- Place the required content in a hierarchy
INSERT INTO `content_hierarchy` (`parent_id`,`content_id`,`url_code`) VALUES
  (0,(SELECT `id` FROM `content` WHERE `title`='Home in Database'),''),
  (0, (SELECT `id` FROM `content` WHERE `title`='Login'), 'login'),
  (0, (SELECT `id` FROM `content` WHERE `title`='Logout'), 'logout'),
  (0, (SELECT `id` FROM `content` WHERE `title`='Test Page'), 'test');
  
-- Now applying permissions
INSERT INTO `group_access` (`content_id`,`group_id`) VALUES
  ((SELECT `id` FROM `content` WHERE `title`='Home in Database'),0),
  ((SELECT `id` FROM `content` WHERE `title`='Login'), 0),
  ((SELECT `id` FROM `content` WHERE `title`='Logout'), 0),
  ((SELECT `id` FROM `content` WHERE `title`='Test Page'), (SELECT `id` FROM `groups` WHERE `name`='Internal'));
