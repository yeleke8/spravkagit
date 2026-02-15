package com.example.spravka

import android.os.Bundle
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.bumptech.glide.Glide
import com.example.spravka.api.RetrofitClient
import kotlinx.coroutines.launch

class PostDetailActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_post_detail)

        // Intent арқылы ID аламыз
        val postId = intent.getIntExtra("POST_ID", 0)
        if (postId == 0) {
            Toast.makeText(this, "Қате ID", Toast.LENGTH_SHORT).show()
            finish()
            return
        }

        // Серверден жүктеу
        fetchDetail(postId)
    }

    private fun fetchDetail(id: Int) {
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance.getPostDetail(id)
                if (response.isSuccessful && response.body() != null) {
                    val apiResponse = response.body()!!
                    if (apiResponse.success && apiResponse.data != null) {
                        val post = apiResponse.data

                        // Экранға шығару
                        findViewById<TextView>(R.id.tvDetailTitle).text = post.title
                        findViewById<TextView>(R.id.tvDetailRating).text = "★ ${post.rating} (${post.reviewsCount} пікір)"

                        // Сипаттама HTML болуы мүмкін, бірақ әзірге жай текст
                        findViewById<TextView>(R.id.tvDetailDescription).text =
                            if (post.description.isNullOrEmpty()) "Сипаттама жоқ" else post.description

                        // Телефон
                        val phone = post.contacts?.phone
                        findViewById<TextView>(R.id.tvDetailPhone).text =
                            if (!phone.isNullOrEmpty()) "Тел: $phone" else "Телефон көрсетілмеген"

                        // Сурет
                        val img = findViewById<ImageView>(R.id.imgDetailPhoto)
                        Glide.with(this@PostDetailActivity)
                            .load(post.photo)
                            .into(img)

                    } else {
                        Toast.makeText(this@PostDetailActivity, "Ақпарат табылмады", Toast.LENGTH_SHORT).show()
                    }
                }
            } catch (e: Exception) {
                Toast.makeText(this@PostDetailActivity, "Қате: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }
}