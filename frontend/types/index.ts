// ============================
// Message
// ============================

export interface Message {
  id: string;

  chat_id: number;

  role: "user" | "assistant";

  content: string;

  created_at: string;

  metadata?: {
    attachments?: Attachment[];
  };

  image_url?: string;
}

// ============================
// Attachment
// ============================

export interface Attachment {
  type: "image" | "file" | "audio";

  url: string;
}

// ============================
// AI Model
// ============================

export interface AIModel {
  id: number;

  key: string;

  name: string;

  provider: string;

  description?: string;

  icon?: string;
}

// ============================
// Chat
// ============================

export interface Chat {
  id: number;

  title: string;

  messages: Message[];

  created_at: string;

  // مدل انتخاب شده برای این چت

  model_id?: number;

  model_name?: string;

  model?: AIModel;
}

// ============================
// Plan
// ============================

export interface Plan {
  id: number;

  name: string;

  display_name: string;

  price: string | number;

  duration_days?: number;

  daily_message_limit: number;

  token_limit: number;

  capabilities?: {
    memory: boolean;

    planner: boolean;

    max_tokens: number;

    temperature: number;
  };

  features: string[];

  models: AIModel[];
}

// ============================
// Subscription
// ============================

export interface Subscription {
  has_subscription: boolean;

  transaction_id?: string | null;

  subscription?: {
    plan: Plan;

    days_remaining: number;

    started_at: string;

    expires_at: string;
  };
}

// ============================
// Token Wallet
// ============================

export interface TokenWallet {
  total_tokens: number;

  used_tokens: number;

  remaining_tokens: number;
}

// ============================
// Usage
// ============================

export interface TodayUsage {
  date: string;

  messages_count: number;

  daily_limit: number;

  remaining_messages: number;

  tokens_used: number;

  tokens_remaining: number;
}

// ============================
// User Status
// ============================

export interface UserStatus {
  has_subscription: boolean;

  daily_message_limit: number;

  today_messages_count: number;

  remaining_messages: number;
}
