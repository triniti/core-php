<?php
declare(strict_types=1);

namespace Triniti\Tests\Sys;

use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\WellKnown\NodeRef;
use Triniti\Schemas\Sys\Command\PurgeCacheV1;
use Triniti\Sys\PurgeCacheHandler;
use Triniti\Tests\AbstractPbjxTest;

class PurgeCacheHandlerTest extends AbstractPbjxTest
{
    private InMemoryNcr $ncr;

    protected function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
    }

    public function testHandle()
    {
        $node = ArticleV1::create()->set('slug', '2019/03/04/luke-perry-dead-dies-stroke-beverly-hills-90210-riverdale');
        $this->ncr->putNode($node);

        $command = PurgeCacheV1::create()->set('node_ref', NodeRef::fromNode($node));
        $handler = new PurgeCacheHandler($this->ncr, [
            'google_amp_private_key' => '-----BEGIN RSA PRIVATE KEY----- test -----END RSA PRIVATE KEY-----',
        ]);

        $handler->handleCommand($command, $this->pbjx);
        $this->assertTrue(true);
    }
}

