import type {
  Subscription,
  Plan,
  AIModel,
  TokenWallet,
  UserStatus,
  Chat,
  WorkspacePlan,
  Workspace,
  DashboardResponse,
  WorkspaceType,
} from "@/types";

const API_BASE = process.env.NEXT_PUBLIC_API_URL;

if (!API_BASE) {
  throw new Error("NEXT_PUBLIC_API_URL is not defined");
}

// ============================
// Auth & Redirect Helpers
// ============================

export default function getToken() {
  if (typeof window === "undefined") {
    return null;
  }
  return localStorage.getItem("auth_token");
}
export async function logout() {
  if (typeof window !== "undefined") {
    localStorage.removeItem("auth_token");
    localStorage.removeItem("user");
    window.location.href = "/login";
  }
}

// ✅ تابع کمکی برای هدایت به صفحه ۴۰۴
function redirectToNotFound() {
  if (typeof window !== "undefined") {
    window.location.href = "/404";
  }
}

function headers(json = true) {
  const token = getToken();

  return {
    ...(token && {
      Authorization: `Bearer ${token}`,
    }),
    Accept: "application/json",
    ...(json && {
      "Content-Type": "application/json",
    }),
  };
}

// ============================
// Core Request
// ============================

async function request<T>(
  endpoint: string,
  options: RequestInit = {},
): Promise<T> {
  const response = await fetch(`${API_BASE}${endpoint}`, {
    ...options,
    headers: {
      ...headers(!(options.body instanceof FormData)),
      ...options.headers,
    },
  });

  if (response.status === 401) {
    logout();
    throw new Error("Unauthorized");
  }

  // ✅ مدیریت خودکار خطای ۴۰۴ در تمام درخواست‌های استاندارد
  if (response.status === 404) {
    redirectToNotFound();
    throw new Error("Resource not found");
  }

  if (!response.ok) {
    const error = await response.json().catch(() => null);
    throw new Error(error?.message ?? "API Request Failed");
  }

  return response.json();
}

// ============================
// Plans
// ============================

interface PlansResponse {
  data: Plan[];
}

export function getPlans() {
  return request<Plan[] | PlansResponse>("/plans");
}

// ============================
// Subscription
// ============================

export function getMySubscription() {
  return request<Subscription>("/my-subscription");
}

export function getWorkspaceTypes() {
  return request<WorkspaceType>("/workspace_types");
}

// ============================
// Dashboard
// ============================

export function getDashboard() {
  return request<DashboardResponse>("/dashboard");
}

export async function getDashboardData() {
  const [plansResponse, subscription] = await Promise.all([
    getPlans(),
    getWorkspaceTypes(),
    getMySubscription(),
  ]);

  return {
    plans: Array.isArray(plansResponse) ? plansResponse : plansResponse.data,
    subscription,
  };
}

// ============================
// Wallet
// ============================

export function getTokenWallet() {
  return request<TokenWallet>("/token-wallet");
}

// ============================
// User
// ============================

export function getUserStatus() {
  return request<UserStatus>("/user/status");
}

export async function getTodayUsage(workspaceId: number) {
  const data = await request<any>(`/usage/today?workspace_id=${workspaceId}`);

  return data.data ?? data;
}

// ============================
// Chat Stream
// ============================
// ============================
export async function sendMessageStream(
  message: string,
  workspaceId: number,
  chatId?: number,
  image?: File | null,
  modelId?: number,
  onChunk?: (chunk: {
    content?: string;
    image?: string | null;
    status?: string;
    type?: string;
    message?: string;
  }) => void,
  signal?: AbortSignal,
  file?: any | null,
  documentIds?: number[],
) {
  const formData = new FormData();

  formData.append("workspace_id", String(workspaceId));
  formData.append("message", message);

  if (chatId) {
    formData.append("chat_id", String(chatId));
  }

  if (modelId) {
    formData.append("model_id", String(modelId));
  }

  if (image) {
    formData.append("image", image);
  }

  if (file) {
    if (file instanceof File) {
      formData.append("pdf", file);
    } else if (file.file instanceof File) {
      formData.append("pdf", file.file);
    }
  }

  if (file?.paper?.id) {
    formData.append("paper_id", String(file.paper.id));
  }

  if (documentIds && documentIds.length > 0) {
    documentIds.forEach((id) => {
      formData.append("document_ids[]", String(id));
    });
  }

  const response = await fetch(`${API_BASE}/chats/stream`, {
    method: "POST",
    headers: headers(false),
    body: formData,
    signal,
  });

  if (response.status === 401) {
    logout();
    throw new Error("Unauthorized");
  }

  if (response.status === 404) {
    redirectToNotFound();
    throw new Error("Chat not found");
  }

  if (!response.ok) {
    const error = await response.json().catch(() => null);

    throw new Error(error?.message ?? "Chat error");
  }

  if (!response.body) {
    throw new Error("Empty stream");
  }

  const reader = response.body.getReader();

  const decoder = new TextDecoder("utf-8");

  let buffer = "";

  while (true) {
    const { done, value } = await reader.read();

    if (done) break;

    buffer += decoder.decode(value, {
      stream: true,
    });

    const lines = buffer.split(/\r?\n/);

    buffer = lines.pop() || "";

    for (const line of lines) {
      const trimmedLine = line.trim();

      if (!trimmedLine || !trimmedLine.startsWith("data:")) {
        continue;
      }

      const payload = trimmedLine.replace(/^data:\s*/, "").trim();

      if (!payload || payload === "[DONE]") {
        continue;
      }

      try {
        const json = JSON.parse(payload);

        if (json.error) {
          throw new Error(json.error);
        }

        /**
         * Support for:
         * 1. Status events (e.g. generating_image)
         * 2. OpenAI style choices & stream data
         *
         * NOTE: content may arrive as a raw JSON number
         * (e.g. {"content": 2}) instead of a string when
         * the model streams a single digit token. We must
         * coerce it to a string before checking/using it,
         * otherwise numeric tokens get silently dropped.
         */
        const choice = json?.choices?.[0];
        const rawContent = choice?.delta?.content ?? json?.content ?? "";
        const content =
          rawContent === null || rawContent === undefined
            ? ""
            : String(rawContent);

        const imageUrl = choice?.image ?? json?.image ?? null;
        const status = json?.status ?? null;
        const type = json?.type ?? null;

        if (content.length > 0 || imageUrl || status || type) {
          onChunk?.({
            content,
            image: imageUrl,
            status,
            type,
          });
        }
      } catch (e) {
        console.warn("Skipped invalid stream chunk:", payload);
      }
    }
  }
}
// =====
// ============================
// Chats
// ============================

export function getChats(workspaceId: number) {
  return request<Chat[]>(`/workspaces/${workspaceId}/chats`);
}

export function getChat(workspaceId: number, id: number) {
  return request<Chat>(`/workspaces/${workspaceId}/chats/${id}`);
}

export function deleteChat(workspaceId: number, id: number) {
  return request(`/workspaces/${workspaceId}/chats/${id}`, {
    method: "DELETE",
  });
}

export async function checkWorkspaceAccess(
  workspaceId: number | string,
  feature?: string,
) {
  const query = feature ? `?feature=${encodeURIComponent(feature)}` : "";
  return request<any>(`/workspaces/${workspaceId}/check-access${query}`);
}

// ============================
// Models
// ============================

export function getAvailableModels(workspace_id: number) {
  return request<{
    models: AIModel[];
  }>(`/workspace/${workspace_id}/models`);
}

// ============================
// File Upload
// ============================

export interface UploadedFileResponse {
  success: boolean;
  paper: {
    id: number;
    title: string;
    url: string;
    size: number;
  };
}

export async function uploadFile(file: File, conversationId: number) {
  const formData = new FormData();
  formData.append("file", file);
  formData.append("conversation_id", String(conversationId));

  return request("/files/upload", {
    method: "POST",
    body: formData,
  });
}

// ============================
// Workspace
// ============================

export interface WorkspaceResponse {
  success: boolean;
  workspace: Workspace;
}

export function getWorkspace(id: number) {
  return request<WorkspaceResponse>(`/workspaces/${id}`);
}

export function getWorkspaces() {
  return request<Workspace[]>("/workspaces");
}

export function getMyWorkspace() {
  return request<{
    success: boolean;
    data: Workspace;
  }>("/workspace/my");
}

// ============================
// Workspace Plans
// ============================

export function getWorkspacePlans(workspaceId: number) {
  return request<{
    success: boolean;
    data: WorkspacePlan[];
  }>(`/workspace-types/${workspaceId}/workspace_plans`);
}

// ============================
// Workspace Models
// ============================

export interface WorkspaceModelsResponse {
  success: boolean;
  workspace: {
    id: number;
    name: string;
  };
  plan: WorkspacePlan;
  models: AIModel[];
}

export function getWorkspaceModels(id: number) {
  return request<WorkspaceModelsResponse>(`/workspace/${id}/models`);
}

// ============================
// Subscribe
// ============================

export function subscribeWorkspace(workspacePlanId: number) {
  return request("/workspace/subscribe", {
    method: "POST",
    body: JSON.stringify({
      workspace_plan_id: workspacePlanId,
    }),
  });
}

export function cancelWorkspace() {
  return request("/workspace/cancel", {
    method: "POST",
  });
}

// ============================
// Workspace Payment
// ============================

export interface WorkspacePaymentResponse {
  success?: boolean;
  payment_url?: string;
  authority?: string;
  amount?: number;
  message?: string;
}

export function initiateWorkspacePayment(
  workspaceId: number,
  workspacePlanId: number,
) {
  return request<WorkspacePaymentResponse>("/workspace/subscription/payment", {
    method: "POST",
    body: JSON.stringify({
      workspace_id: workspaceId,
      workspace_plan_id: workspacePlanId,
    }),
  });
}

// ============================
// Documents
// ============================

export interface DocumentItem {
  id: number;
  name: string;
  path?: string;
  mime_type?: string;
  size?: number;
  status?: string;
  created_at?: string;
}

export interface DocumentsResponse {
  success: boolean;
  data: DocumentItem[];
}

export function getDocuments(workspaceId: number) {
  return request<DocumentsResponse>(`/workspaces/${workspaceId}/documents`);
}

export async function uploadDocument(workspaceId: number, file: File) {
  const formData = new FormData();
  formData.append("file", file);

  return request(`/workspaces/${workspaceId}/documents`, {
    method: "POST",
    body: formData,
  });
}

// ============================
// Document Chat
// ============================

export async function createChat(
  workspaceId: number,
  data: {
    document_ids: number[];
    model_id: number;
    workspace_id: number;
  },
) {
  const token = getToken();

  const response = await fetch(`${API_BASE}/workspaces/${workspaceId}/chats`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      ...(token && { Authorization: `Bearer ${token}` }),
    },
    body: JSON.stringify(data),
  });

  if (response.status === 401) {
    logout();
    throw new Error("Unauthorized");
  }

  // ✅ مدیریت هدایت به صفحه ۴۰۴ برای ایجاد چت یا ورک‌اسپیس نامعتبر
  if (response.status === 404) {
    redirectToNotFound();
    throw new Error("Not found");
  }

  if (!response.ok) {
    throw new Error("خطا در پاسخ سرور");
  }

  if (!response.body) {
    throw new Error("استریم دریافتی خالی است");
  }

  const reader = response.body.getReader();
  const decoder = new TextDecoder();
  let buffer = "";
  let chatId: number | null = null;

  while (true) {
    const { done, value } = await reader.read();
    if (done) break;

    buffer += decoder.decode(value, { stream: true });
    const lines = buffer.split(/\r?\n/);
    buffer = lines.pop() || "";

    for (const line of lines) {
      const trimmed = line.trim();
      if (!trimmed || !trimmed.startsWith("data:")) continue;

      const payload = trimmed.replace(/^data:\s*/, "").trim();

      if (!payload || payload === "[DONE]") continue;

      try {
        const parsed = JSON.parse(payload);

        if (parsed.chat_id) {
          chatId = parsed.chat_id;
        } else if (parsed.chat?.id) {
          chatId = parsed.chat.id;
        } else if (parsed.id) {
          chatId = parsed.id;
        }
      } catch (e) {
        // نادیده گرفتن خطوط نامعتبر
      }
    }
  }

  return {
    success: true,
    chat: {
      id: chatId ?? 0,
    },
  };
}

export async function deleteDocument(
  workspaceId: number | string,
  documentId: number | string,
) {
  const response = await fetch(
    `${API_BASE}/workspaces/${workspaceId}/documents/${documentId}`,
    {
      method: "DELETE",
      headers: headers(true),
    },
  );

  // ✅ مدیریت ۴۰۴ در حذف مستندات
  if (response.status === 404) {
    redirectToNotFound();
    throw new Error("Document not found");
  }

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}));
    throw new Error(errorData.message || "خطا در حذف سند");
  }

  return await response.json();
}
