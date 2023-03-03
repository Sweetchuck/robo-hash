<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\Hash\Tests\Unit\Task;

use Codeception\Test\Unit;
use Consolidation\Config\ConfigInterface;
use League\Container\Container as LeagueContainer;
use Psr\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Config\Config;
use Robo\Robo;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyOutput;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcessHelper;
use Sweetchuck\Robo\Hash\Tests\Helper\Dummy\DummyTaskBuilder;
use Sweetchuck\Robo\Hash\Tests\UnitTester;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ErrorHandler\BufferingLogger;

/**
 * @covers \Sweetchuck\Robo\Hash\Task\HashTask
 * @covers \Sweetchuck\Robo\Hash\HashTaskLoader
 */
class HashTaskTest extends Unit
{

    protected UnitTester $tester;

    protected ContainerInterface $container;

    protected ConfigInterface $config;

    protected CollectionBuilder $builder;

    protected DummyTaskBuilder $taskBuilder;

    /**
     * @SuppressWarnings("CamelCaseMethodName")
     */
    public function _before()
    {
        parent::_before();

        Robo::unsetContainer();

        $this->container = new LeagueContainer();
        $application = new SymfonyApplication('Sweetchuck - Robo Hash', '2.0.0');
        $application->getHelperSet()->set(new DummyProcessHelper(), 'process');
        $this->config = (new Config());
        $input = null;
        $output = new DummyOutput([
            'verbosity' => DummyOutput::VERBOSITY_DEBUG,
        ]);

        $this->container->add('container', $this->container);

        Robo::configureContainer($this->container, $application, $this->config, $input, $output);
        $this->container->addShared('logger', BufferingLogger::class);

        $this->builder = CollectionBuilder::create($this->container, null);
        $this->taskBuilder = new DummyTaskBuilder();
        $this->taskBuilder->setContainer($this->container);
        $this->taskBuilder->setBuilder($this->builder);
    }

    public function casesRun(): array
    {
        $fileContent = 'abcdef';
        $hash = [
            'sha256' => hash('sha256', $fileContent),
        ];
        $fileName = 'data://text/plain;base64,' . base64_encode($fileContent);

        return [
            'success' => [
                [
                    'exitCode' => 0,
                    'exitMessage' => $hash['sha256'],
                    'assets' => [
                        'hash' => $hash['sha256'],
                    ],
                ],
                [
                    'fileName' => $fileName,
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesRun
     */
    public function testRun(array $expected, array $options): void
    {
        $expected += [
            'exitCode' => 0,
            'exitMessage' => '',
        ];

        $taskBuilder = new DummyTaskBuilder();
        $taskBuilder->setContainer($this->getNewContainer());
        $task = $taskBuilder->taskHash($options);
        $result = $task->run();

        $this->tester->assertSame($expected['exitCode'], $result->getExitCode());
        $this->tester->assertSame($expected['exitMessage'], $result->getMessage());
        if (!empty($expected['assets'])) {
            $assets = $result->getArrayCopy();
            foreach ($expected['assets'] as $assetName => $asset) {
                $this->tester->assertArrayHasKey($assetName, $assets);
                $this->tester->assertSame($asset, $result[$assetName]);
            }
        }
    }

    protected function getNewContainer(): ContainerInterface
    {
        $config = [
            'verbosity' => OutputInterface::VERBOSITY_DEBUG,
            'colors' => false,
        ];
        $output = new DummyOutput($config);

        $container = Robo::createDefaultContainer(null, $output);
        $container->add('output', $output, false);

        return $container;
    }
}
