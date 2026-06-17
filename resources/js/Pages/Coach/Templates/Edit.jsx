import React, { useState } from "react";
import { Head, Link, useForm, usePage } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import {
    IconCalendarPlus,
    IconDeviceFloppy,
    IconArrowLeft,
    IconPlus,
    IconTrash,
    IconClock,
} from "@tabler/icons-react";
import Input from "@/Components/Dashboard/Input";
import TextArea from "@/Components/Dashboard/TextArea";
import InputSelect from "@/Components/Dashboard/InputSelect";
import toast from "react-hot-toast";

export default function Edit({ template, coaches }) {
    const { auth } = usePage().props;
    const isAdmin = auth.user.role === "admin";

    // Find initial coach object if admin
    const initialCoach = isAdmin
        ? coaches.find((c) => c.id === template.coach_id) || null
        : template.coach || { id: auth.user.id, name: auth.user.name };

    const [selectedCoach, setSelectedCoach] = useState(initialCoach);

    // Initial state for form pre-filled with template data
    const { data, setData, put, errors, processing } = useForm({
        coach_id: template.coach_id,
        template_name: template.template_name,
        booking_open_days: template.booking_open_days,
        is_active: template.is_active,
        notes: template.notes || "",
        slots: template.template_slots.length > 0
            ? template.template_slots.map((slot) => ({
                  day_of_week: slot.day_of_week,
                  session_name: slot.session_name,
                  start_time: slot.start_time,
                  end_time: slot.end_time,
                  location: slot.location,
                  max_capacity: slot.max_capacity,
                  duration_minutes: slot.duration_minutes,
              }))
            : [
                  {
                      day_of_week: 1,
                      session_name: "Pagi",
                      start_time: "08:00",
                      end_time: "10:00",
                      location: "Lapangan Utama",
                      max_capacity: 10,
                      duration_minutes: 120,
                  },
              ],
    });

    // Handle adding a new slot
    const addSlot = () => {
        setData("slots", [
            ...data.slots,
            {
                day_of_week: 1,
                session_name: "Pagi",
                start_time: "08:00",
                end_time: "10:00",
                location: "Lapangan Utama",
                max_capacity: 10,
                duration_minutes: 120,
            },
        ]);
    };

    // Handle removing a slot
    const removeSlot = (index) => {
        const newSlots = [...data.slots];
        newSlots.splice(index, 1);
        setData("slots", newSlots);
    };

    // Handle updating a specific field in a slot
    const updateSlotField = (index, field, value) => {
        const newSlots = [...data.slots];
        newSlots[index] = {
            ...newSlots[index],
            [field]: value,
        };

        // If start_time or end_time changes, recalculate duration_minutes
        if (field === "start_time" || field === "end_time") {
            const startTime = newSlots[index].start_time;
            const endTime = newSlots[index].end_time;

            if (startTime && endTime) {
                const [startH, startM] = startTime.split(":").map(Number);
                const [endH, endM] = endTime.split(":").map(Number);

                const startTotal = startH * 60 + startM;
                const endTotal = endH * 60 + endM;

                if (endTotal > startTotal) {
                    newSlots[index].duration_minutes = endTotal - startTotal;
                }
            }
        }

        setData("slots", newSlots);
    };

    const submit = (e) => {
        e.preventDefault();

        if (isAdmin && !data.coach_id) {
            toast.error("Silakan pilih Coach terlebih dahulu.");
            return;
        }

        if (data.slots.length === 0) {
            toast.error("Silakan tambahkan minimal satu sesi latihan.");
            return;
        }

        put(route("templates.update", template.id), {
            onSuccess: () => {
                toast.success("Template mingguan berhasil diperbarui");
            },
            onError: () => {
                toast.error("Gagal memperbarui template. Silakan periksa input Anda.");
            },
        });
    };

    const dayOptions = [
        { value: 1, label: "Senin" },
        { value: 2, label: "Selasa" },
        { value: 3, label: "Rabu" },
        { value: 4, label: "Kamis" },
        { value: 5, label: "Jumat" },
        { value: 6, label: "Sabtu" },
        { value: 0, label: "Minggu" },
    ];

    return (
        <>
            <Head title="Edit Template Jadwal" />

            <div className="mb-6">
                <Link
                    href={route("templates.index")}
                    className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-primary-600 mb-3 transition-colors"
                >
                    <IconArrowLeft size={16} />
                    Kembali ke Daftar Template
                </Link>
                <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <IconCalendarPlus size={28} className="text-primary-500" />
                    Edit Template Jadwal: {template.template_name}
                </h1>
                <p className="text-sm text-slate-500 mt-1">
                    Perbarui template mingguan beserta sesi-sesi latihannya. Perubahan akan disimpan secara transaksional.
                </p>
            </div>

            <form onSubmit={submit}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left: General Config */}
                    <div className="lg:col-span-1 space-y-6">
                        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
                            <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 border-b border-slate-100 dark:border-slate-800 pb-3">
                                Informasi Template
                            </h3>

                            {isAdmin ? (
                                <div className="space-y-1">
                                    <InputSelect
                                        label="Pilih Coach / Pelatih"
                                        placeholder="Cari & Pilih Coach"
                                        data={coaches}
                                        selected={selectedCoach}
                                        setSelected={(coach) => {
                                            setSelectedCoach(coach);
                                            setData("coach_id", coach ? coach.id : "");
                                        }}
                                        errors={errors.coach_id}
                                    />
                                </div>
                            ) : (
                                <Input
                                    type="text"
                                    label="Coach / Pelatih"
                                    value={template.coach?.name || auth.user.name}
                                    disabled
                                    readOnly
                                    className="bg-slate-100 dark:bg-slate-800 cursor-not-allowed"
                                />
                            )}

                            <Input
                                type="text"
                                label="Nama Template"
                                placeholder="Contoh: Jadwal Latihan Reguler"
                                value={data.template_name}
                                onChange={(e) => setData("template_name", e.target.value)}
                                errors={errors.template_name}
                                required
                            />

                            <Input
                                type="number"
                                label="Batas Akhir Booking (H-x Hari)"
                                placeholder="2"
                                min="0"
                                max="30"
                                value={data.booking_open_days}
                                onChange={(e) => setData("booking_open_days", e.target.value === "" ? "" : parseInt(e.target.value) || 0)}
                                errors={errors.booking_open_days}
                                required
                                helpText="Batas hari sebelum sesi latihan di mana atlet terakhir bisa melakukan booking (misal: jika diisi 2, maka sesi tanggal 17 terakhir dapat dibooking tanggal 15)."
                            />

                            <div className="flex flex-col gap-2">
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Status Template
                                </label>
                                <div className="flex items-center gap-3 mt-1">
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
                                        {data.is_active ? "Aktif (Utama)" : "Draft / Nonaktif"}
                                    </span>
                                </div>
                                <p className="text-xs text-slate-400 dark:text-slate-500 mt-1 leading-relaxed">
                                    Hanya 1 template yang boleh aktif per Coach. Mengaktifkan template ini akan otomatis menonaktifkan template aktif lainnya milik Coach yang bersangkutan.
                                </p>
                                {errors.is_active && (
                                    <p className="text-xs text-danger-500 mt-1">
                                        {errors.is_active}
                                    </p>
                                )}
                            </div>

                            <TextArea
                                label="Catatan / Keterangan"
                                placeholder="Masukkan informasi tambahan atau aturan latihan..."
                                value={data.notes}
                                onChange={(e) => setData("notes", e.target.value)}
                                errors={errors.notes}
                                rows={3}
                            />
                        </div>

                        {/* Submit Button card */}
                        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex justify-end gap-3">
                            <Link
                                href={route("templates.index")}
                                className="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium text-sm transition-colors"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary-500 hover:bg-primary-600 text-white font-medium text-sm transition-colors disabled:opacity-50 shadow-sm shadow-primary-500/10"
                            >
                                <IconDeviceFloppy size={18} />
                                {processing ? "Menyimpan..." : "Simpan Perubahan"}
                            </button>
                        </div>
                    </div>

                    {/* Right: Slots List */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
                            <div className="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3">
                                <div>
                                    <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                        Daftar Sesi Latihan Mingguan
                                    </h3>
                                    <p className="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                                        Tambahkan sesi-sesi latihan yang akan diulang setiap minggunya.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={addSlot}
                                    className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold text-xs transition-colors"
                                >
                                    <IconPlus size={14} />
                                    Tambah Sesi
                                </button>
                            </div>

                            {errors.slots && typeof errors.slots === "string" && (
                                <div className="p-3 bg-danger-50 dark:bg-danger-950/10 border border-danger-200/50 dark:border-danger-800/30 rounded-xl text-xs text-danger-600 dark:text-danger-400">
                                    {errors.slots}
                                </div>
                            )}

                            <div className="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                                {data.slots.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center p-12 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl text-center bg-slate-50/25 dark:bg-slate-900/20">
                                        <div className="p-3 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-full mb-4">
                                            <IconClock size={32} />
                                        </div>
                                        <h4 className="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1">
                                            Belum Ada Sesi Latihan
                                        </h4>
                                        <p className="text-xs text-slate-400 dark:text-slate-500 max-w-sm mb-4 leading-relaxed">
                                            Template mingguan memerlukan minimal satu sesi latihan. Klik tombol di bawah untuk menambahkan sesi pertama Anda.
                                        </p>
                                        <button
                                            type="button"
                                            onClick={addSlot}
                                            className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-semibold text-xs transition-colors shadow-sm shadow-teal-600/10"
                                        >
                                            <IconPlus size={16} />
                                            Tambah Sesi Pertama
                                        </button>
                                    </div>
                                ) : (
                                    data.slots.map((slot, index) => (
                                        <div
                                            key={index}
                                            className="relative p-5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 group hover:border-slate-300 dark:hover:border-slate-700 transition-all"
                                        >
                                            <div className="absolute top-4 right-4 flex items-center gap-2">
                                                <span className="text-xs text-slate-400 font-semibold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800">
                                                    Sesi #{index + 1}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() => removeSlot(index)}
                                                    className="p-1 rounded-lg text-slate-400 hover:text-danger-600 hover:bg-danger-50 dark:hover:bg-danger-950/10 transition-colors"
                                                    title="Hapus Sesi"
                                                >
                                                    <IconTrash size={16} />
                                                </button>
                                            </div>

                                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                                {/* Day selection */}
                                                <div className="flex flex-col gap-2">
                                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                                        Hari Latihan
                                                    </label>
                                                    <select
                                                        value={slot.day_of_week}
                                                        onChange={(e) => updateSlotField(index, "day_of_week", parseInt(e.target.value))}
                                                        className="w-full h-11 px-4 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200"
                                                    >
                                                        {dayOptions.map((opt) => (
                                                            <option key={opt.value} value={opt.value}>
                                                                {opt.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    {errors[`slots.${index}.day_of_week`] && (
                                                        <small className="text-xs text-danger-500">{errors[`slots.${index}.day_of_week`]}</small>
                                                    )}
                                                </div>

                                                {/* Session Name selection */}
                                                <div className="flex flex-col gap-2">
                                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                                        Nama Sesi / Waktu Hari
                                                    </label>
                                                    <select
                                                        value={slot.session_name}
                                                        onChange={(e) => updateSlotField(index, "session_name", e.target.value)}
                                                        className="w-full h-11 px-4 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200"
                                                    >
                                                        <option value="Pagi">Pagi</option>
                                                        <option value="Siang">Siang</option>
                                                        <option value="Sore">Sore</option>
                                                        <option value="Malam">Malam</option>
                                                    </select>
                                                    {errors[`slots.${index}.session_name`] && (
                                                        <small className="text-xs text-danger-500">{errors[`slots.${index}.session_name`]}</small>
                                                    )}
                                                </div>

                                                {/* Location input */}
                                                <div className="flex flex-col gap-2">
                                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                                        Lokasi Sesi
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={slot.location}
                                                        placeholder="Contoh: Lapangan A"
                                                        onChange={(e) => updateSlotField(index, "location", e.target.value)}
                                                        className="w-full h-11 px-4 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200"
                                                        required
                                                    />
                                                    {errors[`slots.${index}.location`] && (
                                                        <small className="text-xs text-danger-500">{errors[`slots.${index}.location`]}</small>
                                                    )}
                                                </div>

                                                {/* Start Time input */}
                                                <div className="flex flex-col gap-2">
                                                    <label className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                                        Jam Mulai
                                                    </label>
                                                    <input
                                                        type="time"
                                                        value={slot.start_time}
                                                        onChange={(e) => updateSlotField(index, "start_time", e.target.value)}
                                                        className="w-full h-11 px-4 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200"
                                                        required
                                                    />
                                                    {errors[`slots.${index}.start_time`] && (
                                                        <small className="text-xs text-danger-500">{errors[`slots.${index}.start_time`]}</small>
                                                    )}
                                                </div>

                                                {/* End Time input */}
                                                <div className="flex flex-col gap-2">
                                                    <label className="text-sm font-medium text-slate-750 dark:text-slate-300">
                                                        Jam Selesai
                                                    </label>
                                                    <input
                                                        type="time"
                                                        value={slot.end_time}
                                                        onChange={(e) => updateSlotField(index, "end_time", e.target.value)}
                                                        className="w-full h-11 px-4 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200"
                                                        required
                                                    />
                                                    {errors[`slots.${index}.end_time`] && (
                                                        <small className="text-xs text-danger-500">{errors[`slots.${index}.end_time`]}</small>
                                                    )}
                                                </div>

                                                {/* Max Capacity input */}
                                                <div className="flex flex-col gap-2">
                                                    <label className="text-sm font-medium text-slate-750 dark:text-slate-300">
                                                        Kapasitas Maksimal (Atlet)
                                                    </label>
                                                    <input
                                                        type="number"
                                                        value={slot.max_capacity}
                                                        min="1"
                                                        onChange={(e) => updateSlotField(index, "max_capacity", e.target.value === "" ? "" : parseInt(e.target.value) || 0)}
                                                        className="w-full h-11 px-4 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200"
                                                        required
                                                    />
                                                    {errors[`slots.${index}.max_capacity`] && (
                                                        <small className="text-xs text-danger-500">{errors[`slots.${index}.max_capacity`]}</small>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="mt-4 flex items-center gap-1.5 text-xs font-medium text-slate-400 dark:text-slate-500">
                                                <IconClock size={14} />
                                                <span>
                                                    Durasi Sesi Otomatis: {slot.duration_minutes} Menit
                                                </span>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </>
    );
}

Edit.layout = (page) => <DashboardLayout children={page} />;
