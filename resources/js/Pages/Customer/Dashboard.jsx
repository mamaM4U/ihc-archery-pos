import React from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import CustomerLayout from "@/Layouts/CustomerLayout";
import {
    IconReceipt,
    IconCash,
    IconArrowRight,
    IconCalendar,
    IconCheck,
} from "@tabler/icons-react";

const formatCurrency = (value = 0) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);

const Dashboard = ({ recentTransactions, stats }) => {
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
