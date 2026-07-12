"use client";


import { useEffect, useState } from "react";

import styles from "./TokenUsageCard.module.css";

import { getTodayUsage } from "@/lib/api";



export default function TokenUsageCard()
{

    const [usage,setUsage] = useState({

        total_tokens:0,

        prompt_tokens:0,

        completion_tokens:0,

        requests:0

    });



    useEffect(()=>{

        loadUsage();

    },[]);



    const loadUsage = async()=>{

        try{

            const data = await getTodayUsage();


            setUsage({

                total_tokens:data.total_tokens || 0,

                prompt_tokens:data.prompt_tokens || 0,

                completion_tokens:data.completion_tokens || 0,

                requests:data.requests || 0

            });


        }catch(error){

            console.error(error);

        }

    };




    return (

        <section className={styles.card}>


            <div className={styles.header}>


                <div>

                    <span>
                        مصرف امروز
                    </span>

                    <h2>
                        توکن‌های استفاده شده
                    </h2>

                </div>


                <div className={styles.icon}>
                    🧠
                </div>


            </div>



            <div className={styles.mainNumber}>

                {usage.total_tokens.toLocaleString()}

                <small>
                    توکن
                </small>

            </div>



            <div className={styles.stats}>


                <div>

                    <span>
                        ورودی
                    </span>

                    <strong>
                        {usage.prompt_tokens.toLocaleString()}
                    </strong>

                </div>



                <div>

                    <span>
                        خروجی
                    </span>

                    <strong>
                        {usage.completion_tokens.toLocaleString()}
                    </strong>

                </div>



                <div>

                    <span>
                        درخواست
                    </span>

                    <strong>
                        {usage.requests}
                    </strong>

                </div>


            </div>



        </section>

    );


}