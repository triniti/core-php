<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbjx\EventSubscriber;

class SeoInspectedWatcher implements EventSubscriber
{
    private Ncr $ncr;

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:sys:event:seo-inspected' => 'onSeoInspected',
        ];
    }

    public function onSeoInspected(NodeProjectedEvent $pbjxEvent): bool {
        $node = $pbjxEvent->getLastEvent();
        $entity = $this->ncr->getNode($node->get('node_ref'));
        $inspectSeoResponse = json_decode($node->get('inspection_response', true));
        $status = null;

        if ($node->get('search_engine') == "google") {
            $successStates = ['INDEXING_ALLOWED', 'SUCCESSFUL'];
            $inspectionResult = $inspectSeoResponse->inspectionResult;
            $indexStatusResult = $inspectionResult->indexStatusResult;
            $webVerdict =  $indexStatusResult->verdict;
            $indexingState =  $indexStatusResult->indexingState;

            $webPassed = $webVerdict === "PASS" && in_array($indexingState, $successStates);
            $isUnlistedPassed = $entity->get('is_unlisted') && $webPassed;
            $hasFailed = $webVerdict === "FAIL";

            $ampEnabled = $entity->has('amp_enabled') && $entity->get('amp_enabled');
            $ampVerdict = null;
            $ampDisabledPassed = !$ampEnabled && $webPassed;

            if ($ampEnabled){
                if (isset($inspectionResult->ampResult) && isset($inspectionResult->ampResult->verdict)) {
                    $ampVerdict = $inspectionResult->ampResult->verdict;
                } else {
                    $ampVerdict = "FAIL";
                }
            }

            $hasAmpIssue = $ampEnabled && ($webVerdict !== "PASS" || $ampVerdict !== "PASS") || $ampDisabledPassed;
            $hasGeneralIssue = $hasFailed || $isUnlistedPassed || !$webPassed;

            $status = ($hasGeneralIssue || $hasAmpIssue) ? 'FAILED' : 'PASSED';
        }

        return $status == "PASSED";
    }
}
