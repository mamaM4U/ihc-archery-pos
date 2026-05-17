import React from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, router } from "@inertiajs/react";
import { IconDatabaseOff } from "@tabler/icons-react";
import Table from "@/Components/Dashboard/Table";

/**
 * Format a timestamp to HH:mm.
 */
function formatTime(dateTimeString) {
    if (!dateTimeString) return "-";
    return new Date(dateTimeString).toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    });
}

/**
 * Format a date string to Indonesian locale for display.
 */
function formatDateDisplay(dateString) {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    });
}

export default function DailyLog({ sessionUsages, selectedDate, totalCount }) {
    /**
     * Handle date change by navigating with updated query param.
     */
    function handleDateChange(e) {
        router.get(
            route("memberships.daily-log"),
            { date: e.target.value },
            { preserveState: true, preserveScroll: true }
        );
    }

    return (
        <>
            <Head title="Log Harian Check-in" />

            {/* Header */}
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                    Log Harian Check-in
                </h1>
                <p className="text-sm text-slate-500 dark:text-slate-400">
                    Riwayat check-in sesi latihan per hari
                </p>
            </div>

            {/* Toolbar: Date Picker & Summary */}
            <div className="mb-4 flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
                {/* Date Picker */}
                <div className="w-full sm:w-auto">
                    <input
                        type="date"
                        value={selectedDate}
                        onChange={handleDateChange}
                        className="py-2 px-4 block w-full sm:w-auto rounded-lg text-sm border focus:outline-none focus:ring-0 focus:ring-gray-400 text-gray-700 bg-white border-gray-200 focus:border-gray-200 dark:focus:ring-gray-500 dark:focus:border-gray-800 dark:text-gray-200 dark:bg-gray-950 dark:border-gray-900"
                    />
                </div>

                {/* Summary */}
                <div className="flex items-center gap-2">
                    <span className="text-sm text-slate-600 dark:text-slate-400">
                        {formatDateDisplay(selectedDate)}
                    </span>
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400">
                        {totalCount} check-in
                    </span>
                </div>
            </div>

            {/* Content */}
            {sessionUsages.length > 0 ? (
                <Table.Card title="Data Check-in">
                    <Table>
                        <Table.Thead>
                            <tr>
                                <Table.Th className="w-10">No</Table.Th>
                                <Table.Th>Customer</Table.Th>
                                <Table.Th>Paket</Table.Th>
                                <Table.Th>Jam Check-in</Table.Th>
                                <Table.Th>Catatan</Table.Th>
                            </tr>
                        </Table.Thead>
                        <Table.Tbody>
                            {sessionUsages.map((usage, i) => (
                                <tr
                                    className="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                    key={usage.id}
                                >
                                    <Table.Td className="text-center">
                                        {i + 1}
                                    </Table.Td>
                                    <Table.Td>
                                        <p className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                            {usage.customer?.name || "-"}
                                        </p>
                                    </Table.Td>
                                    <Table.Td>
                                        <span className="text-sm text-slate-600 dark:text-slate-400">
                                            {usage.customer_membership?.membership_plan?.name || "-"}
                                        </span>
                                    </Table.Td>
                                    <Table.Td>
                                        <span className="text-sm text-slate-600 dark:text-slate-400">
                                            {formatTime(usage.checked_in_at)}
                                        </span>
                                    </Table.Td>
                                    <Table.Td>
                                        <span className="text-sm text-slate-500 dark:text-slate-400">
                                            {usage.notes || "-"}
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
                        Belum Ada Check-in
                    </h3>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Tidak ada sesi check-in pada tanggal ini.
                    </p>
                </div>
            )}
        </>
    );
}

DailyLog.layout = (page) => <DashboardLayout children={page} />;
