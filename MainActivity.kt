package com.example.obddashboard

import android.Manifest
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.content.pm.PackageManager
import android.graphics.Color
import android.os.Build
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.ImageButton
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat

class MainActivity : AppCompatActivity() {

    // OBD Құрылғысының Bluetooth MAC адресін осы жерге жазыңыз!
    // Мысалы: "00:1D:A5:68:98:8B" немесе жұптастырылған құрылғылар тізімінен таңдау керек.
    private val OBD_DEVICE_ADDRESS = "00:1D:A5:68:98:8B"

    // Негізгі көрсеткіштер
    private lateinit var tvSpeed: TextView
    private lateinit var tvRpm: TextView
    private lateinit var tvTemp: TextView
    private lateinit var tvVolt: TextView

    // Қосымша көрсеткіштер
    private lateinit var tvLoad: TextView
    private lateinit var tvThrottle: TextView
    private lateinit var tvIntakeTemp: TextView
    private lateinit var tvMaf: TextView
    private lateinit var tvFuelLevel: TextView
    private lateinit var tvBarometric: TextView
    private lateinit var tvAmbientTemp: TextView
    private lateinit var tvTiming: TextView

    private lateinit var tvStatus: TextView
    private lateinit var btnPower: ImageButton
    private lateinit var btnMuteAlert: Button
    private lateinit var btnHistory: Button

    private val obdReceiver = object : BroadcastReceiver() {
        override fun onReceive(context: Context?, intent: Intent?) {
            when (intent?.action) {
                ObdService.ACTION_OBD_UPDATE -> {
                    val rpm = intent.getIntExtra("rpm", 0)
                    val speed = intent.getIntExtra("speed", 0)
                    val temp = intent.getIntExtra("temp", 0)
                    val volt = intent.getStringExtra("volt") ?: "--"

                    val load = intent.getStringExtra("load") ?: "0"
                    val throttle = intent.getStringExtra("throttle") ?: "0"
                    val intakeTemp = intent.getStringExtra("intakeTemp") ?: "0"
                    val maf = intent.getStringExtra("maf") ?: "0"
                    val fuel = intent.getStringExtra("fuel") ?: "--"
                    val baro = intent.getStringExtra("baro") ?: "0"
                    val ambient = intent.getStringExtra("ambient") ?: "0"
                    val timing = intent.getStringExtra("timing") ?: "0"

                    val isAlarmActive = intent.getBooleanExtra("isAlarmActive", false)

                    updateUI(rpm, speed, temp, volt, load, throttle, intakeTemp, maf, fuel, baro, ambient, timing, isAlarmActive)
                }
                ObdService.ACTION_OBD_STATUS -> {
                    val statusText = intent.getStringExtra("status") ?: ""
                    tvStatus.text = "Статус: $statusText"

                    // Статусқа қарай түсті өзгерту
                    if (statusText.contains("Қосылды", ignoreCase = true)) {
                        tvStatus.setTextColor(Color.GREEN)
                    } else if (statusText.contains("Қате", ignoreCase = true)) {
                        tvStatus.setTextColor(Color.RED)
                    } else {
                        tvStatus.setTextColor(Color.parseColor("#BB86FC"))
                    }
                }
            }
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        initViews()
        checkPermissions()

        btnPower.setOnClickListener {
            // Қызметті қосу немесе қайта қосу
            startObdService()
        }

        btnMuteAlert.setOnClickListener {
            val intent = Intent(this, ObdService::class.java)
            intent.action = ObdService.ACTION_MUTE_ALARM
            startService(intent)
            btnMuteAlert.visibility = View.GONE
            Toast.makeText(this, "Дыбыс өшірілді", Toast.LENGTH_SHORT).show()
        }

        btnHistory.setOnClickListener {
            Toast.makeText(this, "Тарих беті әзірленуде...", Toast.LENGTH_SHORT).show()
        }
    }

    private fun initViews() {
        tvSpeed = findViewById(R.id.tvSpeed)
        tvRpm = findViewById(R.id.tvRpm)
        tvTemp = findViewById(R.id.tvTemp)
        tvVolt = findViewById(R.id.tvVolt)
        tvLoad = findViewById(R.id.tvLoad)
        tvThrottle = findViewById(R.id.tvThrottle)
        tvIntakeTemp = findViewById(R.id.tvIntakeTemp)
        tvMaf = findViewById(R.id.tvMaf)
        tvFuelLevel = findViewById(R.id.tvFuelLevel)
        tvBarometric = findViewById(R.id.tvBarometric)
        tvAmbientTemp = findViewById(R.id.tvAmbientTemp)
        tvTiming = findViewById(R.id.tvTiming)
        tvStatus = findViewById(R.id.tvStatus)
        btnPower = findViewById(R.id.btnPower)
        btnMuteAlert = findViewById(R.id.btnMuteAlert)
        btnHistory = findViewById(R.id.btnHistory)
    }

    private fun startObdService() {
        val serviceIntent = Intent(this, ObdService::class.java)
        // OBD құрылғысының адресін жібереміз
        serviceIntent.putExtra("device_address", OBD_DEVICE_ADDRESS)

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            startForegroundService(serviceIntent)
        } else {
            startService(serviceIntent)
        }
    }

    private fun updateUI(
        rpm: Int, speed: Int, temp: Int, volt: String,
        load: String, throttle: String, intakeTemp: String, maf: String,
        fuel: String, baro: String, ambient: String, timing: String,
        isAlarmActive: Boolean
    ) {
        tvSpeed.text = "$speed км/сағ"
        tvRpm.text = "$rpm RPM"
        tvTemp.text = "$temp °C"
        tvVolt.text = "$volt V"
        tvLoad.text = "$load %"
        tvThrottle.text = "$throttle %"
        tvIntakeTemp.text = "$intakeTemp °C"
        tvMaf.text = "$maf г/с"
        tvFuelLevel.text = "$fuel %"
        tvBarometric.text = "$baro кПа"
        tvAmbientTemp.text = "$ambient °C"
        tvTiming.text = "$timing °"

        if (temp >= 100) {
            tvTemp.setTextColor(Color.RED)
        } else {
            tvTemp.setTextColor(Color.parseColor("#03DAC5"))
        }

        if (isAlarmActive) {
            btnMuteAlert.visibility = View.VISIBLE
        } else {
            btnMuteAlert.visibility = View.GONE
        }
    }

    private fun checkPermissions() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.BLUETOOTH_CONNECT) != PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(this, arrayOf(Manifest.permission.BLUETOOTH_CONNECT, Manifest.permission.BLUETOOTH_SCAN), 1)
            }
        } else {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(this, arrayOf(Manifest.permission.ACCESS_FINE_LOCATION), 1)
            }
        }
    }

    override fun onResume() {
        super.onResume()
        val filter = IntentFilter().apply {
            addAction(ObdService.ACTION_OBD_UPDATE)
            addAction(ObdService.ACTION_OBD_STATUS)
        }
        // Android 13 (Tiramisu) үшін түзету
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            registerReceiver(obdReceiver, filter, Context.RECEIVER_NOT_EXPORTED)
        } else {
            registerReceiver(obdReceiver, filter)
        }
    }

    override fun onPause() {
        super.onPause()
        unregisterReceiver(obdReceiver)
    }
}