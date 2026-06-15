import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head } from "@inertiajs/react";
import {
    IconUsers,
    IconTarget,
    IconAward,
} from "@tabler/icons-react";

export default function Dashboard({ stats }) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                        Dashboard
                    </h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Selamat datang kembali! Berikut adalah ringkasan aktivitas IHC Archery Club.
                    </p>
                </div>

                {/* Stat Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div className="relative overflow-hidden rounded-2xl p-6 bg-gradient-to-br from-indigo-500 to-indigo-700 text-white shadow-lg">
                        <div className="absolute top-0 right-0 w-32 h-32 opacity-20">
                            <IconUsers size={128} strokeWidth={0.5} className="transform translate-x-8 -translate-y-8" />
                        </div>
                        <div className="relative z-10">
                            <div className="flex items-center gap-2 mb-3">
                                <div className="p-2 rounded-xl bg-white/20">
                                    <IconUsers size={20} strokeWidth={1.5} />
                                </div>
                                <span className="text-sm font-medium opacity-90">Total Pelatih</span>
                            </div>
                            <p className="text-4xl font-bold">{stats.coaches_count}</p>
                            <p className="mt-2 text-sm opacity-80">Pelatih aktif terdaftar</p>
                        </div>
                    </div>

                    <div className="relative overflow-hidden rounded-2xl p-6 bg-gradient-to-br from-emerald-500 to-emerald-700 text-white shadow-lg">
                        <div className="absolute top-0 right-0 w-32 h-32 opacity-20">
                            <IconAward size={128} strokeWidth={0.5} className="transform translate-x-8 -translate-y-8" />
                        </div>
                        <div className="relative z-10">
                            <div className="flex items-center gap-2 mb-3">
                                <div className="p-2 rounded-xl bg-white/20">
                                    <IconAward size={20} strokeWidth={1.5} />
                                </div>
                                <span className="text-sm font-medium opacity-90">Total Atlet (Member)</span>
                            </div>
                            <p className="text-4xl font-bold">{stats.members_count}</p>
                            <p className="mt-2 text-sm opacity-80">Atlet aktif terdaftar</p>
                        </div>
                    </div>

                    <div className="relative overflow-hidden rounded-2xl p-6 bg-gradient-to-br from-amber-500 to-amber-700 text-white shadow-lg">
                        <div className="absolute top-0 right-0 w-32 h-32 opacity-20">
                            <IconTarget size={128} strokeWidth={0.5} className="transform translate-x-8 -translate-y-8" />
                        </div>
                        <div className="relative z-10">
                            <div className="flex items-center gap-2 mb-3">
                                <div className="p-2 rounded-xl bg-white/20">
                                    <IconTarget size={20} strokeWidth={1.5} />
                                </div>
                                <span className="text-sm font-medium opacity-90">Total Wali / Orang Tua</span>
                            </div>
                            <p className="text-4xl font-bold">{stats.guardians_count}</p>
                            <p className="mt-2 text-sm opacity-80">Pendamping terdaftar</p>
                        </div>
                    </div>
                </div>

                {/* Welcome Message Card */}
                <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                    <h2 className="text-lg font-bold text-slate-900 dark:text-white mb-2">
                        IHC Archery Club Management System
                    </h2>
                    <p className="text-slate-600 dark:text-slate-400 leading-relaxed text-sm">
                        Gunakan menu navigasi di sebelah kiri untuk mengelola akun pengguna, mengatur hak akses, dan menetapkan peran masing-masing anggota klub. Hubungkan pelatih dengan atlet, serta orang tua dengan atlet untuk integrasi pemantauan jadwal dan kemajuan latihan yang mulus.
                    </p>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = (page) => <DashboardLayout children={page} />;
