package com.mamboleo.android.ui.screens.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.mamboleo.android.data.api.MamboleoRepository
import com.mamboleo.android.data.model.Channel
import com.mamboleo.android.data.model.Country
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class HomeUiState(
    val ugandaChannels: List<Channel> = emptyList(),
    val featuredChannels: List<Channel> = emptyList(),
    val countries: List<Country> = emptyList(),
    val onlineCount: Int = 0,
    val isLoading: Boolean = true,
    val error: String? = null,
)

@HiltViewModel
class HomeViewModel @Inject constructor(
    private val repository: MamboleoRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(HomeUiState())
    val uiState: StateFlow<HomeUiState> = _uiState

    init {
        loadHomeData()
    }

    fun loadHomeData() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, error = null)

            try {
                val ugandaResult = repository.getUgandaChannels()
                val countriesResult = repository.getCountries()

                val ugandaChannels = ugandaResult.getOrNull()?.channels ?: emptyList()
                val countries = countriesResult.getOrNull() ?: emptyList()
                val featured = ugandaChannels.shuffled().take(12)
                val onlineCount = ugandaChannels.size

                _uiState.value = HomeUiState(
                    ugandaChannels = ugandaChannels,
                    featuredChannels = featured,
                    countries = countries,
                    onlineCount = onlineCount,
                    isLoading = false,
                )
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = e.message ?: "Failed to load data"
                )
            }
        }
    }
}
