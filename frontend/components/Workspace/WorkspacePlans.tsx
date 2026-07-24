"use client";

import Link from "next/link";

import styles from "./WorkspacePlans.module.css";

interface Plan {
  id: number;

  display_name: string;

  price: number;

  duration_days: number;

  features?: string[];
}

interface Props {
  workspaceId: number;

  plans: Plan[];
}

export default function WorkspacePlans({
  workspaceId,

  plans,
}: Props) {
  if (!plans.length) {
    return null;
  }

  return (
    <div className={styles.wrapper}>
      {plans.map((plan) => (
        <div key={plan.id} className={styles.planCard}>
          <h3>{plan.display_name}</h3>

          <div className={styles.price}>
            {plan.price === 0 ? "رایگان" : plan.price.toLocaleString()}

            {plan.price > 0 && <span>تومان</span>}
          </div>

          <div className={styles.duration}>
            {plan.duration_days}
            روز
          </div>

          <ul>
            {plan.features?.map((item, index) => (
              <li key={index}>✓ {item}</li>
            ))}
          </ul>

          <Link
            href={`/checkout/workspace/${workspaceId}?plan=${plan.id}`}
            className={styles.buyButton}
          >
            {plan.price === 0 ? "فعال سازی" : "خرید Workspace"}
          </Link>
        </div>
      ))}
    </div>
  );
}
