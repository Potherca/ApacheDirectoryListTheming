<?php

namespace Potherca\Apache\Modules\AutoIndex;

use League\CommonMark\CommonMarkConverter;

class DirectoryListing
{
    ////////////////////////////// CLASS PROPERTIES \\\\\\\\\\\\\\\\\\\\\\\\\\\\
    private $aEnvironment = [];

    // @FIXME: Instead of a hard-coded list of themes, the contents of ./vendor/bower-asset/bootswatch should be used
    private $aBootswatchThemes = [
        'Cerulean',
        'Cosmo',
        'Cyborg',
        'Darkly',
        'Flatly',
        'Journal',
        'Lumen',
        'Paper',
        'Readable',
        'Sandstone',
        'Simplex',
        'Slate',
        'Spacelab',
        'Superhero',
        'United',
        'Yeti',
        /* "backward compatible" look from screenshot-01.png */
        'Foo',
    ];

    private $aConfig = [
        "theme" => "default",
        "readmePrefixes" => ["readme", "README", "ReadMe"],
        "readmeExtensions" => [".html", ".md", ".txt"],
        "assets" => []
    ];

    /**
     * @var array
     */
    private $aUserInput = [];

    private $bUseBootstrap = false;

    private $sConfigFile = 'config.json';

    //////////////////////////// SETTERS AND GETTERS \\\\\\\\\\\\\\\\\\\\\\\\\\\
    /**
     * @return array
     */
    private function getCurrentRealDirectory()
    {
        static $sCurrentRealDir;

        if ($sCurrentRealDir === null) {

            if (isset($this->aEnvironment['WEB_ROOT'])) {
                $sRoot = $this->aEnvironment['WEB_ROOT'];
            } elseif (is_dir($this->aEnvironment['DOCUMENT_ROOT'])) {
                $sRoot = $this->aEnvironment['DOCUMENT_ROOT'];
            } else {
                $sRoot = dirname(dirname($this->aEnvironment['SCRIPT_FILENAME']));
            }

            $sCurrentWebDir = $this->aEnvironment['REQUEST_URI'];
            $sCurrentRealDir = $this->sanitizeUrl($sRoot . $sCurrentWebDir);
        }

        return $sCurrentRealDir;
    }

    private function getRootDirectory()
    {
        return realpath(__DIR__ . '/../');
    }

    /**
     * @return string
     */
    private function getUrl()
    {
        static $sUrl;

        if ($sUrl === null) {
            $sUrl = $this->sanitizeUrl($this->aEnvironment['REQUEST_URI']);
        }

        return $sUrl;
    }

    //////////////////////////////// PUBLIC API \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    final public function  __construct(array $aEnvironment, array $aUserInput)
    {
        $this->aEnvironment = $aEnvironment;
        $this->aUserInput = $aUserInput;
    }

    final public function footer(TemplateInterface $template)
    {
        $aConfig = $this->loadConfig();

        $context = [];
        $context['aJsAssets'] = $this->buildJavascriptAssets($aConfig);
        $context['aPreviews'] = $this->buildPreviews();
        $context['sFooterReadme'] = $this->buildFooterReadme($aConfig);
        $context['sSignature'] = $this->aEnvironment['SERVER_SIGNATURE'];

        return $template->buildBottom($context);
    }

    final public function header(TemplateInterface $template)
    {
        $aConfig = $this->loadConfig();

        $context = [];
        $context['aCssAssets'] = $this->buildCssAssets($aConfig);
        $context['sIndex'] = 'Index of ' . $this->getUrl();
        $context['sIndexHtml'] = $this->buildBreadcrumbHtml();
        $context['sReadmeHtml'] = $this->buildHeaderReadme($aConfig);

        return $template->buildTop($context);
    }

    ////////////////////////////// UTILITY METHODS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    private function getAssetPath($p_sFile, $sThemeDir)
    {

        $aParts = explode('.', $p_sFile);
        array_splice($aParts, -1, 0, array('min'));

        if (is_file($this->getRootDirectory() . '/' . $sThemeDir . $p_sFile)) {
            $sPath = $sThemeDir . $p_sFile;
        } elseif (is_file($this->getRootDirectory() . '/' . $sThemeDir . implode('.', $aParts))) {
            $sPath = $sThemeDir . implode('.', $aParts);
        } elseif (is_file($this->getRootDirectory() . '/' . '/themes/default/' . $p_sFile)) {
            $sPath = '/themes/default/' . $p_sFile;
        } else {
            throw new \Exception('Could not find asset "' . $p_sFile . '"');
        }

        return '/Directory_Listing_Theme/'. $sPath;
    }

    /**
     * @return string
     */
    private function buildBreadcrumbHtml()
    {
        $sUrl = $this->getUrl();

        $sIndexHtml = '<li>'
            . '<a href="http://' . $this->aEnvironment['SERVER_NAME'] . '">'
            . $this->aEnvironment['SERVER_NAME']
            . '</a>'
            . '</li>'
        ;

        if ($this->aEnvironment['REQUEST_URI'] !== '/') {
            $aParts = explode('/', trim($sUrl, '/'));
            $iCount = count($aParts) - 1;
            $sUrl = 'http://' . $this->aEnvironment['SERVER_NAME'];

            foreach ($aParts as $t_iIndex => $t_sPart) {
                if (!empty($t_sPart)) {

                    $sUrl .= '/' . urlencode($t_sPart);
                    $sIndexHtml .= '<li><a';
                    if ($t_iIndex === $iCount) {
                        $sIndexHtml .= ' class="active"';
                    } else {
                        $sIndexHtml .= ' class="text-muted"';
                    }
                    $sIndexHtml .= ' href="' . $sUrl . '">' . $t_sPart . '</a></li>';
                }
            }
        }

        return $sIndexHtml;
    }

    /**
     * @param array $aConfig
     *
     * @return string
     *
     * @throws \Exception
     */
    private function fetchThemeDirectory($aConfig)
    {
        if (isset($this->aUserInput['theme'])
            && in_array(ucfirst($this->aUserInput['theme']), $this->aBootswatchThemes)
        ) {
            $this->bUseBootstrap = true;
            $sThemeDir = 'vendor/bower-asset/bootswatch/' . $this->aUserInput['theme'] . '/';
        } elseif ($this->bUseBootstrap === true
            && is_dir($this->getRootDirectory() . '/vendor/bower-asset/bootswatch/' . $aConfig['theme'])
        ) {
            $sThemeDir = 'vendor/bower-asset/bootswatch/' . $aConfig['theme'] . '/';
        } elseif (is_dir($this->getRootDirectory() . '/themes/' . $aConfig['theme'])) {
            $sThemeDir = 'themes/' . $aConfig['theme'] . '/';
        } else {
            throw new \Exception('Could not find theme directory "' . $aConfig['theme'] . '"');
        }

        return $sThemeDir;
    }

    /**
     * @CHECKME: Instead of using an external script to create thumbnails, images could be in-lined
     *        using `sprintf('<img src="data:%s;base64,%s">', $sMimeType, base64_encode(file_get_content($sFileName)));`
     *        This would increase page-load time (because each image would need to be opened for reading)
     *        but it would save thumbnails being written. The question is whether
     *        this is a scenario support should be added for...
     * @return string
     */
    private function buildPreviews()
    {
        $aPreviews = [];
        $sCurrentRealDir = $this->getCurrentRealDirectory();

        foreach (scandir($sCurrentRealDir) as $t_sFileName) {
            $aInfo = [];

            $rFileInfo = finfo_open(FILEINFO_MIME_TYPE | FILEINFO_PRESERVE_ATIME /*| FILEINFO_CONTINUE | FILEINFO_SYMLINK*/);
            $sMimeType = finfo_file($rFileInfo, $sCurrentRealDir . $t_sFileName);
            finfo_close($rFileInfo);

            $aInfo['name'] = basename($t_sFileName);
            $aInfo['link'] = $this->getUrl() . $t_sFileName;
            $aInfo['mime-type'] = $sMimeType;

            if (strpos($sMimeType, '/')) {
                list($aInfo['type'], $aInfo['subtype']) = explode('/', $sMimeType);
            } else {
                $aInfo['type'] = $sMimeType;
                $aInfo['subtype'] = '';
            }

            switch ($aInfo['type']) {
                case 'video':
                    $aInfo['tag'] = sprintf(
                        '<video src="%s" preload="%s" controls></video>', // @CHECKME: Add poster="%s" ?
                        $aInfo['link'],
                        'metadata'
                    );
                break;

                case 'audio':
                    $aInfo['tag'] = sprintf(
                        '<audio src="%s" preload="%s" controls></audio>',
                        $aInfo['link'],
                        'metadata'
                    );
                break;

                case 'image':
                   $aInfo['tag'] = sprintf(
                        '<img src="/Directory_Listing_Theme/thumbnail.php?file=%s" alt="%s" />',
                        urlencode($aInfo['link']),
                        $aInfo['name']
                    );
                break;

                case 'directory':
                case 'application':
                case 'text':
                default:
                    $aInfo['tag'] = sprintf(
                        '<p class="no-preview">No preview for %s</p>',
                        $aInfo['type']
                    );
                break;
            }

            array_push($aPreviews, $aInfo);
        }

        return $aPreviews;
    }

    private function loadConfig()
    {
        $aConfig = array_merge([], $this->aConfig);

        if (is_file($this->sConfigFile)) {

            $this->bUseBootstrap = true;

            if (!is_readable($this->sConfigFile)) {
                throw new \Exception("Could not read configuration file");
            } else {
                $aConfig = array_merge(
                    $aConfig,
                    json_decode(file_get_contents($this->sConfigFile), true)
                );
            }
        }

        return $aConfig;
    }

    private function buildFooterReadme($aConfig)
    {
        $sReadme = '';
        foreach ($aConfig['readmeExtensions'] as $t_sExtension) {
            $sReadMeFileName = 'readme-footer' . $t_sExtension;
            $sReadMeFilePath = urldecode($this->aEnvironment['DOCUMENT_ROOT'] . $this->aEnvironment['REQUEST_URI'] . $sReadMeFileName);

            $sReadmeHtml = $this->buildReadmeHtml($sReadMeFilePath, $t_sExtension);

            if (!empty($sReadmeHtml)) {
                break;
            }
        }

        return $sReadme;
    }

    /**
     * @param array $aConfig
     *
     * @return array
     */
    private function buildHeaderReadme($aConfig)
    {
        $sReadmeHtml = '';

        $sCurrentRealDir = $this->getCurrentRealDirectory();

        foreach ($aConfig['readmePrefixes'] as $t_sPrefix) {
            foreach ($aConfig['readmeExtensions'] as $t_sExtension) {
                $sReadMeFileName = $t_sPrefix . $t_sExtension;
                $sReadMeFilePath = $sCurrentRealDir . urldecode($sReadMeFileName);

                $sReadmeHtml = $this->buildReadmeHtml($sReadMeFilePath, $t_sExtension);
                if (empty($sReadmeHtml) === false) {
                    break;
                }
            }
        }

        return $sReadmeHtml;
    }

    /**
     * @param $sReadMeFilePath
     * @param $t_sExtension
     *
     * @return string
     */
    private function buildReadmeHtml($sReadMeFilePath, $t_sExtension)
    {
        $sReadmeHtml = '';

        if (file_exists($sReadMeFilePath)) {
            $sReadmeContent = file_get_contents($sReadMeFilePath);
            if ($t_sExtension === '.md') {
                $converter = new CommonMarkConverter();
                $sReadmeHtml .= $converter->convertToHtml($sReadmeContent);
            } elseif ($t_sExtension === '.txt') {
                $sReadmeHtml .= '<div style="white-space: pre-wrap;">' . $sReadmeContent . '</div>';
            } else {
                $sReadmeHtml .= $sReadmeContent;
            }

        }
        return $sReadmeHtml;
    }

    /**
     * @param array $aConfig
     *
     * @return array
     */
    private function buildCssAssets(array $aConfig)
    {
        $sThemeDir = $this->fetchThemeDirectory($aConfig);

        $aAssets = [
            $this->getAssetPath('table.css', $sThemeDir),
            $this->getAssetPath('thumbnails.css', $sThemeDir),
        ];

        if ($this->bUseBootstrap === false
            || ($this->bUseBootstrap === true && $aConfig['theme'] !== 'default')
        ) {
            array_unshift(
                $aAssets,
                $this->getAssetPath('bootstrap.css', $sThemeDir)
            );
        }

        if ($this->bUseBootstrap === true) {
            array_unshift(
                $aAssets,
                '/Directory_Listing_Theme/vendor/bower-asset/bootstrap/dist/css/bootstrap.min.css',
                '/Directory_Listing_Theme/vendor/bower-asset/bootstrap/dist/css/bootstrap-theme.min.css'
            );
        }

        return $aAssets;
    }

    /**
     * @param array $aConfig
     *
     * @return array
     */
    private function buildJavascriptAssets($aConfig)
    {
        $sThemeDir = $this->fetchThemeDirectory($aConfig);

        $aAssets = [
            '/Directory_Listing_Theme/vendor/bower-asset/jquery/dist/jquery.js',
            $this->getAssetPath('functions.js', $sThemeDir),
        ];

        return $aAssets;
    }

    /**
     * @param $sUrl
     *
     * @return string
     */
    private function sanitizeUrl($sUrl)
    {
        $sUrl = urldecode($sUrl);

        if (strpos($sUrl, '?') !== false) {
            $sUrl = substr($sUrl, 0, strpos($sUrl, '?'));
        }

        return $sUrl;
    }

}
/*EOF*/
