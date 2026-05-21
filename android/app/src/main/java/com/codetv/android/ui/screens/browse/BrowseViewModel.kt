package com.codetv.android.ui.screens.browse

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.codetv.android.data.api.CodetvRepository
import com.codetv.android.data.model.Category
import com.codetv.android.data.model.Channel
import com.codetv.android.data.model.Country
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class BrowseUiState(
    val channels: List<Channel> = emptyList(),
    val countries: List<Country> = emptyList(),
    val categories: List<Category> = emptyList(),
    val selectedCountry: String = "ug",
    val selectedCategory: String? = null,
    val searchQuery: String = "",
    val isLoading: Boolean = true,
    val error: String? = null,
)

@HiltViewModel
class BrowseViewModel @Inject constructor(
    private val repository: CodetvRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(BrowseUiState())
    val uiState: StateFlow<BrowseUiState> = _uiState

    init {
        loadFilters()
        loadChannels()
    }

    fun selectCountry(code: String) {
        _uiState.value = _uiState.value.copy(selectedCountry = code)
        loadChannels()
    }

    fun selectCategory(slug: String?) {
        _uiState.value = _uiState.value.copy(selectedCategory = slug)
        loadChannels()
    }

    fun search(query: String) {
        _uiState.value = _uiState.value.copy(searchQuery = query)
        loadChannels()
    }

    private fun loadFilters() {
        viewModelScope.launch {
            val countriesResult = repository.getCountries()
            val categoriesResult = repository.getCategories()
            _uiState.value = _uiState.value.copy(
                countries = countriesResult.getOrNull() ?: emptyList(),
                categories = categoriesResult.getOrNull() ?: emptyList(),
            )
        }
    }

    fun loadChannels() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, error = null)
            try {
                val state = _uiState.value
                val result = repository.getChannels(
                    country = state.selectedCountry,
                    category = state.selectedCategory,
                )
                val channels = result.getOrNull()?.let {
                    it.data ?: it.channels ?: emptyList()
                } ?: emptyList()
                _uiState.value = _uiState.value.copy(
                    channels = channels,
                    isLoading = false,
                )
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = e.message,
                )
            }
        }
    }
}
