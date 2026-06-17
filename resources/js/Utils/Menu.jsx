import { usePage } from "@inertiajs/react";
import {
    IconCirclePlus,
    IconLayout2,
    IconTable,
    IconUserBolt,
    IconUserShield,
    IconUsers,
    IconCalendar,
    IconClock,
    IconChecks,
    IconActivity,
    IconEye,
    IconLink,
} from "@tabler/icons-react";
import hasAnyPermission from "./Permission";
import React from "react";

/**
 * Bulletproof helper to get route URL or fallback.
 */
const getSafeRoute = (name, fallback = "#") => {
    try {
        return route(name);
    } catch (e) {
        return fallback;
    }
};

export default function Menu() {
    const { auth } = usePage().props;
    const url = usePage().url || "";
    const userRole = auth?.user?.role || "member";

    const menuNavigation = [];

    // 1. Overview Section (All Roles)
    menuNavigation.push({
        title: "Overview",
        details: [
            {
                title: "Dashboard",
                href: getSafeRoute("dashboard"),
                active: url === "/dashboard" ? true : false,
                icon: <IconLayout2 size={20} strokeWidth={1.5} />,
                permissions: hasAnyPermission(["dashboard-access"]),
            },
        ],
    });

    // 2. Admin Section
    if (userRole === "admin") {
        menuNavigation.push({
            title: "User Management",
            details: [
                {
                    title: "Hak Akses",
                    href: getSafeRoute("permissions.index"),
                    active: url === "/dashboard/permissions" ? true : false,
                    icon: <IconUserBolt size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["permissions-access"]),
                },
                {
                    title: "Akses Group",
                    href: getSafeRoute("roles.index"),
                    active: url === "/dashboard/roles" ? true : false,
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
                            href: getSafeRoute("users.index"),
                            icon: <IconTable size={20} strokeWidth={1.5} />,
                            active: url === "/dashboard/users" ? true : false,
                            permissions: hasAnyPermission(["users-access"]),
                        },
                        {
                            title: "Tambah Data Pengguna",
                            href: getSafeRoute("users.create"),
                            icon: <IconCirclePlus size={20} strokeWidth={1.5} />,
                            active: url === "/dashboard/users/create" ? true : false,
                            permissions: hasAnyPermission(["users-create"]),
                        },
                        {
                            title: "Penugasan Hubungan",
                            href: getSafeRoute("users.assignments"),
                            icon: <IconLink size={20} strokeWidth={1.5} />,
                            active: url === "/dashboard/users/assignments" ? true : false,
                            permissions: hasAnyPermission(["users-create"]),
                        },
                    ],
                },
            ],
        });
    }

    // 3. Coach & Admin Section
    if (userRole === "coach" || userRole === "admin") {
        menuNavigation.push({
            title: userRole === "admin" ? "Jadwal & Pelatihan" : "Pelatihan (Coach)",
            details: [
                {
                    title: "Template Jadwal",
                    href: getSafeRoute("templates.index", "/dashboard/templates"),
                    active: url.startsWith("/dashboard/templates") ? true : false,
                    icon: <IconCalendar size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["templates-access"]),
                },
                {
                    title: "Slot Latihan",
                    href: getSafeRoute("slots.index", "/dashboard/slots"),
                    active: url.startsWith("/dashboard/slots") ? true : false,
                    icon: <IconClock size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["slots-access"]),
                },
                {
                    title: "Persetujuan Booking",
                    href: getSafeRoute("bookings.index", "/dashboard/bookings"),
                    active: url.startsWith("/dashboard/bookings") ? true : false,
                    icon: <IconChecks size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["bookings-access"]),
                },
                {
                    title: "Daftar Atlet",
                    href: getSafeRoute("coach.members", "/dashboard/coach/members"),
                    active: url.startsWith("/dashboard/coach/members") ? true : false,
                    icon: <IconUsers size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["member-data-access"]),
                },
            ],
        });
    }

    // 4. Guardian Section
    if (userRole === "guardian") {
        menuNavigation.push({
            title: "Pengawasan (Wali)",
            details: [
                {
                    title: "Daftar Atlet",
                    href: getSafeRoute("guardian.members", "/dashboard/guardian/members"),
                    active: url.startsWith("/dashboard/guardian/members") ? true : false,
                    icon: <IconUsers size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["member-data-access"]),
                },
                {
                    title: "Persetujuan Jadwal",
                    href: getSafeRoute("guardian.bookings", "/dashboard/guardian/bookings"),
                    active: url.startsWith("/dashboard/guardian/bookings") ? true : false,
                    icon: <IconChecks size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["bookings-access"]),
                },
            ],
        });
    }

    // 5. Member Section
    if (userRole === "member") {
        menuNavigation.push({
            title: "Menu Atlet (Member)",
            details: [
                {
                    title: "Jadwal Tersedia",
                    href: getSafeRoute("member.schedule", "/dashboard/member/schedule"),
                    active: url.startsWith("/dashboard/member/schedule") ? true : false,
                    icon: <IconCalendar size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["bookings-access"]),
                },
                {
                    title: "Booking Saya",
                    href: getSafeRoute("member.bookings", "/dashboard/member/bookings"),
                    active: url.startsWith("/dashboard/member/bookings") ? true : false,
                    icon: <IconChecks size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["bookings-access"]),
                },
                {
                    title: "Progress Fisik",
                    href: getSafeRoute("member.data", "/dashboard/member/my-data"),
                    active: url.startsWith("/dashboard/member/my-data") ? true : false,
                    icon: <IconActivity size={20} strokeWidth={1.5} />,
                    permissions: hasAnyPermission(["member-data-access"]),
                },
            ],
        });
    }

    return menuNavigation;
}
