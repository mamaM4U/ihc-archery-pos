import React, { useEffect } from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, router, useForm, usePage } from "@inertiajs/react";
import {
    IconSearch,
    IconUser,
    IconPhone,
    IconCheck,
    IconAlertCircle,
} from "@tabler/icons-react";
import Textarea from "@/Components/Dashboard/TextArea";
import toast from "react-hot-toast";

/**
 * Format a date string to Indonesian locale.
 */
function formatDate(dateString) {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
}

/**
 * Render a colored status badge.
 */
function StatusBadge({ status }) {
    const styles = {
        active: "bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400",
        expired: "bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400",
        pending: "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-400",
    };

    const labels = {
        active: "Aktif",
        expired: "Expired",
        pending: "Pending",
    };

    return (
        <span
            className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${styles[status] || styles.pending}`}
        >
            {labels[status] || status}
        </span>
    );
}

export default function CheckIn({ customers, selectedMembership, filters }) {
    const { flash, errors } = usePage().props;

    const { data, setData, post, processing, reset } = useForm({
        customer_membership_id: selectedMembership?.id || "",
        notes: "",
    });

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);

    /**
     * Handle customer search form submission.
     */
    function handleSearch(e) {
        e.preventDefault();
        const searchValue = e.target.search.value;
        router.get(
            route("memberships.check-in"),
            { search: searchValue },
            { preserveState: true, preserveScroll: true }
        );
    }

    /**
     * Handle customer selection to load their active membership.
     */
    function handleSelectCustomer(customerId) {
        router.get(
            route("memberships.check-in"),
            { ...filters, customer_id: customerId },
            { preserveState: true, preserveScroll: true }
        );
    }

    /**
     * Handle check-in form submission.
     */
    function handleCheckIn(e) {
        e.preventDefault();
        post(route("memberships.check-in.store"), {
            onSuccess: () => reset("notes"),
        });
    }

    return (
        <>
            <Head title="Check-in Sesi" />

            {/* Header */}
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                    Check-in Sesi
                </h1>
                <p className="text-sm text-slate-500 dark:text-slate-400">
                    Catat penggunaan sesi latihan member
                </p>
            </div>

            {/* Check-in error */}
            {errors.check_in && (
                <div className="mb-4 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">
                    <IconAlertCircle size={18} strokeWidth={1.5} />
                    {errors.check_in}
                </div>
            )}

            {/* Customer Search */}
            <div className="mb-6">
                <form onSubmit={handleSearch}>
                    <div className="relative w-full sm:w-96">
                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <IconSearch size={18} strokeWidth={1.5} />
                        </span>
                        <input
                            type="text"
                            name="search"
                            defaultValue={filters?.search || ""}
                            className="w-full h-11 pl-10 pr-4 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200"
                            placeholder="Cari nama atau no. telepon customer..."
                        />
                    </div>
                </form>
            </div>

            {/* Customer List */}
            {customers && customers.length > 0 && (
                <div className="mb-6">
                    <h2 className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                        Hasil Pencarian
                    </h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        {customers.map((customer) => (
                            <button
                                key={customer.id}
                                type="button"
                                onClick={() => handleSelectCustomer(customer.id)}
                                className={`flex items-center gap-3 p-4 rounded-xl border text-left transition-all duration-200 ${
                                    filters?.customer_id == customer.id
                                        ? "border-primary-500 bg-primary-50 dark:bg-primary-900/20 dark:border-primary-500"
                                        : "border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-primary-300 dark:hover:border-primary-700 hover:shadow-sm"
                                }`}
                            >
                                <div className="flex-shrink-0 w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                    <IconUser
                                        size={20}
                                        strokeWidth={1.5}
                                        className="text-slate-500 dark:text-slate-400"
                                    />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">
                                        {customer.name}
                                    </p>
                                    {customer.no_telp && (
                                        <p className="flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
                                            <IconPhone size={12} strokeWidth={1.5} />
                                            {customer.no_telp}
                                        </p>
                                    )}
                                </div>
                            </button>
                        ))}
                    </div>
                </div>
            )}

            {/* No results message */}
            {filters?.search && customers && customers.length === 0 && (
                <div className="mb-6 text-center py-8 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800">
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Tidak ada customer ditemukan untuk "{filters.search}"
                    </p>
                </div>
            )}

            {/* Selected Membership Info & Check-in Form */}
            {filters?.customer_id && selectedMembership && (
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                    <h2 className="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-4">
                        Informasi Membership
                    </h2>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div>
                            <p className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                                Paket
                            </p>
                            <p className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                {selectedMembership.membership_plan?.name || "-"}
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                                Sisa Sesi
                            </p>
                            <p className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                {selectedMembership.remaining_sessions !== undefined
                                    ? selectedMembership.remaining_sessions
                                    : Math.max(0, selectedMembership.session_quota - selectedMembership.session_used)}{" "}
                                / {selectedMembership.session_quota} sesi
                            </p>
                        </div>
                        <div>
                            <p className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                                Status
                            </p>
                            <StatusBadge status={selectedMembership.status} />
                        </div>
                        <div>
                            <p className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                                Berakhir
                            </p>
                            <p className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                {formatDate(selectedMembership.end_date)}
                            </p>
                        </div>
                    </div>

                    {/* Check-in Form */}
                    <form onSubmit={handleCheckIn}>
                        <input
                            type="hidden"
                            name="customer_membership_id"
                            value={data.customer_membership_id}
                        />

                        <Textarea
                            label="Catatan (opsional)"
                            placeholder="Tambahkan catatan untuk sesi ini..."
                            value={data.notes}
                            onChange={(e) => setData("notes", e.target.value)}
                            errors={errors.notes}
                            rows={3}
                        />

                        <div className="mt-4">
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                            >
                                <IconCheck size={18} strokeWidth={1.5} />
                                {processing ? "Memproses..." : "Check-in"}
                            </button>
                        </div>
                    </form>
                </div>
            )}

            {/* No active membership message */}
            {filters?.customer_id && !selectedMembership && (
                <div className="text-center py-8 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800">
                    <IconAlertCircle
                        size={32}
                        strokeWidth={1.5}
                        className="mx-auto text-slate-400 mb-2"
                    />
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Customer ini tidak memiliki membership aktif.
                    </p>
                </div>
            )}
        </>
    );
}

CheckIn.layout = (page) => <DashboardLayout children={page} />;
