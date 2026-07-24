"use client";

import Footer from "@/components/Common/Footer.tsx/Footer";
import WorkspaceCard from "@/components/Workspace/WorkspaceCard";

import Link from "next/link";
import { useEffect, useState } from "react";

import styles from "./page.module.css";

import { services } from "@/lib/services";
import { workspaces } from "@/lib/workspaces";
export default function Home() {
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    setIsAuthenticated(
      !!localStorage.getItem("auth_token")
    );
  }, []);

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div className={styles.logoWrapper}>
          <div className={styles.logoIcon}>AI</div>

          <span className={styles.logo}>
            اکانتسرا
          </span>
        </div>

        <nav className={styles.nav}>
          {isAuthenticated ? (
            <Link
              href="/dashboard"
              className={styles.btnOutline}
            >
              داشبورد
            </Link>
          ) : (
            <Link
              href="/login"
              className={styles.btnOutline}
            >
              ورود / ثبت‌نام
            </Link>
          )}

          <Link
            href="/chat"
            className={styles.btnPrimary}
          >
            شروع گفتگو
          </Link>
        </nav>
      </header>

      <main className={styles.hero}>
        <div className={styles.badge}>
          نسل جدید دستیارهای هوشمند
        </div>

        <h1 className={styles.title}>
          با یک اکانت، به همه
          <span className={styles.gradient}>
            {" "}
            مدل‌های هوش مصنوعی{" "}
          </span>
          دسترسی داشته باش
        </h1>

        <p className={styles.sub}>
          یک پنل واحد برای ChatGPT، Grok، Claude و Codex —
          بدون نیاز به ساخت چند حساب کاربری.
        </p>

        <div className={styles.actions}>
          <Link
            href="/chat"
            className={styles.cta}
          >
            🚀 شروع مکالمه
          </Link>

          {!isAuthenticated && (
            <Link
              href="/login"
              className={styles.secondaryCta}
            >
              ورود به حساب
            </Link>
          )}
        </div>
      </main>

      <section className={styles.servicesSection}>
        <div className={styles.sectionHeader}>
          <h2>سرویس‌های هوش مصنوعی</h2>

          <p>فعلاً فقط ChatGPT فعال است</p>
        </div>

        <div className={styles.servicesGrid}>
          {services.map((service) => {
            const isActive =
              service.status === "active";

            return (
              <div
                key={service.id}
                className={`${styles.serviceCard} ${
                  isActive
                    ? styles.activeService
                    : ""
                }`}
              >
                <div className={styles.serviceTop}>
                  <div className={styles.serviceIcon}>
                    {service.icon}
                  </div>

                  <span
                    className={`${styles.serviceStatus} ${
                      isActive
                        ? styles.statusActive
                        : styles.statusSoon
                    }`}
                  >
                    {isActive
                      ? "فعال"
                      : "به‌زودی"}
                  </span>
                </div>

                <h3>{service.name}</h3>

                <p>{service.description}</p>

                {isActive ? (
                  <Link
                    href={service.href}
                    className={styles.serviceButton}
                  >
                    شروع گفتگو
                  </Link>
                ) : (
                  <button
                    className={
                      styles.serviceButtonDisabled
                    }
                  >
                    غیرفعال
                  </button>
                )}
              </div>
            );
          })}
        </div>
      </section>

      <section className={styles.servicesSection}>
        <div className={styles.sectionHeader}>
          <h2>Workspace های تخصصی</h2>

          <p>
            محیط‌های اختصاصی برای تحقیق، برنامه‌نویسی،
            تولید محتوا و یادگیری
          </p>
        </div>

        <div className={styles.servicesGrid}>
          {workspaces.map((workspace) => (
            <WorkspaceCard
              key={workspace.id}
              workspace={workspace}
            />
          ))}
        </div>
      </section>

      <Footer />
    </div>
  );
}