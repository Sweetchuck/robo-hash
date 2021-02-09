<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\Hash\Test\Helper\RoboFiles;

use Robo\Collection\CollectionBuilder;
use Robo\State\Data as RoboStateData;
use Robo\Tasks;
use Sweetchuck\Robo\Hash\HashTaskLoader;

class HashRoboFile extends Tasks
{

    use HashTaskLoader;

    /**
     * {@inheritdoc}
     */
    protected function output()
    {
        return $this->getContainer()->get('output');
    }

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
