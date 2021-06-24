<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit\Metadata;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SchemaValidator\Metadata\ClassMetadataFactoryWrapper;
use SchemaValidator\Metadata\ClassMetadataFactoryWrapperInterface;
use SchemaValidator\Test\Unit\Fixture\Webhook\RefundWebhook;
use SchemaValidator\Test\Unit\Fixture\Webhook\SuccessPaymentWebhook;
use SchemaValidator\Test\Unit\Fixture\Webhook\WebhookInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

final class ClassMetadataFactoryWrapperTest extends TestCase
{
    private ClassMetadataFactoryWrapperInterface $classMetadataFactoryWrapper;

    /**
     * @var PropertyAccessorInterface&MockObject
     */
    private PropertyAccessorInterface $propertyAccessor;

    /**
     * @var ClassMetadataFactoryInterface&MockObject
     */
    private ClassMetadataFactoryInterface $classMetadataFactory;

    protected function setUp(): void
    {
        $this->propertyAccessor            = $this->createMock(PropertyAccessorInterface::class);
        $this->classMetadataFactory        = $this->createMock(ClassMetadataFactoryInterface::class);
        $this->classMetadataFactoryWrapper = new ClassMetadataFactoryWrapper($this->propertyAccessor, $this->classMetadataFactory);
    }

    /**
     * @dataProvider notFoundConstructorClassDataProvider
     */
    public function testNotFoundConstructorClass(ClassMetadataInterface $classMetadata): void
    {
        $this->expectExceptionCode(0);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not found constructor class:' . $classMetadata->getName());

        $this->classMetadataFactory->method('getMetadataFor')
            ->willReturn($classMetadata)
        ;

        $this->propertyAccessor->method('getValue')
            ->willReturn('success')
        ;

        $this->classMetadataFactoryWrapper->getMetadataFor($classMetadata->getName(), ['type' => 'success']);
    }

    /**
     * @return iterable<int,array<ClassMetadataInterface>>
     */
    public function notFoundConstructorClassDataProvider(): iterable
    {
        yield [new ClassMetadata(RefundWebhook::class)];
        yield [new ClassMetadata(
            WebhookInterface::class,
            new ClassDiscriminatorMapping('type', ['success' => WebhookInterface::class])
        )];
    }

    public function testGetEmptyClassMetadataPropertyNotFound(): void
    {
        $serializerClassMetadata = new ClassMetadata(
            WebhookInterface::class,
            new ClassDiscriminatorMapping('type', ['success' => WebhookInterface::class])
        );

        $this->classMetadataFactory->method('getMetadataFor')
            ->willReturn($serializerClassMetadata)
        ;

        $this->propertyAccessor->method('getValue')
            ->willReturn(null)
        ;

        $classMetadata = $this->classMetadataFactoryWrapper->getMetadataFor(
            $serializerClassMetadata->getName(),
            ['type' => 'success']
        );

        $mapping = $classMetadata->getMapping();

        $this->assertEmpty($classMetadata->getParameters());
        $this->assertEmpty($classMetadata->getAttributes());
        $this->assertNotNull($mapping);
        $this->assertEquals(['success'], $mapping->getMapValue());
        $this->assertEquals('type', $mapping->getProperty()->getName());
        $this->assertNull($mapping->getProperty()->getInvalidValue());
        $this->assertFalse($mapping->getProperty()->isExits());
    }

    public function testGetEmptyClassMetadataClassDiscriminatorNotFound(): void
    {
        $serializerClassMetadata = new ClassMetadata(
            WebhookInterface::class,
            new ClassDiscriminatorMapping('type', ['success' => WebhookInterface::class])
        );

        $this->classMetadataFactory->method('getMetadataFor')
            ->willReturn($serializerClassMetadata)
        ;

        $this->propertyAccessor->method('getValue')
            ->willReturn('not_found')
        ;

        $classMetadata = $this->classMetadataFactoryWrapper->getMetadataFor(
            $serializerClassMetadata->getName(),
            ['type' => 'success']
        );

        $mapping = $classMetadata->getMapping();

        $this->assertEmpty($classMetadata->getParameters());
        $this->assertEmpty($classMetadata->getAttributes());
        $this->assertNotNull($mapping);
        $this->assertEquals(['success'], $mapping->getMapValue());
        $this->assertEquals('type', $mapping->getProperty()->getName());
        $this->assertIsString($mapping->getProperty()->getInvalidValue());
        $this->assertTrue($mapping->getProperty()->isExits());
    }

    /**
     * @dataProvider getMetadataDataProvider
     */
    public function testGetMetadata(ClassMetadataInterface $serializerClassMetadata): void
    {
        $this->classMetadataFactory->method('getMetadataFor')
            ->willReturnCallback(function (string $type) use ($serializerClassMetadata): ClassMetadataInterface {
                if (SuccessPaymentWebhook::class === $type) {
                    return new ClassMetadata(SuccessPaymentWebhook::class);
                }

                return $serializerClassMetadata;
            })
        ;

        $this->propertyAccessor->method('getValue')
            ->willReturn('success')
        ;

        $classMetadata = $this->classMetadataFactoryWrapper->getMetadataFor(
            $serializerClassMetadata->getName(),
            ['type' => 'success']
        );

        $this->assertNotEmpty($classMetadata->getParameters());
        $this->assertEmpty($classMetadata->getAttributes());
        $this->assertFalse($classMetadata->isEmpty());
    }

    /**
     * @return iterable<int,array<ClassMetadataInterface>>
     */
    public function getMetadataDataProvider(): iterable
    {
        yield [new ClassMetadata(SuccessPaymentWebhook::class)];

        yield [new ClassMetadata(
            WebhookInterface::class,
            new ClassDiscriminatorMapping('type', ['success' => SuccessPaymentWebhook::class])
        )];
    }
}
