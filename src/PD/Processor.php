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
    private $bunchCache;
    private $executor;
    private $logger;
    private $translator;
    private $bytesProcessed = 0;

    public function __construct(
        Configure $configure,
        Tokenizer $tokenizer,
        Assembler $assembler,
        BunchCache $bunchCache,
        Executor $executor,
        Logger $logger,
        Translator $translator
    ) {
        $this->configure = $configure;
        $this->tokenizer = $tokenizer;
        $this->assembler = $assembler;
        $this->bunchCache = $bunchCache;
        $this->executor = $executor;
        $this->logger = $logger;
        $this->translator = $translator;
    }

    public function process(FileBunch $fileBunch, string $progress = ''): bool
    {
        $this->logger->addNotice(trim($progress . ' ' . $this->translator->translate('originalFileBunchPath', array($fileBunch))));

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

        // filter by regexps of exif (if any matches, then process)
        if ($this->configure->regexpExifFilter) {
            $matched = false;
            foreach ($fileBunch->exifs() as $extension => $exif) {
                foreach ($this->configure->regexpExifFilter as $key => $value) {
                    if (@preg_match($value, '') !== false) {
                        if (preg_match($value, (string)$exif->{$key})) {
                            $matched = true;
                            break 2;
                        }
                    } elseif ($exif->{$key} == $value) {
                        $matched = true;
                        break 2;
                    }
                }
            }
            if (!$matched) {
                $this->logger->addNotice(
                    $this->translator->translate('skipped/filteredByExifRegExp', [$fileBunch])
                );
                return false;
            }
        }

        // filter by  negative regexps of exif (if any matches, then skipped)
        if ($this->configure->regexpExifNegativeFilter) {
            $matched = false;
            foreach ($fileBunch->exifs() as $extension => $exif) {
                foreach ($this->configure->regexpExifNegativeFilter as $key => $value) {
                    if (@preg_match($value, '') !== false) {
                        if (preg_match($value, (string)$exif->{$key})) {
                            $matched = true;
                            break 2;
                        }
                    } elseif ($exif->{$key} == $value) {
                        $matched = true;
                        break 2;
                    }
                }
            }
            if ($matched) {
                $this->logger->addNotice(
                    $this->translator->translate('skipped/filteredByExifRegExp', [$fileBunch])
                );
                return false;
            }
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

        $queue = array();

        // create dir if needed
        $dir = dirname($newBunchId);
        if (!is_dir($dir)) {
            $queue[] = new Executor\Command(
                'mkdir -p ' . escapeshellarg($dir),
                $this->translator->translate('error/unableToCreateDestinationDir', array($dir)),
                $this->translator->translate('createdDestinationDir', array($dir))
            );
        }

        // move/copy files
        $filesTransferred = false;
        foreach ($fileBunch->extensions() as $extension) {
            $from = $fileBunch->bunchId() . ($extension ? '.' . $extension : '');
            $this->bytesProcessed += filesize($from);
            // QUESTION: should we lowercase only primary extensions + known ones (xmp, txt) ?
            $to = $newBunchId . '.' . mb_strtolower($extension);
            if (($this->configure->regexpFilenameFilter && !preg_match($this->configure->regexpFilenameFilter, $to))
                || ($this->configure->regexpFilenameNegativeFilter && preg_match($this->configure->regexpFilenameNegativeFilter, $to))
            ) {
                $this->logger->addNotice($this->translator->translate('skipped/filteredByFileRegExp', [$to]));
            } elseif (is_file($to) && $this->configure->deleteDuplicates && !$this->configure->copy) {
                $queue[] = new Executor\Command(
                    'rm ' . escapeshellarg($from),
                    $this->translator->translate('error/unableToDelete', array($from)),
                    $this->translator->translate('fileDuplicateWasRemoved', array($extension))
                );
                $filesTransferred = true;
            } elseif (is_file($to) && (!$this->configure->deleteDuplicates || $this->configure->copy)) {
                $this->logger->addNotice($this->translator->translate('skipped/targetAlreadyExists', array($extension)));
            } elseif ($this->configure->copy) {
                $queue[] = new Executor\Command(
                    'cp ' . escapeshellarg($from) . ' ' . escapeshellarg($to),
                    $this->translator->translate('error/unableToCopy', array($from, $to)),
                    $this->translator->translate('newFilePath', array($to))
                );
                $filesTransferred = true;
            } else {
                $queue[] = new Executor\Command(
                    'mv ' . escapeshellarg($from) . ' ' . escapeshellarg($to),
                    $this->translator->translate('error/unableToMove', array($from, $to)),
                    $this->translator->translate('newFilePath', array($to))
                );
                $filesTransferred = true;
            }
        }
        if ($filesTransferred) {
            foreach ($queue as $command) {
                /** @var Executor\Command $command */
                if ($this->configure->saveCommandsFile && !$this->configure->simulate) {
                    file_put_contents($this->configure->saveCommandsFile, $command->command() . PHP_EOL, FILE_APPEND);
                }
                if ($this->configure->simulate) {
                    fwrite(STDOUT, $command->command() . PHP_EOL);
                } elseif (0 !== $this->executor->execute($command->command())) {
                    $this->logger->addError($command->errorMessage());
                } else {
                    $this->logger->addNotice($command->successMessage());
                }
            }
        }

        return true;
    }

    public function bytesProcessed(): int
    {
        return $this->bytesProcessed;
    }
}
