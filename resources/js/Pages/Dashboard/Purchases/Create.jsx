import React from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, Link, useForm } from "@inertiajs/react";
import Button from "@/Components/Dashboard/Button";
import { IconArrowLeft, IconDeviceFloppy } from "@tabler/icons-react";
import toast from "react-hot-toast";

export default function Create({ suppliers }) {
    const { data, setData, post, processing, errors } = useForm({
        supplier_id: "",
        notes: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("purchases.store"), {
            onSuccess: () => {
                toast.success("Draft pembelian berhasil dibuat");
            },
        });
    };

    return (
        <>
            <Head title="Buat Pembelian Baru" />

            <div className="mx-auto max-w-3xl">
                <div className="mb-6">
                    <Link
                        href={route("purchases.index")}
                        className="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-primary-600"
                    >
                        <IconArrowLeft size={16} />
                        Kembali ke daftar pembelian
                    </Link>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                        Buat Pembelian Baru
                    </h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Pilih supplier untuk memulai sesi pembelian barang.
                    </p>
                </div>

                <form
                    onSubmit={submit}
                    className="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div className="mb-5">
                        <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Supplier <span className="text-red-500">*</span>
                        </label>
                        <select
                            value={data.supplier_id}
                            onChange={(e) => setData("supplier_id", e.target.value)}
                            required
                            className={`w-full rounded-xl border bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:bg-slate-800 dark:text-slate-200 ${
                                errors.supplier_id
                                    ? "border-red-500"
                                    : "border-slate-200 dark:border-slate-700"
                            }`}
                        >
                            <option value="" disabled>
                                -- Pilih Supplier --
                            </option>
                            {suppliers.map((supplier) => (
                                <option key={supplier.id} value={supplier.id}>
                                    {supplier.name}
                                </option>
                            ))}
                        </select>
                        {errors.supplier_id && (
                            <p className="mt-1 text-xs text-red-500">
                                {errors.supplier_id}
                            </p>
                        )}
                        {suppliers.length === 0 && (
                            <p className="mt-2 text-xs text-warning-600 dark:text-warning-400">
                                Data supplier masih kosong. Silakan tambahkan supplier terlebih dahulu di menu Master Data.
                            </p>
                        )}
                    </div>

                    <div className="mb-6">
                        <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Catatan Tambahan (Opsional)
                        </label>
                        <textarea
                            value={data.notes}
                            onChange={(e) => setData("notes", e.target.value)}
                            rows={3}
                            placeholder="Contoh: Pembelian rutin bulanan..."
                            className={`w-full rounded-xl border bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:bg-slate-800 dark:text-slate-200 ${
                                errors.notes
                                    ? "border-red-500"
                                    : "border-slate-200 dark:border-slate-700"
                            }`}
                        />
                        {errors.notes && (
                            <p className="mt-1 text-xs text-red-500">{errors.notes}</p>
                        )}
                    </div>

                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-5 dark:border-slate-800">
                        <Link
                            href={route("purchases.index")}
                            className="inline-flex h-11 items-center justify-center rounded-xl px-5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            Batal
                        </Link>
                        <Button
                            type="submit"
                            icon={<IconDeviceFloppy size={18} strokeWidth={1.5} />}
                            className="bg-primary-500 hover:bg-primary-600 text-white"
                            label="Buat Sesi"
                            disabled={processing || suppliers.length === 0}
                        />
                    </div>
                </form>
            </div>
        </>
    );
}

Create.layout = (page) => <DashboardLayout children={page} />;
