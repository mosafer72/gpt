"use client";

import styles from "./Table.module.css";

interface Column {
  key: string;
  label: string;
  render?: (value: any, row: any) => React.ReactNode;
}

interface TableProps {
  columns: Column[];
  data?: any[]; // ✅ Optional با مقدار پیش‌فرض
  actions?: (row: any) => React.ReactNode;
  onRowClick?: (row: any) => void;
  loading?: boolean;
  emptyMessage?: string;
}

export default function Table({
  columns,
  data = [], // ✅ مقدار پیش‌فرض
  actions,
  onRowClick,
  loading = false,
  emptyMessage = "هیچ داده‌ای یافت نشد",
}: TableProps) {
  if (loading) {
    return (
      <div className={styles.loading}>
        <span className={styles.spinner}></span>
        <span>در حال بارگذاری...</span>
      </div>
    );
  }

  if (data.length === 0) {
    return <div className={styles.empty}>{emptyMessage}</div>;
  }

  return (
    <div className={styles.tableWrapper}>
      <table className={styles.table}>
        <thead>
          <tr>
            {columns.map((col) => (
              <th key={col.key}>{col.label}</th>
            ))}
            {actions && <th className={styles.actionsHeader}>عملیات</th>}
          </tr>
        </thead>
        <tbody>
          {data.map((row, index) => (
            <tr
              key={index}
              className={onRowClick ? styles.clickable : ""}
              onClick={() => onRowClick?.(row)}
            >
              {columns.map((col) => (
                <td key={col.key}>
                  {col.render ? col.render(row[col.key], row) : row[col.key]}
                </td>
              ))}
              {actions && <td className={styles.actionsCell}>{actions(row)}</td>}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}