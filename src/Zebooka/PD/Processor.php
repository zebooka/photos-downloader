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
    private $bytesProcessed = 0;

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
            } elseif (ExifAnalyzerException::EXIF_EXCEPTION == $e->getCode()) {
                $tokenizeErrorMessage = $this->translator->translate('error/exifAnalyzer/exifException');
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
            $newBunchId = $this->assembler->assemble($tokens, $photoBunch);
        } catch (AssemblerException $e) {
            $this->logger->addError($this->translator->translate('error/unableToAssembleTokens', array($photoBunch)));
            return false;
        }

        // skip not changed
        if ($photoBunch->bunchId() === $newBunchId) {
            $this->logger->addNotice(
                $this->translator->translate('skippedBecauseSourceEqualsDestination', array(count($photoBunch->extensions()), $photoBunch))
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
            // QUESTION: should we lowercase only photo extensions + known ones (xmp, txt) ?
            $to = $newBunchId . '.' . mb_strtolower($extension);
            $fileTransfered = $fileRemoved = false;
            if (is_file($to) && $this->configure->deleteDuplicates && !$this->configure->copy) {
                $cmd = 'rm ' . escapeshellarg($from);
                $errorMessage = $this->translator->translate('error/unableToDelete', array($from));
                $fileRemoved = true;
            } elseif (is_file($to) && (!$this->configure->deleteDuplicates || $this->configure->copy)) {
                $this->logger->addNotice($this->translator->translate('skippedBecauseTargetAlreadyExists', array($extension)));
                continue;
            } elseif ($this->configure->copy) {
                $cmd = 'cp ' . escapeshellarg($from) . ' ' . escapeshellarg($to);
                $errorMessage = $this->translator->translate('error/unableToCopy', array($from, $to));
                $fileTransfered = true;
            } else {
                $cmd = 'mv ' . escapeshellarg($from) . ' ' . escapeshellarg($to);
                $errorMessage = $this->translator->translate('error/unableToMove', array($from, $to));
                $fileTransfered = true;
            }
            if ($this->configure->simulate) {
                fwrite(STDOUT, $cmd . PHP_EOL);
            } else {
                if (0 !== $this->executor->execute($cmd)) {
                    $this->logger->addError($errorMessage);
                } else {
                    if ($fileTransfered) {
                        $this->logger->addNotice($this->translator->translate('newFilePath', array($to)));
                        if (is_file($to)) {
                            $this->bytesProcessed += filesize($to);
                        }
                    } elseif ($fileRemoved) {
                        $this->logger->addNotice($this->translator->translate('fileDuplicateWasRemoved', array($extension)));
                    }
                }
            }
        }

        return true;
    }

    public function bytesProcessed()
    {
        return intval($this->bytesProcessed);
    }
}
