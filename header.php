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
    <div class="container">
        <div class="header clearfix">
            <h1 class="text-muted">
                <span>Directory index</span>
            </h1>
            <ol class="breadcrumb">
                <?= $sIndexHtml ?>
            </ol>
        </div><!-- .header -->

        <?php if($sReadmeHtml):?>
        <div class="page readme | jumbotron" style="max-height: 24em; overflow: auto;">
            <?= $sReadmeHtml ?>
        </div><!-- .readme -->
        <?php endif;?>

        <?= $sThumbnailHtml ?>

        <div class="page main-content | container panel panel-primary">
            <div class="panel-body">
                <label>
                    Filter by name:
                    <input id="filter" />
                </label>
<!--
            </div>.panel-body
        </div>.main-content
    </div>.container
</body>
</html>
-->
