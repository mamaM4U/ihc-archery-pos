import React from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import CustomerLayout from "@/Layouts/CustomerLayout";
import {
    IconReceipt,
    IconCash,
    IconArrowRight,
    IconCalendar,
    IconCheck,
    IconTarget,
    IconClock,
    IconAlertTriangle,
} from "@tabler/icons-react";

const formatCurrency = (value = 0) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);

const Dashboard = ({ recentTransactions, stats, membershipSummary }) => {
    const { customerAuth } = usePage().props;

    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-8">
                {/* Welcome */}
                <div className="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl p-8 text-white">
                    <h1 className="text-2xl font-bold mb-2">
                        Halo, {customerAuth?.name || "Pelanggan"}! 👋
                    </h1>
                    <p className="text-emerald-100">
                        Selamat datang di portal pelanggan. Lihat riwayat transaksi dan kelola profil Anda.
                    </p>
                </div>

                {/* Membership Summary Widget */}
                {membershipSummary ? (
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                        {/* Expiring Soon Warning */}
                        {membershipSummary.is_expiring_soon && (
                            <div className="flex items-center gap-2 px-6 py-3 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800">
                                <IconAlertTriangle size={16} className="text-amber-600 dark:text-amber-400 shrink-0" />
                                <p className="text-sm text-amber-700 dark:text-amber-300">
                                    Membership akan berakhir dalam <strong>{membershipSummary.remaining_days} hari</strong>
                                </p>
                            </div>
                        )}
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                                        <IconTarget size={20} className="text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <div>
                                        <p className="text-sm text-slate-500 dark:text-slate-400">Membership Aktif</p>
                                        <p className="text-base font-bold text-slate-900 dark:text-white">
                                            {membershipSummary.plan_name}
                                        </p>
                                    </div>
                                </div>
                                <Link
                                    href={route("customer.membership")}
                                    className="flex items-center gap-1 text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700"
                                >
                                    Detail
                                    <IconArrowRight size={14} />
                                </Link>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="flex items-center gap-2">
                                    <IconTarget size={16} className="text-slate-400 dark:text-slate-500" />
                                    <div>
                                        <p className="text-xs text-slate-500 dark:text-slate-400">Sisa Sesi</p>
                                        <p className="text-sm font-bold text-slate-900 dark:text-white">
                                            {membershipSummary.remaining_sessions}
                                            <span className="text-xs font-normal text-slate-400 dark:text-slate-500">
                                                {" "}/ {membershipSummary.session_quota}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <IconClock size={16} className="text-slate-400 dark:text-slate-500" />
                                    <div>
                                        <p className="text-xs text-slate-500 dark:text-slate-400">Sisa Hari</p>
                                        <p className="text-sm font-bold text-slate-900 dark:text-white">
                                            {membershipSummary.remaining_days}
                                            <span className="text-xs font-normal text-slate-400 dark:text-slate-500">
                                                {" "}hari
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                    <IconTarget size={20} className="text-slate-400 dark:text-slate-500" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-slate-900 dark:text-white">
                                        Belum Ada Membership
                                    </p>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                        Mulai berlatih panahan dengan paket membership kami
                                    </p>
                                </div>
                            </div>
                            <Link
                                href={route("customer.membership.plans")}
                                className="flex items-center gap-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl transition-colors"
                            >
                                Lihat Paket
                                <IconArrowRight size={14} />
                            </Link>
                        </div>
                    </div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                                <IconReceipt size={24} className="text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Total Transaksi</p>
                                <p className="text-2xl font-bold text-slate-900 dark:text-white">
                                    {stats?.total_transactions || 0}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 rounded-xl bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center">
                                <IconCash size={24} className="text-teal-600 dark:text-teal-400" />
                            </div>
                            <div>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Total Belanja</p>
                                <p className="text-2xl font-bold text-slate-900 dark:text-white">
                                    {formatCurrency(stats?.total_spent || 0)}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Recent Transactions */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <div className="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-800">
                        <h2 className="text-lg font-bold text-slate-900 dark:text-white">
                            Transaksi Terbaru
                        </h2>
                        <Link
                            href={route("customer.transactions.index")}
                            className="flex items-center gap-1 text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700"
                        >
                            Lihat Semua
                            <IconArrowRight size={16} />
                        </Link>
                    </div>
                    <div className="divide-y divide-slate-100 dark:divide-slate-800">
                        {recentTransactions?.length > 0 ? (
                            recentTransactions.map((tx) => (
                                <Link
                                    key={tx.id}
                                    href={route("customer.transactions.show", tx.invoice)}
                                    className="flex items-center justify-between px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                >
                                    <div className="space-y-1">
                                        <p className="text-sm font-semibold text-slate-900 dark:text-white">
                                            {tx.invoice}
                                        </p>
                                        <div className="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                            <IconCalendar size={14} />
                                            {tx.created_at}
                                            <span>•</span>
                                            <span>{tx.details_count || 0} item</span>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-bold text-slate-900 dark:text-white">
                                            {formatCurrency(tx.grand_total)}
                                        </p>
                                        <span className="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full">
                                            <IconCheck size={12} />
                                            Selesai
                                        </span>
                                    </div>
                                </Link>
                            ))
                        ) : (
                            <div className="p-12 text-center text-slate-500 dark:text-slate-400">
                                Belum ada transaksi.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
};

Dashboard.layout = (page) => <CustomerLayout children={page} />;

export default Dashboard;
