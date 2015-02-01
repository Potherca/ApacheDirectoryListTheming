<!DOCTYPE html>
<?php
    if(isset($_SERVER['WEB_ROOT'])){
        $sRoot = $_SERVER['WEB_ROOT'];
    }
    elseif(is_dir($_SERVER['DOCUMENT_ROOT'])){
        $sRoot = $_SERVER['DOCUMENT_ROOT'];
    }
    else {
        $sRoot = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
    }

    $sCurrentWebDir = $_SERVER['REQUEST_URI'];
	$sCurrentRealDir = urldecode($sRoot . $sCurrentWebDir);

    if(strpos($sCurrentRealDir,'?') !== false){
        $sCurrentRealDir = substr($sCurrentRealDir,0,strpos($sCurrentRealDir,'?'));
    }#if

    /* Sort Out Readme Files */
	$sReadme = '';
    $aReadMePrefixes = array('readme','README','ReadMe');
    $aReadMeExtensions = array('.html','.md','.txt');

    foreach($aReadMePrefixes as $t_sPrefix){
        foreach($aReadMeExtensions as $t_sExtension){
            $sReadMeFileName = $t_sPrefix . $t_sExtension;
            $sReadMeFilePath = $sCurrentRealDir . urldecode($sReadMeFileName);

            if(file_exists($sReadMeFilePath)){
                $sReadmeContent = file_get_contents($sReadMeFilePath);
                if($t_sExtension === '.md'){
                    require 'markdown.php';
                    $sReadmeContent = markdown($sReadmeContent);
                }
                elseif($t_sExtension === '.txt'){
                    $sReadmeContent = '<pre>'.$sReadmeContent.'</pre>';
                }
                else{
                    // default case has already been handled
                }#if

                $sReadme = '<div class="page readme">'.$sReadmeContent.'</div>';
                break;
            }#if
        }#foreach
    }#foreach

    /* Set Title */
    $sIndex = urldecode($_SERVER['REQUEST_URI']);

	$sUrl = urldecode($_SERVER['REQUEST_URI']);
    if(strpos($sUrl,'?') !== false){
		$sUrl = substr($sUrl,0,strpos($sUrl,'?'));
    }#if


    $sIndex = '';
    $sIndexHtml = '';

    if($_SERVER['REQUEST_URI'] !== '/'){
        foreach(explode('/', $sUrl) as $t_sPart){
        	if(!empty($t_sPart)){
		        $sIndex .= '/'.$t_sPart;
		        $sIndexHtml .= '/'.'<a href="' . urlencode($sIndex) . '">'.$t_sPart.'</a>';
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
?>
<html>
<head>
	<title>Index of <?= $sIndex;?></title>
	<link href="/Directory_Listing_Theme/dirlisting.css" media="screen" rel="stylesheet" type="text/css" />
	<link href="/Directory_Listing_Theme/thumbnails.css" media="screen" rel="stylesheet" type="text/css" />

	<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
</head>

<body>
    <?= $sReadme; ?>

	<?php
		if(!empty($aImages)){
			$sContent = '<ul class="thumbnails polaroids">';
			foreach($aImages as $t_sImage => $t_sExtension){
				$sContent .='<li class="'.$t_sExtension.'"><a href="'.$t_sImage.'" title="'.basename($t_sImage).'"><img src="/Directory_Listing_Theme/thumbnail.php?file=' . urlencode($t_sImage) . '" /></a>';
			}#foreach
			$sContent .= '</ul>';
			echo $sContent;
		}#if
	?>

    <div class="page main-content">
    <h1>
        <span>Directory index for</span>
        <a href="http://<?= $_SERVER['SERVER_NAME']; ?>"><?= $_SERVER['SERVER_NAME'];?></a><?= $sIndexHtml;?>
    </h1>

    <?php
    if (!empty($aExtensions)) {
    ?>
    <form action="" method="get" class="small">
    	<label>
            Show only files with extension
               <select name="P" size="1">
                   <option value="*">select a file type</option>
                    <?php
                        foreach($aExtensions as $t_sExtension => $t_sName){
                           echo '<option value="*'.$t_sExtension.'">*.'.$t_sName.'</option>';
                        }#foreach
                    ?>
            </select>
        </label>
    	<button type="submit">Go</button>
    </form>
<?php 
    }
?>
