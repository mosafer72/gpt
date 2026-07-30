"use client";

import { useEffect, useState } from "react";
import {
  getWorkspaceTypes,
  createWorkspaceType,
  updateWorkspaceType,
  toggleWorkspaceTypeStatus,
} from "../services";

export default function WorkspaceTypesPage() {
  const [types, setTypes] = useState<any[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [updatingId, setUpdatingId] = useState<number | string | null>(null);
  const [isMounted, setIsMounted] = useState<boolean>(false);

  // === State های مربوط به مدال (پاپ‌آپ) ===
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<"add" | "edit">("add");
  const [editingId, setEditingId] = useState<number | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // فرم دیتا بر اساس فیلدهای مدل WorkspaceType
  const [formData, setFormData] = useState({
    key: "",
    name: "",
    display_name: "",
    description: "",
  });

  useEffect(() => {
    setIsMounted(true);
  }, []);

  const loadTypes = async () => {
    try {
      setLoading(true);
      const data = await getWorkspaceTypes();
      const typesData = Array.isArray(data) ? data : (data as any).data || [];
      setTypes(typesData);
    } catch (err: any) {
      setError(err.message || "خطا در بارگذاری داده‌ها");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (!isMounted) return;
    loadTypes();
  }, [isMounted]);

  // === توابع مدیریت مدال ===
  const openModal = (mode: "add" | "edit", typeObj: any = null) => {
    setModalMode(mode);
    if (mode === "edit" && typeObj) {
      setEditingId(typeObj.id);
      setFormData({
        key: typeObj.key,
        name: typeObj.name,
        display_name: typeObj.display_name,
        description: typeObj.description || "",
      });
    } else {
      setEditingId(null);
      setFormData({ key: "", name: "", display_name: "", description: "" });
    }
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
    setFormData({ key: "", name: "", display_name: "", description: "" });
    setEditingId(null);
  };

  const handleInputChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>,
  ) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleFormSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      if (modalMode === "add") {
        const res = (await createWorkspaceType(formData)) as any;
        const newType = res?.workspace_type || res;
        setTypes([newType, ...types]);
      } else if (modalMode === "edit" && editingId) {
        await updateWorkspaceType(editingId, formData);
        setTypes(
          types.map((t) => (t.id === editingId ? { ...t, ...formData } : t)),
        );
      }
      closeModal();
    } catch (err: any) {
      alert(err.message || "خطا در ذخیره اطلاعات");
    } finally {
      setIsSubmitting(false);
    }
  };
  // === تابع تغییر وضعیت ===
  const handleToggleStatus = async (id: number, currentStatus: boolean) => {
    try {
      setUpdatingId(id);
      await toggleWorkspaceTypeStatus(id, currentStatus);
      setTypes((prev) =>
        prev.map((t) =>
          t.id === id ? { ...t, is_active: !currentStatus } : t,
        ),
      );
    } catch (err: any) {
      alert(err.message || "عملیات با خطا مواجه شد");
    } finally {
      setUpdatingId(null);
    }
  };

  if (!isMounted)
    return (
      <div className="h-64 flex items-center justify-center">
        در حال آماده‌سازی...
      </div>
    );
  if (loading)
    return (
      <div className="h-64 flex items-center justify-center text-blue-500">
        در حال بارگذاری...
      </div>
    );
  if (error)
    return (
      <div className="h-64 flex items-center justify-center text-red-500">
        {error}
      </div>
    );

  return (
    <div className="space-y-6 relative">
      {/* هدر */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h2 className="text-2xl font-bold admin-text-gradient">
          مدیریت انواع فضای کاری
        </h2>
        <button
          onClick={() => openModal("add")}
          className="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-emerald-500 text-white font-medium rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 hover:scale-[1.02] transition-all duration-200 flex items-center gap-2"
        >
          <svg
            className="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 4v16m8-8H4"
            />
          </svg>
          ایجاد نوع جدید
        </button>
      </div>

      {/* جدول */}
      <div className="admin-card overflow-hidden border border-white/20 bg-white/70 backdrop-blur-sm shadow-xl rounded-2xl">
        <div className="overflow-x-auto">
          <table className="w-full text-right text-sm text-gray-700">
            <thead className="bg-gray-50/80 text-gray-700 font-semibold border-b border-gray-200/60">
              <tr>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">
                  شناسه یکتا (Key)
                </th>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">
                  نام سیستمی
                </th>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">
                  نام نمایشی
                </th>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">
                  وضعیت فعالیت
                </th>
                <th className="px-6 py-4 text-center text-xs uppercase tracking-wider">
                  عملیات
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100/70">
              {types.map((type) => (
                <tr
                  key={type.id}
                  className="hover:bg-blue-50/30 transition-colors duration-150"
                >
                  <td className="px-6 py-4">
                    <span className="inline-flex items-center px-2 py-1 bg-blue-50/80 text-blue-700 rounded-md text-xs font-mono font-semibold border border-blue-200/50">
                      {type.key}
                    </span>
                  </td>
                  <td className="px-6 py-4 font-medium text-gray-600" dir="ltr">
                    {type.name}
                  </td>
                  <td className="px-6 py-4 font-bold text-gray-900">
                    {type.display_name}
                  </td>
                  <td className="px-6 py-4">
                    {type.is_active ? (
                      <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50/80 text-green-700 rounded-full text-xs font-semibold border border-green-200/50">
                        فعال
                      </span>
                    ) : (
                      <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-red-50/80 text-red-700 rounded-full text-xs font-semibold border border-red-200/50">
                        غیرفعال
                      </span>
                    )}
                  </td>
                  <td className="px-6 py-4 flex items-center justify-center gap-2">
                    {/* دکمه ویرایش */}
                    <button
                      onClick={() => openModal("edit", type)}
                      className="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors text-xs font-medium border border-blue-200"
                    >
                      ویرایش
                    </button>

                    {/* دکمه تغییر وضعیت */}
                    <button
                      onClick={() =>
                        handleToggleStatus(type.id, type.is_active)
                      }
                      disabled={updatingId === type.id}
                      className={`
                        px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 border
                        ${
                          updatingId === type.id
                            ? "bg-gray-100 text-gray-400 cursor-not-allowed"
                            : type.is_active
                              ? "bg-red-50 text-red-600 hover:bg-red-100 border-red-200"
                              : "bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border-emerald-200"
                        }
                      `}
                    >
                      {updatingId === type.id
                        ? "در حال..."
                        : type.is_active
                          ? "غیرفعال‌سازی"
                          : "فعال‌سازی"}
                    </button>
                  </td>
                </tr>
              ))}

              {types.length === 0 && !loading && (
                <tr>
                  <td
                    colSpan={5}
                    className="px-6 py-12 text-center text-gray-400"
                  >
                    هیچ نوع فضای کاری یافت نشد.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* ============================= */}
      {/* مدال (پاپ‌آپ) ایجاد / ویرایش */}
      {/* ============================= */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          {/* پس‌زمینه تاریک شیشه‌ای */}
          <div
            className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"
            onClick={closeModal}
          ></div>

          {/* باکس مدال */}
          <div className="relative bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform transition-all border border-gray-100">
            <div className="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
              <h3 className="text-lg font-bold text-gray-800">
                {modalMode === "add"
                  ? "ایجاد نوع فضای کاری"
                  : "ویرایش نوع فضای کاری"}
              </h3>
              <button
                onClick={closeModal}
                className="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg
                  className="w-5 h-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>

            <form onSubmit={handleFormSubmit} className="p-6 space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    شناسه یکتا (Key)
                  </label>
                  <input
                    type="text"
                    name="key"
                    required
                    value={formData.key}
                    onChange={handleInputChange}
                    className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm text-left font-mono"
                    dir="ltr"
                    placeholder="e.g. personal"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    نام سیستمی
                  </label>
                  <input
                    type="text"
                    name="name"
                    required
                    value={formData.name}
                    onChange={handleInputChange}
                    className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm text-left"
                    dir="ltr"
                    placeholder="e.g. Personal Type"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  نام نمایشی (Display Name)
                </label>
                <input
                  type="text"
                  name="display_name"
                  required
                  value={formData.display_name}
                  onChange={handleInputChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm"
                  placeholder="مثال: فضای کاری شخصی"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  توضیحات
                </label>
                <textarea
                  name="description"
                  value={formData.description}
                  onChange={handleInputChange}
                  rows={3}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm resize-none"
                  placeholder="توضیحات تکمیلی در مورد این نوع..."
                ></textarea>
              </div>

              <div className="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                <button
                  type="button"
                  onClick={closeModal}
                  className="px-5 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors"
                >
                  انصراف
                </button>
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/30 flex items-center gap-2 disabled:opacity-70"
                >
                  {isSubmitting && (
                    <svg
                      className="animate-spin h-4 w-4 text-white"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
                      <circle
                        className="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        strokeWidth="4"
                      ></circle>
                      <path
                        className="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                      ></path>
                    </svg>
                  )}
                  {modalMode === "add" ? "ایجاد" : "ذخیره تغییرات"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
