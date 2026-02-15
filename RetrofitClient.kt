package com.example.spravka.api

import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory

object RetrofitClient {
    // Сіздің серверіңіздің API папкасына сілтеме
    // Егер эмулятор қолдансаңыз және локалды сервер болса: "http://10.0.2.2/spravka/api/"
    // Сіздің қазіргі серверіңіз:
    private const val BASE_URL = "https://fervent-williams.195-210-46-54.plesk.page/spravka/api/"

    val instance: ApiService by lazy {
        Retrofit.Builder()
            .baseUrl(BASE_URL)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(ApiService::class.java)
    }
}