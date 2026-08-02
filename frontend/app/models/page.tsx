import Header from "@/components/Common/header/Header";
import styles from "./page.module.css";

const models = [
  {
    name: "ChatGPT",
    icon: "🤖",
    desc: "هوشمندترین مدل OpenAI",
    status: "active",
  },
  {
    name: "Claude",
    icon: "🧠",
    desc: "مناسب برای تحلیل و نگارش",
    status: "soon",
  },
  {
    name: "Grok",
    icon: "⚡",
    desc: "مدل هوش مصنوعی xAI",
    status: "soon",
  },
  {
    name: "Gemini",
    icon: "✨",
    desc: "مدل‌های Google",
    status: "soon",
  },
];

export default function ModelsPage() {
  return (
    <div className={styles.container}>
        <Header/>
      <section className={styles.hero}>
        <span className={styles.badge}>مدل‌های هوش مصنوعی</span>

        <h1 className={styles.title}>
          همه مدل‌ها
          <span className={styles.gradient}> در یک پنل</span>
        </h1>

        <div className={styles.grid}>
          {models.map((item) => (
            <div
              key={item.name}
              className={`${styles.card} ${
                item.status === "active"
                  ? styles.activeCard
                  : ""
              }`}
            >
              <div className={styles.cardTop}>
                <span>{item.icon}</span>

                <span
                  className={`${styles.status} ${
                    item.status === "active"
                      ? styles.active
                      : styles.soon
                  }`}
                >
                  {item.status === "active"
                    ? "فعال"
                    : "به‌زودی"}
                </span>
              </div>

              <h3>{item.name}</h3>

              <p>{item.desc}</p>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}