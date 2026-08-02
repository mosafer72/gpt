"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { Home, ArrowLeft } from "lucide-react";
import styles from "./NotFound.module.css";

export default function NotFound() {
  const router = useRouter();

  return (
    <div className={styles.container}>
      <div className={styles.blob1} />
      <div className={styles.blob2} />

      <div className={styles.content}>
        <h1 className={styles.errorCode}>404</h1>

        <h2 className={styles.title}>صفحه‌ای که دنبالش بودید پیدا نشد!</h2>
        <p className={styles.description}>
          شاید این صفحه حذف شده، آدرس رو اشتباه وارد کردید یا در حال حاضر بهش دسترسی ندارید.
        </p>

        <div className={styles.actions}>
          <Link href="/" className={styles.primaryButton}>
            <Home className="h-4 w-4" />
            <span>بازگشت به خانه</span>
          </Link>

          <button onClick={() => router.back()} className={styles.secondaryButton}>
            <ArrowLeft className="h-4 w-4" />
            <span>صفحه قبلی</span>
          </button>
        </div>
      </div>

      <div className={styles.footer}>
        مسیر خود را گم کرده‌اید؟ نگران نباشید، راه بازگشت همیشه هست.
      </div>
    </div>
  );
}