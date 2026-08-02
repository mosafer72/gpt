import Header from "@/components/Common/header/Header";
import styles from "./page.module.css";

export default function AboutPage() {
  return (
    <div className={styles.container}>
        <Header/>
      <section className={styles.hero}>
        <span className={styles.badge}>درباره دانا‌بوم AI</span>

        <h1 className={styles.title}>
          هوش مصنوعی
          <span className={styles.gradient}> برای همه</span>
        </h1>

        <p className={styles.sub}>
          دانا‌بوم AI بستری برای دسترسی یکپارچه به بهترین مدل‌های
          هوش مصنوعی دنیا است. بدون نیاز به ساخت چندین حساب،
          تنها با یک Workspace می‌توانید از مدل‌های مختلف،
          ابزارهای تخصصی و فضای کاری اختصاصی استفاده کنید.
        </p>

        <div className={styles.grid}>
          <div className={styles.card}>
            <h3>🎯 مأموریت</h3>
            <p>
              ساخت یک محیط هوشمند برای یادگیری، برنامه‌نویسی،
              تحقیق و تولید محتوا.
            </p>
          </div>

          <div className={styles.card}>
            <h3>🚀 چشم‌انداز</h3>
            <p>
              تبدیل شدن به کامل‌ترین Workspace هوش مصنوعی
              فارسی.
            </p>
          </div>

          <div className={styles.card}>
            <h3>⚡ تکنولوژی</h3>
            <p>
              OpenAI، Anthropic، xAI، Google، OpenRouter و
              مدل‌های متن‌باز.
            </p>
          </div>
        </div>
      </section>
    </div>
  );
}