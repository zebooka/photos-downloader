<?php

namespace Zebooka\PD;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zebooka\Translator\Translator;
use Zebooka\Translator\TranslatorFactory;
use Zebooka\Utils\Executor;
use Zebooka\Utils\Size;

class Command extends SymfonyCommand
{
    const AUTHOR = 'author';
    const CAMERAS = 'cameras';
    const COPY = 'copy';
    const FROM = 'from';
    const LIMIT = 'limit';
    const LIST_FILE = 'list-file';
    const NO_COMPARE_EXIFS = 'no-exif-compare';
    const NO_DELETE_DUPLICATES = 'no-delete-duplicates';
    const NO_RECURSIVE = 'no-recursive';
    const NO_SUBDIRS = 'no-subdirs';
    const PANORAMIC_RATIO = 'panoramic-ratio';
    const PREFER_EXIF_DT = 'prefer-exif-datetime';
    const REGEXP_EXIF_FILTER = 'exif-filter';
    const REGEXP_EXIF_NEGATIVE_FILTER = 'exif-negative-filter';
    const REGEXP_FILENAME_FILTER = 'filename-filter';
    const REGEXP_FILENAME_NEGATIVE_FILTER = 'filename-negative-filter';
    const SAVE_COMMANDS_FILE = 'save-commands';
    const SIMULATE = 'simulate';
    const SUBDIRS_FORMAT = 'subdirs-format';
    const TIMEZONE = 'timezone';
    const TO = 'to';
    const TOKENS_ADD = 'add-token';
    const TOKENS_DROP = 'drop-token';
    const TOKENS_DROP_UNKNOWN = 'drop-unknown-tokens';

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

        $this->addOption(self::AUTHOR, 'a', InputOption::VALUE_REQUIRED, $this->td('P_AUTHOR'));
        $this->addOption(self::CAMERAS, 'd', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $this->td('P_CAMERAS'));
        $this->addOption(self::COPY, 'c', InputOption::VALUE_NONE, $this->td('P_COPY'));
        $this->addOption(self::FROM, 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $this->td('P_FROM'));
        $this->addOption(self::LIMIT, 'l', InputOption::VALUE_REQUIRED, $this->td('P_LIMIT'));
        $this->addOption(self::LIST_FILE, 'F', InputOption::VALUE_REQUIRED, $this->td('P_LIST_FILE'));
        $this->addOption(self::NO_COMPARE_EXIFS, 'B', InputOption::VALUE_NONE, $this->td('P_NO_COMPARE_EXIFS'));
        $this->addOption(self::NO_DELETE_DUPLICATES, 'Z', InputOption::VALUE_NONE, $this->td('P_NO_DELETE_DUPLICATES'));
        $this->addOption(self::NO_RECURSIVE, 'R', InputOption::VALUE_NONE, $this->td('P_NO_RECURSIVE'));
        $this->addOption(self::NO_SUBDIRS, 'D', InputOption::VALUE_NONE, $this->td('P_NO_SUBDIRS'));
        $this->addOption(self::PANORAMIC_RATIO, 'p', InputOption::VALUE_REQUIRED, $this->td('P_PANORAMIC_RATIO'));
        $this->addOption(self::PREFER_EXIF_DT, 'T', InputOption::VALUE_NONE, $this->td('P_PREFER_EXIF_DT'));
        $this->addOption(self::REGEXP_EXIF_FILTER, 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $this->td('P_REGEXP_EXIF_FILTER'));
        $this->addOption(self::REGEXP_EXIF_NEGATIVE_FILTER, 'I', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $this->td('P_REGEXP_EXIF_NEGATIVE_FILTER'));
        $this->addOption(self::REGEXP_FILENAME_FILTER, 'g', InputOption::VALUE_REQUIRED, $this->td('P_REGEXP_FILENAME_FILTER'));
        $this->addOption(self::REGEXP_FILENAME_NEGATIVE_FILTER, 'G', InputOption::VALUE_REQUIRED, $this->td('P_REGEXP_FILENAME_NEGATIVE_FILTER'));
        $this->addOption(self::SAVE_COMMANDS_FILE, 'S', InputOption::VALUE_REQUIRED, $this->td('P_SAVE_COMMANDS_FILE'));
        $this->addOption(self::SIMULATE, 's', InputOption::VALUE_NONE, $this->td('P_SIMULATE'));
        $this->addOption(self::SUBDIRS_FORMAT, 'k', InputOption::VALUE_REQUIRED, $this->td('P_SUBDIRS_FORMAT'));
        $this->addOption(self::TIMEZONE, 'z', InputOption::VALUE_REQUIRED, $this->td('P_TIMEZONE'));
        $this->addOption(self::TO, 't', InputOption::VALUE_REQUIRED, $this->td('P_TO'));
        $this->addOption(self::TOKENS_ADD, 'x', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $this->td('P_TOKENS_ADD'));
        $this->addOption(self::TOKENS_DROP, 'y', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $this->td('P_TOKENS_DROP'));
        $this->addOption(self::TOKENS_DROP_UNKNOWN, 'Y', InputOption::VALUE_NONE, $this->td('P_TOKENS_DROP_UNKNOWN'));
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $tokensConfigs = [
            // TODO custom config url
            getcwd() . '/.photos-downloader-rc.json',
            ($_SERVER['HOME'] ?? getcwd()) . '/.photos-downloader-rc.json',
            ($_SERVER['HOME'] ?? getcwd()) . '/.config/photos-downloader-rc.json',
            ($_SERVER['HOME'] ?? getcwd()) . '/.local/photos-downloader-rc.json',
        ];
        $tokensConfigs = array_values(array_filter($tokensConfigs, 'is_file'));

        $configure = isset($tokensConfigs[0])
            ? Configure::constructFromConfigFilename($tokensConfigs[0])
            : Configure::constructEmpty();
        $logger = LoggerFactory::logger($input);

        if (!count(Configure::from($input))) {
            throw new \UnexpectedValueException($this->t('error/noFromPathSpecified'));
        }

        if (!strlen((string)$input->getOption(self::TO))) {
            throw new \UnexpectedValueException($this->t('error/noToPathSpecified'));
        }

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
        $processor = new Processor(
            $input,
            $output,
            $configure,
            new Tokenizer($configure, $input, new ExifAnalyzer($configure)),
            new Assembler($configure, $input, new Hashinator()),
            new BunchCache(),
            new Executor(),
            $logger,
            $this->translator
        );
        $limit = $input->getOption(self::LIMIT);

        $scanner = scannerFactory($input);
        if (!isFromStdin($input) && !isPipelineForced($input)) {
            $scanner = iterator_to_array($scanner);
            $c = count($scanner);
        }

        foreach ($scanner as $i => $bunch) {
            if (isset($c)) {
                // write percentage
                $progress = sprintf("% 3d%%", $i / $c);
                $pad = str_repeat(' ', strlen($progress));
            } else {
                $progress = $pad = '';
            }

            // processor generates command
            foreach ($processor->process($bunch) as $j => $cmd) {
                $args = implode(' ', array_slice($cmd, 1));

                $output->writeln(
                    ($j ? $pad : "<fg=white>{$progress}</>") . ' '
                        . ('mkdir' === $cmd[0] ? "<fg=bright-blue;options=bold>{$cmd[0]}</><fg=gray>{$args}</>" : '')
                        . ('cp' === $cmd[0] ? "<fg=green;options=bold>{$cmd[0]}</><fg=gray>{$args}</>" : '')
                        . ('mv' === $cmd[0] ? "<fg=cyan;options=bold>{$cmd[0]}</><fg=gray>{$args}</>" : '')
                        . ('rm' === $cmd[0] ? "<bg=red;fg=white;options=bold>{$cmd[0]}</><fg=red>{$args}</>" : '')
                        . (!in_array($cmd[0], ['mkdir', 'cp', 'mv', 'rm']) ? "<fg=white;options=bold,reverse>{$cmd[0]}</><fg=gray>{$args}</>" : '')
                );
                passthru(implode(' ', $cmd). ' >&2');
            }

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
