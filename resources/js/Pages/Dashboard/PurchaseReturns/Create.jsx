import React, { useEffect, useState } from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, router } from "@inertiajs/react";
import Button from "@/Components/Dashboard/Button";
import Table from "@/Components/Dashboard/Table";
import {
    IconArrowBackUp,
    IconSearch,
    IconShoppingCart,
} from "@tabler/icons-react";

function formatCurrency(value) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);
}

function formatDateTime(value) {
    if (!value) return "-";
    return new Intl.DateTimeFormat("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(new Date(value));
}

export default function Create({ purchases, filters }) {
    const [searchInput, setSearchInput] = useState(filters.search || "");

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (searchInput === (filters.search || "")) return;
            router.get(
                route("purchase-returns.create"),
                { search: searchInput },
                { preserveState: true, replace: true }
            );
        }, 800);
        return () => clearTimeout(timeout);
    }, [searchInput]);

    const selectPurchase = (purchaseId) => {
        router.post(route("purchase-returns.store"), {
            purchase_id: purchaseId,
        });
    };

    return (
        <>
            <Head title="Retur Pembelian Baru" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                    Buat Retur Pembelian Baru
                </h1>
                <p className="text-sm text-slate-500 dark:text-slate-400">
                    Cari dan pilih nota pembelian yang ingin diretur ke
                    supplier.
                </p>
            </div>

            <div className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <div className="mb-4">
                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Cari Nota Pembelian (Invoice Number / Supplier)
                    </label>
                    <div className="relative">
                        <input
                            type="text"
                            autoFocus
                            value={searchInput}
                            onChange={(e) => setSearchInput(e.target.value)}
                            placeholder="Ketik no invoice atau nama supplier..."
                            className="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 pr-11 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        />
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400">
                            <IconSearch size={18} />
                        </div>
                    </div>
                </div>

                {filters.search ? (
                    purchases.length > 0 ? (
                        <div className="space-y-3">
                            {purchases.map((purchase) => (
                                <button
                                    key={purchase.id}
                                    type="button"
                                    onClick={() =>
                                        selectPurchase(purchase.id)
                                    }
                                    className="flex w-full items-start justify-between gap-3 rounded-xl border border-slate-200 p-4 text-left transition hover:border-primary-300 hover:bg-primary-50/50 dark:border-slate-700 dark:hover:border-primary-700 dark:hover:bg-primary-950/20"
                                >
                                    <div>
                                        <p className="font-semibold text-slate-800 dark:text-slate-200">
                                            {purchase.invoice_number}
                                        </p>
                                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Supplier:{" "}
                                            {purchase.supplier?.name ||
                                                "-"}{" "}
                                            • {purchase.items_count} item •{" "}
                                            {formatCurrency(
                                                purchase.grand_total
                                            )}
                                        </p>
                                        <p className="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                                            Dibuat:{" "}
                                            {formatDateTime(
                                                purchase.created_at
                                            )}
                                        </p>
                                    </div>
                                    <span className="inline-flex shrink-0 rounded-lg bg-primary-500 px-3 py-2 text-xs font-semibold text-white">
                                        Pilih
                                    </span>
                                </button>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Tidak ada nota pembelian yang cocok. Hanya pembelian
                            berstatus <strong>Finalized</strong> yang dapat diretur.
                        </div>
                    )
                ) : (
                    <div className="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <div className="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                            <IconShoppingCart
                                size={28}
                                className="text-slate-400"
                            />
                        </div>
                        Ketik kata kunci untuk mencari nota pembelian yang ingin
                        diretur.
                    </div>
                )}
            </div>
        </>
    );
}

Create.layout = (page) => <DashboardLayout children={page} />;
