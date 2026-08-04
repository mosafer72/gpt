"use client";

import { useEffect, useState } from "react";
import { useRouter, usePathname } from "next/navigation";

export default function AuthGuard({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem("auth_token");

    let user = null;

    try {
      user = JSON.parse(localStorage.getItem("user") || "null");
    } catch {
      user = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */

    const publicRoutes = [
      "/",
      "/about",
      "/contact",
      "/models",
      "/login",
      "/verify",
      "/404",
    ];

    const isPublicRoute = publicRoutes.includes(pathname);

    /*
    |--------------------------------------------------------------------------
    | User Routes
    |--------------------------------------------------------------------------
    */

    const isUserRoute =
      pathname.startsWith("/dashboard") || pathname.startsWith("/workspace");

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */

    const isAdminRoute = pathname.startsWith("/admin");

    /*
    |--------------------------------------------------------------------------
    | Public Access
    |--------------------------------------------------------------------------
    */

    if (isPublicRoute) {
      setLoading(false);

      return;
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Access
    |--------------------------------------------------------------------------
    */

    if (isAdminRoute) {
      // بدون لاگین
      if (!token) {
        router.replace("/login");

        return;
      }

      // کاربر عادی
      if (!user || user.role !== "admin") {
        router.replace("/dashboard");

        return;
      }

      setLoading(false);

      return;
    }

    /*
    |--------------------------------------------------------------------------
    | Authenticated User Access
    |--------------------------------------------------------------------------
    */

    if (isUserRoute) {
      if (!token) {
        router.replace("/login");

        return;
      }

      setLoading(false);

      return;
    }

    /*
    |--------------------------------------------------------------------------
    | Unknown Routes
    |--------------------------------------------------------------------------
    */

    setLoading(false);
  }, [pathname, router]);

  if (loading) {
    return (
      <div className="flex items-center justify-center h-screen">
        Loading...
      </div>
    );
  }

  return <>{children}</>;
}
