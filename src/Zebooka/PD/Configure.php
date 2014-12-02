<?php

namespace Zebooka\PD;

use Zebooka\Utils\Cli\Parameters;

/**
 * @property bool $help
 * @property bool $verboseLevel
 * @property null|string $logFile
 * @property int $logLevel
 * @property bool $simulate
 * @property int $limit
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
 * @property bool $compareExifs
 * @property float $panoramicRatio
 * @property null|string $executableName
 */
class Configure
{
    const PATHS_FROM_STDIN = '-';
    const KEEP_IN_PLACE = '-';

    const P_HELP = 'h';
    const P_VERBOSE_LEVEL = 'E';
    const P_LOG_FILE = 'o';
    const P_LOG_LEVEL = 'O';
    const P_SIMULATE = 's';
    const P_LIMIT = 'l';
    const P_NO_RECURSIVE = 'R';
    const P_FROM = 'f';
    const P_LIST_FILE = 'F';
    const P_TO = 't';
    const P_NO_SUBDIRS = 'D';
    const P_COPY = 'c';
    const P_NO_DELETE_DUPLICATES = 'Z';
    const P_AUTHOR = 'a';
    const P_CAMERAS = 'd';
    const P_TOKENS_ADD = 'x';
    const P_TOKENS_DROP = 'y';
    const P_TOKENS_DROP_UNKNOWN = 'Y';
    const P_NO_COMPARE_EXIFS = 'B';
    const P_REGEXP_FILTER = 'g';
    const P_PANORAMIC_RATIO = 'p';

    public $help = false;
    public $verboseLevel = 100;
    public $logFile = null;
    public $logLevel = 250;
    public $simulate = false;
    public $limit = 0;
    public $recursive = true;
    public $from = array();
    public $listFile = null;
    public $to = self::KEEP_IN_PLACE;
    public $subDirectoriesStructure = true;
    public $copy = false;
    public $deleteDuplicates = true;
    public $author = null;
    public $cameras = array();
    public $tokensToAdd = array();
    public $tokensToDrop = array();
    public $tokensDropUnknown = false;
    public $compareExifs = true;
    public $regexpFilter = null;
    public $panoramicRatio = 2.0;
    public $executableName;

    private $knownAuthors = array();
    private $knownCameras = array();
    private $knownTokens = array();

    public function __construct(array $argv, array $knownLists)
    {
        $argv = $this->decodeArgv($argv);

        $this->help = !empty($argv->{self::P_HELP});
        $this->verboseLevel = (array_key_exists(self::P_VERBOSE_LEVEL, $argv) ? intval($argv->{self::P_VERBOSE_LEVEL}) : $this->verboseLevel);
        $this->logFile = (array_key_exists(self::P_LOG_FILE, $argv) ? strval($argv->{self::P_LOG_FILE}) : $this->logFile);
        $this->logLevel = (array_key_exists(self::P_LOG_LEVEL, $argv) ? intval($argv->{self::P_LOG_LEVEL}) : $this->logLevel);
        $this->simulate = !empty($argv->{self::P_SIMULATE});
        $this->limit = (array_key_exists(self::P_LIMIT, $argv) ? intval($argv->{self::P_LIMIT}) : $this->limit);
        $this->recursive = empty($argv->{self::P_NO_RECURSIVE});
        $this->from = (array_key_exists(self::P_FROM, $argv) ? $argv->{self::P_FROM} : $this->from);
        $this->listFile = (array_key_exists(self::P_LIST_FILE, $argv) ? $argv->{self::P_LIST_FILE} : $this->listFile);
        $this->to = (array_key_exists(self::P_TO, $argv) ? strval($argv->{self::P_TO}) : $this->to);
        $this->subDirectoriesStructure = empty($argv->{self::P_NO_SUBDIRS});
        $this->copy = !empty($argv->{self::P_COPY});
        $this->deleteDuplicates = empty($argv->{self::P_NO_DELETE_DUPLICATES});
        $this->author = (array_key_exists(self::P_AUTHOR, $argv) ? strval($argv->{self::P_AUTHOR}) : $this->author);
        $this->cameras = $this->splitSpaceSeparated(array_key_exists(self::P_CAMERAS, $argv) ? $argv->{self::P_CAMERAS} : $this->cameras);
        $this->tokensToAdd = $this->splitSpaceSeparated(array_key_exists(self::P_TOKENS_ADD, $argv) ? $argv->{self::P_TOKENS_ADD} : $this->tokensToAdd);
        $this->tokensToDrop = $this->splitSpaceSeparated(array_key_exists(self::P_TOKENS_DROP, $argv) ? $argv->{self::P_TOKENS_DROP} : $this->tokensToDrop);
        $this->tokensDropUnknown = !empty($argv->{self::P_TOKENS_DROP_UNKNOWN});
        $this->compareExifs = empty($argv->{self::P_NO_COMPARE_EXIFS});
        $this->regexpFilter = (array_key_exists(self::P_REGEXP_FILTER, $argv) ? strval($argv->{self::P_REGEXP_FILTER}) : $this->regexpFilter);
        $this->panoramicRatio = (array_key_exists(self::P_PANORAMIC_RATIO, $argv) ? strval($argv->{self::P_PANORAMIC_RATIO}) : $this->panoramicRatio);
        $this->from = array_unique(array_merge($this->from, array_slice($argv->positionedParameters(), 1)));
        $positionedParameters = $argv->positionedParameters();
        $this->executableName = (isset($positionedParameters[0]) ? $positionedParameters[0] : null);

        $this->knownAuthors = (isset($knownLists['authors']) && is_array($knownLists['authors']) ? $knownLists['authors'] : $this->knownAuthors);
        $this->knownCameras = (isset($knownLists['cameras']) && is_array($knownLists['cameras']) ? $knownLists['cameras'] : $this->knownCameras);
        $this->knownTokens = (isset($knownLists['tokens']) && is_array($knownLists['tokens']) ? $knownLists['tokens'] : $this->knownTokens);
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
        return Parameters::createFromArgv(
            $argv,
            self::parametersRequiringValues(),
            self::parametersUsableMultipleTimes()
        );
    }

    public function argv()
    {
        $parameters = new Parameters($this->encodeParameters());
        return $parameters->argv(
            self::parametersRequiringValues(),
            self::parametersUsableMultipleTimes()
        );
    }

    /**
     * @return string[]
     */
    public function knownAuthors()
    {
        return $this->knownAuthors;
    }

    /**
     * @return string[]
     */
    public function knownCameras()
    {
        return array_keys($this->knownCameras);
    }

    public function camerasConfigure()
    {
        return $this->knownCameras;
    }

    /**
     * @return string[]
     */
    public function knownTokens()
    {
        return array_keys($this->knownTokens);
    }

    public function tokensConfigure()
    {
        return $this->knownTokens;
    }

    private function encodeParameters()
    {
        return array(
            0 => $this->executableName,
            self::P_HELP => $this->help,
            self::P_VERBOSE_LEVEL => $this->verboseLevel,
            self::P_LOG_FILE => $this->logFile,
            self::P_LOG_LEVEL => $this->logLevel,
            self::P_SIMULATE => $this->simulate,
            self::P_LIMIT => $this->limit,
            self::P_NO_RECURSIVE => !$this->recursive,
            self::P_FROM => $this->from,
            self::P_LIST_FILE => $this->listFile,
            self::P_TO => $this->to,
            self::P_NO_SUBDIRS => !$this->subDirectoriesStructure,
            self::P_COPY => $this->copy,
            self::P_NO_DELETE_DUPLICATES => !$this->deleteDuplicates,
            self::P_AUTHOR => $this->author,
            self::P_TOKENS_ADD => $this->tokensToAdd,
            self::P_TOKENS_DROP => $this->tokensToDrop,
            self::P_TOKENS_DROP_UNKNOWN => $this->tokensDropUnknown,
            self::P_NO_COMPARE_EXIFS => !$this->compareExifs,
            self::P_REGEXP_FILTER => $this->regexpFilter,
            self::P_PANORAMIC_RATIO => $this->panoramicRatio,
        );
    }

    public static function parametersRequiringValues()
    {
        return array(
            self::P_VERBOSE_LEVEL,
            self::P_LOG_FILE,
            self::P_LOG_LEVEL,
            self::P_LIMIT,
            self::P_FROM,
            self::P_LIST_FILE,
            self::P_TO,
            self::P_AUTHOR,
            self::P_CAMERAS,
            self::P_TOKENS_ADD,
            self::P_TOKENS_DROP,
            self::P_REGEXP_FILTER,
            self::P_PANORAMIC_RATIO,
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
