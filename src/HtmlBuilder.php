<?php

namespace CollectiveAddons\Html;

use Illuminate\Contracts\Routing\UrlGenerator;
use Collective\Html\HtmlBuilder as BaseHtmlBuilder;

class HtmlBuilder extends BaseHtmlBuilder
{
    /**
     * Create a new HTML builder instance.
     *
     * @param \Illuminate\Contracts\Routing\UrlGenerator $url
     *
     * @return void
     */
    public function __construct(UrlGenerator $url = null)
    {
        parent::__construct($url);
    }
}
