// ============================
// Core Types ()
// ============================

export interface Message {
  id: number;
  chat_id: number;
  user_id: number | null;
  role: "user" | "assistant" | "system";
  content: string;
  metadata?: {
    model?: string;
    token_usage?: {
      total_tokens: number;
      prompt_tokens: number;
      completion_tokens: number;
    };
    [key: string]: any;
  };
  created_at: string;
  updated_at: string;
}

export interface Chat {
  id: number;
  user_id: number | null;
  title: string | null;
  summary: string | null;
  created_at: string;
  updated_at: string;
  user?: User;
  messages?: Message[];
  messages_count?: number;
}

export interface Plan {
  id: number;
  name: string;
  display_name: string;
  price: number;
  duration_days: number;
  daily_message_limit: number;
  token_limit: number;
  features: string[]; // آرایه‌ای از رشته‌ها
  capabilities: Record<string, any>;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  models?: AIModel[]; // اضافه شود
  
}

export interface Subscription {
  id: number;
  user_id: number;
  plan_id: number;
  started_at: string;
  expires_at: string;
  is_active: boolean;
  transaction_id: string | null;
  created_at: string;
  updated_at: string;
  plan?: Plan;
}

export interface TokenWallet {
  id: number;
  user_id: number;
  total_tokens: number;
  used_tokens: number;
  remaining_tokens: number;
  created_at: string;
  updated_at: string;
}

export interface User {
  id: number;
  name: string | null;
  phone: string;
  phone_verified_at: string | null;
  created_at: string;
  updated_at: string;
  subscription?: Subscription;
  token_wallet?: TokenWallet;
}

export interface AIModel {
  id: number;
  key: string;
  name: string;
  model_name: string;
  provider_id: number;
  provider?: LlmProvider;
  input_cost: number;
  output_cost: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface LlmProvider {
  id: number;
  key: string;
  name: string;
  api_key: string; // encrypted in database
  base_url: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}
export interface LlmProviderForm {
  key: string;
  name: string;
  api_key?: string;
  base_url: string;
  is_active: boolean;
}

export interface Transaction {
  id: number;
  user_id: number;
  plan_id: number;
  amount: number;
  authority: string;
  ref_id: string | null;
  status: "pending" | "paid" | "failed";
  created_at: string;
  updated_at: string;
  user?: User;
  plan?: Plan;
}

export interface LlmRequest {
  id: number;
  user_id: number | null;
  provider: string;
  model: string;
  conversation_id: string | null;
  messages: any; // JSON payload
  request_options: any; // JSON
  response: any; // JSON
  usage: any; // JSON
  latency_ms: number | null;
  status: "pending" | "completed" | "failed" | "cancelled";
  error: string | null;
  created_at: string;
  updated_at: string;
  user?: User;
}

export interface Memory {
  id: number;
  user_id: number;
  key: string;
  value: string;
  source: string; // 'explicit' | 'inferred'
  last_used_at: string | null;
  created_at: string;
  updated_at: string;
  user?: User;
}

// ============================
// Paginated Response
// ============================

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

// ============================
// Dashboard Stats
// ============================

export interface DashboardStats {
  users: number;
  chats: number;
  messages: number;
  revenue: number;
  transactions: number;
  plans: number;
  models: number;
  todayMessages: number;
  weekMessages: number;
  monthMessages: number;
  activeSubscriptions: number;
  pendingTransactions: number;
}

// ============================
// User Status (Client)
// ============================

export interface UserStatus {
  has_subscription: boolean;
  daily_message_limit: number;
  today_messages_count: number;
  remaining_messages: number;
  token_wallet?: TokenWallet;
}

// ============================
// Today Usage (Client)
// ============================

export interface TodayUsage {
  date: string;
  messages_count: number;
  daily_limit: number;
  remaining_messages: number;
  tokens_used: number;
  tokens_remaining: number;
}