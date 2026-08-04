"use client";

import PresentationForm from "./PresentationForm";
import styles from "./PresentationModal.module.css";

interface Props {
  onClose: () => void;
  onCreate: (data: any) => Promise<void>;
}

export default function PresentationModal({ onClose, onCreate }: Props) {
  return (
    <div className={styles.overlay}>
      <div className={styles.modal}>
        <header className={styles.header}>
          <div>
            <h2>✨ ساخت پاورپوینت جدید</h2>

            <p>مشخصات ارائه خود را وارد کنید</p>
          </div>

          <button className={styles.close} onClick={onClose}>
            ×
          </button>
        </header>

        <div className={styles.body}>
          <PresentationForm
            onCreate={async (data) => {
              await onCreate(data);
            }}
          />
        </div>
      </div>
    </div>
  );
}
