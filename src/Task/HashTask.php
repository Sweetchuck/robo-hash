<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\Hash\Task;

use Robo\Result;
use Robo\Task\BaseTask as RoboBaseTask;
use Robo\TaskInfo;

class HashTask extends RoboBaseTask
{
    protected string $taskName = 'Hash';

    protected array $assets = [];

    protected string $assetNamePrefix = '';

    public function getAssetNamePrefix(): string
    {
        return $this->assetNamePrefix;
    }

    /**
     * @return $this
     */
    public function setAssetNamePrefix(string $value)
    {
        $this->assetNamePrefix = $value;

        return $this;
    }

    protected string $hashAlgorithm = 'sha256';

    public function getHashAlgorithm(): string
    {
        return $this->hashAlgorithm;
    }

    /**
     * @return $this
     */
    public function setHashAlgorithm(string $hashAlgorithm)
    {
        $this->hashAlgorithm = $hashAlgorithm;

        return $this;
    }

    protected int $hashFlags = 0;

    public function getHashFlags(): int
    {
        return $this->hashFlags;
    }

    /**
     * @return $this
     */
    public function setHashFlags(int $hashFlags)
    {
        $this->hashFlags = $hashFlags;

        return $this;
    }

    protected ?string $hashKey = null;

    public function getHashKey(): ?string
    {
        return $this->hashKey;
    }

    /**
     * @return $this
     */
    public function setHashKey(?string $hashKey)
    {
        $this->hashKey = $hashKey;

        return $this;
    }

    protected string $fileName = '';

    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return $this
     */
    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @var null|resource
     */
    protected $fileHandler = null;

    /**
     * @return null|resource
     */
    public function getFileHandler()
    {
        return $this->fileHandler;
    }

    /**
     * @param null|resource $fileHandler
     *
     * @return $this
     */
    public function setFileHandler($fileHandler)
    {
        if ($fileHandler !== null
            && (!is_resource($fileHandler) || get_resource_type($fileHandler) !== 'stream')
        ) {
            $isResource = is_resource($fileHandler);
            throw new \InvalidArgumentException(sprintf(
                'the given $fileHandler %s resource, and its type %s',
                $isResource ? 'is' : 'is not',
                $isResource ? get_resource_type($fileHandler) : gettype($fileHandler),
            ));
        }

        $this->fileHandler = $fileHandler;

        return $this;
    }

    protected bool $isExternalFileHandler = false;

    /**
     * 1024 × 1024 × 10.
     */
    protected int $chunkSize = 10485760;

    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * @return $this
     */
    public function setChunkSize(int $chunkSize)
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    /**
     * @return $this
     */
    public function setOptions(array $options)
    {
        if (array_key_exists('fileName', $options)) {
            $this->setFileName($options['fileName']);
        }

        if (array_key_exists('fileHandler', $options)) {
            $this->setFileHandler($options['fileHandler']);
        }

        if (array_key_exists('hashAlgorithm', $options)) {
            $this->setHashAlgorithm($options['hashAlgorithm']);
        }

        if (array_key_exists('hashFlags', $options)) {
            $this->setHashFlags($options['hashFlags']);
        }

        if (array_key_exists('hashKey', $options)) {
            $this->setHashKey($options['hashKey']);
        }

        if (array_key_exists('chunkSize', $options)) {
            $this->setChunkSize($options['chunkSize']);
        }

        if (array_key_exists('assetNamePrefix', $options)) {
            $this->setAssetNamePrefix($options['assetNamePrefix']);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        return $this
            ->runHeader()
            ->runDoIt()
            ->runReturn();
    }

    /**
     * @return $this
     */
    protected function runHeader()
    {
        $chunkSize = $this->getChunkSize();
        $text = $chunkSize === -1 ?
            'Calculate hash of {fileName} with {hashAlgorithm} hashAlgorithm in one step'
            : 'Calculate hash of {fileName} with {hashAlgorithm} hashAlgorithm in chunk size {chunkSize}';

        $this->printTaskInfo(
            $text,
            [
                'hashAlgorithm' => $this->getHashAlgorithm(),
                'fileName' => $this->getFileName(),
                'chunkSize' => $this->getChunkSize(),
            ],
        );

        return $this;
    }

    /**
     * @return $this
     */
    protected function runDoIt()
    {
        $this
            ->runDoItInitFileHandler()
            ->runDoItHash()
            ->runDoItPost();

        return $this;
    }

    protected function runDoItInitFileHandler()
    {
        $fileName = $this->getFileName();
        $fileHandler = $this->getFileHandler();

        if ($fileHandler !== null) {
            $this->isExternalFileHandler = true;

            return $this;
        }

        $fh = fopen($fileName, 'r');
        if ($fh === false) {
            throw new \RuntimeException("file $fileName could not be opened");
        }

        $this->fileHandler = $fh;
        $this->isExternalFileHandler = false;

        return $this;
    }

    protected function runDoItHash()
    {
        $initArgs = [
            $this->getHashAlgorithm(),
            $this->getHashFlags(),
        ];
        if ($this->getHashKey() !== null) {
            $initArgs[] = $this->getHashKey();
        }

        $context = hash_init(...$initArgs);
        $fh = $this->getFileHandler();
        $chunkSize = $this->getChunkSize();
        do {
            $numOfBytesAdded = hash_update_stream($context, $fh, $chunkSize);
        } while ($numOfBytesAdded);

        $this->assets['hash'] = hash_final($context);

        return $this;
    }

    protected function runDoItPost()
    {
        if (!$this->isExternalFileHandler) {
            fclose($this->fileHandler);
        }

        return $this;
    }

    protected function runReturn(): Result
    {
        return new Result(
            $this,
            $this->getTaskResultCode(),
            $this->getTaskResultMessage(),
            $this->getAssetsWithPrefixedNames(),
        );
    }

    protected function getTaskResultCode(): int
    {
        return 0;
    }

    protected function getTaskResultMessage(): string
    {
        return $this->assets['hash'];
    }

    protected function getAssetsWithPrefixedNames(): array
    {
        $prefix = $this->getAssetNamePrefix();
        if (!$prefix) {
            return $this->assets;
        }

        $assets = [];
        foreach ($this->assets as $key => $value) {
            $assets["{$prefix}{$key}"] = $value;
        }

        return $assets;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskContext($context = null)
    {
        if (empty($context['name'])) {
            $context['name'] = $this->getTaskName();
        }

        return parent::getTaskContext($context);
    }

    public function getTaskName(): string
    {
        return $this->taskName ?: TaskInfo::formatTaskName($this);
    }
}
