export interface Message {
  id: string;
  chat_id: number;
  role: "user" | "assistant";
  content: string;
  created_at: string;

  metadata?: {
    attachments?: {
      type: string;
      url: string;
    }[];
  };

  image_url?: string;
}

export interface Attachment {
  type: "image" | "file" | "audio";
  url: string;
}
export interface Chat {
  id: number;
  title: string;
  messages: Message[];
  created_at: string;
}
