// components/Chat/ImageGeneratingBox.tsx
"use client";

import styles from "./ImageGeneratingBox.module.css";

export default function ImageGeneratingBox() {
  return (
    <div className={styles.wrapper}>
      <div className={styles.square}>
        <div className={styles.glow}></div>
        <div className={styles.icon}>✨</div>
        <div className={styles.text}>در حال ساخت تصویر...</div>
      </div>
    </div>
  );
}