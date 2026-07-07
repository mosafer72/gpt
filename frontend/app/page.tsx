import Link from 'next/link';
import styles from './page.module.css';

export default function Home() {
  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <span className={styles.logo}>✨ نابغه</span>
        <nav>
          <Link href="/login" className={styles.btnOutline}>ورود</Link>
          <Link href="/login" className={styles.btnPrimary}>شروع کنید</Link>
        </nav>
      </header>
      <main className={styles.hero}>
        <h1 className={styles.title}>دستیار هوشمند <span className={styles.gradient}>نابغه</span></h1>
        <p className={styles.sub}>برای دانشجویان، محققان و هر کسی که به دنبال یادگیری عمیق‌تر است.</p>
        <Link href="/chat" className={styles.cta}>🚀 شروع مکالمه</Link>
      </main>
      <footer className={styles.footer}>© 2025 نابغه · ساخته شده با ❤️</footer>
    </div>
  );
}