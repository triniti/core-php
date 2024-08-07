<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\UriTemplate\UriTemplateService;
use Psr\Log\LoggerInterface;
use Triniti\Schemas\Sys\Event\SeoInspectedV1;

class InspectSeoHandler implements CommandHandler
{
    public static function handlesCuries(): array
    {
        return [
            'triniti:sys:command:inspect-seo',
        ];
    }

    public function __construct(
        private readonly Ncr             $ncr,
        private readonly Flags           $flags,
        private readonly LoggerInterface $logger,
        private readonly array           $config,
    ) {
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        if ($this->flags->getBoolean('inspect_seo_handler_disabled') || !$command->has('search_engines')) {
            return;
        }

        $searchEngines = $command->get('search_engines');
        $nodeRef = $command->get('node_ref');

        try {
            $context = ['causator' => $command];
            $node = $this->ncr->getNode($nodeRef, false, $context);
        } catch (NodeNotFound $e) {
            $this->logger->error('Unable to get node for search engines processing.', [
                'exception' => $e,
                'node_ref'  => $nodeRef,
            ]);
            return;
        }

        $retryCommand = clone $command;
        $retryCommand->clear('search_engines');
        $pbjx->copyContext($command, $retryCommand);

        foreach ($searchEngines as $searchEngine) {
            $methodName = 'checkIndexStatusFor' . ucfirst($searchEngine);

            if (!method_exists($this, $methodName)) {
                continue;
            }

            $retryCommand = $this->$methodName($retryCommand, $pbjx, $node);
        }

        if (empty($retryCommand->get('search_engines'))) {
            return;
        }

        $retries = $command->get('ctx_retries');
        $maxRetries = $this->flags->getInt('inspect_seo_max_retries', 5);

        if ($retries < $maxRetries) {
            $retryCommand->set('ctx_retries', $retries + 1);

            $pbjx->sendAt(
                $retryCommand,
                strtotime($this->flags->getString('inspect_seo_retry_delay', '+15 minutes')),
                "$nodeRef.inspect-seo"
            );
        }
    }

    protected function resolveGoogleSiteUrl(Message $node): string
    {
        $url = UriTemplateService::expand(
            "{$node::schema()->getQName()}.canonical", $node->getUriTemplateVars()
        );

        $host = parse_url($url, PHP_URL_HOST);
        $parts = explode('.', $host);

        $apexDomain = implode('.', array_slice($parts, -2));
        return "sc-domain:$apexDomain";
    }

    protected function checkIndexStatusForGoogle(Message $command, Pbjx $pbjx, Message $node): Message
    {
        if ($this->flags->getBoolean('inspect_seo_handler_google_disabled')) {
            return $command;
        }

        $request = new \Google_Service_SearchConsole_InspectUrlIndexRequest();

        $url = UriTemplateService::expand(
            "{$node::schema()->getQName()}.canonical", $node->getUriTemplateVars()
        );

        $request->setSiteUrl($this->resolveGoogleSiteUrl($node));
        $request->setInspectionUrl($url);
        $client = new \Google_Client();
        $client->addScope(\Google_Service_SearchConsole::WEBMASTERS_READONLY);

        $response = null;

        try {
            $client->setAuthConfig(json_decode($this->config['google_auth_config'], true));
            $service = new \Google_Service_SearchConsole($client);
            $response = $service->urlInspection_index->inspect($request);
        } catch (\Throwable $e) {
            $this->logger->error('An error occurred in checkIndexStatus for google.', [
                'exception'   => $e,
                'node_ref'    => $node->generateNodeRef(),
                'url'         => $url,
                'retry_count' => $command->get('ctx_retries'),
                'stack_trace' => $e->getTraceAsString(),
            ]);
        }

        if ($response === null) {
            return $command;
        }

        $inspectionResult = $response->getInspectionResult();
        $indexStatusResult = $inspectionResult->getIndexStatusResult() ?? null;
        $ampResult = $inspectionResult->getAmpResult() ?? null;

        $isConclusiveForWeb = $indexStatusResult && ($indexStatusResult->getVerdict() === 'PASS' || $indexStatusResult->getVerdict() === 'FAIL');
        $isConclusiveForAmp = !$node::schema()->hasField('amp_enabled') || ($ampResult && ($ampResult->getVerdict() === 'PASS' || $ampResult->getVerdict() === 'FAIL'));

        if ($isConclusiveForWeb && $isConclusiveForAmp) {
            $this->publishEvent($command, $pbjx, $node, 'google', [
                'response'      => $response,
                'is_conclusive' => true,
            ]);
            return $command;
        }

        $retries = $command->get('ctx_retries');
        $maxRetries = $this->flags->getInt('inspect_seo_max_retries', 5);
        if ($retries < $maxRetries) {
            $command->addToSet('search_engines', ['google']);
            return $command;
        }

        $this->publishEvent($command, $pbjx, $node, 'google', [
            'response'      => $response,
            'is_conclusive' => false,
        ]);

        return $command;
    }

    protected function publishEvent(Message $command, Pbjx $pbjx, Message $node, string $searchEngine, array $response): void
    {
        $event = SeoInspectedV1::create()
            ->set('node_ref', $node->generateNodeRef())
            ->set('search_engine', $searchEngine)
            ->set('inspection_response', json_encode($response));

        $pbjx
            ->copyContext($command, $event)
            ->publish($event);
    }
}
