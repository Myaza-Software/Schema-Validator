<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator;

use SchemaValidator\CircularReference\CircularReferenceStorage;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @codeCoverageIgnore
 */
final class Context
{
    public function __construct(
        private string $path,
        private string $type,
        private bool $strictTypes,
        private ExecutionContextInterface $execution,
        private ?int $maxDepth = null,
        private ?CircularReferenceStorage $circularReferenceStorage = null
    ) {
    }

    public function path(): string
    {
        return $this->path;
    }

    public function execution(): ExecutionContextInterface
    {
        return $this->execution;
    }

    /**
     * @return class-string|string
     */
    public function type(): string
    {
        return $this->type;
    }

    public function strictTypes(): bool
    {
        return $this->strictTypes;
    }

    public function maxDepth(): ?int
    {
        return $this->maxDepth;
    }

    public function withExecution(ExecutionContextInterface $execution): self
    {
        $new            = clone $this;
        $new->execution = $execution;

        return $new;
    }

    public function circularReferenceStorage(): ?CircularReferenceStorage
    {
        return $this->circularReferenceStorage;
    }
}
