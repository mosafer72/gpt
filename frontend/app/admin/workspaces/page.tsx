"use client";

import { useEffect, useState } from 'react';
import { 
  getWorkspaces, 
  createWorkspace, 
  updateWorkspace, 
  toggleWorkspaceStatus, 
  getWorkspaceTypes 
} from '../services';

export default function WorkspacesPage() {
  const [workspaces, setWorkspaces] = useState<any[]>([]);
  const [workspaceTypes, setWorkspaceTypes] = useState<any[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [updatingId, setUpdatingId] = useState<number | string | null>(null);
  const [isMounted, setIsMounted] = useState<boolean>(false);

  // === State های مربوط به مدال (پاپ‌آپ) ایجاد و ویرایش ===
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<'add' | 'edit'>('add');
  const [editingId, setEditingId] = useState<number | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // فرم دیتا بر اساس فیلدهای موردنیاز فضای کاری
  const [formData, setFormData] = useState({
    name: '',
    workspace_type_id: '',
    description: '',
    expires_at: '',
  });

  useEffect(() => {
    setIsMounted(true);
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      // بارگذاری همزمان لیست فضاهای کاری و انواع فضای کاری (برای انتخاب در فرم)
      const [workspacesRes, typesRes] = await Promise.all([
        getWorkspaces(),
        getWorkspaceTypes()
      ]);

      const workspacesData = Array.isArray(workspacesRes) ? workspacesRes : (workspacesRes as any).data || [];
      const typesData = Array.isArray(typesRes) ? typesRes : (typesRes as any).data || [];

      setWorkspaces(workspacesData);
      setWorkspaceTypes(typesData);
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

  // === مدیریت مدال ===
  const openModal = (mode: 'add' | 'edit', workspaceObj: any = null) => {
    setModalMode(mode);
    if (mode === 'edit' && workspaceObj) {
      setEditingId(workspaceObj.id);
      setFormData({
        name: workspaceObj.name || '',
        workspace_type_id: workspaceObj.workspace_type_id || workspaceObj.type_id || '',
        description: workspaceObj.description || '',
        expires_at: workspaceObj.expires_at ? workspaceObj.expires_at.split('T')[0] : '',
      });
    } else {
      setEditingId(null);
      setFormData({ name: '', workspace_type_id: '', description: '', expires_at: '' });
    }
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
    setFormData({ name: '', workspace_type_id: '', description: '', expires_at: '' });
    setEditingId(null);
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleFormSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      if (modalMode === 'add') {
        const res = await createWorkspace(formData);
        const newWorkspace = res.workspace || res;
        setWorkspaces([newWorkspace, ...workspaces]);
      } else if (modalMode === 'edit' && editingId) {
        const res = await updateWorkspace(editingId, formData);
        const updated = res.workspace || res;
        setWorkspaces(workspaces.map(w => w.id === editingId ? { ...w, ...updated } : w));
      }
      closeModal();
      loadData(); // بروزرسانی کامل برای هماهنگی با روابط دیتابیس
    } catch (err: any) {
      alert(err.message || 'خطا در ذخیره اطلاعات');
    } finally {
      setIsSubmitting(false);
    }
  };

  // === تغییر وضعیت فعال/غیرفعال ===
  const handleToggleStatus = async (id: number, currentStatus: boolean) => {
    try {
      setUpdatingId(id);
      await toggleWorkspaceStatus(id, currentStatus);
      setWorkspaces(prev =>
        prev.map(w => w.id === id ? { ...w, is_active: !currentStatus } : w)
      );
    } catch (err: any) {
      alert(err.message || 'عملیات با خطا مواجه شد');
    } finally {
      setUpdatingId(null);
    }
  };

  if (!isMounted) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-gray-500 text-lg">در حال آماده‌سازی پنل...</div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="flex items-center gap-3 text-gray-500">
          <svg className="animate-spin h-6 w-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>در حال بارگذاری فضاهای کاری...</span>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-red-500 bg-red-50 px-6 py-4 rounded-xl border border-red-200">
          {error}
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6 relative">
      {/* هدر */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h2 className="text-2xl font-bold admin-text-gradient">
          مدیریت فضاهای کاری
        </h2>
        <button 
          onClick={() => openModal('add')}
          className="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-emerald-500 text-white font-medium rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 hover:scale-[1.02] transition-all duration-200 flex items-center gap-2"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          ایجاد فضای کاری جدید
        </button>
      </div>

      {/* جدول */}
      <div className="admin-card overflow-hidden border border-white/20 bg-white/70 backdrop-blur-sm shadow-xl rounded-2xl">
        <div className="overflow-x-auto">
          <table className="w-full text-right text-sm text-gray-700">
            <thead className="bg-gray-50/80 text-gray-700 font-semibold border-b border-gray-200/60">
              <tr>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">نام فضای کاری</th>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">نوع</th>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">مالک</th>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">وضعیت فعالیت</th>
                <th className="px-6 py-4 text-xs uppercase tracking-wider">تاریخ انقضا</th>
                <th className="px-6 py-4 text-center text-xs uppercase tracking-wider">عملیات</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100/70">
              {workspaces.map((workspace) => (
                <tr key={workspace.id} className="hover:bg-blue-50/30 transition-colors duration-150">
                  <td className="px-6 py-4 font-medium text-gray-900">{workspace.name}</td>
                  <td className="px-6 py-4">
                    <span className="inline-flex items-center px-3 py-1 bg-indigo-50/80 text-indigo-700 rounded-full text-xs font-semibold border border-indigo-200/50">
                      {workspace.workspace_type?.display_name || workspace.type || '---'}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-gray-600">{workspace.user?.name || 'نامشخص'}</td>
                  <td className="px-6 py-4">
                    {workspace.is_active ? (
                      <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50/80 text-green-700 rounded-full text-xs font-semibold border border-green-200/50">
                        <span className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        فعال
                      </span>
                    ) : (
                      <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-red-50/80 text-red-700 rounded-full text-xs font-semibold border border-red-200/50">
                        <span className="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                        غیرفعال
                      </span>
                    )}
                  </td>
                  <td className="px-6 py-4 font-mono text-sm" dir="ltr">
                    {workspace.expires_at ? new Date(workspace.expires_at).toLocaleDateString('fa-IR') : '---'}
                  </td>
                  <td className="px-6 py-4 text-center flex items-center justify-center gap-2">
                    {/* دکمه ویرایش */}
                    <button
                      onClick={() => openModal('edit', workspace)}
                      className="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors text-xs font-medium border border-blue-200"
                    >
                      ویرایش
                    </button>

                    {/* دکمه تغییر وضعیت */}
                    <button
                      onClick={() => handleToggleStatus(workspace.id, workspace.is_active)}
                      disabled={updatingId === workspace.id}
                      className={`
                        px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 border
                        ${updatingId === workspace.id ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                          : workspace.is_active
                            ? 'bg-red-50 text-red-600 hover:bg-red-100 border-red-200'
                            : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border-emerald-200'
                        }
                      `}
                    >
                      {updatingId === workspace.id ? 'در حال...' : workspace.is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {workspaces.length === 0 && (
            <div className="flex flex-col items-center justify-center py-16 text-gray-500">
              <svg className="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <span className="text-lg font-medium">هیچ فضای کاری یافت نشد</span>
              <span className="text-sm text-gray-400 mt-1">برای ایجاد فضای جدید، دکمه بالا را کلیک کنید.</span>
            </div>
          )}
        </div>
      </div>

      {workspaces.length > 0 && (
        <div className="text-sm text-gray-500 px-2">
          نمایش {workspaces.length} فضای کاری
        </div>
      )}

      {/* ============================= */}
      {/* مدال ایجاد / ویرایش فضای کاری */}
      {/* ============================= */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div 
            className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" 
            onClick={closeModal}
          ></div>
          
          <div className="relative bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-gray-100">
            <div className="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
              <h3 className="text-lg font-bold text-gray-800">
                {modalMode === 'add' ? 'ایجاد فضای کاری جدید' : 'ویرایش فضای کاری'}
              </h3>
              <button onClick={closeModal} className="text-gray-400 hover:text-gray-600 transition-colors">
                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form onSubmit={handleFormSubmit} className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">نام فضای کاری</label>
                <input
                  type="text"
                  name="name"
                  required
                  value={formData.name}
                  onChange={handleInputChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                  placeholder="مثال: تیم توسعه"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">نوع فضای کاری</label>
                <select
                  name="workspace_type_id"
                  required
                  value={formData.workspace_type_id}
                  onChange={handleInputChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm bg-white"
                >
                  <option value="">انتخاب کنید...</option>
                  {workspaceTypes.map((type) => (
                    <option key={type.id} value={type.id}>
                      {type.display_name} ({type.key})
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">تاریخ انقضا</label>
                <input
                  type="date"
                  name="expires_at"
                  value={formData.expires_at}
                  onChange={handleInputChange}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">توضیحات</label>
                <textarea
                  name="description"
                  value={formData.description}
                  onChange={handleInputChange}
                  rows={3}
                  className="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm resize-none"
                  placeholder="توضیحات تکمیلی..."
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
                    <svg className="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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