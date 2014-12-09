This directory and its subdirectories contain the core files for the
LibreWebTools distribution.

- classes - contains core classes which are not associated to any 
  optional modules and are essential to loading the site. Any other 
  module-specific classes will reside within its module directory and will be 
  loaded after the core classes, to allow for potential inheritance 
  of core classes.
  
- design - contains design files that don't have any impact on the actual
  running of the site. It is here to provide an understanding on how the tool
  is designed.
  
- modules - contains  add-on modules that are part of the LibreWebTools 
  project and are deemed as "optional". Any object-oriented classes within these
  modules can safely inherit core classes. Inheritance within the module, if 
  properly sequenced, is possible but not between two modules.

- sql - contains the empty schema to be used to create the site.

- themes - contains available themes (essentially layout templates) for 
  the base/core distribution of LibreWebTools. One or more of these themes can 
  be enabled on a per-page basis.
  
- bootstrap.php - boots the entire site and loads all classes and modules that
  are minimal requirements for the site. Any additional modules would also be
  loaded, but are based on the database registry containing the module/theme
  information
  
- settings.php - By default not git tracked, but can be tracked in your own
  git repository (by removing the entry in .gitignore). This file is essential
  to connecting to the database, and will be used when installing the site and
  adding the relevant databases.
  
- settings.php.example - Example settings file, doesn't do anything but track
  any changes from LibreWebTools upstream


