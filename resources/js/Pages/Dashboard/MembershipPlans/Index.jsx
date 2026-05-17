import React from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, usePage } from "@inertiajs/react";
import Button from "@/Components/Dashboard/Button";
import {
    IconCirclePlus,
    IconDatabaseOff,
    IconPencilCog,
    IconTrash,
} from "@tabler/icons-react";
import Search from "@/Components/Dashboard/Search";
import Table from "@/Components/Dashboard/Table";
import Pagination from "@/Components/Dashboard/Pagination";

/**
 * Format a number as Indonesian Rupiah.
 */
function formatRupiah(amount) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

/**
 * Convert category slug to readable label.
 */
function formatCategory(category) {
    const labels = {
        registration: "Registrasi",
        trial: "Trial",
        monthly_no_equipment: "Bulanan - Tanpa Alat",
        monthly_with_equipment: "Bulanan - Dengan Alat",
        family: "Keluarga",
    };

    return labels[category] || category;
}

export default function Index({ membershipPlans }) {
    const { errors } = usePage().props;

    return (
        <>
            <Head title="Paket Membership" />

            {/* Header */}
            <div className="mb-6">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                            Paket Membership
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            {membershipPlans.total || membershipPlans.data?.length || 0}{" "}
                            paket terdaftar
                        </p>
                    </div>
                    <Button
                        type={"link"}
                        icon={<IconCirclePlus size={18} strokeWidth={1.5} />}
                        className={
                            "bg-primary-500 hover:bg-primary-600 text-white shadow-lg shadow-primary-500/30"
                        }
                        label={"Tambah Paket"}
                        href={route("membership-plans.create")}
                    />
                </div>
            </div>

            {/* Toolbar */}
            <div className="mb-4 flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
                <div className="w-full sm:w-80">
                    <Search
                        url={route("membership-plans.index")}
                        placeholder="Cari paket membership..."
                    />
                </div>
            </div>

            {/* Content */}
            {membershipPlans.data.length > 0 ? (
                <Table.Card title={"Data Paket Membership"}>
                    <Table>
                        <Table.Thead>
                            <tr>
                                <Table.Th className="w-10">No</Table.Th>
                                <Table.Th>Nama</Table.Th>
                                <Table.Th>Kategori</Table.Th>
                                <Table.Th>Harga</Table.Th>
                                <Table.Th>Durasi</Table.Th>
                                <Table.Th>Kuota Sesi</Table.Th>
                                <Table.Th>Alat</Table.Th>
                                <Table.Th>Status</Table.Th>
                                <Table.Th></Table.Th>
                            </tr>
                        </Table.Thead>
                        <Table.Tbody>
                            {membershipPlans.data.map((plan, i) => (
                                <tr
                                    className="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                    key={plan.id}
                                >
                                    <Table.Td className="text-center">
                                        {++i +
                                            (membershipPlans.current_page - 1) *
                                                membershipPlans.per_page}
                                    </Table.Td>
                                    <Table.Td>
                                        <p className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                            {plan.name}
                                        </p>
                                    </Table.Td>
                                    <Table.Td>
                                        <span className="text-sm text-slate-600 dark:text-slate-400">
                                            {formatCategory(plan.category)}
                                        </span>
                                    </Table.Td>
                                    <Table.Td>
                                        <span className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                            {formatRupiah(plan.price)}
                                        </span>
                                    </Table.Td>
                                    <Table.Td>
                                        <span className="text-sm text-slate-600 dark:text-slate-400">
                                            {plan.duration_days > 0
                                                ? `${plan.duration_days} hari`
                                                : "-"}
                                        </span>
                                    </Table.Td>
                                    <Table.Td>
                                        <span className="text-sm text-slate-600 dark:text-slate-400">
                                            {plan.session_quota > 0
                                                ? `${plan.session_quota} sesi`
                                                : "-"}
                                        </span>
                                    </Table.Td>
                                    <Table.Td>
                                        <span className="text-sm text-slate-600 dark:text-slate-400">
                                            {plan.equipment_provided
                                                ? "Ya"
                                                : "Tidak"}
                                        </span>
                                    </Table.Td>
                                    <Table.Td>
                                        {plan.is_active ? (
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400">
                                                Aktif
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400">
                                                Nonaktif
                                            </span>
                                        )}
                                    </Table.Td>
                                    <Table.Td>
                                        <div className="flex gap-2">
                                            <Button
                                                type={"edit"}
                                                icon={
                                                    <IconPencilCog
                                                        size={16}
                                                        strokeWidth={1.5}
                                                    />
                                                }
                                                className={
                                                    "border bg-warning-100 border-warning-200 text-warning-600 hover:bg-warning-200 dark:bg-warning-900/50 dark:border-warning-800 dark:text-warning-400"
                                                }
                                                href={route(
                                                    "membership-plans.edit",
                                                    plan.id
                                                )}
                                            />
                                            <Button
                                                type={"delete"}
                                                icon={
                                                    <IconTrash
                                                        size={16}
                                                        strokeWidth={1.5}
                                                    />
                                                }
                                                className={
                                                    "border bg-danger-100 border-danger-200 text-danger-600 hover:bg-danger-200 dark:bg-danger-900/50 dark:border-danger-800 dark:text-danger-400"
                                                }
                                                url={route(
                                                    "membership-plans.destroy",
                                                    plan.id
                                                )}
                                            />
                                        </div>
                                    </Table.Td>
                                </tr>
                            ))}
                        </Table.Tbody>
                    </Table>
                </Table.Card>
            ) : (
                /* Empty State */
                <div className="flex flex-col items-center justify-center py-16 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <div className="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                        <IconDatabaseOff
                            size={32}
                            className="text-slate-400"
                            strokeWidth={1.5}
                        />
                    </div>
                    <h3 className="text-lg font-medium text-slate-800 dark:text-slate-200 mb-1">
                        Belum Ada Paket Membership
                    </h3>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Tambahkan paket membership pertama Anda.
                    </p>
                    <Button
                        type={"link"}
                        icon={<IconCirclePlus size={18} />}
                        className={
                            "bg-primary-500 hover:bg-primary-600 text-white"
                        }
                        label={"Tambah Paket"}
                        href={route("membership-plans.create")}
                    />
                </div>
            )}

            {membershipPlans.last_page !== 1 && (
                <Pagination links={membershipPlans.links} />
            )}
        </>
    );
}

Index.layout = (page) => <DashboardLayout children={page} />;
