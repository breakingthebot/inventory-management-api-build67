<?php

// tests/Service/AuditTrailEngineTest.php
// Unit tests for AuditTrailEngine verifying entity revision recording, snapshot serialization, and state rollbacks.
// Connects to: src/Service/AuditTrailEngine.php, src/Entity/EntityRevision.php, src/Entity/Product.php
// Created: 2026-08-05

namespace App\Tests\Service;

use App\Entity\EntityRevision;
use App\Entity\Product;
use App\Repository\EntityRevisionRepository;
use App\Service\AuditTrailEngine;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AuditTrailEngineTest extends TestCase
{
    private EntityRevisionRepository $revisionRepository;
    private EntityManagerInterface $entityManager;
    private NormalizerInterface $normalizer;
    private AuditTrailEngine $engine;

    protected function setUp(): void
    {
        $this->revisionRepository = $this->createMock(EntityRevisionRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->normalizer = $this->createMock(NormalizerInterface::class);

        $this->engine = new AuditTrailEngine(
            $this->revisionRepository,
            $this->entityManager,
            $this->normalizer
        );
    }

    public function testRecordRevisionSerializesSnapshotAndSaves(): void
    {
        $product = new Product();
        $product->setSku('REV-001');

        $this->normalizer->method('normalize')->willReturn(['sku' => 'REV-001', 'name' => 'Original Name']);
        $this->revisionRepository->expects($this->once())->method('save');

        $rev = $this->engine->recordRevision($product, EntityRevision::ACTION_CREATED, 'admin@test.com');

        $this->assertEquals(Product::class, $rev->getEntityClass());
        $this->assertEquals(EntityRevision::ACTION_CREATED, $rev->getAction());
        $this->assertEquals('admin@test.com', $rev->getChangedBy());
        $this->assertEquals(['sku' => 'REV-001', 'name' => 'Original Name'], $rev->getPayloadSnapshot());
    }

    public function testRollbackEntityToRevisionRestoresState(): void
    {
        $product = new Product();
        $product->setName('New Changed Name');

        $revision = new EntityRevision();
        $revision->setEntityClass(Product::class);
        $revision->setEntityId(1);
        $revision->setPayloadSnapshot(['name' => 'Restored Original Name']);

        $this->revisionRepository->method('find')->with(10)->willReturn($revision);
        $this->entityManager->method('find')->with(Product::class, 1)->willReturn($product);
        $this->entityManager->expects($this->once())->method('flush');

        $this->normalizer->method('normalize')->willReturn(['name' => 'Restored Original Name']);

        $restored = $this->engine->rollbackEntityToRevision(10);

        $this->assertSame($product, $restored);
        $this->assertEquals('Restored Original Name', $product->getName());
    }
}
