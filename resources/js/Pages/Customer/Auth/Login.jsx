import { useEffect, useState } from "react";
import { Head, useForm } from "@inertiajs/react";
import {
    IconShoppingCart,
    IconPhone,
    IconLock,
    IconEye,
    IconEyeOff,
    IconLoader2,
} from "@tabler/icons-react";

export default function CustomerLogin({ status }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        no_telp: "",
        password: "",
        remember: false,
    });
    const [showPassword, setShowPassword] = useState(false);

    useEffect(() => {
        return () => reset("password");
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route("customer.login"));
    };

    return (
        <>
            <Head title="Login Pelanggan" />

            <div className="min-h-screen flex bg-slate-50 dark:bg-slate-950">
                {/* Left - Form */}
                <div className="flex-1 flex items-center justify-center p-8">
                    <div className="w-full max-w-md">
                        {/* Logo */}
                        <div className="mb-8">
                            <div className="inline-flex items-center gap-3 mb-6">
                                <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                                    <IconShoppingCart size={24} className="text-white" />
                                </div>
                                <span className="text-2xl font-bold text-slate-900 dark:text-white">
                                    Portal Pelanggan
                                </span>
                            </div>
                            <h1 className="text-3xl font-bold text-slate-900 dark:text-white">
                                Selamat Datang
                            </h1>
                            <p className="mt-2 text-slate-600 dark:text-slate-400">
                                Masuk untuk melihat riwayat transaksi Anda
                            </p>
                        </div>

                        {/* Status Message */}
                        {status && (
                            <div className="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 text-sm">
                                {status}
                            </div>
                        )}

                        {/* Form */}
                        <form onSubmit={submit} className="space-y-5">
                            {/* Phone */}
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    No. Telepon
                                </label>
                                <div className="relative">
                                    <div className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                        <IconPhone size={20} />
                                    </div>
                                    <input
                                        type="text"
                                        inputMode="numeric"
                                        value={data.no_telp}
                                        onChange={(e) => setData("no_telp", e.target.value)}
                                        placeholder="08xxxxxxxxxx"
                                        className={`w-full h-12 pl-12 pr-4 rounded-xl border-2 ${
                                            errors.no_telp
                                                ? "border-red-500 focus:border-red-500"
                                                : "border-slate-200 dark:border-slate-700 focus:border-emerald-500"
                                        } bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-4 focus:ring-emerald-500/20 transition-all`}
                                    />
                                </div>
                                {errors.no_telp && (
                                    <p className="mt-1.5 text-sm text-red-500">{errors.no_telp}</p>
                                )}
                            </div>

                            {/* Password */}
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Password
                                </label>
                                <div className="relative">
                                    <div className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                        <IconLock size={20} />
                                    </div>
                                    <input
                                        type={showPassword ? "text" : "password"}
                                        value={data.password}
                                        onChange={(e) => setData("password", e.target.value)}
                                        placeholder="••••••••"
                                        className={`w-full h-12 pl-12 pr-12 rounded-xl border-2 ${
                                            errors.password
                                                ? "border-red-500 focus:border-red-500"
                                                : "border-slate-200 dark:border-slate-700 focus:border-emerald-500"
                                        } bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-4 focus:ring-emerald-500/20 transition-all`}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    >
                                        {showPassword ? <IconEyeOff size={20} /> : <IconEye size={20} />}
                                    </button>
                                </div>
                                {errors.password && (
                                    <p className="mt-1.5 text-sm text-red-500">{errors.password}</p>
                                )}
                            </div>

                            {/* Remember */}
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData("remember", e.target.checked)}
                                    className="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-emerald-500 focus:ring-emerald-500"
                                />
                                <span className="text-sm text-slate-600 dark:text-slate-400">
                                    Ingat saya
                                </span>
                            </label>

                            {/* Submit */}
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full h-12 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-semibold hover:from-emerald-600 hover:to-teal-700 focus:ring-4 focus:ring-emerald-500/30 disabled:opacity-50 transition-all flex items-center justify-center gap-2"
                            >
                                {processing ? (
                                    <>
                                        <IconLoader2 size={20} className="animate-spin" />
                                        Memproses...
                                    </>
                                ) : (
                                    "Masuk"
                                )}
                            </button>

                            <p className="text-center text-sm text-slate-500 dark:text-slate-400">
                                Hubungi toko untuk mendapatkan akses portal
                            </p>
                        </form>
                    </div>
                </div>

                {/* Right - Decoration */}
                <div className="hidden lg:flex flex-1 bg-gradient-to-br from-emerald-500 to-teal-700 items-center justify-center p-12">
                    <div className="max-w-md text-center text-white">
                        <div className="w-24 h-24 rounded-2xl bg-white/20 flex items-center justify-center mx-auto mb-8">
                            <IconShoppingCart size={48} />
                        </div>
                        <h2 className="text-3xl font-bold mb-4">Portal Pelanggan</h2>
                        <p className="text-lg opacity-90">
                            Lihat riwayat transaksi, cek status pembayaran, dan kelola profil Anda dengan mudah.
                        </p>
                        <div className="mt-8 flex flex-wrap justify-center gap-3">
                            {["Riwayat Transaksi", "Struk Digital", "Profil Saya"].map((feature, i) => (
                                <span
                                    key={i}
                                    className="px-4 py-2 bg-white/20 rounded-full text-sm font-medium"
                                >
                                    {feature}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
