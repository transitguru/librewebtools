# Modules

The subdirectories contain modules that are officially supported for the
LibreWebTools (LWT) distribution. The minimum files shown below are required
for LWT to use when enabled. Each subdirectory would contain the following:

- PHP files: The important ones to include are the following:
    - `bootstrap.php`: LWT includes this file, it use for including any other
      files that may be needed for the operation of the module. It is
      recommended to use object oriented programming and use the reserved `LWT`
      namespace for official LWT code.
    - `template.php`: LWT looks for this file for templating
- JS files: All JavaScript files are included at this directory level. All JS
  files should be wrapped up into classes within the `LWT` namespace object.
- CSS files: All CSS stylesheets are included at this directory level.
- info.json: Information about the module to properly register it to LWT

Additional files can be added and may be referenced using the `include()` or
`require_once()` function in `bootstrap.php` or `template.php`. Use of
subdirectories such as `classes` or `includes` is recommended to keep the root
level clean as new rules may develop in the future on automatic inclusion of
files at the top-level directory of modules.

