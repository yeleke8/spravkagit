package com.example.mygentrathree

import android.app.Application
import android.location.Location
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import kotlin.math.abs
import kotlin.math.roundToInt
import kotlin.math.sin

class MainViewModel(application: Application) : AndroidViewModel(application) {

    private val locationRepository = LocationRepository(application)
    // OdometerManager жойылды

    // UI бақылайтын StateFlow деректері
    private val _currentSpeed = MutableStateFlow(0)
    val currentSpeed = _currentSpeed.asStateFlow()

    private val _bearing = MutableStateFlow(0f)
    val bearing = _bearing.asStateFlow()

    // Қашықтық есептеу айнымалылары жойылды

    private val _cameraAlert = MutableStateFlow<CameraAlert?>(null)
    val cameraAlert = _cameraAlert.asStateFlow()

    private val _currentLocation = MutableStateFlow<Location?>(null)
    val currentLocation = _currentLocation.asStateFlow()

    private val _cameraZones = MutableStateFlow<List<CameraZone>>(emptyList())
    val cameraZones = _cameraZones.asStateFlow()

    private val distResults = FloatArray(2)
    private var activeZoneId: Int = -1

    init {
        // Камераларды жүктеу (фондық режимде)
        viewModelScope.launch(Dispatchers.IO) {
            CameraRepository.loadCameras(application)
            _cameraZones.value = CameraRepository.zones
        }
    }

    fun startLocationUpdates() {
        viewModelScope.launch {
            locationRepository.getLocationUpdates().collect { location ->
                processLocation(location)
            }
        }
    }

    private fun processLocation(location: Location) {
        _currentLocation.value = location

        val speedMs = if (location.hasSpeed()) location.speed else 0f
        val speedKmh = (speedMs * 3.6).roundToInt()
        val finalSpeed = if (speedKmh <= 10) 0 else speedKmh
        _currentSpeed.value = finalSpeed

        if (location.hasBearing() && finalSpeed > 2) {
            _bearing.value = location.bearing
        }

        // Одометр есептеу logic-гі алынып тасталды

        checkCameraProximity(location, finalSpeed)
    }

    private fun checkCameraProximity(location: Location, speedKmh: Int) {
        viewModelScope.launch(Dispatchers.Default) {
            val nearbyZones = CameraRepository.getNearbyCameras(location.latitude, location.longitude)

            if (nearbyZones.isEmpty()) {
                activeZoneId = -1
                _cameraAlert.value = null
                return@launch
            }

            val activeAlerts = mutableListOf<Triple<CameraZone, Int, Boolean>>()

            for (zone in nearbyZones) {
                Location.distanceBetween(location.latitude, location.longitude, zone.location.lat, zone.location.lon, distResults)
                val distance = distResults[0]
                val detectionRadius = if (zone.speedLimit >= 100) 1000f else zone.radius

                if (distance <= detectionRadius) {
                    var isMatch = false
                    var isPassed = false

                    if (location.hasBearing()) {
                        val userHeading = location.bearing
                        val bearingToCamera = distResults[1]
                        var angleDiff = abs(userHeading - bearingToCamera)
                        if (angleDiff > 180) angleDiff = 360 - angleDiff
                        val lateralDistance = distance * sin(Math.toRadians(angleDiff.toDouble())).toFloat()

                        if (lateralDistance <= 40f) {
                            val isHeadingRelevant = zone.azimuths.any { az ->
                                var diff = abs(userHeading - az)
                                if (diff > 180) diff = 360 - diff
                                diff < 45 || diff > 135
                            }
                            if (isHeadingRelevant) {
                                isMatch = true
                                if (angleDiff > 95) isPassed = true
                            }
                        }
                    } else {
                        isMatch = true
                    }
                    if (isMatch) activeAlerts.add(Triple(zone, distance.toInt(), isPassed))
                }
            }

            if (activeAlerts.isEmpty()) {
                activeZoneId = -1
                _cameraAlert.value = null
            } else {
                activeAlerts.sortBy { it.second }
                val (closestZone, _, _) = activeAlerts[0]
                val isNew = if (activeZoneId != closestZone.id) {
                    activeZoneId = closestZone.id
                    true
                } else false

                _cameraAlert.value = CameraAlert(activeAlerts, isNew)
            }
        }
    }

    override fun onCleared() {
        super.onCleared()
        // odometerManager.save() алынып тасталды
    }
}

data class CameraAlert(
    val alerts: List<Triple<CameraZone, Int, Boolean>>,
    val isNewAlert: Boolean
) {
    val closestLimit: Int
        get() = alerts.firstOrNull()?.first?.speedLimit ?: 60
}