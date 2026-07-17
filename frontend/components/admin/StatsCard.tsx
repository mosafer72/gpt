import styles from "./StatsCard.module.css";

interface StatsCardProps {
  icon: string;
  label: string;
  value: string | number;
  trend?: {
    value: number;
    direction: "up" | "down";
  };
}

export default function StatsCard({ icon, label, value, trend }: StatsCardProps) {
  return (
    <div className={styles.card}>
      <div className={styles.iconWrapper}>
        <span className={styles.icon}>{icon}</span>
      </div>
      <div className={styles.info}>
        <span className={styles.label}>{label}</span>
        <span className={styles.value}>{value}</span>
        {trend && (
          <span className={`${styles.trend} ${styles[trend.direction]}`}>
            {trend.direction === "up" ? "↑" : "↓"} {Math.abs(trend.value)}%
          </span>
        )}
      </div>
    </div>
  );
}