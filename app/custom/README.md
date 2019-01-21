# Custom Modules

The subdirectories contain modules that are not officially supported for the
LibreWebTools (LWT) distribution. These are not (by default) tracked by git and
it is likely that each directory within this directory is developed by third
parties. The minimum files shown below are required to be provided for LWT to
use when enabled. Each subdirectory would contain the following:

- PHP files: The important ones to include are the following:
    - `bootstrap.php`: LWT includes this file, use it for including any other
      files that may be needed for the operation of the module. It is
      recommended to use object oriented programming and use a namespace that is
      not using the reserved `LWT` namespace.
    - `template.php`: LWT looks for this file for templating
- JS files: All JavaScript files are included at this directory level. All JS
  code should be wrapped up into classes within a namespace object of your
  choosing. The `LWT` global object is reserved and should not be used.
- CSS files: All CSS stylesheets are included at this directory level.
- info.json: Information about the module to properly register it to LWT

Additional files can be added and may be referenced using the `include()` or
`require_once()` function in `bootstrap.php` or `template.php`. Use of
subdirectories such as `classes` or `includes` is recommended to keep the root
level clean as new rules may develop in the future on automatic inclusion of
files at the top-level directory of modules.

