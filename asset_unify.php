<?php
namespace AssetUnify;

/*******************
 *  File
 *******************/
class File {
  public static function dir_from_array($arr) {
    return join(DIRECTORY_SEPARATOR, $arr);
  }

  function __construct($path) {
    if(is_array($path)) {
      $this->path = File::dir_from_array($path);
    } else {
      $this->path = $path;
    }
  }

  function contents() {
    return file_get_contents($this->path);
  }
}


/*******************
 *  ScriptFile
 *******************/
class ScriptFile extends File {
  function __construct($path) {
    parent::__construct($path);
    if(substr($this->path, -3, 3) != ".js") $this->path = $this->path . ".js";
  }
}


/*******************
 *  StylesheetFile
 *******************/
class StylesheetFile extends File {
  function __construct($path) {
    parent::__construct($path);
    if(substr($this->path, -4, 4) != ".css") $this->path = $this->path . ".css";
  }
}


/*******************
 *  Package
 *******************/
class Package {

  function __construct($files) {
    $this->files = array();
    if(is_array($files)) {
      foreach($files as $file) {
        array_push($this->files, $file);
      }
    }
  }

  function contents() {
    $contents = "";

    foreach($this->files as $file) {
      $contents .= $file->contents();
    }

    return $contents;
  }

  function push($file) {
    array_push($this->files, $file);
    return $this;
  }

  function shift($file) {
    array_shift($this->files, $file);
    return $this;
  }

}

/*******************
 *  ScriptPackage
 *******************/
class ScriptPackage extends Package {
  function contents() {
    $contents = parent::contents();

    if(Packager::$config['scripts']['minify']) {
      eval('$contents='. Packager::$config['scripts']['minify'] . '($contents);');
    }

    return $contents;
  }

  function inline_contents() {
    return "<script type='text/javascript'>\n" . $this->contents() . "\n</script>";
  }
}

/************************
 *  StylesheetPackage
 ************************/
class StylesheetPackage extends Package {
  function contents() {
    $contents = parent::contents();

    if(Packager::$config['stylesheets']['minify']) {
      eval('$contents='. Packager::$config['stylesheets']['minify'] . '($contents);');
    }

    return $contents;
  }

  function inline_contents() {
    return "<style type='text/css'>\n" . $this->contents() . "\n</style>";
  }
}

/*******************
 *  Packager
 *******************/
class Packager {
  public static $root;
  public static $defaults;
  public static $config;
  public static $script_packages = array();
  public static $stylesheet_packages = array();

}

Packager::$root = $_SERVER['DOCUMENT_ROOT'];
Packager::$defaults = array(
  "config_file" => array(Packager::$root, "config", "assets.json"),
  "scripts" => array(
    "directory" => array(Packager::$root, "public", "javascripts"),
    "output" => array(Packager::$root, "public", "assets"),
    "minify" => false
  ),
  "stylesheets" => array(
    "directory" => array(Packager::$root, "public", "stylesheets"),
    "output" => array(Packager::$root, "public", "assets"),
    "minify" => false
  ),
);



/*******************
 *  Read config
 *******************/
call_user_func(function() {
  $config = new File(Packager::$defaults['config_file']);
  $config = json_decode($config->contents(), true);
  if(is_null($config)) trigger_error("Error parsing config file.", E_USER_ERROR);
  if(isset($config['scripts']['dir'])) $config['scripts']['dir'] = array_merge((array)Packager::$root, (array)$config['scripts']['dir']);
  if(isset($config['stylesheets']['dir'])) $config['stylesheets']['dir'] = array_merge((array)Packager::$root, (array)$config['stylesheets']['dir']);
  $config = array_merge(Packager::$defaults, $config);
  Packager::$config = $config;
});


/**************************
 *  Init script packages
 **************************/
call_user_func(function() {
  $script_packages = array();
  foreach(Packager::$config["scripts"]["packages"] as $name => $paths) {
    $current_package = $script_packages[$name] = new ScriptPackage(null);
    foreach($paths as $path) {
      $current_package->push(new ScriptFile(array_merge(Packager::$config["scripts"]["dir"],(array)$path)));
    }
  }
  Packager::$script_packages = $script_packages;
});

/*****************************
 *  Init stylesheet packages
 *****************************/
call_user_func(function() {
  $stylesheet_packages = array();
  foreach(Packager::$config["stylesheets"]["packages"] as $name => $paths) {
    $current_package = $stylesheet_packages[$name] = new StylesheetPackage(null);
    foreach($paths as $path) {
      $current_package->push(new StylesheetFile(array_merge(Packager::$config["stylesheets"]["dir"],(array)$path)));
    }
  }
  Packager::$stylesheet_packages = $stylesheet_packages;
});



/*****************************
 *  Script API functions
 *****************************/

function is_javascript_package($name) {
  return isset(Packager::$script_packages[$name]);
}


function include_scripts($package_names, $options) {
  $packages = array();
  foreach((array)$package_names as $package_name) {
     if(is_javascript_package($package_name)) array_push($packages, Packager::$script_packages[$package_name]);
  }


  foreach($packages as $package) {
    echo $package->inline_contents() . "\n";
  }
}


/*****************************
 *  Stylesheet API functions
 *****************************/

function is_stylsheet_package($name) {
  return isset(Packager::$stylesheet_packages[$name]);
}


function include_stylesheets($package_names, $options) {
  $packages = array();
  foreach((array)$package_names as $package_name) {
     if(is_stylsheet_package($package_name)) array_push($packages, Packager::$stylesheet_packages[$package_name]);
  }


  foreach($packages as $package) {
    echo $package->inline_contents() . "\n";
  }
}

