<?php

namespace Zebooka\PD;

class Assembler
{
    private $configure;
    private $hashinator;
    /**
     * @var PhotoBunch[]
     */
    private $simulated = array();

    public function  __construct(Configure $configure, Hashinator $hashinator)
    {
        $this->configure = $configure;
        $this->hashinator = $hashinator;
    }

    public function assemble(Tokens $tokens, PhotoBunch $photoBunch)
    {
        if (Configure::KEEP_IN_PLACE !== $this->configure->to) {
            $to = (file_exists($this->configure->to) ? realpath($this->configure->to) : $this->configure->to);
        }
        while (true) {
            if (Configure::KEEP_IN_PLACE === $this->configure->to) {
                $newBunchId = $photoBunch->directory() . DIRECTORY_SEPARATOR . $tokens->assembleBasename();
            } elseif ($this->configure->subDirectoriesStructure && $dir = $tokens->assembleDirectory()) {
                $newBunchId = $to . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $tokens->assembleBasename();
            } else {
                $newBunchId = $to . DIRECTORY_SEPARATOR . $tokens->assembleBasename();
            }
            if (!$this->bunchTaken($newBunchId, $photoBunch)) {
                break;
            }
            $tokens->increaseShot();
        }

        if ($this->configure->simulate) {
            $this->simulated[$newBunchId] = $photoBunch;
        }
        return $newBunchId;
    }

    private function bunchTaken($newBunchId, PhotoBunch $photoBunch)
    {
        // find extensions of new bunch
        $foundExtensions = $this->findExtensionsForBunchId($newBunchId);

        // if nothing found - return false
        if (!$foundExtensions) {
            return false;
        }

        // TODO: this may lead to unforeseen consequences when FS is case insensitive and we have .JPG and .jpg files
        $intersect = array_intersect($foundExtensions, $photoBunch->extensions());
        // if intersect is empty - return true
        if (!$intersect) {
            return true;
        }

        // if intersect is of same files (hashes), return false
        foreach ($intersect as $extension) {
            $newFile = $newBunchId . '.' . $extension;
            $oldFile = $photoBunch->bunchId() . '.' . $extension;
            if (file_exists($newFile) && !is_file($newFile)) {
                return true;
            } elseif (is_file($newFile) && !$this->hashinator->equal($newFile, $oldFile)) {
                return true;
            } elseif (!file_exists($newFile) && isset($this->simulated[$newBunchId])
                && !$this->hashinator->equal($this->simulated[$newBunchId]->bunchId() . '.' . $extension, $oldFile)
            ) {
                return true;
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
