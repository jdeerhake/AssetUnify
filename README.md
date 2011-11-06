AssetUnify - asset packaging for PHP.
====================================

Configration
------------

Create a file at DOCUMENT_ROOT/config/assets.json.  Define packages using JSON like so:

    {
      "scripts" : {
        "packages" : {
          "cats" : ["cheetah", "leopard"],
          "dogs" : ["greyhound", "shephard"]
        }
      },
      "stylesheets" : {
        "packages" : {
          "cats" : ["cheetah", "leopard"],
          "dogs" : ["greyhound", "shephard"]
        }
      }
    }

Include a "directory" key under either scripts or stylesheet will set the directory where they are located (relative to DOCUMENT_ROOT, defaults are javascripts and stylesheets, respectively):

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

Example page:

    <?php
    require_once($_SERVER['document_root'] . "lib/asset_unify.php");
    AssetUnify\Packager::$env = "development"; //Currently required to function. Instructs it to just include each file as a separate tag
    ?>
    <!doctype html>
    <html>
      <head>
        <?= AssetUnify\include_stylesheets("dogs"); //Can pass just string name of a package ?>
      </head>
      <body>
        ...
        <?= AssetUnify\include_scripts(array("dogs", "cats")); //Or an array naming multiple packages ?>
      </body>
    </html>



Example file structure
----------------------

File layout used for the above examples.


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

