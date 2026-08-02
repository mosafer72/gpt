"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";

import styles from "./page.module.css";

import {
  getWorkspace,
  getWorkspacePlans,
  initiateWorkspacePayment,
} from "@/lib/api";

export default function SubscriptionPage() {
  const params = useParams();

  const router = useRouter();

  const id = Number(params.id);

  const [workspace, setWorkspace] = useState<any>(null);

  const [plans, setPlans] = useState<any[]>([]);

  const [loading, setLoading] = useState(true);

  const [processing, setProcessing] = useState(false);

  async function load() {
    try {
      const response = await getWorkspace(id);

      /**
       * API:
       *
       * {
       *   success:true,
       *   workspace:{
       *       id,
       *       workspace_type_id,
       *       plan,
       *       ...
       *   }
       * }
       */

      const workspaceData = response.workspace;

      const plansResponse = await getWorkspacePlans(
        Number(workspaceData.workspace_type_id),
      );

      setWorkspace(workspaceData);

      setPlans(plansResponse.data ?? []);
    } catch (error) {
      console.log(error);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (id) {
      load();
    }
  }, [id]);

  async function buy(planId: number) {
    try {
      setProcessing(true);

      const result: any = await initiateWorkspacePayment(workspace.id, planId);

      if (result.payment_url) {
        window.location.href = result.payment_url;

        return;
      }

      if (result.success) {
        alert("Workspace با موفقیت فعال شد");

        router.push(`/workspace/${workspace.id}`);
      }
    } catch (error) {
      console.log(error);
    } finally {
      setProcessing(false);
    }
  }

  if (loading) {
    return <div className={styles.loading}>در حال بارگذاری...</div>;
  }

  if (!workspace) {
    return null;
  }

  const currentPlan =
    workspace.plan ??
    workspace.workspace_subscriptions?.[0]?.workspace_plan ??
    null;

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div className={styles.icon}>{workspace.workspace_type?.icon}</div>

        <div>
          <h1>اشتراک {workspace.name}</h1>

          <p>مدیریت پلن و پرداخت</p>
        </div>
      </header>

      <section className={styles.current}>
        <div>
          <span>پلن فعلی</span>

          <h2>{currentPlan?.display_name ?? "بدون پلن"}</h2>

          <p>
            {currentPlan?.token_limit
              ? currentPlan.token_limit.toLocaleString()
              : 0}{" "}
            توکن
          </p>
        </div>

        <div className={styles.active}>فعال</div>
      </section>

      <section>
        <h2 className={styles.title}>ارتقا پلن</h2>

        <div className={styles.grid}>
          {plans.map((plan) => (
            <div key={plan.id} className={styles.card}>
              <h3>{plan.display_name}</h3>

              <p>{plan.duration_days} روز</p>

              <strong>
                {plan.price === 0
                  ? "رایگان"
                  : `${plan.price.toLocaleString()} تومان`}
              </strong>

              <ul>
                {plan.features?.map((item: string, index: number) => (
                  <li key={index}>✓ {item}</li>
                ))}
              </ul>

              <button disabled={processing} onClick={() => buy(plan.id)}>
                {processing ? "در حال اتصال..." : "خرید"}
              </button>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}
