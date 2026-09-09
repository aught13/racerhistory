<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string,array{label:string,type:string,default:mixed,help?:string,options?:array<string,string>}> $siteOptionDefinitions
 * @var array<string,mixed> $siteOptions
 */
echo $this->element('Admin/site_options_form', [
    'siteOptionDefinitions' => $siteOptionDefinitions,
    'siteOptions' => $siteOptions,
]);
