package com.example.mygentrathree

import android.content.Context
import android.util.Log
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import java.io.IOException
import kotlin.math.floor

data class Point(val lat: Double, val lon: Double)

data class CameraZone(
    val id: Int,
    val location: Point,
    val speedLimit: Int,
    val azimuths: List<Float>,
    val status: String,
    val radius: Float = 300f
)

object CameraRepository {
    var zones: List<CameraZone> = emptyList()
        private set

    private val gridMap = HashMap<String, MutableList<CameraZone>>()
    private const val GRID_SIZE = 0.05

    fun loadCameras(context: Context) {
        if (zones.isNotEmpty()) return

        try {
            val jsonString = context.assets.open("cameras.json").bufferedReader().use { it.readText() }
            val listType = object : TypeToken<List<CameraZone>>() {}.type
            zones = Gson().fromJson(jsonString, listType)
            initGrid()
        } catch (e: IOException) {
            Log.e("CameraRepo", "cameras.json оқылмады (файл жоқ болуы мүмкін)", e)
        } catch (e: Exception) {
            Log.e("CameraRepo", "JSON парсинг қатесі", e)
        }
    }

    private fun initGrid() {
        gridMap.clear()
        for (zone in zones) {
            val key = getGridKey(zone.location.lat, zone.location.lon)
            if (!gridMap.containsKey(key)) {
                gridMap[key] = mutableListOf()
            }
            gridMap[key]?.add(zone)
        }
    }

    private fun getGridKey(lat: Double, lon: Double): String {
        val latIdx = floor(lat / GRID_SIZE).toInt()
        val lonIdx = floor(lon / GRID_SIZE).toInt()
        return "${latIdx}_${lonIdx}"
    }

    fun getNearbyCameras(lat: Double, lon: Double): List<CameraZone> {
        val result = ArrayList<CameraZone>()
        val currentLatIdx = floor(lat / GRID_SIZE).toInt()
        val currentLonIdx = floor(lon / GRID_SIZE).toInt()

        for (x in -1..1) {
            for (y in -1..1) {
                val key = "${currentLatIdx + x}_${currentLonIdx + y}"
                gridMap[key]?.let { result.addAll(it) }
            }
        }
        return result
    }
}