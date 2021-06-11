<?php

declare(strict_types=1);

namespace SchemaValidator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class Context
{
    public function __construct(
        private string $rootPath,
        private string $rootType,
        private ExecutionContextInterface $execution
    ) {
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    public function getExecution(): ExecutionContextInterface
    {
        return $this->execution;
    }

    public function getRootType(): string
    {
        return $this->rootType;
    }

    public function withExecution(ExecutionContextInterface $execution): self
    {
        $new            = clone $this;
        $new->execution = $execution;

        return $new;
    }
}
