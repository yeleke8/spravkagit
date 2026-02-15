package com.example.spravka.adapter

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.example.spravka.R
import com.example.spravka.model.Post

class PostAdapter(
    private var posts: List<Post>,
    private val onClick: (Post) -> Unit
) : RecyclerView.Adapter<PostAdapter.PostViewHolder>() {

    class PostViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        val imgPost: ImageView = view.findViewById(R.id.imgPost)
        val tvTitle: TextView = view.findViewById(R.id.tvTitle)
        val tvAddress: TextView = view.findViewById(R.id.tvAddress)
        val tvRating: TextView = view.findViewById(R.id.tvRating)
        val tvPrice: TextView = view.findViewById(R.id.tvPrice)
        val tvReviews: TextView = view.findViewById(R.id.tvReviews)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): PostViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_post, parent, false)
        return PostViewHolder(view)
    }

    override fun onBindViewHolder(holder: PostViewHolder, position: Int) {
        val post = posts[position]

        holder.tvTitle.text = post.title
        holder.tvAddress.text = post.address
        holder.tvRating.text = "★ ${post.rating}"
        holder.tvReviews.text = "${post.reviewsCount} пікір"
        holder.tvPrice.text = post.priceSign ?: "₸"

        // Суретті жүктеу
        Glide.with(holder.itemView.context)
            .load(post.photo)
            .placeholder(R.drawable.ic_launcher_foreground) // Жүктелу кезіндегі сурет
            .into(holder.imgPost)

        holder.itemView.setOnClickListener { onClick(post) }
    }

    override fun getItemCount() = posts.size

    fun updateData(newPosts: List<Post>) {
        posts = newPosts
        notifyDataSetChanged()
    }
}