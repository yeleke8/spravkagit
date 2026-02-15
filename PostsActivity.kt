package com.example.spravka

import android.content.Intent
import android.os.Bundle
import android.util.Log
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.example.spravka.adapter.PostAdapter
import com.example.spravka.api.RetrofitClient
import kotlinx.coroutines.launch

class PostsActivity : AppCompatActivity() {

    private lateinit var rvPosts: RecyclerView
    private lateinit var tvTitle: TextView
    private lateinit var adapter: PostAdapter

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_posts)

        val catId = intent.getIntExtra("CAT_ID", 0)
        val catName = intent.getStringExtra("CAT_NAME") ?: "Заведениелер"

        tvTitle = findViewById(R.id.tvCategoryTitle)
        tvTitle.text = catName

        rvPosts = findViewById(R.id.rvPosts)
        rvPosts.layoutManager = LinearLayoutManager(this)

        // --- ӨЗГЕРТУ КЕРЕК ЖЕРІ ОСЫ ---
        // Бұрын мұнда Toast.makeText(...) тұрған еді.
        // Енді оны өшіріп, келесі Activity-ге өту кодын жазамыз:
        adapter = PostAdapter(emptyList()) { post ->
            val intent = Intent(this, PostDetailActivity::class.java)
            intent.putExtra("POST_ID", post.id) // ID-ні келесі бетке жібереміз
            startActivity(intent)
        }

        rvPosts.adapter = adapter

        fetchPosts(catId)
    }

    private fun fetchPosts(catId: Int) {
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance.getPosts(catId = catId)

                if (response.isSuccessful && response.body() != null) {
                    val apiResponse = response.body()!!
                    if (apiResponse.success) {
                        val posts = apiResponse.data
                        if (!posts.isNullOrEmpty()) {
                            adapter.updateData(posts)
                        } else {
                            Toast.makeText(this@PostsActivity, "Бұл категория бос", Toast.LENGTH_SHORT).show()
                        }
                    } else {
                        Toast.makeText(this@PostsActivity, "Қате: ${apiResponse.message}", Toast.LENGTH_SHORT).show()
                    }
                }
            } catch (e: Exception) {
                Log.e("API_ERROR", "Error fetching posts: ${e.message}")
                Toast.makeText(this@PostsActivity, "Интернет қатесі", Toast.LENGTH_SHORT).show()
            }
        }
    }
}