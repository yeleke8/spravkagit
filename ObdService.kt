package com.example.obddashboard

import android.annotation.SuppressLint
import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothSocket
import android.content.Context
import android.content.Intent
import android.media.Ringtone
import android.media.RingtoneManager
import android.os.Build
import android.os.IBinder
import android.util.Log
import androidx.core.app.NotificationCompat
import com.github.pires.obd.commands.SpeedCommand
import com.github.pires.obd.commands.control.ModuleVoltageCommand
import com.github.pires.obd.commands.control.TimingAdvanceCommand
import com.github.pires.obd.commands.engine.LoadCommand
import com.github.pires.obd.commands.engine.MassAirFlowCommand
import com.github.pires.obd.commands.engine.RPMCommand
import com.github.pires.obd.commands.engine.ThrottlePositionCommand
import com.github.pires.obd.commands.fuel.FuelLevelCommand
import com.github.pires.obd.commands.pressure.BarometricPressureCommand
import com.github.pires.obd.commands.protocol.*
import com.github.pires.obd.commands.temperature.AirIntakeTemperatureCommand
import com.github.pires.obd.commands.temperature.AmbientAirTemperatureCommand
import com.github.pires.obd.commands.temperature.EngineCoolantTemperatureCommand
import com.github.pires.obd.enums.ObdProtocols
import kotlinx.coroutines.*
import java.io.IOException
import java.util.UUID

class ObdService : Service() {

    companion object {
        const val CHANNEL_ID = "ObdChannel"
        const val ACTION_OBD_UPDATE = "com.example.obddashboard.OBD_UPDATE"
        const val ACTION_OBD_STATUS = "com.example.obddashboard.OBD_STATUS"
        const val ACTION_MUTE_ALARM = "com.example.obddashboard.MUTE_ALARM"

        var isRunning = false
        var bluetoothSocket: BluetoothSocket? = null
    }

    private val serviceJob = Job()
    private val serviceScope = CoroutineScope(Dispatchers.IO + serviceJob)

    private var alarmRingtone: Ringtone? = null
    private var isTempAlertActive = false
    private var isManuallyMuted = false

    override fun onCreate() {
        super.onCreate()
        createNotificationChannel()

        try {
            val alarmUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_ALARM)
                ?: RingtoneManager.getDefaultUri(RingtoneManager.TYPE_RINGTONE)
            alarmRingtone = RingtoneManager.getRingtone(applicationContext, alarmUri)
        } catch (e: Exception) {
            Log.e("ObdService", "Ringtone error", e)
        }
    }

    @SuppressLint("MissingPermission")
    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        val deviceAddress = intent?.getStringExtra("device_address")

        if (intent?.action == "STOP_SERVICE") {
            cleanupAndStop()
            return START_NOT_STICKY
        }

        if (intent?.action == ACTION_MUTE_ALARM) {
            if (alarmRingtone?.isPlaying == true) {
                alarmRingtone?.stop()
            }
            isManuallyMuted = true
            return START_STICKY
        }

        val notification = createNotification("OBD Daewoo Gentra қосылуда...")
        startForeground(1, notification)
        isRunning = true

        if (deviceAddress != null) {
            // Ескі байланыс болса жабу
            if (bluetoothSocket?.isConnected == true) {
                try { bluetoothSocket?.close() } catch (e: Exception){}
            }
            connectToDevice(deviceAddress)
        } else {
            sendStatus("ҚАТЕ: Адрес жоқ")
        }

        return START_STICKY
    }

    @SuppressLint("MissingPermission")
    private fun connectToDevice(address: String) {
        serviceScope.launch {
            sendStatus("ІЗДЕУДЕ... $address")
            try {
                val bluetoothManager = getSystemService(Context.BLUETOOTH_SERVICE) as? android.bluetooth.BluetoothManager
                val adapter = bluetoothManager?.adapter ?: BluetoothAdapter.getDefaultAdapter()

                if (adapter == null || !adapter.isEnabled) {
                    sendStatus("ҚАТЕ: Bluetooth өшірулі")
                    return@launch
                }

                val device = adapter.getRemoteDevice(address)
                val uuid = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")

                bluetoothSocket = device.createRfcommSocketToServiceRecord(uuid)
                bluetoothSocket?.connect()

                if (bluetoothSocket!!.isConnected) {
                    sendStatus("БАПТАУ ЖҮРУДЕ...")
                    configureObd() // Daewoo Gentra үшін арнайы баптау
                    sendStatus("ҚОСЫЛДЫ ✅")
                    startDataLoop()
                }
            } catch (e: IOException) {
                Log.e("ObdService", "Connection failed", e)
                sendStatus("ҚАТЕ: Қосыла алмады. Адаптерді суырып, қайта тығыңыз.")
                try { bluetoothSocket?.close() } catch (ignore: Exception) {}
            } catch (e: Exception) {
                Log.e("ObdService", "Error", e)
                sendStatus("ҚАТЕ: ${e.message}")
            }
        }
    }

    private fun configureObd() {
        try {
            val socket = bluetoothSocket ?: return
            val ins = socket.inputStream
            val outs = socket.outputStream

            // 1. Адаптерді баптаймыз
            EchoOffCommand().run(ins, outs)
            LineFeedOffCommand().run(ins, outs)

            // 2. Хаттаманы тазалау және AUTO-ға қою
            // Осы жерде алдымен AT SP 0 (Auto) жіберген дұрыс
            SelectProtocolCommand(ObdProtocols.AUTO).run(ins, outs)

            // 3. Gentra үшін кейде Headers-ті қосу қажет болуы мүмкін
            // Ол үшін "AT H1" командасын жіберуге болады (бірақ міндетті емес)

        } catch (e: Exception) {
            Log.e("ObdService", "Config error", e)
        }
    }

    private suspend fun startDataLoop() {
        while (isRunning && bluetoothSocket?.isConnected == true) {
            try {
                val socket = bluetoothSocket ?: break
                val input = socket.inputStream
                val output = socket.outputStream

                // Командаларды орындау

                // Gentra кейде барлық командаларды қолдамайды, сондықтан әрқайсысын жеке тексереміз.

                // 1. RPM
                val rpmCmd = RPMCommand()
                runCommand(rpmCmd, input, output)
                val rpm = rpmCmd.rpm

                // 2. Speed
                val speedCmd = SpeedCommand()
                runCommand(speedCmd, input, output)
                val speed = speedCmd.metricSpeed

                // 3. Coolant Temp
                val tempCmd = EngineCoolantTemperatureCommand()
                runCommand(tempCmd, input, output)
                val temp = tempCmd.temperature.toInt()
                checkTemperatureAlert(temp)

                // 4. Voltage
                val voltCmd = ModuleVoltageCommand()
                runCommand(voltCmd, input, output)
                val voltStr = String.format("%.1f", voltCmd.voltage)

                // 5. Load
                val loadCmd = LoadCommand()
                runCommand(loadCmd, input, output)
                val loadStr = String.format("%.0f", loadCmd.percentage)

                // 6. Throttle
                val throtCmd = ThrottlePositionCommand()
                runCommand(throtCmd, input, output)
                val throttleStr = String.format("%.0f", throtCmd.percentage)

                // 7. Intake Temp
                val intakeCmd = AirIntakeTemperatureCommand()
                runCommand(intakeCmd, input, output)
                val intakeTempStr = String.format("%.0f", intakeCmd.temperature)

                // 8. MAF (Gentra-да кейде MAF болмайды, MAP болады. Егер 0 көрсетсе, MAP қосу керек болуы мүмкін)
                val mafCmd = MassAirFlowCommand()
                runCommand(mafCmd, input, output)
                val mafStr = String.format("%.1f", mafCmd.maf)

                // 9. Fuel
                val fuelCmd = FuelLevelCommand()
                runCommand(fuelCmd, input, output)
                val fuelStr = String.format("%.0f", fuelCmd.fuelLevel)

                // 10. Barometric
                val baroCmd = BarometricPressureCommand()
                runCommand(baroCmd, input, output)
                val baroStr = String.format("%.0f", baroCmd.metricUnit)

                // 11. Ambient
                val ambCmd = AmbientAirTemperatureCommand()
                runCommand(ambCmd, input, output)
                val ambientStr = String.format("%.0f", ambCmd.temperature)

                // 12. Timing
                val timeCmd = TimingAdvanceCommand()
                runCommand(timeCmd, input, output)
                val timingStr = String.format("%.0f", timeCmd.percentage)

                // Broadcast жіберу
                val intent = Intent(ACTION_OBD_UPDATE).apply {
                    putExtra("rpm", rpm)
                    putExtra("speed", speed)
                    putExtra("temp", temp)
                    putExtra("volt", voltStr)
                    putExtra("load", loadStr)
                    putExtra("throttle", throttleStr)
                    putExtra("intakeTemp", intakeTempStr)
                    putExtra("maf", mafStr)
                    putExtra("fuel", fuelStr)
                    putExtra("baro", baroStr)
                    putExtra("ambient", ambientStr)
                    putExtra("timing", timingStr)
                    putExtra("isAlarmActive", isTempAlertActive)
                }
                sendBroadcast(intent)

            } catch (e: Exception) {
                Log.e("ObdService", "Loop error", e)
                sendStatus("БАЙЛАНЫС ҮЗІЛДІ: Қайта қосылуда...")
                cleanupAndStop()
                break
            }
            // Gentra ECU ескілеу болуы мүмкін, сондықтан кідірісті сәл көбейтеміз
            delay(150)
        }
    }

    // Команданы қауіпсіз іске қосу
    private fun runCommand(cmd: com.github.pires.obd.commands.ObdCommand, input: java.io.InputStream, output: java.io.OutputStream) {
        try {
            cmd.run(input, output)
        } catch (e: Exception) {
            // Егер команда жұмыс істемесе, тыныш қана өткізіп жібереміз
        }
    }

    private fun checkTemperatureAlert(temp: Int) {
        if (temp >= 100) {
            if (!isManuallyMuted && !isTempAlertActive) {
                alarmRingtone?.play()
                isTempAlertActive = true
            }
        } else if (temp < 95) {
            if (isTempAlertActive || isManuallyMuted) {
                alarmRingtone?.stop()
                isTempAlertActive = false
                isManuallyMuted = false
            }
        }
    }

    private fun cleanupAndStop() {
        isRunning = false
        alarmRingtone?.stop()
        try { bluetoothSocket?.close() } catch (e: Exception) {}
        stopForeground(STOP_FOREGROUND_REMOVE)
        stopSelf()
        serviceJob.cancel()
    }

    private fun sendStatus(status: String) {
        val intent = Intent(ACTION_OBD_STATUS)
        intent.putExtra("status", status)
        sendBroadcast(intent)
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val serviceChannel = NotificationChannel(
                CHANNEL_ID,
                "OBD Service Channel",
                NotificationManager.IMPORTANCE_LOW
            )
            val manager = getSystemService(NotificationManager::class.java)
            manager.createNotificationChannel(serviceChannel)
        }
    }

    private fun createNotification(text: String): Notification {
        return NotificationCompat.Builder(this, CHANNEL_ID)
            .setContentTitle("OBD Dashboard: Gentra")
            .setContentText(text)
            .setSmallIcon(R.drawable.ic_launcher_foreground)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .build()
    }

    override fun onDestroy() {
        super.onDestroy()
        cleanupAndStop()
    }

    override fun onBind(intent: Intent?): IBinder? = null
}