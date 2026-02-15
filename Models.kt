package com.example.spravka.model

import com.google.gson.annotations.SerializedName

// Жалпы жауап
data class ApiResponse<T>(
    val success: Boolean,
    val message: String,
    val data: T?
)

// Категориялар
data class Category(
    val id: Int,
    val name: String,
    val slug: String,
    @SerializedName("subcategories") val subcategories: List<SubCategory>? = null
)

data class SubCategory(
    val id: Int,
    val name: String,
    val slug: String
)

// Тізімдегі қысқаша пост
data class Post(
    val id: Int,
    val title: String,
    val address: String,
    val photo: String,
    val rating: Double,
    @SerializedName("reviews_count") val reviewsCount: Int,
    @SerializedName("price_sign") val priceSign: String? = null,
    @SerializedName("is_open") val isOpen: Boolean = false
)

// --- ЖАҢА: Толық ақпарат моделі ---
data class PostDetail(
    @SerializedName("post_id") val id: Int,
    val title: String,
    val address: String,
    val photo: String,
    val description: String?,
    @SerializedName("rating_avg") val rating: Double,
    @SerializedName("rating_count") val reviewsCount: Int,

    // JSON ішіндегі объектілер
    val contacts: Contacts?,
    val tags: List<Tag>?,
    val comments: List<Comment>?
)

data class Contacts(
    val phone: String?,
    val instagram: String?,
    val whatsapp: String?
)

data class Tag(
    @SerializedName("attr_name") val name: String
)

data class Comment(
    val rating: Int,
    val comment: String,
    @SerializedName("user_name") val userName: String,
    @SerializedName("created_at") val date: String
)