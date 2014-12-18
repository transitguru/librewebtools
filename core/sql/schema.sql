/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.limited>
 * 
 * Core database set up for empty tables in LWT
 */


-- -----------------------------------------------------
-- Table `roles` 
-- -----------------------------------------------------

DROP TABLE IF EXISTS `roles` ;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary identifier for a role',
  `sortorder`   INT(11) NOT NULL DEFAULT 0 COMMENT 'Allows a site admin to sort roles',
  `name`  VARCHAR(255) NOT NULL COMMENT 'Human readable name for a role',
  `created` DATETIME NOT NULL COMMENT 'Date created',
  `desc` TEXT NULL COMMENT 'Optional additional information about the role',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`name` ASC)
)
ENGINE = InnoDB COMMENT = 'Basic user roles';


-- -----------------------------------------------------
-- Table `groups` 
-- -----------------------------------------------------
DROP TABLE IF EXISTS `groups` ;

CREATE TABLE IF NOT EXISTS `groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary identifier for a group' ,
  `parent_id` INT UNSIGNED DEFAULT NULL COMMENT 'Parent for a group, 0 is root, NULL means ready to delete (unless id=0)' ,
  `sortorder`   INT(11) NOT NULL DEFAULT 0 COMMENT 'Allows a site admin to sort groups',
  `name` VARCHAR(100) NOT NULL COMMENT 'Human readable name for a group',
  `created` DATETIME NOT NULL COMMENT 'Date created',
  `desc` TEXT COMMENT 'Optional additional information about the group',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`parent_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  UNIQUE KEY (`name` ASC)
) ENGINE=InnoDB COMMENT = 'User groups (in a hierarchical tree)';


-- -----------------------------------------------------
-- Table `users` 
-- -----------------------------------------------------
DROP TABLE IF EXISTS `users` ;

CREATE TABLE IF NOT EXISTS `users` (
  `id`  INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary identifier for the user',
  `login`  VARCHAR(40) NOT NULL COMMENT 'Username for logging in the website',
  `firstname`  VARCHAR(100) NOT NULL COMMENT 'First name',
  `lastname`  VARCHAR(100) NOT NULL COMMENT 'Surname',
  `email`  VARCHAR(255) NOT NULL COMMENT 'Email address',
  `created` DATETIME NOT NULL COMMENT 'Date created',
  `desc`  TEXT NULL  COMMENT 'Optional additional information about the user',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`login`),
  UNIQUE KEY (`email`)
)
ENGINE = InnoDB COMMENT = 'User information for authenticated users';

-- -----------------------------------------------------
-- Table `user_groups`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `user_groups` ;

CREATE TABLE IF NOT EXISTS `user_groups` (
  `user_id` INT UNSIGNED NOT NULL COMMENT 'Reference to users.id',
  `group_id` INT UNSIGNED NOT NULL COMMENT 'Reference to groups.id',
  PRIMARY KEY (`group_id`, `user_id`) ,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
)
ENGINE = InnoDB COMMENT = 'Adds users to group membership';


-- -----------------------------------------------------
-- Table `user_roles` 
-- -----------------------------------------------------
DROP TABLE IF EXISTS `user_roles` ;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` INT UNSIGNED NOT NULL COMMENT 'Reference to users.id',
  `role_id` INT UNSIGNED NOT NULL COMMENT 'Reference to roles.id',
  PRIMARY KEY (`role_id`, `user_id`) ,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
)
ENGINE = InnoDB COMMENT = 'Adds users to roles';


-- -----------------------------------------------------
-- Table `passwords` 
-- -----------------------------------------------------
DROP TABLE IF EXISTS `passwords` ;

CREATE TABLE IF NOT EXISTS `passwords` (
  `user_id`  INT UNSIGNED NOT NULL COMMENT 'Reference to users.id',
  `valid_date` DATETIME NOT NULL COMMENT 'Valid date for this password',
  `expire_date` DATETIME NULL COMMENT 'Expiration date for this password',
  `reset_date` DATETIME NULL COMMENT 'Expiration date for this reset code',
  `reset_code` VARCHAR(255) NULL COMMENT 'Reset code that would be used in a URL for a user to reset the password',
  `hashed` VARCHAR(255) NOT NULL COMMENT 'Hashed password',
  PRIMARY KEY (`user_id`, `valid_date`) ,
  FOREIGN KEY (`user_id` ) REFERENCES `users` (`id` ) ON DELETE CASCADE ON UPDATE CASCADE
)
ENGINE = InnoDB COMMENT = 'User hashed passwords';

-- -----------------------------------------------------
-- Table `modules`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `modules` ;

CREATE TABLE IF NOT EXISTS `modules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for theme in database',
  `type` VARCHAR(100) NOT NULL DEFAULT 'theme' COMMENT 'Determine if it is a "module" or "theme"',
  `core` TINYINT NOT NULL DEFAULT 1 COMMENT 'Boolean to designate core modules. Custom is 0',
  `code` VARCHAR(100) NOT NULL COMMENT 'Name of module or theme (directory name, no spaces or special chars)',
  `name` VARCHAR(255) NOT NULL COMMENT 'Human-friendly name of the module or theme',
  `enabled` TINYINT NOT NULL DEFAULT 1 COMMENT 'Boolean determining if module or theme is enabled',
  `required` TINYINT NOT NULL DEFAULT 1 COMMENT 'Boolean determining if module or theme is required for site to work',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`type` ASC,`core` DESC,`code` ASC)
)
ENGINE = InnoDB COMMENT = 'Registry of installed modules or themes';

-- -----------------------------------------------------
-- Table `pages`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `pages` ;

CREATE TABLE IF NOT EXISTS `pages` (
  `id`  INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for page' ,
  `parent_id` INT UNSIGNED DEFAULT NULL COMMENT 'Parent for a page, 0 is root, NULL means ready to delete (unless id=0)' ,
  `user_id` INT UNSIGNED  DEFAULT NULL COMMENT 'User who created originally the page (references users.id)',
  `theme_id` INT UNSIGNED DEFAULT NULL COMMENT 'Theme that this page would follow (references modules.id)',
  `url_code` VARCHAR(100) NOT NULL COMMENT 'URL alias at that level (no slashes allowed)',
  `title` VARCHAR(255) NOT NULL COMMENT 'Current title of this content',
  `app_root` TINYINT NOT NULL DEFAULT 0 COMMENT 'Boolean to determine if this is the root of an application, therfore no sub-pages allowed',
  `core_page` TINYINT NOT NULL DEFAULT 0 COMMENT 'Boolean to determine this needs to be protected cannot delete or remove functions',  
  `ajax_call` VARCHAR(255) NULL COMMENT 'Function to call BEFORE loading the page',
  `render_call` VARCHAR(255) NULL COMMENT 'Function to call WHILE loading the page',
  `created` DATETIME NOT NULL COMMENT 'Created date',
  `activated` DATETIME DEFAULT NULL COMMENT 'Optional date for the page to go live',
  `deactivated` DATETIME DEFAULT NULL COMMENT 'Optional date for user to retract the content (or to reflect "deleted" items)',
  UNIQUE KEY (`parent_id`,`url_code`) ,
  PRIMARY KEY (`id`), 
  FOREIGN KEY (`parent_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`theme_id`) REFERENCES `modules` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
)
ENGINE = InnoDB COMMENT = 'Web "pages" that can bootstrap other applications and/or contain content';

-- -----------------------------------------------------
-- Table `page_content`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `page_content` ;

CREATE TABLE IF NOT EXISTS `page_content` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier to Content',
  `page_id`  INT UNSIGNED NOT NULL COMMENT 'Reference to pages.id' ,
  `user_id` INT UNSIGNED DEFAULT NULL COMMENT 'User who edited the content (references users.id)',
  `created` DATETIME NOT NULL COMMENT 'Date when this history item was created',
  `title` VARCHAR(255) NOT NULL COMMENT 'Title of this content',
  `summary` LONGTEXT NULL COMMENT 'User inputted summary',
  `content` LONGTEXT NULL COMMENT 'User inputted comment (html)',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`page_id`,`created`),
  FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE  
)
ENGINE = InnoDB COMMENT = 'Content for webpages';

-- -----------------------------------------------------
-- Table `menus`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `menus` ;

CREATE TABLE IF NOT EXISTS `menus`(
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier to Menu item',
  `parent_id` INT UNSIGNED DEFAULT NULL COMMENT 'Parent menu item, 0 if at root',
  `sortorder`   INT(11) NOT NULL DEFAULT 0 COMMENT 'Allows a site admin to sort menu items',
  `name` VARCHAR(255) NOT NULL COMMENT 'Title or name of menu item',
  `page_id` INT UNSIGNED DEFAULT NULL COMMENT 'Reference to pages.id',
  `external_link` VARCHAR(255) DEFAULT NULL COMMENT 'External link in lieu of Page ID',
  `created` DATETIME NOT NULL COMMENT 'Date when this menu item was created',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`parent_id`, `name`),
  FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
)
ENGINE = InnoDB COMMENT 'Creates menus and their Menu Links';

-- -----------------------------------------------------
-- Table `page_roles`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `page_roles` ;

CREATE TABLE IF NOT EXISTS `page_roles` (
  `role_id` INT UNSIGNED NOT NULL COMMENT 'Reference to roles.id',
  `page_id` INT UNSIGNED NOT NULL COMMENT 'Reference to pages.id',
  PRIMARY KEY (`role_id`, `page_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
)
ENGINE = InnoDB COMMENT 'Simple page permissions for roles';

-- -----------------------------------------------------
-- Table `page_groups`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `page_groups` ;

CREATE TABLE IF NOT EXISTS `page_groups` (
  `group_id` INT UNSIGNED NOT NULL COMMENT 'Reference to groups.id',
  `page_id` INT UNSIGNED NOT NULL COMMENT 'Reference to pages.id',
  PRIMARY KEY (`group_id`, `page_id`),
  FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
)
ENGINE = InnoDB COMMENT 'Simple page permissions for groups';

-- -----------------------------------------------------
-- Table `files`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `files` ;

CREATE TABLE IF NOT EXISTS `files` (
  `id`  INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique file identifier',
  `user_id` INT UNSIGNED  DEFAULT NULL COMMENT 'User who created uploaded the file (references users.id)',
  `basename` VARCHAR(255) NOT NULL COMMENT 'File basename (as uploaded)',
  `path` VARCHAR(255) NOT NULL COMMENT 'File name adjusted, to prevent repeats',
  `size` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'File size',
  `mimetype` VARCHAR(255) COMMENT 'File type',
  `uploaded` DATETIME NOT NULL COMMENT 'Date uploaded',
  `title` VARCHAR(255) NULL COMMENT 'Optional title',
  `caption` TEXT NULL COMMENT 'Optional caption text',
  PRIMARY KEY (`id`),
  UNIQUE INDEX (`path`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
)
ENGINE = InnoDB COMMENT 'Files registry';

-- -----------------------------------------------------
-- Table `file_roles`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `file_roles` ;

CREATE TABLE IF NOT EXISTS `file_roles` (
  `role_id` INT UNSIGNED NOT NULL COMMENT 'Reference to roles.id',
  `file_id` INT UNSIGNED NOT NULL COMMENT 'Reference to files.id',
  PRIMARY KEY (`role_id`, `file_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
)
ENGINE = InnoDB COMMENT 'Simple file permissions for roles';

-- -----------------------------------------------------
-- Table `file_groups`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `file_groups` ;

CREATE TABLE IF NOT EXISTS `file_groups` (
  `group_id` INT UNSIGNED NOT NULL COMMENT 'Reference to groups.id',
  `file_id` INT UNSIGNED NOT NULL COMMENT 'Reference to files.id',
  PRIMARY KEY (`group_id`, `file_id`),
  FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`file_id`)  REFERENCES `files` (`id`)  ON DELETE CASCADE ON UPDATE CASCADE
)
ENGINE = InnoDB COMMENT 'Simple file permissions for groups';


