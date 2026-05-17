import React, { useState } from "react";
import { Head, Link, router } from "@inertiajs/react";
import CustomerLayout from "@/Layouts/CustomerLayout";
import {
    IconTarget,
    IconAlertTriangle,
    IconArrowLeft,
    IconCheck,
    IconUsers,
    IconBow,
    IconShoppingCart,
} from "@tabler/icons-react";

/**
 * Format price in Indonesian Rupiah.
 */
const formatRupiah = (amount) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const Plans = ({ plans, currentMembership, hasRegistration }) => {
    const [purchasing, setPurchasing] = useState(null);
    const [error, setError] = useState(null);

    const categoryLabels = {
        registration: "Pendaftaran Member",
        trial: "Paket Trial",
        monthly_no_equipment: "Bulanan - Belum Punya Alat",
        monthly_with_equipment: "Bulanan - Sudah Punya Alat",
        family: "Paket Keluarga",
    };

    const categoryDescriptions = {
        registration: "Biaya pendaftaran awal untuk menjadi member IHC Archery.",
        trial: "Coba 1 sesi latihan panahan untuk pemula.",
        monthly_no_equipment: "Paket bulanan untuk member yang belum memiliki alat sendiri. Alat disediakan.",
        monthly_with_equipment: "Paket bulanan untuk member yang sudah memiliki alat sendiri.",
        family: "Paket keluarga untuk berlatih bersama.",
    };

    const categoryOrder = [
        "registration",
        "trial",
        "monthly_no_equipment",
        "monthly_with_equipment",
        "family",
    ];

    const requiresRegistration = (category) => {
        return ["monthly_no_equipment", "monthly_with_equipment", "family"].includes(category);
    };

    const handlePurchase = (planId) => {
        setError(null);
        setPurchasing(planId);

        router.post(
            route("customer.membership.purchase"),
            { membership_plan_id: planId },
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    const responseData = page.props?.flash;
                    // If there's a payment_url in the response, redirect
                    if (responseData?.payment_url) {
                        window.location.href = responseData.payment_url;
                    }
                    setPurchasing(null);
                },
                onError: (errors) => {
                    setError(
                        errors?.message ||
                        errors?.membership_plan_id ||
                        "Terjadi kesalahan saat memproses pembelian."
                    );
                    setPurchasing(null);
                },
                onFinish: () => {
                    setPurchasing(null);
                },
            }
        );
    };

    const isRenewal = currentMembership && (
        currentMembership.is_expiring_soon ||
        currentMembership.remaining_days <= 0
    );

    return (
        <>
            <Head title="Paket Membership" />

            <div className="space-y-8">
                {/* Page Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                            Paket Membership
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Pilih paket membership yang sesuai dengan kebutuhan Anda
                        </p>
                    </div>
                    <Link
                        href={route("customer.membership")}
                        className="hidden sm:flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 transition-all"
                    >
                        <IconArrowLeft size={18} />
                        Kembali
                    </Link>
                </div>

                {/* Error Message */}
                {error && (
                    <div
                        className="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl"
                        role="alert"
                    >
                        <IconAlertTriangle
                            size={20}
                            className="text-red-600 dark:text-red-400 mt-0.5 shrink-0"
                        />
                        <div>
                            <p className="text-sm font-semibold text-red-800 dark:text-red-200">
                                Gagal Memproses
                            </p>
                            <p className="text-sm text-red-700 dark:text-red-300 mt-0.5">
                                {error}
                            </p>
                        </div>
                    </div>
                )}

                {/* Renewal Notice */}
                {isRenewal && (
                    <div
                        className="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl"
                        role="alert"
                        aria-live="polite"
                    >
                        <IconAlertTriangle
                            size={20}
                            className="text-amber-600 dark:text-amber-400 mt-0.5 shrink-0"
                        />
                        <div>
                            <p className="text-sm font-semibold text-amber-800 dark:text-amber-200">
                                {currentMembership.remaining_days <= 0
                                    ? "Membership Anda telah berakhir"
                                    : `Membership akan berakhir dalam ${currentMembership.remaining_days} hari`}
                            </p>
                            <p className="text-sm text-amber-700 dark:text-amber-300 mt-0.5">
                                Perpanjang membership Anda sekarang agar tidak terputus.
                            </p>
                        </div>
                    </div>
                )}

                {/* Registration Notice */}
                {!hasRegistration && (
                    <div
                        className="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl"
                        role="alert"
                    >
                        <IconTarget
                            size={20}
                            className="text-blue-600 dark:text-blue-400 mt-0.5 shrink-0"
                        />
                        <div>
                            <p className="text-sm font-semibold text-blue-800 dark:text-blue-200">
                                Pendaftaran Diperlukan
                            </p>
                            <p className="text-sm text-blue-700 dark:text-blue-300 mt-0.5">
                                Anda perlu melakukan pendaftaran member terlebih dahulu
                                sebelum dapat membeli paket bulanan atau keluarga.
                            </p>
                        </div>
                    </div>
                )}

                {/* Plans by Category */}
                {categoryOrder.map((category) => {
                    const categoryPlans = plans[category];
                    if (!categoryPlans || categoryPlans.length === 0) return null;

                    const needsRegistration = requiresRegistration(category) && !hasRegistration;

                    return (
                        <section key={category} aria-labelledby={`category-${category}`}>
                            <div className="mb-4">
                                <h2
                                    id={`category-${category}`}
                                    className="text-lg font-bold text-slate-900 dark:text-white"
                                >
                                    {categoryLabels[category] || category}
                                </h2>
                                <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                                    {categoryDescriptions[category]}
                                </p>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {categoryPlans.map((plan) => (
                                    <div
                                        key={plan.id}
                                        className={`bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col transition-shadow hover:shadow-md ${
                                            needsRegistration ? "opacity-60" : ""
                                        }`}
                                    >
                                        {/* Plan Card Content */}
                                        <div className="p-6 flex-1 flex flex-col">
                                            <h3 className="font-semibold text-slate-900 dark:text-white">
                                                {plan.name}
                                            </h3>

                                            {/* Price */}
                                            <p className="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-3">
                                                {formatRupiah(plan.price)}
                                            </p>

                                            {/* Session Quota & Duration */}
                                            {plan.session_quota > 0 && (
                                                <p className="text-sm text-slate-600 dark:text-slate-400 mt-2">
                                                    <span className="font-medium">{plan.session_quota} sesi</span>
                                                    {plan.duration_days > 0 && (
                                                        <span> / {plan.duration_days} hari</span>
                                                    )}
                                                </p>
                                            )}

                                            {plan.session_quota === 0 && plan.category === "registration" && (
                                                <p className="text-sm text-slate-600 dark:text-slate-400 mt-2">
                                                    Biaya pendaftaran (satu kali)
                                                </p>
                                            )}

                                            {/* Description */}
                                            {plan.description && (
                                                <p className="text-sm text-slate-500 dark:text-slate-400 mt-3">
                                                    {plan.description}
                                                </p>
                                            )}

                                            {/* Features List */}
                                            <div className="mt-4 space-y-2 flex-1">
                                                {plan.equipment_provided && (
                                                    <div className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                                        <IconBow size={16} className="text-emerald-500 shrink-0" />
                                                        <span>Alat disediakan</span>
                                                    </div>
                                                )}
                                                {!plan.equipment_provided && plan.category !== "registration" && plan.category !== "trial" && (
                                                    <div className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                                        <IconBow size={16} className="text-slate-400 shrink-0" />
                                                        <span>Bawa alat sendiri</span>
                                                    </div>
                                                )}
                                                {plan.family_members > 1 && (
                                                    <div className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                                        <IconUsers size={16} className="text-emerald-500 shrink-0" />
                                                        <span>{plan.family_members} anggota keluarga</span>
                                                    </div>
                                                )}
                                                {plan.duration_days > 0 && (
                                                    <div className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                                        <IconCheck size={16} className="text-emerald-500 shrink-0" />
                                                        <span>Berlaku {plan.duration_days} hari</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        {/* Purchase Button */}
                                        <div className="px-6 pb-6">
                                            {needsRegistration ? (
                                                <p className="text-xs text-center text-slate-500 dark:text-slate-400 italic">
                                                    Daftar member terlebih dahulu
                                                </p>
                                            ) : (
                                                <button
                                                    type="button"
                                                    onClick={() => handlePurchase(plan.id)}
                                                    disabled={purchasing !== null}
                                                    className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition-colors"
                                                    aria-label={`Beli paket ${plan.name}`}
                                                >
                                                    {purchasing === plan.id ? (
                                                        <>
                                                            <svg
                                                                className="animate-spin h-4 w-4"
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                fill="none"
                                                                viewBox="0 0 24 24"
                                                                aria-hidden="true"
                                                            >
                                                                <circle
                                                                    className="opacity-25"
                                                                    cx="12"
                                                                    cy="12"
                                                                    r="10"
                                                                    stroke="currentColor"
                                                                    strokeWidth="4"
                                                                />
                                                                <path
                                                                    className="opacity-75"
                                                                    fill="currentColor"
                                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                                />
                                                            </svg>
                                                            <span>Memproses...</span>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <IconShoppingCart size={16} />
                                                            <span>Pilih Paket</span>
                                                        </>
                                                    )}
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>
                    );
                })}

                {/* Empty State */}
                {Object.keys(plans).length === 0 && (
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center">
                        <div className="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4">
                            <IconTarget size={32} className="text-slate-400 dark:text-slate-500" />
                        </div>
                        <h3 className="text-lg font-bold text-slate-900 dark:text-white mb-2">
                            Tidak Ada Paket Tersedia
                        </h3>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            Tidak ada paket membership yang tersedia saat ini.
                            Silakan coba lagi nanti.
                        </p>
                    </div>
                )}

                {/* Mobile Back Link */}
                <div className="sm:hidden">
                    <Link
                        href={route("customer.membership")}
                        className="flex items-center justify-center gap-2 w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                    >
                        <IconArrowLeft size={18} />
                        Kembali ke Membership
                    </Link>
                </div>
            </div>
        </>
    );
};

Plans.layout = (page) => <CustomerLayout children={page} />;

export default Plans;
