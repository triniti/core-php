<?php
declare(strict_types=1);

namespace Triniti\Sys;

class IndexingFailureContext
{
    public Message $command; 
    public Pbjx $pbjx; 
    public Message $article; 
    public bool $shouldRetry; 
    public InspectUrlIndexResponse $inspectSeoUrlIndexResponse;
    public string $failMessage = '';
    public $failureCallback = null;

    public function __construct(
        Message $command, 
        Pbjx $pbjx, 
        Message $article, 
        bool $shouldRetry, 
        InspectUrlIndexResponse $inspectSeoUrlIndexResponse,
        string $failMessage = '',
        ?callable $failureCallback = null
    ) {
        $this->command = $command;
        $this->pbjx = $pbjx;
        $this->article = $article;
        $this->shouldRetry = $shouldRetry;
        $this->$inspectSeoUrlIndexResponse = $inspectSeoUrlIndexResponse;
        $this->failMessage = $failMessage;
        $this->failureCallback = $failureCallback;
    }
}