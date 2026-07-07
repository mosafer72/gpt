const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

const getToken = () => localStorage.getItem('auth_token');
const headers = (): HeadersInit => ({
  'Authorization': `Bearer ${getToken()}`,
  'Accept': 'application/json',
  'Content-Type': 'application/json',
});

const handleUnauthorized = (res: Response) => {
  if (res.status === 401) {
    localStorage.removeItem('auth_token');
    document.cookie = 'auth_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
    window.location.href = '/login';
    throw new Error('Unauthorized');
  }
  return res;
};

export const getChats = async (): Promise<Chat[]> => {
  const res = await fetch(`${API_BASE}/chats`, { headers: headers() });
  handleUnauthorized(res);
  if (!res.ok) throw new Error('Failed to fetch');
  return res.json();
};

export const getChat = async (id: number): Promise<Chat> => {
  const res = await fetch(`${API_BASE}/chats/${id}`, { headers: headers() });
  handleUnauthorized(res);
  if (!res.ok) throw new Error('Failed to fetch chat');
  return res.json();
};

export const deleteChat = async (id: number): Promise<void> => {
  const res = await fetch(`${API_BASE}/chats/${id}`, { method: 'DELETE', headers: headers() });
  handleUnauthorized(res);
  if (!res.ok) throw new Error('Failed to delete');
};

export const sendMessage = async (message: string, chatId?: number, image?: File | null) => {
  const formData = new FormData();
  formData.append('message', message);
  if (chatId) formData.append('chat_id', String(chatId));
  if (image) formData.append('image', image);

  const res = await fetch(`${API_BASE}/chats`, {
    method: 'POST',
    body: formData,
    headers: { 'Authorization': `Bearer ${getToken()}` },
  });
  handleUnauthorized(res);
  if (!res.ok) {
    const err = await res.json();
    throw new Error(err.error || 'خطا در ارسال');
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
  if (!token) throw new Error('No token');
  
  const res = await fetch(`${API_BASE}/user/status`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  if (res.status === 401) {
    handleUnauthorized(res);
  }
  if (!res.ok) throw new Error('Failed to fetch status');
  return res.json();
}