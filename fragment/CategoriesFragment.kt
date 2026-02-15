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
import com.example.spravka.PostsActivity
import com.example.spravka.R
import com.example.spravka.adapter.CategoryAdapter
import com.example.spravka.api.RetrofitClient
import kotlinx.coroutines.launch

class CategoriesFragment : Fragment() {

    private lateinit var rvCategories: RecyclerView
    private lateinit var adapter: CategoryAdapter

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        return inflater.inflate(R.layout.fragment_categories, container, false)
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        rvCategories = view.findViewById(R.id.rvCategoriesFragment)
        rvCategories.layoutManager = LinearLayoutManager(context)

        // Категорияны басқанда PostsActivity ашылады (бұрынғыдай)
        adapter = CategoryAdapter(emptyList()) { category ->
            val intent = Intent(context, PostsActivity::class.java)
            intent.putExtra("CAT_ID", category.id)
            intent.putExtra("CAT_NAME", category.name)
            startActivity(intent)
        }
        rvCategories.adapter = adapter

        fetchCategories()
    }

    private fun fetchCategories() {
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance.getCategories()
                if (response.isSuccessful && response.body() != null) {
                    val apiResponse = response.body()!!
                    if (apiResponse.success) {
                        adapter.updateData(apiResponse.data ?: emptyList())
                    }
                }
            } catch (e: Exception) {
                // Error handling
            }
        }
    }
}