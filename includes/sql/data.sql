/**
 * @file
 * @author Michael Sypolt <msypolt@gmail.com>
 * 
 * Populates LWT database with minimal information needed to get started
 */
 
-- Username is admin and password will be Admin. Make sure to reset it in the website.

INSERT INTO `groups` (`name`) VALUES 
  ('Everyone'),
  ('Internal'), 
  ('External');
  
INSERT INTO `group_hierarchy` (`parent_id`,`group_id`) VALUES 
  (0, (SELECT `id` FROM `groups` WHERE `name`='Everyone')), 
  ((SELECT `id` FROM `groups` WHERE `name`='Everyone'), (SELECT `id` FROM `groups` WHERE `name`='Internal')),
  ((SELECT `id` FROM `groups` WHERE `name`='Everyone'), (SELECT `id` FROM `groups` WHERE `name`='External'));
  
INSERT INTO `roles` (`name`, `desc`) VALUES 
  ('Administrator','Administers website'),
  ('Authenticated User', 'Basic user');
  
INSERT INTO `users` (`login`, `firstname`, `lastname`, `desc`) VALUES ('admin', 'Site', 'Adminstrator', 'Site administrator');
INSERT INTO `user_roles` (`role_id`, `user_id`) VALUES ((SELECT `id` FROM `roles` WHERE `name`='Administrator'), (SELECT `id` FROM `users` WHERE `login`='admin'));
INSERT INTO `user_groups` (`group_id`, `user_id`) VALUES ((SELECT `id` FROM `groups` WHERE `name`='Everyone'), (SELECT `id` FROM `users` WHERE `login`='admin'));
INSERT INTO `passwords` (`user_id`, `valid_date`, `hash`, `key`) VALUES 
  ((SELECT `id` FROM `users` WHERE `login`='admin'),'2014-01-01', '$2a$07$FrwVLqsQApBQ6bWD3NgE9u37SeiH9QwANXKt0EopNTHkq3Ly9l1.C', 'FrwVLqsQApBQ6bWD3NgE94');

-- Adding required content to enable site to run

INSERT INTO `content` (`title`,`function_call`,`content`) VALUES
  ('Login', 'lwt_render_login',NULL),
  ('Logout', 'lwt_process_logout', NULL),
  ('Test Page',NULL,'<p>This is a Test Page<br />Making sure it shows up</p>');

INSERT INTO `content_hierarchy` (`parent_id`,`content_id`,`url_code`) VALUES
  (0, (SELECT `id` FROM `content` WHERE `title`='Login'), 'login'),
  (0, (SELECT `id` FROM `content` WHERE `title`='Logout'), 'logout'),
  (0, (SELECT `id` FROM `content` WHERE `title`='Test Page'), 'test');
  
-- Now adding some dummy content
