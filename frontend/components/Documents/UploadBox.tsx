"use client";

import styles from "./UploadBox.module.css";

export default function UploadBox() {
  return (
    <div className={styles.box}>
      <div>📤</div>

      <h3>آپلود Document</h3>

      <p>PDF , Word , TXT</p>

      <button>انتخاب فایل</button>
    </div>
  );
}
