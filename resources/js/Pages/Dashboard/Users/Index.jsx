import DashboardLayout from "@/Layouts/DashboardLayout";
import React, { useState, useEffect } from "react";
import { Head, useForm, usePage, Link, router } from "@inertiajs/react";
import Button from "@/Components/Dashboard/Button";
import {
    IconDatabaseOff,
    IconCirclePlus,
    IconTrash,
    IconPencilCog,
    IconShield,
    IconMail,
    IconLayoutGrid,
    IconList,
    IconPhone,
    IconChecks,
    IconUsers,
    IconFilter,
    IconUserCheck,
    IconUserX,
    IconLink,
} from "@tabler/icons-react";
import Search from "@/Components/Dashboard/Search";
import Table from "@/Components/Dashboard/Table";
import Checkbox from "@/Components/Dashboard/Checkbox";
import Pagination from "@/Components/Dashboard/Pagination";
import Swal from "sweetalert2";

// User Card for Grid View
function UserCard({ user, isSelected, onSelect, onDelete }) {
    const avatarUrl = user.avatar;
    const initial =
        user.name?.charAt(0)?.toUpperCase() ||
        user.email?.charAt(0)?.toUpperCase() ||
        "?";

    const getRoleBadge = (role) => {
        switch (role) {
            case "admin":
                return "bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800";
            case "coach":
                return "bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800";
            case "guardian":
                return "bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800";
            case "member":
                return "bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800";
            default:
                return "bg-slate-100 text-slate-700 dark:bg-slate-900/40 dark:text-slate-300 border border-slate-200 dark:border-slate-800";
        }
    };

    return (
        <div
            className={`
            group bg-white dark:bg-slate-900 rounded-2xl border-2 transition-all duration-300 hover:shadow-lg
            ${
                isSelected
                    ? "border-primary-500 dark:border-primary-600 shadow-md"
                    : "border-slate-200 dark:border-slate-800 hover:border-primary-300 dark:hover:border-primary-700"
            }
            overflow-hidden
        `}
        >
            {/* Header with checkbox */}
            <div className="p-4 flex items-start justify-between">
                <div className="flex items-center gap-3">
                    <div className="relative">
                        <div className="w-12 h-12 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-lg font-bold overflow-hidden shadow-inner">
                            {avatarUrl ? (
                                <img
                                    src={avatarUrl}
                                    alt={user.name}
                                    className="w-full h-full object-cover"
                                />
                            ) : (
                                initial
                            )}
                        </div>
                        <span
                            className={`absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white dark:border-slate-900 ${
                                user.is_active ? "bg-emerald-500" : "bg-slate-400"
                            }`}
                        />
                    </div>
                    <div>
                        <h3 className="text-base font-semibold text-slate-800 dark:text-slate-200 line-clamp-1">
                            {user.name}
                        </h3>
                        <p className="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-0.5">
                            <IconMail size={12} className="shrink-0" />
                            <span className="truncate max-w-[140px]">{user.email}</span>
                        </p>
                        {user.phone && (
                            <p className="text-[11px] text-slate-400 dark:text-slate-500 flex items-center gap-1 mt-0.5">
                                <IconPhone size={11} className="shrink-0" />
                                <span>{user.phone}</span>
                            </p>
                        )}
                    </div>
                </div>
                <Checkbox
                    value={user.id}
                    onChange={onSelect}
                    checked={isSelected}
                />
            </div>

            {/* Role & Status info */}
            <div className="px-4 pb-4 flex items-center justify-between gap-2">
                <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full capitalize ${getRoleBadge(user.role)}`}>
                    <IconShield size={12} />
                    {user.role}
                </span>
                
                <span className={`text-xs font-medium flex items-center gap-1 ${
                    user.is_active ? "text-emerald-600 dark:text-emerald-400" : "text-slate-500"
                }`}>
                    {user.is_active ? (
                        <>
                            <IconUserCheck size={14} />
                            <span>Aktif</span>
                        </>
                    ) : (
                        <>
                            <IconUserX size={14} />
                            <span>Nonaktif</span>
                        </>
                    )}
                </span>
            </div>

            {/* Actions */}
            <div className="flex border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                <Link
                    href={route("users.edit", user.id)}
                    className="flex-1 flex items-center justify-center gap-1.5 py-3 text-warning-600 dark:text-warning-400 hover:bg-warning-50/50 dark:hover:bg-warning-950/20 text-sm font-medium transition-colors"
                >
                    <IconPencilCog size={16} />
                    <span>Edit</span>
                </Link>
                <div className="w-px bg-slate-100 dark:bg-slate-800" />
                <button
                    onClick={() => onDelete(user.id)}
                    className="flex-1 flex items-center justify-center gap-1.5 py-3 text-danger-600 dark:text-danger-400 hover:bg-danger-50/50 dark:hover:bg-danger-950/20 text-sm font-medium transition-colors"
                >
                    <IconTrash size={16} />
                    <span>Hapus</span>
                </button>
            </div>
        </div>
    );
}

export default function Index() {
    const { users, filters } = usePage().props;
    const [viewMode, setViewMode] = useState(() => {
        if (typeof window !== "undefined") {
            return localStorage.getItem("users_view_mode") || "grid";
        }
        return "grid";
    });

    // States for local filter inputs
    const [searchVal, setSearchVal] = useState(filters.search || "");
    const [roleVal, setRoleVal] = useState(filters.role || "");
    const [statusVal, setStatusVal] = useState(filters.status || "");

    const {
        data,
        setData,
        delete: destroy,
        reset,
    } = useForm({
        selectedUser: [],
    });

    useEffect(() => {
        if (typeof window !== "undefined") {
            localStorage.setItem("users_view_mode", viewMode);
        }
    }, [viewMode]);

    // Apply filters function
    const applyFilters = () => {
        router.get(
            route("users.index"),
            {
                search: searchVal,
                role: roleVal,
                status: statusVal,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    // Trigger filter update on dropdown changes
    useEffect(() => {
        applyFilters();
    }, [roleVal, statusVal]);

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        applyFilters();
    };

    const setSelectedUser = (e) => {
        let items = [...data.selectedUser];
        const val = e.target.value.toString();
        if (items.includes(val)) {
            items = items.filter((id) => id !== val);
        } else {
            items.push(val);
        }
        setData("selectedUser", items);
    };

    const deleteData = async (id) => {
        const isBulk = Array.isArray(id);
        Swal.fire({
            title: isBulk ? "Hapus Pengguna Terpilih?" : "Hapus Pengguna?",
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Hapus!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                const deleteId = isBulk ? id.join(",") : id;
                destroy(route("users.destroy", deleteId), {
                    onSuccess: () => {
                        Swal.fire({
                            title: "Berhasil!",
                            text: "Data berhasil dihapus!",
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1500,
                        });
                        setData("selectedUser", []);
                    },
                });
            }
        });
    };

    const getRoleBadge = (role) => {
        switch (role) {
            case "admin":
                return "bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800";
            case "coach":
                return "bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800";
            case "guardian":
                return "bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800";
            case "member":
                return "bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800";
            default:
                return "bg-slate-100 text-slate-700 dark:bg-slate-900/40 dark:text-slate-300 border border-slate-200 dark:border-slate-800";
        }
    };

    return (
        <>
            <Head title="Manajemen Pengguna" />

            {/* Header */}
            <div className="mb-6">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <IconUsers className="text-primary-500" size={28} />
                            Pengguna
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            Kelola pengguna sistem, peran, dan penugasan hubungan (Coach & Guardian).
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route("users.assignments")}
                            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-900 text-sm font-semibold shadow-sm transition-colors"
                        >
                            <IconLink size={18} />
                            <span>Penugasan Hubungan</span>
                        </Link>
                        {data.selectedUser.length > 0 && (
                            <Button
                                type={"bulk"}
                                icon={<IconTrash size={18} />}
                                className={
                                    "bg-danger-500 hover:bg-danger-600 text-white"
                                }
                                label={`Hapus ${data.selectedUser.length}`}
                                onClick={() => deleteData(data.selectedUser)}
                            />
                        )}
                        <Button
                            type={"link"}
                            href={route("users.create")}
                            icon={
                                <IconCirclePlus size={18} strokeWidth={1.5} />
                            }
                            className={
                                "bg-primary-500 hover:bg-primary-600 text-white shadow-lg shadow-primary-500/30"
                            }
                            label={"Tambah Pengguna"}
                        />
                    </div>
                </div>
            </div>

            {/* Toolbar & Filters */}
            <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 mb-6 shadow-sm">
                <div className="flex flex-col lg:flex-row gap-4 items-stretch lg:items-center justify-between">
                    {/* Search Form */}
                    <form onSubmit={handleSearchSubmit} className="flex-1 max-w-md">
                        <div className="relative">
                            <input
                                type="text"
                                value={searchVal}
                                onChange={(e) => setSearchVal(e.target.value)}
                                className="py-2.5 px-4 pr-11 block w-full rounded-xl text-sm border focus:outline-none focus:ring-1 focus:ring-primary-500 text-slate-700 bg-slate-50 border-slate-200 focus:bg-white dark:text-slate-200 dark:bg-slate-950 dark:border-slate-850 focus:border-primary-500 dark:focus:ring-primary-500 transition-colors"
                                placeholder="Cari nama, email, atau telepon..."
                            />
                            <button
                                type="submit"
                                className="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-650 dark:hover:text-slate-205"
                            >
                                <IconFilter size={18} />
                            </button>
                        </div>
                    </form>

                    {/* Filter Dropdowns */}
                    <div className="flex flex-wrap items-center gap-3">
                        {/* Role Select */}
                        <div className="min-w-[140px] flex-1 sm:flex-none">
                            <select
                                value={roleVal}
                                onChange={(e) => setRoleVal(e.target.value)}
                                className="block w-full py-2.5 px-3 border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-955 text-slate-700 dark:text-slate-300 rounded-xl text-sm focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                            >
                                <option value="">Semua Role</option>
                                <option value="admin">Admin</option>
                                <option value="coach">Coach</option>
                                <option value="guardian">Guardian</option>
                                <option value="member">Member</option>
                            </select>
                        </div>

                        {/* Status Select */}
                        <div className="min-w-[140px] flex-1 sm:flex-none">
                            <select
                                value={statusVal}
                                onChange={(e) => setStatusVal(e.target.value)}
                                className="block w-full py-2.5 px-3 border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-955 text-slate-700 dark:text-slate-300 rounded-xl text-sm focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                            >
                                <option value="">Semua Status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </div>

                        {/* Reset Filters button if active */}
                        {(searchVal || roleVal || statusVal) && (
                            <button
                                type="button"
                                onClick={() => {
                                    setSearchVal("");
                                    setRoleVal("");
                                    setStatusVal("");
                                    router.get(route("users.index"), {}, { preserveState: true });
                                }}
                                className="text-sm font-medium text-primary-500 hover:text-primary-600 px-2 py-1.5"
                            >
                                Reset Filter
                            </button>
                        )}

                        <div className="h-8 w-px bg-slate-200 dark:bg-slate-800 hidden sm:block mx-1" />

                        {/* Layout Toggle */}
                        <div className="flex items-center gap-1 border border-slate-200 dark:border-slate-800 p-1 rounded-xl bg-slate-50 dark:bg-slate-955">
                            <button
                                onClick={() => setViewMode("grid")}
                                className={`p-1.5 rounded-lg transition-colors ${
                                    viewMode === "grid"
                                        ? "bg-white dark:bg-slate-800 text-primary-600 dark:text-primary-400 shadow-sm"
                                        : "text-slate-400 hover:text-slate-650"
                                }`}
                                title="Grid View"
                            >
                                <IconLayoutGrid size={18} />
                            </button>
                            <button
                                onClick={() => setViewMode("list")}
                                className={`p-1.5 rounded-lg transition-colors ${
                                    viewMode === "list"
                                        ? "bg-white dark:bg-slate-800 text-primary-600 dark:text-primary-400 shadow-sm"
                                        : "text-slate-400 hover:text-slate-655"
                                }`}
                                title="List View"
                            >
                                <IconList size={18} />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Content */}
            {users.data.length > 0 ? (
                viewMode === "grid" ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {users.data.map((user) => (
                            <UserCard
                                key={user.id}
                                user={user}
                                isSelected={data.selectedUser.includes(
                                    user.id.toString()
                                )}
                                onSelect={setSelectedUser}
                                onDelete={deleteData}
                            />
                        ))}
                    </div>
                ) : (
                    <Table.Card title={"Daftar Pengguna"}>
                        <Table>
                            <Table.Thead>
                                <tr>
                                    <Table.Th className={"w-10"}>
                                        <Checkbox
                                            onChange={(e) => {
                                                const allUserIds =
                                                    users.data.map((user) =>
                                                        user.id.toString()
                                                    );
                                                setData(
                                                    "selectedUser",
                                                    e.target.checked
                                                        ? allUserIds
                                                        : []
                                                );
                                            }}
                                            checked={
                                                data.selectedUser.length ===
                                                    users.data.length &&
                                                users.data.length > 0
                                            }
                                        />
                                    </Table.Th>
                                    <Table.Th className={"w-10 text-center"}>No</Table.Th>
                                    <Table.Th>Pengguna</Table.Th>
                                    <Table.Th>Role / Peran</Table.Th>
                                    <Table.Th>Telepon</Table.Th>
                                    <Table.Th>Status</Table.Th>
                                    <Table.Th className="w-24"></Table.Th>
                                </tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {users.data.map((user, i) => (
                                    <tr
                                        className="hover:bg-slate-50/80 dark:hover:bg-slate-800/30 transition-colors"
                                        key={user.id}
                                    >
                                        <Table.Td>
                                            <Checkbox
                                                value={user.id}
                                                onChange={setSelectedUser}
                                                checked={data.selectedUser.includes(
                                                    user.id.toString()
                                                )}
                                            />
                                        </Table.Td>
                                        <Table.Td className={"text-center font-medium"}>
                                            {i + 1 + (users.current_page - 1) * users.per_page}
                                        </Table.Td>
                                        <Table.Td>
                                            <div className="flex items-center gap-3">
                                                <div className="relative shrink-0">
                                                    <div className="w-9 h-9 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-sm font-bold overflow-hidden shadow-inner">
                                                        {user.avatar ? (
                                                            <img
                                                                src={user.avatar}
                                                                alt={user.name}
                                                                className="w-full h-full object-cover"
                                                            />
                                                        ) : (
                                                            user.name
                                                                .charAt(0)
                                                                .toUpperCase()
                                                        )}
                                                    </div>
                                                </div>
                                                <div>
                                                    <p className="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                        {user.name}
                                                    </p>
                                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                                        {user.email}
                                                    </p>
                                                </div>
                                            </div>
                                        </Table.Td>
                                        <Table.Td>
                                            <span className={`inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full capitalize ${getRoleBadge(user.role)}`}>
                                                {user.role}
                                            </span>
                                        </Table.Td>
                                        <Table.Td className="text-slate-650 dark:text-slate-350 text-sm">
                                            {user.phone || "-"}
                                        </Table.Td>
                                        <Table.Td>
                                            <span className={`inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-0.5 rounded-full ${
                                                user.is_active
                                                    ? "bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-250 dark:border-emerald-900"
                                                    : "bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700"
                                            }`}>
                                                <span className={`w-1.5 h-1.5 rounded-full ${user.is_active ? "bg-emerald-500" : "bg-slate-400"}`} />
                                                {user.is_active ? "Aktif" : "Nonaktif"}
                                            </span>
                                        </Table.Td>
                                        <Table.Td>
                                            <div className="flex gap-2 justify-end">
                                                <Button
                                                    type={"edit"}
                                                    icon={
                                                        <IconPencilCog
                                                            size={16}
                                                            strokeWidth={1.5}
                                                        />
                                                    }
                                                    className={
                                                        "border bg-warning-100 border-warning-200 text-warning-600 hover:bg-warning-200 dark:bg-warning-900/50 dark:border-warning-800 dark:text-warning-400"
                                                    }
                                                    href={route(
                                                        "users.edit",
                                                        user.id
                                                    )}
                                                />
                                                <Button
                                                    type={"delete"}
                                                    icon={
                                                        <IconTrash
                                                            size={16}
                                                            strokeWidth={1.5}
                                                        />
                                                    }
                                                    className={
                                                        "border bg-danger-100 border-danger-200 text-danger-600 hover:bg-danger-200 dark:bg-danger-900/50 dark:border-danger-800 dark:text-danger-400"
                                                    }
                                                    onClick={() => deleteData(user.id)}
                                                />
                                            </div>
                                        </Table.Td>
                                    </tr>
                                ))}
                            </Table.Tbody>
                        </Table>
                    </Table.Card>
                )
            ) : (
                <div className="flex flex-col items-center justify-center py-16 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <div className="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                        <IconDatabaseOff
                            size={32}
                            className="text-slate-400"
                            strokeWidth={1.5}
                        />
                    </div>
                    <h3 className="text-lg font-medium text-slate-800 dark:text-slate-200 mb-1">
                        Belum Ada Pengguna
                    </h3>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mb-4 text-center max-w-sm">
                        Tidak ada pengguna yang cocok dengan kriteria filter pencarian Anda. Coba atur ulang filter.
                    </p>
                    <Button
                        type={"link"}
                        icon={<IconCirclePlus size={18} />}
                        className={
                            "bg-primary-500 hover:bg-primary-600 text-white"
                        }
                        label={"Tambah Pengguna"}
                        href={route("users.create")}
                    />
                </div>
            )}

            {users.last_page !== 1 && <Pagination links={users.links} />}
        </>
    );
}

Index.layout = (page) => <DashboardLayout children={page} />;
