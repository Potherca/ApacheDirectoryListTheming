<?php

namespace Potherca\Apache\Modules\AutoIndex;

interface TemplateInterface
{
    public function buildTop(array $context);

    public function buildBottom(array $context);
}
