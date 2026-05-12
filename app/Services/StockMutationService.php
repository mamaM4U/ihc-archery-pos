<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\SalesReturn;
use App\Models\StockMutation;
use App\Models\StockOpname;

class StockMutationService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {
    }

    public function recordInitialStock(Product $product, ?int $userId = null): ?StockMutation
    {
        $initialStock = (int) $product->stock;

        if ($initialStock <= 0) {
            return null;
        }

        $mutation = StockMutation::create([
            'product_id' => $product->id,
            'reference_type' => 'product_create',
            'reference_id' => $product->id,
            'mutation_type' => 'in',
            'qty' => $initialStock,
            'stock_before' => 0,
            'stock_after' => $initialStock,
            'notes' => 'Initial stock saat produk dibuat.',
            'created_by' => $userId,
        ]);

        $this->auditLogService->log(
            event: 'stock.adjusted',
            module: 'stock',
            auditable: $product,
            description: 'Initial stock produk dicatat.',
            before: [
                'product_id' => $product->id,
                'stock_before' => 0,
                'stock_after' => 0,
                'difference' => 0,
                'reason' => 'Initial stock saat produk dibuat.',
                'reference' => 'product:' . $product->id,
            ],
            after: [
                'product_id' => $product->id,
                'stock_before' => 0,
                'stock_after' => $initialStock,
                'difference' => $initialStock,
                'reason' => 'Initial stock saat produk dibuat.',
                'reference' => 'product:' . $product->id,
            ],
            meta: [
                'stock_mutation_id' => $mutation->id,
                'reference_type' => $mutation->reference_type,
                'reference_id' => $mutation->reference_id,
                'mutation_type' => $mutation->mutation_type,
                'qty' => (int) $mutation->qty,
            ],
        );

        return $mutation;
    }

    public function recordStockOpnameAdjustment(
        Product $product,
        StockOpname $stockOpname,
        int $stockBefore,
        int $stockAfter,
        ?string $reason,
        ?int $userId = null
    ): ?StockMutation {
        if ($stockBefore === $stockAfter) {
            return null;
        }

        $mutation = StockMutation::create([
            'product_id' => $product->id,
            'reference_type' => 'stock_opname',
            'reference_id' => $stockOpname->id,
            'mutation_type' => 'adjustment',
            'qty' => abs($stockAfter - $stockBefore),
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $reason ?: 'Adjustment dari stock opname.',
            'created_by' => $userId,
        ]);

        $this->auditLogService->log(
            event: 'stock.adjusted',
            module: 'stock',
            auditable: $product,
            description: 'Stok produk disesuaikan melalui stock opname.',
            before: [
                'product_id' => $product->id,
                'stock_before' => $stockBefore,
                'stock_after' => $stockBefore,
                'difference' => 0,
                'reason' => $reason,
                'reference' => $stockOpname->code,
            ],
            after: [
                'product_id' => $product->id,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'difference' => $stockAfter - $stockBefore,
                'reason' => $reason ?: 'Adjustment dari stock opname.',
                'reference' => $stockOpname->code,
            ],
            meta: [
                'stock_mutation_id' => $mutation->id,
                'stock_opname_id' => $stockOpname->id,
                'stock_opname_code' => $stockOpname->code,
                'mutation_type' => $mutation->mutation_type,
                'qty' => (int) $mutation->qty,
            ],
        );

        return $mutation;
    }

    public function recordSalesReturnRestock(
        Product $product,
        SalesReturn $salesReturn,
        int $stockBefore,
        int $stockAfter,
        ?string $reason,
        ?int $userId = null
    ): ?StockMutation {
        if ($stockBefore === $stockAfter) {
            return null;
        }

        $mutation = StockMutation::create([
            'product_id' => $product->id,
            'reference_type' => 'sales_return',
            'reference_id' => $salesReturn->id,
            'mutation_type' => 'in',
            'qty' => abs($stockAfter - $stockBefore),
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $reason ?: 'Restock dari retur penjualan.',
            'created_by' => $userId,
        ]);

        $this->auditLogService->log(
            event: 'stock.adjusted',
            module: 'stock',
            auditable: $product,
            description: 'Stok produk bertambah dari restock retur penjualan.',
            before: [
                'product_id' => $product->id,
                'stock_before' => $stockBefore,
                'stock_after' => $stockBefore,
                'difference' => 0,
                'reason' => $reason,
                'reference' => $salesReturn->code,
            ],
            after: [
                'product_id' => $product->id,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'difference' => $stockAfter - $stockBefore,
                'reason' => $reason ?: 'Restock dari retur penjualan.',
                'reference' => $salesReturn->code,
            ],
            meta: [
                'stock_mutation_id' => $mutation->id,
                'sales_return_id' => $salesReturn->id,
                'sales_return_code' => $salesReturn->code,
                'mutation_type' => $mutation->mutation_type,
                'qty' => (int) $mutation->qty,
            ],
        );

        return $mutation;
    }

    public function recordPurchaseReturn(
        Product $product,
        PurchaseReturn $purchaseReturn,
        int $stockBefore,
        int $stockAfter,
        ?string $reason,
        ?int $userId = null
    ): ?StockMutation {
        if ($stockBefore === $stockAfter) {
            return null;
        }

        $mutation = StockMutation::create([
            'product_id' => $product->id,
            'reference_type' => 'purchase_return',
            'reference_id' => $purchaseReturn->id,
            'mutation_type' => 'out',
            'qty' => abs($stockAfter - $stockBefore),
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $reason ?: 'Retur pembelian ke supplier.',
            'created_by' => $userId,
        ]);

        $this->auditLogService->log(
            event: 'stock.adjusted',
            module: 'stock',
            auditable: $product,
            description: 'Stok produk berkurang dari retur pembelian.',
            before: [
                'product_id' => $product->id,
                'stock_before' => $stockBefore,
                'stock_after' => $stockBefore,
                'difference' => 0,
                'reason' => $reason,
                'reference' => $purchaseReturn->return_number,
            ],
            after: [
                'product_id' => $product->id,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'difference' => $stockAfter - $stockBefore,
                'reason' => $reason ?: 'Retur pembelian ke supplier.',
                'reference' => $purchaseReturn->return_number,
            ],
            meta: [
                'stock_mutation_id' => $mutation->id,
                'purchase_return_id' => $purchaseReturn->id,
                'purchase_return_number' => $purchaseReturn->return_number,
                'mutation_type' => $mutation->mutation_type,
                'qty' => (int) $mutation->qty,
            ],
        );

        return $mutation;
    }
}
