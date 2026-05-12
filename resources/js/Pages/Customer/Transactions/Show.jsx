import React from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import CustomerLayout from "@/Layouts/CustomerLayout";
import { IconArrowLeft, IconCalendar, IconCheck, IconReceipt } from "@tabler/icons-react";

const fmt = (v = 0) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(v);

const Show = ({ transaction }) => {
    const { storeProfile } = usePage().props;
    const details = transaction?.details ?? [];

    return (
        <>
            <Head title={`Transaksi ${transaction.invoice}`} />
            <div className="space-y-6">
                <Link href={route("customer.transactions.index")} className="inline-flex items-center gap-2 text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700">
                    <IconArrowLeft size={18} /> Kembali
                </Link>

                {/* Header Card */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                    <div className="flex flex-col sm:flex-row justify-between gap-4">
                        <div>
                            <p className="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Invoice</p>
                            <h1 className="text-xl font-bold text-slate-900 dark:text-white">{transaction.invoice}</h1>
                            <p className="flex items-center gap-1 mt-1 text-sm text-slate-500 dark:text-slate-400">
                                <IconCalendar size={14} /> {transaction.created_at}
                            </p>
                        </div>
                        <div className="text-right">
                            <p className="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Total</p>
                            <p className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{fmt(transaction.grand_total)}</p>
                            {transaction.payment_status === "paid" ? (
                                <span className="inline-flex items-center gap-1 mt-1 px-2 py-0.5 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full">
                                    <IconCheck size={12} /> Lunas
                                </span>
                            ) : (
                                <span className="inline-flex items-center mt-1 px-2 py-0.5 text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full">
                                    {transaction.payment_status === "pending" ? "Pending" : "Piutang"}
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                {/* Items */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <div className="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h2 className="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <IconReceipt size={20} className="text-emerald-500" /> Detail Item
                        </h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-slate-100 dark:border-slate-800">
                                    <th className="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Produk</th>
                                    <th className="px-6 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Qty</th>
                                    <th className="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Harga</th>
                                    <th className="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {details.map((d, i) => (
                                    <tr key={i} className="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <td className="px-6 py-4">
                                            <p className="text-sm font-medium text-slate-900 dark:text-white">{d.product?.title || "-"}</p>
                                            {d.product?.sku && <p className="text-xs text-slate-500 dark:text-slate-400">{d.product.sku}</p>}
                                        </td>
                                        <td className="px-6 py-4 text-center text-sm text-slate-700 dark:text-slate-300">{d.qty}</td>
                                        <td className="px-6 py-4 text-right text-sm text-slate-700 dark:text-slate-300">{fmt(d.price)}</td>
                                        <td className="px-6 py-4 text-right text-sm font-semibold text-slate-900 dark:text-white">{fmt(d.qty * d.price)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {/* Summary */}
                    <div className="border-t border-slate-200 dark:border-slate-700 px-6 py-4 space-y-2">
                        {transaction.discount > 0 && (
                            <div className="flex justify-between text-sm"><span className="text-slate-500 dark:text-slate-400">Diskon</span><span className="text-red-500">-{fmt(transaction.discount)}</span></div>
                        )}
                        {transaction.shipping_cost > 0 && (
                            <div className="flex justify-between text-sm"><span className="text-slate-500 dark:text-slate-400">Ongkos Kirim</span><span className="text-slate-900 dark:text-white">{fmt(transaction.shipping_cost)}</span></div>
                        )}
                        <div className="flex justify-between text-base font-bold pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span className="text-slate-900 dark:text-white">Total</span>
                            <span className="text-emerald-600 dark:text-emerald-400">{fmt(transaction.grand_total)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-slate-500 dark:text-slate-400">Metode Bayar</span>
                            <span className="text-slate-900 dark:text-white capitalize">{transaction.payment_method?.replace("_", " ") || "-"}</span>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

Show.layout = (page) => <CustomerLayout children={page} />;
export default Show;
