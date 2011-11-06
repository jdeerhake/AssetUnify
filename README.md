AssetUnify - asset packaging for PHP.
====================================

(It doesn't actually do packaging yet, but it can manage packages)

Example file structure
----------------------

File locations can be configured, but for the simplicity's sake the examples use the default file structure, outlined below:


    DOCUMENT_ROOT
    │
    ├── lib
    │   └── asset_unify.php
    │
    ├── config
    │   └── assets.json
    │
    ├── stylesheets
    │   ├── leopard.css
    │   ├── cheetah.css
    │   ├── greyhound.css
    │   └── shephard.css
    │
    ├── javascripts
    │   ├── leopard.js
    │   ├── cheetah.js
    │   ├── greyhound.js
    │   └── shephard.js
    │
    └── index.php



Configration
------------

Create a file at DOCUMENT_ROOT/config/assets.json.  Define packages using JSON like so:

    {
      "scripts" : {
        "packages" : {
          "cats" : ["cheetah.js", "leopard.js"],
          "dogs" : ["greyhound.js", "shephard.js"]
        }
      },
      "stylesheets" : {
        "packages" : {
          "cats" : ["cheetah.css", "leopard.css"],
          "dogs" : ["greyhound.css", "shephard.css"]
        }
      }
    }

File extensions are optional, and assumed to be .js/.css if omitted.  Order within each package is maintained.


Including a "directory" key under either scripts or stylesheet will set the directory where they are located (relative to DOCUMENT_ROOT, defaults are javascripts and stylesheets, respectively):

    {
      "scripts" : {
        "directory" : "/js",
        ...
      },
      "stylesheets" : {
        "directory" : "/css"
      }
    }

To run minification or any sort of modifcation on the assets, provide a "minify" key.  Its value should be the function name that the files contents should be called with. For example:

    {
      "scripts" : {
        "minify" : "run_jsmin"
        ...
      },
      ...
    }

Would cause the contents of each file to run through the global function "run_jsmin" like this:

    $file_contents = run_jsmin($file_contents);

(Soon minification will be built into AssetUnify)


Usage
-----

After including asset_unify.php, add `AssetUnify\Packager::$env = "development";`.  This sets the env mode - currently only "development" will do anything, and it will simply keep all of the scripts/stylesheets in separate tags and not run minification on them.

To include packages on the page add `echo AssetUnify\include_stylesheets("dogs");`.  This function will return a string containing appropriate tags.   You can also call this function with an array to include multiple packages at once `echo AssetUnify\include_stylesheets(array("cats", "dogs"));`.  Package order is maintained.


Optionally you can pass a second argument, a configuration object, to this function.  Currently the only meaningful key is "type" which when given the value "inline" will render the asset in the page - `echo AssetUnify\include_stylesheets("dogs", array("type" => "inline"));`.

Example page:

    <?php
    require_once($_SERVER['document_root'] . "lib/asset_unify.php");
    AssetUnify\Packager::$env = "development"; //Currently required to function. Instructs it to just include each file as a separate tag
    ?>
    <!doctype html>
    <html>
      <head>
        <?= AssetUnify\include_stylesheets("dogs"); //Can pass just string name of a package ?>
        <?= AssetUnify\include_stylesheets("cats", array("type" => "inline")); //Render in-page ?>
      </head>
      <body>
        ...
        <?= AssetUnify\include_scripts(array("dogs", "cats")); //Or an array naming multiple packages ?>
      </body>
    </html>

Example output:

    <!doctype html>
    <html>
      <head>
        <link rel='stylesheet' type='text/css' media='all' href='/stylesheets/greyhound.css' />
        <link rel='stylesheet' type='text/css' media='all' href='/stylesheets/shephard.css' />
        <style type='text/css'>
          .cheetah { color: blue; }
          .leopard { font-size: 20px; }
        </style>
      </head>
      <body>
        ...
        <script type='text/javascript' src='/javascripts/greyhound.js'></script>
        <script type='text/javascript' src='/javascripts/shephard.js'></script>
        <script type='text/javascript' src='/javascripts/cheetah.js'></script>
        <script type='text/javascript' src='/javascripts/leopard.js'></script>
      </body>
    </html>

