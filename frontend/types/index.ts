// ============================
// Dashboard
// ============================

export interface AIModel {
  id: number;
  key: string;
  name: string;
  model_name: string;
  provider_id: number;
  input_cost: number;
  output_cost: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}
export interface DashboardResponse {
  user: {
    id: number;
    phone: string;
    name: string;
    role?: string;
  };

  workspaces: Workspace[];

  workspace_types: WorkspaceType[];
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

  features: any[];

  is_active: boolean;

  plans: WorkspacePlan[];
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

  workspace_type_id: number;

  features: any[];

  capabilities?: {
    memory?: boolean;

    planner?: boolean;

    documents?: boolean;

    file_upload?: boolean;

    max_tokens?: number;

  };

  models?: AIModel[];
}




// ============================
// Workspace
// ============================

export interface Workspace {

  id: number;

  user_id: number;

  workspace_type_id: number;

  name: string;

  type: string;

  started_at: string;

  expires_at: string;

  is_active: boolean;


  workspace_type: WorkspaceType;


  plan: WorkspacePlan | null;


  wallet: TokenWallet | null;


  subscription: {
    id: number;

    started_at: string;

    expires_at: string;

    is_active: boolean;

  } | null;


  today_usage: {
    requests: number;

    input_tokens: number;

    output_tokens: number;

    total_tokens: number;

  };

}
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
interface User {
  id: number;
  name: string;
  phone: string;
  role: string;
  phone_verified_at: string | null;
  created_at: string;
  workspaces?: any[];
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
export interface TodayUsage {
  messages_count: number;
  daily_limit: number;
  remaining_messages: number;
  tokens_used: number;
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
export interface UserStatus {
  has_subscription: boolean;
  daily_message_limit: number;
  today_messages_count: number;
  remaining_messages: number;
  token_wallet?: TokenWallet;
}