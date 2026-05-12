import React, { useState, useEffect } from "react";
import { Head, Link, router } from "@inertiajs/react";
import CustomerLayout from "@/Layouts/CustomerLayout";
import Pagination from "@/Components/Dashboard/Pagination";
import {
    IconSearch,
    IconReceipt,
    IconFilter,
    IconX,
    IconCheck,
    IconCalendar,
    IconDatabaseOff,
} from "@tabler/icons-react";

const fmt = (v = 0) =>
    new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(v);

const defaultFilters = { invoice: "", start_date: "", end_date: "" };

const Index = ({ transactions, filters }) => {
    const [filterData, setFilterData] = useState({ ...defaultFilters, ...filters });
    const [showFilters, setShowFilters] = useState(false);

    useEffect(() => { setFilterData({ ...defaultFilters, ...filters }); }, [filters]);

    const applyFilters = (e) => {
        e.preventDefault();
        router.get(route("customer.transactions.index"), filterData, { preserveScroll: true, preserveState: true });
        setShowFilters(false);
    };

    const resetFilters = () => {
        setFilterData(defaultFilters);
        router.get(route("customer.transactions.index"), defaultFilters, { preserveScroll: true, preserveState: true, replace: true });
    };

    const rows = transactions?.data ?? [];
    const links = transactions?.links ?? [];
    const hasActiveFilters = filterData.invoice || filterData.start_date || filterData.end_date;

    return (
        <>
            <Head title="Transaksi Saya" />
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <IconReceipt size={28} className="text-emerald-500" />
                            Transaksi Saya
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            {transactions?.total || 0} transaksi tercatat
                        </p>
                    </div>
                    <button
                        onClick={() => setShowFilters(!showFilters)}
                        className={`inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm font-medium transition-colors ${
                            showFilters || hasActiveFilters
                                ? "bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950/50 dark:border-emerald-800 dark:text-emerald-400"
                                : "bg-white border-slate-200 text-slate-700 hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-300"
                        }`}
                    >
                        <IconFilter size={18} />
                        Filter
                        {hasActiveFilters && <span className="w-2 h-2 rounded-full bg-emerald-500" />}
                    </button>
                </div>

                {/* Filters */}
                {showFilters && (
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
                        <form onSubmit={applyFilters}>
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">No. Invoice</label>
                                    <input type="text" placeholder="TRX-..." value={filterData.invoice}
                                        onChange={(e) => setFilterData((p) => ({ ...p, invoice: e.target.value }))}
                                        className="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Dari Tanggal</label>
                                    <input type="date" value={filterData.start_date}
                                        onChange={(e) => setFilterData((p) => ({ ...p, start_date: e.target.value }))}
                                        className="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Sampai Tanggal</label>
                                    <input type="date" value={filterData.end_date}
                                        onChange={(e) => setFilterData((p) => ({ ...p, end_date: e.target.value }))}
                                        className="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                                </div>
                                <div className="flex items-end gap-2">
                                    <button type="submit" className="flex-1 h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-medium transition-colors">
                                        <IconSearch size={18} /> Cari
                                    </button>
                                    {hasActiveFilters && (
                                        <button type="button" onClick={resetFilters} className="h-11 px-4 inline-flex items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                            <IconX size={18} />
                                        </button>
                                    )}
                                </div>
                            </div>
                        </form>
                    </div>
                )}

                {/* List */}
                {rows.length > 0 ? (
                    <div className="space-y-3">
                        {rows.map((tx) => (
                            <Link key={tx.id} href={route("customer.transactions.show", tx.invoice)}
                                className="block bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 hover:border-emerald-300 dark:hover:border-emerald-700 hover:shadow-lg hover:shadow-emerald-500/5 transition-all">
                                <div className="flex items-center justify-between">
                                    <div className="space-y-1">
                                        <p className="text-base font-bold text-slate-900 dark:text-white">{tx.invoice}</p>
                                        <div className="flex items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
                                            <span className="flex items-center gap-1"><IconCalendar size={14} />{tx.created_at}</span>
                                            <span>{tx.details_count || 0} item</span>
                                        </div>
                                    </div>
                                    <div className="text-right space-y-1">
                                        <p className="text-lg font-bold text-slate-900 dark:text-white">{fmt(tx.grand_total)}</p>
                                        {tx.payment_status === "paid" ? (
                                            <span className="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full">
                                                <IconCheck size={12} /> Lunas
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full">
                                                {tx.payment_status === "pending" ? "Pending" : "Piutang"}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <div className="flex flex-col items-center justify-center py-16 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <div className="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                            <IconDatabaseOff size={32} className="text-slate-400" strokeWidth={1.5} />
                        </div>
                        <h3 className="text-lg font-medium text-slate-800 dark:text-slate-200 mb-1">Belum Ada Transaksi</h3>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            {hasActiveFilters ? "Tidak ada transaksi sesuai filter." : "Transaksi akan muncul di sini."}
                        </p>
                    </div>
                )}

                {links.length > 3 && <Pagination links={links} />}
            </div>
        </>
    );
};

Index.layout = (page) => <CustomerLayout children={page} />;
export default Index;
