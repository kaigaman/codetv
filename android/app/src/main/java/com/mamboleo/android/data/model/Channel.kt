package com.mamboleo.android.data.model

import com.google.gson.annotations.SerializedName

data class Channel(
    val id: Int,
    val name: String,
    val slug: String,
    @SerializedName("stream_url") val streamUrl: String,
    @SerializedName("stream_type") val streamType: String = "hls",
    val country: Country? = null,
    val category: Category? = null,
    val languages: List<Language>? = null,
    @SerializedName("logo_url") val logoUrl: String? = null,
    @SerializedName("is_hd") val isHd: Boolean = false,
    @SerializedName("is_online") val isOnline: Boolean = false,
    val resolution: String? = null,
    val website: String? = null,
    val description: String? = null,
    @SerializedName("tvg_id") val tvgId: String? = null,
)

data class Country(
    val id: Int,
    val code: String,
    val name: String,
    @SerializedName("channels_count") val channelsCount: Int? = null,
)

data class Category(
    val id: Int,
    val slug: String,
    val name: String,
    @SerializedName("channels_count") val channelsCount: Int? = null,
)

data class Language(
    val id: Int,
    val code: String,
    val name: String,
)

data class ChannelsResponse(
    val data: List<Channel>? = null,
    val channels: List<Channel>? = null,
    val by_category: List<CategoryGroup>? = null,
    val total: Int = 0,
    val country: Country? = null,
)

data class CategoryGroup(
    val category: String,
    val count: Int,
    val channels: List<Channel>,
)

data class SearchResult(
    val channels: List<Channel>? = null,
    val countries: List<Country>? = null,
    val query: String = "",
    @SerializedName("total_channels") val totalChannels: Int = 0,
)

data class LoginRequest(val email: String, val password: String)
data class RegisterRequest(val name: String, val email: String, val password: String)
data class AuthResponse(val user: User, val token: String)
data class User(val id: Int, val name: String, val email: String)
data class FavoriteToggle(val channel_id: Int)
data class FavoriteResponse(val favorited: Boolean)
data class ToggleResponse(val favorited: Boolean)
data class HealthResponse(val status: String)
