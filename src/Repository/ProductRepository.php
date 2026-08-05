<?php

// src/Repository/ProductRepository.php
// Doctrine repository handling product queries, filtering by category, status, and search string.
// Connects to: src/Entity/Product.php
// Created: 2026-08-05

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function save(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Finds products with optional search query, category filter, and status filter.
     *
     * @return Product[]
     */
    public function findByFilters(?string $search = null, ?int $categoryId = null, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('LOWER(p.name) LIKE :search OR LOWER(p.sku) LIKE :search OR LOWER(p.description) LIKE :search')
               ->setParameter('search', '%' . strtolower(trim($search)) . '%');
        }

        if ($categoryId !== null) {
            $qb->andWhere('p.category = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        if ($status !== null && trim($status) !== '') {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', strtoupper(trim($status)));
        }

        return $qb->getQuery()->getResult();
    }
}
