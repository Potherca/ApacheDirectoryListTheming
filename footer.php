<?php require 'common.php';?>
            </div><!-- .panel-body -->
        </div><!-- .panel -->
    </div><!-- .container -->

    <?= $sReadme ?>

    <?= $_SERVER['SERVER_SIGNATURE'] ?>

    <?php foreach($aAssets['js'] as $sJavascript):?>
        <script src="/Directory_Listing_Theme/<?=$sJavascript?>"></script>
    <?php endforeach?>
</body>
</html>
