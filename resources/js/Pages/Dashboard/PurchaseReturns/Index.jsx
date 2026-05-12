import React from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, Link, router } from "@inertiajs/react";
import Button from "@/Components/Dashboard/Button";
import Table from "@/Components/Dashboard/Table";
import Pagination from "@/Components/Dashboard/Pagination";
import {
    IconCirclePlus,
    IconArrowBackUp,
    IconEye,
    IconSearch,
} from "@tabler/icons-react";

function formatDateTime(value) {
    if (!value) return "-";
    return new Intl.DateTimeFormat("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(new Date(value));
}

function formatCurrency(value) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);
}

const resolutionLabels = {
    refund: "Refund",
    credit: "Potong Hutang",
    exchange: "Tukar Barang",
};

export default function Index({ purchaseReturns, filters }) {
    const handleFilterChange = (key, value) => {
        router.get(
            route("purchase-returns.index"),
            { ...filters, [key]: value },
            { preserveState: true, replace: true }
        );
    };

    return (
        <>
            <Head title="Retur Pembelian" />

            <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                        Retur Pembelian
                    </h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Catat pengembalian barang ke supplier.
                    </p>
                </div>
                <Button
                    type="link"
                    href={route("purchase-returns.create")}
                    icon={<IconCirclePlus size={18} strokeWidth={1.5} />}
                    className="bg-primary-500 hover:bg-primary-600 text-white shadow-lg shadow-primary-500/30"
                    label="Retur Baru"
                />
            </div>

            <div className="mb-4 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900 md:grid-cols-3">
                <div className="relative md:col-span-2">
                    <input
                        type="text"
                        value={filters.search || ""}
                        onChange={(e) => handleFilterChange("search", e.target.value)}
                        placeholder="Cari no retur, no invoice, atau supplier..."
                        className="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 pr-11 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    />
                    <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400">
                        <IconSearch size={18} />
                    </div>
                </div>

                <select
                    value={filters.status || ""}
                    onChange={(e) => handleFilterChange("status", e.target.value)}
                    className="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                >
                    <option value="">Semua Status</option>
                    <option value="draft">Draft</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <Table.Card title="Daftar Retur Pembelian">
                <Table>
                    <Table.Thead>
                        <tr>
                            <Table.Th>No. Retur & Invoice</Table.Th>
                            <Table.Th>Supplier</Table.Th>
                            <Table.Th>Status</Table.Th>
                            <Table.Th>Tipe Resolusi</Table.Th>
                            <Table.Th>Total Retur</Table.Th>
                            <Table.Th className="w-24 text-center">Aksi</Table.Th>
                        </tr>
                    </Table.Thead>
                    <Table.Tbody>
                        {purchaseReturns.data.length > 0 ? (
                            purchaseReturns.data.map((pr) => (
                                <tr
                                    key={pr.id}
                                    className="transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                >
                                    <Table.Td>
                                        <div>
                                            <p className="font-semibold text-slate-800 dark:text-slate-200">
                                                {pr.return_number}
                                            </p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                                Ref: {pr.purchase?.invoice_number || "-"}
                                            </p>
                                        </div>
                                    </Table.Td>
                                    <Table.Td>{pr.supplier?.name || "-"}</Table.Td>
                                    <Table.Td>
                                        <span
                                            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                pr.status === "completed"
                                                    ? "bg-success-100 text-success-700 dark:bg-success-950/40 dark:text-success-400"
                                                    : "bg-warning-100 text-warning-700 dark:bg-warning-950/40 dark:text-warning-400"
                                            }`}
                                        >
                                            {pr.status === "completed" ? "Completed" : "Draft"}
                                        </span>
                                    </Table.Td>
                                    <Table.Td>
                                        {pr.resolution_type ? (
                                            <span className="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                {resolutionLabels[pr.resolution_type] || pr.resolution_type}
                                            </span>
                                        ) : (
                                            "-"
                                        )}
                                    </Table.Td>
                                    <Table.Td className="font-medium text-slate-900 dark:text-white">
                                        {formatCurrency(pr.total_return_amount)}
                                    </Table.Td>
                                    <Table.Td className="text-center">
                                        <Link
                                            href={route("purchase-returns.show", pr.id)}
                                            className="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-2 text-slate-600 transition hover:border-primary-300 hover:text-primary-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-primary-700 dark:hover:text-primary-400"
                                        >
                                            <IconEye size={18} />
                                        </Link>
                                    </Table.Td>
                                </tr>
                            ))
                        ) : (
                            <Table.Empty
                                colSpan={6}
                                message={
                                    <div className="text-slate-500 dark:text-slate-400">
                                        Belum ada data retur pembelian.
                                    </div>
                                }
                            >
                                <div className="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                    <IconArrowBackUp size={28} className="text-slate-400" />
                                </div>
                            </Table.Empty>
                        )}
                    </Table.Tbody>
                </Table>
            </Table.Card>

            {purchaseReturns.last_page > 1 && (
                <Pagination links={purchaseReturns.links} />
            )}
        </>
    );
}

Index.layout = (page) => <DashboardLayout children={page} />;
