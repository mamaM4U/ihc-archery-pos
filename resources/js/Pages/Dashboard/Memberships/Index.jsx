import React from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, router } from "@inertiajs/react";
import { IconDatabaseOff, IconFilter } from "@tabler/icons-react";
import Table from "@/Components/Dashboard/Table";
import Pagination from "@/Components/Dashboard/Pagination";

/**
 * Format a date string to Indonesian locale.
 */
function formatDate(dateString) {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
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

/**
 * Render a colored status badge.
 */
function StatusBadge({ status }) {
    const styles = {
        active: "bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400",
        expired: "bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400",
        pending: "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-400",
    };

    const labels = {
        active: "Aktif",
        expired: "Expired",
        pending: "Pending",
    };

    return (
        <span
            className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${styles[status] || styles.pending}`}
        >
            {labels[status] || status}
        </span>
    );
}

export default function Index({ memberships, categories, filters }) {
    /**
     * Handle filter changes by navigating with updated query params.
     */
    function handleFilter(key, value) {
        router.get(
            route("memberships.index"),
            {
                ...filters,
                [key]: value,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }

    return (
        <>
            <Head title="Daftar Member" />

            {/* Header */}
            <div className="mb-6">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                            Daftar Member
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            {memberships.total || 0} member terdaftar
                        </p>
                    </div>
                </div>
            </div>

            {/* Toolbar / Filters */}
            <div className="mb-4 flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
                {/* Search */}
                <div className="w-full sm:w-80">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            handleFilter("search", e.target.search.value);
                        }}
                    >
                        <input
                            type="text"
                            name="search"
                            defaultValue={filters.search || ""}
                            className="py-2 px-4 block w-full rounded-lg text-sm border focus:outline-none focus:ring-0 focus:ring-gray-400 text-gray-700 bg-white border-gray-200 focus:border-gray-200 dark:focus:ring-gray-500 dark:focus:border-gray-800 dark:text-gray-200 dark:bg-gray-950 dark:border-gray-900"
                            placeholder="Cari nama customer..."
                        />
                    </form>
                </div>

                {/* Filter Dropdowns */}
                <div className="flex items-center gap-2">
                    <IconFilter
                        size={18}
                        strokeWidth={1.5}
                        className="text-slate-400"
                    />

                    {/* Status Filter */}
                    <select
                        value={filters.status || ""}
                        onChange={(e) => handleFilter("status", e.target.value)}
                        className="py-2 px-3 rounded-lg text-sm border focus:outline-none focus:ring-0 text-gray-700 bg-white border-gray-200 focus:border-gray-200 dark:text-gray-200 dark:bg-gray-950 dark:border-gray-900 dark:focus:border-gray-800"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="expired">Expired</option>
                        <option value="pending">Pending</option>
                    </select>

                    {/* Category Filter */}
                    <select
                        value={filters.category || ""}
                        onChange={(e) =>
                            handleFilter("category", e.target.value)
                        }
                        className="py-2 px-3 rounded-lg text-sm border focus:outline-none focus:ring-0 text-gray-700 bg-white border-gray-200 focus:border-gray-200 dark:text-gray-200 dark:bg-gray-950 dark:border-gray-900 dark:focus:border-gray-800"
                    >
                        <option value="">Semua Kategori</option>
                        {categories.map((category) => (
                            <option key={category} value={category}>
                                {formatCategory(category)}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            {/* Content */}
            {memberships.data.length > 0 ? (
                <Table.Card title={"Data Member"}>
                    <Table>
                        <Table.Thead>
                            <tr>
                                <Table.Th className="w-10">No</Table.Th>
                                <Table.Th>Customer</Table.Th>
                                <Table.Th>Paket</Table.Th>
                                <Table.Th>Status</Table.Th>
                                <Table.Th>Sisa Sesi</Table.Th>
                                <Table.Th>Mulai</Table.Th>
                                <Table.Th>Berakhir</Table.Th>
                            </tr>
                        </Table.Thead>
                        <Table.Tbody>
                            {memberships.data.map((membership, i) => (
                                <tr
                                    className="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                    key={membership.id}
                                >
                                    <Table.Td className="text-center">
                                        {++i +
                                            (memberships.current_page - 1) *
                                                memberships.per_page}
                                    </Table.Td>
                                    <Table.Td>
                                        <p className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                            {membership.customer?.name || "-"}
                                        </p>
                                    </Table.Td>
                                    <Table.Td>
                                        <span className="text-sm text-slate-600 dark:text-slate-400">
                                            {membership.membership_plan?.name ||
                                                "-"}
                                        </span>
                                    </Table.Td>
                                    <Table.Td>
                                        <StatusBadge
                                            status={membership.status}
                                        />
                                    </Table.Td>
                                    <Table.Td>
                                        <span className="text-sm text-slate-600 dark:text-slate-400">
                                            {membership.remaining_sessions !==
                                            undefined
                                                ? `${membership.remaining_sessions} sesi`
                                                : `${Math.max(0, membership.session_quota - membership.session_used)} sesi`}
                                        </span>
                                    </Table.Td>
                                    <Table.Td>
                                        <span className="text-sm text-slate-600 dark:text-slate-400">
                                            {formatDate(membership.start_date)}
                                        </span>
                                    </Table.Td>
                                    <Table.Td>
                                        <span className="text-sm text-slate-600 dark:text-slate-400">
                                            {formatDate(membership.end_date)}
                                        </span>
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
                        Belum Ada Data Member
                    </h3>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Belum ada membership yang terdaftar.
                    </p>
                </div>
            )}

            {memberships.last_page !== 1 && (
                <Pagination links={memberships.links} />
            )}
        </>
    );
}

Index.layout = (page) => <DashboardLayout children={page} />;
