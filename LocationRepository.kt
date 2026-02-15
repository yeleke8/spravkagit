package com.example.mygentrathree

import android.annotation.SuppressLint
import android.content.Context
import android.location.Location
import android.location.LocationListener
import android.location.LocationManager
import android.os.Bundle
import kotlinx.coroutines.channels.awaitClose
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.callbackFlow

class LocationRepository(private val context: Context) {

    private val locationManager = context.getSystemService(Context.LOCATION_SERVICE) as LocationManager

    @SuppressLint("MissingPermission") // Рұқсат ViewModel немесе Activity деңгейінде тексеріледі
    fun getLocationUpdates(): Flow<Location> = callbackFlow {
        val locationListener = object : LocationListener {
            override fun onLocationChanged(location: Location) {
                trySend(location)
            }
            override fun onStatusChanged(provider: String?, status: Int, extras: Bundle?) {}
            override fun onProviderEnabled(provider: String) {}
            override fun onProviderDisabled(provider: String) {}
        }

        // GPS жаңартуларын сұрау (1 секунд, 1 метр)
        locationManager.requestLocationUpdates(
            LocationManager.GPS_PROVIDER,
            1000L,
            1f,
            locationListener
        )

        // Flow тоқтағанда (Activity жабылғанда) жаңартуды тоқтату
        awaitClose {
            locationManager.removeUpdates(locationListener)
        }
    }
}