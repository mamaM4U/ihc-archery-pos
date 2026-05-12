import React, { useEffect, useState } from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, Link, router, useForm } from "@inertiajs/react";
import Button from "@/Components/Dashboard/Button";
import Modal from "@/Components/Dashboard/Modal";
import Table from "@/Components/Dashboard/Table";
import {
    IconArrowLeft,
    IconCheck,
    IconDeviceFloppy,
    IconPackage,
    IconArrowBackUp,
    IconCash,
    IconCreditCard,
    IconRefresh,
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

const resolutionLabels = {
    refund: "Refund (Uang Kembali)",
    credit: "Potong Hutang",
    exchange: "Tukar Barang",
};

const resolutionDescriptions = {
    refund: "Supplier mengembalikan uang tunai atas barang yang diretur.",
    credit: "Nilai retur akan memotong sisa hutang Anda di Payables.",
    exchange:
        "Supplier menukar barang rusak dengan barang yang bagus. Tidak ada perubahan stok maupun keuangan.",
};

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
        danger:
            "border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-400",
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

export default function Show({ purchaseReturn, payable }) {
    const isDraft = purchaseReturn.status === "draft";
    const [localItems, setLocalItems] = useState(purchaseReturn.items);
    const [savingItemId, setSavingItemId] = useState(null);
    const [showCompleteModal, setShowCompleteModal] = useState(false);
    const [validationErrors, setValidationErrors] = useState({});

    const completeForm = useForm({
        resolution_type: "refund",
        notes: purchaseReturn.notes || "",
    });

    useEffect(() => {
        setLocalItems(purchaseReturn.items);
    }, [purchaseReturn.items]);

    const totalReturnAmount = localItems.reduce(
        (sum, item) => sum + item.qty_return * item.buy_price,
        0
    );

    const hasItemsToReturn = localItems.some((item) => item.qty_return > 0);

    const setItemField = (itemId, key, value) => {
        setLocalItems((currentItems) =>
            currentItems.map((item) => {
                if (item.id !== itemId) return item;

                const updated = { ...item, [key]: value };

                if (key === "qty_return") {
                    updated.qty_return = Math.max(
                        0,
                        Math.min(Number(value), item.max_returnable)
                    );
                    updated.subtotal = updated.qty_return * item.buy_price;
                }

                return updated;
            })
        );

        // Clear validation error for this item when user types
        if (validationErrors[itemId]) {
            setValidationErrors((prev) => {
                const next = { ...prev };
                delete next[itemId];
                return next;
            });
        }
    };

    const persistItem = (item) => {
        if (!isDraft) return;

        // Validate: if qty > 0, reason is required
        if (item.qty_return > 0 && !item.return_reason?.trim()) {
            setValidationErrors((prev) => ({
                ...prev,
                [item.id]: "Alasan retur wajib diisi jika qty > 0",
            }));
            toast.error("Alasan retur wajib diisi jika qty > 0");
            return;
        }

        setSavingItemId(item.id);

        router.patch(
            route("purchase-returns.items.update", [
                purchaseReturn.id,
                item.id,
            ]),
            {
                qty_return: item.qty_return,
                return_reason: item.return_reason,
            },
            {
                preserveScroll: true,
                onSuccess: () => toast.success("Item diperbarui"),
                onError: (errors) => {
                    const firstError = Object.values(errors)[0];
                    toast.error(firstError || "Gagal memperbarui item");
                },
                onFinish: () => setSavingItemId(null),
            }
        );
    };

    const openCompleteModal = () => {
        // Default to credit if payable exists, refund otherwise
        const defaultType =
            payable && payable.remaining > 0 ? "credit" : "refund";
        completeForm.setData({
            resolution_type: defaultType,
            notes: purchaseReturn.notes || "",
        });
        setShowCompleteModal(true);
    };

    const submitComplete = (e) => {
        e.preventDefault();
        completeForm.post(
            route("purchase-returns.complete", purchaseReturn.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setShowCompleteModal(false);
                    toast.success("Retur pembelian berhasil diselesaikan");
                },
                onError: (errors) => {
                    const firstError = Object.values(errors)[0];
                    toast.error(
                        firstError || "Gagal menyelesaikan retur pembelian"
                    );
                },
            }
        );
    };

    return (
        <>
            <Head title={purchaseReturn.return_number} />

            <div className="mb-6">
                <Link
                    href={route("purchase-returns.index")}
                    className="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-primary-600"
                >
                    <IconArrowLeft size={16} />
                    Kembali ke daftar retur
                </Link>

                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div className="mb-2 flex items-center gap-2">
                            <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                                {purchaseReturn.return_number}
                            </h1>
                            <span
                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                                    isDraft
                                        ? "bg-warning-100 text-warning-700 dark:bg-warning-950/30 dark:text-warning-400"
                                        : "bg-success-100 text-success-700 dark:bg-success-950/30 dark:text-success-400"
                                }`}
                            >
                                {isDraft ? "Draft" : "Completed"}
                            </span>
                            {purchaseReturn.resolution_type && (
                                <span className="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {resolutionLabels[
                                        purchaseReturn.resolution_type
                                    ] || purchaseReturn.resolution_type}
                                </span>
                            )}
                        </div>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            Ref:{" "}
                            <strong>
                                {purchaseReturn.purchase?.invoice_number}
                            </strong>{" "}
                            • Supplier:{" "}
                            <strong>
                                {purchaseReturn.supplier?.name}
                            </strong>{" "}
                            • Dibuat oleh{" "}
                            {purchaseReturn.creator?.name || "-"} •{" "}
                            {formatDateTime(purchaseReturn.created_at)}
                        </p>
                        {!isDraft && (
                            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Diselesaikan oleh{" "}
                                {purchaseReturn.completer?.name || "-"} •{" "}
                                {formatDateTime(purchaseReturn.completed_at)}
                            </p>
                        )}
                    </div>

                    {isDraft && (
                        <Button
                            type="button"
                            icon={<IconCheck size={18} />}
                            className="bg-success-500 hover:bg-success-600 text-white shadow-lg shadow-success-500/20 disabled:opacity-50"
                            label="Selesaikan Retur"
                            onClick={openCompleteModal}
                            disabled={!hasItemsToReturn}
                        />
                    )}
                </div>
            </div>

            <div className="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <SummaryCard
                    label="Item Diretur"
                    value={localItems.filter((i) => i.qty_return > 0).length}
                />
                <SummaryCard
                    label="Total Nilai Retur"
                    value={formatCurrency(
                        purchaseReturn.total_return_amount || totalReturnAmount
                    )}
                    tone="danger"
                />
                {!isDraft && purchaseReturn.resolution_type === "credit" && (
                    <SummaryCard
                        label="Potong Hutang"
                        value={formatCurrency(purchaseReturn.credited_amount)}
                        tone="primary"
                    />
                )}
                {!isDraft && purchaseReturn.refund_amount > 0 && (
                    <SummaryCard
                        label="Refund"
                        value={formatCurrency(purchaseReturn.refund_amount)}
                        tone="success"
                    />
                )}
            </div>

            <div className="grid gap-6 xl:grid-cols-[1.7fr_1fr]">
                <div className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                    <h2 className="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
                        Item Retur
                    </h2>

                    <div className="overflow-x-auto">
                        <Table>
                            <Table.Thead>
                                <tr>
                                    <Table.Th>Produk</Table.Th>
                                    <Table.Th>Qty Beli</Table.Th>
                                    <Table.Th>Qty Retur</Table.Th>
                                    <Table.Th>Harga Beli</Table.Th>
                                    <Table.Th>Subtotal Retur</Table.Th>
                                    <Table.Th>Alasan</Table.Th>
                                    {isDraft && (
                                        <Table.Th className="w-20 text-center">
                                            Aksi
                                        </Table.Th>
                                    )}
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
                                                        {item.product.category
                                                            ?.name || "-"}{" "}
                                                        •{" "}
                                                        {item.product.barcode ||
                                                            item.product.sku ||
                                                            "-"}
                                                    </p>
                                                </div>
                                            </Table.Td>
                                            <Table.Td>
                                                <span className="text-sm text-slate-600 dark:text-slate-300">
                                                    {item.original_qty}
                                                </span>
                                                <span className="ml-1 text-xs text-slate-400">
                                                    (max{" "}
                                                    {item.max_returnable})
                                                </span>
                                            </Table.Td>
                                            <Table.Td>
                                                <input
                                                    type="number"
                                                    min="0"
                                                    max={item.max_returnable}
                                                    value={item.qty_return}
                                                    disabled={!isDraft}
                                                    onChange={(e) =>
                                                        setItemField(
                                                            item.id,
                                                            "qty_return",
                                                            e.target.value
                                                        )
                                                    }
                                                    className={`h-10 w-20 rounded-lg border bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-slate-800 dark:text-slate-200 ${
                                                        validationErrors[
                                                            item.id
                                                        ]
                                                            ? "border-red-400 dark:border-red-600"
                                                            : "border-slate-200 dark:border-slate-700"
                                                    }`}
                                                />
                                            </Table.Td>
                                            <Table.Td>
                                                <span className="text-sm text-slate-600 dark:text-slate-300">
                                                    {formatCurrency(
                                                        item.buy_price
                                                    )}
                                                </span>
                                            </Table.Td>
                                            <Table.Td>
                                                <span className="font-semibold text-slate-700 dark:text-slate-300">
                                                    {formatCurrency(
                                                        item.qty_return *
                                                            item.buy_price
                                                    )}
                                                </span>
                                            </Table.Td>
                                            <Table.Td>
                                                {isDraft ? (
                                                    <div>
                                                        <input
                                                            type="text"
                                                            value={
                                                                item.return_reason ||
                                                                ""
                                                            }
                                                            onChange={(e) =>
                                                                setItemField(
                                                                    item.id,
                                                                    "return_reason",
                                                                    e.target
                                                                        .value
                                                                )
                                                            }
                                                            placeholder="Rusak / Expired / ..."
                                                            className={`h-10 w-full min-w-[140px] rounded-lg border bg-slate-50 px-3 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:bg-slate-800 dark:text-slate-200 ${
                                                                validationErrors[
                                                                    item.id
                                                                ]
                                                                    ? "border-red-400 dark:border-red-600"
                                                                    : "border-slate-200 dark:border-slate-700"
                                                            }`}
                                                        />
                                                        {validationErrors[
                                                            item.id
                                                        ] && (
                                                            <p className="mt-1 text-xs text-red-500">
                                                                {
                                                                    validationErrors[
                                                                        item.id
                                                                    ]
                                                                }
                                                            </p>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-slate-600 dark:text-slate-300">
                                                        {item.return_reason ||
                                                            "-"}
                                                    </span>
                                                )}
                                            </Table.Td>
                                            {isDraft && (
                                                <Table.Td className="text-center">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            persistItem(item)
                                                        }
                                                        disabled={
                                                            savingItemId ===
                                                            item.id
                                                        }
                                                        className="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-2 text-slate-600 transition hover:border-primary-300 hover:text-primary-600 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-primary-700 dark:hover:text-primary-400"
                                                        title="Simpan perubahan item"
                                                    >
                                                        <IconDeviceFloppy
                                                            size={18}
                                                        />
                                                    </button>
                                                </Table.Td>
                                            )}
                                        </tr>
                                    ))
                                ) : (
                                    <Table.Empty
                                        colSpan={isDraft ? 7 : 6}
                                        message={
                                            <div className="text-slate-500 dark:text-slate-400">
                                                Tidak ada item untuk diretur.
                                            </div>
                                        }
                                    >
                                        <div className="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                            <IconPackage
                                                size={28}
                                                className="text-slate-400"
                                            />
                                        </div>
                                    </Table.Empty>
                                )}
                            </Table.Tbody>
                        </Table>
                    </div>
                </div>

                <div className="space-y-6">
                    <div className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                        <h2 className="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
                            Informasi
                        </h2>
                        <div className="space-y-3 text-sm text-slate-500 dark:text-slate-400">
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                                <p className="font-medium text-slate-700 dark:text-slate-200">
                                    Cara penggunaan
                                </p>
                                <ul className="mt-2 space-y-2">
                                    <li>
                                        1. Isi <strong>Qty Retur</strong> untuk
                                        setiap produk yang akan dikembalikan.
                                    </li>
                                    <li>
                                        2. Isi <strong>Alasan</strong> (wajib)
                                        misal: "Rusak", "Expired", dll.
                                    </li>
                                    <li>
                                        3. Klik tombol{" "}
                                        <strong>Simpan (disket)</strong> di tiap
                                        baris.
                                    </li>
                                    <li>
                                        4. Klik{" "}
                                        <strong>Selesaikan Retur</strong> dan
                                        pilih tipe penyelesaian.
                                    </li>
                                </ul>
                            </div>

                            {payable && (
                                <div className="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-900 dark:bg-primary-950/30">
                                    <p className="font-medium text-primary-700 dark:text-primary-300">
                                        Hutang Terkait
                                    </p>
                                    <p className="mt-1 text-primary-600 dark:text-primary-400">
                                        Sisa hutang untuk invoice ini:{" "}
                                        <strong>
                                            {formatCurrency(payable.remaining)}
                                        </strong>
                                    </p>
                                    <p className="mt-1 text-xs text-primary-500 dark:text-primary-500">
                                        Jika Anda pilih "Potong Hutang", nilai
                                        retur akan otomatis mengurangi sisa
                                        hutang ini.
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Complete Modal */}
            <Modal
                show={showCompleteModal}
                onClose={() => setShowCompleteModal(false)}
                title={
                    <div className="flex items-center gap-2">
                        <IconCheck size={18} />
                        Selesaikan Retur Pembelian
                    </div>
                }
                maxWidth="lg"
            >
                <form onSubmit={submitComplete} className="space-y-5">
                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-center justify-between">
                            <span className="text-sm text-slate-500 dark:text-slate-400">
                                Total Nilai Retur:
                            </span>
                            <span className="text-lg font-bold text-slate-900 dark:text-white">
                                {formatCurrency(totalReturnAmount)}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label className="mb-3 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Pilih Tipe Penyelesaian{" "}
                            <span className="text-red-500">*</span>
                        </label>
                        <div className="space-y-3">
                            {[
                                {
                                    value: "refund",
                                    icon: (
                                        <IconCash
                                            size={20}
                                            className="text-success-500"
                                        />
                                    ),
                                    label: resolutionLabels.refund,
                                    desc: resolutionDescriptions.refund,
                                },
                                {
                                    value: "credit",
                                    icon: (
                                        <IconCreditCard
                                            size={20}
                                            className="text-primary-500"
                                        />
                                    ),
                                    label: resolutionLabels.credit,
                                    desc: resolutionDescriptions.credit,
                                    disabled:
                                        !payable || payable.remaining <= 0,
                                    disabledNote:
                                        "Tidak tersedia. Tidak ada hutang yang belum lunas untuk invoice ini.",
                                },
                                {
                                    value: "exchange",
                                    icon: (
                                        <IconRefresh
                                            size={20}
                                            className="text-warning-500"
                                        />
                                    ),
                                    label: resolutionLabels.exchange,
                                    desc: resolutionDescriptions.exchange,
                                },
                            ].map((option) => (
                                <label
                                    key={option.value}
                                    className={`flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition ${
                                        option.disabled
                                            ? "cursor-not-allowed border-slate-100 bg-slate-50 opacity-50 dark:border-slate-800 dark:bg-slate-900"
                                            : completeForm.data
                                                    .resolution_type ===
                                                option.value
                                              ? "border-primary-300 bg-primary-50/60 dark:border-primary-700 dark:bg-primary-950/20"
                                              : "border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600"
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="resolution_type"
                                        value={option.value}
                                        disabled={option.disabled}
                                        checked={
                                            completeForm.data
                                                .resolution_type ===
                                            option.value
                                        }
                                        onChange={() =>
                                            completeForm.setData(
                                                "resolution_type",
                                                option.value
                                            )
                                        }
                                        className="mt-1 h-4 w-4 border-slate-300 text-primary-500 focus:ring-primary-500"
                                    />
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2">
                                            {option.icon}
                                            <span className="font-medium text-slate-800 dark:text-slate-200">
                                                {option.label}
                                            </span>
                                        </div>
                                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            {option.disabled
                                                ? option.disabledNote
                                                : option.desc}
                                        </p>
                                    </div>
                                </label>
                            ))}
                        </div>
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Catatan Tambahan
                        </label>
                        <textarea
                            value={completeForm.data.notes}
                            onChange={(e) =>
                                completeForm.setData("notes", e.target.value)
                            }
                            rows={2}
                            placeholder="Catatan opsional..."
                            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        />
                    </div>

                    <div className="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            onClick={() => setShowCompleteModal(false)}
                            className="inline-flex h-11 items-center justify-center rounded-xl px-5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            Batal
                        </button>
                        <Button
                            type="submit"
                            icon={<IconCheck size={18} />}
                            className="bg-primary-500 hover:bg-primary-600 text-white"
                            label="Proses Retur"
                            disabled={completeForm.processing}
                        />
                    </div>
                </form>
            </Modal>
        </>
    );
}

Show.layout = (page) => <DashboardLayout children={page} />;
