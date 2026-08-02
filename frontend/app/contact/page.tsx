import Header from "@/components/Common/header/Header";
import styles from "./page.module.css";

export default function ContactPage() {
  return (
    <div className={styles.container}>
        <Header/>
      <section className={styles.hero}>
        <span className={styles.badge}>ارتباط با ما</span>

        <h1 className={styles.title}>
          همیشه
          <span className={styles.gradient}> در دسترس شما</span>
        </h1>

        <p className={styles.sub}>
          اگر سوال، پیشنهاد یا گزارشی دارید خوشحال می‌شویم با ما
          در ارتباط باشید.
        </p>

        <div className={styles.grid}>
          <div className={styles.card}>
            <h3>📧 ایمیل</h3>
            <p>support@accountsara.com</p>
          </div>

          <div className={styles.card}>
            <h3>💬 تلگرام</h3>
            <p>@accountsara</p>
          </div>

          <div className={styles.card}>
            <h3>🛠 پشتیبانی</h3>
            <p>پاسخگویی ۲۴ ساعته</p>
          </div>
        </div>
      </section>
    </div>
  );
}