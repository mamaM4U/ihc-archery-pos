import React from "react";
import { Head, usePage, useForm, Link } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import {
    IconUserPlus,
    IconDeviceFloppy,
    IconArrowLeft,
    IconShield,
    IconPhone,
    IconEyeCheck,
} from "@tabler/icons-react";
import Input from "@/Components/Dashboard/Input";
import toast from "react-hot-toast";
import { useState } from "react";

export default function Create() {
    const { roles } = usePage().props;

    const { data, setData, post, errors, processing } = useForm({
        name: "",
        email: "",
        phone: "",
        password: "",
        password_confirmation: "",
        role: "member",
        is_active: true,
        avatar: null,
    });

    const [avatarPreview, setAvatarPreview] = useState(null);

    const submit = (e) => {
        e.preventDefault();
        post(route("users.store"), {
            onSuccess: () => toast.success("Pengguna berhasil ditambahkan"),
            onError: () => toast.error("Gagal menyimpan pengguna. Harap periksa kembali input Anda."),
        });
    };

    return (
        <>
            <Head title="Tambah Pengguna" />

            <div className="mb-6">
                <Link
                    href={route("users.index")}
                    className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-primary-600 mb-3"
                >
                    <IconArrowLeft size={16} />
                    Kembali ke Pengguna
                </Link>
                <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <IconUserPlus size={28} className="text-primary-500" />
                    Tambah Pengguna Baru
                </h1>
                <p className="text-sm text-slate-500 mt-1">
                    Buat akun pengguna baru dengan menetapkan data profile, role, dan status aktif.
                </p>
            </div>

            <form onSubmit={submit}>
                <div className="max-w-2xl space-y-6">
                    {/* Account Info */}
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                        <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">
                            Informasi Profil & Akun
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="md:col-span-2">
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Avatar
                                </label>
                                <div className="flex items-center gap-3">
                                    <div className="w-14 h-14 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden flex items-center justify-center text-slate-600 font-semibold border dark:border-slate-700 shrink-0">
                                        {avatarPreview ? (
                                            <img
                                                src={avatarPreview}
                                                alt="Preview"
                                                className="w-full h-full object-cover"
                                            />
                                        ) : (
                                            <span>
                                                {data.name
                                                    ? data.name
                                                          .charAt(0)
                                                          .toUpperCase()
                                                    : "?"}
                                            </span>
                                        )}
                                    </div>
                                    <Input
                                        type="file"
                                        accept="image/*"
                                        onChange={(e) => {
                                            const file = e.target.files[0];
                                            if (file) {
                                                setData("avatar", file);
                                                setAvatarPreview(
                                                    URL.createObjectURL(file)
                                                );
                                            }
                                        }}
                                        errors={errors.avatar}
                                    />
                                </div>
                            </div>
                            <Input
                                type="text"
                                label="Nama Lengkap"
                                placeholder="Masukkan nama lengkap"
                                value={data.name}
                                onChange={(e) =>
                                    setData("name", e.target.value)
                                }
                                errors={errors.name}
                            />
                            <Input
                                type="email"
                                label="Email"
                                placeholder="email@example.com"
                                value={data.email}
                                onChange={(e) =>
                                    setData("email", e.target.value)
                                }
                                errors={errors.email}
                            />
                            <Input
                                type="text"
                                label="Nomor Telepon"
                                placeholder="Contoh: 08123456789"
                                value={data.phone}
                                onChange={(e) =>
                                    setData("phone", e.target.value)
                                }
                                errors={errors.phone}
                            />
                            <div className="flex flex-col justify-center">
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Status Akun
                                </label>
                                <div className="flex items-center gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setData("is_active", !data.is_active)}
                                        className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                                            data.is_active ? "bg-primary-500" : "bg-slate-200 dark:bg-slate-800"
                                        }`}
                                    >
                                        <span
                                            className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                                                data.is_active ? "translate-x-5" : "translate-x-0"
                                            }`}
                                        />
                                    </button>
                                    <span className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {data.is_active ? "Aktif" : "Nonaktif / Ditangguhkan"}
                                    </span>
                                </div>
                                {errors.is_active && (
                                    <p className="text-xs text-danger-500 mt-1">
                                        {errors.is_active}
                                    </p>
                                )}
                            </div>
                            <Input
                                type="password"
                                label="Kata Sandi"
                                placeholder="Minimal 8 karakter"
                                value={data.password}
                                onChange={(e) =>
                                    setData("password", e.target.value)
                                }
                                errors={errors.password}
                            />
                            <Input
                                type="password"
                                label="Konfirmasi Kata Sandi"
                                placeholder="Ulangi kata sandi"
                                value={data.password_confirmation}
                                onChange={(e) =>
                                    setData(
                                        "password_confirmation",
                                        e.target.value
                                    )
                                }
                                errors={errors.password_confirmation}
                            />
                        </div>
                    </div>

                    {/* Role Selection */}
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                        <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                            <IconShield size={16} />
                            Pilih Peran / Role Pengguna
                        </h3>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {[
                                { id: "admin", label: "Admin", desc: "Akses penuh ke semua modul, laporan, dan konfigurasi sistem." },
                                { id: "coach", label: "Coach / Pelatih", desc: "Mengelola jadwal latihan mingguan, registrasi slot, dan memantau progres atlet." },
                                { id: "guardian", label: "Guardian / Wali", desc: "Melihat jadwal, kemajuan, dan menyetujui jadwal latihan untuk atlet di bawah pengawasan." },
                                { id: "member", label: "Member / Atlet", desc: "Melihat jadwal tersedia, mendaftar slot latihan, dan melihat data kesehatan pribadi." },
                            ].map((roleItem) => (
                                <button
                                    type="button"
                                    key={roleItem.id}
                                    onClick={() => setData("role", roleItem.id)}
                                    className={`text-left p-4 rounded-xl border-2 transition-all flex flex-col justify-between ${
                                        data.role === roleItem.id
                                            ? "border-primary-500 bg-primary-50/40 dark:bg-primary-950/20 text-slate-900 dark:text-white"
                                            : "border-slate-200 dark:border-slate-850 hover:border-primary-300 dark:hover:border-primary-800 bg-white dark:bg-slate-900/60"
                                    }`}
                                >
                                    <div className="flex items-center justify-between w-full mb-1">
                                        <span className="font-semibold text-sm capitalize">
                                            {roleItem.label}
                                        </span>
                                        <span className={`w-4 h-4 rounded-full border flex items-center justify-center ${
                                            data.role === roleItem.id
                                                ? "border-primary-500 bg-primary-500"
                                                : "border-slate-300 dark:border-slate-700"
                                        }`}>
                                            {data.role === roleItem.id && (
                                                <span className="w-1.5 h-1.5 rounded-full bg-white" />
                                            )}
                                        </span>
                                    </div>
                                    <p className="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                        {roleItem.desc}
                                    </p>
                                </button>
                            ))}
                        </div>
                        {errors.role && (
                            <p className="text-xs text-danger-500 mt-3">
                                {errors.role}
                            </p>
                        )}
                    </div>

                    {/* Submit */}
                    <div className="flex justify-end gap-3">
                        <Link
                            href={route("users.index")}
                            className="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium transition-colors"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary-500 hover:bg-primary-600 text-white font-medium transition-colors disabled:opacity-50"
                        >
                            <IconDeviceFloppy size={18} />
                            {processing ? "Menyimpan..." : "Simpan Pengguna"}
                        </button>
                    </div>
                </div>
            </form>
        </>
    );
}

Create.layout = (page) => <DashboardLayout children={page} />;
