import { Workspace } from "@/types";

const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";

if (!API_BASE) {
  throw new Error("NEXT_PUBLIC_API_URL is not defined");
}

// ============================
// Auth & Helpers
// ============================

/**
 * دریافت توکن از LocalStorage به صورت امن در سمت کلاینت
 */
function getToken() {
  if (typeof window !== "undefined") {
    return localStorage.getItem("auth_token");
  }
  return null;
}

function getHeaders(isJson = true): HeadersInit {
  const token = getToken();

  return {
    ...(token && { Authorization: `Bearer ${token}` }),
    Accept: "application/json",
    ...(isJson && { "Content-Type": "application/json" }),
  };
}

/**
 * تابع مرکزی برای ارسال درخواست‌های HTTP در سمت کلاینت
 */
async function request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const isFormData = options.body instanceof FormData;

  const response = await fetch(`${API_BASE}${endpoint}`, {
    ...options,
    headers: {
      ...getHeaders(!isFormData),
      ...options.headers,
    },
  });

  // اگر کاربر دسترسی نداشت یا توکن منقضی شده بود
  if (response.status === 401) {
    if (typeof window !== "undefined") {
      localStorage.removeItem("auth_token");
      window.location.href = "/login"; // ریدایرکت امن کلاینتی
    }
    throw new Error("Unauthorized");
  }

  if (!response.ok) {
    const error = await response.json().catch(() => null);
    throw new Error(error?.message ?? "API Request Failed");
  }

  return response.json();
}

// ============================
// Actions / Services
// ============================

/**
 * دریافت لیست Workspace ها در کلاینت
 */
export async function getWorkspaces(): Promise<Workspace[]> {
  return request<Workspace[]>("/admin/workspaces", {
    cache: "no-store" // غیرفعال کردن کش برای دریافت اطلاعات لحظه‌ای در کلاینت
  });
}

/**
 * تغییر وضعیت Workspace
 */
export async function toggleWorkspaceStatus(workspaceId: number, currentStatus: boolean) {
  return request(`/admin/workspaces/${workspaceId}/toggle-status`, {
    method: "PATCH",
    body: JSON.stringify({ is_active: !currentStatus }),
  });
}

export async function createWorkspace(data: any) {
  return request("/admin/workspaces", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateWorkspace(id: number, data: any) {
  return request(`/admin/workspaces/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

// دریافت انواع فضای کاری همراه با پلن‌ها
export async function getWorkspaceTypes() {
  return request("/admin/workspace-types");
}

// ساخت نوع جدید
export async function createWorkspaceType(data: any) {
  return request("/admin/workspace-types", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

// ویرایش نوع موجود
export async function updateWorkspaceType(id: number, data: any) {
  return request(`/admin/workspace-types/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

// تغییر وضعیت
export async function toggleWorkspaceTypeStatus(id: number, currentStatus: boolean) {
  return request(`/admin/workspace-types/${id}/toggle-status`, {
    method: "PATCH",
    body: JSON.stringify({ is_active: !currentStatus }),
  });
}

export async function getLlmProviders() {
  return request("/admin/llm-providers");
}

export async function createLlmProvider(data: any) {
  return request("/admin/llm-providers", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateLlmProvider(id: number, data: any) {
  return request(`/admin/llm-providers/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function toggleLlmProviderStatus(id: number, currentStatus: boolean) {
  return request(`/admin/llm-providers/${id}/toggle-status`, {
    method: "PATCH",
    body: JSON.stringify({ is_active: !currentStatus }),
  });
}

export async function getAiModels() {
  return request("/admin/ai-models");
}

export async function createAiModel(data: any) {
  return request("/admin/ai-models", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateAiModel(id: number, data: any) {
  return request(`/admin/ai-models/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function toggleAiModelStatus(id: number, currentStatus: boolean) {
  return request(`/admin/ai-models/${id}/toggle-status`, {
    method: "PATCH",
    body: JSON.stringify({ is_active: !currentStatus }),
  });
}

export async function getWorkspacePlans() {
  return request("/admin/workspace-plans");
}

export async function createWorkspacePlan(data: any) {
  return request("/admin/workspace-plans", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateWorkspacePlan(id: number, data: any) {
  return request(`/admin/workspace-plans/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function toggleWorkspacePlanStatus(id: number, currentStatus: boolean) {
  return request(`/admin/workspace-plans/${id}/toggle-status`, {
    method: "PATCH",
    body: JSON.stringify({ is_active: !currentStatus }),
  });
}

export async function getWorkspaceSubscriptions() {
  return request("/admin/workspace-subscriptions");
}

export async function createWorkspaceSubscription(data: any) {
  return request("/admin/workspace-subscriptions", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateWorkspaceSubscription(id: number, data: any) {
  return request(`/admin/workspace-subscriptions/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function toggleWorkspaceSubscriptionStatus(id: number, currentStatus: boolean) {
  return request(`/admin/workspace-subscriptions/${id}/toggle-status`, {
    method: "PATCH",
    body: JSON.stringify({ is_active: !currentStatus }),
  });
}


export async function getUsers(search = "") {
  return request(`/admin/users${search ? `?search=${search}` : ""}`);
}

export async function createUser(data: any) {
  return request("/admin/users", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateUser(id: number, data: any) {
  return request(`/admin/users/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function deleteUser(id: number) {
  return request(`/admin/users/${id}`, {
    method: "DELETE",
  });
}

export async function loginAsUser(userId: number) {
  return request(`/admin/users/${userId}/login-as`, { method: "POST" });
}

export async function getUserWorkspaces(userId: number) {
  return request(`/admin/users/${userId}/workspaces`);
}

/**
 * ایجاد و انتساب فضای کاری به همراه نوع و پلن اشتراک به کاربر
 */
export async function assignWorkspaceToUser(userId: number, data: { workspace_type_id: string | number; workspace_plan_id: string | number; name?: string }) {
  return request(`/admin/users/${userId}/assign-workspace`, {
    method: "POST",
    body: JSON.stringify(data),
  });
}

/**
 * مدیریت کیف پول (افزایش، کاهش یا صفر کردن توکن‌های فضای کاری کاربر)
 */
export async function updateUserWallet(userId: number, data: { workspace_id: number; amount: number; action: 'add' | 'subtract' | 'set' }) {
  return request(`/admin/users/${userId}/wallet`, {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function cancelUserWorkspace(userId: number, workspaceId: number) {
  return request(`/admin/users/${userId}/workspaces/${workspaceId}/cancel`, {
    method: "POST",
  });
}