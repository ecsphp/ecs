<?php

declare (strict_types=1);
namespace ECSPrefix202609\Entropy\FileSystem;

use ECSPrefix202609\Entropy\Utils\FileSystem;
use SplFileInfo;
final class FileInfo extends SplFileInfo
{
    /**
     * @readonly
     * @var string
     */
    private $relativePath = '';
    /**
     * @readonly
     * @var string
     */
    private $relativePathname = '';
    public function __construct(string $filePath, string $relativePath = '', string $relativePathname = '')
    {
        $this->relativePath = $relativePath;
        $this->relativePathname = $relativePathname;
        parent::__construct($filePath);
    }
    /**
     * @api
     */
    public function getContents(): string
    {
        return FileSystem::read($this->getPathname());
    }
    /**
     * @api
     */
    public function getRelativePath(): string
    {
        return $this->relativePath;
    }
    /**
     * @api
     */
    public function getRelativePathname(): string
    {
        return $this->relativePathname;
    }
}
