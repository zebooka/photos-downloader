<?php

namespace Zebooka\PD;

use Monolog\Logger;
use Zebooka\Translator\Translator;
use Zebooka\Utils\Executor;

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

    public function process(FileBunch $fileBunch)
    {
        $this->logger->addNotice($this->translator->translate('originalFileBunchPath', array($fileBunch)));

        // tokenize
        try {
            $tokens = $this->tokenizer->tokenize($fileBunch);
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
                    'skipped/CameraNotInList',
                    array($tokens->camera, $fileBunch)
                )
            );
            return false;
        }

        // assemble
        try {
            $newBunchId = $this->assembler->assemble($tokens, $fileBunch);
        } catch (AssemblerException $e) {
            $this->logger->addError($this->translator->translate('error/unableToAssembleTokens', array($fileBunch)));
            return false;
        }

        // skip not changed
        if ($fileBunch->bunchId() === $newBunchId) {
            $this->logger->addNotice(
                $this->translator->translate('skipped/sourceEqualsDestination', array(count($fileBunch->extensions()), $fileBunch))
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
        foreach ($fileBunch->extensions() as $extension) {
            $from = $fileBunch->bunchId() . '.' . $extension;
            // QUESTION: should we lowercase only primary extensions + known ones (xmp, txt) ?
            $to = $newBunchId . '.' . mb_strtolower($extension);
            $fileTransfered = $fileRemoved = false;
            if (($this->configure->regexpFilter && !preg_match($this->configure->regexpFilter, $to))
            || ($this->configure->regexpNegativeFilter && preg_match($this->configure->regexpNegativeFilter, $to))) {
                $this->logger->addNotice($this->translator->translate('skipped/filteredByRegExp', array($to)));
                continue;
            } elseif (is_file($to) && $this->configure->deleteDuplicates && !$this->configure->copy) {
                $cmd = 'rm ' . escapeshellarg($from);
                $errorMessage = $this->translator->translate('error/unableToDelete', array($from));
                $fileRemoved = true;
            } elseif (is_file($to) && (!$this->configure->deleteDuplicates || $this->configure->copy)) {
                $this->logger->addNotice($this->translator->translate('skipped/targetAlreadyExists', array($extension)));
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
