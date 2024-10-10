<?php

namespace Zebooka\PD;

use Symfony\Component\Console\Input\InputInterface;

class Assembler
{
    /** @var Configure */
    private $configure;

    /** @var InputInterface */
    private $input;

    /** @var Hashinator */
    private $hashinator;

    /** @var FileBunch[] */
    private $simulated = [];

    public function  __construct(Configure $configure, InputInterface $input, Hashinator $hashinator)
    {
        $this->configure = $configure;
        $this->input = $input;
        $this->hashinator = $hashinator;
    }

    public function assemble(Tokens $tokens, FileBunch $fileBunch)
    {
        if (!$tokens->shot) {
            $tokens->shot = 1;
            $newBunchId = $this->assembleNewBunchId($tokens, $fileBunch);
            if (!$this->bunchTaken($newBunchId, $fileBunch)) {
                $tokens->shot = null;
            } else {
                $tokens->increaseShot();
            }
        }
        while (true) {
            $newBunchId = $this->assembleNewBunchId($tokens, $fileBunch);
            if (!$this->bunchTaken($newBunchId, $fileBunch)) {
                break;
            }
            $tokens->increaseShot();
        }

        if (Configure::simulate($this->input)) {
            $this->simulated[$newBunchId] = $fileBunch;
        }
        return $newBunchId;
    }

    private function assembleNewBunchId(Tokens $tokens, FileBunch $fileBunch)
    {
        $to = (!Configure::isKeepInPlace($this->input) && file_exists(Configure::to($this->input)))
            ? realpath(Configure::to($this->input))
            : Configure::to($this->input);
        if (Configure::isKeepInPlace($this->input)) {
            return $fileBunch->directory() . DIRECTORY_SEPARATOR . $tokens->assembleBasename();
        } elseif (Configure::subDirectoriesStructure($this->input) && $dir = $tokens->assembleDirectory($this->input)) {
            return $to . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $tokens->assembleBasename();
        } else {
            return $to . DIRECTORY_SEPARATOR . $tokens->assembleBasename();
        }
    }

    private function bunchTaken($newBunchId, FileBunch $fileBunch)
    {
        // find extensions of new bunch
        $foundExtensions = $this->findExtensionsForBunchId($newBunchId);

        // if nothing found - return false
        if (!$foundExtensions) {
            return false;
        }

        // we will always compare all files having same lowercased extensions
        $intersect = array_intersect(
            array_map('mb_strtolower', $foundExtensions),
            array_map('mb_strtolower', $fileBunch->extensions())
        );
        // if intersect is empty - return true
        if (!$intersect) {
            return true;
        }

        $foundExtensionsIndex = array_reduce(
            $foundExtensions,
            function ($extensions, $extension) {
                $lowercaseExtension = mb_strtolower($extension);
                if (!isset($extensions[$lowercaseExtension])) {
                    $extensions[$lowercaseExtension] = array();
                }
                $extensions[$lowercaseExtension][] = $extension;
                return $extensions;
            },
            array()
        );

        // if intersect is of same files (hashes), return false
        foreach ($fileBunch->extensions() as $extension) {
            $lowercaseExtension = mb_strtolower($extension);
            if (!in_array($lowercaseExtension, $intersect)) {
                continue;
            }
            $oldFile = $fileBunch->bunchId() . '.' . $extension;
            foreach ($foundExtensionsIndex[$lowercaseExtension] as $foundExtension) {
                $newFile = $newBunchId . '.' . $foundExtension;
                if (file_exists($newFile) && !is_file($newFile)) {
                    return true;
                } elseif (is_file($newFile) && !$this->hashinator->equal($newFile, $oldFile)) {
                    return true;
                } elseif (!file_exists($newFile) && isset($this->simulated[$newBunchId])
                    && !$this->hashinator->equal($this->simulated[$newBunchId]->bunchId() . '.' . $foundExtension, $oldFile)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    private function findExtensionsForBunchId($bunchId)
    {
        $extensions = array();
        if (is_dir(dirname($bunchId))) {
            foreach (new \DirectoryIterator(dirname($bunchId)) as $di) {
                /** @var \DirectoryIterator $di */
                if (!$di->isDot() && preg_match('/^' . preg_quote(basename($bunchId), '/') . '\\.([^\\.]+)$/', $di->getBasename())) {
                    $extensions[] = $di->getExtension();
                }
            }
        }
        if (Configure::simulate($this->input) && isset($this->simulated[$bunchId])) {
            $extensions = array_unique(array_merge($extensions, $this->simulated[$bunchId]->extensions()));
        }
        return $extensions;
    }
}
