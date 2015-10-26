<?php

namespace Potherca\Apache\Modules\AutoIndex;

use PHPTAL;

require 'vendor/autoload.php';

function run($p_sMethod) {
    $sTemplate = 'src/template.html';

    $oDirectoryListing = new DirectoryListing($_SERVER, array_merge([], $_COOKIE, $_REQUEST));
    $PHPTAL = new PHPTAL($sTemplate);
    $oTemplate = new Template($PHPTAL);

    return $oDirectoryListing->{$p_sMethod}($oTemplate);
}

/*EOF*/
