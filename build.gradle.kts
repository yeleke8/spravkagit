plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
}

android {
    namespace = "com.example.obddashboard"
    compileSdk {
        version = release(36)
    }

    defaultConfig {
        applicationId = "com.example.obddashboard"
        minSdk = 31
        targetSdk = 36
        versionCode = 1
        versionName = "1.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_11
        targetCompatibility = JavaVersion.VERSION_11
    }
    kotlinOptions {
        jvmTarget = "11"
    }
}

dependencies {
    // Ваши текущие зависимости (оставляем как есть)
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.appcompat)
    implementation(libs.material)
    implementation(libs.androidx.activity)
    implementation(libs.androidx.constraintlayout)
    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.junit)
    androidTestImplementation(libs.androidx.espresso.core)

    // --- ДОБАВЛЯЕМ НОВЫЕ БИБЛИОТЕКИ НИЖЕ ---

    // Библиотека для работы с OBD-II командами
    // (пишем в кавычках, так как её нет в вашем каталоге libs)
    implementation("com.github.pires:obd-java-api:1.0")

    // Coroutines для работы в фоновом потоке
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3")
}