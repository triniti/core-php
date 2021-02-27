<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\Util\StringUtil;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\UriTemplate\UriTemplateService;
use GuzzleHttp\Client as GuzzleClient;

class PurgeCacheHandler implements CommandHandler
{
    protected Ncr $ncr;
    protected array $config;

    public static function handlesCuries(): array
    {
        return [
            'triniti:sys:command:purge-cache',
        ];
    }

    public function __construct(Ncr $ncr, array $config = [])
    {
        $this->ncr = $ncr;
        $this->config = $config;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');

        try {
            $node = $this->ncr->getNode($nodeRef, false, ['causator' => $command]);
        } catch (\Throwable $e) {
            return;
        }

        $this->purgeCache($node, $command, $pbjx);
    }

    protected function purgeCache(Message $node, Message $command, Pbjx $pbjx): void
    {
        $this->purgeGoogleAmpCache($node);
    }

    protected function purgeGoogleAmpCache(Message $node): void
    {
        if (!isset($this->config['google_amp_private_key'])) {
            return;
        }

        $url = UriTemplateService::expand("{$node::schema()->getQName()}.amp", $node->getUriTemplateVars());
        if (null === $url) {
            return;
        }

        $urlParts = parse_url($url);
        $host = $urlParts['host'];
        $path = trim($urlParts['path'], '/');
        $timestamp = time();
        $uri = "/update-cache/c/s/{$host}/{$path}/?amp_action=flush&amp_ts={$timestamp}";
        $signature = $this->generateGoogleAmpSignature($uri);

        $cdnHost = str_replace(['-', '.'], ['--', '-'], $host) . '.cdn.ampproject.org';
        $bustUrl = "https://{$cdnHost}{$uri}&amp_url_signature={$signature}";

        try {
            $client = new GuzzleClient();
            $client->get($bustUrl);
        } catch (\Throwable $e) {
            // ignore google amp cache busting errors
        }
    }

    protected function generateGoogleAmpSignature(string $string): string
    {
        $privateKey = $this->config['google_amp_private_key'];
        $resource = openssl_pkey_get_private($privateKey);

        if (!$resource) {
            throw new \InvalidArgumentException(__CLASS__ . ':: Could not retrieve private key resource.');
        }

        if (!openssl_sign($string, $signature, $resource, OPENSSL_ALGO_SHA256)) {
            throw new \InvalidArgumentException(__CLASS__ . ':: Failed to sign ' . $string);
        }

        return StringUtil::urlsafeB64Encode($signature);
    }
}

