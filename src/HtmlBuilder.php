<?php

namespace CollectiveAddons\Html;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Collective\Html\HtmlBuilder as BaseHtmlBuilder;

class HtmlBuilder extends BaseHtmlBuilder
{
    /**
     * Create a new HTML builder instance.
     *
     * @param \Illuminate\Contracts\Routing\UrlGenerator $url
     * @param \Illuminate\Contracts\View\Factory         $view
     *
     * @return void
     */
    public function __construct(UrlGenerator $url = null, Factory $view)
    {
        parent::__construct($url, $view);
    }
}
