<?php

namespace Potherca\Apache\Modules\AutoIndex;

use League\CommonMark\CommonMarkConverter;

class DirectoryListing
{
    private $aEnvironment = [];

    private $aBootswatchThemes = [
        'Cosmo',
        'Cyborg',
        'Darkly',
        'Flatly',
        'Journal',
        'Kingboard',
        'Lumen',
        'Paper',
        'React',
        'Readable',
        'Sandstone',
        'Simplex',
        'Slate',
        'Spacelab',
        'Superhero',
        'United',
        'Yeti',
        'Zerif',
    ];

    private $aConfig = [
        "theme" => "default",
        "readmePrefixes" => ["readme", "README", "ReadMe"],
        "readmeExtensions" => [".html", ".md", ".txt"],
        "assets" => []
    ];

    private $bUseBootstrap = false;

    private $sConfigFile = 'config.json';

    final public function  __construct(array $aEnvironment)
    {
        $this->aEnvironment = $aEnvironment;
    }

    final public function footer(Template $template)
    {
        $aConfig = $this->loadConfig();

        $context['aAssets'] = $this->buildAssetsArray();
        $context['sFooterReadme'] = $this->buildFooterReadme($aConfig);
        $context['sIndex'] = 'Index of ' . $this->getUrl();
        $context['sIndexHtml'] = $this->buildBreadcrumbHtml();
        $context['sReadmeHtml'] = $this->buildHeaderReadme($aConfig);
        $context['sSignature'] = $_SERVER['SERVER_SIGNATURE'];
        $context['sThumbnailHtml'] = $this->buildThumbnailHtml();

        return $template->buildBottom($context);
    }

    final public function header(Template $template)
    {
        $aAssets = $this->buildAssetsArray();
        $aConfig = $this->loadConfig();

        $context = [];
        $context['aAssets'] = $aAssets;
        $context['sFooterReadme'] = $this->buildFooterReadme($aConfig);
        $context['sIndex'] = 'Index of ' . $this->getUrl();
        $context['sIndexHtml'] = $this->buildBreadcrumbHtml();
        $context['sReadmeHtml'] = $this->buildHeaderReadme($aConfig);
        $context['sSignature'] = $_SERVER['SERVER_SIGNATURE'];
        $context['sThumbnailHtml'] = $this->buildThumbnailHtml();

        return $template->buildTop($context);
    }

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

        return $sPath;
    }

    private function getRootDirectory()
    {
        return realpath(__DIR__ . '/../');
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
     * @return string
     */
    private function getUrl()
    {
        $sUrl = urldecode($this->aEnvironment['REQUEST_URI']);
        if (strpos($sUrl, '?') !== false) {
            $sUrl = substr($sUrl, 0, strpos($sUrl, '?'));
        }

        return $sUrl;
    }

    /**
     * @throws \Exception
     */
    private function buildAssetsArray()
    {
        $aConfig = $this->loadConfig();

        $sThemeDir = $this->fetchThemeDirectory($aConfig);

        $aAssets = [
            'css' => [
                '/Directory_Listing_Theme/' . $this->getAssetPath('table.css', $sThemeDir),
                '/Directory_Listing_Theme/' . $this->getAssetPath('thumbnails.css', $sThemeDir),
            ],
            'js' => [
                'vendor/bower-asset/jquery/dist/jquery.js',
                '/Directory_Listing_Theme/' . $this->getAssetPath('functions.js', $sThemeDir),
            ],
        ];

        if ($this->bUseBootstrap === false || ($this->bUseBootstrap === true && $aConfig['theme'] !== 'default')) {
            array_unshift(
                $aAssets['css'],
                $this->getAssetPath('bootstrap.css', $sThemeDir)
            );
        }

        if ($this->bUseBootstrap === true) {
            array_unshift(
                $aAssets['css'],
                'vendor/bower-asset/bootstrap/dist/css/bootstrap.min.css',
                'vendor/bower-asset/bootstrap/dist/css/bootstrap-theme.min.css'
            );
        }

        $aAssets = array_merge_recursive($aAssets, $aConfig['assets']);

        return $aAssets;
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
        if (isset($_GET['theme'])
            && in_array(ucfirst($_GET['theme']), $this->aBootswatchThemes)
        ) {
            $this->bUseBootstrap = true;
            $sThemeDir = 'vendor/bower-asset/bootswatch/' . $_GET['theme'] . '/';
            return $sThemeDir;
        } elseif ($this->bUseBootstrap === true
            && is_dir($this->getRootDirectory() . '/vendor/bower-asset/bootswatch/' . $aConfig['theme'])
        ) {
            $sThemeDir = 'vendor/bower-asset/bootswatch/' . $aConfig['theme'] . '/';
            return $sThemeDir;
        } elseif (is_dir($this->getRootDirectory() . '/themes/' . $aConfig['theme'])) {
            $sThemeDir = 'themes/' . $aConfig['theme'] . '/';
            return $sThemeDir;
        } else {
            throw new \Exception('Could not find theme directory "' . $aConfig['theme'] . '"');
        }
    }

    /**
     * @return array
     */
    private function getCurrentRealDirectory()
    {
        if (isset($this->aEnvironment['WEB_ROOT'])) {
            $sRoot = $this->aEnvironment['WEB_ROOT'];
        } elseif (is_dir($this->aEnvironment['DOCUMENT_ROOT'])) {
            $sRoot = $this->aEnvironment['DOCUMENT_ROOT'];
        } else {
            $sRoot = dirname(dirname($this->aEnvironment['SCRIPT_FILENAME']));
        }

        $sCurrentWebDir = $this->aEnvironment['REQUEST_URI'];
        $sCurrentRealDir = urldecode($sRoot . $sCurrentWebDir);

        if (strpos($sCurrentRealDir, '?') !== false) {
            $sCurrentRealDir = substr($sCurrentRealDir, 0,
                strpos($sCurrentRealDir, '?'));
        }
        return $sCurrentRealDir;
    }

    /**
     * @return string
     */
    private function buildThumbnailHtml()
    {
        $sThumbnailHtml = '';

        /* Sort out extension filter and thumbnail for images/pdf/etc. */
        $aExtensions = array();
        $aImages = array();
        $sCurrentRealDir = $this->getCurrentRealDirectory();
        $sCurrentWebDir = $this->aEnvironment['REQUEST_URI'];

        foreach (scandir($sCurrentRealDir) as $t_sFileName) {
            $isDirectory = is_dir($sCurrentRealDir . $t_sFileName);
            $iPositionOfDot = strrpos($t_sFileName, '.');
            if ($isDirectory === false && is_integer($iPositionOfDot)) {
                $sExtension = substr($t_sFileName, $iPositionOfDot);
                $sExtension = strtolower($sExtension);

                $aExtensions[$sExtension] = substr($sExtension, 1);

                $aSupportedExtensions = [
                    '.bmp',
                    '.eps',
                    '.gif',
                    '.ico',
                    '.jpg',
                    '.png',
                    '.ps',
                    '.pdf',
                    '.psd',
                    '.svg',
                    '.tiff',
                ];
                if (in_array($sExtension, $aSupportedExtensions)) {
                    $aImages[$sCurrentWebDir . $t_sFileName] = substr($sExtension, 1);
                }
            }
        }

        natcasesort($aExtensions);

        if (!empty($aImages)) {
            $sThumbnailHtml .= '<ul class="thumbnails polaroids">';
            foreach ($aImages as $t_sImage => $t_sExtension) {
                $sThumbnailHtml .= '<li class="' . $t_sExtension . '"><a href="' . $t_sImage . '" title="' . basename($t_sImage) . '"><img src="/Directory_Listing_Theme/thumbnail.php?file=' . urlencode($t_sImage) . '" /></a>';
            }
            $sThumbnailHtml .= '</ul>';
        }
        return $sThumbnailHtml;
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

                $sReadmeHtml .= $this->buildReadmeHtml($sReadMeFilePath, $t_sExtension);

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
}
/*EOF*/
