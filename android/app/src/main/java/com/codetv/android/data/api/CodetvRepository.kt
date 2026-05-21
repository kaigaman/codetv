package com.codetv.android.data.api

import com.codetv.android.data.model.*
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class CodetvRepository @Inject constructor(
    private val api: CodetvApi
) {
    suspend fun getChannels(
        country: String? = null,
        category: String? = null,
        page: Int = 1
    ): Result<ChannelsResponse> = apiCall {
        api.getChannels(country = country, category = category, page = page)
    }

    suspend fun getChannel(id: Int): Result<Channel> = apiCall {
        api.getChannel(id)
    }

    suspend fun getUgandaChannels(): Result<ChannelsResponse> = apiCall {
        api.getUgandaChannels()
    }

    suspend fun getChannelsByCountry(code: String): Result<ChannelsResponse> = apiCall {
        api.getChannelsByCountry(code)
    }

    suspend fun getRandomChannel(): Result<Channel> = apiCall {
        api.getRandomChannel()
    }

    suspend fun getCountries(): Result<List<Country>> = apiCall {
        api.getCountries()
    }

    suspend fun getCategories(): Result<List<Category>> = apiCall {
        api.getCategories()
    }

    suspend fun search(query: String): Result<SearchResult> = apiCall {
        api.search(query)
    }

    suspend fun suggest(query: String): Result<List<String>> = apiCall {
        api.suggest(query)
    }

    suspend fun login(email: String, password: String): Result<AuthResponse> = apiCall {
        api.login(LoginRequest(email, password))
    }

    suspend fun register(name: String, email: String, password: String): Result<AuthResponse> = apiCall {
        api.register(RegisterRequest(name, email, password))
    }

    suspend fun getFavorites(): Result<List<Channel>> = apiCall {
        api.getFavorites()
    }

    suspend fun toggleFavorite(channelId: Int): Result<ToggleResponse> = apiCall {
        api.toggleFavorite(FavoriteToggle(channelId))
    }

    suspend fun checkFavorite(channelId: Int): Result<FavoriteResponse> = apiCall {
        api.checkFavorite(FavoriteToggle(channelId))
    }

    private suspend fun <T> apiCall(call: suspend () -> retrofit2.Response<T>): Result<T> {
        return try {
            val response = call()
            if (response.isSuccessful) {
                response.body()?.let { Result.success(it) }
                    ?: Result.failure(Exception("Empty response body"))
            } else {
                Result.failure(Exception("API error: ${response.code()} ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
