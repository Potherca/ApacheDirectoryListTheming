<?php

//@WARNING: requires Imagick!

//@FIXME:	This is a *very* basic example! Check and validations still need to be done.

if(!is_dir($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
}

$sRootDirectory = $_SERVER['DOCUMENT_ROOT'];

if (isset($_SERVER['THUMBNAIL_DIRECTORY'])) {
    $sThumbDirectory = $_SERVER['THUMBNAIL_DIRECTORY'];
} else {
    $sCurrentDirectory = dirname(__FILE__) . '/';
    $sThumbDirectory =  $sCurrentDirectory. '.thumbs/';
}

if(isset($_GET['DEBUG']) || isset($_GET['debug']) ) {
    define('DEBUG', true);
} else {
    define('DEBUG', false);
}

if(isset($_GET['file'])) {
    $sFilePath = $sRootDirectory . $_GET['file'];
}
else {
    $sFilePath = $sThumbDirectory . 'empty.png';
}

try {
    if(strlen($sFilePath) - strrpos($sFilePath, '.svg') === 4) {
        // Display SVG directly
        header('Content-Type: image/svg+xml');
        readfile($sFilePath);
    } else {
        outputThumbnail($sFilePath, $sThumbDirectory, 200);
    }
} catch(Exception $eAny) {
    buildExceptionImage($eAny);
}

function outputThumbnail($p_sFilePath, $p_sThumbDirectory, $p_iImageWidth, $p_sOutputType = 'png') {

    $bIOnlyKnowHowToWorkImagickByUsingTheCommandline = true;

    if(!is_dir($p_sThumbDirectory)) {
        throw new Exception('The directory to store the Thumbnails in does not exist at "'.$p_sThumbDirectory.'"');
    }
    elseif(!is_writable($p_sThumbDirectory)) {
        throw new Exception('The directory to store the Thumbnails is not writable "'.$p_sThumbDirectory.'"');
    }

    //@TODO: check if file is on of following: '.gif', '.jpg', '.png', '.svg', '.tiff', '.ps', '.pdf', '.bmp', '.eps', '.psd',
    /* for .swf support look at http://www.swftools.org/ ?
     * for html preview, it is already possible to do to pdf... http://code.google.com/p/wkhtmltopdf/
     * although it's a bit of a hack that might work.
     */

    $sSaveFileName = sanitize($p_sFilePath) . '.' . $p_sOutputType;
    $sSaveFilePath = $p_sThumbDirectory . $sSaveFileName;

    if(class_exists('Imagick')) {
        $bRefresh=false;
        if($bRefresh === true && is_file($sSaveFilePath)) {
            unlink($sSaveFilePath);
        }#if

        if(!is_file($sSaveFilePath)) {
            $aSize = getimagesize($p_sFilePath);
            // Create thumb for file
            if($bIOnlyKnowHowToWorkImagickByUsingTheCommandline === true) {
                // Save Locally
                $iImageWidth = $p_iImageWidth;
                if(isset($aSize[0]) && $aSize[0] <= $p_iImageWidth) {
                    $iImageWidth = $aSize[0];
                }#if

                $sCommand =
                    'convert \'' . urldecode($p_sFilePath) . '[0]\''
                        . ' -colorspace RGB'
                        . ' -geometry ' . $iImageWidth
                        . ' \'' . $sSaveFilePath . '\''
                ;

                $aResult = executeCommand($sCommand);

                if($aResult['return'] !== 0) {
                    throw new Exception('Error executing '.$aResult['stderr'] . '(full command : "'. $aResult['stdin'] .'")');
                }#if
            }
            else {
                // This probably still needs some fixing before it'll actually work :-S
                $oImagick = new imagick( $p_sFilePath.'[0]');      // Read First Page of PDF

                $oImagick->setResolution($p_iImageWidth, 0);  // If 0 is provided as a width or height parameter, aspect ratio is maintained

                $oImagick->setImageFormat( "png" );         // Convert to png
                header('Content-Type: image/png');          // Send out

                echo $oImagick;
            }#if
        }#if
        $oImagick = new imagick($sSaveFilePath);      // Read First Page
        header('Content-Type: image/png');          // Send out
        echo $oImagick;
    } /** @noinspection SpellCheckingInspection */
    elseif(function_exists('imagettfbbox') === true) {
        throw new Exception('Currently only Imagick is supported');
    }else {
        throw new Exception('Either Imagick or the GD2 library need to installed on the server');
    }#if
}

//@TODO: This needs to be placed somewhere more suitable
/*
 * Because exec/sytem/etc. Are a bit lame in giving error feedback a workaround
 * is required. Instead of executing commands directly, we open a stream, write
 * the command to the stream and read whatever comes back out of the pipes.
 *
 * For general info on Standard input (stdin), Standard output (stdout) and
 * Standard error (stderr) please visit:
 *      http://en.wikipedia.org/wiki/Standard_streams
 */
function executeCommand($p_sCommand, $p_sInput='') {

    $rProcess = proc_open(
        $p_sCommand
        , array(
              0 => array('pipe', 'r')
            , 1 => array('pipe', 'w')
            , 2 => array('pipe', 'w'))
        , $aPipes
    );
    fwrite($aPipes[0], $p_sInput);
    fclose($aPipes[0]);


    $sStandardOutput = stream_get_contents($aPipes[1]);
    fclose($aPipes[1]);

    $sStandardError = stream_get_contents($aPipes[2]);
    fclose($aPipes[2]);

    $iReturn=proc_close($rProcess);

    return array(
          'stdin'  => $p_sCommand
        , 'stdout' => $sStandardOutput
        , 'stderr' => $sStandardError
        , 'return' => $iReturn
    );
}


function buildExceptionImage(\Exception $ex) {
    $aMessage = array();
    if (DEBUG === true) {
        $aMessage[] = 'Uncaught ' . get_class($ex);
        $aMessage[] = ' in ' . basename($ex->getFile()) . ':' . $ex->getLine();
        $aMessage[] = "\n";
    }
    $aMessage[] = ' Error: ' . $ex->getMessage() . ' ';

    $message = implode("\n", $aMessage);

    $rImage = drawText($message, 10, '255.0.0.0');

    imagerectangle($rImage, 0, 0, \imagesx($rImage) - 1, \imagesy($rImage) - 1, imagecolorallocatealpha($rImage, 255, 0, 0, 0));

    header('Content-Type: image/png');
    imagepng($rImage);
    imagedestroy($rImage);
}

function drawText($p_sText, $p_dSize, $p_sRgba, $p_iAngle=0, $p_sFontFile=null) {
    $text  = (string) $p_sText;
    $size  = (float)  $p_dSize;
    $angle = (int)    $p_iAngle;

    if($p_sFontFile === null) {
        $sFontFile = __DIR__ . '/DroidSans.ttf';
    }
    else {
        $sFontFile = $p_sFontFile;
    }

    if(!is_file($sFontFile)) {
        echo 'Could not find font ' . $sFontFile;
        die;
    }

    if (1 !== preg_match('~^([\d]+?)\.([\d]+?)\.([\d]+?)\.([\d]+?)$~', $p_sRgba)) {
        $sRgba = '0.0.0.0';
    }
    else {
        $sRgba = $p_sRgba;
    }
    list($r, $g, $b, $a) = explode('.', $sRgba);

    $size = max(8, min(127, intval($size)));
    $r = max(0, min(255, intval($r)));
    $g = max(0, min(255, intval($g)));
    $b = max(0, min(255, intval($b)));
    $a = max(0, min(127, intval($a)));

    $box = imagettfbbox($size, $angle, $sFontFile, $text);
    $width  = abs($box[4]) + abs($box[0]);// - $box[6];
    $height = abs($box[3]) + abs($box[7]);// - $box[1];
    $x = 0; // $width;
    $y = $height - $box[1]; // $height;
    $image = imagecreatetruecolor($width, $height);
    imagesavealpha($image, true);
    imagealphablending($image, true);

    $transp = imagecolorallocatealpha($image, 255, 0, 0, 127);
    imagefill($image, 0, 0, $transp);

    $black = imagecolorallocatealpha($image, $r, $g, $b, $a);
    imagettftext($image, $size, $angle, $x, $y, $black, $sFontFile, $text);

    return $image;
}

function sanitize($p_sName){
    return preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', '__'), $p_sName);
}

#EOF
