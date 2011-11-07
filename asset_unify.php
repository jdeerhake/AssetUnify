<?php
namespace AssetUnify;

/*******************
 *  Packager
 *******************/
class Packager {
  public static $root;
  public static $defaults;
  public static $config;
  public static $script_packages = array();
  public static $stylesheet_packages = array();
  public static $web_root_dir;
  public static $env = "production";
}

Packager::$defaults = array(
  "root" => $_SERVER['DOCUMENT_ROOT'],
  "config_file" => array("config", "assets.json"),
  "web_root" => array(""),
  "scripts" => array(
    "directory" => array("javascripts"),
    "output" => array("assets"),
    "minify" => false
  ),
  "stylesheets" => array(
    "directory" => array("stylesheets"),
    "output" => array("assets"),
    "minify" => false
  ),
);



/*******************
 *  Read config
 *******************/
call_user_func(function() {
  $config = new File(Packager::$defaults['root'], Packager::$defaults['config_file']);

  $config = json_decode($config->contents(), true);
  if(is_null($config)) trigger_error("Error parsing config file.", E_USER_ERROR);
  $config = array_merge_recursive(Packager::$defaults, $config);
  Packager::$config = $config;

  Packager::$web_root_dir = File::join_paths(Packager::$config['root'], Packager::$config['web_root']);
});



/*******************
 *  File
 *******************/
class File {
  public static function dir_from_array($arr) {
    return join(DIRECTORY_SEPARATOR, $arr);
  }

  public static function join_paths($begin, $end) {
    return File::dir_from_array(array_merge((array)$begin, (array)$end));
  }

  public static function ensure_leading_slash($dir) {
    if(substr($dir, 0, 1) != DIRECTORY_SEPARATOR) {
      return DIRECTORY_SEPARATOR . $dir;
    } else {
      return $dir;
    }
  }

  function __construct($base, $relative_path) {
    if(is_array($relative_path)) {
      $this->relative_path = File::dir_from_array($relative_path);
    } else {
      $this->relative_path = $relative_path;
    }

    if(is_array($base)) {
      $this->base = File::dir_from_array($base);
    } else {
      $this->base = $base;
    }
  }

  function path() {
    return File::join_paths($this->base, $this->relative_path);
  }

  function contents() {
    return file_get_contents($this->path());
  }

  function url() {
    return File::ensure_leading_slash($this->relative_path);
  }
}


/*******************
 *  ScriptFile
 *******************/
class ScriptFile extends File {
  function __construct($base, $path) {
    parent::__construct($base, $path);
    if(substr($this->relative_path, -3, 3) != ".js") $this->relative_path = $this->relative_path . ".js";
  }

  function tag() {
    return "<script type='text/javascript' src='" . $this->url() . "'></script>";
  }

}


/*******************
 *  StylesheetFile
 *******************/
class StylesheetFile extends File {
  function __construct($base, $path) {
    parent::__construct($base, $path);
    if(substr($this->relative_path, -4, 4) != ".css") $this->relative_path = $this->relative_path . ".css";
  }

  function tag() {
    return "<link rel='stylesheet' type='text/css' media='all' href='" . $this->url() . "' />";
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

  function tag() {
    if(Packager::$env == "development") {
      $tags = array();

      foreach($this->files as $file) {
        array_push($tags, $file->tag());
      }

      return join("\n", $tags);
    }
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

/**************************
 *  Init script packages
 **************************/
call_user_func(function() {
  $script_packages = array();

  foreach(Packager::$config["scripts"]["packages"] as $name => $paths) {
    $current_package = $script_packages[$name] = new ScriptPackage(null);

    foreach($paths as $path) {
      $file = new ScriptFile(Packager::$web_root_dir, File::join_paths(Packager::$config["scripts"]["directory"], $path));
      $current_package->push($file);
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
    $current_package = $stylesheet_packages[$name] = new stylesheetPackage(null);

    foreach($paths as $path) {
      $file = new stylesheetFile(Packager::$web_root_dir, File::join_paths(Packager::$config["stylesheets"]["directory"], $path));
      $current_package->push($file);
    }
  }

  Packager::$stylesheet_packages = $stylesheet_packages;
});

/*****************************
 *  Initialize
 *****************************/


/*****************************
 *  Script API functions
 *****************************/

function is_javascript_package($name) {
  return isset(Packager::$script_packages[$name]);
}


function include_scripts($package_names, $options = array("type" => "external")) {
  $packages = array();
  foreach((array)$package_names as $package_name) {
     if(is_javascript_package($package_name)) array_push($packages, Packager::$script_packages[$package_name]);
  }

  $output = array();
  foreach($packages as $package) {
    if($options["type"] == "inline") {
      array_push($output, $package->inline_contents());
    } else {
      array_push($output, $package->tag());
    }
  }

  return join("\n", $output);
}


/*****************************
 *  Stylesheet API functions
 *****************************/

function is_stylsheet_package($name) {
  return isset(Packager::$stylesheet_packages[$name]);
}


function include_stylesheets($package_names, $options = array("type" => "external")) {
  $packages = array();
  foreach((array)$package_names as $package_name) {
     if(is_stylsheet_package($package_name)) array_push($packages, Packager::$stylesheet_packages[$package_name]);
  }

  $output = array();
  foreach($packages as $package) {
    if($options["type"] == "inline") {
      array_push($output, $package->inline_contents());
    } else {
      array_push($output, $package->tag());
    }
  }

  return join("\n", $output);
}

