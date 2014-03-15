<?php

namespace Zebooka\PD;

use Monolog\Logger;

class Processor
{
    private $configure;
    private $logger;
    private $translator;

    public function __construct(Configure $configure, Logger $logger, Translator $translator)
    {
        $this->configure = $configure;
        $this->logger = $logger;
        $this->translator = $translator;
    }

    public function process(PhotoBunch $photoBunch)
    {
        $this->logger->addNotice($photoBunch);
    }
}
