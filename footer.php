<?php require 'common.php';?>
            </div><!-- .panel-body -->
        </div><!-- .main-content -->
    </div><!-- .container -->

    <?= $sReadme ?>

    <footer class="footer">
        <div class="container">
            <?= $_SERVER['SERVER_SIGNATURE'] ?>
        </div>
    </footer>

    <?php foreach($aAssets['js'] as $sJavascript):?>
        <script src="/Directory_Listing_Theme/<?=$sJavascript?>"></script>
    <?php endforeach?>
</body>
</html>
