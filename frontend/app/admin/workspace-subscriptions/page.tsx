"use client";

import { useEffect, useState } from 'react';
import { 
  getWorkspaceSubscriptions, 
  createWorkspaceSubscription, 
  updateWorkspaceSubscription, 
  toggleWorkspaceSubscriptionStatus,
  getWorkspacePlans,
  getUsers,
  getWorkspaces
} from '../services';

export default function WorkspaceSubscriptionsPage() {
  const [subscriptions, setSubscriptions] = useState<any[]>([]);
  const [plans, setPlans] = useState<any[]>([]);
  const [users, setUsers] = useState<any[]>([]);
  const [workspaces, setWorkspaces] = useState<any[]>([]);
  
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [updatingId, setUpdatingId] = useState<number | string | null>(null);
  const [isMounted, setIsMounted] = useState<boolean>(false);

  // === State های مدال ===
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<'add' | 'edit'>('add');
  const [editingId, setEditingId] = useState<number | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  const [formData, setFormData] = useState({
    user_id: '',
    workspace_id: '',
    workspace_plan_id: '',
    started_at: '',
    expires_at: '',
    transaction_id: '',
  });

  useEffect(() => {
    setIsMounted(true);
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const [subsRes, plansRes, usersRes, workspacesRes] = await Promise.all([
        getWorkspaceSubscriptions(),
        getWorkspacePlans(),
        getUsers().catch(() => []),
        getWorkspaces().catch(() => [])
      ]);

      setSubscriptions(Array.isArray(subsRes) ? subsRes : (subsRes as any).data || []);
      setPlans(Array.isArray(plansRes) ? plansRes : (plansRes as any).data || []);
      setUsers(Array.isArray(usersRes) ? usersRes : (usersRes as any).data || []);
      setWorkspaces(Array.isArray(workspacesRes) ? workspacesRes : (workspacesRes as any).data || []);
    } catch (err: any) {
      setError(err.message || 'خطا در بارگذاری داده‌ها');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (!isMounted) return;
    loadData();
  }, [isMounted]);

  const openModal = (mode: 'add' | 'edit', obj: any = null) => {
    setModalMode(mode);
    if (mode === 'edit' && obj) {
      setEditingId(obj.id);
      setFormData({
        user_id: obj.user_id || '',
        workspace_id: obj.workspace_id || '',
        workspace_plan_id: obj.workspace_plan_id || '',
        started_at: obj.started_at ? obj.started_at.substring(0, 10) : '',
        expires_at: obj.expires_at ? obj.expires_at.substring(0, 10) : '',
        transaction_id: obj.transaction_id || '',
      });
    } else {
      setEditingId(null);
      setFormData({
        user_id: '',
        workspace_id: '',
        workspace_plan_id: '',
        started_at: '',
        expires_at: '',
        transaction_id: '',
      });
    }
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
    setEditingId(null);
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleFormSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      if (modalMode === 'add') {
        await createWorkspaceSubscription(formData);
      } else if (modalMode === 'edit' && editingId) {
        await updateWorkspaceSubscription(editingId, formData);
      }
      closeModal();
      loadData();
    } catch (err: any) {
      alert(err.message || 'خطا در ذخیره اطلاعات');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleToggleStatus = async (id: number, currentStatus: boolean) => {
    try {
      setUpdatingId(id);
      await toggleWorkspaceSubscriptionStatus(id, currentStatus);
      setSubscriptions(prev =>
        prev.map(sub => sub.id === id ? { ...sub, is_active: !currentStatus } : sub)
      );
    } catch (err: any) {
      alert(err.message || 'عملیات با خطا مواجه شد');
    } finally {
      setUpdatingId(null);
    }
  };

  if (!isMounted) return <div className="h-64 flex items-center justify-center">در حال آماده‌سازی...</div>;
  if (loading) return <div className="h-64 flex items-center justify-center text-blue-500">در حال بارگذاری...</div>;
  if (error) return <div className="h-64 flex items-center justify-center text-red-500">{error}</div>;

  return (
    <div className="space-y-6 relative">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h2 className="text-2xl font-bold admin-text-gradient">مدیریت اشتراک‌های فضای کاری</h2>
        <button 
          onClick={() => openModal('add')}
          className="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-emerald-500 text-white font-medium rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 hover:scale-[1.02] transition-all duration-200 flex items-center gap-2"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          افزودن اشتراک جدید
        </button>
      </div>

      <div className="admin-card overflow-hidden border border-white/20 bg-white/70 backdrop-blur-sm shadow-xl rounded-2xl">
        <div className="overflow-x-auto">
          <table className="w-full text-right text-sm text-gray-700">
            <thead className="bg-gray-50/80 text-gray-700 font-semibold border-b border-gray-200/60">
              <tr>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">کاربر</th>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">فضای کاری</th>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">طرح اشتراک</th>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">تاریخ شروع</th>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">تاریخ انقضا</th>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">کد تراکنش</th>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">وضعیت</th>
                <th className="px-6 py-4 text-center text-xs uppercase tracking-wider">عملیات</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100/70">
              {subscriptions.map((item) => (
                <tr key={item.id} className="hover:bg-blue-50/30 transition-colors duration-150">
                  <td className="px-6 py-4 font-bold text-gray-900">{item.user?.name || item.user?.email || `کاربر #${item.user_id}`}</td>
                  <td className="px-6 py-4 text-gray-600">{item.workspace?.name || `فضا #${item.workspace_id}`}</td>
                  <td className="px-6 py-4 font-medium text-indigo-600">{item.workspace_plan?.display_name || `طرح #${item.workspace_plan_id}`}</td>
                  <td className="px-6 py-4 font-mono text-xs">{item.started_at ? item.started_at.substring(0, 10) : '---'}</td>
                  <td className="px-6 py-4 font-mono text-xs">{item.expires_at ? item.expires_at.substring(0, 10) : '---'}</td>
                  <td className="px-6 py-4 font-mono text-xs text-gray-500">{item.transaction_id || '---'}</td>
                  <td className="px-6 py-4">
                    {item.is_active ? (
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
                    <button
                      onClick={() => openModal('edit', item)}
                      className="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors text-xs font-medium border border-blue-200"
                    >
                      ویرایش
                    </button>
                    <button
                      onClick={() => handleToggleStatus(item.id, item.is_active)}
                      disabled={updatingId === item.id}
                      className={`
                        px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 border
                        ${updatingId === item.id ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                          : item.is_active
                            ? 'bg-red-50 text-red-600 hover:bg-red-100 border-red-200'
                            : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border-emerald-200'
                        }
                      `}
                    >
                      {updatingId === item.id ? 'در حال...' : item.is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی'}
                    </button>
                  </td>
                </tr>
              ))}
              
              {subscriptions.length === 0 && !loading && (
                <tr>
                  <td colSpan={8} className="px-6 py-12 text-center text-gray-400">
                    هیچ اشتراکی یافت نشد.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
          <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onClick={closeModal}></div>
          
          <div className="relative bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-gray-100 my-8 z-10">
            <div className="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
              <h3 className="text-lg font-bold text-gray-800">
                {modalMode === 'add' ? 'افزودن اشتراک جدید' : 'ویرایش اشتراک'}
              </h3>
              <button onClick={closeModal} className="text-gray-400 hover:text-gray-600 transition-colors">
                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form onSubmit={handleFormSubmit} className="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">کاربر</label>
                <select
                  name="user_id"
                  required
                  value={formData.user_id}
                  onChange={handleInputChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white"
                >
                  <option value="">انتخاب کاربر...</option>
                  {users.map((u) => (
                    <option key={u.id} value={u.id}>{u.name || u.email} (شناسه: {u.id})</option>
                  ))}
                </select>
                {users.length === 0 && (
                  <input
                    type="number"
                    name="user_id"
                    required
                    value={formData.user_id}
                    onChange={handleInputChange}
                    placeholder="شناسه کاربر (User ID)"
                    className="mt-2 w-full px-4 py-2 border border-gray-200 rounded-xl text-sm"
                  />
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">فضای کاری</label>
                <select
                  name="workspace_id"
                  required
                  value={formData.workspace_id}
                  onChange={handleInputChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white"
                >
                  <option value="">انتخاب فضای کاری...</option>
                  {workspaces.map((w) => (
                    <option key={w.id} value={w.id}>{w.name} (شناسه: {w.id})</option>
                  ))}
                </select>
                {workspaces.length === 0 && (
                  <input
                    type="number"
                    name="workspace_id"
                    required
                    value={formData.workspace_id}
                    onChange={handleInputChange}
                    placeholder="شناسه فضای کاری (Workspace ID)"
                    className="mt-2 w-full px-4 py-2 border border-gray-200 rounded-xl text-sm"
                  />
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">طرح اشتراک</label>
                <select
                  name="workspace_plan_id"
                  required
                  value={formData.workspace_plan_id}
                  onChange={handleInputChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white"
                >
                  <option value="">انتخاب طرح اشتراک...</option>
                  {plans.map((p) => (
                    <option key={p.id} value={p.id}>{p.display_name} ({p.name})</option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">تاریخ شروع</label>
                  <input
                    type="date"
                    name="started_at"
                    required
                    value={formData.started_at}
                    onChange={handleInputChange}
                    className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-mono"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">تاریخ انقضا</label>
                  <input
                    type="date"
                    name="expires_at"
                    required
                    value={formData.expires_at}
                    onChange={handleInputChange}
                    className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-mono"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">کد تراکنش (Transaction ID)</label>
                <input
                  type="text"
                  name="transaction_id"
                  value={formData.transaction_id}
                  onChange={handleInputChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-mono"
                  placeholder="مثال: tr_123456789"
                />
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
                    <svg className="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                  )}
                  {modalMode === 'add' ? 'ایجاد' : 'ذخیره تغییرات'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}