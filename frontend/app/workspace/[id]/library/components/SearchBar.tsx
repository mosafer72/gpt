"use client";

import styles from "./SearchBar.module.css";

type Props = {
  value: string;
  onChange: (value: string) => void;
};

export default function SearchBar({
  value,
  onChange,
}: Props) {
  return (
    <div className={styles.wrapper}>
      <span className={styles.icon}>🔍</span>

      <input
        value={value}
        onChange={(e)=>onChange(e.target.value)}
        placeholder="جستجوی عنوان مقاله، نویسنده ..."
      />
    </div>
  );
}