package com.codetv.android.ui.theme

import android.app.Activity
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.SideEffect
import androidx.compose.ui.graphics.toArgb
import androidx.compose.ui.platform.LocalView
import androidx.core.view.WindowCompat

private val CodetvColorScheme = darkColorScheme(
    primary = CodetvGreen,
    onPrimary = Gray950,
    primaryContainer = CodetvGreenDark,
    secondary = CodetvGreenLight,
    background = Gray950,
    onBackground = Gray100,
    surface = Gray900,
    onSurface = Gray100,
    surfaceVariant = Gray800,
    onSurfaceVariant = Gray400,
    outline = Gray700,
    outlineVariant = Gray600,
)

@Composable
fun CodetvTheme(content: @Composable () -> Unit) {
    val view = LocalView.current
    if (!view.isInEditMode) {
        SideEffect {
            val window = (view.context as Activity).window
            window.statusBarColor = Gray950.toArgb()
            window.navigationBarColor = Gray950.toArgb()
            WindowCompat.getInsetsController(window, view).isAppearanceLightStatusBars = false
        }
    }

    MaterialTheme(
        colorScheme = CodetvColorScheme,
        content = content
    )
}
