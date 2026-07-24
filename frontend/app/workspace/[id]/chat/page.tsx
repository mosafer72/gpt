"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";

import { getWorkspace } from "@/lib/api";

import ChatInterface from "@/components/Chat/ChatInterface";

import styles from "./page.module.css";

export default function WorkspaceChatPage() {
  const params = useParams();

  const id = Number(params.id);

  const [workspace, setWorkspace] = useState<any>(null);

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function load() {
      try {
        const response = await getWorkspace(id);

        console.log("CHAT WORKSPACE:", response);

        setWorkspace(response.workspace ?? response);
      } catch (error) {
        console.log(error);
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

  if (!workspace) {
    return <div className={styles.loading}>Workspace پیدا نشد</div>;
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
