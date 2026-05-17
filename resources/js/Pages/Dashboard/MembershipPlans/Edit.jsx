import React from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, useForm, usePage, Link } from "@inertiajs/react";
import Input from "@/Components/Dashboard/Input";
import Textarea from "@/Components/Dashboard/TextArea";
import Checkbox from "@/Components/Dashboard/Checkbox";
import toast from "react-hot-toast";
import {
    IconCreditCard,
    IconDeviceFloppy,
    IconArrowLeft,
} from "@tabler/icons-react";

const CATEGORY_OPTIONS = [
    { value: "registration", label: "Registrasi" },
    { value: "trial", label: "Trial" },
    { value: "monthly_no_equipment", label: "Bulanan - Tanpa Alat" },
    { value: "monthly_with_equipment", label: "Bulanan - Dengan Alat" },
    { value: "family", label: "Keluarga" },
];

export default function Edit({ membershipPlan }) {
    const { errors } = usePage().props;

    const { data, setData, put, processing } = useForm({
        name: membershipPlan.name || "",
        category: membershipPlan.category || "",
        price: membershipPlan.price ?? "",
        duration_days: membershipPlan.duration_days ?? "",
        session_quota: membershipPlan.session_quota ?? "",
        description: membershipPlan.description || "",
        equipment_provided: membershipPlan.equipment_provided ?? false,
        family_members: membershipPlan.family_members ?? "",
        is_active: membershipPlan.is_active ?? true,
    });

    const handleCategoryChange = (value) => {
        setData((prev) => {
            const updated = { ...prev, category: value };
            if (value === "trial") {
                updated.session_quota = 1;
            } else if (value === "registration") {
                updated.session_quota = 0;
                updated.duration_days = 0;
            }
            return updated;
        });
    };

    const submit = (e) => {
        e.preventDefault();
        put(route("membership-plans.update", membershipPlan.id), {
            onSuccess: () =>
                toast.success("Paket membership berhasil diperbarui"),
            onError: () => toast.error("Gagal memperbarui paket membership"),
        });
    };

    return (
        <>
            <Head title="Edit Paket Membership" />

            {/* Header */}
            <div className="mb-6">
                <Link
                    href={route("membership-plans.index")}
                    className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-primary-600 mb-3"
                >
                    <IconArrowLeft size={16} />
                    Kembali ke Paket Membership
                </Link>
                <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <IconCreditCard size={28} className="text-primary-500" />
                    Edit Paket Membership
                </h1>
            </div>

            <form onSubmit={submit}>
                <div className="max-w-2xl space-y-6">
                    {/* Basic Info */}
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                        <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">
                            Informasi Dasar
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="md:col-span-2">
                                <Input
                                    type="text"
                                    label="Nama Paket"
                                    placeholder="Masukkan nama paket"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData("name", e.target.value)
                                    }
                                    errors={errors.name}
                                />
                            </div>

                            <div className="md:col-span-2">
                                <div className="flex flex-col gap-2">
                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Kategori
                                    </label>
                                    <select
                                        value={data.category}
                                        onChange={(e) =>
                                            handleCategoryChange(e.target.value)
                                        }
                                        className={`
                                            w-full h-11 px-4 text-sm rounded-xl
                                            border border-slate-200 dark:border-slate-700
                                            bg-slate-50 dark:bg-slate-800
                                            text-slate-800 dark:text-slate-200
                                            focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500
                                            transition-all duration-200
                                            ${errors.category ? "border-danger-500 focus:border-danger-500 focus:ring-danger-500/20" : ""}
                                        `}
                                    >
                                        <option value="">
                                            Pilih kategori
                                        </option>
                                        {CATEGORY_OPTIONS.map((opt) => (
                                            <option
                                                key={opt.value}
                                                value={opt.value}
                                            >
                                                {opt.label}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.category && (
                                        <small className="text-xs text-danger-500 dark:text-danger-400">
                                            {errors.category}
                                        </small>
                                    )}
                                </div>
                            </div>

                            <div className="md:col-span-2">
                                <Textarea
                                    label="Deskripsi"
                                    placeholder="Deskripsi paket (opsional)"
                                    value={data.description}
                                    onChange={(e) =>
                                        setData("description", e.target.value)
                                    }
                                    errors={errors.description}
                                    rows={3}
                                />
                            </div>
                        </div>
                    </div>

                    {/* Pricing & Duration */}
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                        <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">
                            Harga & Durasi
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Input
                                type="number"
                                label="Harga (Rp)"
                                placeholder="0"
                                value={data.price}
                                onChange={(e) =>
                                    setData("price", e.target.value)
                                }
                                errors={errors.price}
                                min="0"
                            />
                            <Input
                                type="number"
                                label="Durasi (hari)"
                                placeholder="0"
                                value={data.duration_days}
                                onChange={(e) =>
                                    setData("duration_days", e.target.value)
                                }
                                errors={errors.duration_days}
                                min="0"
                            />
                            <Input
                                type="number"
                                label="Kuota Sesi"
                                placeholder="0"
                                value={data.session_quota}
                                onChange={(e) =>
                                    setData("session_quota", e.target.value)
                                }
                                errors={errors.session_quota}
                                min="0"
                            />
                        </div>
                    </div>

                    {/* Additional Options */}
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                        <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">
                            Opsi Tambahan
                        </h3>
                        <div className="space-y-4">
                            <Checkbox
                                label="Alat disediakan"
                                checked={data.equipment_provided}
                                onChange={(e) =>
                                    setData(
                                        "equipment_provided",
                                        e.target.checked
                                    )
                                }
                                errors={errors.equipment_provided}
                            />

                            {data.category === "family" && (
                                <Input
                                    type="number"
                                    label="Jumlah Anggota Keluarga"
                                    placeholder="1"
                                    value={data.family_members}
                                    onChange={(e) =>
                                        setData(
                                            "family_members",
                                            e.target.value
                                        )
                                    }
                                    errors={errors.family_members}
                                    min="1"
                                />
                            )}

                            <Checkbox
                                label="Aktif"
                                checked={data.is_active}
                                onChange={(e) =>
                                    setData("is_active", e.target.checked)
                                }
                                errors={errors.is_active}
                            />
                        </div>
                    </div>

                    {/* Submit */}
                    <div className="flex justify-end gap-3">
                        <Link
                            href={route("membership-plans.index")}
                            className="px-6 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium transition-colors"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-primary-500 hover:bg-primary-600 text-white font-medium transition-colors disabled:opacity-50"
                        >
                            <IconDeviceFloppy size={18} />
                            {processing
                                ? "Menyimpan..."
                                : "Perbarui Paket"}
                        </button>
                    </div>
                </div>
            </form>
        </>
    );
}

Edit.layout = (page) => <DashboardLayout children={page} />;
