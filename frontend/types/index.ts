export interface Message {
  id: number; chat_id: number; role: 'user' | 'assistant';
  content: string; created_at: string;
}
export interface Chat {
  id: number; title: string; messages: Message[]; created_at: string;
}