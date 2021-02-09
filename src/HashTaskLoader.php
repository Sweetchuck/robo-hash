<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\Hash;

use Sweetchuck\Robo\Hash\Task\HashTask;

trait HashTaskLoader
{

    /**
     * @return \Sweetchuck\Robo\Hash\Task\HashTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskHash(array $options = [])
    {
        /** @var \Sweetchuck\Robo\Hash\Task\HashTask|\Robo\Collection\CollectionBuilder $task */
        $task = $this->task(HashTask::class);
        $task->setOptions($options);

        return $task;
    }
}
