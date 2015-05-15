<?php
 date_default_timezone_set('Europe/Amsterdam');

    $sReadme = '';
    $aReadMeExtensions = array('.html','.md','.txt');
    foreach($aReadMeExtensions as $t_sExtension){
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

?>
    </div><!-- .page -->

    <?php echo $sReadme; ?>

    <?php echo $_SERVER['SERVER_SIGNATURE'] ?>

    <script src="/Directory_Listing_Theme/zepto.min.js"></script>
    <script src="/Directory_Listing_Theme/functions.js"></script>
</body>
</html>
