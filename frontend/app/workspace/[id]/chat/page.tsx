"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import Link from "next/link";

import { checkWorkspaceAccess } from "@/lib/api";

import ChatInterface from "@/components/Chat/ChatInterface";

import styles from "./page.module.css";

export default function WorkspaceChatPage() {
  const params = useParams();

  const id = Number(params.id);

  const [workspace, setWorkspace] = useState<any>(null);

  const [loading, setLoading] = useState(true);

  const [hasAccess, setHasAccess] = useState<boolean | null>(null);

  useEffect(() => {
    async function load() {
      try {
        // ارسال workspace_id و feature به بک‌اند
        const response = await checkWorkspaceAccess(id, "chat");

        console.log("CHAT WORKSPACE:", response);

        if (response.has_access) {
          setHasAccess(true);
          setWorkspace(response.workspace ?? response);
        } else {
          setHasAccess(false);
        }
      } catch (error) {
        console.log(error);
        setHasAccess(false);
      } finally {
        setLoading(false);
      }
    }

    if (id) {
      load();
    }
  }, [id]);

  if (loading) {
    return <div className={styles.loading}>در حال آماده سازی...</div>;
  }

  if (!hasAccess || !workspace) {
    return (
      <div className={styles.notFoundContainer}>
        <div className={styles.notFoundContent}>
          <span className={styles.errorCode}>404</span>
          <h1 className={styles.errorTitle}>فضای کاری یافت نشد</h1>
          <p className={styles.errorDesc}>
            شما به این Workspace دسترسی ندارید یا این قابلیت فعال نیست.
          </p>
          <Link href="/workspaces" className={styles.backButton}>
            بازگشت به لیست فضاهای کاری
          </Link>
        </div>
      </div>
    );
  }

  /**
   * Backend فعلی:
   *
   * workspace
   *   └── plan
   *        └── models
   *
   */

  const models = workspace.plan?.models ?? [];
  console.log(workspace);
  return (
    <div className={styles.container}>
      <section className={styles.chatBox}>
        <ChatInterface
          workspace={workspace}
          workspaceId={workspace.id}
          models={models}
          onNewChat={() => {}}
        />
      </section>
    </div>
  );
}