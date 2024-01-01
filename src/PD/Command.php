<?php

namespace Zebooka\PD;

use PhpParser\Node\Stmt\Throw_;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zebooka\Translator\Translator;
use Zebooka\Translator\TranslatorFactory;
use Zebooka\Utils\Size;

class Command extends SymfonyCommand
{
    const SIMULATE = 'simulate';
    const SAVE_COMMANDS_FILE = 'save-commands';
    const LIMIT = 'limit';
    const NO_RECURSIVE = 'no-recursive';

    const FROM = 'from';
    const LIST_FILE = 'list-file';
    const TO = 'to';
    const NO_SUBDIRS = 'no-subdirs';
    const SUBDIRS_FORMAT = 'subdirs-format';
    const COPY = 'copy';
    const NO_DELETE_DUPLICATES = 'no-delete-duplicates';
    const AUTHOR = 'author';
    const CAMERAS = 'cameras';
    const PREFER_EXIF_DT = 'prefer-exif-datetime';
    const TIMEZONE = 'timezone';
    const TOKENS_ADD = 'add-token';
    const TOKENS_DROP = 'drop-token';
    const TOKENS_DROP_UNKNOWN = 'drop-unknown-tokens';
    const NO_COMPARE_EXIFS = 'no-exif-compare';
    const REGEXP_EXIF_FILTER = 'exif-filter';
    const REGEXP_EXIF_NEGATIVE_FILTER = 'exif-negative-filter';
    const REGEXP_FILENAME_FILTER = 'filename-filter';
    const REGEXP_FILENAME_NEGATIVE_FILTER = 'filename-negative-filter';
    const PANORAMIC_RATIO = 'panoramic-ratio';

    /** @var string */
    private $locale;

    /** @var Translator */
    private $translator;

    public function __construct(string $name = null, string $locale = 'en')
    {
        $this->locale = $locale;
        parent::__construct($name);
    }

    private function t(string $translationKey, array $params = []): string
    {
        return $this->translator->translate($translationKey, $params);
    }

    private function td(string $descriptionKey): string
    {
        return $this->translator->translate('parameters/description/' . $descriptionKey, []);
    }

    protected function configure()
    {
        parent::configure();
        $this->translator = TranslatorFactory::translator(__DIR__ . '/../../res', $this->locale);

        $this->addOption(self::SIMULATE, 's', InputOption::VALUE_NONE, $this->td('P_SIMULATE'));
        $this->addOption(self::SAVE_COMMANDS_FILE, 'S', InputOption::VALUE_REQUIRED, $this->td('P_SAVE_COMMANDS_FILE'));
        $this->addOption(self::LIMIT, 'l', InputOption::VALUE_REQUIRED, $this->td('P_LIMIT'));
        $this->addOption(self::NO_RECURSIVE, 'R', InputOption::VALUE_NONE, $this->td('P_NO_RECURSIVE'));
        $this->addOption(self::FROM, 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $this->td('P_FROM'));
        $this->addOption(self::LIST_FILE, 'F', InputOption::VALUE_REQUIRED, $this->td('P_LIST_FILE'));
        $this->addOption(self::TO, 't', InputOption::VALUE_REQUIRED, $this->td('P_TO'));
        $this->addOption(self::NO_SUBDIRS, 'D', InputOption::VALUE_NONE, $this->td('P_NO_SUBDIRS'));
        $this->addOption(self::SUBDIRS_FORMAT, 'k', InputOption::VALUE_REQUIRED, $this->td('P_SUBDIRS_FORMAT'));
        $this->addOption(self::COPY, 'c', InputOption::VALUE_NONE, $this->td('P_COPY'));
        $this->addOption(self::NO_DELETE_DUPLICATES, 'Z', InputOption::VALUE_NONE, $this->td('P_NO_DELETE_DUPLICATES'));
        $this->addOption(self::AUTHOR, 'a', InputOption::VALUE_REQUIRED, $this->td('P_AUTHOR'));
        $this->addOption(self::CAMERAS, 'd', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $this->td('P_CAMERAS'));
        $this->addOption(self::PREFER_EXIF_DT, 'T', InputOption::VALUE_NONE, $this->td('P_PREFER_EXIF_DT'));
        $this->addOption(self::TIMEZONE, 'z', InputOption::VALUE_REQUIRED, $this->td('P_TIMEZONE'));
        $this->addOption(self::TOKENS_ADD, 'x', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $this->td('P_TOKENS_ADD'));
        $this->addOption(self::TOKENS_DROP, 'y', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $this->td('P_TOKENS_DROP'));
        $this->addOption(self::TOKENS_DROP_UNKNOWN, 'Y', InputOption::VALUE_NONE, $this->td('P_TOKENS_DROP_UNKNOWN'));
        $this->addOption(self::NO_COMPARE_EXIFS, 'B', InputOption::VALUE_NONE, $this->td('P_NO_COMPARE_EXIFS'));
        $this->addOption(self::REGEXP_EXIF_FILTER, 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $this->td('P_REGEXP_EXIF_FILTER'));
        $this->addOption(self::REGEXP_EXIF_NEGATIVE_FILTER, 'I', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $this->td('P_REGEXP_EXIF_NEGATIVE_FILTER'));
        $this->addOption(self::REGEXP_FILENAME_FILTER, 'g', InputOption::VALUE_REQUIRED, $this->td('P_REGEXP_FILENAME_FILTER'));
        $this->addOption(self::REGEXP_FILENAME_NEGATIVE_FILTER, 'G', InputOption::VALUE_REQUIRED, $this->td('P_REGEXP_FILENAME_NEGATIVE_FILTER'));
        $this->addOption(self::PANORAMIC_RATIO, 'p', InputOption::VALUE_REQUIRED, $this->td('P_PANORAMIC_RATIO'));
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        // TODO: remove
        $configure = new \Zebooka\PD\Configure(
            $_SERVER['argv'],
            json_decode(file_get_contents(__DIR__ . '/../../res/tokens.json'), true)
        );
        $logger = \Zebooka\PD\LoggerFactory::logger($configure);
        // TODO: END remove

        // TODO add FROM/TO validation

        $output->write($this->t('appName') . ' ');
        $output->writeln('<fg=green>' . VERSION . '</>');
        $output->writeln($this->t('copyrightInfo'));

        if ($input instanceof ArgvInput || $input instanceof ArrayInput) {
            $output->writeln($this->t('currentConfiguration', [$this->getName(), $input]));
        }

        // validate regexps
        $regexps = [
            self::REGEXP_FILENAME_FILTER,
            self::REGEXP_FILENAME_NEGATIVE_FILTER,
        ];
        foreach ($regexps as $regexp) {
            @preg_match($input->getOption($regexp) ?: '/test/', 'test');
            if (preg_last_error()) {
                throw new \UnexpectedValueException($this->t('error/regexpInvalid', [$input->getOption($regexp)]));
            }
        }

        // processing
        $processor = new \Zebooka\PD\Processor(
            $input,
            $output,
            $configure,
            new \Zebooka\PD\Tokenizer($configure, new \Zebooka\PD\ExifAnalyzer($configure)),
            new \Zebooka\PD\Assembler($configure, new \Zebooka\PD\Hashinator()),
            new \Zebooka\PD\BunchCache(),
            new \Zebooka\Utils\Executor(),
            $logger,
            $this->translator
        );
        $i = 0;
        $limit = $input->getOption(self::LIMIT);
        $scannerIterator = new \Zebooka\PD\ScannerIterator($configure->from, $configure->recursive);
        foreach ($scannerIterator as $fileBunch) {
            $i++;
            $processor->process(
                $fileBunch,
                "{$i} >> {$scannerIterator->getScanner()->dirsLeft()}:{$scannerIterator->getScanner()->filesLeft()}"
            );

            if ($limit && $i >= $limit) {
                $output->writeln($this->t('processedFilesLimitWasReached', [$limit]));
                break;
            }
        }

        $output->writeln(
            $this->t(
                'xUsageStatistics',
                [
                    $i,
                    Size::humanReadableSize($processor->bytesProcessed()),
                    Size::humanReadableSize(memory_get_peak_usage(true))
                ]
            )
        );

        return 0;
    }
}
