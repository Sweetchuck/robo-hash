# Robo Hash

[![CircleCI](https://circleci.com/gh/Sweetchuck/robo-hash/tree/2.x.svg?style=svg)](https://circleci.com/gh/Sweetchuck/robo-hash/?branch=2.x)
[![codecov](https://codecov.io/gh/Sweetchuck/robo-hash/branch/2.x/graph/badge.svg?token=HSF16OGPyr)](https://app.codecov.io/gh/Sweetchuck/robo-hash/branch/2.x)


Wrapper for [PHP Hash]

```php
<?php

declare(strict_types = 1);

use Robo\Collection\CollectionBuilder;
use Robo\State\Data as RoboStateData;
use Robo\Tasks;
use Sweetchuck\Robo\Hash\HashTaskLoader;

class RoboFile extends Tasks
{

    use HashTaskLoader;

    /**
     * @command hash:file-name
     */
    public function hashFileName(
        string $fileName,
        array $options = [
            'hash-algorithm' => 'sha256',
            'hash-flags' => '0',
            'hash-key' => '',
            'chunk-size' => '-1',
            'asset-name-prefix' => '',
        ]
    ): CollectionBuilder {
        $hashOptions = $this->commandOptionsToHashOptions($options);
        $hashOptions['fileName'] = $fileName;

        return $this
            ->collectionBuilder()
            ->addTask($this->taskHash($hashOptions))
            ->addCode(function (RoboStateData $data) use ($hashOptions): int {
                $key = $hashOptions['assetNamePrefix'] . 'hash';
                $this->output()->writeln("{$data[$key]}  {$hashOptions['fileName']}");

                return 0;
            });
    }

    /**
     * @command hash:file-handler
     */
    public function hashFileHandler(
        string $fileContent,
        array $options = [
            'hash-algorithm' => 'sha256',
            'hash-flags' => '0',
            'hash-key' => '',
            'chunk-size' => '-1',
            'file-name' => '/dummy/foo.txt',
            'asset-name-prefix' => '',
        ]
    ): CollectionBuilder {
        $fileNameReal = 'data://text/plain;base64,' . base64_encode($fileContent);
        $fileNameDummy = $options['file-name'];

        $hashOptions = $this->commandOptionsToHashOptions($options);
        $hashOptions['fileName'] = $fileNameDummy;
        $hashOptions['fileHandler'] = fopen($fileNameReal, 'r');

        return $this
            ->collectionBuilder()
            ->addTask($this->taskHash($hashOptions))
            ->addCode(function (RoboStateData $data) use ($hashOptions): int {
                $key = $hashOptions['assetNamePrefix'] . 'hash';
                $this->output()->writeln("{$data[$key]}  {$hashOptions['fileName']}");

                return 0;
            });
    }

    protected function commandOptionsToHashOptions(array $options): array
    {
        return [
            'hashAlgorithm' => $options['hash-algorithm'] ?? 'sha256',
            'hashFlags' => (int) ($options['hash-flags'] ?? 0),
            'hashKey' => $options['hash-key'] ?: null,
            'chunkSize' => (int) ($options['chunk-size'] ?? 0),
            'assetNamePrefix' => $options['asset-name-prefix'] ?? '',
        ];
    }
}

```

[PHP Hash]: https://www.php.net/manual/en/function.hash.php
