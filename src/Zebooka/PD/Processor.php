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

        // tokenize
        try {
            $tokens = $this->tokenizer->tokenize($photoBunch);
        } catch (TokenizerException $e) {
            if (TokenizerException::NO_DATE_TIME_DETECTED == $e->getCode()) {
                $tokenizeErrorMessage = $this->translator->translate('error/tokenize/datetime');
            } else {
                throw $e;
            }
            $this->logger->addError($tokenizeErrorMessage);
            return false;
        } catch (ExifAnalyzerException $e) {
            if (ExifAnalyzerException::DIFFERENT_DATES == $e->getCode()) {
                $tokenizeErrorMessage = $this->translator->translate('error/exifAnalyzer/differentDates');
            } elseif (ExifAnalyzerException::DIFFERENT_CAMERAS == $e->getCode()) {
                $tokenizeErrorMessage = $this->translator->translate('error/exifAnalyzer/differentCameras');
            } else {
                throw $e;
            }
            $this->logger->addError($tokenizeErrorMessage);
            return false;
        }

        // skip cameras
        if ($this->configure->cameras && !in_array($tokens->camera, $this->configure->cameras)) {
            $this->logger->addNotice(
                $this->translator->translate(
                    'skippedBecauseCameraNotInList',
                    array($tokens->camera, $photoBunch)
                )
            );
            return false;
        }

        // assemble
        try {
            // TODO: simulate problem — no files are placed -> wrong shot numbers
            $newBunchId = $this->assembler->assemble($tokens, $photoBunch); // TODO: implement some md5 hashes compare
        } catch (AssemblerException $e) {
            $this->logger->addError($this->translator->translate('error/unableToAssembleTokens', array($photoBunch)));
            return false;
        }

        // skip not changed
        if ($photoBunch->bunchId() === $newBunchId) {
            $this->logger->addNotice(
                $this->translator->translate(
                    'skippedBecauseSourceEqualsDestination',
                    array(count($photoBunch->extensions()), $photoBunch)
                )
            );
            return false;
        }

        // create dir if needed
        $dir = dirname($newBunchId);
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
