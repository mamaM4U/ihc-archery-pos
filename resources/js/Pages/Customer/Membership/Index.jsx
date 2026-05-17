import React from "react";
import { Head, Link } from "@inertiajs/react";
import CustomerLayout from "@/Layouts/CustomerLayout";
import {
    IconCalendar,
    IconClock,
    IconTarget,
    IconAlertTriangle,
    IconArrowRight,
    IconUser,
    IconHistory,
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

/**
 * Format a datetime string to Indonesian locale with time.
 */
const formatDateTime = (dateStr) => {
    if (!dateStr) return "-";
    const date = new Date(dateStr);
    return date.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const Index = ({ membership, sessionUsages, isExpiringSoon }) => {
    const remainingSessions = membership
        ? Math.max(0, membership.session_quota - membership.session_used)
        : 0;
    const usagePercentage = membership && membership.session_quota > 0
        ? Math.round((membership.session_used / membership.session_quota) * 100)
        : 0;

    return (
        <>
            <Head title="Membership Saya" />

            <div className="space-y-8">
                {/* Page Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                            Membership Saya
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Kelola membership dan lihat riwayat penggunaan sesi
                        </p>
                    </div>
                    <Link
                        href={route("customer.membership.history")}
                        className="hidden sm:flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 transition-all"
                    >
                        <IconHistory size={18} />
                        Riwayat
                    </Link>
                </div>

                {/* Expiring Soon Warning */}
                {isExpiringSoon && membership && (
                    <div
                        className="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl"
                        role="alert"
                        aria-live="polite"
                    >
                        <IconAlertTriangle
                            size={20}
                            className="text-amber-600 dark:text-amber-400 mt-0.5 shrink-0"
                        />
                        <div>
                            <p className="text-sm font-semibold text-amber-800 dark:text-amber-200">
                                Membership akan segera berakhir
                            </p>
                            <p className="text-sm text-amber-700 dark:text-amber-300 mt-0.5">
                                Membership Anda akan berakhir dalam{" "}
                                <strong>{membership.remaining_days} hari</strong>.
                                Perpanjang sekarang agar tidak terputus.
                            </p>
                            <Link
                                href={route("customer.membership.plans")}
                                className="inline-flex items-center gap-1 mt-2 text-sm font-medium text-amber-800 dark:text-amber-200 hover:text-amber-900 dark:hover:text-amber-100 underline underline-offset-2"
                            >
                                Perpanjang Membership
                                <IconArrowRight size={14} />
                            </Link>
                        </div>
                    </div>
                )}

                {/* Membership Status Card */}
                {membership ? (
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                        {/* Card Header */}
                        <div className="bg-gradient-to-r from-emerald-500 to-teal-600 p-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-emerald-100 text-sm font-medium">
                                        Paket Aktif
                                    </p>
                                    <h2 className="text-xl font-bold mt-1">
                                        {membership.membership_plan?.name || "Membership"}
                                    </h2>
                                </div>
                                <div className="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center">
                                    <IconTarget size={24} />
                                </div>
                            </div>
                        </div>

                        {/* Session Quota Progress */}
                        <div className="p-6 border-b border-slate-100 dark:border-slate-800">
                            <div className="flex items-center justify-between mb-3">
                                <p className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Kuota Sesi
                                </p>
                                <p className="text-sm text-slate-500 dark:text-slate-400">
                                    {membership.session_used} / {membership.session_quota} digunakan
                                </p>
                            </div>
                            {/* Progress Bar */}
                            <div
                                className="w-full h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden"
                                role="progressbar"
                                aria-valuenow={usagePercentage}
                                aria-valuemin={0}
                                aria-valuemax={100}
                                aria-label={`${remainingSessions} dari ${membership.session_quota} sesi tersisa`}
                            >
                                <div
                                    className={`h-full rounded-full transition-all duration-500 ${
                                        usagePercentage >= 90
                                            ? "bg-red-500"
                                            : usagePercentage >= 70
                                              ? "bg-amber-500"
                                              : "bg-emerald-500"
                                    }`}
                                    style={{ width: `${usagePercentage}%` }}
                                />
                            </div>
                            <div className="flex items-center justify-between mt-2">
                                <p className="text-xs text-slate-500 dark:text-slate-400">
                                    {usagePercentage}% terpakai
                                </p>
                                <p className="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                    {remainingSessions} sesi tersisa
                                </p>
                            </div>
                        </div>

                        {/* Membership Details Grid */}
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 p-6">
                            <div className="space-y-1">
                                <div className="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                                    <IconTarget size={14} />
                                    <p className="text-xs font-medium">Sisa Sesi</p>
                                </div>
                                <p className="text-lg font-bold text-slate-900 dark:text-white">
                                    {remainingSessions}
                                    <span className="text-sm font-normal text-slate-400 dark:text-slate-500">
                                        {" "}/ {membership.session_quota}
                                    </span>
                                </p>
                            </div>
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
                                    <IconClock size={14} />
                                    <p className="text-xs font-medium">Sisa Hari</p>
                                </div>
                                <p className="text-lg font-bold text-slate-900 dark:text-white">
                                    {membership.remaining_days}
                                    <span className="text-sm font-normal text-slate-400 dark:text-slate-500">
                                        {" "}hari
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                ) : (
                    /* No Active Membership */
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center">
                        <div className="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4">
                            <IconTarget size={32} className="text-slate-400 dark:text-slate-500" />
                        </div>
                        <h3 className="text-lg font-bold text-slate-900 dark:text-white mb-2">
                            Belum Ada Membership Aktif
                        </h3>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
                            Anda belum memiliki membership aktif. Pilih paket membership
                            untuk mulai berlatih panahan bersama kami.
                        </p>
                        <Link
                            href={route("customer.membership.plans")}
                            className="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl transition-colors"
                        >
                            Lihat Paket Membership
                            <IconArrowRight size={16} />
                        </Link>
                    </div>
                )}

                {/* Session Usage History */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <div className="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-800">
                        <h2 className="text-lg font-bold text-slate-900 dark:text-white">
                            Riwayat Penggunaan Sesi
                        </h2>
                        {sessionUsages?.length > 0 && (
                            <span className="text-xs font-medium px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-full">
                                {sessionUsages.length} sesi
                            </span>
                        )}
                    </div>
                    <div className="divide-y divide-slate-100 dark:divide-slate-800">
                        {sessionUsages?.length > 0 ? (
                            sessionUsages.map((usage) => (
                                <div
                                    key={usage.id}
                                    className="flex items-center justify-between px-6 py-4"
                                >
                                    <div className="flex items-center gap-3">
                                        <div className="w-9 h-9 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center shrink-0">
                                            <IconTarget
                                                size={16}
                                                className="text-emerald-600 dark:text-emerald-400"
                                            />
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-slate-900 dark:text-white">
                                                Sesi Latihan
                                            </p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                {formatDateTime(usage.checked_in_at)}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        {usage.checked_in_by && (
                                            <div className="flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
                                                <IconUser size={12} />
                                                <span>{usage.checked_in_by.name}</span>
                                            </div>
                                        )}
                                        {usage.notes && (
                                            <p className="text-xs text-slate-400 dark:text-slate-500 mt-0.5 max-w-[150px] truncate">
                                                {usage.notes}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="p-12 text-center">
                                <IconHistory
                                    size={32}
                                    className="text-slate-300 dark:text-slate-600 mx-auto mb-3"
                                />
                                <p className="text-sm text-slate-500 dark:text-slate-400">
                                    Belum ada riwayat penggunaan sesi.
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Mobile History Link */}
                <div className="sm:hidden">
                    <Link
                        href={route("customer.membership.history")}
                        className="flex items-center justify-center gap-2 w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                    >
                        <IconHistory size={18} />
                        Lihat Riwayat Membership
                    </Link>
                </div>
            </div>
        </>
    );
};

Index.layout = (page) => <CustomerLayout children={page} />;

export default Index;
