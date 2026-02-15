package com.example.spravka.api

import com.example.spravka.model.ApiResponse
import com.example.spravka.model.Category
import com.example.spravka.model.Post
import com.example.spravka.model.PostDetail
import retrofit2.Response
import retrofit2.http.GET
import retrofit2.http.Query

interface ApiService {

    @GET("categories.php")
    suspend fun getCategories(): Response<ApiResponse<List<Category>>>

    @GET("posts.php")
    suspend fun getPosts(
        @Query("cat_id") catId: Int = 0,
        @Query("q") query: String = "",
        @Query("sort") sort: String = "rating",
        @Query("page") page: Int = 1
    ): Response<ApiResponse<List<Post>>>

    // --- ЖАҢА: Толық ақпарат алу ---
    @GET("post_detail.php")
    suspend fun getPostDetail(@Query("id") id: Int): Response<ApiResponse<PostDetail>>
}