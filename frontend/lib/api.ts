import type {
  Subscription,
  Plan,
  AIModel,
  TokenWallet,
  UserStatus,
  Chat,
} from "@/types";

const API_BASE = process.env.NEXT_PUBLIC_API_URL;

if (!API_BASE) {
  throw new Error("NEXT_PUBLIC_API_URL is not defined");
}

// ============================
// Auth
// ============================

function getToken() {
  if (typeof window === "undefined") {
    return null;
  }

  return localStorage.getItem("auth_token");
}

function logout() {
  if (typeof window !== "undefined") {
    localStorage.removeItem("auth_token");

    localStorage.removeItem("user");

    window.location.href = "/login";
  }
}

function headers(json: boolean = true) {
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
  const response = await fetch(
    `${API_BASE}${endpoint}`,

    {
      ...options,

      headers: {
        ...headers(!(options.body instanceof FormData)),

        ...options.headers,
      },
    },
  );

  if (response.status === 401) {
    logout();

    throw new Error("Unauthorized");
  }

  if (!response.ok) {
    const error = await response.json().catch(() => null);

    throw new Error(error?.message || "API Request Failed");
  }

  return response.json();
}

// ============================
// Types
// ============================

// ============================
// Dashboard
// ============================

export async function getDashboardData() {
  const [plansResponse, subscription] = await Promise.all([
    getPlans(),
    getMySubscription(),
  ]);

  const plans = Array.isArray(plansResponse)
    ? plansResponse
    : plansResponse.data;

  return {
    plans,
    subscription,
  };
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

// ============================
// Token Wallet
// ============================

export function getTokenWallet() {
  return request<TokenWallet>("/token-wallet");
}

// ============================
// Usage
// ============================

export async function getTodayUsage() {
  const data = await request<any>("/usage/today");

  return data.data ?? data;
}

export function getUserStatus() {
  return request<UserStatus>("/user/status");
}

export async function sendMessageStream(
  message: string,
  chatId?: number,
  image?: File | null,
  modelId?: number,
  onChunk?: (text: string) => void,
  signal?: AbortSignal,
  file?: UploadedFileResponse | null,
) {
  const formData = new FormData();

  formData.append("model_id", String(modelId));
  formData.append("message", message);
  if (file) {
    formData.append("file", JSON.stringify(file));
  }
  if (chatId) {
    formData.append("chat_id", String(chatId));
  }

  if (image) {
    formData.append("image", image);
  }

  const response = await fetch(`${API_BASE}/chats/stream`, {
    method: "POST",
    headers: headers(false),
    body: formData,
    signal,
  });

  // عدم احراز هویت
  if (response.status === 401) {
    logout();
    throw new Error("Unauthorized");
  }

  // خطای بک‌اند قبل از شروع stream
  if (!response.ok) {
    let errorData: any = null;

    try {
      errorData = await response.json();
    } catch {
      errorData = null;
    }

    throw {
      type: "backend",
      status: response.status,
      message: errorData?.message || "خطایی رخ داده است",
    };
  }

  if (!response.body) {
    throw new Error("Empty stream");
  }

  const reader = response.body.getReader();
  const decoder = new TextDecoder();

  let buffer = "";

  while (true) {
    const { done, value } = await reader.read();

    if (done) {
      break;
    }

    buffer += decoder.decode(value, {
      stream: true,
    });

    const events = buffer.split("\n\n");

    buffer = events.pop() || "";

    for (const event of events) {
      if (!event.startsWith("data:")) {
        continue;
      }

      const payload = event.replace(/^data:\s*/, "").trim();

      // پایان SSE
      if (payload === "[DONE]") {
        continue;
      }

      try {
        const json = JSON.parse(payload);

        // خطای ارسال شده از سرور
        if (json.error) {
          throw new Error(json.error);
        }

        // ==========================
        // پاسخ تصویر
        // ==========================
        if (json.type === "image" && json.image_url) {
          onChunk?.(
            JSON.stringify({
              type: "image",

              image_url: json.image_url,
            }),
          );

          continue;
        }

        // ==========================
        // پاسخ متنی Stream
        // ==========================
        if (typeof json.content === "string" && json.content.length > 0) {
          onChunk?.(
            JSON.stringify({
              content: json.content,
            }),
          );
        }
      } catch (err: any) {
        // payload غیر JSON
        if (err instanceof SyntaxError) {
          console.log("Invalid stream chunk", payload);

          continue;
        }

        throw err;
      }
    }
  }
}
// ============================
// Chats
// ============================

export function getChats() {
  return request<Chat[]>("/chats");
}

export function getChat(id: number) {
  return request<Chat>(`/chats/${id}`);
}

export async function deleteChat(id: number) {
  await request(`/chats/${id}`, {
    method: "DELETE",
  });
}

// ============================
// Payment
// ============================

export interface PaymentResponse {
  payment_url?: string;

  message?: string;
}

export function initiatePayment(planId: number) {
  return request<PaymentResponse>(
    "/initiate-payment",

    {
      method: "POST",

      body: JSON.stringify({
        plan_id: planId,
      }),
    },
  );
}
export function getAvailableModels() {
  return request<{
    models: AIModel[];
  }>("/models");
}

// ============================
// File Upload
// ============================

export interface UploadedFileResponse {
  path: string;
  name: string;
  size: number;
  mime: string;
}

export async function uploadFile(file: File) {
  const formData = new FormData();
  formData.append("file", file);

  const response = await fetch(`${API_BASE}/files/upload`, {
    method: "POST",
    headers: headers(false), // بدون Content-Type
    body: formData,
  });

  if (response.status === 401) {
    logout();
    throw new Error("Unauthorized");
  }

  if (!response.ok) {
    const error = await response.json().catch(() => null);
    throw new Error(error?.message || "خطا در آپلود فایل");
  }

  return response.json();
}
