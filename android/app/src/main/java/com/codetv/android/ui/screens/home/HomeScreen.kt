package com.codetv.android.ui.screens.home

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.navigation.NavController
import coil.compose.AsyncImage
import com.codetv.android.data.model.Channel
import com.codetv.android.ui.navigation.Screen
import com.codetv.android.ui.theme.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(
    navController: NavController,
    viewModel: HomeViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        "CODETV",
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.primary,
                        fontSize = 22.sp,
                    )
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.background,
                )
            )
        }
    ) { padding ->
        if (uiState.isLoading) {
            Box(
                modifier = Modifier.fillMaxSize().padding(padding),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
            }
        } else {
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(padding),
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(24.dp),
            ) {
                item {
                    UgandaBanner(navController)
                }

                if (uiState.ugandaChannels.isNotEmpty()) {
                    item {
                        SectionHeader(
                            title = "🇺🇬 Ugandan Channels",
                            subtitle = "Free local channels",
                            onViewAll = { navController.navigate(Screen.Browse.route) }
                        )
                    }
                    item {
                        ChannelRow(
                            channels = uiState.ugandaChannels.take(10),
                            navController = navController,
                        )
                    }
                }

                item {
                    SectionHeader(
                        title = "Featured HD Channels",
                        subtitle = null,
                        onViewAll = null,
                    )
                }
                item {
                    ChannelRow(
                        channels = uiState.featuredChannels,
                        navController = navController,
                    )
                }

                item {
                    SectionHeader(
                        title = "Browse by Country",
                        subtitle = "${uiState.countries.size} countries available",
                        onViewAll = { navController.navigate(Screen.Browse.route) },
                    )
                }
                item {
                    LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        items(uiState.countries.take(20)) { country ->
                            CountryChip(
                                name = country.name,
                                flag = getFlagEmoji(country.code),
                                count = country.channelsCount ?: 0,
                                onClick = {
                                    navController.navigate(Screen.Browse.route)
                                }
                            )
                        }
                    }
                }

                item { Spacer(Modifier.height(32.dp)) }
            }
        }
    }
}

@Composable
fun UgandaBanner(navController: NavController) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { navController.navigate(Screen.Browse.route) },
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(
            containerColor = Yellow900.copy(alpha = 0.3f)
        ),
    ) {
        Row(
            modifier = Modifier.padding(20.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Text(fontSize = 36.sp, text = "🇺🇬")
            Spacer(Modifier.width(16.dp))
            Column {
                Text(
                    "Ugandan Channels",
                    fontWeight = FontWeight.Bold,
                    fontSize = 18.sp,
                    color = MaterialTheme.colorScheme.onSurface,
                )
                Text(
                    "Free live TV channels from Uganda",
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
    }
}

@Composable
fun SectionHeader(
    title: String,
    subtitle: String?,
    onViewAll: (() -> Unit)?,
) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(title, fontWeight = FontWeight.Bold, fontSize = 18.sp)
            if (subtitle != null) {
                Text(subtitle, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
        }
        if (onViewAll != null) {
            TextButton(onClick = onViewAll) {
                Text("View All", color = MaterialTheme.colorScheme.primary)
            }
        }
    }
}

@Composable
fun ChannelRow(channels: List<Channel>, navController: NavController) {
    LazyRow(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
        items(channels) { channel ->
            ChannelCard(
                channel = channel,
                onClick = { navController.navigate(Screen.Player.createRoute(channel.id)) }
            )
        }
    }
}

@Composable
fun ChannelCard(channel: Channel, onClick: () -> Unit) {
    Card(
        modifier = Modifier
            .width(160.dp)
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
    ) {
        Column(modifier = Modifier.padding(8.dp)) {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .aspectRatio(16f / 9f)
                    .clip(RoundedCornerShape(8.dp))
                    .background(MaterialTheme.colorScheme.background),
                contentAlignment = Alignment.Center,
            ) {
                if (channel.logoUrl != null) {
                    AsyncImage(
                        model = channel.logoUrl,
                        contentDescription = channel.name,
                        modifier = Modifier.fillMaxSize().padding(4.dp),
                        contentScale = ContentScale.Fit,
                    )
                } else {
                    Text("📺", fontSize = 24.sp)
                }
            }
            Spacer(Modifier.height(6.dp))
            Text(
                channel.name,
                fontWeight = FontWeight.Medium,
                fontSize = 13.sp,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
            if (channel.isHd) {
                Text(
                    "HD",
                    fontSize = 10.sp,
                    color = MaterialTheme.colorScheme.primary,
                )
            }
        }
    }
}

@Composable
fun CountryChip(name: String, flag: String, count: Int, onClick: () -> Unit) {
    Card(
        modifier = Modifier.clickable(onClick = onClick),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 14.dp, vertical = 10.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Text(flag, fontSize = 18.sp)
            Spacer(Modifier.width(8.dp))
            Column {
                Text(name, fontWeight = FontWeight.Medium, fontSize = 13.sp, maxLines = 1)
                Text("$count ch", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
        }
    }
}

fun getFlagEmoji(countryCode: String): String {
    val codePoints = countryCode.uppercase().map { Character.toChars(0x1F1E6 - 'A' + it.code) }
    return String(codePoints.toTypedArray().let { chars ->
        val arr = CharArray(chars.sumOf { it.size })
        var i = 0
        for (c in chars) { for (ch in c) { arr[i++] = ch } }
        arr
    })
}
