import styles from './Footer.module.css';

const services = ['ChatGPT', 'Grok', 'Claude', 'Codex'];

const socials = ['تلگرام', 'اینستاگرام', 'گیت‌هاب'];

export default function Footer() {

  return (

    <footer className={styles.footer}>

      <div className={styles.top}>

        <div className={styles.brand}>

          <div className={styles.logo}>

            <div className={styles.logoIcon}>AI</div>

            <span>اکانتسرا</span>

          </div>

          <p>

            یک حساب، دسترسی به چندین مدل هوش مصنوعی. سریع، امن و

            یکپارچه برای دانشجویان، برنامه‌نویسان و محققان.

          </p>

        </div>

        <div className={styles.column}>

          <h4>سرویس‌ها</h4>

          {services.map((item) => (

            <a key={item} href="#">{item}</a>

          ))}

        </div>

        <div className={styles.column}>

          <h4>ارتباط با ما</h4>

          <a href="mailto:support@nabeghe.ai">support@nabeghe.ai</a>

          {socials.map((item) => (

            <a key={item} href="#">{item}</a>

          ))}

        </div>

      </div>

      <div className={styles.bottom}>

        <span>© 2026 اکانتسرا — تمامی حقوق محفوظ است</span>

        <div className={styles.bottomLinks}>

          <a href="#">حریم خصوصی</a>

          <a href="#">قوانین استفاده</a>

          <a href="#">پشتیبانی</a>

        </div>

      </div>

    </footer>

  );

}