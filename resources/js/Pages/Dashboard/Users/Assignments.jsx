import React, { useState } from "react";
import { Head, usePage, useForm, Link } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import {
    IconArrowLeft,
    IconLink,
    IconTrash,
    IconUserCheck,
    IconShield,
    IconUserCircle,
    IconNotebook,
    IconCheckbox,
} from "@tabler/icons-react";
import toast from "react-hot-toast";
import Swal from "sweetalert2";

export default function Assignments() {
    const { coaches, members, guardians, coachMembers, userRelationships } = usePage().props;
    const [activeTab, setActiveTab] = useState("coach");

    // Coach Member Form
    const coachForm = useForm({
        coach_id: "",
        member_id: "",
    });

    // Guardian Member Form
    const guardianForm = useForm({
        guardian_id: "",
        member_id: "",
        can_approve_booking: true,
    });

    const submitCoach = (e) => {
        e.preventDefault();
        coachForm.post(route("users.assign-coach"), {
            onSuccess: () => {
                toast.success("Coach berhasil ditugaskan ke Member");
                coachForm.reset("member_id"); // Reset member select for easier sequential assignments
            },
            onError: () => toast.error("Gagal melakukan penugasan Coach"),
        });
    };

    const submitGuardian = (e) => {
        e.preventDefault();
        guardianForm.post(route("users.assign-guardian"), {
            onSuccess: () => {
                toast.success("Guardian berhasil ditugaskan ke Member");
                guardianForm.reset("member_id");
            },
            onError: () => toast.error("Gagal melakukan penugasan Guardian"),
        });
    };

    const removeCoach = (coachId, memberId, coachName, memberName) => {
        Swal.fire({
            title: "Hapus Penugasan?",
            text: `Apakah Anda yakin ingin melepas hubungan Coach ${coachName} dengan Member ${memberName}?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Lepas Hubungan!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                const deleteForm = useForm({
                    coach_id: coachId,
                    member_id: memberId,
                });
                deleteForm.delete(route("users.remove-coach"), {
                    onSuccess: () => {
                        Swal.fire({
                            title: "Berhasil!",
                            text: "Hubungan Coach & Member berhasil dilepas.",
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1500,
                        });
                    },
                    onError: () => toast.error("Gagal melepas hubungan Coach & Member"),
                });
            }
        });
    };

    const removeGuardian = (guardianId, memberId, guardianName, memberName) => {
        Swal.fire({
            title: "Hapus Hubungan Wali?",
            text: `Apakah Anda yakin ingin melepas hubungan Guardian ${guardianName} dengan Member ${memberName}?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Lepas Hubungan!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                const deleteForm = useForm({
                    guardian_id: guardianId,
                    member_id: memberId,
                });
                deleteForm.delete(route("users.remove-guardian"), {
                    onSuccess: () => {
                        Swal.fire({
                            title: "Berhasil!",
                            text: "Hubungan Guardian & Member berhasil dilepas.",
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1500,
                        });
                    },
                    onError: () => toast.error("Gagal melepas hubungan Guardian & Member"),
                });
            }
        });
    };

    return (
        <>
            <Head title="Penugasan Hubungan Pengguna" />

            <div className="mb-6">
                <Link
                    href={route("users.index")}
                    className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-primary-600 mb-3 transition-colors"
                >
                    <IconArrowLeft size={16} />
                    Kembali ke Pengguna
                </Link>
                <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <IconLink size={28} className="text-primary-500" />
                    Penugasan Hubungan Pengguna
                </h1>
                <p className="text-sm text-slate-500 mt-1">
                    Hubungkan Member/Atlet ke Coach (Pelatih) pendamping dan Guardian (Wali/Orang Tua).
                </p>
            </div>

            {/* Tab Navigation */}
            <div className="flex border-b border-slate-200 dark:border-slate-800 mb-6 bg-white dark:bg-slate-900 rounded-xl p-1 shadow-sm max-w-md">
                <button
                    onClick={() => setActiveTab("coach")}
                    className={`flex-1 py-2 text-sm font-semibold rounded-lg transition-all ${
                        activeTab === "coach"
                            ? "bg-primary-500 text-white shadow-sm"
                            : "text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40"
                    }`}
                >
                    Coach → Member
                </button>
                <button
                    onClick={() => setActiveTab("guardian")}
                    className={`flex-1 py-2 text-sm font-semibold rounded-lg transition-all ${
                        activeTab === "guardian"
                            ? "bg-primary-500 text-white shadow-sm"
                            : "text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40"
                    }`}
                >
                    Guardian → Member
                </button>
            </div>

            {/* Tab Contents */}
            {activeTab === "coach" ? (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Form */}
                    <div className="lg:col-span-1 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm h-fit">
                        <h3 className="text-base font-bold text-slate-850 dark:text-slate-100 mb-4 flex items-center gap-2">
                            <IconUserCheck size={20} className="text-indigo-500" />
                            Tugaskan Coach
                        </h3>
                        <form onSubmit={submitCoach} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-355 mb-1.5">
                                    Pilih Coach / Pelatih
                                </label>
                                <select
                                    value={coachForm.data.coach_id}
                                    onChange={(e) => coachForm.setData("coach_id", e.target.value)}
                                    className="block w-full py-2.5 px-3 border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 rounded-xl text-sm focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                                    required
                                >
                                    <option value="">-- Pilih Coach --</option>
                                    {coaches.map((coach) => (
                                        <option key={coach.id} value={coach.id}>
                                            {coach.name} ({coach.email})
                                        </option>
                                    ))}
                                </select>
                                {coachForm.errors.coach_id && (
                                    <p className="text-xs text-danger-550 mt-1">{coachForm.errors.coach_id}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-355 mb-1.5">
                                    Pilih Member / Atlet
                                </label>
                                <select
                                    value={coachForm.data.member_id}
                                    onChange={(e) => coachForm.setData("member_id", e.target.value)}
                                    className="block w-full py-2.5 px-3 border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 rounded-xl text-sm focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                                    required
                                >
                                    <option value="">-- Pilih Member --</option>
                                    {members.map((member) => (
                                        <option key={member.id} value={member.id}>
                                            {member.name} ({member.email})
                                        </option>
                                    ))}
                                </select>
                                {coachForm.errors.member_id && (
                                    <p className="text-xs text-danger-555 mt-1">{coachForm.errors.member_id}</p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={coachForm.processing}
                                className="w-full py-2.5 rounded-xl bg-indigo-650 hover:bg-indigo-700 text-white font-semibold text-sm shadow-md transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                <IconLink size={16} />
                                {coachForm.processing ? "Menyimpan..." : "Tugaskan Coach"}
                            </button>
                        </form>
                    </div>

                    {/* Right Table */}
                    <div className="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div className="p-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                            <h3 className="text-base font-bold text-slate-850 dark:text-slate-100 flex items-center gap-2">
                                <IconNotebook size={18} className="text-slate-500" />
                                Daftar Hubungan Coach & Member ({coachMembers.length})
                            </h3>
                        </div>
                        <div className="overflow-x-auto">
                            {coachMembers.length > 0 ? (
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-slate-50/50 dark:bg-slate-900/50 text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                                            <th className="p-4 pl-6">Coach / Pelatih</th>
                                            <th className="p-4">Member / Atlet</th>
                                            <th className="p-4 text-right pr-6 w-24"></th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {coachMembers.map((cm) => (
                                            <tr key={cm.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                                <td className="p-4 pl-6">
                                                    <div className="font-semibold text-sm text-slate-800 dark:text-slate-200">
                                                        {cm.coach?.name || "N/A"}
                                                    </div>
                                                    <div className="text-xs text-slate-500 dark:text-slate-400">
                                                        {cm.coach?.email || "N/A"}
                                                    </div>
                                                </td>
                                                <td className="p-4">
                                                    <div className="font-semibold text-sm text-slate-800 dark:text-slate-200">
                                                        {cm.member?.name || "N/A"}
                                                    </div>
                                                    <div className="text-xs text-slate-500 dark:text-slate-400">
                                                        {cm.member?.email || "N/A"}
                                                    </div>
                                                </td>
                                                <td className="p-4 text-right pr-6">
                                                    <button
                                                        onClick={() => removeCoach(cm.coach_id, cm.member_id, cm.coach?.name, cm.member?.name)}
                                                        className="p-1.5 rounded-lg border border-danger-200 dark:border-danger-900/60 bg-danger-50 dark:bg-danger-950/20 text-danger-600 dark:text-danger-400 hover:bg-danger-100 dark:hover:bg-danger-950/40 transition-colors"
                                                        title="Lepas Penugasan"
                                                    >
                                                        <IconTrash size={16} />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            ) : (
                                <div className="text-center py-12 text-slate-500 dark:text-slate-400">
                                    Belum ada penugasan Coach untuk Member.
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            ) : (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Form */}
                    <div className="lg:col-span-1 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm h-fit">
                        <h3 className="text-base font-bold text-slate-850 dark:text-slate-100 mb-4 flex items-center gap-2">
                            <IconUserCircle size={20} className="text-amber-500" />
                            Tugaskan Wali / Guardian
                        </h3>
                        <form onSubmit={submitGuardian} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-355 mb-1.5">
                                    Pilih Guardian / Wali
                                </label>
                                <select
                                    value={guardianForm.data.guardian_id}
                                    onChange={(e) => guardianForm.setData("guardian_id", e.target.value)}
                                    className="block w-full py-2.5 px-3 border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 rounded-xl text-sm focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                                    required
                                >
                                    <option value="">-- Pilih Guardian --</option>
                                    {guardians.map((guardian) => (
                                        <option key={guardian.id} value={guardian.id}>
                                            {guardian.name} ({guardian.email})
                                        </option>
                                    ))}
                                </select>
                                {guardianForm.errors.guardian_id && (
                                    <p className="text-xs text-danger-555 mt-1">{guardianForm.errors.guardian_id}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-355 mb-1.5">
                                    Pilih Member / Atlet
                                </label>
                                <select
                                    value={guardianForm.data.member_id}
                                    onChange={(e) => guardianForm.setData("member_id", e.target.value)}
                                    className="block w-full py-2.5 px-3 border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-955 text-slate-850 dark:text-slate-200 rounded-xl text-sm focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                                    required
                                >
                                    <option value="">-- Pilih Member --</option>
                                    {members.map((member) => (
                                        <option key={member.id} value={member.id}>
                                            {member.name} ({member.email})
                                        </option>
                                    ))}
                                </select>
                                {guardianForm.errors.member_id && (
                                    <p className="text-xs text-danger-555 mt-1">{guardianForm.errors.member_id}</p>
                                )}
                            </div>

                            <div className="flex items-center gap-3 py-2">
                                <button
                                    type="button"
                                    onClick={() => guardianForm.setData("can_approve_booking", !guardianForm.data.can_approve_booking)}
                                    className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                                        guardianForm.data.can_approve_booking ? "bg-amber-500" : "bg-slate-200 dark:bg-slate-800"
                                    }`}
                                >
                                    <span
                                        className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                                            guardianForm.data.can_approve_booking ? "translate-x-5" : "translate-x-0"
                                        }`}
                                    />
                                </button>
                                <span className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Dapat Menyetujui Booking
                                </span>
                            </div>

                            <button
                                type="submit"
                                disabled={guardianForm.processing}
                                className="w-full py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-semibold text-sm shadow-md transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                <IconLink size={16} />
                                {guardianForm.processing ? "Menyimpan..." : "Tugaskan Guardian"}
                            </button>
                        </form>
                    </div>

                    {/* Right Table */}
                    <div className="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div className="p-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                            <h3 className="text-base font-bold text-slate-850 dark:text-slate-100 flex items-center gap-2">
                                <IconNotebook size={18} className="text-slate-500" />
                                Daftar Hubungan Wali & Member ({userRelationships.length})
                            </h3>
                        </div>
                        <div className="overflow-x-auto">
                            {userRelationships.length > 0 ? (
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-slate-50/50 dark:bg-slate-900/50 text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                                            <th className="p-4 pl-6">Guardian / Wali</th>
                                            <th className="p-4">Member / Atlet</th>
                                            <th className="p-4 text-center">Izin Booking</th>
                                            <th className="p-4 text-right pr-6 w-24"></th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {userRelationships.map((ur) => (
                                            <tr key={ur.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                                <td className="p-4 pl-6">
                                                    <div className="font-semibold text-sm text-slate-800 dark:text-slate-200">
                                                        {ur.guardian?.name || "N/A"}
                                                    </div>
                                                    <div className="text-xs text-slate-500 dark:text-slate-400">
                                                        {ur.guardian?.email || "N/A"}
                                                    </div>
                                                </td>
                                                <td className="p-4">
                                                    <div className="font-semibold text-sm text-slate-800 dark:text-slate-200">
                                                        {ur.member?.name || "N/A"}
                                                    </div>
                                                    <div className="text-xs text-slate-500 dark:text-slate-400">
                                                        {ur.member?.email || "N/A"}
                                                    </div>
                                                </td>
                                                <td className="p-4 text-center">
                                                    <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold ${
                                                        ur.can_approve_booking
                                                            ? "bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200"
                                                            : "bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200"
                                                    }`}>
                                                        {ur.can_approve_booking ? "Diizinkan" : "Tidak Diizinkan"}
                                                    </span>
                                                </td>
                                                <td className="p-4 text-right pr-6">
                                                    <button
                                                        onClick={() => removeGuardian(ur.guardian_id, ur.member_id, ur.guardian?.name, ur.member?.name)}
                                                        className="p-1.5 rounded-lg border border-danger-200 dark:border-danger-900/60 bg-danger-50 dark:bg-danger-950/20 text-danger-600 dark:text-danger-400 hover:bg-danger-100 dark:hover:bg-danger-950/40 transition-colors"
                                                        title="Lepas Hubungan"
                                                    >
                                                        <IconTrash size={16} />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            ) : (
                                <div className="text-center py-12 text-slate-500 dark:text-slate-400">
                                    Belum ada hubungan Wali & Member.
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}

Assignments.layout = (page) => <DashboardLayout children={page} />;
