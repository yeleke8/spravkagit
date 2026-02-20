package com.example.mygentrathree

import android.Manifest
import android.animation.ObjectAnimator
import android.animation.ValueAnimator
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.PointF
import android.media.AudioManager
import android.media.ToneGenerator
import android.os.Build
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.provider.Settings
import android.view.View
import android.view.WindowManager
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.activity.enableEdgeToEdge
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
// Yandex MapKit кітапханаларының импорттары
import com.yandex.mapkit.Animation
import com.yandex.mapkit.MapKitFactory
import com.yandex.mapkit.ScreenPoint
import com.yandex.mapkit.geometry.LinearRing
import com.yandex.mapkit.geometry.Point as YandexPoint
import com.yandex.mapkit.geometry.Polygon
import com.yandex.mapkit.layers.ObjectEvent
import com.yandex.mapkit.map.CameraListener
import com.yandex.mapkit.map.CameraPosition
import com.yandex.mapkit.map.CameraUpdateReason
import com.yandex.mapkit.map.IconStyle
import com.yandex.mapkit.map.Map
import com.yandex.mapkit.map.MapObjectCollection
import com.yandex.mapkit.map.RotationType
import com.yandex.mapkit.mapview.MapView
import com.yandex.mapkit.user_location.UserLocationLayer
import com.yandex.mapkit.user_location.UserLocationObjectListener
import com.yandex.mapkit.user_location.UserLocationView
import com.yandex.runtime.image.ImageProvider
import kotlinx.coroutines.launch
import kotlin.math.*

// Негізгі Activity класы.
// UserLocationObjectListener - картадағы адамның белгішесін өзгерту үшін.
// CameraListener - картаның жылжуын бақылау үшін.
class MainActivity : AppCompatActivity(), UserLocationObjectListener, CameraListener {

    private val PREFS_NAME = "CarSettings" // Баптауларды сақтайтын файл аты
    private val LOCATION_REQ_CODE = 1001   // Локация рұқсатын сұрау коды

    // ViewModel - бизнес логика мен деректерді сақтау үшін (экран бұрылғанда дерек жоғалмауы үшін)
    private val viewModel: MainViewModel by viewModels()

    // UI элементтері (XML-дегі компоненттермен байланысу үшін)
    private lateinit var cameraAlertBox: View    // Камера туралы ескерту терезесі
    private lateinit var cameraText: TextView    // Ескерту мәтіні
    private lateinit var cameraIcon: ImageView   // Жылдамдық белгісінің суреті

    // Карта режимі батырмалары
    private lateinit var btnMapModeContainer: View // Батырманың өзі (басу үшін)
    private lateinit var btnMapModeIcon: ImageView // Иконка (түсін өзгерту үшін)

    // Жаңа: Navigation (Clean Mode) батырмасы
    private lateinit var btnNavContainer: View

    // Карта элементтері
    private lateinit var mapView: MapView
    private lateinit var userLocationLayer: UserLocationLayer // Пайдаланушының орны (көк нүкте/стрелка)
    private lateinit var mapObjects: MapObjectCollection      // Картаға сурет салу объектілері (камералар, сызықтар)

    // Жағдайды бақылайтын айнымалылар
    private var isFirstFix = true       // Карта ең бірінші рет ашылып тұр ма?
    private var isNightMode = true      // Түнгі режим қосулы ма?
    private var isAutoFollow = true     // Карта автоматты түрде көліктің соңынан еріп отыра ма?
    private var isCleanMode = false     // Жаңа: Камераларды жасыру режимі

    // Жылдамдық белгілерін кэштеу (жылдам жұмыс істеу үшін)
    private val speedIconCache = mutableMapOf<Int, Int>()

    // Handler - уақытпен жұмыс істеу үшін (таймер)
    private val mainHandler = Handler(Looper.getMainLooper())

    // Егер қолданушы картаны қолымен жылжытса, біраздан кейін қайтадан "автоматты ілесу" режиміне оралу логикасы
    private val returnToFollowRunnable = Runnable {
        isAutoFollow = true
        val loc = viewModel.currentLocation.value
        if (loc != null) {
            val currentPos = YandexPoint(loc.latitude, loc.longitude)
            // Камераны қайтадан адам тұрған жерге жаймен жылжыту
            mapView.map.move(
                CameraPosition(currentPos, 16f, mapView.map.cameraPosition.azimuth, 0f),
                Animation(Animation.Type.LINEAR, 0f),
                null
            )
        }
    }

    // Қолданба іске қосылғанда бірінші орындалатын функция
    override fun onCreate(savedInstanceState: Bundle?) {
        // Yandex MapKit кілтін орнату және инициализациялау
        MapKitFactory.setApiKey(getString(R.string.yandex_api_key))
        MapKitFactory.initialize(this)

        super.onCreate(savedInstanceState)
        // Толық экран режимін қосу
        window.setFlags(WindowManager.LayoutParams.FLAG_FULLSCREEN, WindowManager.LayoutParams.FLAG_FULLSCREEN)
        enableEdgeToEdge()
        setContentView(R.layout.activity_main)

        // Функцияларды шақыру
        initViews()         // UI элементтерін табу
        loadNightMode()     // Сақталған режимді жүктеу
        setupMap()          // Картаны баптау
        setupListeners()    // Түймелерді басуды тыңдау

        observeViewModel()  // Деректер өзгерісін бақылау
        checkPermissionsAndStart() // GPS рұқсатын тексеру
    }

    // XML файлдағы id-лар арқылы элементтерді табу
    private fun initViews() {
        cameraAlertBox = findViewById(R.id.camera_alert_box)
        cameraText = findViewById(R.id.camera_text)
        cameraIcon = findViewById(R.id.alert_icon_bg)

        // Жаңартылған батырмалар
        btnMapModeContainer = findViewById(R.id.btn_map_mode_container)
        btnMapModeIcon = findViewById(R.id.btn_map_mode_icon)

        // Navigation батырмасы
        btnNavContainer = findViewById(R.id.btn_nav_container)

        mapView = findViewById(R.id.mapview)

        // Экранның жоғарғы және төменгі жүйелік шегіністерін реттеу
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main)) { v, insets ->
            val sb = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(sb.left, sb.top, sb.right, sb.bottom)
            insets
        }
    }

    // Түймелерге басу оқиғаларын тіркеу
    private fun setupListeners() {
        // Карта режимін (Түн/Күн) ауыстыру түймесі (контейнерге басылады)
        btnMapModeContainer.setOnClickListener {
            isNightMode = !isNightMode
            applyMapMode()
            saveNightMode()
        }

        // Жаңа: Navigation (Clean Mode) батырмасы
        btnNavContainer.setOnClickListener {
            isCleanMode = !isCleanMode // Режимді ауыстыру

            if (isCleanMode) {
                // ТАЗА РЕЖИМ: Камераларды өшіру
                mapObjects.clear() // Картадан барлық объектілерді (камера/сәуле) өшіру
                cameraAlertBox.visibility = View.GONE // Ескерту терезесін жасыру
                Toast.makeText(this, "Clean Mode: ON", Toast.LENGTH_SHORT).show()
            } else {
                // ҚАЛЫПТЫ РЕЖИМ: Камераларды қайта салу
                val zones = viewModel.cameraZones.value
                drawCamerasOnMap(zones)
                Toast.makeText(this, "Clean Mode: OFF", Toast.LENGTH_SHORT).show()
            }
        }
    }

    // Картаның бастапқы параметрлерін орнату
    private fun setupMap() {
        applyMapMode() // Түнгі/күндізгі режимді қою
        mapObjects = mapView.map.mapObjects
        mapView.map.addCameraListener(this) // Карта қозғалысын тыңдаушы қосу

        // Пайдаланушының орнын көрсететін қабатты құру
        val mapKit = MapKitFactory.getInstance()
        userLocationLayer = mapKit.createUserLocationLayer(mapView.mapWindow)
        userLocationLayer.isVisible = true
        userLocationLayer.isHeadingEnabled = true // Телефон бұрылғанда карта да бұрылуы үшін
        userLocationLayer.setObjectListener(this) // Иконканы өзгерту үшін тыңдаушы
    }

    // ViewModel-дегі деректерді бақылау (Observer pattern)
    private fun observeViewModel() {
        lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                // 1. Камера ескертулерін бақылау
                launch {
                    viewModel.cameraAlert.collect { alert ->
                        handleCameraAlert(alert)
                    }
                }
                // 2. Ағымдағы локацияны бақылау (картаны жылжыту үшін)
                launch {
                    viewModel.currentLocation.collect { loc ->
                        if (loc != null) updateMapPosition(loc)
                    }
                }
                // 3. Картадағы камералар тізімін бақылау (сызып шығу үшін)
                launch {
                    viewModel.cameraZones.collect { zones ->
                        if (zones.isNotEmpty()) {
                            drawCamerasOnMap(zones)
                        }
                    }
                }
            }
        }
    }

    // Карта позициясын жаңарту функциясы
    private fun updateMapPosition(l: android.location.Location) {
        val currentPos = YandexPoint(l.latitude, l.longitude)

        // Фокусты экранның сәл төменгі жағына қою (навигатор стиль)
        if (mapView.width > 0 && mapView.height > 0 && isAutoFollow) {
            mapView.mapWindow.focusPoint = ScreenPoint(mapView.width / 2f, mapView.height * 0.7f)
        }

        // Егер бірінші рет анықталса, жаймен жақындату (zoom)
        if (isFirstFix) {
            mapView.map.move(CameraPosition(currentPos, 17f, 0.0f, 0.0f), Animation(Animation.Type.SMOOTH, 1.5f), null)
            isFirstFix = false
        } else if (isAutoFollow) {
            // Егер қозғалыста болса, картаны бұру (bearing)
            val finalBearing = if (l.hasSpeed() && l.speed > 1.5f && l.hasBearing()) l.bearing else mapView.map.cameraPosition.azimuth
            mapView.map.move(CameraPosition(currentPos, 17f, finalBearing, 0.0f), Animation(Animation.Type.LINEAR, 1f), null)
        }
    }

    // Камера туралы ескерту келгенде UI-ды өзгерту
    private fun handleCameraAlert(alertData: CameraAlert?) {
        // Егер "Таза режим" қосулы болса, ештеңе көрсетпейміз
        if (isCleanMode) {
            cameraAlertBox.visibility = View.GONE
            return
        }

        if (alertData != null) {
            val sb = StringBuilder()
            // Барлық жақын камераларды тізіп шығу
            for ((zone, dist, passed) in alertData.alerts) {
                val status = if (passed) getString(R.string.camera_status_passed) else getString(R.string.camera_status_ahead)
                sb.append(getString(R.string.camera_alert_template, zone.speedLimit, dist, status))
                sb.append("\n")
            }

            // Мәтіндерді орнату және терезені көрсету
            cameraText.text = sb.toString().trim()
            cameraAlertBox.visibility = View.VISIBLE

            // Жылдамдық белгісінің иконкасын қою (мысалы, 60 немесе 80)
            val iconRes = getSpeedIconId(alertData.closestLimit)
            if (iconRes != 0) cameraIcon.setImageResource(iconRes)

            // Фон түсін өзгерту
            val targetBg = R.drawable.bg_surface_card
            if (cameraAlertBox.tag != targetBg) {
                cameraAlertBox.setBackgroundResource(targetBg)
                cameraAlertBox.tag = targetBg
            }
        } else {
            // Егер қауіп жоқ болса, терезені жасыру
            cameraAlertBox.visibility = View.GONE
        }
    }

    // GPS рұқсатын тексеру және сұрау
    private fun checkPermissionsAndStart() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED) {
            viewModel.startLocationUpdates() // Рұқсат бар болса, локацияны қосу
        } else {
            // Рұқсат жоқ болса, сұрау
            ActivityCompat.requestPermissions(this, arrayOf(Manifest.permission.ACCESS_FINE_LOCATION), LOCATION_REQ_CODE)
        }
    }

    // Рұқсат сұрау нәтижесін өңдеу
    override fun onRequestPermissionsResult(requestCode: Int, permissions: Array<out String>, grantResults: IntArray) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        when (requestCode) {
            LOCATION_REQ_CODE -> {
                if (grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                    viewModel.startLocationUpdates()
                }
            }
        }
    }

    // Түнгі режимді картаға қолдану
    private fun applyMapMode() {
        mapView.map.isNightModeEnabled = isNightMode
        val color = if (isNightMode) R.color.text_secondary else R.color.yellow
        // Иконканың түсін өзгертеміз (Container емес)
        btnMapModeIcon.setColorFilter(ContextCompat.getColor(this, color))
    }

    // Жадыдан (SharedPreferences) түнгі режим баптауын оқу
    private fun loadNightMode() {
        isNightMode = getSharedPreferences(PREFS_NAME, 0).getBoolean("is_night_mode", true)
    }

    // Түнгі режим баптауын жадыға сақтау
    private fun saveNightMode() {
        getSharedPreferences(PREFS_NAME, 0).edit().putBoolean("is_night_mode", isNightMode).apply()
    }

    // Карта қозғалғанда шақырылады
    override fun onCameraPositionChanged(map: Map, cameraPosition: CameraPosition, cameraUpdateReason: CameraUpdateReason, finished: Boolean) {
        // Егер қолданушы саусағымен жылжытса (GESTURES)
        if (cameraUpdateReason == CameraUpdateReason.GESTURES) {
            isAutoFollow = false // Автоматты ілесуді тоқтату
            mainHandler.removeCallbacks(returnToFollowRunnable)
            mainHandler.postDelayed(returnToFollowRunnable, 5000) // 5 секундтан кейін қайта қосу
        }
    }

    // Пайдаланушының локация объектісі қосылғанда (белгішені өзгерту)
    override fun onObjectAdded(view: UserLocationView) {
        view.accuracyCircle.fillColor = Color.TRANSPARENT // Дәлдік шеңберін жасыру
        val iconStyle = IconStyle()
            .setAnchor(PointF(0.5f, 0.5f))
            .setRotationType(RotationType.ROTATE)
            .setZIndex(1f)
            .setScale(1.2f)
        // Арнайы суретті (seekbar_custom_thumb) қолдану
        val iconProvider = ImageProvider.fromResource(this, R.drawable.arrow2)
        view.arrow.setIcon(iconProvider, iconStyle)
        view.pin.setIcon(iconProvider, iconStyle)
    }

    // Бұл әдістер міндетті болғанымен, бос қалдырылған
    override fun onObjectRemoved(view: UserLocationView) {}
    override fun onObjectUpdated(view: UserLocationView, event: ObjectEvent) {}

    // Vector (SVG) суретті Bitmap форматына айналдыру (MapKit үшін қажет)
    private fun getBitmapFromVector(drawableId: Int): Bitmap? {
        val drawable = ContextCompat.getDrawable(this, drawableId) ?: return null
        val bitmap = Bitmap.createBitmap(drawable.intrinsicWidth, drawable.intrinsicHeight, Bitmap.Config.ARGB_8888)
        val canvas = Canvas(bitmap)
        drawable.setBounds(0, 0, canvas.width, canvas.height)
        drawable.draw(canvas)
        return bitmap
    }

    // Жылдамдыққа сәйкес суреттің ID-ін алу (мысалы, "speed_60")
    private fun getSpeedIconId(speedLimit: Int): Int {
        if (speedIconCache.containsKey(speedLimit)) {
            return speedIconCache[speedLimit]!!
        }
        val resId = resources.getIdentifier("speed_$speedLimit", "drawable", packageName)
        if (resId != 0) {
            speedIconCache[speedLimit] = resId
        }
        return resId
    }

    // Барлық камераларды картаға салу
    private fun drawCamerasOnMap(zones: List<CameraZone>) {
        // Егер "Таза режим" болса, сурет салмаймыз
        if (isCleanMode) return
        if (zones.isEmpty()) return

        for (zone in zones) {
            val point = YandexPoint(zone.location.lat, zone.location.lon)
            val resId = getSpeedIconId(zone.speedLimit)
            // Камера иконкасын қою
            if (resId != 0) {
                val placemark = mapObjects.addPlacemark(point)
                val bitmap = getBitmapFromVector(resId)
                if (bitmap != null) {
                    val imageProvider = ImageProvider.fromBitmap(bitmap)
                    val iconStyle = IconStyle().setScale(0.3f)
                    placemark.setIcon(imageProvider, iconStyle)
                }
            }
            // Камераның қарау бұрышын (сәулесін) салу
            for (azimuth in zone.azimuths) {
                drawCameraBeam(point, azimuth)
            }
        }
    }

    // Камераның "көру аймағын" (үшбұрыш/полигон) сызу
    private fun drawCameraBeam(center: YandexPoint, azimuth: Float) {
        val beamLength = 170.0 // Сәуле ұзындығы
        val beamWidthAngle = 6.0 // Сәуле ені (градуспен)

        // Математикалық есептеу арқылы үшбұрыш нүктелерін табу
        val p2 = calculatePoint(center, beamLength, azimuth - beamWidthAngle)
        val p3 = calculatePoint(center, beamLength, azimuth + beamWidthAngle)

        val points = arrayListOf(center, p2, p3, center)
        val polygon = mapObjects.addPolygon(Polygon(LinearRing(points), arrayListOf()))
        polygon.strokeColor = Color.TRANSPARENT
        polygon.fillColor = Color.argb(55, 255, 0, 0) // Қызыл түсті, жартылай мөлдір
    }

    // Географиялық координаттарды есептеу (бастапқы нүкте + қашықтық + бұрыш = жаңа нүкте)
    private fun calculatePoint(start: YandexPoint, distanceMeters: Double, bearingDegrees: Double): YandexPoint {
        val radiusEarth = 6371000.0 // Жер радиусы
        val d = distanceMeters / radiusEarth
        val brng = Math.toRadians(bearingDegrees)
        val lat1 = Math.toRadians(start.latitude)
        val lon1 = Math.toRadians(start.longitude)

        // Тригонометриялық формулалар
        val lat2 = asin(sin(lat1) * cos(d) + cos(lat1) * sin(d) * cos(brng))
        val lon2 = lon1 + atan2(sin(brng) * sin(d) * cos(lat1), cos(d) - sin(lat1) * sin(lat2))
        return YandexPoint(Math.toDegrees(lat2), Math.toDegrees(lon2))
    }

    private fun openSettings(action: String) = try { startActivity(Intent(action)) } catch (e: Exception) {}

    // Activity басталғанда MapKit-ті қосу (міндетті)
    override fun onStart() {
        super.onStart()
        MapKitFactory.getInstance().onStart()
        mapView.onStart()
    }

    // Activity тоқтағанда MapKit-ті тоқтату (батарея үнемдеу үшін)
    override fun onStop() {
        mapView.onStop()
        MapKitFactory.getInstance().onStop()
        super.onStop()
    }

    // Қосымша жабылғанда Handler-ді тазалау
    override fun onDestroy() {
        mainHandler.removeCallbacksAndMessages(null)
        super.onDestroy()
    }
}