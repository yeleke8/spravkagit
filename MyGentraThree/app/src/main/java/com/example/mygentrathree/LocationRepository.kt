package com.example.mygentrathree

import android.annotation.SuppressLint
import android.content.Context
import android.location.Location
import android.os.Looper
import com.google.android.gms.location.FusedLocationProviderClient
import com.google.android.gms.location.LocationCallback
import com.google.android.gms.location.LocationRequest
import com.google.android.gms.location.LocationResult
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import kotlinx.coroutines.channels.awaitClose
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.callbackFlow

class LocationRepository(private val context: Context) {

    // Инициализация клиента Fused Location (Google Play Services)
    private val fusedLocationClient: FusedLocationProviderClient =
        LocationServices.getFusedLocationProviderClient(context)

    @SuppressLint("MissingPermission") // Рұқсат ViewModel немесе Activity деңгейінде тексеріледі
    fun getLocationUpdates(): Flow<Location> = callbackFlow {

        // Настройка запроса локации
        // PRIORITY_HIGH_ACCURACY - маңызды, спидометр үшін GPS қолданады
        // intervalMillis = 1000L - әр 1 секунд сайын жаңарту
        val locationRequest = LocationRequest.Builder(Priority.PRIORITY_HIGH_ACCURACY, 1000L)
            .setMinUpdateDistanceMeters(0f) // Кішкене қозғалысты да ұстау үшін
            .setWaitForAccurateLocation(false) // Тез алу үшін күтпеу
            .build()

        // Callback - жаңа координат келгенде осы жер істейді
        val locationCallback = object : LocationCallback() {
            override fun onLocationResult(result: LocationResult) {
                result.lastLocation?.let { location ->
                    // Flow-ға дерек жіберу
                    trySend(location)
                }
            }
        }

        // Жаңартуларды бастау
        fusedLocationClient.requestLocationUpdates(
            locationRequest,
            locationCallback,
            Looper.getMainLooper()
        )

        // Flow жабылғанда (Activity тоқтағанда) GPS өшіру
        awaitClose {
            fusedLocationClient.removeLocationUpdates(locationCallback)
        }
    }
}