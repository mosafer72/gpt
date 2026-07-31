"use client";

import { useState, useEffect } from "react";
import {
  getUsers,
  createUser,
  deleteUser,
  loginAsUser,
  getUserWorkspaces,
  assignWorkspaceToUser,
  getWorkspaceTypes,
  updateUserWallet,
  cancelUserWorkspace,
} from "../services";

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
  const [isWalletModalOpen, setIsWalletModalOpen] = useState(false);

  // انتخاب‌ها و لیست‌های کمکی
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [userWorkspacesList, setUserWorkspacesList] = useState<any[]>([]);

  const [workspaceTypes, setWorkspaceTypes] = useState<any[]>([]);
  const [workspacePlans, setWorkspacePlans] = useState<any[]>([]);
  const [selectedTypeId, setSelectedTypeId] = useState("");
  const [selectedPlanId, setSelectedPlanId] = useState("");
  const [workspaceName, setWorkspaceName] = useState("");

  // مدیریت ولت
  const [selectedWorkspaceId, setSelectedWorkspaceId] = useState("");
  const [walletAmount, setWalletAmount] = useState("");
  const [walletAction, setWalletAction] = useState<"add" | "subtract" | "set">(
    "add",
  );

  const [formData, setFormData] = useState({
    name: "",
    phone: "",
    role: "user",
  });

  useEffect(() => {
    fetchUsers();
    fetchWorkspaceTypesData();
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

  const fetchWorkspaceTypesData = async () => {
    try {
      const res: any = await getWorkspaceTypes();
      // پشتیبانی از ساختارهای مختلف پاسخ {status: 'success', data: [...]} یا آرایه مستقیم
      const typesList = Array.isArray(res) ? res : res.data || [];
      setWorkspaceTypes(typesList);
    } catch (error) {
      console.error("خطا در دریافت انواع فضای کاری", error);
    }
  };

  const handleTypeChange = (typeId: string) => {
    setSelectedTypeId(typeId);
    setSelectedPlanId("");
    const foundType = workspaceTypes.find(
      (t: any) => t.id.toString() === typeId,
    );
    // پشتیبانی از کلیدهای plans یا workspace_plans
    setWorkspacePlans(foundType?.plans || foundType?.workspace_plans || []);
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
    } catch (error: any) {
      alert(error?.message || "خطا در ایجاد کاربر");
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

  const handleOpenWorkspacesModal = async (user: User) => {
    setSelectedUser(user);
    try {
      const res: any = await getUserWorkspaces(user.id);
      if (res.status === "success") {
        // فقط فضاهایی که فعال هستند را نگه می‌داریم
        const activeWorkspaces = (res.data || []).filter(
          (ws: any) => ws.is_active === true,
        );
        setUserWorkspacesList(activeWorkspaces);
      }
    } catch (error) {
      setUserWorkspacesList([]);
    }
    setIsWorkspacesModalOpen(true);
  };

  const handleOpenAssignModal = (user: User) => {
    setSelectedUser(user);
    setSelectedTypeId("");
    setSelectedPlanId("");
    setWorkspaceName("");
    setWorkspacePlans([]);
    setIsAssignModalOpen(true);
  };

  const handleAssignSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedUser || !selectedTypeId || !selectedPlanId) return;

    try {
      const res: any = await assignWorkspaceToUser(selectedUser.id, {
        workspace_type_id: selectedTypeId,
        workspace_plan_id: selectedPlanId,
        name: workspaceName || undefined,
      });
      if (res.status === "success") {
        setIsAssignModalOpen(false);
        fetchUsers();
        alert("فضای کاری و پلن اشتراک با موفقیت ایجاد شد.");
      }
    } catch (error: any) {
      alert(
        error?.message ||
          "خطا در انتساب فضا. ممکن است کاربر از قبل این فضای فعال را داشته باشد.",
      );
    }
  };
  const handleCancelWorkspace = async (userId: number, workspaceId: number) => {
    if (
      !confirm("آیا از لغو این اشتراک و غیرفعال‌سازی فضای کاری اطمینان دارید؟")
    )
      return;

    try {
      const res: any = await cancelUserWorkspace(userId, workspaceId);
      if (res.status === "success") {
        alert("اشتراک با موفقیت لغو شد.");
        // به‌روزرسانی لیست فضاها داخل مودال و جدول اصلی
        if (selectedUser) {
          const updatedWorkspaces: any = await getUserWorkspaces(
            selectedUser.id,
          );
          if (updatedWorkspaces.status === "success") {
            setUserWorkspacesList(updatedWorkspaces.data);
          }
        }
        fetchUsers();
      }
    } catch (error: any) {
      alert(error?.message || "خطا در لغو اشتراک");
    }
  };

  const handleOpenWalletModal = (user: User) => {
    setSelectedUser(user);
    setSelectedWorkspaceId(user.workspaces?.[0]?.id?.toString() || "");
    setWalletAmount("");
    setWalletAction("add");
    setIsWalletModalOpen(true);
  };

  const handleWalletSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedUser || !selectedWorkspaceId || !walletAmount) return;

    try {
      await updateUserWallet(selectedUser.id, {
        workspace_id: Number(selectedWorkspaceId),
        amount: Number(walletAmount),
        action: walletAction,
      });
      setIsWalletModalOpen(false);
      fetchUsers();
      alert("کیف پول فضای کاری با موفقیت بروزرسانی شد.");
    } catch (error) {
      console.error("خطا در ویرایش ولت:", error);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">مدیریت کاربران</h1>
          <p className="text-sm text-gray-500 mt-1">
            مدیریت دسترسی‌ها، فضاهای کاری، پلن‌های اشتراک و کیف پول توکن
            کاربران.
          </p>
        </div>
        <button
          onClick={() => setIsCreateModalOpen(true)}
          className="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-emerald-600 text-white font-medium rounded-xl shadow-lg shadow-blue-500/20 hover:scale-[1.02] transition"
        >
          + افزودن کاربر جدید
        </button>
      </div>

      <div className="bg-white p-4 rounded-2xl shadow-sm border border-gray-200">
        <input
          type="text"
          placeholder="جستجو بر اساس نام یا شماره موبایل..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          className="w-full md:w-80 px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 text-sm"
        />
      </div>

      <div className="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-right border-collapse">
            <thead>
              <tr className="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <th className="py-4 px-6">نام کاربر</th>
                <th className="py-4 px-6">شماره تماس</th>
                <th className="py-4 px-6">نقش سیستم</th>
                <th className="py-4 px-6">فضاها و پلن‌های فعال</th>
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
                    <td className="py-4 px-6 font-medium text-gray-900">
                      {user.name || "بدون نام"}
                    </td>
                    <td className="py-4 px-6 font-mono text-xs">
                      {user.phone}
                    </td>
                    <td className="py-4 px-6">
                      <span
                        className={`px-3 py-1 rounded-lg text-xs font-semibold ${
                          user.role === "admin"
                            ? "bg-purple-50 text-purple-600"
                            : "bg-blue-50 text-blue-600"
                        }`}
                      >
                        {user.role === "admin" ? "مدیر سیستم" : "کاربر عادی"}
                      </span>
                    </td>
                    <td className="py-4 px-6">
                      <div className="flex flex-col gap-1">
                        {user.workspaces &&
                        user.workspaces.filter((ws: any) => ws.is_active)
                          .length > 0 ? (
                          user.workspaces
                            .filter((ws: any) => ws.is_active)
                            .map((ws: any) => (
                              <span
                                key={ws.id}
                                className="text-xs bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md flex items-center justify-between gap-2"
                              >
                                <span>
                                  <strong>{ws.name}</strong> (
                                  {ws.workspace_type?.name || "نوع"})
                                </span>
                                <span className="text-[10px] text-emerald-600 font-mono">
                                  توکن مانده: {ws.wallet?.remaining_tokens ?? 0}
                                </span>
                              </span>
                            ))
                        ) : (
                          <span className="text-xs text-gray-400">
                            بدون فضای فعال
                          </span>
                        )}
                      </div>
                    </td>
                    <td className="py-4 px-6 text-center">
                      <div className="flex items-center justify-center gap-1.5 flex-wrap">
                        <button
                          onClick={() => handleLoginAs(user)}
                          title="ورود به اکانت"
                          className="px-2.5 py-1 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-lg text-xs font-medium transition"
                        >
                          Login As
                        </button>
                        <button
                          onClick={() => handleOpenWorkspacesModal(user)}
                          title="مدیریت فضاها"
                          className="px-2.5 py-1 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs font-medium transition"
                        >
                          فضاها (
                          {user.workspaces?.filter((ws: any) => ws.is_active)
                            .length || 0}
                          )
                        </button>
                        <button
                          onClick={() => handleOpenAssignModal(user)}
                          title="ایجاد فضا و پلن"
                          className="px-2.5 py-1 bg-purple-50 text-purple-600 hover:bg-purple-100 rounded-lg text-xs font-medium transition"
                        >
                          + فضا/پلن
                        </button>
                        <button
                          onClick={() => handleOpenWalletModal(user)}
                          title="مدیریت کیف پول"
                          className="px-2.5 py-1 bg-amber-50 text-amber-600 hover:bg-amber-100 rounded-lg text-xs font-medium transition"
                        >
                          کیف پول
                        </button>
                        <button
                          onClick={() => handleDelete(user.id)}
                          className="text-red-500 hover:text-red-700 p-1 rounded-lg hover:bg-red-50 transition text-xs"
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

      {/* ========== مودال افزودن کاربر ========== */}
      {isCreateModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
          <div className="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl border border-gray-100">
            <h3 className="text-lg font-bold text-gray-800 mb-4">
              افزودن کاربر جدید
            </h3>
            <form onSubmit={handleCreateSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">
                  نام و نام خانوادگی
                </label>
                <input
                  type="text"
                  required
                  value={formData.name}
                  onChange={(e) =>
                    setFormData({ ...formData, name: e.target.value })
                  }
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 text-sm"
                  placeholder="مثال: محمدرضا"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">
                  شماره موبایل
                </label>
                <input
                  type="text"
                  required
                  value={formData.phone}
                  onChange={(e) =>
                    setFormData({ ...formData, phone: e.target.value })
                  }
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 text-sm font-mono"
                  placeholder="09123456789"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">
                  نقش سیستم
                </label>
                <select
                  value={formData.role}
                  onChange={(e) =>
                    setFormData({ ...formData, role: e.target.value })
                  }
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 bg-white text-sm"
                >
                  <option value="user">کاربر عادی</option>
                  <option value="admin">مدیر سیستم</option>
                </select>
              </div>
              <div className="flex justify-end gap-3 mt-6">
                <button
                  type="button"
                  onClick={() => setIsCreateModalOpen(false)}
                  className="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition text-sm"
                >
                  انصراف
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-blue-600 text-white rounded-xl shadow-lg shadow-blue-500/20 hover:bg-blue-700 transition text-sm"
                >
                  ذخیره اطلاعات
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ========== مودال مشاهده و لغو فضاها ========== */}
      {isWorkspacesModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
          <div className="bg-white rounded-2xl w-full max-w-xl p-6 shadow-2xl border border-gray-100">
            <h3 className="text-lg font-bold text-gray-800 mb-2">
              فضاهای کاری: {selectedUser?.name}
            </h3>
            <p className="text-xs text-gray-500 mb-4">
              لیست کامل فضاهای کاری و اشتراک‌های مرتبط:
            </p>

            <div className="space-y-3 max-h-72 overflow-y-auto">
              {userWorkspacesList.length === 0 ? (
                <p className="text-center text-sm text-gray-400 py-4">
                  این کاربر هیچ فضای کاری ندارد.
                </p>
              ) : (
                userWorkspacesList.map((ws: any) => (
                  <div
                    key={ws.id}
                    className="p-3.5 bg-gray-50 rounded-xl border border-gray-200 flex flex-col gap-2"
                  >
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-sm font-semibold text-gray-800">
                          {ws.name}
                        </p>
                        <p className="text-xs text-gray-400">
                          نوع فضا: {ws.workspace_type?.name}
                        </p>
                      </div>
                      <div className="flex items-center gap-2">
                        <span
                          className={`px-2.5 py-0.5 rounded-full text-xs font-bold ${ws.is_active ? "bg-emerald-100 text-emerald-700" : "bg-gray-200 text-gray-600"}`}
                        >
                          {ws.is_active ? "فعال" : "غیرفعال"}
                        </span>
                        {ws.is_active && (
                          <button
                            onClick={() =>
                              handleCancelWorkspace(selectedUser!.id, ws.id)
                            }
                            className="px-2.5 py-1 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-xs font-medium transition"
                          >
                            لغو پلن
                          </button>
                        )}
                      </div>
                    </div>
                    <div className="text-xs bg-white p-2 rounded border border-gray-100 flex justify-between items-center">
                      <span>
                        پلن فعال:{" "}
                        <strong>
                          {ws.current_subscription?.workspace_plan?.name ||
                            "بدون پلن"}
                        </strong>
                      </span>
                      <span className="font-mono text-blue-600">
                        مانده توکن: {ws.wallet?.remaining_tokens ?? 0}
                      </span>
                    </div>
                  </div>
                ))
              )}
            </div>

            <div className="flex justify-end mt-6">
              <button
                type="button"
                onClick={() => setIsWorkspacesModalOpen(false)}
                className="px-5 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition text-sm"
              >
                بستن
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ========== مودال ساخت فضا و پلن جدید ========== */}
      {isAssignModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
          <div className="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl border border-gray-100">
            <h3 className="text-lg font-bold text-gray-800 mb-2">
              ایجاد فضا و پلن برای {selectedUser?.name}
            </h3>
            <p className="text-xs text-gray-500 mb-4">
              انتخاب نوع فضا و پکیج اشتراک مربوطه:
            </p>

            <form onSubmit={handleAssignSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">
                  نام فضای کاری (اختیاری)
                </label>
                <input
                  type="text"
                  value={workspaceName}
                  onChange={(e) => setWorkspaceName(e.target.value)}
                  placeholder="مثال: فضای کاری اختصاصی"
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 text-sm"
                />
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">
                  نوع فضای کاری
                </label>
                <select
                  required
                  value={selectedTypeId}
                  onChange={(e) => handleTypeChange(e.target.value)}
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 bg-white text-sm"
                >
                  <option value="">-- انتخاب نوع فضا --</option>
                  {workspaceTypes.map((type: any) => (
                    <option key={type.id} value={type.id}>
                      {type.display_name || type.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">
                  پلن اشتراک
                </label>
                <select
                  required
                  value={selectedPlanId}
                  onChange={(e) => setSelectedPlanId(e.target.value)}
                  disabled={!selectedTypeId}
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 bg-white text-sm disabled:bg-gray-100"
                >
                  <option value="">-- ابتدا نوع فضا را انتخاب کنید --</option>
                  {workspacePlans.map((plan: any) => (
                    <option key={plan.id} value={plan.id}>
                      {plan.name} (توکن: {plan.token_limit})
                    </option>
                  ))}
                </select>
              </div>

              <div className="flex justify-end gap-3 mt-6">
                <button
                  type="button"
                  onClick={() => setIsAssignModalOpen(false)}
                  className="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition text-sm"
                >
                  انصراف
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-purple-600 text-white rounded-xl shadow-lg shadow-purple-500/20 hover:bg-purple-700 transition text-sm"
                >
                  ایجاد و انتساب
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ========== مودال مدیریت کیف پول توکن ========== */}
      {isWalletModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
          <div className="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl border border-gray-100">
            <h3 className="text-lg font-bold text-gray-800 mb-2">
              مدیریت کیف پول: {selectedUser?.name}
            </h3>
            <p className="text-xs text-gray-500 mb-4">
              مدیریت توکن‌های فضای کاری:
            </p>

            <form onSubmit={handleWalletSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">
                  فضای کاری مدنظر
                </label>
                <select
                  required
                  value={selectedWorkspaceId}
                  onChange={(e) => setSelectedWorkspaceId(e.target.value)}
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 bg-white text-sm"
                >
                  <option value="">-- انتخاب فضای کاری --</option>
                  {selectedUser?.workspaces?.map((ws: any) => (
                    <option key={ws.id} value={ws.id}>
                      {ws.name} (مانده: {ws.wallet?.remaining_tokens ?? 0})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">
                  نوع عملیات
                </label>
                <select
                  value={walletAction}
                  onChange={(e: any) => setWalletAction(e.target.value)}
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 bg-white text-sm"
                >
                  <option value="add">شارژ / افزایش توکن</option>
                  <option value="subtract">کسر توکن</option>
                  <option value="set">تنظیم مقدار کل</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-600 mb-1">
                  تعداد توکن
                </label>
                <input
                  type="number"
                  required
                  value={walletAmount}
                  onChange={(e) => setWalletAmount(e.target.value)}
                  placeholder="مقدار توکن..."
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 outline-none focus:border-blue-500 text-sm font-mono"
                />
              </div>

              <div className="flex justify-end gap-3 mt-6">
                <button
                  type="button"
                  onClick={() => setIsWalletModalOpen(false)}
                  className="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition text-sm"
                >
                  انصراف
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-amber-600 text-white rounded-xl shadow-lg shadow-amber-500/20 hover:bg-amber-700 transition text-sm"
                >
                  اعمال تغییرات
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
