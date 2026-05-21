package com.codetv.android.ui.screens.browse

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Search
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
import com.codetv.android.ui.navigation.Screen
import com.codetv.android.ui.theme.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun BrowseScreen(
    navController: NavController,
    viewModel: BrowseViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsState()
    var showCountrySheet by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Browse Channels", fontWeight = FontWeight.Bold) },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.background,
                )
            )
        }
    ) { padding ->
        Row(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
        ) {
            // Sidebar
            Surface(
                modifier = Modifier.width(200.dp).fillMaxHeight(),
                color = MaterialTheme.colorScheme.surface,
            ) {
                LazyColumn(
                    modifier = Modifier.padding(12.dp),
                    verticalArrangement = Arrangement.spacedBy(4.dp),
                ) {
                    item {
                        Text("Country", fontWeight = FontWeight.Bold, fontSize = 13.sp, modifier = Modifier.padding(vertical = 8.dp))
                    }
                    item {
                        FilterChip(
                            selected = uiState.selectedCountry == "ug",
                            onClick = { viewModel.selectCountry("ug") },
                            label = { Text("🇺🇬 Uganda", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth(),
                        )
                    }
                    items(uiState.countries) { country ->
                        FilterChip(
                            selected = uiState.selectedCountry == country.code,
                            onClick = { viewModel.selectCountry(country.code) },
                            label = { Text("${country.name} (${country.channelsCount ?: 0})", fontSize = 11.sp) },
                            modifier = Modifier.fillMaxWidth(),
                        )
                    }

                    item { Spacer(Modifier.height(16.dp)) }
                    item {
                        Text("Category", fontWeight = FontWeight.Bold, fontSize = 13.sp, modifier = Modifier.padding(vertical = 8.dp))
                    }
                    item {
                        FilterChip(
                            selected = uiState.selectedCategory == null,
                            onClick = { viewModel.selectCategory(null) },
                            label = { Text("All", fontSize = 12.sp) },
                            modifier = Modifier.fillMaxWidth(),
                        )
                    }
                    items(uiState.categories) { cat ->
                        FilterChip(
                            selected = uiState.selectedCategory == cat.slug,
                            onClick = { viewModel.selectCategory(cat.slug) },
                            label = { Text(cat.name, fontSize = 11.sp) },
                            modifier = Modifier.fillMaxWidth(),
                        )
                    }
                }
            }

            // Channel grid
            if (uiState.isLoading) {
                Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                }
            } else {
                LazyVerticalGrid(
                    columns = GridCells.Adaptive(160.dp),
                    modifier = Modifier.fillMaxSize().padding(12.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    items(uiState.channels) { channel ->
                        ChannelGridCard(channel = channel) {
                            navController.navigate(Screen.Player.createRoute(channel.id))
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun ChannelGridCard(channel: com.codetv.android.data.model.Channel, onClick: () -> Unit) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
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
                        modifier = Modifier.fillMaxSize().padding(8.dp),
                        contentScale = ContentScale.Fit,
                    )
                } else {
                    Text("📺", fontSize = 28.sp)
                }
            }
            Spacer(Modifier.height(6.dp))
            Text(
                channel.name,
                fontWeight = FontWeight.Medium,
                fontSize = 12.sp,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    channel.country?.name ?: "",
                    fontSize = 10.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.weight(1f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                )
                if (channel.isHd) {
                    Text("HD", fontSize = 9.sp, color = MaterialTheme.colorScheme.primary)
                }
            }
        }
    }
}
