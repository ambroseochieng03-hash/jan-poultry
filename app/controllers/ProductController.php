<?php

require_once
__DIR__ .
'/../models/Product.php';

class ProductController
{
    private Product $product;

    public function __construct()
    {
        $this->product =
        new Product();
    }

    public function getAll(): array
    {
        return
        $this->product
        ->getAll();
    }

    public function find(
        int $id
    ): ?array {

        return
        $this->product
        ->find($id);
    }

    public function update(
        int $id,
        array $data
    ): bool {

        return
        $this->product
        ->update(
            $id,
            $data
        );
    }

    public function toggleAvailability(
        int $id
    ): bool {

        return
        $this->product
        ->toggleAvailability($id);
    }

    public function delete(
        int $id
    ): bool {

        return
        $this->product
        ->delete($id);
    }

    public function create(array $data): bool
    {
        return $this->product->create($data);
    }

    public function count(): int
    {
        return
        $this->product
        ->count();
    }
}