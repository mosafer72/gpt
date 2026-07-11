"use client";

import styles from "./ChatImage.module.css";


export default function ChatImage({
  src,
}: {
  src: string;
}) {

  return (
    <div className={styles.wrapper}>

      <img
        src={src}
        alt="image"
        className={styles.image}
        loading="lazy"
      />

    </div>
  );
}