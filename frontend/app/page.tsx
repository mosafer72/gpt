import Footer from '@/components/Common/Footer.tsx/Footer';
import styles from './page.module.css';
import Link from 'next/link';

const services = [
  {
    id: 'chatgpt',
    name: 'ChatGPT',
    icon: '🤖',
    description: 'گفتگو، تحلیل، تولید محتوا و کدنویسی حرفه‌ای',
    status: 'active',
    href: '/chat',
  },
  {
    id: 'grok',
    name: 'Grok',
    icon: '⚡',
    description: 'تحلیل داده‌های لحظه‌ای و اخبار روز',
    status: 'soon',
    href: '#',
  },
  {
    id: 'claude',
    name: 'Claude',
    icon: '☁️',
    description: 'پردازش متون طولانی و تحلیل عمیق',
    status: 'soon',
    href: '#',
  },
  {
    id: 'codex',
    name: 'Codex',
    icon: '💻',
    description: 'تولید کد حرفه‌ای و توسعه نرم‌افزار',
    status: 'soon',
    href: '#',
  },
  // ===== سرویس‌های جدید =====
  {
    id: 'speech-to-text',
    name: 'تبدیل صدا به متن',
    icon: '🎤',
    description: 'رونویسی خودکار فایل‌های صوتی و جلسات',
    status: 'soon',
    href: '#',
  },
  {
    id: 'pdf-to-word',
    name: 'تبدیل PDF به Word',
    icon: '📄',
    description: 'تبدیل دقیق فایل‌های PDF به اسناد قابل ویرایش',
    status: 'soon',
    href: '#',
  },
  {
    id: 'ocr',
    name: 'استخراج متن از تصویر',
    icon: '🖼️',
    description: 'تشخیص کاراکترهای نوری برای اسناد و اسکرین‌شات‌ها',
    status: 'soon',
    href: '#',
  },
  {
    id: 'ai-video',
    name: 'تولید ویدیو با هوش مصنوعی',
    icon: '🎬',
    description: 'ساخت ویدیو از توضیحات متنی با کیفیت بالا',
    status: 'soon',
    href: '#',
  },
];

export default function Home() {
  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div className={styles.logoWrapper}>
          <div className={styles.logoIcon}>AI</div>
          <span className={styles.logo}>اکانتسرا</span>
        </div>
        <nav className={styles.nav}>
          <Link href="/login" className={styles.btnOutline}>
            ورود
          </Link>
          <Link href="/chat" className={styles.btnPrimary}>
            شروع گفتگو
          </Link>
        </nav>
      </header>

      <main className={styles.hero}>
        <div className={styles.badge}>نسل جدید دستیارهای هوشمند</div>
        <h1 className={styles.title}>
          با یک اکانت، به همه
          <span className={styles.gradient}> مدل‌های هوش مصنوعی </span>
          دسترسی داشته باش
        </h1>
        <p className={styles.sub}>
          یک پنل واحد برای ChatGPT، Grok، Claude و Codex — بدون نیاز به
          ساخت چند حساب کاربری.
        </p>
        <div className={styles.actions}>
          <Link href="/chat" className={styles.cta}>
            🚀 شروع مکالمه
          </Link>
          <Link href="/login" className={styles.secondaryCta}>
            ورود به حساب
          </Link>
        </div>
      </main>

      <section className={styles.servicesSection}>
        <div className={styles.sectionHeader}>
          <h2>سرویس‌های هوش مصنوعی</h2>
          <p>فعلاً فقط ChatGPT فعال است</p>
        </div>
        <div className={styles.servicesGrid}>
          {services.map((service) => {
            const isActive = service.status === 'active';
            return (
              <div
                key={service.id}
                className={`${styles.serviceCard} ${
                  isActive ? styles.activeService : ''
                }`}
              >
                <div className={styles.serviceTop}>
                  <div className={styles.serviceIcon}>{service.icon}</div>
                  <span
                    className={`${styles.serviceStatus} ${
                      isActive ? styles.statusActive : styles.statusSoon
                    }`}
                  >
                    {isActive ? 'فعال' : 'به‌زودی'}
                  </span>
                </div>
                <h3>{service.name}</h3>
                <p>{service.description}</p>
                {isActive ? (
                  <Link href={service.href} className={styles.serviceButton}>
                    شروع گفتگو
                  </Link>
                ) : (
                  <button className={styles.serviceButtonDisabled}>
                    غیرفعال
                  </button>
                )}
              </div>
            );
          })}
        </div>
      </section>

      <Footer />
    </div>
  );
}