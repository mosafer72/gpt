"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import styles from "./Header.module.css";
import { usePathname } from "next/navigation";
import { logout } from "@/lib/api";
export default function Header() {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const pathname = usePathname();
  const isDashboard = pathname?.startsWith("/dashboard");

  useEffect(() => {
    setIsAuthenticated(Boolean(localStorage.getItem("auth_token")));
  }, []);
  return (
    <header className={styles.header}>
      <Link href="/" className={styles.logoWrapper}>
        <div className={styles.logoIcon}>AI</div>

        <span className={styles.logo}>دانا‌بوم</span>
      </Link>

      <nav className={styles.menu}>
        <Link href="/">خانه</Link>
        <Link href="/workspaces">فضاهای کاری</Link>
        <Link href="/models">مدل‌ها</Link>
        <Link href="/about">درباره ما</Link>
        <Link href="/contact">تماس با ما</Link>
      </nav>

      <div className={styles.nav}>
        {isAuthenticated ? (
          isDashboard ? (
            // اگر در داشبورد بودیم، دکمه خروج نمایش داده شود
            <button onClick={logout} className={styles.btnOutline}>
              خروج
            </button>
          ) : (
            // در سایر صفحات، دکمه داشبورد نمایش داده شود
            <Link href="/dashboard" className={styles.btnOutline}>
              داشبورد
            </Link>
          )
        ) : (
          <Link href="/login" className={styles.btnOutline}>
            ورود / ثبت‌نام
          </Link>
        )}
      </div>
    </header>
  );
}
