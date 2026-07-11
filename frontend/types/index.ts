export interface Message {
  id: string;
  role: "user" | "assistant";
  content: string;

  attachments?: Attachment[];
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
