<?php

namespace Zebooka\PD;

class Assembler
{
    private $configure;
    private $hashinator;
    /**
     * @var FileBunch[]
     */
    private $simulated = array();

    public function  __construct(Configure $configure, Hashinator $hashinator)
    {
        $this->configure = $configure;
        $this->hashinator = $hashinator;
    }

    public function assemble(Tokens $tokens, FileBunch $fileBunch)
    {
        if (Configure::KEEP_IN_PLACE !== $this->configure->to) {
            $to = (file_exists($this->configure->to) ? realpath($this->configure->to) : $this->configure->to);
        }
        while (true) {
            if (Configure::KEEP_IN_PLACE === $this->configure->to) {
                $newBunchId = $fileBunch->directory() . DIRECTORY_SEPARATOR . $tokens->assembleBasename();
            } elseif ($this->configure->subDirectoriesStructure && $dir = $tokens->assembleDirectory()) {
                $newBunchId = $to . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $tokens->assembleBasename();
            } else {
                $newBunchId = $to . DIRECTORY_SEPARATOR . $tokens->assembleBasename();
            }
            if (!$this->bunchTaken($newBunchId, $fileBunch)) {
                break;
            }
            $tokens->increaseShot();
        }

        if ($this->configure->simulate) {
            $this->simulated[$newBunchId] = $fileBunch;
        }
        return $newBunchId;
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
        if ($this->configure->simulate && isset($this->simulated[$bunchId])) {
            $extensions = array_unique(array_merge($extensions, $this->simulated[$bunchId]->extensions()));
        }
        return $extensions;
    }
}
