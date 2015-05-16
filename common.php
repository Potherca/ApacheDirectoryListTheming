<?php

$aConfig = [
    "theme" => "default",
    "readmePrefixes" => ["readme","README","ReadMe"],
    "readmeExtensions" => [".html",".md",".txt"],
    "assets" => []
];

$bUseBootstrap = false;

$sConfigFile = 'config.json';
if (is_file($sConfigFile)) {

    $bUseBootstrap = true;

    if (!is_readable($sConfigFile)) {
        throw new Exception("Could not read configuration file");
    } else {
        $aConfig = array_merge(
            $aConfig,
            json_decode(file_get_contents($sConfigFile), true)
        );
    }
}

if ($bUseBootstrap === true
    && is_dir(__DIR__ . '/vendor/bower-asset/bootswatch/' . $aConfig['theme'])
) {
    $sThemeDir = 'vendor/bower-asset/bootswatch/' . $aConfig['theme'] . '/';
} elseif (is_dir(__DIR__ . '/themes/' . $aConfig['theme'])) {
    $sThemeDir = 'themes/' . $aConfig['theme'] . '/';
} else {
    throw new Exception('Could not find theme directory "' . $aConfig['theme'] . '"');
}


$aAssets = [
    'css' => [
        getAssetPath('bootstrap.css', $sThemeDir),
        getAssetPath('table.css', $sThemeDir),
        getAssetPath('thumbnails.css', $sThemeDir),
    ],
    'js' => [
        'vendor/bower-asset/jquery/dist/jquery.js',
        getAssetPath('functions.js', $sThemeDir),
    ],
];

if ($bUseBootstrap === true) {
    array_unshift(
        $aAssets['css'],
        'vendor/bower-asset/bootstrap/dist/css/bootstrap.min.css',
        'vendor/bower-asset/bootstrap/dist/css/bootstrap-theme.min.css'
    );
}

$aAssets = array_merge_recursive($aAssets, $aConfig['assets']);

if (isset($_SERVER['WEB_ROOT'])) {
    $sRoot = $_SERVER['WEB_ROOT'];
} elseif(is_dir($_SERVER['DOCUMENT_ROOT'])){
    $sRoot = $_SERVER['DOCUMENT_ROOT'];
} else {
    $sRoot = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
}

$sCurrentWebDir = $_SERVER['REQUEST_URI'];
$sCurrentRealDir = urldecode($sRoot . $sCurrentWebDir);

if (strpos($sCurrentRealDir,'?') !== false) {
    $sCurrentRealDir = substr($sCurrentRealDir,0,strpos($sCurrentRealDir,'?'));
}#if

/* Sort Out Readme Files */
$sReadmeHtml = '';

foreach($aConfig['readmePrefixes'] as $t_sPrefix){
    foreach($aConfig['readmeExtensions'] as $t_sExtension){
        $sReadMeFileName = $t_sPrefix . $t_sExtension;
        $sReadMeFilePath = $sCurrentRealDir . urldecode($sReadMeFileName);

        if(file_exists($sReadMeFilePath)){
            $sReadmeContent = file_get_contents($sReadMeFilePath);
            if($t_sExtension === '.md'){
                require 'vendor/autoload.php';
				$converter = new League\CommonMark\CommonMarkConverter\CommonMarkConverter();
                $sReadmeHtml = $converter->convertToHtml($sReadmeContent);
            }
            elseif($t_sExtension === '.txt'){
                $sReadmeHtml = '<div style="white-space: pre-wrap;">'.$sReadmeContent.'</div>';
            }
            else{
                $sReadmeHtml = $sReadmeContent;
            }#if

            break;
        }#if
    }#foreach
}#foreach

/* Set Title */
$sUrl = urldecode($_SERVER['REQUEST_URI']);
if(strpos($sUrl,'?') !== false){
    $sUrl = substr($sUrl,0,strpos($sUrl,'?'));
}#if


$sIndex = urldecode($_SERVER['REQUEST_URI']);
$sIndexHtml = '<li><a href="http://' . $_SERVER['SERVER_NAME'] .'">' . $_SERVER['SERVER_NAME'] . '</a></li>';

if($_SERVER['REQUEST_URI'] !== '/'){
    $aParts = explode('/', trim($sUrl, '/'));
    $iCount = count($aParts) - 1;
    $sUrl = 'http://' . $_SERVER['SERVER_NAME'];

    foreach($aParts as $t_iIndex => $t_sPart){
        if(!empty($t_sPart)){
            $sIndex .= '/'.$t_sPart;

            $sUrl .= '/' . urlencode($t_sPart);
            $sIndexHtml .= '<li><a';
            if ($t_iIndex === $iCount) {
                $sIndexHtml .= ' class="active"';
            } else {
                $sIndexHtml .= ' class="text-muted"';
            }
            $sIndexHtml .= ' href="' . $sUrl . '">'.$t_sPart.'</a></li>';
        }#if
    }#foreach
}#if

/* Sort out extension filter and thumbnail for images/pdf/etc. */
$aExtensions = array();
$aImages = array();
foreach (scandir($sCurrentRealDir)as $t_sFileName) {
    if( ! is_dir($sCurrentRealDir.$t_sFileName)
        AND strrpos($t_sFileName,'.')!== false
    ){
        $sExtension = substr($t_sFileName, strrpos($t_sFileName,'.'));
        $sExtension = strtolower($sExtension);

        $aExtensions[$sExtension] = substr($sExtension,1);

        if(in_array(
              $sExtension
            , array(
                  '.bmp'
                , '.eps'
                , '.gif'
                , '.ico'
                , '.jpg'
                , '.png'
                , '.ps'
                , '.pdf'
                , '.psd'
                , '.svg'
                , '.tiff'
                )
            )
        ){
            $aImages[$sCurrentWebDir . $t_sFileName] = substr($sExtension,1);
        }#if
    }#if
}#foreach
natcasesort($aExtensions);

$sThumbnailHtml = '';
if(!empty($aImages)){
    $sThumbnailHtml .= '<ul class="thumbnails polaroids">';
    foreach($aImages as $t_sImage => $t_sExtension){
        $sThumbnailHtml .='<li class="'.$t_sExtension.'"><a href="'.$t_sImage.'" title="'.basename($t_sImage).'"><img src="/Directory_Listing_Theme/thumbnail.php?file=' . urlencode($t_sImage) . '" /></a>';
    }#foreach
    $sThumbnailHtml .= '</ul>';
}#if

/******************************************************************************/
$sReadme = '';
$aConfig['readmeExtensions'] = array('.html','.md','.txt');
foreach($aConfig['readmeExtensions'] as $t_sExtension){
    $sReadMeFileName = 'readme-footer' . $t_sExtension;
    $sReadMeFilePath = urldecode($_SERVER['DOCUMENT_ROOT'].$_SERVER['REQUEST_URI'].$sReadMeFileName);

    if(file_exists($sReadMeFilePath)){
        $sReadmeContent = file_get_contents($sReadMeFilePath);
        if($t_sExtension === '.md'){
            require 'markdown.php';
            $sReadmeContent = markdown($sReadmeContent);
        }
        elseif($t_sExtension === '.txt'){
            $sReadmeContent = '<pre>'.$sReadmeContent.'</pre>';
        }
        $sReadme = '<div class="page">'.$sReadmeContent.'</div>';
        break;
    }#if
}#foreach

function getAssetPath($p_sFile, $sThemeDir) {
    $sPath = '';

    $aParts = explode('.', $p_sFile);
    array_splice($aParts, -1, 0, array('min'));

    if (is_file(__DIR__ . '/' . $sThemeDir . $p_sFile)) {
        $sPath = $sThemeDir . $p_sFile;
    } elseif (is_file(__DIR__ . '/' . $sThemeDir . implode('.', $aParts))) {
        $sPath = $sThemeDir . implode('.', $aParts);
    } elseif (is_file(__DIR__ . '/' . '/themes/default/' . $p_sFile)) {
        $sPath = '/themes/default/' . $p_sFile;
    } else {
        throw new Exception('Could not find asset "' . $p_sFile . '"');
    }

    return $sPath;
}

/*EOF*/
