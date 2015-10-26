<?php

namespace Potherca\Apache\Modules\AutoIndex;

use League\CommonMark\CommonMarkConverter;

/**
 * Note: The variable naming scheme used in this code is an adaption of
 * Systems Hungarian which is explained at http://pother.ca/VariableNamingConvention/
 */
class DirectoryListing
{
    ////////////////////////////// CLASS PROPERTIES \\\\\\\\\\\\\\\\\\\\\\\\\\\\
    const DIRECTORY_NAME = 'Directory_Listing_Theme';

    private $m_aEnvironment = [];

    // @FIXME: Instead of a hard-coded list of themes, the contents of ./vendor/bower-asset/bootswatch should be used
    private $m_aBootswatchThemes = [
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

    private $m_aConfig = [
        "theme" => "default",
        "readmePrefixes" => ["readme", "README", "ReadMe"],
        "readmeExtensions" => [".html", ".md", ".txt"],
        "assets" => []
    ];

    /**
     * @var array
     */
    private $m_aUserInput = [];

    private $m_bUseBootstrap = false;

    private $m_sConfigFile = 'config.json';

    //////////////////////////// SETTERS AND GETTERS \\\\\\\\\\\\\\\\\\\\\\\\\\\
    /**
     * @return array
     */
    private function getCurrentRealDirectory()
    {
        static $sCurrentRealDir;

        if ($sCurrentRealDir === null) {

            if (isset($this->m_aEnvironment['WEB_ROOT'])) {
                $sRoot = $this->m_aEnvironment['WEB_ROOT'];
            } elseif (is_dir($this->m_aEnvironment['DOCUMENT_ROOT'])) {
                $sRoot = $this->m_aEnvironment['DOCUMENT_ROOT'];
            } else {
                $sRoot = dirname(dirname($this->m_aEnvironment['SCRIPT_FILENAME']));
            }

            $sCurrentWebDir = $this->m_aEnvironment['REQUEST_URI'];
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
            $sUrl = $this->sanitizeUrl($this->m_aEnvironment['REQUEST_URI']);
        }

        return $sUrl;
    }

    //////////////////////////////// PUBLIC API \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    final public function  __construct(array $p_aEnvironment, array $p_aUserInput)
    {
        $this->m_aEnvironment = $p_aEnvironment;
        $this->m_aUserInput = $p_aUserInput;
    }

    final public function footer(TemplateInterface $p_oTemplate)
    {
        $aConfig = $this->loadConfig();

        $aContext = [];
        $aContext['aJsAssets'] = $this->buildJavascriptAssets($aConfig);
        $aContext['aPreviews'] = $this->buildPreviews();
        $aContext['sFooterReadme'] = $this->buildFooterReadme($aConfig);
        $aContext['sSignature'] = $this->m_aEnvironment['SERVER_SIGNATURE'];

        return $p_oTemplate->buildBottom($aContext);
    }

    final public function header(TemplateInterface $p_oTemplate)
    {
        $aConfig = $this->loadConfig();

        $aContext = [];
        $aContext['aCssAssets'] = $this->buildCssAssets($aConfig);
        $aContext['sIndex'] = 'Index of ' . $this->getUrl();
        $aContext['sIndexHtml'] = $this->buildBreadcrumbHtml();
        $aContext['sReadmeHtml'] = $this->buildHeaderReadme($aConfig);

        return $p_oTemplate->buildTop($aContext);
    }

    ////////////////////////////// UTILITY METHODS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    private function getAssetPath($p_sFile, $p_sThemeDir)
    {

        $aParts = explode('.', $p_sFile);
        array_splice($aParts, -1, 0, array('min'));

        if (is_file($this->getRootDirectory() . '/' . $p_sThemeDir . $p_sFile)) {
            $sPath = $p_sThemeDir . $p_sFile;
        } elseif (is_file($this->getRootDirectory() . '/' . $p_sThemeDir . implode('.', $aParts))) {
            $sPath = $p_sThemeDir . implode('.', $aParts);
        } elseif (is_file($this->getRootDirectory() . '/' . '/themes/default/' . $p_sFile)) {
            $sPath = '/themes/default/' . $p_sFile;
        } else {
            throw new \Exception('Could not find asset "' . $p_sFile . '"');
        }

        return '/' . self::DIRECTORY_NAME . '/' . $sPath;
    }

    /**
     * @TODO: Move breadcrumb HTML to template
     *
     * @return string
     */
    private function buildBreadcrumbHtml()
    {
        $sIndexHtml = sprintf(
            '<li><a href="http://%1$s">%1$s</a></li>',
            $this->m_aEnvironment['SERVER_NAME']
        );

        $sUrl = $this->getUrl();

        if ($this->m_aEnvironment['REQUEST_URI'] !== '/') {
            $aParts = explode('/', trim($sUrl, '/'));
            $iCount = count($aParts) - 1;
            $sUrl = 'http://' . $this->m_aEnvironment['SERVER_NAME'];

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
     * @param array $p_aConfig
     *
     * @return string
     *
     * @throws \Exception
     */
    private function fetchThemeDirectory($p_aConfig)
    {
        if (isset($this->m_aUserInput['theme'])
            && in_array(ucfirst($this->m_aUserInput['theme']), $this->m_aBootswatchThemes)
        ) {
            $this->m_bUseBootstrap = true;
            $sThemeDir = 'vendor/bower-asset/bootswatch/' . $this->m_aUserInput['theme'] . '/';
        } elseif ($this->m_bUseBootstrap === true
            && is_dir($this->getRootDirectory() . '/vendor/bower-asset/bootswatch/' . $p_aConfig['theme'])
        ) {
            $sThemeDir = 'vendor/bower-asset/bootswatch/' . $p_aConfig['theme'] . '/';
        } elseif (is_dir($this->getRootDirectory() . '/themes/' . $p_aConfig['theme'])) {
            $sThemeDir = 'themes/' . $p_aConfig['theme'] . '/';
        } else {
            throw new \Exception('Could not find theme directory "' . $p_aConfig['theme'] . '"');
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
                        '<img src="/%s/thumbnail.php?file=%s" alt="%s" />',
                        self::DIRECTORY_NAME,
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
        $aConfig = array_merge([], $this->m_aConfig);

        if (is_file($this->m_sConfigFile)) {

            $this->m_bUseBootstrap = true;

            if (!is_readable($this->m_sConfigFile)) {
                throw new \Exception("Could not read configuration file");
            } else {
                $aConfig = array_merge(
                    $aConfig,
                    json_decode(file_get_contents($this->m_sConfigFile), true)
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
            $sReadMeFilePath = urldecode($this->m_aEnvironment['DOCUMENT_ROOT'] . $this->m_aEnvironment['REQUEST_URI'] . $sReadMeFileName);

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
     * @param array $p_aConfig
     *
     * @return array
     */
    private function buildCssAssets(array $p_aConfig)
    {
        $sThemeDir = $this->fetchThemeDirectory($p_aConfig);

        $aAssets = [
            $this->getAssetPath('table.css', $sThemeDir),
            $this->getAssetPath('thumbnails.css', $sThemeDir),
        ];

        if ($this->m_bUseBootstrap === false
            || ($this->m_bUseBootstrap === true && $p_aConfig['theme'] !== 'default')
        ) {
            array_unshift(
                $aAssets,
                $this->getAssetPath('bootstrap.css', $sThemeDir)
            );
        }

        if ($this->m_bUseBootstrap === true) {
            array_unshift(
                $aAssets,
                '/' . self::DIRECTORY_NAME . '/vendor/bower-asset/bootstrap/dist/css/bootstrap.min.css',
                '/' . self::DIRECTORY_NAME . '/vendor/bower-asset/bootstrap/dist/css/bootstrap-theme.min.css'
            );
        }

        return $aAssets;
    }

    /**
     * @param array $p_aConfig
     *
     * @return array
     */
    private function buildJavascriptAssets($p_aConfig)
    {
        $sThemeDir = $this->fetchThemeDirectory($p_aConfig);

        $aAssets = [
            '/' . self::DIRECTORY_NAME . '/vendor/bower-asset/jquery/dist/jquery.js',
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
