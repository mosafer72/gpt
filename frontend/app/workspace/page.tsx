"use client";

import { useEffect, useState } from "react";

import styles from "./page.module.css";

import WorkspaceCard from "@/components/Workspace/WorkspaceCard";

import { getWorkspaces } from "@/lib/api";

interface Workspace {
  id: number;
  name: string;
  description: string;
  icon: string;
  features: string[];
  plans?: any[];
}

export default function WorkspacesPage() {
  const [workspaces, setWorkspaces] = useState<Workspace[]>([]);

  const [loading, setLoading] = useState(true);

  const [error, setError] = useState("");

  useEffect(() => {
    async function loadWorkspaces() {
      try {
        const data = await getWorkspaces();

        setWorkspaces(data as any);
      } catch (err: any) {
        setError(err.message || "خطا در دریافت Workspace ها");
      } finally {
        setLoading(false);
      }
    }

    loadWorkspaces();
  }, []);

  if (loading) {
    return (
      <div className={styles.container}>
        <p>در حال دریافت Workspace ها...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className={styles.container}>
        <p>{error}</p>
      </div>
    );
  }

  return (
    <div className={styles.container}>

      <div className={styles.grid}>
        {workspaces.map((workspace) => (
          <WorkspaceCard key={workspace.id} workspace={workspace} />
        ))}
      </div>
    </div>
  );
}
