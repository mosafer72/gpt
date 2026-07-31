"use client";

import { useEffect, useState } from "react";
import {
  getWorkspaceTypes,
  createWorkspaceType,
  updateWorkspaceType,
  toggleWorkspaceTypeStatus,
} from "../services";

const DEFAULT_TOOLS = [
  {
    key: "documents",
    icon: "📄",
    title: "Documents",
    desc: "آپلود و تحلیل جزوه و فایل‌های PDF",
    path: "documents",
  },
  {
    key: "library",
    icon: "📚",
    title: "Paper Library",
    desc: "مدیریت و جستجوی مقالات علمی",
    path: "library",
  },
  {
    key: "summarizer",
    icon: "📝",
    title: "Summarizer",
    desc: "خلاصه‌سازی متن و استخراج نکات مهم",
    path: "summarizer",
  },
  {
    key: "solver",
    icon: "🧮",
    title: "AI Solver",
    desc: "حل تمرین و توضیح مرحله به مرحله",
    path: "solver",
  },
  {
    key: "research",
    icon: "🔍",
    title: "Research",
    desc: "تحقیق، مقاله و جمع‌آوری منابع",
    path: "research",
  },
  {
    key: "memory",
    icon: "🧠",
    title: "Memory",
    desc: "حافظه هوشمند دانشجویی",
    path: "memory",
  },
];

export default function WorkspaceTypesPage() {
  const [types, setTypes] = useState<any[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [updatingId, setUpdatingId] = useState<number | string | null>(null);
  const [isMounted, setIsMounted] = useState<boolean>(false);

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<"add" | "edit">("add");
  const [editingId, setEditingId] = useState<number | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const [availableTools, setAvailableTools] = useState(DEFAULT_TOOLS);
  const [showNewToolInput, setShowNewToolInput] = useState(false);
  const [newTool, setNewTool] = useState({
    key: "",
    icon: "✨",
    title: "",
    desc: "",
    path: "",
  });

  const [formData, setFormData] = useState({
    key: "",
    name: "",
    display_name: "",
    description: "",
    features: [] as any[],
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

      const allCustomTools: any[] = [...DEFAULT_TOOLS];
      typesData.forEach((t: any) => {
        if (Array.isArray(t.features)) {
          t.features.forEach((feat: any) => {
            if (
              typeof feat === "object" &&
              feat !== null &&
              !allCustomTools.some((dt) => dt.key === feat.key)
            ) {
              allCustomTools.push(feat);
            }
          });
        }
      });
      setAvailableTools(allCustomTools);
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

  const openModal = (mode: "add" | "edit", typeObj: any = null) => {
    setModalMode(mode);
    setShowNewToolInput(false);
    if (mode === "edit" && typeObj) {
      setEditingId(typeObj.id);
      setFormData({
        key: typeObj.key,
        name: typeObj.name,
        display_name: typeObj.display_name,
        description: typeObj.description || "",
        features: Array.isArray(typeObj.features) ? typeObj.features : [],
      });
    } else {
      setEditingId(null);
      setFormData({
        key: "",
        name: "",
        display_name: "",
        description: "",
        features: [],
      });
    }
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
    setFormData({
      key: "",
      name: "",
      display_name: "",
      description: "",
      features: [],
    });
    setEditingId(null);
  };

  const handleInputChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>,
  ) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleToolToggle = (tool: any) => {
    const exists = formData.features.some((f: any) => f.key === tool.key);
    if (exists) {
      setFormData({
        ...formData,
        features: formData.features.filter((f: any) => f.key !== tool.key),
      });
    } else {
      setFormData({
        ...formData,
        features: [...formData.features, { ...tool }],
      });
    }
  };

  // قابلیت ویرایش ویژگی‌های یک فیچر انتخاب شده در همین فرم
  const handleFeaturePropertyChange = (
    key: string,
    field: string,
    value: string,
  ) => {
    const updatedFeatures = formData.features.map((feat: any) => {
      if (feat.key === key) {
        return { ...feat, [field]: value };
      }
      return feat;
    });
    setFormData({ ...formData, features: updatedFeatures });
  };

  const handleAddNewTool = () => {
    if (!newTool.key || !newTool.title) {
      alert("لطفا کلید یکتا و عنوان ابزار را وارد کنید.");
      return;
    }

    const cleanKey = newTool.key.trim().toLowerCase().replace(/\s+/g, "_");
    const toolObj = {
      key: cleanKey,
      icon: newTool.icon || "✨",
      title: newTool.title.trim(),
      desc: newTool.desc.trim() || "توضیحات ابزار",
      path: cleanKey,
    };

    if (availableTools.some((t) => t.key === cleanKey)) {
      alert("این کلید ابزار از قبل وجود دارد.");
      return;
    }

    setAvailableTools([...availableTools, toolObj]);
    setFormData({
      ...formData,
      features: [...formData.features, toolObj],
    });

    setNewTool({ key: "", icon: "✨", title: "", desc: "", path: "" });
    setShowNewToolInput(false);
  };

  const handleFormSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      const payload = {
        ...formData,
        features: formData.features.length > 0 ? formData.features : [],
      };

      if (modalMode === "add") {
        const res = (await createWorkspaceType(payload)) as any;
        const newType = res?.workspace_type || res;
        setTypes([newType, ...types]);
      } else if (modalMode === "edit" && editingId) {
        await updateWorkspaceType(editingId, payload);
        setTypes(
          types.map((t) => (t.id === editingId ? { ...t, ...payload } : t)),
        );
      }
      closeModal();
      loadTypes();
    } catch (err: any) {
      alert(err.message || "خطا در ذخیره اطلاعات");
    } finally {
      setIsSubmitting(false);
    }
  };

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
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h2 className="text-2xl font-bold admin-text-gradient">
          مدیریت انواع فضای کاری
        </h2>
        <button
          onClick={() => openModal("add")}
          className="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-emerald-500 text-white font-medium rounded-xl shadow-lg shadow-blue-500/20 hover:scale-[1.02] transition-all flex items-center gap-2"
        >
          + ایجاد نوع جدید
        </button>
      </div>

      <div className="admin-card overflow-hidden border border-white/20 bg-white/70 backdrop-blur-sm shadow-xl rounded-2xl">
        <div className="overflow-x-auto">
          <table className="w-full text-right text-sm text-gray-700">
            <thead className="bg-gray-50/80 text-gray-700 font-semibold border-b border-gray-200/60">
              <tr>
                <th className="px-6 py-4 text-xs uppercase">
                  شناسه یکتا (Key)
                </th>
                <th className="px-6 py-4 text-xs uppercase">نام نمایشی</th>
                <th className="px-6 py-4 text-xs uppercase">
                  ابزارها (Features)
                </th>
                <th className="px-6 py-4 text-xs uppercase">وضعیت</th>
                <th className="px-6 py-4 text-center text-xs uppercase">
                  عملیات
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100/70">
              {types.map((type) => (
                <tr
                  key={type.id}
                  className="hover:bg-blue-50/30 transition-colors"
                >
                  <td className="px-6 py-4 font-mono text-xs font-semibold text-blue-700">
                    {type.key}
                  </td>
                  <td className="px-6 py-4 font-bold text-gray-900">
                    {type.display_name}
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex flex-wrap gap-1.5">
                      {Array.isArray(type.features) &&
                      type.features.length > 0 ? (
                        type.features.map((feat: any, i: number) => (
                          <span
                            key={i}
                            className="px-2 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs flex items-center gap-1 border"
                          >
                            <span>{feat.icon}</span>
                            <span className="font-semibold">{feat.title}</span>
                          </span>
                        ))
                      ) : (
                        <span className="text-gray-400 text-xs">
                          بدون ابزار
                        </span>
                      )}
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <span
                      className={`px-3 py-1 rounded-full text-xs font-semibold ${type.is_active ? "bg-green-50 text-green-700" : "bg-red-50 text-red-700"}`}
                    >
                      {type.is_active ? "فعال" : "غیرفعال"}
                    </span>
                  </td>
                  <td className="px-6 py-4 flex items-center justify-center gap-2">
                    <button
                      onClick={() => openModal("edit", type)}
                      className="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 text-xs font-medium border border-blue-200"
                    >
                      ویرایش
                    </button>
                    <button
                      onClick={() =>
                        handleToggleStatus(type.id, type.is_active)
                      }
                      disabled={updatingId === type.id}
                      className={`px-3 py-1.5 rounded-lg text-xs font-medium border ${type.is_active ? "bg-red-50 text-red-600 hover:bg-red-100 border-red-200" : "bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border-emerald-200"}`}
                    >
                      {type.is_active ? "غیرفعال‌سازی" : "فعال‌سازی"}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* مدال ایجاد / ویرایش */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div
            className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
            onClick={closeModal}
          ></div>
          <div className="relative bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden border border-gray-100">
            <div className="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
              <h3 className="text-lg font-bold text-gray-800">
                {modalMode === "add"
                  ? "ایجاد نوع فضای کاری"
                  : "ویرایش نوع فضای کاری"}
              </h3>
              <button
                onClick={closeModal}
                className="text-gray-400 hover:text-gray-600"
              >
                ✕
              </button>
            </div>

            <form
              onSubmit={handleFormSubmit}
              className="p-6 space-y-4 max-h-[80vh] overflow-y-auto"
            >
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
                    className="w-full px-4 py-2 border rounded-xl text-sm font-mono text-left"
                    dir="ltr"
                    placeholder="e.g. academic"
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
                    className="w-full px-4 py-2 border rounded-xl text-sm text-left"
                    dir="ltr"
                    placeholder="e.g. Academic Workspace"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  نام نمایشی
                </label>
                <input
                  type="text"
                  name="display_name"
                  required
                  value={formData.display_name}
                  onChange={handleInputChange}
                  className="w-full px-4 py-2 border rounded-xl text-sm"
                  placeholder="مثال: فضای کاری دانشگاهی"
                />
              </div>

              {/* بخش ابزارها */}
              <div>
                <div className="flex justify-between items-center mb-2">
                  <label className="block text-sm font-medium text-gray-700">
                    انتخاب ابزارها
                  </label>
                  <button
                    type="button"
                    onClick={() => setShowNewToolInput(!showNewToolInput)}
                    className="text-xs text-blue-600 hover:text-blue-800 font-semibold"
                  >
                    {showNewToolInput
                      ? "بستن فرم ابزار جدید"
                      : "+ ساخت ابزار سفارشی جدید"}
                  </button>
                </div>

                {showNewToolInput && (
                  <div className="mb-3 p-3 bg-blue-50/50 rounded-xl border border-blue-200 space-y-2">
                    <p className="text-xs font-bold text-blue-800">
                      تعریف جزئیات ابزار جدید:
                    </p>
                    <div className="grid grid-cols-3 gap-2">
                      <input
                        type="text"
                        placeholder="کلید (مثال: quiz)"
                        value={newTool.key}
                        onChange={(e) =>
                          setNewTool({
                            ...newTool,
                            key: e.target.value,
                            path: e.target.value,
                          })
                        }
                        className="px-3 py-1.5 border rounded-lg text-xs font-mono bg-white"
                        dir="ltr"
                      />
                      <input
                        type="text"
                        placeholder="آیکون (مثال: 📝)"
                        value={newTool.icon}
                        onChange={(e) =>
                          setNewTool({ ...newTool, icon: e.target.value })
                        }
                        className="px-3 py-1.5 border rounded-lg text-xs bg-white text-center"
                      />
                      <input
                        type="text"
                        placeholder="عنوان"
                        value={newTool.title}
                        onChange={(e) =>
                          setNewTool({ ...newTool, title: e.target.value })
                        }
                        className="px-3 py-1.5 border rounded-lg text-xs bg-white"
                      />
                    </div>
                    <input
                      type="text"
                      placeholder="توضیحات کوتاه ابزار"
                      value={newTool.desc}
                      onChange={(e) =>
                        setNewTool({ ...newTool, desc: e.target.value })
                      }
                      className="w-full px-3 py-1.5 border rounded-lg text-xs bg-white"
                    />
                    <button
                      type="button"
                      onClick={handleAddNewTool}
                      className="w-full py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium shadow"
                    >
                      ثبت و افزودن ابزار به لیست
                    </button>
                  </div>
                )}

                {/* لیست انتخاب ابزارها */}
                <div className="grid grid-cols-2 gap-2 bg-gray-50 p-3 rounded-xl border border-gray-200 max-h-48 overflow-y-auto">
                  {availableTools.map((tool) => {
                    const isSelected = formData.features.some(
                      (f: any) => f.key === tool.key,
                    );
                    return (
                      <div
                        key={tool.key}
                        onClick={() => handleToolToggle(tool)}
                        className={`p-2.5 rounded-xl border cursor-pointer flex items-center justify-between transition-all ${
                          isSelected
                            ? "bg-blue-50 border-blue-300 text-blue-900 shadow-sm"
                            : "bg-white border-gray-200 text-gray-700 hover:bg-gray-100"
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          <span className="text-xl">{tool.icon}</span>
                          <div>
                            <p className="text-xs font-bold">{tool.title}</p>
                            <p className="text-[10px] text-gray-400 truncate max-w-[120px]">
                              {tool.desc}
                            </p>
                          </div>
                        </div>
                        <input
                          type="checkbox"
                          checked={isSelected}
                          onChange={() => {}}
                          className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                      </div>
                    );
                  })}
                </div>
              </div>

              {/* بخش ویرایش مقادیر ابزارهای انتخاب شده */}
              {formData.features.length > 0 && (
                <div className="space-y-3 pt-2 border-t">
                  <label className="block text-sm font-medium text-gray-700">
                    ویرایش جزئیات ابزارهای انتخاب‌شده در این فضا
                  </label>
                  <div className="space-y-2 max-h-48 overflow-y-auto pr-1">
                    {formData.features.map((feat: any, index: number) => (
                      // استفاده از ترکیب کلید و ایندکس برای جلوگیری از خطای کلید تکراری یا undefined
                      <div
                        key={`edit-feat-${feat?.key || index}`}
                        className="p-3 bg-gray-50 rounded-xl border border-gray-200 space-y-2"
                      >
                        <div className="flex justify-between items-center text-xs font-mono text-blue-700 font-bold">
                          <span>کلید: {feat?.key || "بدون کلید"}</span>
                          <button
                            type="button"
                            onClick={() => handleToolToggle(feat)}
                            className="text-red-500 hover:text-red-700 text-xs font-sans"
                          >
                            حذف از این فضا
                          </button>
                        </div>
                        <div className="grid grid-cols-3 gap-2">
                          <input
                            type="text"
                            value={feat?.icon || ""}
                            onChange={(e) =>
                              handleFeaturePropertyChange(
                                feat.key,
                                "icon",
                                e.target.value,
                              )
                            }
                            placeholder="آیکون"
                            className="px-2 py-1 border rounded text-xs text-center bg-white"
                          />
                          <input
                            type="text"
                            value={feat?.title || ""}
                            onChange={(e) =>
                              handleFeaturePropertyChange(
                                feat.key,
                                "title",
                                e.target.value,
                              )
                            }
                            placeholder="عنوان"
                            className="px-2 py-1 border rounded text-xs bg-white col-span-2"
                          />
                        </div>
                        <input
                          type="text"
                          value={feat?.desc || ""}
                          onChange={(e) =>
                            handleFeaturePropertyChange(
                              feat.key,
                              "desc",
                              e.target.value,
                            )
                          }
                          placeholder="توضیحات"
                          className="w-full px-2 py-1 border rounded text-xs bg-white"
                        />
                      </div>
                    ))}
                  </div>
                </div>
              )}

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  توضیحات کلی
                </label>
                <textarea
                  name="description"
                  value={formData.description}
                  onChange={handleInputChange}
                  rows={2}
                  className="w-full px-4 py-2 border rounded-xl text-sm resize-none"
                ></textarea>
              </div>

              <div className="flex justify-end gap-3 pt-4 border-t">
                <button
                  type="button"
                  onClick={closeModal}
                  className="px-5 py-2 text-sm bg-gray-100 rounded-xl"
                >
                  انصراف
                </button>
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="px-6 py-2 text-sm bg-blue-600 text-white rounded-xl shadow-lg"
                >
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
