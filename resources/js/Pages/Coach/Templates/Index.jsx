import React, { useState } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import {
    IconCalendar,
    IconPlus,
    IconEdit,
    IconTrash,
    IconCheck,
    IconX,
    IconInfoCircle,
} from "@tabler/icons-react";
import Pagination from "@/Components/Dashboard/Pagination";
import Modal from "@/Components/Dashboard/Modal";
import toast from "react-hot-toast";
import hasAnyPermission from "@/Utils/Permission";

export default function Index({ templates }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const isAdmin = user.role === "admin";

    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [templateToDelete, setTemplateToDelete] = useState(null);

    const openDeleteModal = (template) => {
        setTemplateToDelete(template);
        setDeleteModalOpen(true);
    };

    const closeDeleteModal = () => {
        setTemplateToDelete(null);
        setDeleteModalOpen(false);
    };

    const handleDelete = () => {
        if (!templateToDelete) return;

        router.delete(route("templates.destroy", templateToDelete.id), {
            onSuccess: () => {
                toast.success("Template mingguan berhasil dihapus");
                closeDeleteModal();
            },
            onError: () => {
                toast.error("Gagal menghapus template");
            },
        });
    };

    // Helper to map day index to day name in Indonesian
    const getDayName = (dayIndex) => {
        const days = [
            "Minggu",
            "Senin",
            "Selasa",
            "Rabu",
            "Kamis",
            "Jumat",
            "Sabtu",
        ];
        return days[dayIndex] || "";
    };

    return (
        <>
            <Head title="Template Jadwal Mingguan" />

            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <IconCalendar size={28} className="text-primary-500" />
                        Template Jadwal Mingguan
                    </h1>
                    <p className="text-sm text-slate-500 mt-1">
                        Kelola template jadwal latihan rutin mingguan Anda. Template yang aktif digunakan untuk lazy generation slot jadwal.
                    </p>
                </div>
                {hasAnyPermission(["templates-create"]) && (
                    <Link
                        href={route("templates.create")}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-500 hover:bg-primary-600 text-white font-medium text-sm transition-all shadow-sm hover:shadow-primary-500/10"
                    >
                        <IconPlus size={18} />
                        Tambah Template
                    </Link>
                )}
            </div>

            <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-slate-50/75 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                <th className="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Nama Template
                                </th>
                                {isAdmin && (
                                    <th className="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Coach / Pelatih
                                    </th>
                                )}
                                <th className="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Batas Akhir Booking
                                </th>
                                <th className="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Jumlah Sesi
                                </th>
                                <th className="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Status
                                </th>
                                <th className="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Catatan
                                </th>
                                <th className="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-right">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                            {templates.data.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={isAdmin ? 7 : 6}
                                        className="px-6 py-10 text-center text-sm text-slate-400 dark:text-slate-500"
                                    >
                                        <div className="flex flex-col items-center justify-center gap-2">
                                            <IconInfoCircle size={32} />
                                            <span>Belum ada template jadwal mingguan.</span>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                templates.data.map((template) => (
                                    <tr
                                        key={template.id}
                                        className="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors"
                                    >
                                        <td className="px-6 py-4">
                                            <div className="font-semibold text-slate-800 dark:text-slate-200">
                                                {template.template_name}
                                            </div>
                                            <div className="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                                Dibuat pada {new Date(template.created_at).toLocaleDateString("id-ID")}
                                            </div>
                                        </td>
                                        {isAdmin && (
                                            <td className="px-6 py-4">
                                                <span className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                                    {template.coach?.name || "-"}
                                                </span>
                                            </td>
                                        )}
                                        <td className="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                            H-{template.booking_open_days} sebelum sesi
                                        </td>
                                        <td className="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                            <div className="flex flex-wrap gap-1 items-center">
                                                <span className="inline-flex items-center justify-center px-2 py-1 text-xs font-semibold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                    {template.template_slots_count || 0} Sesi
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span
                                                className={`inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full ${
                                                    template.is_active
                                                        ? "bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 border border-green-200/50 dark:border-green-800/30"
                                                        : "bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 border border-slate-200/50 dark:border-slate-750"
                                                }`}
                                            >
                                                {template.is_active ? (
                                                    <>
                                                        <IconCheck size={12} strokeWidth={3} />
                                                        Aktif
                                                    </>
                                                ) : (
                                                    <>
                                                        <IconX size={12} strokeWidth={3} />
                                                        Nonaktif
                                                    </>
                                                )}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-slate-500 dark:text-slate-400 max-w-xs truncate">
                                            {template.notes || "-"}
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <div className="flex justify-end gap-2">
                                                {hasAnyPermission(["templates-update"]) && (
                                                    <Link
                                                        href={route("templates.edit", template.id)}
                                                        className="p-1.5 rounded-lg text-slate-500 hover:text-primary-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                                        title="Edit Template"
                                                    >
                                                        <IconEdit size={18} />
                                                    </Link>
                                                )}
                                                {hasAnyPermission(["templates-delete"]) && (
                                                    <button
                                                        type="button"
                                                        onClick={() => openDeleteModal(template)}
                                                        className="p-1.5 rounded-lg text-slate-500 hover:text-danger-600 hover:bg-danger-50 dark:hover:bg-danger-950/10 transition-colors"
                                                        title="Hapus Template"
                                                    >
                                                        <IconTrash size={18} />
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {templates.links && templates.data.length > 0 && (
                    <div className="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <span className="text-sm text-slate-500 dark:text-slate-400">
                            Menampilkan {templates.from} - {templates.to} dari {templates.total} template
                        </span>
                        <Pagination links={templates.links} />
                    </div>
                )}
            </div>

            {/* Delete Confirmation Modal */}
            <Modal show={deleteModalOpen} onClose={closeDeleteModal}>
                <div className="p-6">
                    <h2 className="text-lg font-bold text-slate-900 dark:text-white mb-2">
                        Konfirmasi Hapus Template
                    </h2>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mb-6">
                        Apakah Anda yakin ingin menghapus template{" "}
                        <span className="font-semibold text-slate-800 dark:text-slate-200">
                            "{templateToDelete?.template_name}"
                        </span>
                        ? Tindakan ini tidak dapat dibatalkan, namun jadwal yang sudah di-generate sebelumnya akan tetap dipertahankan.
                    </p>
                    <div className="flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={closeDeleteModal}
                            className="px-4 py-2 text-sm font-medium border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            onClick={handleDelete}
                            className="px-4 py-2 text-sm font-medium bg-danger-500 hover:bg-danger-600 text-white rounded-xl transition-colors"
                        >
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </Modal>
        </>
    );
}

Index.layout = (page) => <DashboardLayout children={page} />;
