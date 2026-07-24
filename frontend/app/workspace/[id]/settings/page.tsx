"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";

import styles from "./page.module.css";

import { getWorkspace } from "@/lib/api";

export default function WorkspaceSettingsPage() {
  const params = useParams();

  const router = useRouter();

  const id = Number(params.id);

  const [workspace, setWorkspace] = useState<any>(null);

  const [loading, setLoading] = useState(true);

  async function load() {
    try {
      const data = await getWorkspace(id);

      console.log("SETTINGS WORKSPACE:", data);

      setWorkspace(data.workspace ?? data);
    } catch (error) {
      console.log(error);
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
    return <div className={styles.loading}>در حال بارگذاری تنظیمات...</div>;
  }

  if (!workspace) {
    return <div className={styles.loading}>Workspace پیدا نشد</div>;
  }

  const type = workspace.workspace_type ?? {};

  const plan = workspace.plan ?? {};

  const wallet = workspace.wallet ?? {};

  return (
    <div className={styles.container}>
      {/* Header */}

      <header className={styles.header}>
        <div className={styles.icon}>⚙️</div>

        <div>
          <h1>تنظیمات Workspace</h1>

          <p>مدیریت اطلاعات عمومی {workspace.name}</p>
        </div>
      </header>

      {/* Information */}

      <section className={styles.card}>
        <h2>🏷 اطلاعات Workspace</h2>

        <Info title="نام" value={workspace.name} />

        <Info title="نوع" value={type.display_name} />

        <Info title="پلن فعلی" value={plan.display_name} />

        <Info
          title="وضعیت"
          value={workspace.is_active ? "فعال ✅" : "غیرفعال"}
        />
      </section>

      {/* Dates */}

      <section className={styles.card}>
        <h2>📅 اطلاعات اشتراک</h2>

        <Info
          title="شروع"
          value={new Date(workspace.started_at).toLocaleDateString("fa-IR")}
        />

        <Info
          title="انقضا"
          value={new Date(workspace.expires_at).toLocaleDateString("fa-IR")}
        />
      </section>

      {/* Usage */}

      <section className={styles.grid}>
        <Stat title="کل توکن" value={wallet.total_tokens?.toLocaleString()} />

        <Stat title="مصرف شده" value={wallet.used_tokens?.toLocaleString()} />

        <Stat
          title="باقی مانده"
          value={wallet.remaining_tokens?.toLocaleString()}
        />
      </section>

      {/* Actions */}

      <section className={styles.actions}>
        <Action
          title="💬 گفتگو"
          desc="شروع گفتگو با هوش مصنوعی"
          click={() => router.push(`/workspace/${id}/chat`)}
        />

        <Action
          title="🤖 مدل‌ها"
          desc="مشاهده مدل‌های فعال"
          click={() => router.push(`/workspace/${id}/models`)}
        />

        <Action
          title="💳 اشتراک"
          desc="مدیریت پلن"
          click={() => router.push(`/workspace/${id}/subscription`)}
        />
      </section>

      <section className={styles.danger}>
        <h2>⚠️ Danger Zone</h2>

        <p>حذف Workspace در نسخه آینده فعال خواهد شد.</p>

        <button disabled>حذف Workspace</button>
      </section>
    </div>
  );
}

function Info({ title, value }: any) {
  return (
    <div className={styles.info}>
      <span>{title}</span>

      <strong>{value ?? "-"}</strong>
    </div>
  );
}

function Stat({ title, value }: any) {
  return (
    <div className={styles.stat}>
      <span>{title}</span>

      <strong>{value ?? 0}</strong>
    </div>
  );
}

function Action({ title, desc, click }: any) {
  return (
    <div className={styles.action}>
      <h3>{title}</h3>

      <p>{desc}</p>

      <button onClick={click}>ورود</button>
    </div>
  );
}
