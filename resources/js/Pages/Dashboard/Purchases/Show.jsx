import React, { useEffect, useMemo, useState } from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, Link, router, useForm } from "@inertiajs/react";
import Button from "@/Components/Dashboard/Button";
import Modal from "@/Components/Dashboard/Modal";
import Table from "@/Components/Dashboard/Table";
import {
    IconArrowLeft,
    IconCheck,
    IconDeviceFloppy,
    IconShoppingCart,
    IconPackage,
    IconPlus,
    IconSearch,
    IconTrash,
} from "@tabler/icons-react";
import toast from "react-hot-toast";

const formatDateTime = (value) =>
    value
        ? new Intl.DateTimeFormat("id-ID", {
              dateStyle: "medium",
              timeStyle: "short",
          }).format(new Date(value))
        : "-";

const formatCurrency = (value) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);

function SummaryCard({ label, value, tone = "default" }) {
    const toneClasses = {
        default:
            "border-slate-200 bg-white text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white",
        success:
            "border-success-200 bg-success-50 text-success-700 dark:border-success-900 dark:bg-success-950/30 dark:text-success-400",
        warning:
            "border-warning-200 bg-warning-50 text-warning-700 dark:border-warning-900 dark:bg-warning-950/30 dark:text-warning-400",
        primary:
            "border-primary-200 bg-primary-50 text-primary-700 dark:border-primary-900 dark:bg-primary-950/30 dark:text-primary-400",
    };

    return (
        <div className={`rounded-2xl border p-4 ${toneClasses[tone]}`}>
            <p className="text-xs font-medium uppercase tracking-wide opacity-80">
                {label}
            </p>
            <p className="mt-2 text-2xl font-bold">{value}</p>
        </div>
    );
}

export default function Show({
    purchase,
    availableProducts,
    productFilters,
}) {
    const isDraft = purchase.status === "draft";
    const [localItems, setLocalItems] = useState(purchase.items);
    const [savingItemId, setSavingItemId] = useState(null);
    const [showProductModal, setShowProductModal] = useState(false);
    const [showFinalizeModal, setShowFinalizeModal] = useState(false);
    const [paidAmount, setPaidAmount] = useState("");
    const [productSearchInput, setProductSearchInput] = useState(
        productFilters.search || ""
    );

    const detailForm = useForm({
        notes: purchase.notes || "",
        discount: purchase.discount || 0,
    });

    const finalizeForm = useForm({
        paid_amount: "",
    });

    useEffect(() => {
        setLocalItems(purchase.items);
        detailForm.setData({
            notes: purchase.notes || "",
            discount: purchase.discount || 0,
        });
    }, [purchase.items, purchase.notes, purchase.discount]);

    useEffect(() => {
        setProductSearchInput(productFilters.search || "");
    }, [productFilters.search]);

    const filters = useMemo(
        () => ({
            product_search: productFilters.search || "",
        }),
        [productFilters]
    );
    const isWaitingSearch =
        showProductModal &&
        productSearchInput.trim() !== (filters.product_search || "").trim();

    useEffect(() => {
        if (!showProductModal) {
            return;
        }

        const timeoutId = setTimeout(() => {
            if (productSearchInput === (filters.product_search || "")) {
                return;
            }

            updateFilter("product_search", productSearchInput);
        }, 1200);

        return () => clearTimeout(timeoutId);
    }, [productSearchInput, showProductModal, filters.product_search]);

    const updateFilter = (key, value) => {
        router.get(
            route("purchases.show", purchase.id),
            {
                ...filters,
                [key]: value,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const saveDetails = (event) => {
        event.preventDefault();

        detailForm.patch(route("purchases.update", purchase.id), {
            preserveScroll: true,
            onSuccess: () => toast.success("Detail pembelian diperbarui"),
            onError: () => toast.error("Gagal memperbarui detail pembelian"),
        });
    };

    const addProduct = (productId) => {
        router.post(
            route("purchases.items.store", purchase.id),
            { product_id: productId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setShowProductModal(false);
                    toast.success("Produk ditambahkan ke draft");
                },
                onError: (errors) => {
                    const firstError = Object.values(errors)[0];
                    toast.error(firstError || "Gagal menambahkan produk");
                },
            }
        );
    };

    const setItemField = (itemId, key, value) => {
        setLocalItems((currentItems) =>
            currentItems.map((item) => {
                if (item.id !== itemId) {
                    return item;
                }

                const updatedValue = key === "qty" ? Math.max(1, Number(value)) : Number(value);
                const nextQty = key === "qty" ? updatedValue : item.qty;
                const nextBuyPrice = key === "buy_price" ? updatedValue : item.buy_price;

                return {
                    ...item,
                    [key]: updatedValue,
                    subtotal: nextQty * nextBuyPrice,
                };
            })
        );
    };

    const persistItem = (item) => {
        if (!isDraft) return;

        setSavingItemId(item.id);

        router.patch(
            route("purchases.items.update", [purchase.id, item.id]),
            {
                qty: item.qty,
                buy_price: item.buy_price,
            },
            {
                preserveScroll: true,
                onSuccess: () => toast.success("Item diperbarui"),
                onError: () => toast.error("Gagal memperbarui item"),
                onFinish: () => setSavingItemId(null),
            }
        );
    };

    const deleteItem = (item) => {
        if (!isDraft || !confirm("Yakin ingin menghapus item ini?")) return;

        router.delete(
            route("purchases.items.destroy", [purchase.id, item.id]),
            {
                preserveScroll: true,
                onSuccess: () => toast.success("Item dihapus"),
            }
        );
    };

    const openFinalizeModal = () => {
        finalizeForm.setData("paid_amount", purchase.grand_total);
        setShowFinalizeModal(true);
    };

    const finalize = (e) => {
        e.preventDefault();
        finalizeForm.post(
            route("purchases.finalize", purchase.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setShowFinalizeModal(false);
                    toast.success("Pembelian difinalisasi");
                },
                onError: (errors) => {
                    const firstError = Object.values(errors)[0];
                    toast.error(firstError || "Gagal finalize. Periksa data.");
                },
            }
        );
    };

    return (
        <>
            <Head title={purchase.invoice_number} />

            <div className="mb-6">
                <Link
                    href={route("purchases.index")}
                    className="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-primary-600"
                >
                    <IconArrowLeft size={16} />
                    Kembali ke daftar pembelian
                </Link>

                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div className="mb-2 flex items-center gap-2">
                            <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                                {purchase.invoice_number}
                            </h1>
                            <span
                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                                    isDraft
                                        ? "bg-warning-100 text-warning-700 dark:bg-warning-950/30 dark:text-warning-400"
                                        : "bg-success-100 text-success-700 dark:bg-success-950/30 dark:text-success-400"
                                }`}
                            >
                                {isDraft ? "Draft" : "Finalized"}
                            </span>
                        </div>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            Supplier: <strong>{purchase.supplier?.name}</strong> • Dibuat oleh {purchase.creator?.name || "-"} •{" "}
                            {formatDateTime(purchase.created_at)}
                        </p>
                        {!isDraft && (
                            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Difinalisasi oleh {purchase.finalizer?.name || "-"} •{" "}
                                {formatDateTime(purchase.finalized_at)}
                            </p>
                        )}
                    </div>

                    {isDraft && (
                        <Button
                            type="button"
                            icon={<IconCheck size={18} />}
                            className="bg-success-500 hover:bg-success-600 text-white shadow-lg shadow-success-500/20 disabled:opacity-50"
                            label="Finalize Pembelian"
                            onClick={openFinalizeModal}
                            disabled={localItems.length === 0}
                        />
                    )}
                </div>
            </div>

            <div className="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <SummaryCard label="Total Item" value={localItems.length} />
                <SummaryCard
                    label="Subtotal"
                    value={formatCurrency(purchase.total)}
                />
                <SummaryCard
                    label="Diskon"
                    value={formatCurrency(purchase.discount)}
                    tone={purchase.discount > 0 ? "warning" : "default"}
                />
                <SummaryCard
                    label="Grand Total"
                    value={formatCurrency(purchase.grand_total)}
                    tone="primary"
                />
            </div>

            <div className="grid gap-6 xl:grid-cols-[1.7fr_1fr]">
                <div className="space-y-6">
                    <div className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                                Item Pembelian
                            </h2>
                            {isDraft && (
                                <Button
                                    type="button"
                                    icon={<IconPlus size={18} />}
                                    className="bg-primary-500 hover:bg-primary-600 text-white"
                                    label="Tambah Produk"
                                    onClick={() => setShowProductModal(true)}
                                />
                            )}
                        </div>

                        <div className="overflow-x-auto">
                            <Table>
                                <Table.Thead>
                                    <tr>
                                        <Table.Th>Produk</Table.Th>
                                        <Table.Th>Qty</Table.Th>
                                        <Table.Th>Harga Beli</Table.Th>
                                        <Table.Th>Subtotal</Table.Th>
                                        <Table.Th className="w-24 text-center">Aksi</Table.Th>
                                    </tr>
                                </Table.Thead>
                                <Table.Tbody>
                                    {localItems.length > 0 ? (
                                        localItems.map((item) => (
                                            <tr
                                                key={item.id}
                                                className="transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                            >
                                                <Table.Td>
                                                    <div>
                                                        <p className="font-medium text-slate-800 dark:text-slate-200">
                                                            {item.product.title}
                                                        </p>
                                                        <p className="text-xs text-slate-500 dark:text-slate-400">
                                                            {item.product.category?.name || "-"} •{" "}
                                                            {item.product.barcode || item.product.sku || "-"}
                                                        </p>
                                                    </div>
                                                </Table.Td>
                                                <Table.Td>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        value={item.qty}
                                                        disabled={!isDraft}
                                                        onChange={(e) =>
                                                            setItemField(item.id, "qty", e.target.value)
                                                        }
                                                        className="h-10 w-20 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                                    />
                                                </Table.Td>
                                                <Table.Td>
                                                    <div className="relative">
                                                        <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">Rp</span>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            value={item.buy_price}
                                                            disabled={!isDraft}
                                                            onChange={(e) =>
                                                                setItemField(item.id, "buy_price", e.target.value)
                                                            }
                                                            className="h-10 w-32 rounded-lg border border-slate-200 bg-slate-50 pl-8 pr-3 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                                        />
                                                    </div>
                                                </Table.Td>
                                                <Table.Td>
                                                    <span className="font-semibold text-slate-700 dark:text-slate-300">
                                                        {formatCurrency(item.subtotal)}
                                                    </span>
                                                </Table.Td>
                                                <Table.Td className="text-center">
                                                    {isDraft ? (
                                                        <div className="flex items-center justify-center gap-2">
                                                            <button
                                                                type="button"
                                                                onClick={() => persistItem(item)}
                                                                disabled={savingItemId === item.id}
                                                                className="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-2 text-slate-600 transition hover:border-primary-300 hover:text-primary-600 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-primary-700 dark:hover:text-primary-400"
                                                                title="Simpan perubahan item"
                                                            >
                                                                <IconDeviceFloppy size={18} />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => deleteItem(item)}
                                                                className="inline-flex rounded-xl border border-red-200 bg-red-50 p-2 text-red-600 transition hover:bg-red-100 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40"
                                                                title="Hapus item"
                                                            >
                                                                <IconTrash size={18} />
                                                            </button>
                                                        </div>
                                                    ) : (
                                                        "-"
                                                    )}
                                                </Table.Td>
                                            </tr>
                                        ))
                                    ) : (
                                        <Table.Empty
                                            colSpan={5}
                                            message={
                                                <div className="text-slate-500 dark:text-slate-400">
                                                    Belum ada produk pada sesi ini.
                                                </div>
                                            }
                                        >
                                            <div className="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                                <IconPackage size={28} className="text-slate-400" />
                                            </div>
                                        </Table.Empty>
                                    )}
                                </Table.Tbody>
                            </Table>
                        </div>
                    </div>
                </div>

                <div className="space-y-6">
                    <form
                        onSubmit={saveDetails}
                        className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <h2 className="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
                            Detail Pembelian
                        </h2>
                        
                        <div className="mb-4">
                            <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Total Diskon (Rp)
                            </label>
                            <input
                                type="number"
                                min="0"
                                value={detailForm.data.discount}
                                disabled={!isDraft}
                                onChange={(e) => detailForm.setData("discount", e.target.value)}
                                className="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            />
                        </div>

                        <div className="mb-4">
                            <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Catatan
                            </label>
                            <textarea
                                value={detailForm.data.notes}
                                disabled={!isDraft}
                                onChange={(event) =>
                                    detailForm.setData("notes", event.target.value)
                                }
                                rows={3}
                                className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                placeholder="Catatan pembelian..."
                            />
                        </div>
                        
                        {isDraft && (
                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    icon={<IconDeviceFloppy size={18} />}
                                    className="bg-primary-500 hover:bg-primary-600 text-white"
                                    label="Simpan Detail"
                                    disabled={detailForm.processing}
                                />
                            </div>
                        )}
                    </form>

                    <div className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                        <h2 className="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
                            Informasi Sesi
                        </h2>
                        <div className="space-y-3 text-sm text-slate-500 dark:text-slate-400">
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                                <p className="font-medium text-slate-700 dark:text-slate-200">
                                    Cara penggunaan
                                </p>
                                <ul className="mt-2 space-y-2">
                                    <li>1. Tambahkan produk yang dibeli.</li>
                                    <li>2. Ubah Qty dan Harga Beli sesuai nota asli dari Supplier.</li>
                                    <li>3. Jangan lupa klik <strong>Simpan</strong> (disket) di tiap baris item yang diubah.</li>
                                    <li>4. Isi diskon jika ada, lalu klik Simpan Detail.</li>
                                    <li>5. Klik <strong>Finalize</strong> untuk memproses stok dan mencatat pembayaran/hutang.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Modal
                show={showProductModal}
                onClose={() => setShowProductModal(false)}
                title={
                    <div className="flex items-center gap-2">
                        <IconShoppingCart size={18} />
                        Cari Produk untuk Dibeli
                    </div>
                }
                maxWidth="2xl"
            >
                <div className="space-y-4">
                    <div className="relative">
                        <input
                            type="text"
                            autoFocus
                            value={productSearchInput}
                            onChange={(event) =>
                                setProductSearchInput(event.target.value)
                            }
                            placeholder="Cari nama produk, barcode, atau SKU..."
                            className="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 pr-11 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        />
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400">
                            <IconSearch size={18} />
                        </div>
                    </div>

                    {isWaitingSearch ? (
                        <div className="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Menunggu input selesai, pencarian akan dijalankan dalam 1-2 detik.
                        </div>
                    ) : filters.product_search ? (
                        availableProducts.length > 0 ? (
                            <div className="max-h-[420px] space-y-3 overflow-y-auto pr-1 dashboard-scrollbar">
                                {availableProducts.map((product) => (
                                    <button
                                        key={product.id}
                                        type="button"
                                        onClick={() => addProduct(product.id)}
                                        className="flex w-full items-start justify-between gap-3 rounded-xl border border-slate-200 p-4 text-left transition hover:border-primary-300 hover:bg-primary-50/50 dark:border-slate-700 dark:hover:border-primary-700 dark:hover:bg-primary-950/20"
                                    >
                                        <div>
                                            <p className="font-medium text-slate-800 dark:text-slate-200">
                                                {product.title}
                                            </p>
                                            <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                {product.category?.name || "-"} • {product.barcode || product.sku || "-"}
                                            </p>
                                            <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                Stok saat ini: {product.stock} • Harga beli terakhir: {formatCurrency(product.buy_price)}
                                            </p>
                                        </div>
                                        <span className="inline-flex rounded-lg bg-primary-500 px-3 py-2 text-xs font-semibold text-white">
                                            Tambah
                                        </span>
                                    </button>
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                Tidak ada produk yang cocok dengan kata kunci pencarian.
                            </div>
                        )
                    ) : (
                        <div className="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Ketik kata kunci, lalu tunggu sebentar untuk menampilkan hasil pencarian produk.
                        </div>
                    )}
                </div>
            </Modal>

            <Modal
                show={showFinalizeModal}
                onClose={() => setShowFinalizeModal(false)}
                title={
                    <div className="flex items-center gap-2">
                        <IconCheck size={18} />
                        Finalisasi Pembelian
                    </div>
                }
                maxWidth="md"
            >
                <form onSubmit={finalize} className="space-y-5">
                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-slate-500 dark:text-slate-400">Grand Total Tagihan:</span>
                            <span className="text-lg font-bold text-slate-900 dark:text-white">{formatCurrency(purchase.grand_total)}</span>
                        </div>
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Jumlah Dibayarkan (Rp) <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            min="0"
                            max={purchase.grand_total}
                            value={finalizeForm.data.paid_amount}
                            onChange={(e) => finalizeForm.setData("paid_amount", e.target.value)}
                            required
                            className="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        />
                        <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            Jika jumlah yang dibayarkan kurang dari Grand Total, sistem akan otomatis mencatat sisa kekurangannya sebagai <strong>Hutang (Payable)</strong> ke Supplier.
                        </p>
                    </div>

                    <div className="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            onClick={() => setShowFinalizeModal(false)}
                            className="inline-flex h-11 items-center justify-center rounded-xl px-5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            Batal
                        </button>
                        <Button
                            type="submit"
                            icon={<IconCheck size={18} />}
                            className="bg-primary-500 hover:bg-primary-600 text-white"
                            label="Proses Finalisasi"
                            disabled={finalizeForm.processing}
                        />
                    </div>
                </form>
            </Modal>
        </>
    );
}

Show.layout = (page) => <DashboardLayout children={page} />;
