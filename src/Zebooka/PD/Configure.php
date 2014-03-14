<?php

namespace Zebooka\PD;

use Zebooka\Utils\Cli\Parameters;

/**
 * @property bool $help
 * @property bool $debug
 * @property bool $simulate
 * @property false|int $limit
 * @property bool $recursive
 * @property array $from
 * @property string $to
 * @property bool $subDirectoriesStructure
 * @property bool $copy
 * @property bool $deleteDuplicates
 * @property null|string $author
 * @property array $cameras
 * @property array $tokensToAdd
 * @property array $tokensToDrop
 * @property bool $tokensDropUnknown
 * @property array $positionedParameters
 * @property null|string $logFile
 * @property int $logLevel
 */
class Configure
{
    const PATHS_FROM_STDIN = '-';
    const KEEP_IN_PLACE = '-';

    const P_HELP = 'h';
    const P_DEBUG = 'E';
    const P_LOG_FILE = 'o';
    const P_LOG_LEVEL = 'O';
    const P_SIMULATE = 's';
    const P_LIMIT = 'l';
    const P_NO_RECURSIVE = 'R';
    const P_FROM = 'f';
    const P_TO = 't';
    const P_NO_SUBDIRS = 'D';
    const P_COPY = 'c';
    const P_NO_DELETE_DUPLICATES = 'Z';
    const P_AUTHOR = 'a';
    const P_CAMERAS = 'd';
    const P_TOKENS_ADD = 'x';
    const P_TOKENS_DROP = 'y';
    const P_TOKENS_DROP_UNKNOWN = 'X';

    const ERROR_NO_FROM = 1;
    const ERROR_NO_TO = 2;

    public $help = false;
    public $debug = false;
    public $logFile = null;
    public $logLevel = 250;
    public $simulate = false;
    public $limit = false;
    public $recursive = true;
    public $from = array();
    public $to;
    public $subDirectoriesStructure = true;
    public $copy = false;
    public $deleteDuplicates = true;
    public $author = null;
    public $cameras = array();
    public $tokensToAdd = array();
    public $tokensToDrop = array();
    public $tokensDropUnknown = false;
    public $positionedParameters = array();

    public function __construct(array $argv)
    {
        $argv = $this->decodeArgv($argv);

        $this->help = !empty($argv->{self::P_HELP});
        $this->debug = !empty($argv->{self::P_DEBUG});
        $this->logFile = (array_key_exists(self::P_LOG_FILE, $argv) ? strval($argv->{self::P_LOG_FILE}) : $this->logFile);
        $this->logLevel = (array_key_exists(self::P_LOG_LEVEL, $argv) ? intval($argv->{self::P_LOG_LEVEL}) : $this->logLevel);
        $this->simulate = !empty($argv->{self::P_SIMULATE});
        $this->limit = (array_key_exists(self::P_LIMIT, $argv) ? intval($argv->{self::P_LIMIT}) : $this->limit);
        $this->recursive = empty($argv->{self::P_NO_RECURSIVE});
        $this->from = (array_key_exists(self::P_FROM, $argv) ? $argv->{self::P_FROM} : $this->from);
        $this->to = (array_key_exists(self::P_TO, $argv) ? strval($argv->{self::P_TO}) : $this->to);
        $this->subDirectoriesStructure = empty($argv->{self::P_NO_SUBDIRS});
        $this->copy = !empty($argv->{self::P_COPY});
        $this->deleteDuplicates = empty($argv->{self::P_NO_DELETE_DUPLICATES});
        $this->author = (array_key_exists(self::P_AUTHOR, $argv) ? strval($argv->{self::P_AUTHOR}) : $this->author);
        $this->cameras = $this->splitSpaceSeparated(array_key_exists(self::P_CAMERAS, $argv) ? $argv->{self::P_CAMERAS} : $this->cameras);
        $this->tokensToAdd = $this->splitSpaceSeparated(array_key_exists(self::P_TOKENS_ADD, $argv) ? $argv->{self::P_TOKENS_ADD} : $this->tokensToAdd);
        $this->tokensToDrop = $this->splitSpaceSeparated(array_key_exists(self::P_TOKENS_DROP, $argv) ? $argv->{self::P_TOKENS_DROP} : $this->tokensToDrop);
        $this->tokensDropUnknown = !empty($argv->{self::P_TOKENS_DROP_UNKNOWN});
        $this->positionedParameters = $argv->positionedParameters();
        $this->from = array_unique(array_merge($this->from, array_slice($this->positionedParameters, 1)));
    }

    private function splitSpaceSeparated(array $values)
    {
        $splitted = array();
        foreach ($values as $value) {
            $splitted = array_merge($splitted, preg_split('/[\\s,]+/', $value));
        }
        return array_unique($splitted);
    }

    private function decodeArgv(array $argv)
    {
        return new Parameters(
            $argv,
            self::parametersRequiringValues(),
            self::parametersUsableMultipleTimes()
        );
    }

    public static function parametersRequiringValues()
    {
        return array(
            self::P_LOG_FILE,
            self::P_LOG_LEVEL,
            self::P_LIMIT,
            self::P_FROM,
            self::P_TO,
            self::P_AUTHOR,
            self::P_CAMERAS,
            self::P_TOKENS_ADD,
            self::P_TOKENS_DROP,
        );
    }

    public static function parametersUsableMultipleTimes()
    {
        return array(
            self::P_FROM,
            self::P_CAMERAS,
            self::P_TOKENS_ADD,
            self::P_TOKENS_DROP,
        );
    }
}
