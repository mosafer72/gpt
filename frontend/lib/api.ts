import { Chat , Message} from "@/types";
const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";

const getToken = () => localStorage.getItem("auth_token");
const headers = (): HeadersInit => ({
  Authorization: `Bearer ${getToken()}`,
  Accept: "application/json",
  "Content-Type": "application/json",
});

const handleUnauthorized = (res: Response) => {
  if (res.status === 401) {
    localStorage.removeItem("auth_token");
    document.cookie =
      "auth_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
    window.location.href = "/login";
    throw new Error("Unauthorized");
  }
  return res;
};

export const getChats = async (): Promise<Chat[]> => {
  const res = await fetch(`${API_BASE}/chats`, { headers: headers() });
  handleUnauthorized(res);
  if (!res.ok) throw new Error("Failed to fetch");
  return res.json();
};

export const getChat = async (id: number): Promise<Chat> => {
  const res = await fetch(`${API_BASE}/chats/${id}`, { headers: headers() });
  handleUnauthorized(res);
  if (!res.ok) throw new Error("Failed to fetch chat");
  return res.json();
};

export const deleteChat = async (id: number): Promise<void> => {
  const res = await fetch(`${API_BASE}/chats/${id}`, {
    method: "DELETE",
    headers: headers(),
  });
  handleUnauthorized(res);
  if (!res.ok) throw new Error("Failed to delete");
};

export const sendMessage = async (
  message: string,
  chatId?: number,
  image?: File | null,
) => {
  const formData = new FormData();
  formData.append("message", message);
  if (chatId) formData.append("chat_id", String(chatId));
  if (image) formData.append("image", image);

  const res = await fetch(`${API_BASE}/chats`, {
    method: "POST",
    body: formData,
    headers: { Authorization: `Bearer ${getToken()}` },
  });
  handleUnauthorized(res);
  if (!res.ok) {
    const err = await res.json();
    throw new Error(err.error || "خطا در ارسال");
  }
  return res.json();
};

// lib/api.ts
export async function getUserStatus(): Promise<{
  has_subscription: boolean;
  daily_message_limit: number;
  today_messages_count: number;
  remaining_messages: number;
}> {
  const token = getToken();
  if (!token) throw new Error("No token");

  const res = await fetch(`${API_BASE}/user/status`, {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
  });
  if (res.status === 401) {
    handleUnauthorized(res);
  }
  if (!res.ok) throw new Error("Failed to fetch status");
  return res.json();
}
export async function sendMessageStream(
  message: string,

  chatId?: number,

  image?: File | null,

  onChunk?: (text: string) => void,

  signal?: AbortSignal,
): Promise<void> {
  const token = getToken();

  if (!token) throw new Error("No token");

  const formData = new FormData();

  formData.append("message", message);

  if (chatId) {
    formData.append("chat_id", String(chatId));
  }

  if (image) {
    formData.append("image", image);
  }

  const res = await fetch(`${API_BASE}/chats/stream`, {
    method: "POST",

    headers: {
      Authorization: `Bearer ${token}`,

      Accept: "text/event-stream",
    },

    body: formData,

    // اضافه شد
    signal,
  });

  if (res.status === 401) {
    handleUnauthorized(res);
  }

  if (!res.ok || !res.body) {
    throw new Error("Failed to stream message");
  }

  const reader = res.body.getReader();

  const decoder = new TextDecoder();

  let buffer = "";

  try {
    while (true) {
      const { done, value } = await reader.read();

      if (done) break;

      buffer += decoder.decode(value, {
        stream: true,
      });

      const events = buffer.split("\n\n");

      buffer = events.pop() || "";

      for (const event of events) {
        if (!event.startsWith("data: ")) continue;

        const data = event.replace("data: ", "");

        if (data === "[DONE]") {
          continue;
        }

        const json = JSON.parse(data);

        if (json.content) {
          onChunk?.(json.content);
        }
      }
    }
  } catch (error: any) {
    if (error.name === "AbortError") {
      console.log("Stream aborted");

      // مهم:
      // reader را آزاد کن
      await reader.cancel();

      throw error;
    }

    throw error;
  }
}
export async function getTodayUsage() {
  const token = localStorage.getItem("auth_token");

  const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/usage/today`, {
    headers: {
      Authorization: `Bearer ${token}`,

      Accept: "application/json",
    },
  });

  const json = await res.json();

  return json.data;
}
export async function getChatMessages(
  chatId: number,
  page: number = 1,
  perPage: number = 10,
): Promise<{
  data: Message[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}> {
  const token = localStorage.getItem("auth_token");
  const response = await fetch(
    `${process.env.NEXT_PUBLIC_API_URL}/chats/${chatId}/messages?page=${page}&per_page=${perPage}`,
    {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  );
  if (!response.ok) {
    throw new Error("Failed to fetch messages");
  }
  return response.json();
}
