import React, { useState } from "react";
import { Link, usePage, router } from "@inertiajs/react";
import { Toaster } from "react-hot-toast";
import {
    IconHome,
    IconReceipt,
    IconUser,
    IconLogout,
    IconMenu2,
    IconX,
    IconShoppingCart,
} from "@tabler/icons-react";

export default function CustomerLayout({ children }) {
    const { customerAuth, storeProfile } = usePage().props;
    const { url } = usePage();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const navigation = [
        { title: "Dashboard", href: route("customer.dashboard"), icon: IconHome, active: url === "/customer" },
        { title: "Transaksi Saya", href: route("customer.transactions.index"), icon: IconReceipt, active: url.startsWith("/customer/transactions") },
        { title: "Profil", href: route("customer.profile"), icon: IconUser, active: url.startsWith("/customer/profile") },
    ];

    const handleLogout = (e) => {
        e.preventDefault();
        router.post(route("customer.logout"));
    };

    return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
            {/* Navbar */}
            <nav className="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-slate-200 dark:border-slate-800">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between h-16">
                        {/* Logo */}
                        <Link href={route("customer.dashboard")} className="flex items-center gap-3">
                            {storeProfile?.logo ? (
                                <img src={storeProfile.logo} alt={storeProfile.name} className="w-8 h-8 rounded-lg object-cover" />
                            ) : (
                                <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                                    <IconShoppingCart size={18} className="text-white" />
                                </div>
                            )}
                            <span className="text-lg font-bold text-slate-900 dark:text-white hidden sm:block">
                                {storeProfile?.name || "Portal Pelanggan"}
                            </span>
                        </Link>

                        {/* Desktop Nav */}
                        <div className="hidden md:flex items-center gap-1">
                            {navigation.map((item) => (
                                <Link
                                    key={item.title}
                                    href={item.href}
                                    className={`flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all ${
                                        item.active
                                            ? "bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400"
                                            : "text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                                    }`}
                                >
                                    <item.icon size={18} />
                                    {item.title}
                                </Link>
                            ))}
                        </div>

                        {/* User Menu */}
                        <div className="flex items-center gap-3">
                            <div className="hidden sm:block text-right">
                                <p className="text-sm font-semibold text-slate-900 dark:text-white">
                                    {customerAuth?.name || "Pelanggan"}
                                </p>
                                <p className="text-xs text-slate-500 dark:text-slate-400">
                                    {customerAuth?.no_telp}
                                </p>
                            </div>
                            <button
                                onClick={handleLogout}
                                className="hidden md:flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/50 dark:hover:text-red-400 transition-all"
                            >
                                <IconLogout size={18} />
                            </button>
                            <button
                                onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                                className="md:hidden p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                            >
                                {mobileMenuOpen ? <IconX size={20} /> : <IconMenu2 size={20} />}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Mobile Menu */}
                {mobileMenuOpen && (
                    <div className="md:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 animate-slide-down">
                        <div className="px-4 py-3 space-y-1">
                            {navigation.map((item) => (
                                <Link
                                    key={item.title}
                                    href={item.href}
                                    onClick={() => setMobileMenuOpen(false)}
                                    className={`flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all ${
                                        item.active
                                            ? "bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400"
                                            : "text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                                    }`}
                                >
                                    <item.icon size={18} />
                                    {item.title}
                                </Link>
                            ))}
                            <button
                                onClick={handleLogout}
                                className="flex items-center gap-3 w-full px-4 py-3 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 transition-all"
                            >
                                <IconLogout size={18} />
                                Keluar
                            </button>
                        </div>
                    </div>
                )}
            </nav>

            {/* Content */}
            <main className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {children}
            </main>

            <Toaster position="top-right" />
        </div>
    );
}
