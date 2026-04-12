<?php
declare(strict_types=1);

namespace App\Repositories;

final class OrderItemRepository extends BaseRepository
{
    public function createBatch(int $companyId, int $orderId, array $items): void
    {
        $itemSql = "
            INSERT INTO order_items (
                company_id,
                order_id,
                product_id,
                product_name_snapshot,
                unit_price,
                quantity,
                notes,
                line_subtotal,
                status,
                created_at
            ) VALUES (
                :company_id,
                :order_id,
                :product_id,
                :product_name_snapshot,
                :unit_price,
                :quantity,
                :notes,
                :line_subtotal,
                'active',
                NOW()
            )
        ";

        $itemStmt = $this->db()->prepare($itemSql);
        $additionalStmt = $this->db()->prepare("
            INSERT INTO order_item_additionals (
                company_id,
                order_item_id,
                additional_item_id,
                additional_name_snapshot,
                unit_price,
                quantity,
                line_subtotal,
                created_at
            ) VALUES (
                :company_id,
                :order_item_id,
                :additional_item_id,
                :additional_name_snapshot,
                :unit_price,
                :quantity,
                :line_subtotal,
                NOW()
            )
        ");

        foreach ($items as $item) {
            $itemStmt->execute([
                'company_id' => $companyId,
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'product_name_snapshot' => $item['product_name_snapshot'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'notes' => $item['notes'],
                'line_subtotal' => $item['line_subtotal'],
            ]);

            $orderItemId = (int) $this->db()->lastInsertId();
            $additionals = $item['additionals'] ?? [];
            if (!is_array($additionals) || $additionals === []) {
                continue;
            }

            foreach ($additionals as $additional) {
                $additionalStmt->execute([
                    'company_id' => $companyId,
                    'order_item_id' => $orderItemId,
                    'additional_item_id' => (int) ($additional['additional_item_id'] ?? 0),
                    'additional_name_snapshot' => (string) ($additional['additional_name_snapshot'] ?? ''),
                    'unit_price' => (float) ($additional['unit_price'] ?? 0),
                    'quantity' => (int) ($additional['quantity'] ?? 1),
                    'line_subtotal' => (float) ($additional['line_subtotal'] ?? 0),
                ]);
            }
        }
    }
}
