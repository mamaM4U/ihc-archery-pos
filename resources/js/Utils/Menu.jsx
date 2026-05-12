import { usePage } from "@inertiajs/react";
import {
    IconArrowBackUp,
    IconBooks,
    IconBox,
    IconCategory,
    IconChartArrowsVertical,
    IconChartBarPopular,
    IconChartInfographic,
    IconCirclePlus,
    IconClockHour6,
    IconCreditCard,
    IconFileCertificate,
    IconFileDescription,
    IconFolder,
    IconLayout2,
    IconBuildingStore,
    IconSchool,
    IconShoppingCart,
    IconTable,
    IconUserBolt,
    IconUserShield,
    IconUserSquare,
    IconUsers,
    IconUsersPlus,
    IconFileInvoice,
    IconBuildingWarehouse,
    IconCurrencyDollar,
    IconWallet,
    IconFileSearch,
} from "@tabler/icons-react";
import hasAnyPermission from "./Permission";
import React from "react";

export default function Menu() {
    // define use page
    const { url } = usePage();

    // define menu navigations
    const menuNavigation = [
        {
            title: "Overview",
            details: [
                {
                    title: "Dashboard",
                    href: route("dashboard"),
                    active: url === "/dashboard" ? true : false, // Update comparison here
                    icon: <IconLayout2 size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["dashboard-access"]),
                },
            ],
        },
        {
            title: "Data Management",
            details: [
                {
                    title: "Kategori",
                    href: route("categories.index"),
                    active: url === "/dashboard/categories" ? true : false,
                    icon: <IconFolder size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["categories-access"]),
                },
                {
                    title: "Produk",
                    href: route("products.index"),
                    active: url === "/dashboard/products" ? true : false,
                    icon: <IconBox size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["products-access"]),
                },
                {
                    title: "Stok",
                    icon: <IconBuildingWarehouse size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission([
                        "stock-opnames-access",
                        "stock-mutations-access",
                        "purchases-access",
                        "purchase-returns-access",
                    ]),
                    subdetails: [
                        {
                            title: "Stock Opname",
                            href: route("stock-opnames.index"),
                            active: url.startsWith("/dashboard/stock-opnames"),
                            icon: <IconFileDescription size={20} strokeWidth={1.5} />,
                            permissions: hasAnyPermission(["stock-opnames-access"]),
                        },
                        {
                            title: "Mutasi Stok",
                            href: route("stock-mutations.index"),
                            active: url.startsWith("/dashboard/stock-mutations"),
                            icon: <IconChartArrowsVertical size={20} strokeWidth={1.5} />,
                            permissions: hasAnyPermission(["stock-mutations-access"]),
                        },
                        {
                            title: "Pembelian (Barang Masuk)",
                            href: route("purchases.index"),
                            active: url.startsWith("/dashboard/purchases"),
                            icon: <IconShoppingCart size={20} strokeWidth={1.5} />,
                            permissions: hasAnyPermission(["purchases-access"]),
                        },
                        {
                            title: "Retur Pembelian",
                            href: route("purchase-returns.index"),
                            active: url.startsWith("/dashboard/purchase-returns"),
                            icon: <IconArrowBackUp size={20} strokeWidth={1.5} />,
                            permissions: hasAnyPermission(["purchase-returns-access"]),
                        },
                    ],
                },
                {
                    title: "Audit Log",
                    href: route("audit-logs.index"),
                    active: url.startsWith("/dashboard/audit-logs"),
                    icon: <IconFileSearch size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["audit-logs-access"]),
                },
                {
                    title: "Shift Kasir",
                    href: route("cashier-shifts.index"),
                    active: url.startsWith("/dashboard/cashier-shifts"),
                    icon: <IconWallet size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["cashier-shifts-access"]),
                },
                {
                    title: "Pelanggan",
                    href: route("customers.index"),
                    active: url === "/dashboard/customers" ? true : false,
                    icon: <IconUsersPlus size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["customers-access"]),
                },
            ],
        },
        {
            title: "Transaksi",
            details: [
                {
                    title: "Transaksi",
                    href: route("transactions.index"),
                    active: url === "/dashboard/transactions" ? true : false,
                    icon: <IconShoppingCart size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["transactions-access"]),
                },
                {
                    title: "Riwayat Transaksi",
                    href: route("transactions.history"),
                    active:
                        url === "/dashboard/transactions/history"
                            ? true
                            : false,
                    icon: <IconClockHour6 size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["transactions-access"]),
                },
                {
                    title: "Retur Penjualan",
                    href: route("sales-returns.index"),
                    active: url.startsWith("/dashboard/sales-returns"),
                    icon: <IconFileCertificate size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["sales-returns-access"]),
                },
                {
                    title: "Nota Barang (Piutang)",
                    href: route("receivables.index"),
                    active: url.startsWith("/dashboard/receivables"),
                    icon: <IconFileInvoice size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["receivables-access"]),
                },
                {
                    title: "Supplier",
                    href: route("suppliers.index"),
                    active: url.startsWith("/dashboard/suppliers"),
                    icon: <IconUserBolt size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["suppliers-access"]),
                },
                {
                    title: "Hutang Supplier",
                    href: route("payables.index"),
                    active: url.startsWith("/dashboard/payables"),
                    icon: <IconCurrencyDollar size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["payables-access"]),
                },
            ],
        },
        {
            title: "Laporan",
            details: [
                {
                    title: "Laporan Penjualan",
                    href: route("reports.sales.index"),
                    active: url.startsWith("/dashboard/reports/sales"),
                    icon: (
                        <IconChartArrowsVertical size={20} strokeWidth={1.5} />
                    ),
                    permissions: hasAnyPermission(["reports-access"]),
                },
                {
                    title: "Laporan Keuntungan",
                    href: route("reports.profits.index"),
                    active: url.startsWith("/dashboard/reports/profits"),
                    icon: <IconChartBarPopular size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["profits-access"]),
                },
            ],
        },
        {
            title: "User Management",
            details: [
                {
                    title: "Hak Akses",
                    href: route("permissions.index"),
                    active: url === "/dashboard/permissions" ? true : false, // Update comparison here
                    icon: <IconUserBolt size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["permissions-access"]),
                },
                {
                    title: "Akses Group",
                    href: route("roles.index"),
                    active: url === "/dashboard/roles" ? true : false, // Update comparison here
                    icon: <IconUserShield size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["roles-access"]),
                },
                {
                    title: "Pengguna",
                    icon: <IconUsers size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["users-access"]),
                    subdetails: [
                        {
                            title: "Data Pengguna",
                            href: route("users.index"),
                            icon: <IconTable size={20} strokeWidth={1.5} />,
                            active: url === "/dashboard/users" ? true : false,
                            permissions: hasAnyPermission(["users-access"]),
                        },
                        {
                            title: "Tambah Data Pengguna",
                            href: route("users.create"),
                            icon: (
                                <IconCirclePlus size={20} strokeWidth={1.5} />
                            ),
                            active:
                                url === "/dashboard/users/create"
                                    ? true
                                    : false,
                            permissions: hasAnyPermission(["users-create"]),
                        },
                    ],
                },
            ],
        },
        {
            title: "Pengaturan",
            details: [
                {
                    title: "Payment Gateway",
                    href: route("settings.payments.edit"),
                    active: url === "/dashboard/settings/payments",
                    icon: <IconCreditCard size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["payment-settings-access"]),
                },
                {
                    title: "Profil Toko",
                    href: route("settings.store"),
                    active: url === "/dashboard/settings/store",
                    icon: <IconBuildingStore size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["dashboard-access"]),
                },
                {
                    title: "Rekening Bank",
                    href: route("settings.bank-accounts.index"),
                    active: url === "/dashboard/settings/bank-accounts",
                    icon: <IconCreditCard size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["payment-settings-access"]),
                },
                {
                    title: "Target Penjualan",
                    href: route("settings.target"),
                    active: url === "/dashboard/settings/target",
                    icon: <IconChartInfographic size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["dashboard-access"]),
                },
            ],
        },
    ];

    return menuNavigation;
}
