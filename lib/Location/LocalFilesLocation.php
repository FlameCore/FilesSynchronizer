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

namespace FlameCore\Synchronizer\Files\Location;

/**
 * The FilesLocation class
 *
 * @author   Christian Neff <christian.neff@gmail.com>
 */
class LocalFilesLocation implements FilesLocationInterface
{
    /**
     * @var string
     */
    protected $path;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $settings)
    {
        if (!isset($settings['dir']) || !is_string($settings['dir'])) {
            throw new \InvalidArgumentException(sprintf('The %s does not define "dir" setting.', get_class($this)));
        }

        $this->path = !$this->isAbsolutePath($settings['dir']) ? realpath(getcwd() . DIRECTORY_SEPARATOR . $settings['dir']) : $settings['dir'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilesPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilesList($exclude = false)
    {
        $fileslist = array();

        $iterator = new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);

        if ((is_string($exclude) || is_array($exclude)) && !empty($exclude)) {
            $iterator = new \RecursiveCallbackFilterIterator($iterator, function ($current) use ($exclude) {
                if ($current->isDir()) {
                    return true;
                }

                $subpath = substr($current->getPathName(), strlen($this->path) + 1);

                foreach ((array) $exclude as $pattern) {
                    if ($pattern[0] == '!' ? !fnmatch(substr($pattern, 1), $subpath) : fnmatch($pattern, $subpath)) {
                        return false;
                    }
                }

                return true;
            });
        }

        $iterator = new \RecursiveIteratorIterator($iterator);
        foreach ($iterator as $file) {
            $pathname = substr($file->getPathName(), strlen($this->path));
            $filename = $file->getBasename();
            $dirname = dirname($pathname);

            $fileslist[$dirname][$filename] = substr($pathname, 1);
        }

        return $fileslist;
    }

    /**
     * {@inheritdoc}
     */
    public function getRealPathName($file)
    {
        return $this->path . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isAbsolutePath($path)
    {
        return $path[0] === DIRECTORY_SEPARATOR || preg_match('#^(?:/|\\\\|[A-Za-z]:\\\\|[A-Za-z]:/)#', $path);
    }
}
