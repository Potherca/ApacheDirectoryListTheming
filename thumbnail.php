<?php

//@WARNING: requires Imagick!

//@FIXME:	This is a *very* basic example! Check and validations still need to be done.
//@TODO:	Replace md5 with base64encode so a thumb can be traced back to it parent
//@TODO: 	Support AVI/MPEG thumbs
//@TODO: 	Support SWF thumbs
//@TODO: 	Support HTML thumbs
//@TODO:	Make an animated GIF for multi pages PDFs, etc?
//@TODO:	Create GD compatible version
//@TODO:	Use Images for exceptions...
//@DONE: 	Only resize if image size is larger than 200x200
if(!is_dir($_SERVER['DOCUMENT_ROOT'])){
    $_SERVER['DOCUMENT_ROOT'] = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
}

$sRootDirectory = $_SERVER['DOCUMENT_ROOT'];
$sCurrentDirectory = dirname(__FILE__) . '/';
$sThumbDirectory = $sCurrentDirectory . 'thumbs/';

if(isset($_GET['file'])){
    $sFilePath = $sRootDirectory . $_GET['file'];
}
else{
    $sFilePath = $sThumbDirectory . 'empty.png';
}

outputThumbnail($sFilePath, $sThumbDirectory, 200);

function outputThumbnail($p_sFilePath, $p_sThumbDirectory, $p_iImageWidth, $p_sOutputType = 'png'){

    $bIOnlyKnowHowToWorkImagickByUsingTheCommandline = true;

    if(!is_dir($p_sThumbDirectory)){
        throw new Exception('The directory to store the Thumbnails in does not exist at "'.$p_sThumbDirectory.'"');
    }
    elseif(!is_writable($p_sThumbDirectory)){
        throw new Exception('The directory to store the Thumbnails is not writable "'.$p_sThumbDirectory.'"');
    }

    //@TODO: check if file is on of following: '.gif', '.jpg', '.png', '.svg', '.tiff', '.ps', '.pdf', '.bmp', '.eps', '.psd',
    /* for .swf support look at http://www.swftools.org/ ?
     * for html preview, it is already possible to do to pdf... http://code.google.com/p/wkhtmltopdf/
     * although it's a bit of a hack that might work.
     */

    $sSaveFileName = md5(basename($p_sFilePath)) . '.'.$p_sOutputType;
    $sSaveFilePath = $p_sThumbDirectory . $sSaveFileName;

    if(class_exists('Imagick')){
        $bRefresh=false;
        if($bRefresh === true && is_file($sSaveFilePath)){
            unlink($sSaveFilePath);
        }#if

        if(!is_file($sSaveFilePath)){
            $aSize = getimagesize($p_sFilePath);
            // Create thumb for file
            if($bIOnlyKnowHowToWorkImagickByUsingTheCommandline === true){
                // Save Locally
                $iImageWidth = $p_iImageWidth;
                if(isset($aSize[0]) && $aSize[0] <= $p_iImageWidth){
                    $iImageWidth = $aSize[0];
                }#if

                $sCommand =
                    'convert \'' . urldecode($p_sFilePath) . '[0]\''
                        . ' -colorspace RGB'
                        . ' -geometry ' . $iImageWidth
                        . ' \'' . $sSaveFilePath . '\''
                ;

                $aResult = executeCommand($sCommand);

                if($aResult['return'] !== 0){
                    throw new Exception('Error executing '.$aResult['stderr'] . '(full command : "'. $aResult['stdin'] .'")');
                }#if
            }
            else{
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
    }else{
        echo 'Only Imagick is currently supported...';
    }#if
}
#EOF

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
function executeCommand($p_sCommand, $p_sInput=''){

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
