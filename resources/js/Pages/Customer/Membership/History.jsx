import React from "react";
import { Head, Link } from "@inertiajs/react";
import CustomerLayout from "@/Layouts/CustomerLayout";
import {
    IconArrowLeft,
    IconCalendar,
    IconHistory,
    IconTarget,
} from "@tabler/icons-react";

/**
 * Format a date string to Indonesian locale.
 */
const formatDate = (dateStr) => {
    if (!dateStr) return "-";
    const date = new Date(dateStr);
    return date.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });
};

const statusLabels = {
    active: "Aktif",
    expired: "Berakhir",
    pending: "Menunggu Pembayaran",
};

const statusColors = {
    active: "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300",
    expired: "bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300",
    pending: "bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300",
};

const History = ({ memberships }) => {
    return (
        <>
            <Head title="Riwayat Membership" />

            <div className="space-y-8">
                {/* Page Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                            Riwayat Membership
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Daftar semua membership Anda, dari yang terbaru
                        </p>
                    </div>
                    <Link
                        href={route("customer.membership")}
                        className="hidden sm:flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 transition-all"
                    >
                        <IconArrowLeft size={18} />
                        Kembali
                    </Link>
                </div>

                {/* Membership List */}
                {memberships?.length > 0 ? (
                    <div className="space-y-4">
                        {memberships.map((membership) => {
                            const remainingSessions = Math.max(
                                0,
                                membership.session_quota - membership.session_used
                            );

                            return (
                                <article
                                    key={membership.id}
                                    className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden"
                                    aria-label={`Membership ${membership.membership_plan?.name || "Unknown"} - ${statusLabels[membership.status] || membership.status}`}
                                >
                                    {/* Card Header */}
                                    <div className="flex items-start justify-between p-6 pb-0">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center shrink-0">
                                                <IconTarget
                                                    size={20}
                                                    className="text-emerald-600 dark:text-emerald-400"
                                                />
                                            </div>
                                            <div>
                                                <h3 className="text-base font-semibold text-slate-900 dark:text-white">
                                                    {membership.membership_plan?.name || "Membership"}
                                                </h3>
                                                {membership.membership_plan?.category && (
                                                    <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                        {membership.membership_plan.category
                                                            .replace(/_/g, " ")
                                                            .replace(/\b\w/g, (c) => c.toUpperCase())}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                        <span
                                            className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${statusColors[membership.status] || statusColors.expired}`}
                                            role="status"
                                            aria-label={`Status: ${statusLabels[membership.status] || membership.status}`}
                                        >
                                            {statusLabels[membership.status] || membership.status}
                                        </span>
                                    </div>

                                    {/* Card Details */}
                                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 p-6">
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                                                <IconCalendar size={14} />
                                                <p className="text-xs font-medium">Mulai</p>
                                            </div>
                                            <p className="text-sm font-semibold text-slate-900 dark:text-white">
                                                {formatDate(membership.start_date)}
                                            </p>
                                        </div>
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                                                <IconCalendar size={14} />
                                                <p className="text-xs font-medium">Berakhir</p>
                                            </div>
                                            <p className="text-sm font-semibold text-slate-900 dark:text-white">
                                                {formatDate(membership.end_date)}
                                            </p>
                                        </div>
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                                                <IconTarget size={14} />
                                                <p className="text-xs font-medium">Kuota Sesi</p>
                                            </div>
                                            <p className="text-sm font-semibold text-slate-900 dark:text-white">
                                                {membership.session_quota}
                                            </p>
                                        </div>
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                                                <IconTarget size={14} />
                                                <p className="text-xs font-medium">Sesi Digunakan</p>
                                            </div>
                                            <p className="text-sm font-semibold text-slate-900 dark:text-white">
                                                {membership.session_used}
                                                <span className="text-xs font-normal text-slate-400 dark:text-slate-500">
                                                    {" "}/ {membership.session_quota}
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    {/* Session Usage Progress Bar */}
                                    {membership.session_quota > 0 && (
                                        <div className="px-6 pb-6">
                                            <div
                                                className="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden"
                                                role="progressbar"
                                                aria-valuenow={membership.session_used}
                                                aria-valuemin={0}
                                                aria-valuemax={membership.session_quota}
                                                aria-label={`${membership.session_used} dari ${membership.session_quota} sesi digunakan`}
                                            >
                                                <div
                                                    className={`h-full rounded-full transition-all ${
                                                        membership.status === "active"
                                                            ? "bg-emerald-500"
                                                            : "bg-slate-400 dark:bg-slate-600"
                                                    }`}
                                                    style={{
                                                        width: `${Math.min(100, Math.round((membership.session_used / membership.session_quota) * 100))}%`,
                                                    }}
                                                />
                                            </div>
                                            <p className="text-xs text-slate-500 dark:text-slate-400 mt-1.5">
                                                {membership.status === "active"
                                                    ? `${remainingSessions} sesi tersisa`
                                                    : `${membership.session_used} dari ${membership.session_quota} sesi terpakai`}
                                            </p>
                                        </div>
                                    )}
                                </article>
                            );
                        })}
                    </div>
                ) : (
                    /* Empty State */
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center">
                        <div className="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4">
                            <IconHistory size={32} className="text-slate-400 dark:text-slate-500" />
                        </div>
                        <h3 className="text-lg font-bold text-slate-900 dark:text-white mb-2">
                            Belum Ada Riwayat Membership
                        </h3>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
                            Anda belum pernah memiliki membership. Mulai berlatih
                            panahan dengan memilih paket membership.
                        </p>
                        <Link
                            href={route("customer.membership.plans")}
                            className="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl transition-colors"
                        >
                            <IconTarget size={16} />
                            Lihat Paket Membership
                        </Link>
                    </div>
                )}

                {/* Mobile Back Link */}
                <div className="sm:hidden">
                    <Link
                        href={route("customer.membership")}
                        className="flex items-center justify-center gap-2 w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                    >
                        <IconArrowLeft size={18} />
                        Kembali ke Membership
                    </Link>
                </div>
            </div>
        </>
    );
};

History.layout = (page) => <CustomerLayout children={page} />;

export default History;
