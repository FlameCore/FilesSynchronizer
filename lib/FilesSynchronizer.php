<?php
/**
 * Synchronizer Library
 * Copyright (C) 2014 IceFlame.net
 *
 * Permission to use, copy, modify, and/or distribute this software for
 * any purpose with or without fee is hereby granted, provided that the
 * above copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE
 * FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY
 * DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER
 * IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING
 * OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 *
 * @package  FlameCore\Synchronizer
 * @version  0.1-dev
 * @link     http://www.flamecore.org
 * @license  ISC License <http://opensource.org/licenses/ISC>
 */

namespace FlameCore\Synchronizer\Files;

use FlameCore\Synchronizer\AbstractSynchronizer;
use FlameCore\Synchronizer\SynchronizerSourceInterface;
use FlameCore\Synchronizer\SynchronizerTargetInterface;
use FlameCore\Synchronizer\Files\Source\FilesSourceInterface;
use FlameCore\Synchronizer\Files\Target\FilesTargetInterface;

/**
 * The FilesSynchronizer class
 *
 * @author   Christian Neff <christian.neff@gmail.com>
 */
class FilesSynchronizer extends AbstractSynchronizer
{
    /**
     * @var int
     */
    protected $fails = 0;

    /**
     * {@inheritdoc}
     */
    public function synchronize($preserve = true)
    {
        if ($this->source === null) {
            throw new \LogicException('Cannot synchronize without source.');
        }

        if ($this->target === null) {
            throw new \LogicException('Cannot synchronize without target.');
        }

        $diff = new FilesComparer($this->source, $this->target, $this->excludes);

        $this->updateOutdated($diff);
        $this->addMissing($diff);

        if (!$preserve) {
            $this->removeObsolete($diff);
        }

        return $this->fails == 0;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsSource(SynchronizerSourceInterface $source)
    {
        return $source instanceof FilesSourceInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTarget(SynchronizerTargetInterface $target)
    {
        return $target instanceof FilesTargetInterface;
    }

    /**
     * @param \FlameCore\Synchronizer\Files\FilesComparer $diff
     */
    protected function updateOutdated(FilesComparer $diff)
    {
        $files = $diff->getOutdatedFiles();

        foreach ($files as $file) {
            if (!$this->target->put($file, $this->source->get($file), 0777)) {
                $this->fails++;
            }
        }
    }

    /**
     * @param \FlameCore\Synchronizer\Files\FilesComparer $diff
     */
    protected function addMissing(FilesComparer $diff)
    {
        $files = $diff->getMissingFiles();
        $directories = $diff->getMissingDirs();

        foreach ($directories as $directory) {
            if (!$this->target->createDir($directory)) {
                $this->fails++;
            }
        }

        foreach ($files as $file) {
            if (!$this->target->put($file, $this->source->get($file), 0777)) {
                $this->fails++;
            }
        }
    }

    /**
     * @param \FlameCore\Synchronizer\Files\FilesComparer $diff
     */
    protected function removeObsolete(FilesComparer $diff)
    {
        $files = $diff->getObsoleteFiles();
        $directories = $diff->getObsoleteDirs();

        foreach ($files as $file) {
            if (!$this->target->remove($file)) {
                $this->fails++;
            }
        }

        foreach ($directories as $directory) {
            if (!$this->target->removeDir($directory)) {
                $this->fails++;
            }
        }
    }
}
