<?php

namespace Zebooka\PD;

use Symfony\Component\Console\Input\InputInterface;

trait InputTrait
{
    private function input($opts = [])
    {
        $defaults = [
            Command::AUTHOR => null,
            Command::CAMERAS => [],
            Command::COPY => false,
            Command::NO_COMPARE_EXIFS => false,
            Command::PANORAMIC_RATIO => 2,
            Command::PREFER_EXIF_DT => true,
            Command::SUBDIRS_FORMAT => '%Y/%m',
            Command::TIMEZONE => null,
            Command::TO => '-',
            Command::TOKENS_ADD => [],
            Command::TOKENS_DROP => [],
            Command::TOKENS_DROP_UNKNOWN => false,
            Command::REGEXP_EXIF_FILTER => [],
            Command::REGEXP_EXIF_NEGATIVE_FILTER => [],
            Command::REGEXP_FILENAME_FILTER => null,
            Command::REGEXP_FILENAME_NEGATIVE_FILTER => null,
            Command::SAVE_COMMANDS_FILE => null,
            Command::SIMULATE => false,
        ];
        $mock = \Mockery::mock(InputInterface::class);
        foreach (array_unique(array_merge(array_keys($defaults), array_keys($opts))) as $option) {
            $mock = $mock->shouldReceive('getOption')->with($option)->andReturn($opts[$option] ?? $defaults[$option])->getMock();
        }
        return $mock;
    }
}
