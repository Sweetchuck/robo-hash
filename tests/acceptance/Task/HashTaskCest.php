<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\Hash\Tests\Acceptance\Task;

use Codeception\Example;
use Sweetchuck\Robo\Hash\Test\AcceptanceTester;
use Sweetchuck\Robo\Hash\Test\Helper\RoboFiles\HashRoboFile;
use Sweetchuck\Robo\Hash\Tests\Acceptance\CestBase;

class HashTaskCest extends CestBase
{
    protected function hashCases(): array
    {
        $fileContent = 'abcdef';
        $fileName = 'data://text/plain;base64,' . base64_encode($fileContent);
        $hash = [
            'sha256' => hash('sha256', $fileContent),
        ];

        return [
            [
                'id' => 'hash:file-name',
                'expectedExitCode' => 0,
                'expectedStdOutput' => "{$hash['sha256']}  $fileName\n",
                'expectedStdError' => " [Hash] Calculate hash of $fileName with sha256 hashAlgorithm in one step\n",
                'cli' => [
                    'hash:file-name',
                    $fileName,
                ],
            ],
            [
                'id' => 'hash:file-handler',
                'expectedExitCode' => 0,
                'expectedStdOutput' => "{$hash['sha256']}  /foo.txt\n",
                'expectedStdError' => " [Hash] Calculate hash of /foo.txt with sha256 hashAlgorithm in one step\n",
                'cli' => [
                    'hash:file-handler',
                    "--file-name=/foo.txt",
                    '--asset-name-prefix=my.',
                    $fileContent,
                ],
            ],
        ];
    }

    /**
     * @dataProvider hashCases
     */
    public function hash(AcceptanceTester $tester, Example $example)
    {
        $tester->runRoboTask($example['id'], HashRoboFile::class, ...$example['cli']);
        $exitCode = $tester->getRoboTaskExitCode($example['id']);
        $stdOutput = $tester->getRoboTaskStdOutput($example['id']);
        $stdError = $tester->getRoboTaskStdError($example['id']);

        $tester->assertSame($example['expectedExitCode'], $exitCode, 'ExitCode');
        $tester->assertSame($example['expectedStdOutput'], $stdOutput, 'StdOutput');
        $tester->assertSame($example['expectedStdError'], $stdError, 'StdError');
    }
}
