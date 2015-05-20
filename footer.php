<?php

namespace Potherca\Apache\Modules\AutoIndex;

require 'vendor/autoload.php';

$directoryListing = new DirectoryListing($_SERVER);

echo $directoryListing->footer();

/*EOF*/
