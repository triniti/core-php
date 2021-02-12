<?php
declare(strict_types=1);

namespace Triniti\Tests\Sys;

use Acme\Schemas\News\Node\ArticleV1;
use Acme\Schemas\Sys\Node\FlagsetV1;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\UriTemplate\UriTemplateService;
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
        $node = ArticleV1::fromArray([
            '_id'        => '91277082-41b4-11e9-a4fd-30342d323838',
            'created_at' => '1457466497000000',
        ])->set('slug', '2019/03/04/luke-perry-dead-dies-stroke-beverly-hills-90210-riverdale');
        $command = PurgeCacheV1::create()->set('node_ref', NodeRef::fromNode($node));
        $this->ncr->putNode($node);

        $flagset = FlagsetV1::fromArray(['_id' => 'test'])
            ->addToMap('booleans', 'google_amp_purge_cache_disabled', false);
        $this->ncr->putNode($flagset);

        $handler = new PurgeCacheHandler($this->ncr, [
            'google_amp_private_key' => '-----BEGIN RSA PRIVATE KEY----- test -----END RSA PRIVATE KEY-----',
        ]);
        $handler->handleCommand($command, $this->pbjx);

        $this->assertTrue(true);
    }
}

