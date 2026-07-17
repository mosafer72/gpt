import type {
  Plan,
  AIModel,
  LlmProvider,
  User,
  Chat,
  Message,
  Transaction,
  LlmRequest,
  Memory,
  DashboardStats,
  PaginatedResponse,
} from "@/types/admin_types";

const API_BASE = process.env.NEXT_PUBLIC_API_URL;

if (!API_BASE) {
  throw new Error("NEXT_PUBLIC_API_URL is not defined");
}

// ============================
// Admin Token Management
// ============================

function getAdminToken() {
  if (typeof window === "undefined") return null;
  return localStorage.getItem("admin_token") || localStorage.getItem("auth_token");
}

function adminHeaders(json: boolean = true) {
  const token = getAdminToken();
  return {
    ...(token && { Authorization: `Bearer ${token}` }),
    Accept: "application/json",
    ...(json && { "Content-Type": "application/json" }),
  };
}

async function adminRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const response = await fetch(`${API_BASE}${endpoint}`, {
    ...options,
    headers: {
      ...adminHeaders(!(options.body instanceof FormData)),
      ...options.headers,
    },
  });

  if (response.status === 401) {
    if (typeof window !== "undefined") {
      localStorage.removeItem("admin_token");
      localStorage.removeItem("auth_token");
      window.location.href = "/login?redirect=admin";
    }
    throw new Error("Unauthorized - Admin");
  }

  if (!response.ok) {
    const error = await response.json().catch(() => null);
    throw new Error(error?.message || "Admin API Request Failed");
  }

  return response.json();
}

// ============================
// Admin: Plans
// ============================

export const adminPlans = {
  list: () => adminRequest<Plan[]>("/admin/plans"),
  get: (id: number) => adminRequest<Plan>(`/admin/plans/${id}`),
  create: (data: Partial<Plan>) =>
    adminRequest<Plan>("/admin/plans", {
      method: "POST",
      body: JSON.stringify(data),
    }),
  update: (id: number, data: Partial<Plan>) =>
    adminRequest<Plan>(`/admin/plans/${id}`, {
      method: "PUT",
      body: JSON.stringify(data),
    }),
  delete: (id: number) =>
    adminRequest(`/admin/plans/${id}`, { method: "DELETE" }),
};

// ============================
// Admin: AI Models
// ============================

export const adminModels = {
  list: () => adminRequest<AIModel[]>("/admin/models"),
  get: (id: number) => adminRequest<AIModel>(`/admin/models/${id}`),
  create: (data: Partial<AIModel>) =>
    adminRequest<AIModel>("/admin/models", {
      method: "POST",
      body: JSON.stringify(data),
    }),
  update: (id: number, data: Partial<AIModel>) =>
    adminRequest<AIModel>(`/admin/models/${id}`, {
      method: "PUT",
      body: JSON.stringify(data),
    }),
  delete: (id: number) =>
    adminRequest(`/admin/models/${id}`, { method: "DELETE" }),
};

// ============================
// Admin: Providers
// ============================

export const adminProviders = {
  list: () => adminRequest<LlmProvider[]>("/admin/providers"),
  get: (id: number) => adminRequest<LlmProvider>(`/admin/providers/${id}`),
  create: (data: Partial<LlmProvider>) =>
    adminRequest<LlmProvider>("/admin/providers", {
      method: "POST",
      body: JSON.stringify(data),
    }),
  update: (id: number, data: Partial<LlmProvider>) =>
    adminRequest<LlmProvider>(`/admin/providers/${id}`, {
      method: "PUT",
      body: JSON.stringify(data),
    }),
  delete: (id: number) =>
    adminRequest(`/admin/providers/${id}`, { method: "DELETE" }),
};

// ============================
// Admin: Users
// ============================

export const adminUsers = {
  list: () => adminRequest<User[]>("/admin/users"),
  get: (id: number) => adminRequest<User>(`/admin/users/${id}`),
  delete: (id: number) =>
    adminRequest(`/admin/users/${id}`, { method: "DELETE" }),
};

// ============================
// Admin: Chats
// ============================

export const adminChats = {
  list: (params?: Record<string, any>) => {
    const query = params ? `?${new URLSearchParams(params)}` : "";
    return adminRequest<PaginatedResponse<Chat>>(`/admin/chats${query}`);
  },
  get: (id: number) => adminRequest<Chat>(`/admin/chats/${id}`),
  delete: (id: number) =>
    adminRequest(`/admin/chats/${id}`, { method: "DELETE" }),
};

// ============================
// Admin: Transactions
// ============================

export const adminTransactions = {
  list: (params?: Record<string, any>) => {
    const query = params ? `?${new URLSearchParams(params)}` : "";
    return adminRequest<PaginatedResponse<Transaction>>(`/admin/transactions${query}`);
  },
};

// ============================
// Admin: LLM Requests
// ============================

export const adminLlmRequests = {
  list: (params?: Record<string, any>) => {
    const query = params ? `?${new URLSearchParams(params)}` : "";
    return adminRequest<PaginatedResponse<LlmRequest>>(`/admin/llm-requests${query}`);
  },
  get: (id: number) => adminRequest<LlmRequest>(`/admin/llm-requests/${id}`),
};

// ============================
// Admin: Memories
// ============================

export const adminMemories = {
  list: (params?: Record<string, any>) => {
    const query = params ? `?${new URLSearchParams(params)}` : "";
    return adminRequest<PaginatedResponse<Memory>>(`/admin/memories${query}`);
  },
  delete: (id: number) =>
    adminRequest(`/admin/memories/${id}`, { method: "DELETE" }),
};

// ============================
// Admin API (یکجا)
// ============================

export const adminApi = {
  plans: adminPlans,
  models: adminModels,
  providers: adminProviders,
  users: adminUsers,
  chats: adminChats,
  transactions: adminTransactions,
  llmRequests: adminLlmRequests,
  memories: adminMemories,
};
export type {
  Chat,
  Message,
  User,
  Plan,
  AIModel,
  LlmProvider,
  Transaction,
  LlmRequest,
  Memory,
  DashboardStats,
  PaginatedResponse,
};