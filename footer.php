<?php

namespace Potherca\Apache\Modules\AutoIndex;

use PHPTAL;

require 'vendor/autoload.php';

$directoryListing = new DirectoryListing($_SERVER);
$template = new Template(new PHPTAL('src/template.html'));

echo $directoryListing->footer($template);

/*EOF*/
