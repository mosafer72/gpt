"use client";

import styles from "./WorkspaceFeatures.module.css";


interface Props {
  features: string[];
}


export default function WorkspaceFeatures({
  features
}: Props) {


  return (
    <ul className={styles.features}>

      {features.map((feature,index)=>(
        <li key={index}>
          <span>
            ✓
          </span>

          {feature}

        </li>
      ))}

    </ul>
  );
}