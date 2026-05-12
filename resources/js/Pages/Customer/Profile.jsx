import React from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import CustomerLayout from "@/Layouts/CustomerLayout";
import toast from "react-hot-toast";
import {
    IconUser,
    IconPhone,
    IconMapPin,
    IconLock,
    IconLoader2,
    IconCheck,
} from "@tabler/icons-react";

const Profile = ({ customer }) => {
    const { data, setData, patch, processing, errors, reset } = useForm({
        name: customer?.name || "",
        address: customer?.address || "",
        current_password: "",
        new_password: "",
        new_password_confirmation: "",
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route("customer.profile.update"), {
            onSuccess: () => {
                toast.success("Profil berhasil diperbarui!");
                reset("current_password", "new_password", "new_password_confirmation");
            },
        });
    };

    return (
        <>
            <Head title="Profil Saya" />

            <div className="max-w-2xl mx-auto space-y-6">
                <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <IconUser size={28} className="text-emerald-500" />
                    Profil Saya
                </h1>

                <form onSubmit={submit} className="space-y-6">
                    {/* Info */}
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-5">
                        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Informasi Akun</h2>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nama</label>
                            <div className="relative">
                                <div className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><IconUser size={18} /></div>
                                <input type="text" value={data.name} onChange={(e) => setData("name", e.target.value)}
                                    className={`w-full h-11 pl-11 pr-4 rounded-xl border ${errors.name ? "border-red-500" : "border-slate-200 dark:border-slate-700"} bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all`} />
                            </div>
                            {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">No. Telepon</label>
                            <div className="relative">
                                <div className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"><IconPhone size={18} /></div>
                                <input type="text" value={customer?.no_telp || ""} disabled
                                    className="w-full h-11 pl-11 pr-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 cursor-not-allowed" />
                            </div>
                            <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">Hubungi toko untuk mengubah nomor telepon.</p>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Alamat</label>
                            <div className="relative">
                                <div className="absolute left-4 top-3 text-slate-400"><IconMapPin size={18} /></div>
                                <textarea value={data.address} onChange={(e) => setData("address", e.target.value)} rows={3}
                                    className={`w-full pl-11 pr-4 py-3 rounded-xl border ${errors.address ? "border-red-500" : "border-slate-200 dark:border-slate-700"} bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all resize-none`} />
                            </div>
                            {errors.address && <p className="mt-1 text-sm text-red-500">{errors.address}</p>}
                        </div>
                    </div>

                    {/* Password */}
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-5">
                        <h2 className="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                            <IconLock size={20} /> Ubah Password
                        </h2>
                        <p className="text-sm text-slate-500 dark:text-slate-400">Kosongkan jika tidak ingin mengubah password.</p>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Password Saat Ini</label>
                            <input type="password" value={data.current_password} onChange={(e) => setData("current_password", e.target.value)}
                                className={`w-full h-11 px-4 rounded-xl border ${errors.current_password ? "border-red-500" : "border-slate-200 dark:border-slate-700"} bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all`} />
                            {errors.current_password && <p className="mt-1 text-sm text-red-500">{errors.current_password}</p>}
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Password Baru</label>
                                <input type="password" value={data.new_password} onChange={(e) => setData("new_password", e.target.value)}
                                    className={`w-full h-11 px-4 rounded-xl border ${errors.new_password ? "border-red-500" : "border-slate-200 dark:border-slate-700"} bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all`} />
                                {errors.new_password && <p className="mt-1 text-sm text-red-500">{errors.new_password}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Konfirmasi Password</label>
                                <input type="password" value={data.new_password_confirmation} onChange={(e) => setData("new_password_confirmation", e.target.value)}
                                    className="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                            </div>
                        </div>
                    </div>

                    {/* Submit */}
                    <button type="submit" disabled={processing}
                        className="w-full h-12 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-semibold hover:from-emerald-600 hover:to-teal-700 focus:ring-4 focus:ring-emerald-500/30 disabled:opacity-50 transition-all flex items-center justify-center gap-2">
                        {processing ? (
                            <><IconLoader2 size={20} className="animate-spin" /> Menyimpan...</>
                        ) : (
                            <><IconCheck size={20} /> Simpan Perubahan</>
                        )}
                    </button>
                </form>
            </div>
        </>
    );
};

Profile.layout = (page) => <CustomerLayout children={page} />;
export default Profile;
