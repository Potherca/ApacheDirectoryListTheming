<?php
namespace Potherca\Apache\Modules\AutoIndex;

use PHPTAL;

class Template implements TemplateInterface
{
    const DELIMINATOR = '<!-- ┈✂┈┈┈┈┈✄┈┈┈┈┈✄┈┈┈┈┈✄┈┈┈┈┈✄┈┈┈┈┈✄┈┈┈┈┈✂┈┈┈┈┈✄┈┈┈┈┈✂┈┈┈┈┈✄┈┈┈┈┈✂┈┈┈┈┈✄┈ -->';

    /** @var string */
    private $bottom;
    /** @var string */
    private $top;
    /** @var array */
    private $context = [
        'sIndexHtml' => '',
        'sIndex' => '',
        'sSignature' => '',
        'sReadmeHtml' => '',
        'aPreviews' => [],
        'sFooterReadme' => '',
        'aCssAssets' => [],
        'aJsAssets' => [],
    ];

    /** @var PHPTAL */
    private $template;

    final public function __construct(PHPTAL $template)
    {
        $this->template = $template;
    }

    final public function buildTop(array $context)
    {
        if (empty($this->top)) {
            $this->doSplit($context);
        }

        return $this->top;
    }

    final public function buildBottom(array $context)
    {
        if (empty($this->bottom)) {
            $this->doSplit($context);
        }

        return $this->bottom;
    }

    private function split(PHPTAL $template, array $context)
    {
        foreach ($context as $key => $value) {
            $template->set($key, $value);
        }

        $sHtml = $template->execute();

        list($this->top, $middle, $this->bottom) = explode(self::DELIMINATOR, $sHtml);
        unset($middle);
    }

    /**
     * @param $context
     */
    private function doSplit(array $context)
    {
        $context = array_merge($this->context, $context);
        $this->split($this->template, $context);
    }
}

/*EOF*/
