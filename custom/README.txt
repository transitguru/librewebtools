Place any custom modules or themes in the subdirectories of their repective
directories, to aid in upgrading the core without affecting custom files. By
default, they would be gitignored, but you can change that if you desire
tracking your entire project within one git repo.

- modules - Place any custom modules in subdirectories of this directory. By 
  default, git does not track changes in here (but you are welcome to put this 
  in your git repository if you want your entire project in one git repo). Any 
  object-oriented classes within these modules can safely inherit core classes. 
  Inheritance within any module is possible, but not between two modules. It is 
  also not recommended to attempt to inherit classes from core modules (as 
  opposed to the core classes) as optional modules may not be enabled, which can
  potentially cause an unrecoverable error upon loading of the class.

- themes - Place any custom themes in subdirectories of this directory. By 
  default, git does not track changes in here (but you are welcome to put this 
  in your git repository if you want your entire project in one git repo).

