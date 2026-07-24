  "use client";

  import styles from "./FilterBar.module.css";

  type Props = {
    status: string;
    sort: string;

    onStatus: (v: string) => void;
    onSort: (v: string) => void;
  };

  export default function FilterBar({ status, sort, onStatus, onSort }: Props) {
    return (
      <div className={styles.filters}>
        <select value={status} onChange={(e) => onStatus(e.target.value)}>
          <option value="">همه وضعیت‌ها</option>

          <option value="completed">آماده</option>

          <option value="processing">درحال پردازش</option>

          <option value="failed">خطا</option>
        </select>

        <select value={sort} onChange={(e) => onSort(e.target.value)}>
          <option value="newest">جدیدترین</option>

          <option value="oldest">قدیمی‌ترین</option>

          <option value="title">عنوان</option>

          <option value="year">سال انتشار</option>
        </select>
      </div>
    );
  }
