<?php
declare(strict_types=1);

use Triniti\Dam\UrlProvider as DamUrlProvider;
use Triniti\Dam\UrlService;
use Triniti\Ovp\ArtifactUrlProvider;
use Triniti\Ovp\ArtifactUrlService;

(function() {
    $damUrlProvider = new DamUrlProvider(['default' => 'https://dam.acme.com/']);
    UrlService::setProvider($damUrlProvider);
    ArtifactUrlService::setProvider(new ArtifactUrlProvider($damUrlProvider));
})();
