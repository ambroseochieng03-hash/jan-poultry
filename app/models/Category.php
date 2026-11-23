<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Category
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public static function clean(string $name): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));
        return mb_substr($name, 0, 100);
    }

    /** All categories with how many products use each. */
    public function allWithCounts(): array
    {
        return $this->db->query(
            "SELECT c.id, c.name, c.sort_order, c.is_active,
                    (SELECT COUNT(*) FROM products p WHERE p.category = c.name) AS product_count
             FROM categories c
             ORDER BY c.sort_order ASC, c.name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Every category name, offered in the admin product forms (hidden ones too, so editing never drops a category). */
    public function allNames(): array
    {
        return $this->db->query(
            "SELECT name FROM categories ORDER BY sort_order ASC, name ASC"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Returns the stored spelling of a category name, or '' when it does not exist. */
    public function canonical(string $name): string
    {
        $name = self::clean($name);
        if ($name === '') {
            return '';
        }
        $stmt = $this->db->prepare("SELECT name FROM categories WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        return (string) ($stmt->fetchColumn() ?: '');
    }

    /** Active categories that have at least one available product (used on the home page). */
    public function forHome(): array
    {
        return $this->db->query(
            "SELECT c.name FROM categories c
             WHERE c.is_active = 1
               AND EXISTS (SELECT 1 FROM products p WHERE p.category = c.name AND p.is_available = 1)
             ORDER BY c.sort_order ASC, c.name ASC"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function create(string $name): array
    {
        $name = self::clean($name);
        if ($name === '') {
            return [false, 'Enter a category name.'];
        }
        $stmt = $this->db->prepare("SELECT 1 FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn()) {
            return [false, 'That category already exists.'];
        }
        $this->db->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
        return [true, 'Category added.'];
    }

    public function rename(int $id, string $name): array
    {
        $name = self::clean($name);
        if ($name === '') {
            return [false, 'Enter a category name.'];
        }
        $cur = $this->db->prepare("SELECT name FROM categories WHERE id = ?");
        $cur->execute([$id]);
        $old = $cur->fetchColumn();
        if ($old === false) {
            return [false, 'Category not found.'];
        }
        $dup = $this->db->prepare("SELECT 1 FROM categories WHERE name = ? AND id <> ?");
        $dup->execute([$name, $id]);
        if ($dup->fetchColumn()) {
            return [false, 'Another category already uses that name.'];
        }
        $this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE categories SET name = ? WHERE id = ?")->execute([$name, $id]);
            $this->db->prepare("UPDATE products SET category = ? WHERE category = ?")->execute([$name, $old]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return [true, 'Category renamed.'];
    }

    public function toggle(int $id): array
    {
        $this->db->prepare("UPDATE categories SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
        return [true, 'Category updated.'];
    }

    public function delete(int $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT (SELECT COUNT(*) FROM products p WHERE p.category = c.name) FROM categories c WHERE c.id = ?"
        );
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        if ($count === false) {
            return [false, 'Category not found.'];
        }
        if ((int) $count > 0) {
            return [false, 'This category still has products. Move them to another category first, or hide it instead.'];
        }
        $this->db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        return [true, 'Category deleted.'];
    }
}
