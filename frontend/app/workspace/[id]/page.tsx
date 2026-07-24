"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import styles from "./page.module.css";
import { getWorkspace } from "@/lib/api";

// لیست ابزارها
const tools = [
  {
    icon: "📄",
    title: "Documents",
    desc: "آپلود و تحلیل جزوه و فایل‌های PDF",
    path: "documents",
  },
  {
    icon: "📚",
    title: "Paper Library",
    desc: "مدیریت و جستجوی مقالات علمی",
    path: "library",
  },
  {
    icon: "📝",
    title: "Summarizer",
    desc: "خلاصه‌سازی متن و استخراج نکات مهم",
    path: "summarizer",
  },
  {
    icon: "🧮",
    title: "AI Solver",
    desc: "حل تمرین و توضیح مرحله به مرحله",
    path: "solver",
  },
  {
    icon: "🔍",
    title: "Research",
    desc: "تحقیق، مقاله و جمع‌آوری منابع",
    path: "research",
  },
  {
    icon: "🧠",
    title: "Memory",
    desc: "حافظه هوشمند دانشجویی",
    path: "memory",
  },
];

export default function WorkspacePage() {
  const params = useParams();
  const router = useRouter();
  const id = Number(params?.id);

  const [workspace, setWorkspace] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  async function load() {
    try {
      const data = await getWorkspace(id);
      console.log("WORKSPACE:", data.workspace);
      setWorkspace(data.workspace);
    } catch (error) {
      console.log("Workspace error:", error);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (id) {
      load();
    }
  }, [id]);

  if (loading) {
    return <div className={styles.loading}>در حال بارگذاری Workspace...</div>;
  }

  if (!workspace) {
    return <div className={styles.loading}>Workspace پیدا نشد</div>;
  }

  const type = workspace.workspace_type ?? {};
  const plan =
    workspace.plan ??
    workspace.workspace_subscriptions?.[0]?.workspace_plan ??
    {};
  const wallet = workspace.wallet ?? {
    total_tokens: 0,
    used_tokens: 0,
    remaining_tokens: 0,
  };

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div className={styles.icon}>{type.icon ?? "🚀"}</div>
        <div>
          <h1>{workspace.name}</h1>
          <p>
            {type.display_name}
            {" - "}
            {type.description}
          </p>
        </div>
      </header>

      <section className={styles.planCard}>
        <div>
          <span>پلن فعال</span>
          <h2>{plan.display_name ?? "بدون پلن"}</h2>
          <p>
            پایان:{" "}
            {workspace.expires_at
              ? new Date(workspace.expires_at).toLocaleDateString("fa-IR")
              : "-"}
          </p>
        </div>
        <div className={styles.badge}>فعال</div>
      </section>

      <section className={styles.stats}>
        <Stat title="کل توکن" value={plan.token_limit?.toLocaleString()} />
        <Stat title="مصرف شده" value={wallet.used_tokens?.toLocaleString()} />
        <Stat title="باقی مانده" value={wallet.remaining_tokens?.toLocaleString()} />
      </section>

      <section className={styles.card}>
        <h2>امکانات Workspace</h2>
        <div className={styles.features}>
          {type.features?.map((item: string, index: number) => (
            <div key={index} className={styles.feature}>
              ✓ {item}
            </div>
          ))}
        </div>
      </section>

      <section className={styles.card}>
        <h2>امکانات پلن</h2>
        <div className={styles.features}>
          {plan.features?.map((item: string, index: number) => (
            <div key={index} className={styles.feature}>
              ⭐ {item}
            </div>
          ))}
        </div>
      </section>

      {/* بخش عملیات پایه Workspace */}
      <section className={styles.grid}>
        <Action
          title="💬 گفتگو"
          desc="شروع گفتگو با AI"
          click={() => router.push(`/workspace/${id}/chat`)}
        />
        <Action
          title="🤖 مدل‌ها"
          desc="مدل‌های فعال"
          click={() => router.push(`/workspace/${id}/models`)}
        />
        <Action
          title="💳 اشتراک"
          desc="ارتقا یا تمدید پلن"
          click={() => router.push(`/workspace/${id}/subscription`)}
        />
        <Action
          title="⚙ تنظیمات"
          desc="مدیریت Workspace"
          click={() => router.push(`/workspace/${id}/settings`)}
        />
      </section>

      {/* بخش جدید ابزارهای هوشمند */}
      <div style={{ maxWidth: '1200px', margin: '40px auto 20px', position: 'relative', zIndex: 2 }}>
        <h2>ابزارهای هوشمند دانشجویی</h2>
      </div>
      
      <section className={styles.grid} style={{ marginBottom: '40px' }}>
        {tools.map((tool) => (
          <Tool
            key={tool.path}
            icon={tool.icon}
            title={tool.title}
            desc={tool.desc}
            click={() => router.push(`/workspace/${id}/${tool.path}`)}
          />
        ))}
      </section>

    </div>
  );
}

// کامپوننت‌های کمکی

function Stat({ title, value }: any) {
  return (
    <div>
      <span>{title}</span>
      <strong>{value ?? 0}</strong>
    </div>
  );
}

function Action({ title, desc, click }: any) {
  return (
    <div>
      <h3>{title}</h3>
      <p>{desc}</p>
      <button onClick={click}>ورود</button>
    </div>
  );
}

function Tool({ icon, title, desc, click }: any) {
  return (
    <div>
      <div style={{ fontSize: '32px', marginBottom: '12px' }}>{icon}</div>
      <h3>{title}</h3>
      <p>{desc}</p>
      <button onClick={click}>ورود</button>
    </div>
  );
}