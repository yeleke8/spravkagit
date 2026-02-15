package com.example.spravka.fragment

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.example.spravka.PostDetailActivity
import com.example.spravka.R
import com.example.spravka.adapter.PostAdapter
import com.example.spravka.api.RetrofitClient
import kotlinx.coroutines.launch

class HomeFragment : Fragment() {

    private lateinit var rvPosts: RecyclerView
    private lateinit var adapter: PostAdapter

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        return inflater.inflate(R.layout.fragment_home, container, false)
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        rvPosts = view.findViewById(R.id.rvAllPosts)
        rvPosts.layoutManager = LinearLayoutManager(context)

        // Постты басқанда PostDetailActivity ашылады
        adapter = PostAdapter(emptyList()) { post ->
            val intent = Intent(context, PostDetailActivity::class.java)
            intent.putExtra("POST_ID", post.id)
            startActivity(intent)
        }
        rvPosts.adapter = adapter

        fetchAllPosts()
    }

    private fun fetchAllPosts() {
        lifecycleScope.launch {
            try {
                // Параметрсіз шақырсақ, барлық посттар келеді
                val response = RetrofitClient.instance.getPosts()

                if (response.isSuccessful && response.body() != null) {
                    val apiResponse = response.body()!!
                    if (apiResponse.success) {
                        adapter.updateData(apiResponse.data ?: emptyList())
                    }
                }
            } catch (e: Exception) {
                // Қате болса үндемейміз немесе Toast шығарамыз
            }
        }
    }
}