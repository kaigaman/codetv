package com.codetv.android.data.api

import com.codetv.android.data.model.*
import retrofit2.Response
import retrofit2.http.*

interface CodetvApi {

    @GET("channels")
    suspend fun getChannels(
        @Query("country") country: String? = null,
        @Query("category") category: String? = null,
        @Query("language") language: String? = null,
        @Query("search") search: String? = null,
        @Query("hd") hd: Boolean? = null,
        @Query("per_page") perPage: Int = 50,
        @Query("page") page: Int = 1,
    ): Response<ChannelsResponse>

    @GET("channels/{id}")
    suspend fun getChannel(@Path("id") id: Int): Response<Channel>

    @GET("channels/country/{code}")
    suspend fun getChannelsByCountry(@Path("code") code: String): Response<ChannelsResponse>

    @GET("channels/uganda/all")
    suspend fun getUgandaChannels(): Response<ChannelsResponse>

    @GET("channels/random/one")
    suspend fun getRandomChannel(): Response<Channel>

    @GET("countries")
    suspend fun getCountries(): Response<List<Country>>

    @GET("categories")
    suspend fun getCategories(): Response<List<Category>>

    @GET("languages")
    suspend fun getLanguages(): Response<List<Language>>

    @GET("search")
    suspend fun search(@Query("q") query: String): Response<SearchResult>

    @GET("search/suggest")
    suspend fun suggest(@Query("q") query: String): Response<List<String>>

    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): Response<AuthResponse>

    @POST("auth/register")
    suspend fun register(@Body request: RegisterRequest): Response<AuthResponse>

    @GET("favorites")
    suspend fun getFavorites(): Response<List<Channel>>

    @POST("favorites/toggle")
    suspend fun toggleFavorite(@Body request: FavoriteToggle): Response<ToggleResponse>

    @POST("favorites/check")
    suspend fun checkFavorite(@Body request: FavoriteToggle): Response<FavoriteResponse>
}
