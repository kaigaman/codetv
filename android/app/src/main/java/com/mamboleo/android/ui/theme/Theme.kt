package com.mamboleo.android.ui.theme

import android.app.Activity
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.SideEffect
import androidx.compose.ui.graphics.toArgb
import androidx.compose.ui.platform.LocalView
import androidx.core.view.WindowCompat

private val MamboleoColorScheme = darkColorScheme(
    primary = MamboleoNavyLight,
    onPrimary = White,
    primaryContainer = MamboleoNavy,
    secondary = MamboleoNavyLight,
    background = MamboleoNavyBg,
    onBackground = Gray100,
    surface = MamboleoNavyDark,
    onSurface = Gray100,
    surfaceVariant = Gray800,
    onSurfaceVariant = Gray400,
    outline = Gray700,
    outlineVariant = Gray600,
)

@Composable
fun MamboleoTheme(content: @Composable () -> Unit) {
    val view = LocalView.current
    if (!view.isInEditMode) {
        SideEffect {
            val window = (view.context as Activity).window
            window.statusBarColor = MamboleoNavyBg.toArgb()
            window.navigationBarColor = MamboleoNavyBg.toArgb()
            WindowCompat.getInsetsController(window, view).isAppearanceLightStatusBars = false
        }
    }

    MaterialTheme(
        colorScheme = MamboleoColorScheme,
        content = content
    )
}
