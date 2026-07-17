"use client";

import Layout from "@/components/admin/Layout";
import { useEffect } from "react";
import { useRouter } from "next/navigation";

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();
    
  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
      router.push("/login");
    }
  }, [router]);

  return <Layout>{children}</Layout>;
}