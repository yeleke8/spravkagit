plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
}

android {
    namespace = "com.example.mygentrathree"
    compileSdk = 36

    defaultConfig {
        applicationId = "com.example.mygentrathree"
        minSdk = 23
        targetSdk = 34
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
    buildFeatures {
        viewBinding = true
    }
}

dependencies {
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.appcompat)
    implementation(libs.material)
    implementation(libs.androidx.activity)
    implementation(libs.androidx.constraintlayout)
    implementation("androidx.activity:activity-ktx:1.9.0")

    // --- GEOLOCATION (Google Play Services) ---
    // Добавили библиотеку для точного и быстрого GPS
    implementation("com.google.android.gms:play-services-location:21.0.1")

    // --- OBD II КІТАПХАНАСЫ ---
    implementation("com.github.pires:obd-java-api:1.0")

    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.junit)
    androidTestImplementation(libs.androidx.espresso.core)
    implementation("androidx.gridlayout:gridlayout:1.0.0")
    implementation("androidx.documentfile:documentfile:1.0.1")
    implementation("com.yandex.android:maps.mobile:4.4.0-full")
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3")
    implementation("com.google.code.gson:gson:2.10.1")
}