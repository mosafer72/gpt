import WorkspaceCard from "@/components/Workspace/WorkspaceCard";
import { workspaces } from "@/lib/workspaces";
import styles from "./page.module.css";
import Header from "@/components/Common/header/Header";

export default function WorkspacesPage() {
  return (
    <div className={styles.container}>
        <Header/>
      <section className={styles.hero}>
        <span className={styles.badge}>
          Workspaceهای تخصصی
        </span>

        <h1 className={styles.title}>
          برای هر کار
          <span className={styles.gradient}>
            {" "}
            یک Workspace
          </span>
        </h1>

        <p className={styles.sub}>
          هر فضای کاری ابزارها، مدل‌ها و قابلیت‌های اختصاصی خود
          را دارد.
        </p>

        <div className={styles.grid}>
          {workspaces.map((workspace) => (
            <WorkspaceCard
              key={workspace.id}
              workspace={workspace}
            />
          ))}
        </div>
      </section>
    </div>
  );
}