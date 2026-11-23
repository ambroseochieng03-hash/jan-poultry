<?php

require_once
__DIR__ .
'/../config/database.php';

class Product
{
    private PDO $db;

    public function __construct()
    {
        $this->db =
        (new Database())
        ->getConnection();
    }

    public function getAll(): array
    {
        $stmt =
        $this->db->query(
            "SELECT *
             FROM products
             ORDER BY id DESC"
        );

        return
        $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int)
        $this->db
        ->query(
            "SELECT COUNT(*) FROM products"
        )
        ->fetchColumn();
    }

    public function create(array $data): bool
    {
        $sql = "
            INSERT INTO products
            (
                name,
                description,
                price,
                image,
                category,
                product_type,
                breed,
                age_weeks,
                weight_kg,
                stock_quantity,
                sku,
                is_available
            )
            VALUES
            (
                :name,
                :description,
                :price,
                :image,
                :category,
                :product_type,
                :breed,
                :age_weeks,
                :weight_kg,
                :stock_quantity,
                :sku,
                :is_available
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':price' => $data['price'],
            ':image' => $data['image'],
            ':category' => $data['category'],
            ':product_type' => $data['product_type'],
            ':breed' => $data['breed'],
            ':age_weeks' => $data['age_weeks'],
            ':weight_kg' => $data['weight_kg'],
            ':stock_quantity' => $data['stock_quantity'],
            ':sku' => $data['sku'],
            ':is_available' => $data['is_available']
        ]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT *
            FROM products
            WHERE id = ?"
        );

        $stmt->execute([$id]);

        $product = $stmt->fetch();

        return $product ?: null;
    }

    public function update(
        int $id,
        array $data
    ): bool {

        $sql = "
            UPDATE products
            SET
                name = :name,
                description = :description,
                price = :price,
                image = :image,
                category = :category,
                product_type = :product_type,
                breed = :breed,
                age_weeks = :age_weeks,
                weight_kg = :weight_kg,
                stock_quantity = :stock_quantity,
                sku = :sku,
                is_available = :is_available
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([

            ':id' => $id,

            ':name' => $data['name'],

            ':description' => $data['description'],

            ':price' => $data['price'],

            ':image' => $data['image'],

            ':category' => $data['category'],

            ':product_type' => $data['product_type'],

            ':breed' => $data['breed'],

            ':age_weeks' => $data['age_weeks'],

            ':weight_kg' => $data['weight_kg'],

            ':stock_quantity' => $data['stock_quantity'],

            ':sku' => $data['sku'],

            ':is_available' => $data['is_available']

        ]);
    }

    public function toggleAvailability(
        int $id
    ): bool {

        $sql = "
            UPDATE products
            SET is_available =
                CASE
                    WHEN is_available = 1 THEN 0
                    ELSE 1
                END
            WHERE id = :id
        ";

        $stmt =
        $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id
        ]);
    }

    public function delete(
        int $id
    ): bool {

        $stmt =
        $this->db->prepare(
            "DELETE
            FROM products
            WHERE id = ?"
        );

        return
        $stmt->execute([$id]);
    }

    /* ======================
       LATEST PRODUCTS
    ======================= */
    public function getLatest(
        int $limit = 6
    ): array {

        $limit = max(1, $limit);

        $sql = "
            SELECT *
            FROM products
            ORDER BY id DESC
            LIMIT {$limit}
        ";

        $stmt =
            $this->db->query($sql);

        return
            $stmt->fetchAll();
    }

        /* ======================
       LATEST PRODUCTS
       FOR HOME PAGE
    ====================== */

    /** Products shown on the public home page: available ones only, newest first. */
    public function getForHome(int $limit = 48): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, description, price, image, category, product_type, breed,
                    age_weeks, weight_kg, stock_quantity
             FROM products
             WHERE is_available = 1
             ORDER BY id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLatestForHomepage(
        int $limit = 6
    ): array {

        $sql = "

            SELECT
                id,
                name,
                description,
                image,
                category,
                product_type,
                breed,
                is_available

            FROM products

            ORDER BY id DESC

            LIMIT :limit

        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll();
    }

        /* ==========================================
       INVENTORY DATABASE METHODS
       Database access only. No business logic.
    ========================================== */

    public function getProductForInventory(
        int $productId
    ): ?array {

        $stmt = $this->db->prepare(
            "

            SELECT

                id,
                stock_quantity,
                is_available

            FROM products

            WHERE id = ?

            LIMIT 1

            "
        );

        $stmt->execute([
            $productId
        ]);

        $product = $stmt->fetch();

        return $product ?: null;
    }

    public function getStockQuantity(
        int $productId
    ): int {

        $stmt = $this->db->prepare(
            "

            SELECT stock_quantity

            FROM products

            WHERE id = ?

            LIMIT 1

            "
        );

        $stmt->execute([
            $productId
        ]);

        return (int)
        $stmt->fetchColumn();
    }

    public function isEnabled(
        int $productId
    ): bool {

        $stmt = $this->db->prepare(
            "

            SELECT is_available

            FROM products

            WHERE id = ?

            LIMIT 1

            "
        );

        $stmt->execute([
            $productId
        ]);

        return (bool)
        $stmt->fetchColumn();
    }

    public function lockProduct(
        int $productId
    ): ?array {

        $stmt = $this->db->prepare(
            "

            SELECT *

            FROM products

            WHERE id = ?

            FOR UPDATE

            "
        );

        $stmt->execute([
            $productId
        ]);

        $product = $stmt->fetch();

        return $product ?: null;
    }

    public function updateStockQuantity(
        int $productId,
        int $quantity
    ): bool {

        $stmt = $this->db->prepare(
            "

            UPDATE products

            SET stock_quantity = ?

            WHERE id = ?

            "
        );

        return $stmt->execute([
            $quantity,
            $productId
        ]);
    }

    public function deductStock(
        int $productId,
        int $quantity
    ): bool {

        $stmt =
        $this->db->prepare(

            "

            UPDATE products

            SET stock_quantity =
                stock_quantity - :quantity

            WHERE id = :id

            "

        );

        return $stmt->execute([

            ':quantity' => $quantity,

            ':id' => $productId

        ]);

    }

    public function restoreStock(
        int $productId,
        int $quantity
    ): bool {

        $stmt =
        $this->db->prepare(

            "

            UPDATE products

            SET stock_quantity =
                stock_quantity + :quantity

            WHERE id = :id

            "

        );

        return $stmt->execute([

            ':quantity' => $quantity,

            ':id' => $productId

        ]);

    }
}