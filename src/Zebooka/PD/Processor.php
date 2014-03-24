<?php

namespace Zebooka\PD;

use Monolog\Logger;
use Zebooka\Translator\Translator;

class Processor
{
    private $configure;
    private $tokenizer;
    private $assembler;
    private $executor;
    private $logger;
    private $translator;
    private $bytesTransferred = 0;

    public function __construct(
        Configure $configure,
        Tokenizer $tokenizer,
        Assembler $assembler,
        Executor $executor,
        Logger $logger,
        Translator $translator
    ) {
        $this->configure = $configure;
        $this->tokenizer = $tokenizer;
        $this->assembler = $assembler;
        $this->executor = $executor;
        $this->logger = $logger;
        $this->translator = $translator;
    }

    public function process(PhotoBunch $photoBunch)
    {
        $this->logger->addNotice($this->translator->translate('originalPhotoBunchPath', array($photoBunch)));
        $tokens = $this->tokenizer->tokenize($photoBunch); // TODO: try-catch
        if ($this->configure->cameras && !in_array($tokens->camera, $this->configure->cameras)) {
            $this->logger->addNotice(
                $this->translator->translate(
                    'skippedBecauseCameraNotInList',
                    array($tokens->camera, $photoBunch)
                )
            );
            return false;
        }
        $newBunchId = $this->assembler->assemble($tokens); // TODO: try-catch
        if (false === $newBunchId) {
            $this->logger->addNotice($this->translator->translate('error/unableToAssembleTokens', array($photoBunch)));
            return false;
        }
        if ($photoBunch->bunchId() === $newBunchId) {
            $this->logger->addNotice(
                $this->translator->translate(
                    'skippedBecauseSourceEqualsDestination',
                    array(count($photoBunch->extensions()), $photoBunch)
                )
            );
            return false;
        }
        $dir = dirname($newBunchId);
        // create dir if needed
        if (!is_dir($dir)) {
            $cmd = 'mkdir -p ' . escapeshellarg($dir);
            if ($this->configure->simulate) {
                fwrite(STDOUT, $cmd . PHP_EOL);
            } else {
                if (0 !== $this->executor->execute($cmd)) {
                    $this->logger->addError($this->translator->translate('error/unableToCreateDestinationDir', array($dir)));
                    return false; // we fail to create destination dir and move/copy will also fail
                } else {
                    $this->logger->addNotice($this->translator->translate('createdDestinationDir', array($dir)));
                }
            }
        }
        // move/copy files
        foreach ($photoBunch->extensions() as $extension) {
            $from = $photoBunch->bunchId() . '.' . $extension;
            $to = $newBunchId . '.' . $extension;
            $cmd = (dirname($from) !== $dir && $this->configure->copy ? 'cp' : 'mv') .
                ' ' . escapeshellarg($from) . ' ' . escapeshellarg($to);
            if ($this->configure->simulate) {
                fwrite(STDOUT, $cmd . PHP_EOL);
            } else {
                if (0 !== $this->executor->execute($cmd)) {
                    $this->logger->addError(
                        $this->translator->translate(
                            'error/unableToMoveOrCopy',
                            array(intval($this->configure->copy), $from, $to)
                        )
                    );
                } else {
                    $this->logger->addNotice($this->translator->translate('newFilePath', array($to)));
                    $this->bytesTransferred += filesize($to);
                }
            }
        }
        return true;
    }

    public function bytesTransferred()
    {
        return intval($this->bytesTransferred);
    }
}
