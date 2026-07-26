// app/admin/layout.tsx
"use client"; // چون از usePathname استفاده می‌کنیم

import Link from "next/link";
import { ReactNode } from "react";
import { usePathname } from "next/navigation";
import "./admin-globals.css";
import localFont from "next/font/local";

const iranSans = localFont({
  src: [
    {
      path: "../../public/fonts/Vazirmatn-Regular.woff2",
      weight: "400",
      style: "normal",
    },
    {
      path: "../../public/fonts/Vazirmatn-Bold.woff2",
      weight: "700",
      style: "normal",
    },
    {
      path: "../../public/fonts/Vazirmatn-Medium.woff2",
      weight: "500",
      style: "normal",
    },
  ],
  variable: "--font-iran-sans",
  display: "swap",
});

export default function AdminLayout({ children }: { children: ReactNode }) {
  const pathname = usePathname();

  // تابع تشخیص فعال بودن بر اساس مسیر
  const isActive = (href: string) => {
    return pathname === href || pathname.startsWith(href + "/");
  };

  return (
    <div
      style={{ fontFamily: "var(--font-iran-sans)" }}
      className="flex h-screen bg-gradient-to-br from-gray-50 to-gray-100/80 text-right dir-rtl font-sans antialiased"
    >
      {/* ========== SIDEBAR ========== */}
      <aside className="hidden md:flex flex-col w-72 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-white shadow-2xl shadow-slate-900/30 relative overflow-hidden">
        {/* decorative glow */}
        <div className="absolute -top-40 -right-40 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl pointer-events-none" />
        <div className="absolute -bottom-40 -left-40 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none" />

        {/* Brand */}
        <div className="relative z-10 flex items-center justify-center h-24 border-b border-white/5 backdrop-blur-sm bg-white/5">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-emerald-400 flex items-center justify-center shadow-lg shadow-blue-500/25">
              <svg
                className="w-6 h-6 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                />
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
            </div>
            <h1 className="text-xl font-bold tracking-tight bg-gradient-to-r from-blue-300 via-emerald-300 to-blue-300 bg-clip-text text-transparent bg-[length:200%_auto] animate-[gradient_4s_ease_infinite]">
              پنل ادمین
            </h1>
          </div>
        </div>

        {/* Navigation - با منوهای خفن */}
        <nav className="relative z-10 flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
          <p className="px-4 mb-4 text-xs font-medium tracking-wider text-white/30 uppercase">
            منوی اصلی
          </p>

          <NavItem href="/admin/dashboard" icon="dashboard" active={isActive("/admin/dashboard")}>
            داشبورد
          </NavItem>
          <NavItem href="/admin/users" icon="users" badge="۱۲" active={isActive("/admin/users")}>
            کاربران
          </NavItem>
          <NavItem href="/admin/workspaces" icon="workspace" badge="۴" active={isActive("/admin/workspaces")}>
            فضاهای کاری
          </NavItem>
          <NavItem href="/admin/workspace-types" icon="logs" badge="۱۵" active={isActive("/admin/workspace-types")}>
            نوع فضای کاری
          </NavItem>
          <NavItem href="/admin/plans" icon="plans" active={isActive("/admin/plans")}>
            پلن‌ها
          </NavItem>
          <NavItem href="/admin/workspace-subscriptions" icon="subscriptions" active={isActive("/admin/workspace-subscriptions")}>
            اشتراک‌های فضای کاری
          </NavItem>
          <NavItem href="/admin/providers" icon="providers" active={isActive("/admin/providers")}>
            پرووایدرها
          </NavItem>
          <NavItem href="/admin/models" icon="models" active={isActive("/admin/models")}>
            مدل‌ها
          </NavItem>
          <NavItem href="/admin/transactions" icon="transactions" active={isActive("/admin/transactions")}>
            تراکنش‌های مالی
          </NavItem>

          <div className="my-6 border-t border-white/5" />

          <p className="px-4 mb-4 text-xs font-medium tracking-wider text-white/30 uppercase">
            سیستم
          </p>
          <NavItem href="/admin/settings" icon="settings" active={isActive("/admin/settings")}>
            تنظیمات
          </NavItem>
          <NavItem href="/admin/logs" icon="logs" active={isActive("/admin/logs")}>
            لاگ‌های سیستم
          </NavItem>
        </nav>

        {/* Sidebar Footer */}
        <div className="relative z-10 p-4 border-t border-white/5 bg-white/5 backdrop-blur-sm">
          <div className="flex items-center gap-3 px-2 py-2 rounded-xl bg-white/5 hover:bg-white/10 transition">
            <div className="w-9 h-9 rounded-full bg-gradient-to-br from-blue-400 to-purple-400 flex items-center justify-center text-sm font-bold text-white shadow-lg shadow-blue-500/20">
              MS
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-white truncate">
                مدیر سیستم
              </p>
              <p className="text-xs text-white/40 truncate">admin@system.com</p>
            </div>
            <button className="text-white/30 hover:text-white/60 transition p-1.5 rounded-lg hover:bg-white/10">
              <svg
                className="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                />
              </svg>
            </button>
          </div>
        </div>
      </aside>

      {/* ========== MAIN CONTENT ========== */}
      <main className="flex-1 flex flex-col overflow-hidden">
        {/* ===== HEADER ===== */}
        <header className="flex items-center justify-between h-20 px-6 md:px-8 bg-white/80 backdrop-blur-xl border-b border-gray-200/60 shadow-sm sticky top-0 z-20">
          <div className="flex items-center gap-4">
            <button className="md:hidden text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100 transition">
              <svg
                className="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M4 6h16M4 12h16M4 18h16"
                />
              </svg>
            </button>
            <div className="hidden md:flex items-center gap-2 text-sm">
              <span className="text-gray-400">پنل ادمین</span>
              <span className="text-gray-300">/</span>
              <span className="text-gray-700 font-medium">فضاهای کاری</span>
            </div>
          </div>
          <div className="flex items-center gap-4">
            <div className="hidden md:flex items-center gap-2 px-4 py-2 bg-gray-100/70 rounded-xl border border-gray-200/50 focus-within:border-blue-400 focus-within:bg-white focus-within:shadow-md transition-all duration-200">
              <svg
                className="w-4 h-4 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
              </svg>
              <input
                type="text"
                placeholder="جستجو..."
                className="bg-transparent text-sm text-gray-700 placeholder:text-gray-400 outline-none w-40 lg:w-56"
              />
              <kbd className="hidden lg:block text-[10px] font-mono text-gray-400 bg-gray-200/50 px-1.5 py-0.5 rounded border border-gray-200/50">
                ⌘K
              </kbd>
            </div>
            <button className="relative p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition">
              <svg
                className="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
              </svg>
              <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white animate-pulse" />
            </button>
            <div className="flex items-center gap-3 pr-3 border-r border-gray-200/60 md:border-r-0 md:pr-0">
              <div className="hidden md:block text-left">
                <p className="text-sm font-medium text-gray-700 leading-tight">
                  مدیر سیستم
                </p>
                <p className="text-xs text-gray-400 leading-tight">مدیر ارشد</p>
              </div>
              <div className="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-emerald-400 flex items-center justify-center text-sm font-bold text-white shadow-lg shadow-blue-500/25 ring-2 ring-white/80 cursor-pointer hover:scale-105 transition-transform duration-200">
                MS
              </div>
            </div>
          </div>
        </header>

        {/* ===== CONTENT ===== */}
        <div className="flex-1 overflow-y-auto p-6 md:p-8 lg:p-10">
          <div className="absolute inset-0 pointer-events-none opacity-[0.03] bg-[radial-gradient(#0f172a_1px,transparent_1px)] [background-size:20px_20px]" />
          <div className="relative z-10">{children}</div>
        </div>
      </main>
    </div>
  );
}

// ========== NAV ITEM ==========
function NavItem({
  href,
  icon,
  children,
  active = false,
  badge,
}: {
  href: string;
  icon:
    | "dashboard"
    | "users"
    | "workspace"
    | "plans"
    | "transactions"
    | "settings"
    | "logs"
    | "subscriptions"
    | "providers"
    | "models";
  children: ReactNode;
  active?: boolean;
  badge?: string;
}) {
  const icons = {
    dashboard: (
      <svg
        className="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
        />
      </svg>
    ),
    users: (
      <svg
        className="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
        />
      </svg>
    ),
    workspace: (
      <svg
        className="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
        />
      </svg>
    ),
    plans: (
      <svg
        className="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
        />
      </svg>
    ),
    subscriptions: (
      <svg
        className="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
        />
      </svg>
    ),
    providers: (
      <svg
        className="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"
        />
      </svg>
    ),
    models: (
      <svg
        className="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"
        />
      </svg>
    ),
    transactions: (
      <svg
        className="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z"
        />
      </svg>
    ),
    settings: (
      <svg
        className="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
        />
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
        />
      </svg>
    ),
    logs: (
      <svg
        className="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        />
      </svg>
    ),
  };

  return (
    <Link
      href={href}
      className={`
        group relative flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 
        overflow-hidden
        ${
          active
            ? "bg-gradient-to-r from-blue-600/30 via-emerald-600/20 to-transparent text-white shadow-lg shadow-blue-500/10 ring-1 ring-white/10"
            : "text-white/60 hover:text-white hover:bg-white/5"
        }
      `}
    >
      {/* نور هولوگرافیک در حالت فعال */}
      {active && (
        <>
          <span className="absolute inset-0 bg-gradient-to-r from-blue-500/10 via-emerald-500/10 to-transparent animate-pulse" />
          <span className="absolute -inset-full top-1/2 -translate-y-1/2 w-1/2 h-full bg-gradient-to-r from-transparent via-white/10 to-transparent rotate-12 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000" />
        </>
      )}

      {/* نشانگر عمودی فعال */}
      {active && (
        <span className="absolute right-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-gradient-to-b from-blue-400 to-emerald-400 rounded-l-full shadow-lg shadow-blue-500/50" />
      )}

      {/* آیکون با افکت درخشش */}
      <span
        className={`
          relative transition-all duration-300
          ${
            active
              ? "text-blue-300 drop-shadow-[0_0_8px_rgba(59,130,246,0.5)]"
              : "text-white/40 group-hover:text-white/80 group-hover:scale-110"
          }
        `}
      >
        {icons[icon]}
      </span>

      {/* متن */}
      <span className="flex-1 text-sm font-medium tracking-wide">
        {children}
      </span>

      {/* Badge */}
      {badge && (
        <span
          className={`
            px-2.5 py-0.5 text-[10px] font-bold rounded-full border 
            transition-all duration-300
            ${
              active
                ? "bg-emerald-500/30 text-emerald-300 border-emerald-400/30 shadow-[0_0_12px_rgba(16,185,129,0.3)]"
                : "bg-white/10 text-white/60 border-white/10 group-hover:bg-white/20 group-hover:text-white"
            }
          `}
        >
          {badge}
        </span>
      )}

      {/* فلش کشویی در هاور */}
      <span
        className={`
          opacity-0 group-hover:opacity-100 transition-all duration-300 text-white/20 
          ${active ? "opacity-100 text-white/30" : ""}
          -translate-x-2 group-hover:translate-x-0
        `}
      >
        <svg
          className="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M17 8l4 4m0 0l-4 4m4-4H3"
          />
        </svg>
      </span>
    </Link>
  );
}