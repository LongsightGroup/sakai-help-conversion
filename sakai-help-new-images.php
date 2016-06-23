#!/usr/bin/php
<?php

$imagedir = "/tmp/help/images/";
$dest = "/home/samo/dev/trunk-git/reference/library/src/webapp/image/help/en/";

$arr = scandir ($imagedir);

foreach ($arr AS $dir) {
  if ($dir == "." || $dir == "..") continue;

  $subdir = scandir ($imagedir . $dir);
  foreach ($subdir AS $sub) {
    if ($sub == "." || $sub == "..") continue;

    if (!is_file($imagedir . $dir . "/" . $sub)) {
      print "Skipping " . $imagedir . $dir . "/" . $sub . "\n";
      continue;
    }

    if (!is_file ($dest . $dir . "/" .  $sub)) {
      $cmd =  "optipng -o7  " . $imagedir . $dir . "/" . $sub;
      var_dump ($cmd);
      exec ($cmd);

      $cmd =  "pngquant --ext .png --force 64 " . $imagedir . $dir . "/" . $sub;
      var_dump ($cmd);
      exec ($cmd);

      if (!is_dir ($dest . $dir)) {
        mkdir ($dest . $dir);
      }
      copy ($imagedir . $dir . "/" . $sub, $dest . $dir . "/". $sub);
    }
  }

}
