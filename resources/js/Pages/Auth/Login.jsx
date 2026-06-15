import { useEffect } from "react";
import { Head, Link, useForm } from "@inertiajs/react";
import {
    IconTarget,
    IconMail,
    IconLock,
    IconEye,
    IconEyeOff,
    IconLoader2,
    IconSun,
    IconMoon,
} from "@tabler/icons-react";
import { useState } from "react";
import { useTheme } from "../../Context/ThemeSwitcherContext";

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
    });
    const [showPassword, setShowPassword] = useState(false);
    const { darkMode, themeSwitcher } = useTheme();

    useEffect(() => {
        return () => reset("password");
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route("login"));
    };

    return (
        <>
            <Head title="Masuk ke IHC Archery" />

            <div className="min-h-screen flex bg-slate-50 dark:bg-slate-950 font-sans transition-colors duration-300 relative overflow-hidden">
                {/* Theme Toggle Button at top right */}
                <div className="absolute top-6 right-6 z-50">
                    <button
                        onClick={themeSwitcher}
                        className="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all shadow-sm hover:scale-105 active:scale-95"
                        type="button"
                        aria-label="Toggle Theme"
                    >
                        {darkMode ? <IconSun size={20} /> : <IconMoon size={20} />}
                    </button>
                </div>

                {/* Left - Form Section */}
                <div className="flex-1 flex items-center justify-center p-8 lg:p-16 z-10">
                    <div className="w-full max-w-md space-y-8 animate-fade-in">
                        {/* Header Branding */}
                        <div className="space-y-3">
                            <div className="inline-flex items-center gap-3">
                                <div className="w-12 h-12 rounded-2xl bg-gradient-to-tr from-primary-500 to-primary-600 flex items-center justify-center shadow-lg shadow-primary-500/25 transform hover:rotate-12 transition-transform duration-300">
                                    <IconTarget
                                        size={26}
                                        className="text-white"
                                    />
                                </div>
                                <span className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                    IHC Archery
                                </span>
                            </div>
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                                    Selamat Datang
                                </h1>
                                <p className="mt-2 text-slate-600 dark:text-slate-400">
                                    Masuk ke akun Anda untuk mengelola POS dan aktivitas panahan.
                                </p>
                            </div>
                        </div>

                        {/* Status Message */}
                        {status && (
                            <div className="p-4 rounded-2xl bg-success-50 dark:bg-success-950/30 border border-success-200 dark:border-success-900/50 text-success-800 dark:text-success-400 text-sm animate-slide-up">
                                {status}
                            </div>
                        )}

                        {/* Login Form */}
                        <form onSubmit={submit} className="space-y-6">
                            {/* Email */}
                            <div className="space-y-2">
                                <label className="block text-sm font-semibold text-slate-800 dark:text-slate-200">
                                    Email
                                </label>
                                <div className="relative group">
                                    <div className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary-500 transition-colors">
                                        <IconMail size={20} />
                                    </div>
                                    <input
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData("email", e.target.value)
                                        }
                                        placeholder="nama@email.com"
                                        className={`w-full h-12 pl-12 pr-4 rounded-xl border-2 ${
                                            errors.email
                                                ? "border-danger-500 focus:border-danger-500 focus:ring-danger-500/20"
                                                : "border-slate-200 dark:border-slate-800 focus:border-primary-500 focus:ring-primary-500/20"
                                        } bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:ring-4 transition-all`}
                                        required
                                    />
                                </div>
                                {errors.email && (
                                    <p className="text-sm text-danger-600 dark:text-danger-400 mt-1 animate-slide-up">
                                        {errors.email}
                                    </p>
                                )}
                            </div>

                            {/* Password */}
                            <div className="space-y-2">
                                <label className="block text-sm font-semibold text-slate-800 dark:text-slate-200">
                                    Password
                                </label>
                                <div className="relative group">
                                    <div className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary-500 transition-colors">
                                        <IconLock size={20} />
                                    </div>
                                    <input
                                        type={
                                            showPassword ? "text" : "password"
                                        }
                                        value={data.password}
                                        onChange={(e) =>
                                            setData("password", e.target.value)
                                        }
                                        placeholder="••••••••"
                                        className={`w-full h-12 pl-12 pr-12 rounded-xl border-2 ${
                                            errors.password
                                                ? "border-danger-500 focus:border-danger-500 focus:ring-danger-500/20"
                                                : "border-slate-200 dark:border-slate-800 focus:border-primary-500 focus:ring-primary-500/20"
                                        } bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:ring-4 transition-all`}
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setShowPassword(!showPassword)
                                        }
                                        className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors"
                                    >
                                        {showPassword ? (
                                            <IconEyeOff size={20} />
                                        ) : (
                                            <IconEye size={20} />
                                        )}
                                    </button>
                                </div>
                                {errors.password && (
                                    <p className="text-sm text-danger-600 dark:text-danger-400 mt-1 animate-slide-up">
                                        {errors.password}
                                    </p>
                                )}
                            </div>

                            {/* Remember & Lupa Password */}
                            <div className="flex items-center justify-between">
                                <label className="flex items-center gap-2.5 cursor-pointer group select-none">
                                    <input
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(e) =>
                                            setData(
                                                "remember",
                                                e.target.checked
                                            )
                                        }
                                        className="w-4.5 h-4.5 rounded border-slate-300 dark:border-slate-700 text-primary-600 focus:ring-primary-500 bg-white dark:bg-slate-900"
                                    />
                                    <span className="text-sm text-slate-600 dark:text-slate-400 group-hover:text-slate-800 dark:group-hover:text-slate-200 transition-colors">
                                        Ingat saya
                                    </span>
                                </label>

                                {canResetPassword && (
                                    <Link
                                        href={route("password.request")}
                                        className="text-sm text-primary-500 hover:text-primary-600 dark:text-primary-400 dark:hover:text-primary-300 font-semibold transition-colors"
                                    >
                                        Lupa password?
                                    </Link>
                                )}
                            </div>

                            {/* Submit Button */}
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full h-12 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 text-white font-semibold shadow-lg shadow-primary-500/20 hover:shadow-primary-500/35 hover:scale-[1.01] active:scale-[0.99] disabled:opacity-50 disabled:pointer-events-none transition-all flex items-center justify-center gap-2"
                            >
                                {processing ? (
                                    <>
                                        <IconLoader2
                                            size={20}
                                            className="animate-spin"
                                        />
                                        Memproses...
                                    </>
                                ) : (
                                    "Masuk"
                                )}
                            </button>

                            {/* Register Link */}
                            <p className="text-center text-sm text-slate-600 dark:text-slate-400">
                                Belum punya akun?{" "}
                                <Link
                                    href="/register"
                                    className="text-primary-500 hover:text-primary-600 dark:text-primary-400 dark:hover:text-primary-300 font-semibold transition-colors"
                                >
                                    Daftar Sekarang
                                </Link>
                            </p>
                        </form>
                    </div>
                </div>

                {/* Right - Banner Image */}
                <div className="hidden lg:flex flex-1 relative bg-slate-900">
                    <img
                        src="/images/archery_login_banner.png"
                        alt="Archery Background"
                        className="absolute inset-0 w-full h-full object-cover opacity-80"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/40 to-transparent" />
                    
                    {/* Floating Info Box */}
                    <div className="absolute bottom-16 left-16 right-16 p-8 rounded-3xl bg-slate-950/60 backdrop-blur-md border border-white/10 text-white space-y-3 max-w-xl animate-slide-in">
                        <span className="px-3 py-1 rounded-full text-xs font-semibold bg-primary-500/20 border border-primary-500/30 text-primary-300 inline-block uppercase tracking-wider">
                            Archery Management System
                        </span>
                        <h2 className="text-3xl font-bold leading-tight">
                            Kelola Klub & POS Panahan Anda Secara Profesional
                        </h2>
                        <p className="text-slate-300 text-sm leading-relaxed">
                            Mulai dari pencatatan skor latihan anggota, kualifikasi, rekam kehadiran, booking jadwal pelatih, hingga modul pembayaran terintegrasi dalam satu sistem yang dinamis.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
