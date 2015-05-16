<!DOCTYPE html>
<?php require 'common.php';?>
<html>
<head>
    <meta charset="utf-8" />
    <title>Index of <?= $sIndex;?></title>

    <?php foreach($aAssets['css'] as $sStylesheet):?>
        <link rel="stylesheet" href="/Directory_Listing_Theme/<?=$sStylesheet?>" />
    <?php endforeach?>

	<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
</head>

<body>
        <?php if($sReadmeHtml):?>
        <div class="page readme" style="max-height: 24em; overflow: auto;">
            <?= $sReadmeHtml ?>
        </div>
        <?php endif;?>

        <?= $sThumbnailHtml ?>

    <div class="page main-content">
		<h1>
		    <span>Directory index for</span>
		    <?= $sIndexHtml ?>
		</h1>

		<label>
		    Filter by name:
		    <input id="filter" />
		</label>
