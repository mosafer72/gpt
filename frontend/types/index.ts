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

  model_name?: string;

  provider_id?: number;

  input_cost?: string;

  output_cost?: string;

  type?: string;

  pivot?: {
    plan_id?: number;

    workspace_plan_id?: number;

    ai_model_id: number;

    is_default?: number | boolean;
  };
}

// ============================
// Chat
// ============================

export interface Chat {
  id: number;

  title: string;

  messages: Message[];

  created_at: string;

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

  models?: AIModel[];
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

// ============================
// Workspace Subscription
// ============================

export interface WorkspaceSubscription {
  id: number;

  user_id: number;

  workspace_id: number;

  workspace_plan_id: number;

  workspace_plan: WorkspacePlan;

  started_at: string;

  expires_at: string;

  is_active: boolean;

  transaction_id?: string | null;
}

// ============================
// Dashboard
// ============================

export interface DashboardResponse {
  user: {
    id: number;

    phone: string;

    name: string;

    role: string;
  };

  workspace: Workspace | null;

  subscription: WorkspaceSubscription | null;

  wallet: TokenWallet | null;

  today_usage: {
    requests: number;

    prompt_tokens: number;

    completion_tokens: number;

    total_tokens: number;
  };
}

// ============================
// Workspace Type
// ============================

export interface WorkspaceType {
  id: number;

  key: string;

  name: string;

  display_name: string;

  description: string;

  icon: string;

  features: string[];

  is_active: boolean;
}

// ============================
// Workspace Plan
// ============================

export interface WorkspacePlan {
  id: number;

  name: string;

  display_name: string;

  description?: string | null;

  price: number;

  duration_days: number;

  token_limit: number;

  features: string[];

  workspace_type_id: number;

  is_active: boolean;

  models?: AIModel[];
}

// ============================
// Token Wallet
// ============================

export interface TokenWallet {
  id: number;

  workspace_id: number;

  total_tokens: number;

  used_tokens: number;

  remaining_tokens: number;
}

export interface DocumentItem {
  id: number;

  name: string;

  size: string;

  pages: number;

  selected?: boolean;
}

// ============================
// Workspace
// ============================

export interface Workspace {
  id: number;

  user_id: number;

  workspace_type_id: number;

  workspace_plan_id: number;

  name: string;

  type: string;

  started_at: string;

  expires_at: string;

  is_active: boolean;

  workspace_type: WorkspaceType;

  wallet: TokenWallet | null;

  /*
   |--------------------------------------------------------------------------
   | حالت اصلی فعلی API
   |--------------------------------------------------------------------------
   */

  workspace_subscriptions?: WorkspaceSubscription[];

  /*
   |--------------------------------------------------------------------------
   | برای بعضی endpoint ها
   |--------------------------------------------------------------------------
   */

  plan?: WorkspacePlan;
}
