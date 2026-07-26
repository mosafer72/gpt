// app/admin/users/page.tsx
"use client";

import { useState, useEffect } from "react";
import { getUsers, createUser, deleteUser, loginAsUser, getUserWorkspaces, assignWorkspaceToUser, getWorkspaces } from "../services";

interface User {
  id: number;
  name: string;
  phone: string;
  role: string;
  phone_verified_at: string | null;
  created_at: string;
  workspaces?: any[];
}

export default function AdminUsersPage() {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState("");

  // مودال‌ها
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isAssignModalOpen, setIsAssignModalOpen] = useState(false);
  const [isWorkspacesModalOpen, setIsWorkspacesModalOpen] = useState(false);

  // داده‌های موقت انتخاب شده برای مودال‌ها
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [allWorkspaces, setAllWorkspaces] = useState<any[]>([]);
  const [userWorkspacesList, setUserWorkspacesList] = useState<any[]>([]);
  const [selectedWorkspaceId, setSelectedWorkspaceId] = useState("");

  const [formData, setFormData] = useState({
    name: "",
    phone: "",
    role: "user",
  });

  useEffect(() => {
    fetchUsers();
    fetchGeneralWorkspaces();
  }, [searchQuery]);

  const fetchUsers = async () => {
    try {
      setLoading(true);
      const res: any = await getUsers(searchQuery);
      if (res.status === "success") {
        setUsers(res.data.data || res.data);
      }
    } catch (error) {
      console.error("خطا در دریافت کاربران:", error);
    } finally {
      setLoading(false);
    }
  };

  const fetchGeneralWorkspaces = async () => {
    try {
      const res: any = await getWorkspaces();
      setAllWorkspaces(res.data || res);
    } catch (error) {
      console.error("خطا در دریافت فضاهای کاری:", error);
    }
  };

  const handleCreateSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res: any = await createUser(formData);
      if (res.status === "success") {
        setIsCreateModalOpen(false);
        fetchUsers();
        setFormData({ name: "", phone: "", role: "user" });
      }
    } catch (error) {
      console.error("خطا در ایجاد کاربر:", error);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm("آیا از حذف این کاربر اطمینان دارید؟")) return;

    try {
      await deleteUser(id);
      setUsers(users.filter((u) => u.id !== id));
    } catch (error) {
      console.error("خطا در حذف کاربر:", error);
    }
  };

  // عملیات Login As (ورود به اکانت کاربر)
  const handleLoginAs = async (user: User) => {
    try {
      const res: any = await loginAsUser(user.id);
      if (res.status === "success" && res.token) {
        localStorage.setItem("auth_token", res.token);
        window.open("/dashboard", "_blank");
      }
    } catch (error) {
      console.error("خطا در ورود به اکانت کاربر:", error);
    }
  };

  // باز کردن پاپ‌آپ مشاهده فضاهای کاری کاربر
  const handleOpenWorkspacesModal = async (user: User) => {
    setSelectedUser(user);
    try {
      const res: any = await getUserWorkspaces(user.id);
      if (res.status === "success") {
        setUserWorkspacesList(res.data);
      }
    } catch (error) {
      setUserWorkspacesList([]);
    }
    setIsWorkspacesModalOpen(true);
  };

  // باز کردن پاپ‌آپ انتساب فضای کاری
  const handleOpenAssignModal = (user: User) => {
    setSelectedUser(user);
    setSelectedWorkspaceId("");
    setIsAssignModalOpen(true);
  };

  const handleAssignSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedUser || !selectedWorkspaceId) return;

    try {
      const res: any = await assignWorkspaceToUser(selectedUser.id, Number(selectedWorkspaceId));
      if (res.status === "success") {
        setIsAssignModalOpen(false);
        fetchUsers();
        alert("فضای کاری با موفقیت به کاربر انتساب داده شد.");
      }
    } catch (error) {
      console.error("خطا در انتساب فضای کاری:", error);
    }
  };

  return (
    <div className="space-y-6">
      {/* هدر صفحه */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">مدیریت کاربران</h1>
          <p className="text-sm text-gray-500 mt-1">مدیریت دسترسی‌ها، فضاهای کاری و ورود به حساب کاربران.</p>
        </div>
        <button
          onClick={() => setIsCreateModalOpen(true)}
          className="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-emerald-600 text-white font-medium rounded-xl shadow-lg shadow-blue-500/20 hover:scale-[1.02] transition"
        >
          + افزودن کاربر جدید
        </button>
      </div>

      {/* جستجو */}
      <div className="bg-white p-4 rounded-2xl shadow-sm border border-gray-200">
        <input
          type="text"
          placeholder="جستجو بر اساس نام یا شماره موبایل..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          className="w-full md:w-80 px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 text-sm"
        />
      </div>

      {/* جدول کاربران */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-right border-collapse">
            <thead>
              <tr className="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <th className="py-4 px-6">نام کاربر</th>
                <th className="py-4 px-6">شماره تماس</th>
                <th className="py-4 px-6">نقش سیستم</th>
                <th className="py-4 px-6">وضعیت تایید</th>
                <th className="py-4 px-6 text-center">عملیات و مدیریت</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 text-sm text-gray-700">
              {loading ? (
                <tr>
                  <td colSpan={5} className="py-8 text-center text-gray-400">
                    در حال بارگذاری اطلاعات...
                  </td>
                </tr>
              ) : users.length === 0 ? (
                <tr>
                  <td colSpan={5} className="py-8 text-center text-gray-400">
                    هیچ کاربری یافت نشد.
                  </td>
                </tr>
              ) : (
                users.map((user) => (
                  <tr key={user.id} className="hover:bg-gray-50/50 transition">
                    <td className="py-4 px-6 font-medium text-gray-900">{user.name || "بدون نام"}</td>
                    <td className="py-4 px-6 font-mono text-xs">{user.phone}</td>
                    <td className="py-4 px-6">
                      <span
                        className={`px-3 py-1 rounded-lg text-xs font-semibold ${
                          user.role === "admin" ? "bg-purple-50 text-purple-600" : "bg-blue-50 text-blue-600"
                        }`}
                      >
                        {user.role === "admin" ? "مدیر سیستم" : "کاربر عادی"}
                      </span>
                    </td>
                    <td className="py-4 px-6">
                      <span
                        className={`px-3 py-1 rounded-full text-xs font-bold ${
                          user.phone_verified_at ? "bg-emerald-100 text-emerald-700" : "bg-amber-100 text-amber-700"
                        }`}
                      >
                        {user.phone_verified_at ? "تایید شده" : "تایید نشده"}
                      </span>
                    </td>
                    <td className="py-4 px-6 text-center">
                      <div className="flex items-center justify-center gap-2">
                        {/* دکمه Login As */}
                        <button
                          onClick={() => handleLoginAs(user)}
                          title="ورود به اکانت"
                          className="px-2.5 py-1.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-lg text-xs font-medium transition"
                        >
                          Login As
                        </button>
                        {/* دکمه مشاهده Workspaceها */}
                        <button
                          onClick={() => handleOpenWorkspacesModal(user)}
                          title="فضاهای کاری"
                          className="px-2.5 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs font-medium transition"
                        >
                          Workspace
                        </button>
                        {/* دکمه انتساب Workspace جدید */}
                        <button
                          onClick={() => handleOpenAssignModal(user)}
                          title="انتساب فضای کاری"
                          className="px-2.5 py-1.5 bg-purple-50 text-purple-600 hover:bg-purple-100 rounded-lg text-xs font-medium transition"
                        >
                          انتساب فضا
                        </button>
                        {/* دکمه حذف */}
                        <button
                          onClick={() => handleDelete(user.id)}
                          className="text-red-500 hover:text-red-700 p-1.5 rounded-lg hover:bg-red-50 transition"
                        >
                          حذف
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* ========== پاپ‌آپ افزودن کاربر جدید ========== */}
      {isCreateModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
          <div className="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl border border-gray-100">
            <h3 className="text-lg font-bold text-gray-800 mb-4">افزودن کاربر جدید</h3>
            <form onSubmit={handleCreateSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">نام و نام خانوادگی</label>
                <input
                  type="text"
                  required
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500"
                  placeholder="مثال: محمدرضا"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">شماره موبایل</label>
                <input
                  type="text"
                  required
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 font-mono"
                  placeholder="09123456789"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">نقش کاربر</label>
                <select
                  value={formData.role}
                  onChange={(e) => setFormData({ ...formData, role: e.target.value })}
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 bg-white"
                >
                  <option value="user">کاربر عادی</option>
                  <option value="admin">مدیر سیستم</option>
                </select>
              </div>
              <div className="flex justify-end gap-3 mt-6">
                <button
                  type="button"
                  onClick={() => setIsCreateModalOpen(false)}
                  className="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition"
                >
                  انصراف
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-blue-600 text-white rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition"
                >
                  ذخیره اطلاعات
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ========== پاپ‌آپ مشاهده فضاهای کاری کاربر ========== */}
      {isWorkspacesModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
          <div className="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl border border-gray-100">
            <h3 className="text-lg font-bold text-gray-800 mb-2">فضاهای کاری کاربر: {selectedUser?.name}</h3>
            <p className="text-xs text-gray-500 mb-4">لیست فضاهای کاری متعلق به این کاربر:</p>
            
            <div className="space-y-2 max-h-60 overflow-y-auto">
              {userWorkspacesList.length === 0 ? (
                <p className="text-center text-sm text-gray-400 py-4">این کاربر هیچ فضای کاری ندارد.</p>
              ) : (
                userWorkspacesList.map((ws: any) => (
                  <div key={ws.id} className="p-3 bg-gray-50 rounded-xl border border-gray-200 flex items-center justify-between">
                    <div>
                      <p className="text-sm font-semibold text-gray-800">{ws.name}</p>
                      <p className="text-xs text-gray-400">شناسه: {ws.id}</p>
                    </div>
                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold ${ws.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600'}`}>
                      {ws.is_active ? 'فعال' : 'غیرفعال'}
                    </span>
                  </div>
                ))
              )}
            </div>

            <div className="flex justify-end mt-6">
              <button
                type="button"
                onClick={() => setIsWorkspacesModalOpen(false)}
                className="px-5 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition"
              >
                بستن
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ========== پاپ‌آپ انتساب فضای کاری به کاربر ========== */}
      {isAssignModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
          <div className="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl border border-gray-100">
            <h3 className="text-lg font-bold text-gray-800 mb-2">انتساب فضای کاری به {selectedUser?.name}</h3>
            <p className="text-xs text-gray-500 mb-4">یک فضای کاری از لیست انتخاب کنید تا به این کاربر منتقل/منتسب شود:</p>

            <form onSubmit={handleAssignSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">انتخاب فضای کاری</label>
                <select
                  required
                  value={selectedWorkspaceId}
                  onChange={(e) => setSelectedWorkspaceId(e.target.value)}
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 bg-white text-sm"
                >
                  <option value="">-- انتخاب کنید --</option>
                  {allWorkspaces.map((ws: any) => (
                    <option key={ws.id} value={ws.id}>
                      {ws.name} (شناسه: {ws.id})
                    </option>
                  ))}
                </select>
              </div>

              <div className="flex justify-end gap-3 mt-6">
                <button
                  type="button"
                  onClick={() => setIsAssignModalOpen(false)}
                  className="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition"
                >
                  انصراف
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-purple-600 text-white rounded-xl shadow-lg shadow-purple-500/20 hover:bg-purple-700 transition"
                >
                  تایید و انتساب
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}