"use client";

import { useRouter } from "next/navigation";

import WorkspaceFeatures from "./WorkspaceFeatures";

import styles from "./WorkspaceCard.module.css";

interface Props {
  workspace: any;
}

export default function WorkspaceCard({ workspace }: Props) {
  const router = useRouter();

  return (
    <div className={styles.card}>
      <div className={styles.header}>
        <div className={styles.icon}>{workspace.icon}</div>

        <div>
          <h2>{workspace.name}</h2>

          <p>{workspace.description}</p>
        </div>
      </div>

      <WorkspaceFeatures features={workspace.features ?? []} />

      <div className={styles.actions}>
        <button onClick={() => router.push(`/workspace/${workspace.id}`)}>
          ورود به Workspace
        </button>

        <button
          onClick={() => router.push(`/workspace/${workspace.id}/billing`)}
        >
          ارتقا پلن
        </button>
      </div>
    </div>
  );
}
